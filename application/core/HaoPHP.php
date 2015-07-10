<?php
/**
 * HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

/**
 * HaoPHP version
 * @var string
 */
define('HaoPHP_VERSION', '1.0');

/**
 * HaoPHP baseUrl
 * @var string
 */
defined('BASE_URL') OR define('BASE_URL', (
	$baseUrl = str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])))
	? '/'.trim($baseUrl,'/').'/'
	: '/'
);

/**
 * HaoPHP核心类
 * @author ChenHao <dzswchenhao@126.com>
 */
class HaoPHP
{
	private static $importMap=array();
	private static $classMap=array();
	private static $routeMap=array();
	private static $_instance;
	private static $_coreClasses=array(
		'Controller' => '/core/controller.php',
		'Model' => '/core/model.php',
		'View' => '/core/view.php',
	);
	private function __clone() {}
	private function __construct($config=NULL) {
		if(is_string($config))
			$config=require($config);
		if (isset($config['import'])) 
			self::$importMap = $config['import'];
		if(isset($config['routes'])) 
			self::$routeMap = $config['routes'];
		if(isset($config['timeZone']))
			date_default_timezone_set($config['timeZone']);
	}
	
	public static function getInstance($config=NULL){
		if(!(self::$_instance instanceof HaoPHP)){
			self::$_instance = new HaoPHP($config);
		}
		return self::$_instance;
	}
	
	public function run() {
		return self::serve(self::$routeMap);
	}
	
	public static function autoload($className,$classMapOnly=false)
	{
		if(isset(self::$classMap[$className]))
			include(self::$classMap[$className]);
		elseif(isset(self::$_coreClasses[$className]))
			include(APP_PATH.self::$_coreClasses[$className]);
		elseif($classMapOnly)
			return false;
		elseif (self::$importMap) 
		{
			foreach (self::$importMap as $classDir) {
				$classDir = str_replace(array('.','*'), array('/',$className), $classDir);
				$classFile = APP_PATH.DS.$classDir.'.php';
				if(is_file($classFile)) {
					include($classFile);
				}
			}
			return class_exists($className,false) || interface_exists($className,false);
		}
		else {
			$classFile=APP_PATH.DS.$className.'.php';
			if(is_file($classFile)) {
				include($classFile);
			}
			return class_exists($className,false) || interface_exists($className,false);
		}
		return true;
	}
	
	private static function serve($routes)
	{
		Hook::exec('before_request', compact('routes'));
		$request_method = strtolower($_SERVER['REQUEST_METHOD']);
		$path_info = '/';
		if (! empty($_SERVER['PATH_INFO'])) {
			$path_info = $_SERVER['PATH_INFO'];
		} elseif (! empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php') {
			$path_info = $_SERVER['ORIG_PATH_INFO'];
		} else {
			if (! empty($_SERVER['REQUEST_URI'])) {
				$path_info = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
				if($path_info != '/')
					$path_info = str_replace(BASE_URL, "/", $path_info);
			}
		}
		$discovered_handler = null;
		$regex_matches = array();
		if (isset($routes[$path_info])) {
			$discovered_handler = $routes[$path_info];
		} elseif ($routes) {
			$tokens = array(
				':string' => '([a-zA-Z]+)',
				':number' => '([0-9]+)',
				':alpha'  => '([a-zA-Z0-9-_]+)'
			);
			foreach ($routes as $pattern => $handler_name) {
				$pattern = strtr($pattern, $tokens);
				if (preg_match('#^/?' . $pattern . '/?$#', $path_info, $matches)) {
					$discovered_handler = $handler_name;
					$regex_matches = $matches;
					break;
				}
			}
		}
		$result = null;
		$handler_instance = null;
		if ($discovered_handler) {
			if (is_string($discovered_handler)) {
				$handler_instance = new $discovered_handler();
			} elseif (is_callable($discovered_handler)) {
				$handler_instance = $discovered_handler();
			}
		}
		if ($handler_instance) {
			unset($regex_matches[0]);
			if (self::is_xhr_request() && method_exists($handler_instance, $request_method . '_xhr')) {
				header('Content-type: application/json');
				header('Expires: Mon, 26 Jul 1985 05:00:00 GMT');
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
				header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Cache-Control: post-check=0, pre-check=0', false);
				header('Pragma: no-cache');
				$request_method .= '_xhr';
			}
			if (method_exists($handler_instance, $request_method)) {
				Hook::exec('before_handler', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
				$result = call_user_func_array(array($handler_instance, $request_method), $regex_matches);
				Hook::exec('after_handler', compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
			} else {
				Hook::exec('404', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
			}
		} else {
			Hook::exec('404', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
		}
		Hook::exec('after_request', compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
	}
	
	private static function is_xhr_request()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}
	
}
/**
 * 勾子处理类
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */
class Hook
{
	private static $instance;
	private $hooks = array();
	private function __construct() {}
	private function __clone() {}
	public static function add($hook_name, $fn)
	{
		$instance = self::get_instance();
		$instance->hooks[$hook_name][] = $fn;
	}
	public static function exec($hook_name, $params = null)
	{
		$instance = self::get_instance();
		if (isset($instance->hooks[$hook_name])) {
			foreach ($instance->hooks[$hook_name] as $fn) {
				call_user_func_array($fn, array(&$params));
			}
		}
	}
	public static function get_instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new Hook();
		}
		return self::$instance;
	}
}

//自动加载类库
spl_autoload_register(array('HaoPHP','autoload'));
//请求开始
Hook::add("before_request", function($params){
	
});
//404页面
Hook::add("404", function(){
	header('HTTP/1.1 404 Not Found');
	header("status: 404 Not Found");
	include(APP_PATH.'/views/system/404_page.php');
});
//请求结束
Hook::add("after_request", function($params){
	
});









