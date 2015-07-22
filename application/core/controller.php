<?php
/**
 * 主控制器  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

abstract class Controller {
	protected $layout="public/layout.html";
	public function __construct(){
		$this->init();
		Hook::add("before_handler", function ($params=NULL){
			return $this->before_handler($params);
		});
		Hook::add("after_handler", function($params=NULL){
			return $this->after_handler($params);
		});
	}
	protected function init(){}
	protected function get_xhr() {}
	protected function post_xhr() {}
	protected function before_handler($params){}
	protected function after_handler($params){}
	abstract function get($params=NULL);
	abstract function post($params=NULL);
	
	protected function model($model) {
		$model = ucfirst($model);
		return new $model();
	}
	
	protected function display($template=NULL,$params=array()) {
		$viewDir = APP_PATH.DS.'views'.DS;
		$viewFile = $viewDir.strtolower(substr(get_class($this),0,-10)).DS.$template;
		try {
			$view = new View($viewFile,$viewDir.$this->layout);
			foreach ($params as $k=>$v) {
				$view->$k = $v;
			}
			return $view->render();
		}
		catch (Exception $e) {
			exit($e->getMessage());
		}
	}
	
	protected function redirect($url,$http_code="302") {
		if(!preg_match('/^https?:\/\//i', $url)) {
			$url = BASE_URL.ltrim($url,'/');
		}
		header("Location: ".$url, TRUE, $http_code);
		exit;
	}
}