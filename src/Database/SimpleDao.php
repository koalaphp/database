<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 26/12/2017
 * Time: 23:51
 */

namespace Koala\Database;

use Koala\Database\Exception\DBParamException;
use Koala\Database\Exception\ErrorCode;

class SimpleDao
{
	// 连接的数据库
	protected $database = "";
	// 表名
	protected $table = "";
	// 主键字段名
	protected $primaryKey = "id";
	// 字段列表
	protected $fieldList = [];

	// 映射的Model 类
	protected $modelClass = \stdClass::class;

	// select查询的时候是否使用master，默认select也是查询master
	protected $isMaster = true;

	// WHERE条件拼接语句中单个OR语句的前缀
	protected static $singleOrConditionPrefix = "single_or_condition";
	// WHERE条件拼接语句中单个AND语句的前缀
	protected static $singleAndConditionPrefix = "single_and_condition";

	protected static $singletonInstance = null;
	/**
	 * @return static
	 */
	public static function getInstance() {
		if (static::$singletonInstance == null) {
			static::$singletonInstance = new static();
		}
		return static::$singletonInstance;
	}

	protected function __construct() {
	}

	/**
	 * 设置select查询的时候使用master
	 */
	public function setSelectQueryFromMaster() {
		$this->isMaster = true;
	}

	/**
	 * 设置select查询的时候使用slave
	 */
	public function setSelectQueryFromSlave() {
		$this->isMaster = false;
	}

	/**
	 * 获取该数据库的主库的 PDO 连接
	 *
	 * @return \PDO
	 */
	public function getMasterPdo() {
		$masterPdo = \Koala\Database\Connection::getConnection($this->database)->getMasterHandler();
		return $masterPdo;
	}

	/**
	 * 获取该数据库的从库的 PDO 连接
	 * @return \PDO
	 */
	public function getSlavePdo() {
		$slavePdo = \Koala\Database\Connection::getConnection($this->database)->getSlaveHandler();
		return $slavePdo;
	}

	/**
	 * 查询符合条件的某一个对象
	 * $conditions 的详细用法参见findAllCore
	 * @param array $conditions
	 * @param string $sort
	 * @return \stdClass|null
	 */
	public function findOne($conditions = [], $sort = "id desc") {
		$curList = $this->findAllRecordCore($conditions, $sort, 0, 1);
		if (count($curList) == 1) {
			return $curList[0];
		} else {
			return null;
		}
	}

	/**
	 * 获取该查询条件下的总数
	 * @param array $conditions
	 * @return int
	 */
	public function findCount($conditions = []) {
		$fieldsStr = "count(`{$this->primaryKey}`) as totalCount";
		$whereStr = "`{$this->primaryKey}` > 0";
		$bindValueList = [];
		if (is_array($conditions) && !empty($conditions)) {
			list($whereStr, $bindValueList) = $this->buildWhereStr($conditions);
		}
		$selectSql = sprintf("SELECT %s FROM `%s` WHERE %s",
			$fieldsStr,
			$this->table,
			$whereStr
		);
		$curConn = \Koala\Database\Connection::getConnection($this->database);
		$curSelectPdo = $this->isMaster ? $curConn ->getMasterHandler() : $curConn->getSlaveHandler();
		$statement = $curSelectPdo->prepare($selectSql);
		if (!empty($bindValueList)) {
			// 如果有进行参数绑定，执行绑定操作
			$this->bindArrayValue($statement, $bindValueList);
		}

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($selectSql, $bindValueList);
		}

		$statement->execute();
		$result = $statement->fetch(\PDO::FETCH_ASSOC);

		$statement = null;

