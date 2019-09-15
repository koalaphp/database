<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 26/11/2017
 * Time: 14:00
 */

// timezone init
date_default_timezone_set('Asia/Shanghai');
define('APP_ROOT', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));

// 定义Dao的生成路径
define('OUTPUT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Dao' . DIRECTORY_SEPARATOR . "%dbNamespace%");

define('ENVIRONMENT', 'develop');

// autoload
require APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// 自定义的自动加载
require 'custom_autoload.php';

$defaultDatabaseConfig = [
	'test' => [
		'master' => [
			'dbname' => 'test',
			'host' => '127.0.0.1',
			'port' => 3306,
			'user' => 'root',
			'pass' => 'ke0vfyex0yrtwjsaw6sazeivnyxegjcg',
			'charset' => 'utf8mb4',
		],
		'slaves' => [
			[
				'dbname' => 'test',
				'host' => '127.0.0.1',
				'port' => 3306,
				'user' => 'root',
				'pass' => 'ke0vfyex0yrtwjsaw6sazeivnyxegjcg',
				'charset' => 'utf8mb4',
			]
		]
	],
	'koala' => [
		'master' => [
			'dbname' => 'test',
			'host' => '127.0.0.1',
			'port' => 3306,
			'user' => 'root',
			'pass' => '123456',
			'charset' => 'utf8mb4',
		],
		'slaves' => [
			[
				'dbname' => 'test',
				'host' => '127.0.0.1',
				'port' => 3306,
				'user' => 'root',
				'pass' => '123456',
				'charset' => 'utf8mb4',
			]
		]
	],
	'koalaconfigerror' => [
		'master' => [
			'dbname' => 'test',
			'host' => '127.0.0.1',
			'port' => 3306,
			'user' => 'root',
			'charset' => 'utf8mb4',
		],
		'slaves' => [
			[
				'dbname' => 'test',
				'host' => '127.0.0.1',
				'port' => 3306,
				'user' => 'root',
				'pass' => '123456',
			]
		]
	]
];

// 初始化配置
Koala\Database\Connection::initDatabaseConfig($defaultDatabaseConfig);
// 打开输出拼接的SQL语句到屏幕的日志
Koala\Database\DBLogger::$isPrint = true;

class CurLogger {
	public function info($msg) {
		// .....
	}
}

// 设置dummy日志类
Koala\Database\DBLogger::setLogger(new CurLogger());
