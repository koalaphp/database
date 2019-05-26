<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 18/12/2017
 * Time: 00:57
 */

namespace Koala\Database;

use Koala\Database\Exception\DBConnectionException;
use Koala\Database\Exception\ErrorCode;

class Connection
{
	/**
	 * 默认的数据库配置
	 * @var array
	 */
	private static $defaultDatabaseConfig = [
		// 支持一主多从的MySQL配置
		'koala' => [
			'master' => [
				'dbname' => 'koala',
				'host' => '127.0.0.1',
				'port' => 3306,
				'user' => 'test',
				'pass' => '123456',
				'charset' => 'utf8mb4',
			],
			'slaves' => [
				[
					'dbname' => 'koala',
					'host' => '127.0.0.1',
					'port' => 3306,
					'user' => 'test',
					'pass' => '123456',
					'charset' => 'utf8mb4',
				]
			]
		]
	];

	/**
	 * 当前的数据库配置
	 * @var array
	 */
	protected static $curDatabaseConfig = [];

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
	}

	/**
	 * 初始化数据库的配置
	 * @codeCoverageIgnore
	 *
	 * @param array $config
	 */
	public static function initDatabaseConfig($config = []) {
		if (empty($config)) {
			self::$curDatabaseConfig = self::$defaultDatabaseConfig;
		} else {
			self::$curDatabaseConfig = $config;
		}
	}

	private static $simplePDOContainer = [];

	/**
	 * 获取对应数据库的连接对象 (只有真正发起SQL查询的时候，才会开始连接)
	 *
	 * @param $name
	 * @return \Koala\Database\SimplePDO
	 * @throws DBConnectionException
	 */
	public static function getConnection($name) {
		if (empty($name) || !is_string($name) || !isset(self::$curDatabaseConfig[$name])) {
			throw new DBConnectionException("database config not found: [{$name}] ", ErrorCode::INVALID_PARAM);
		}

		if (isset(self::$simplePDOContainer[$name]) && !empty(self::$simplePDOContainer[$name])) {
			return self::$simplePDOContainer[$name];
		}

		$curSingleDatabase = self::$curDatabaseConfig[$name];

		// 检查配置是否有效
		self::checkSingleDatabaseConfigValidation($name, $curSingleDatabase);

		self::$simplePDOContainer[$name] = new \Koala\Database\SimplePDO($name, $curSingleDatabase);
		return self::$simplePDOContainer[$name];
	}

	/**
	 * 检查配置是否有效
	 * @param $name
	 * @param $curSingleDatabase
	 */
	protected static function checkSingleDatabaseConfigValidation($name, $curSingleDatabase) {
		$needExistConfig = [
			'dbname',
			'host',
			'port',
			'user',
			'pass',
			'charset',
		];
		$isAllValid = false;
		if (isset($curSingleDatabase['master']) && isset($curSingleDatabase['slaves']) && is_array($curSingleDatabase['master']) && is_array($curSingleDatabase['slaves'])) {
			// 检查master
			$isMasterValid = true;
			foreach ($needExistConfig as $field) {
				if (!isset($curSingleDatabase['master'][$field])) {
					$isMasterValid = false;
					break;
				}
			}
			// 检查slaves
			$isSlavesValid = true;
			foreach ($curSingleDatabase['slaves'] as $tmpSlave) {
				foreach ($needExistConfig as $field) {
					if (!isset($tmpSlave[$field])) {
						$isSlavesValid = false;
						break;
					}
				}
			}
			if ($isMasterValid && $isSlavesValid) {
				$isAllValid = true;
			}
		}
		// 如果检查没有通过
		if ($isAllValid == false) {
			throw new DBConnectionException("database config not valid: [{$name}] ", ErrorCode::INVALID_PARAM);
		}
	}
}