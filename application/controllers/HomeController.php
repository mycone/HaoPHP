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
		//use mode
		//$user = User::model()->findAll();
		//print_r($user);
		
		//$user = $this->model('user')->findAll();
		//print_r($user);
		
		//use template engine
		$this->display('index.html',array(
			'title' => 'HaoPHP 1.0',
			'array' => array(
				'1' => "First array item",
				'2' => "Second array item",
				'n' => "N-th array item",
			),
			'j' => 5,
			//print user table record
			//'user' => $user,
		));
		//echo "hello world! request method is get";
	}
	/**
	 * $_POST请求
	 * @see Controller::post()
	 */
	public function post($params=NULL) {
		echo "hello world! request method is post";
	}
}