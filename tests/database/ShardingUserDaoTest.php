<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 31/12/2017
 * Time: 00:44
 */

use PHPUnit\Framework\TestCase;

class ShardingUserDaoTest extends TestCase
{
	protected static $curId1 = 0;
	protected static $curId2 = 0;
	protected static $curId3 = 0;

	public static function setUpBeforeClass()
	{
		echo "hello world" . PHP_EOL;
	}

	public function testInsertRow() {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$shardingUserDao = \Library\Dao\Test\ShardingUserDao::getShardingInstance("user01");
		$insertData = [
			'name' => 'han',
			'phone' => '13688888888',
			'password' => password_hash("123456", PASSWORD_DEFAULT),
			'money' => 10.12,
			'remark' => NULL,
			'status' => true,
			'create_time' => time(),
			'update_time' => time(),
		];
		$res = $shardingUserDao->insertRow($insertData, true);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "insertRow result not empty");
		static::$curId1 = $res;

		$insertData = [
			'name' => 'lai',
			'phone' => '13688888888',
			'password' => password_hash("123456", PASSWORD_DEFAULT),
			'money' => 100.12,
			'remark' => NULL,
			'status' => 1,
			'create_time' => time(),
			'update_time' => time(),
		];
		$res = $shardingUserDao->insertRow($insertData, true);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "insertRow result not empty");
		static::$curId2 = $res;

		$insertData = [
			'name' => 'lin',
			'phone' => '13688888888',
			'password' => password_hash("123456", PASSWORD_DEFAULT),
			'money' => 1000.12,
			'remark' => "hello world",
			'status' => 2,
			'create_time' => time(),
			'update_time' => time(),
		];
		$res = $shardingUserDao->insertRow($insertData, true);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "insertRow result not empty");
		static::$curId3 = $res;

		return true;
	}

	/**
	 * @depends testInsertRow
	 */
	public function testUpdateRow($isSucc) {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$shardingUserDao = \Library\Dao\Test\ShardingUserDao::getShardingInstance("user01");
		$updateData = [
			'phone' => '13688888899',
			'status' => 1,
			'update_time' => time(),
		];
		$res = $shardingUserDao->updateRow(static::$curId1, $updateData);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "testUpdateRow result not empty");

		return true;
	}

	/**
	 * @depends testUpdateRow
	 */
	public function testFindAllRecordCore() {
		$shardingDao = \Library\Dao\Test\ShardingUserDao::getShardingInstance("user01");
		$res = $shardingDao->findAllRecordCore();
		$this->assertNotEmpty($res, "ShardingUserDao result not empty");
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		return true;
	}

	/**
	 * @depends testFindAllRecordCore
	 */
	public function testDeleteRow($isSucc) {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$shardingDao = \Library\Dao\Test\ShardingUserDao::getShardingInstance("user01");
		$res = $shardingDao->deleteRow(static::$curId1);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");

		$res = $shardingDao->deleteRow(static::$curId2);
		echo $this->prettyJsonEncode($res). PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");

		$res = $shardingDao->deleteRow(static::$curId3);
		echo $this->prettyJsonEncode($res). PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");
	}

	private function prettyJsonEncode($data) {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}
