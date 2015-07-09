<?php
/**
 * 主配置文件  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package config
 * @since 1.0
 */

return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'HaoPHP',
	'theme'=>'default',
	'timeZone'=>'Asia/Shanghai',
	'import'=>array(
		'application.models.*',
		'application.controllers.*',
	),
	'db' => require(dirname(__FILE__).DIRECTORY_SEPARATOR.'db.php'),
	'routes' => require(dirname(__FILE__).DIRECTORY_SEPARATOR.'routes.php'),
);