<?php
/**
 * HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

header("content-Type: text/html; charset=utf-8");
define('DS', DIRECTORY_SEPARATOR);
define('SYSTEM_PATH',str_replace(array('\\', '\\\\'), DS, dirname(__FILE__)));
define('APP_PATH', SYSTEM_PATH.DS.'application');
//载入配置
$config = require(APP_PATH.DS.'config/main.php');
//载入核心文件并运行框架
require_once APP_PATH.DS.'core/HaoPHP.php';
HaoPHP::getInstance($config)->run();