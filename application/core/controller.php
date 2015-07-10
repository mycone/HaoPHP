<?php
/**
 * 主控制器  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

abstract class Controller {
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
}