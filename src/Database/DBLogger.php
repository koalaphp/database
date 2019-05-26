<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/10/6
 * Time: 00:28
 */

namespace Koala\Database;

class DBLogger
{
	protected static $logger = null;
	/**
	 * 设置当前的日志，推荐是继承自Monolog的logger类(需要实现了 "info" 方法)
	 *
	 * @param $curLogger
	 *
	 * @codeCoverageIgnore
	 */
	public static function setLogger($curLogger) {
		static::$logger = $curLogger;
	}

	// 是否直接打印输出日志
	public static $isPrint = false;

	/**
	 * 输出日志
	 *
	 * @param $sql
	 * @param array $params
	 */
	public static function info($sql, $params = []) {
		$logStr = sprintf("SQL: %s, Params: %s", $sql, json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		if (self::$logger != null) {
			self::$logger->info($logStr);
		}

		if (static::$isPrint) {
			echo $logStr . PHP_EOL;
		}
	}
}
