<?php
/**
 * 主模型类  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

class Model {
	private static $instance;
	private function __construct() {}
	private function __clone() {}
	public static function getInstance($config=array()) {
		if (!self::$instance) {
			self::$instance = new PDO($config['connectionString'], $config['username'], $config['password']);
			self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		return self::$instance;
	}
	
	final public static function __callStatic( $chrMethod, $arrArguments ) {
		$objInstance = self::getInstance(); 
        return call_user_func_array(array($objInstance, $chrMethod), $arrArguments);
	}
}