		return intval(isset($result['totalCount']) ? $result['totalCount']: 0);
	}

	/**
	 * 用来进行查询获取记录的方法
	 *
	 * $conditions 的用法介绍举例
	 *
	 * 通过查询条件构造 WHERE SQL 语句的说明及示例
	 * (拼接查询条件的时候如果字段名出错，仍然能够拼成合法查询条件，只是执行之后将会报错，为的是方便排查)
	 *
	 * 1. 基本的 AND 语句
	 * 		SQL: `name` = 'admin' AND `status` = 1
	 * 		$conditions = ['name' => 'admin', 'status' => 1];
	 *
	 * 2. 某个字段带有不等式的查询
	 * 		SQL: `status` = 1 AND `create_time` >= 1514713145
	 *		$conditions = [
	 * 			'status' => 1,
	 * 			'create_time' => [
	 * 				">=" => 1514713145,
	 * 			],
	 * 		];
	 *
	 * 3. 某个字段同时带有大于和小于的范围查询
	 * 		SQL: `status` = 1 AND `create_time` >= 1514713145 AND `create_time` <= 1514813145
	 *		$conditions = [
	 * 			'status' => 1,
	 * 			'create_time' => [
	 * 				">=" => 1514713145,
	 * 				"<=" => 1514813145
	 * 			]
	 * 		];
	 *
	 * 4. 某个字段带有 IN 范围查询
	 *		SQL: `name` = 'admin' AND `status` IN (1, 2)
	 *		$conditions = [
	 * 			'name' => 'admin',
	 * 			'status' => [
	 * 				"IN" => [1, 2], // 支持整型、浮点型和字符串的IN查询
	 * 			],
	 * 		];
	 *
	 * 5. 某个字段带有 LIKE 范围查询
	 *		SQL: `name` LIKE '%adm%' AND `status` IN (1, 2)
	 *		$conditions = [
	 * 			'name' => [
	 * 				'LIKE' => '%adm%'
	 * 			],
	 * 			'status' => [
	 * 				"IN" => [1, 2], // 支持整型、浮点型和字符串的IN查询
	 * 			],
	 * 		];
	 *
	 *
	 *  6. 带有某个或者多个字段的 OR 语句 (必须以"single_or_condition"为前缀)
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
	 *  7. 带有某个或者多个字段的 AND 语句 (必须以"single_and_condition"为前缀)
	 *  SQL: (`status` = 2 AND (`status` = 1 OR `id` >= 20))
	 *  $conditions = [
	 * 		'single_and_condition1' => [
	 *			'status' => 2,
	 *			'single_or_condition1' => [
	 *				'status' => 1,
	 *				'id' => [">=" => 20],
	 *			],
	 *		],
	 *  ];
	 *
	 *  8. 带有GROUP BY 语句的聚合查询语句
	 *  SQL: (`status` >= 1 AND `id` >= 20) GROUP BY `status`
	 *  $conditions = [
	 * 		'status' => [">=" => 1],
	 * 		'id' => [">=" => 20],
	 * 		'group by' => 'status',
	 *  ];
	 *
	 *
	 * @param array $conditions
	 * @param string $sort 可选 排序方式 默认为根据主键降序，当 $sort 为空字符串的时候，表示不进行 order by。
	 * @param int $offset 可选 偏移量 默认：0
	 * @param int $limit 可选 单次查询出的个数 默认：20，特殊情况当limit 等于 -1 时表示查找全部。
	 * @param array $fieldList 可选 查询的字段列表 默认查出所有字段
	 * @return array
	 */
	public function findAllRecordCore($conditions = [], $sort = "id desc", $offset = 0, $limit = 20, $fieldList = []) {
		// 默认查询所有字段 (方便统一通过对象来进行操作)
		$fields = array_keys($this->fieldList);
		$fieldsStr = "`" . implode("`, `", $fields) . "`";
		// 使用者知道自己要查什么字段，信任开发者，直接进行拼接
		if (is_array($fieldList) && !empty($fieldList)) {
			$fieldsStr = implode(",", $fieldList);
		}

		$whereStr = "`{$this->primaryKey}` > 0";
		$bindValueList = [];
		if (is_array($conditions) && !empty($conditions)) {
			// 拼接GROUP BY条件语句
			$groupByStr = "";
			foreach ($conditions as $key => $val) {
				if (strtoupper($key) == "GROUP BY" && is_string($val) && !empty($val)) {
					$groupByStr = " GROUP BY {$val}";
					unset($conditions[$key]);
					break;
				}
			}

			// 拼接其他普通的where查询条件
			list($whereStr, $bindValueList) = $this->buildWhereStr($conditions);

			// 将group by语句放在where条件后面
			$whereStr = $whereStr . $groupByStr;
		}

		$offset = intval($offset);
		$limit = intval($limit);

		// 可自定义各种排序
		$sortStr = !empty($sort) ? " ORDER BY $sort" : "";

		// 拼接limit offset语句
		$limitStr = sprintf(" LIMIT %s,%s", $offset, $limit);
		if ($limit == -1) {
			$limitStr = "";
		}

		$selectSql = sprintf("SELECT %s FROM `%s` WHERE %s%s%s",
			$fieldsStr,
			$this->table,
			$whereStr,
			$sortStr,
			$limitStr
		);

		$curSelectPdo = $this->isMaster ? $this->getMasterPdo() : $this->getSlavePdo();
		$statement = $curSelectPdo->prepare($selectSql);
		if (!empty($bindValueList)) {
			// 如果有进行参数绑定，执行绑定操作
			$this->bindArrayValue($statement, $bindValueList);
		}

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($selectSql, $bindValueList);
		}

		$statement->execute();
		$result = $statement->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);
		$statement = null;

		return $result;
	}

	/**
	 * 可以实现循环获取所有行的功能（适用于在定时任务中扫描一整张表）
	 *
	 * 生成指定条件的迭代器
	 * @param array $conditions
	 * @param int $numPerTime
	 * @param bool $isBatch false: 一次返回1个结果，true：一次批量返回 {$numPerTime} 个结果
	 * @return \Generator
	 */
	public function createGenerator($conditions = [], $numPerTime = 100, $isBatch = false) {
		$minIdObj = $this->findOne($conditions, "`{$this->primaryKey}` asc");
		$maxIdObj = $this->findOne($conditions, "`{$this->primaryKey}` desc");

		// 避免没有数据的情况
		$minId = 0;
		$maxId = -1;
		if (!empty($minIdObj)) {
			$minId = intval($minIdObj->{$this->primaryKey});
		}
		if (!empty($maxIdObj)) {
			$maxId = intval($maxIdObj->{$this->primaryKey});
		}

		$curId = $minId;
		while($curId <= $maxId) {
			$newConditions = $conditions;
			// 增加每次的根据ID获取的条件
			// 如果没有主键作为条件，那么直接合并
			if (!isset($newConditions[$this->primaryKey])) {
				$newConditions[$this->primaryKey] = [">=" => $curId];
			} else {
				// 说明存在以主键为条件的查询
				$newConditions['single_and_condition_' . $this->primaryKey] = [$this->primaryKey => [">=" => $curId]];
			}
			$curList = $this->findAllRecordCore($newConditions, "`{$this->primaryKey}` asc", 0, $numPerTime);
			// 更新 $curId
			if (!empty($curList) && is_array($curList)) {
				$curListCount = count($curList);
				$curItem = $curList[$curListCount - 1];
				$curId = intval($curItem->{$this->primaryKey});
			}
			if ($isBatch) {
				yield $curList;
			} else {
				foreach ($curList as $curItem) {
					yield $curItem;
				}
			}
			$curId++;
		}
	}

	/**
	 * 插入一行数据到数据库中
	 *
	 * @param $insertData array 插入的数组
	 * @param bool $isReturnId 是否返回插入的ID，默认是返回
	 * @return int
	 */
	public function insertRow($insertData, $isReturnId = true) {
		$fieldsStr = "`" . implode("`, `", array_keys($insertData)) . "`";
		$fieldValues = "";
		$bindValueList = [];
		if (is_array($insertData) && !empty($insertData)) {
			foreach ($insertData as $key => $val) {
				$bindValueList[":" . $key] = $val;
			}
			$fieldValues = implode(", ", array_keys($bindValueList));
		}
		$insertSql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)",
			$this->table,
			$fieldsStr,
			$fieldValues
		);
		$masterPdo = $this->getMasterPdo();
		$statement = $masterPdo->prepare($insertSql);
		$this->bindArrayValue($statement, $bindValueList);

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($insertSql, $bindValueList);
		}

		$isSucc = $statement->execute();
		$statement = null;

		$finalRes = intval($isSucc);

		// 如果是返回插入的ID,那么获取 lastInsertId
		if ($isSucc && $isReturnId) {
			$finalRes = intval($masterPdo->lastInsertId());
		}
		return $finalRes;
	}

	/**
	 * 通过ID（主键）更新特定的一行记录
	 *
	 * @param int $id
	 * @param array $updateData
	 * @return bool
	 */
	public function updateRow($id, $updateData) {
		$id = intval($id);
		$bindValueList = [];
		$setValList = [];
		if (is_array($updateData) && !empty($updateData)) {
			foreach ($updateData as $key => $val) {
				$bindValueList[":" . $key] = $val;
				$setValList[] = "`{$key}` = :{$key}";
			}
		}

		$updateSql = sprintf("UPDATE `%s` SET %s WHERE `%s` = %d",
			$this->table,
			implode(', ', $setValList),
			$this->primaryKey,
			$id
		);

		$masterPdo = $this->getMasterPdo();
		$statement = $masterPdo->prepare($updateSql);
		$this->bindArrayValue($statement, $bindValueList);

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($updateSql, $bindValueList);
		}

		$isSucc = $statement->execute();
		$statement = null;
		return (bool) $isSucc;
	}


	/**
	 * 通过ID（主键）列表来更新特定的多行记录
	 *
	 * @param array $idList
	 * @param array $updateData
	 * @param bool $isReturnEffectRows 是否返回影响的行数
	 * @return bool
	 */
	public function updateMultiRows($idList, $updateData, $isReturnEffectRows = true) {
		$idList = self::forceIntvalFilterUnique($idList);
		if (empty($idList)) {
			throw new DBParamException("updateMultiRows idList can not be empty", ErrorCode::INVALID_PARAM);
		}

		$bindValueList = [];
		$setValList = [];
		if (is_array($updateData) && !empty($updateData)) {
			foreach ($updateData as $key => $val) {
				$bindValueList[":" . $key] = $val;
				$setValList[] = "`{$key}` = :{$key}";
			}
		}

		$updateSql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN (%s)",
			$this->table,
			implode(', ', $setValList),
			$this->primaryKey,
			implode(",", $idList)
		);

		$masterPdo = $this->getMasterPdo();
		$statement = $masterPdo->prepare($updateSql);
		$this->bindArrayValue($statement, $bindValueList);

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($updateSql, $bindValueList);
		}

		$isSucc = $statement->execute();
		$result = $isSucc;
		if ($isReturnEffectRows) {
			$rowCount = intval($statement->rowCount());
			$result = $rowCount;
		}

		$statement = null;
		return $result;
	}

	/**
	 * 通过ID（主键）删除特定的一行记录
	 * @param int $id
	 * @return bool
	 */
	public function deleteRow($id = 0) {
		$id = intval($id);

		$deleteSql = sprintf("DELETE FROM `%s` WHERE `%s` = %d",
			$this->table,
			$this->primaryKey,
			$id
		);

		$masterPdo = $this->getMasterPdo();
		$statement = $masterPdo->prepare($deleteSql);

		// 打印执行SQL的日志，显示实际执行的日志
		if ($this->isDebugMode()) {
			\Koala\Database\DBLogger::info($deleteSql);
		}
		$isSucc = $statement->execute();
		$statement = null;
		return (bool) $isSucc;
	}

	/**
	 *
	 * @param array $conditions
	 * @return array [$whereStr, $bindValueList]
	 */
	protected function buildWhereStr($conditions = []) {
		$whereStr = "";
		$bindValueList = [];
		$glueConn = " AND ";
		if (is_array($conditions) && !empty($conditions)) {
			$whereArr = [];
			$queryIndex = 1;
			foreach ($conditions as $field => $value) {
				$singleOrPos = strpos($field, self::$singleOrConditionPrefix);
				$singleAndPos = strpos($field, self::$singleAndConditionPrefix);
				if ($singleOrPos !== false && $singleOrPos == 0) {
					// 单个外包括号的OR 语句的拼接
					list($curWhereStr, $curBindValueList) = $this->innerBuildWhereCondition($value, self::QUERY_OR, $queryIndex);
					$whereArr[] = $curWhereStr;
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				} else if ($singleAndPos !== false && $singleAndPos == 0) {
					// 单个外包括号的AND 语句的拼接
					list($curWhereStr, $curBindValueList) = $this->innerBuildWhereCondition($value, self::QUERY_AND, $queryIndex);
					$whereArr[] = $curWhereStr;
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				} else {
					// 只是简单的所有条件的 AND 拼接
					list($curWhereArr, $curBindValueList) = $this->innerParseFieldToValueCondition($field, $value, $queryIndex);
					$whereArr = array_merge($whereArr, $curWhereArr);
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				}
			}
		}

		// 粘合查询条件
		if (!empty($whereArr)) {
			$whereStr = implode($glueConn, $whereArr);
		}

		return [$whereStr, $bindValueList];
	}

	const QUERY_AND = 1; // 单个外包括号的AND 语句的拼接
	const QUERY_OR = 2; // 单个外包括号的OR 语句的拼接

	protected function innerBuildWhereCondition($conditions = [], $type = self::QUERY_AND, &$queryIndex) {
		$whereStr = "";
		$bindValueList = [];

		// 处理条件
		if (is_array($conditions) && !empty($conditions)) {
			$whereArr = [];
			foreach ($conditions as $field => $value) {
				$singleOrPos = strpos($field, self::$singleOrConditionPrefix);
				$singleAndPos = strpos($field, self::$singleAndConditionPrefix);
				if ($singleOrPos !== false && $singleOrPos == 0) {
					// 单个外包括号的OR 语句的拼接
					list($curWhereStr, $curBindValueList) = $this->innerBuildWhereCondition($value, self::QUERY_OR, $queryIndex);
					$whereArr[] = $curWhereStr;
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				} elseif ($singleAndPos !== false && $singleAndPos == 0) {
					// 单个外包括号的AND 语句的拼接
					list($curWhereStr, $curBindValueList) = $this->innerBuildWhereCondition($value, self::QUERY_AND, $queryIndex);
					$whereArr[] = $curWhereStr;
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				} else {
					list($curWhereArr, $curBindValueList) = $this->innerParseFieldToValueCondition($field, $value, $queryIndex);
					$whereArr = array_merge($whereArr, $curWhereArr);
					$bindValueList = array_merge($bindValueList, $curBindValueList);
				}
			}
		}

		$glueConn = ($type == self::QUERY_AND) ? " AND " : " OR ";
		if (!empty($whereArr)) {
			$whereStr = sprintf("(%s)", implode($glueConn, $whereArr));
		}

		return [$whereStr, $bindValueList];
	}

	protected function innerParseFieldToValueCondition($field, $value, &$queryIndex) {
		$whereArr = [];
		$bindValueList = [];
		$bindPrefix = ":param";
		if (is_scalar($value)) {
			$curBindIndex = $bindPrefix . $queryIndex;
			$whereArr[] = sprintf("`%s` = %s", $field, $curBindIndex);
			$bindValueList[$curBindIndex] = $value;
			$queryIndex++;
		} else if (is_null($value)) {
			$whereArr[] = sprintf("`%s` = NULL", $field);
		} else if (is_array($value)) {
			// 条件数组是一个数组
			foreach ($value as $subConn => $subVal) {
				// 支持IN操作
				$subConn = strtoupper($subConn);
				if ($subConn == "IN") {
					$tmpWhereInStr = $this->buildWhereInStr($subVal);
					if (empty($tmpWhereInStr)) {
						throw new DBParamException("IN查询条件不能为空", ErrorCode::INVALID_PARAM);
					}
					$whereArr[] = sprintf("`%s` IN (%s)",
						$field,
						$tmpWhereInStr
					);
				} else if ($subConn == "LIKE") {
					$curBindIndex = $bindPrefix . $queryIndex;
					$whereArr[] = sprintf("`%s` LIKE %s", $field, $curBindIndex);
					$bindValueList[$curBindIndex] = $subVal;
					$queryIndex++;
				} else {
					// 其他不等于符号
					$curBindIndex = $bindPrefix . $queryIndex;
					$whereArr[] = sprintf("`%s` %s %s", $field, $subConn, $curBindIndex);
					$bindValueList[$curBindIndex] = $subVal;
					$queryIndex++;
				}
			}
		}

		return [$whereArr, $bindValueList];
	}

	/**
	 * 拼接 WHERE IN 查询括号里面的字符串语句(数字类型会强制转化，字符串类型会进行add_slash操作)
	 * @param $list
	 * @return string
	 */
	protected function buildWhereInStr($list) {
		$resultStr = "";
		if (is_array($list) && !empty($list)) {
			$formatList = [];
			foreach ($list as $val) {
				if (is_int($val)) {
					$formatList[] = intval($val);
				} else if (is_float($val)) {
					$formatList[] = floatval($val);
				} else if (is_string($val)) {
					$formatList[] = sprintf("'%s'", addslashes($val));
				}
			}
			$formatList = array_values(array_unique($formatList));
			$resultStr = implode(",", $formatList);
		}
		return $resultStr;
	}

	/**
	 * 绑定PDOStatement的参数
	 * @param string $pdoStatement : the query on which link the values
	 * @param array $array : associative array containing the values ​​to bind
	 */
	protected function bindArrayValue($pdoStatement, $array) {
		if(is_object($pdoStatement) && ($pdoStatement instanceof \PDOStatement)) {
			foreach($array as $key => $value) {
				$param = -1;
				if (is_int($value)) {
					$param = \PDO::PARAM_INT;
				} else if (is_float($value)) {
					$param = \PDO::PARAM_STR;
				} else if (is_bool($value)) {
					$param = \PDO::PARAM_BOOL;
				} else if (is_null($value)) {
					$param = \PDO::PARAM_NULL;
				} else if(is_string($value)) {
					$param = \PDO::PARAM_STR;
				}
				// 有绑定的参数
				if($param != -1) {
					$pdoStatement->bindValue($key, $value, $param);
				}
			}
		}
	}

	/**
	 * 是否处于debug模式，debug模式下将会打印执行的每一条SQL语句
	 * @return bool
	 */
	protected function isDebugMode() {
		return true;
	}

	/**
	 * 强制转化为int整型数组
	 * （对于获取ID列表很有用）
	 *
	 * @param array $arr
	 * @return array
	 */
	protected static function forceIntvalFilterUnique($arr = []) {
		if (!is_array($arr)) {
			$arr = [];
 		}
		return array_values(array_unique(array_map('intval', $arr)));
	}
}