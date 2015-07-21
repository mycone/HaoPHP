<?php
/**
 * 主模型类  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

class Model {
	/**
	 * @object, PDO statement object
	 */
	private $query;
	
	/**
	 * @array, The parameters of the SQL query
	 */
	private $parameters;
	
	/**
	 * @var string 数据表名，由子类指定
	 */
	protected $_table = '';
	
	/**
	 * @var string 数据表主键名，由子类指定
	 */
	protected $_pkey = 'id';
	
	/**
	 * @var bool 是否对查询做缓存
	 * 注意：
	 *      本缓存功能只用于读操作，即仅用于get系列的方法在内部执行过程中将内容从db缓存到cache。
	 *      写操作不对cache进行操作，所以数据更新事务需要另行处理。
	 */
	protected $_query_cache = FALSE;
	
	/**
	 * 查询缓存的过期时间。默认为一天
	 * @var int
	 */
	protected $_query_cache_ttl = 86400;
	
	/**
	 * PDO对象
	 * @var object
	 */
	public static $db;
	
	/**
	 * 主要用于实例化当前模型
	 * @var Object
	 */
	private static $_models=array();
	
	/**
	 * 分页大小
	 * 会影响在get_page()方法返回的数据条数
	 * @var int
	 */
	protected $_pagesize = 12;
	
	/**
	 * 属性变量数组
	 * @var array
	 */
	public $variables = array();
	
	/**
	 * 构造函数
	 * @param array $config
	 */
	public function __construct(array $config=array()) {
		$this->_init();
		if(empty($config))
			$config = HaoPHP::config('db');
		self::$db = pPDO::getInstance($config);
		$this->parameters = array();
		if($this->_query_cache) {
			HaoPHP::import('cache.drivers.FileCache');
			$this->cache = new FileCache(array('cache_dir'=>APP_PATH.'/runtime/cache'));
		}
		if(!empty($config['tablePrefix']) && preg_match('/\{(.*?)\}/', $this->_table, $match))
			$this->_table = $config['tablePrefix'].$match[1];
	}
	
	/**
	 * 用于子控制器重写的初始化函数；子控制器可编写自定义的初始化代码；
	 */
	protected function _init(){}
	
	/**
	 * 传入参数，生成cache使用的key
	 * @return string
	 */
	protected function cache_key(){
		return md5($this->_table.':'.json_encode(func_get_args()));
	}
	
	/**
	 * 计算缓存ttl，传入int（指秒数）|timestamp（时间戳）|“day”,“week”,“month”,“year”等，返回从现在开始计秒的ttl
	 * @param $cache_ttl
	 * @return int
	 */
	public function cache_ttl_sec($cache_ttl=NULL){
		if(is_int($cache_ttl)){
			$cache_ttl = intval($cache_ttl);
			//判断为时间戳
			if($cache_ttl>time()){
				$ttl_sec =  $cache_ttl - time();
			}
			//判断为秒数
			else{
				$ttl_sec = $cache_ttl;
			}
		}
		//判断为字符串
		elseif(is_string($cache_ttl)){
			switch($cache_ttl){
				case 'day';
				$ttl_sec = strtotime('tomorrow') - time();
				break;
				case 'week';
				$ttl_sec = strtotime('next monday') - time();
				break;
				case 'month';
				$ttl_sec = mktime(23,59,59,date("m"),date("t"),date("Y")) - time();
				break;
				case 'year';
				$ttl_sec = mktime(0,0,0,1,1,date("Y")+1) - time();
				break;
			}
		}
		if(!isset($ttl_sec)){
			$ttl_sec = $this->_query_cache_ttl;
		}
		return $ttl_sec;
	}
	
	/**
	 * 初始化查询
	 * @param String $query
	 * @param string $parameters
	 */
	private function query_init($query,$parameters = "")
	{
		try {
			//分析查询
			$this->query = self::$db->prepare($query);
			
			# 将查询参数进行组装
			$this->bind_more($parameters);
			# 绑定查询参数
			if(!empty($this->parameters)) {
				foreach($this->parameters as $param)
				{
					$parameters = explode("\x7F",$param);
					$this->query->bindParam($parameters[0],$parameters[1]);
				}
			}
			# Execute SQL
			$this->query->execute();
		}
		catch(PDOException $e)
		{
			die("Error:".$e->getMessage()."<br/>SQL:$query");
		}
		# Reset the parameters
		$this->parameters = array();
	}
	
	/**
	 * 绑定单个参数
	 * @param String $para
	 * @param String $value
	 */
	public function bind($para, $value)
	{
		$this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . utf8_encode($value);
	}
	
	/**
	 * 绑定多个参数
	 * @param array $parray
	 */
	public function bind_more($parray)
	{
		if(empty($this->parameters) && is_array($parray)) {
			$columns = array_keys($parray);
			foreach($columns as $i => &$column)	{
				$this->bind($column, $parray[$column]);
			}
		}
	}
	
	/**
	 * 如果执行的SQL为SELECT或SHOW将返回执行结果行的数组
	 * 如果执行的SQL为DELETE,INSERT或UPDATE将返回影响记录的行数
	 * 
	 *  @param  string $query
	 *	@param  array  $params
	 *	@param  int    $fetchmode
	 *	@return mixed
	 */
	public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC) {
		$query = trim ( $query );
		$this->query_init ( $query, $params );
		$rawStatement = explode ( " ", $query );
		
		$statement = strtolower ( $rawStatement [0] );
		
		if ($statement === 'select' || $statement === 'show') {
			//若开启了缓存功能首先查询缓存
			if($this->_query_cache){
				$cache_key = $this->cache_key($query, $params);
				$cache_result = $this->cache->get($cache_key);
				//若查到了内容，则返回内容
				if($cache_result!==FALSE){
					return $cache_result;
				}
				//未命中，则继续查数据库 ↓
			}
			$result = $this->query->fetchAll ( $fetchmode );
			//若开启了缓存功能，则写回缓存
			if($this->_query_cache){
				$this->cache->save($cache_key,$result,$this->cache_ttl_sec());
			}
			return $result;
		} 
		elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
			return $this->query->rowCount ();
		} 
		else {
			return NULL;
		}
	}
	
	/**
	 *  返回最后写入的数据ID(通常为自增主键).
	 *  @return string
	 */
	public function last_insert_id() {
		return self::$db->lastInsertId();
	}
	
	/**
	 *	从查询结果中返回某一列
	 *
	 *	@param  string $query
	 *	@param  array  $params
	 *	@return array
	 */
	public function column($query,$params = null)
	{
		$this->query_init($query,$params);
		$Columns = $this->query->fetchAll(PDO::FETCH_NUM);
		
		$column = null;
		foreach($Columns as $cells) {
			$column[] = $cells[0];
		}
		return $column;
			
	}
	
	/**
	 *	从查询结果中返回一行数据
	 *
	 *	@param  string $query
	 *	@param  array  $params
	 *  @param  int    $fetchmode
	 *	@return array
	 */
	public function row($query,$params = null,$fetchmode = PDO::FETCH_ASSOC)
	{
		$this->query_init($query,$params);
		return $this->query->fetch($fetchmode);
	}
	
	/**
	 *	Returns the value of one single field/column
	 *
	 *	@param  string $query
	 *	@param  array  $params
	 *	@return string
	 */
	public function single($query,$params = null)
	{
		$this->query_init($query,$params);
		return $this->query->fetchColumn();
	}
	
	
	/**
	 * 魔术方法 设置一个属性
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name,$value){
		if(strtolower($name) === $this->_pkey) {
			$this->variables[$this->_pkey] = $value;
		}
		else {
			$this->variables[$name] = $value;
		}
	}
	/**
	 * 魔术方法   获取一个属性
	 * @param string $name
	 * @return NULL
	 */
	public function __get($name)
	{
		if(is_array($this->variables)) {
			if(array_key_exists($name,$this->variables)) {
				return $this->variables[$name];
			}
		}
		$trace = debug_backtrace();
		trigger_error(
		'Undefined property via __get(): ' . $name .
		' in ' . $trace[0]['file'] .
		' on line ' . $trace[0]['line'],
		E_USER_NOTICE);
		return null;
	}
	
	/**
	 * 返回当前模型
	 * 每一个模型实例必须重写本方法，示例如下：
	 * <pre>
	 * public static function model($className=__CLASS__)
	 * {
	 *     return parent::model($className);
	 * }
	 * </pre>
	 * @param system $className
	 * @return object
	 */
	public static function model($className=__CLASS__)
	{
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else
			return self::$_models[$className]=new $className();
	}
	
	/**
	 * CRUD操作  save()方法更新数据
	 * 
	 * @param string $id
	 * @return Ambigous <mixed, NULL>
	 */
	public function save($id = "0") {
		$this->variables [$this->_pkey] = (empty ( $this->variables [$this->_pkey] )) ? $id : $this->variables [$this->_pkey];
		$fieldsvals = '';
		$columns = array_keys ( $this->variables );
		foreach ( $columns as $column ) {
			if ($column !== $this->_pkey)
				$fieldsvals .= $column . " = :" . $column . ",";
		}
		$fieldsvals = substr_replace ( $fieldsvals, '', - 1 );
		if (count ( $columns ) > 1) {
			$sql = "UPDATE " . $this->_table . " SET " . $fieldsvals . " WHERE " . $this->_pkey . "= :" . $this->_pkey;
			return $this->query ( $sql, $this->variables );
		}
		return NULL;
	}
	
	/**
	 * CRUD操作  create()方法新增记录
	 * @return int|boolean
	 */
	public function create() {
		$bindings   	= $this->variables;
		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array(implode(",",$fields),":" . implode(",:",$fields));
			$sql 		= "INSERT INTO ".$this->_table." (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else {
			$sql 		= "INSERT INTO ".$this->_table." () VALUES ()";
		}
		if($this->query($sql,$bindings)>0) {
			return $this->last_insert_id();
		}
		return FALSE;
	}
	
	/**
	 * CRUD操作  delete()方法删除记录
	 * @return boolean|int
	 */
	public function delete($id = "") {
		$id = (empty($this->variables[$this->_pkey])) ? $id : $this->variables[$this->_pkey];
		if(!empty($id)) {
			$sql = "DELETE FROM " . $this->_table . " WHERE " . $this->_pkey . "= :" . $this->_pkey. " LIMIT 1" ;
			return $this->query($sql,array($this->_pkey=>$id));
		}
		return FALSE;
	}
	
	/**
	 * CRUD操作  find()方法查询记录
	 * @return array|false
	 */
	public function find($id = "") {
		$id = (empty($this->variables[$this->_pkey])) ? $id : $this->variables[$this->_pkey];
		if(!empty($id)) {
			$sql = "SELECT * FROM " . $this->_table ." WHERE " . $this->_pkey . "= :" . $this->_pkey . " LIMIT 1";
			return $this->variables = $this->row($sql,array($this->_pkey=>$id));
		}
		else {
			return false;
		}
	}
	
	/**
	 * CRUD操作  findAll()方法查询所有记录
	 * @return array|NULL
	 */
	public function findAll(){
		return $this->query("SELECT * FROM " . $this->_table);
	}
	
	/**
	 * CRUD操作  min()方法查询某列最小值
	 * @param string $field
	 * @return string
	 */
	public function min($field)  {
		if($field)
			return $this->single("SELECT min(" . $field . ")" . " FROM " . $this->_table);
	}
	
	/**
	 * CRUD操作  max()方法查询某列最大值
	 * @param string $field
	 * @return string
	 */
	public function max($field)  {
		if($field)
			return $this->single("SELECT max(" . $field . ")" . " FROM " . $this->_table);
	}
	
	/**
	 * CRUD操作  avg()方法 查询某列平均值
	 * @param string $field
	 * @return string
	 */
	public function avg($field)  {
		if($field)
			return $this->single("SELECT avg(" . $field . ")" . " FROM " . $this->_table);
	}
	
	/**
	 * CRUD操作  sum()方法 某列求合
	 * @param string $field
	 * @return string
	 */
	public function sum($field)  {
		if($field)
			return $this->single("SELECT sum(" . $field . ")" . " FROM " . $this->_table);
	}
	
	/**
	 * CRUD操作  count()方法 统计记录条数
	 * @param string $field
	 * @return string
	 */
	public function count($field)  {
		if($field)
			return $this->single("SELECT count(" . $field . ")" . " FROM " . $this->_table);
	}
	
	/**
	 * 设置分页大小
	 * @param $pagesize
	 */
	public function set_pagesize($pagesize){
		$this->_pagesize = $pagesize;
	}
	
	/**
	 * 按页获取数据(分页)
	 * @param string $args
	 * @param number $pagenum
	 * @param string $pagesize
	 */
	public function get_page($args=NULL, $pagenum=1, $pagesize=NULL) {
		//待完善
	}
	
	
}
/**
 * PDO实例
 * @author ChenHao
 *
 */
class pPDO {
	private static $instance;
	private function __construct() {}
	private function __clone() {}
	public static function getInstance($config=array()) {
		if (!self::$instance) {
			try {
				if(!isset($config['charset'])) $config['charset'] = "utf8";
				self::$instance = new PDO($config['connectionString'], $config['username'], $config['password'],array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"));
				self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e) {
				die("DB Connect Error:{$e->getMessage()}");
			}
		}
		return self::$instance;
	}
	
	final public static function __callStatic( $chrMethod, $arrArguments ) {
		$objInstance = self::getInstance(); 
        return call_user_func_array(array($objInstance, $chrMethod), $arrArguments);
	}
}