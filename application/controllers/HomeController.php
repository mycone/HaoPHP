<?php
/**
 * 默认首页控制器  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package controllers
 * @since 1.0
 */

class HomeController extends Controller {
	/**
	 * $_GET请求
	 * @see Controller::get()
	 */
	public function get($params=NULL) {
		echo "hello world! request method is get";
	}
	/**
	 * $_POST请求
	 * @see Controller::post()
	 */
	public function post($params=NULL) {
		echo "hello world! request method is post";
	}
}