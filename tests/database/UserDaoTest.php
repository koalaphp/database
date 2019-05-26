<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 31/12/2017
 * Time: 00:44
 */

use PHPUnit\Framework\TestCase;

class UserDaoTest extends TestCase
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
		$userDao = \Library\Dao\Test\UserDao::getInstance();
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
		$res = $userDao->insertRow($insertData, true);
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
		$res = $userDao->insertRow($insertData, true);
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
		$res = $userDao->insertRow($insertData, true);
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
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		$updateData = [
			'phone' => '13688888899',
			'status' => 1,
			'update_time' => time(),
		];
		$res = $userDao->updateRow(static::$curId1, $updateData);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "testUpdateRow result not empty");
		return true;
	}

	/**
	 * @depends testUpdateRow
	 */
	public function testUpdateMultiRow($isSucc) {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		sleep(1);
		$updateData = [
			'phone' => '13688888877',
			'status' => 1,
			'update_time' => time(),
		];
		$res = $userDao->updateMultiRows([static::$curId1, static::$curId2], $updateData);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "testUpdateRow result not empty");
	}

	/**
	 * @depends testUpdateRow
	 * @expectedException \RuntimeException
	 */
	public function testUpdateError() {
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		$updateData = [
			'phone' => '13688888877',
			'status' => 1,
			'update_time' => time(),
		];
		$res = $userDao->updateMultiRows(static::$curId1, $updateData);
	}

	/**
	 * @depends testUpdateMultiRow
	 */
	public function testFindAllRecordCore() {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		$userDao->setSelectQueryFromSlave();
		$res = $userDao->findAllRecordCore();
		$this->assertNotEmpty($res, "testUpdateRow result not empty");

		/**
		 * 1. 基本的 AND 语句
		 * 		SQL: `name` = 'admin' AND `status` = 1
		 * 		$conditions = ['name' => 'lai', 'status' => 1];
		 */
		echo "1. 基本的 AND 语句" . PHP_EOL;
		$conditions = ['name' => 'lai', 'status' => 1];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 * 2. 某个字段带有不等式的查询
		 * 		SQL: `status` = 1 AND `create_time` >= 1514713145
		 *		$conditions = [
		 * 			'status' => 1,
		 * 			'create_time' => [
		 * 				">=" => 1514713145,
		 * 			],
		 * 		];
		 */
		echo "2. 某个字段带有不等式的查询" . PHP_EOL;
		$conditions = [
			'status' => 1,
			'create_time' => [
				">=" => time() - 3600,
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 * 3. 某个字段同时带有大于和小于的范围查询
		 * 		SQL: `status` = 1 AND `create_time` >= 1514713145 AND `create_time` <= 1514813145
		 *		$conditions = [
		 * 			'status' => 1,
		 * 			'create_time' => [
		 * 				">=" => 1514713145,
		 * 				"<=" => 1514813145
		 * 			]
		 * 		];
		 */
		echo "3. 某个字段同时带有大于和小于的范围查询" . PHP_EOL;
		$conditions = [
			'status' => 1,
			'create_time' => [
				">=" => time() - 3600,
				"<=" => time() + 3600,
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 * 4. 某个字段带有 IN 范围查询
		 *		SQL: `name` = 'han' AND `status` IN (1, 'test', 1.2) AND `status` >= 1
		 *		$conditions = [
		 * 			'name' => 'admin',
		 * 			'status' => [
		 * 				"IN" => [1, 'test', 1.2], // 支持整型、浮点型和字符串的IN查询
		 * 			],
		 * 		];
		 */
		echo "4. 某个字段带有 IN 范围查询" . PHP_EOL;
		$conditions = [
			'name' => 'han',
			'status' => [
				"IN" => [1, 'test', 1.2],
				">=" => 1,
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 * 5. 某个字段带有 LIKE 范围查询
		 *		SQL: `name` LIKE 'l%' AND `status` IN (1, 2)
		 *		$conditions = [
		 * 			'name' => [
		 * 				'LIKE' => 'l%'
		 * 			],
		 * 			'status' => [
		 * 				"IN" => [1, 2], // 支持整型、浮点型和字符串的IN查询
		 * 			],
		 * 		];
		 */
		echo "5. 某个字段带有 LIKE 范围查询" . PHP_EOL;
		$conditions = [
			'name' => [
				'LIKE' => 'l%',
			],
			'status' => [
				"IN" => [1, 2],
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 *  6. 带有某个或者多个字段的 OR 语句 (必须以"single_or_condition"为前缀)，OR条件中的所有条件都以 OR
		 *     来进行拼接。
		 *		SQL: `name` = 'admin' AND (`status` = 1 OR `id` IN (1, 2) OR `id` >= 20 )
		 *  	$conditions = [
		 * 			'name' => 'admin',
		 * 			'single_or_condition1' => [
		 * 				'status' => 1,
		 * 				'id' => [
		 * 					"IN" => [1, 2], // 只支持数组元素的IN查询
		 * 					">=" => 20,
		 * 				],
		 * 			],
		 * 		];
		 *
		 */
		echo "7. 带有某个或者多个字段的 AND 语句 (必须以\"single_and_condition\"为前缀)" . PHP_EOL;
		$conditions = [
			'name' => 'han',
			'single_or_condition1' => [
				'status' => 1,
				'id' => [
					"IN" => [1, 2], // IN查询
					">=" => 20,
				],
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 *  7. 带有某个或者多个字段的 AND 语句 (必须以"single_and_condition"为前缀)
		 *  SQL: (`name` = 'han' AND (`status` = 1 OR `id` >= 20))
		 *  $conditions = [
		 * 		'single_and_condition1' => [
		 *			'name' => 'han',
		 *			'single_or_condition1' => [
		 *				'status' => 1,
		 *				'id' => [">=" => 20],
		 *			],
		 *		],
		 *  ];
		 */
		echo "7. 带有某个或者多个字段的 AND 语句 (必须以\"single_and_condition\"为前缀)" . PHP_EOL;
		$conditions = [
			'single_and_condition1' => [
				'name' => 'han',
				'single_or_condition1' => [
					'status' => 1,
					'id' => [
						"IN" => [1, 2], // IN查询
						">=" => 20,
					],
				],
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		$conditions = [
			'single_or_condition1' => [
				'name' => 'han',
				'single_and_condition1' => [
					'status' => 1,
					'id' => [
						"IN" => [1, 2], // IN查询
						">=" => 20,
					],
				],
			],
		];
		echo var_export($conditions) . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		/**
		 *  8. 带有GROUP BY 语句的聚合查询语句
		 *  SQL: (`status` >= 1 AND `id` >= 20) GROUP BY `status`
		 *  $conditions = [
		 * 		'status' => [">=" => 1],
		 * 		'id' => [">=" => 20],
		 * 		'group by' => 'status',
		 *  ];
		 */
		$conditions = [
			'status' => [">=" => 1],
			'id' => [">=" => 20],
			'group by' => 'status',
		];
		echo var_export($conditions) . PHP_EOL;
		echo "8. 带有GROUP BY 语句的聚合查询语句" . PHP_EOL;
		$res = $userDao->findAllRecordCore($conditions, "", 0, -1, ["status", "count(status) as num"]);
		echo $this->prettyJsonEncode($res) . PHP_EOL;

		// 切换为从master查询
		$userDao->setSelectQueryFromMaster();

		echo "total count: " . $userDao->findCount(['id' => static::$curId1]) . PHP_EOL;
		$result = $userDao->findOne(['id' => ['=' => static::$curId1]]);
		echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

		$this->assertTrue(true);

		$result = $userDao->findOne(['id' => ['=' => 0]]);
		$this->assertNull($result);

		$this->execCreateGenerator();

		return true;
	}

	/**
	 * @depends testUpdateMultiRow
	 */
	public function testSelectNull() {
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		// 覆盖一些刁钻的case
		$res = $userDao->findAllRecordCore(["name" => NULL]);
		$this->assertEmpty($res);
	}

	/**
	 * @depends testUpdateMultiRow
	 * @expectedException \RuntimeException
	 */
	public function testWhereInError() {
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		$res = $userDao->findAllRecordCore(["status" => ["IN" => []]]);
	}

	protected function execCreateGenerator() {
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		foreach ($userDao->createGenerator([]) as $userObj) {
			echo $this->prettyJsonEncode($userObj) . PHP_EOL;
		}

		foreach ($userDao->createGenerator(["id" => ["<" => 2]]) as $userObj) {
			echo $this->prettyJsonEncode($userObj) . PHP_EOL;
		}
	}

	/**
	 * @depends testFindAllRecordCore
	 */
	public function testDeleteRow($isSucc) {
		echo PHP_EOL . "Start test [" . __METHOD__ . "]" . PHP_EOL;
		$userDao = \Library\Dao\Test\UserDao::getInstance();
		$res = $userDao->deleteRow(static::$curId1);
		echo $this->prettyJsonEncode($res) . PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");

		$res = $userDao->deleteRow(static::$curId2);
		echo $this->prettyJsonEncode($res). PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");

		$res = $userDao->deleteRow(static::$curId3);
		echo $this->prettyJsonEncode($res). PHP_EOL;
		$this->assertNotEmpty($res, "deleteRow result not empty");
	}

	private function prettyJsonEncode($data) {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}
