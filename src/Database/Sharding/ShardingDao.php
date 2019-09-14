<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2019/9/15
 * Time: 00:07
 */

namespace Koala\Database\Sharding;

use Koala\Database\SimpleDao;

/**
 * 分库分表时连接数据库的ShardingDao
 *
 * Class ShardingDao
 * @package Koala\Database\Sharding
 */
class ShardingDao extends SimpleDao
{
	/**
	 * Sharding分库分表的表名，如：user01
	 * @var string
	 */
	protected $shardingTableName = "";

	protected static $singletonShardingInstance = null;
	/**
	 * 获取分库分表的Dao对象
	 * @param string $shardingTableName Sharding分库分表的表名，如：user01
	 * @return static
	 */
	public static function getShardingInstance($shardingTableName) {
		if (static::$singletonShardingInstance == null) {
			static::$singletonShardingInstance = new static($shardingTableName);
		}
		return static::$singletonShardingInstance;
	}
	/**
	 * ShardingDao
	 * @param string $shardingTableName Sharding分库分表的表名，如：user01
	 */
	protected function __construct($shardingTableName = "") {
		parent::__construct();
		$this->table = $shardingTableName;
	}
}