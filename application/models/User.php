<?php
/**
 * 用户模型示例
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package models
 * @since 1.0
 * 
 * //以下为表结构示例
 * 
 CREATE TABLE `user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID，自动编号',
	`name` VARCHAR(50) NULL DEFAULT NULL COMMENT '用户名',
	`passwd` CHAR(40) NULL DEFAULT NULL COMMENT '登录密码',
	`salt` CHAR(8) NOT NULL COMMENT '盐',
	`status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0，新注册未验证；1，验证中；2，验证通过；',
	`mail` VARCHAR(50) NULL DEFAULT NULL COMMENT '邮箱地扯',
	`phone` VARCHAR(50) NULL DEFAULT NULL COMMENT '手机号码',
	`avatar` VARCHAR(50) NULL DEFAULT NULL COMMENT '头像',
	`reg_time` DATETIME NULL DEFAULT NULL COMMENT '注册时间',
	`sign_time` DATETIME NULL DEFAULT NULL COMMENT '登陆时间',
	`sign_ip` VARCHAR(20) NULL DEFAULT NULL COMMENT '登陆ip',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`),
	INDEX `mail` (`mail`),
	INDEX `phone` (`phone`),
	INDEX `status` (`status`)
)
COMMENT='用户信息表'
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1;
 */


class User extends Model {
	//表名必须定义，若设置有表前缀且此处加{}则使用表前缀，否则不使用表前缀
	protected $_table = "user";
	//主键必须定义
	protected $_pkey = "id";
	
	//缓存开关，只针对select
	protected $_query_cache = TRUE;
	/**
	 * 实例化本模型
	 * @param system $className
	 * @return <object>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	} 
}