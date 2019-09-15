# KoalaPHP Database Component

## 1. 建立数据库连接

只需要在 `Bootstrap` 阶段初始化一次即可。

```php
$defaultDatabaseConfig = [
    'test' => [
        'master' => [
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => 'yourpassword',
            'charset' => 'utf8mb4',
        ],
        'slaves' => [
            [
                'dbname' => 'test',
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'pass' => 'yourpassword',
                'charset' => 'utf8mb4',
            ]
        ]
    ],
];
// 初始化配置
Koala\Database\Connection::initDatabaseConfig($defaultDatabaseConfig);
// 打开输出到屏幕的日志
Koala\Database\DBLogger::$isPrint = true;
// 设置当前的日志，可改为自己的日志处理类，推荐是继承自Monolog的logger类(需要实现了 "info" 方法)
Koala\Database\DBLogger::setLogger(new MyLogger());
```

## 2. 获取PDO连接

```
$conn = \Koala\Database\Connection::getConnection("test");
$tmpMasterPdo = $conn->getMasterHandler();
$tmpSlavePdo = $conn->getSlaveHandler();
```

## 3. SimpleDao 接口文档（增、删、改、查等）

前提假设条件：

- user表SQL语句

```
CREATE TABLE `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `phone` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '手机号码',
  `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码',
  `money` DECIMAL(14,2) DEFAULT '0.0' COMMENT '金额',
  `remark` VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `status` TINYINT NOT NULL DEFAULT '1' COMMENT '1: 有效，2：无效',
  `create_time` INT NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` INT NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员表';
```

- UserModel PHP文件

```
namespace Library\Dao\Test;

/**
 * Created by Koala Command Tool.
 * Author: laiconglin3@126.com
 * Date: 2019-05-26 22:51:58
 * 
 * 会员表 Model 模型类
 */
class UserModel
{
    public $id; // int(10) unsigned ID
    public $name; // varchar(32) 用户昵称
    public $phone; // varchar(32) 手机号码
    public $password; // varchar(255) 密码
    public $money; // decimal(14,2) 金额
    public $remark; // varchar(255) 备注
    public $status; // tinyint(4) 1: 有效，2：无效
    public $create_time; // int(11) 创建时间
    public $update_time; // int(11) 更新时间
}
```

- UserDao PHP文件

```
namespace Library\Dao\Test;
use Koala\Database\SimpleDao;
/**
 * Created by Koala Command Tool.
 * Author: laiconglin3@126.com
 * Date: 2019-05-26 22:51:58
 * 
 * @method UserModel findOne($conditions = [], $sort = "id desc")
 * @method UserModel[] findAllRecordCore($conditions = [],  $sort = "id desc", $offset = 0, $limit = 20, $fieldList = [])
 * @method \Generator|UserModel[]|UserModel[][] createGenerator($conditions = [], $numPerTime = 100, $isBatch = false)
 * 
 * 会员表 Dao 类，提供基本的增删改查功能
 */
class UserDao extends SimpleDao
{
	// 连接的数据库
    protected $database = 'test';
    // 表名
    protected $table = 'user';
    // 主键字段名
    protected $primaryKey = 'id';
    
    // select查询的时候是否使用master，默认select也是查询master
    protected $isMaster = true;
    
    // select查询出来的结果映射的Model类
    protected $modelClass = UserModel::class;
    
    protected $fieldList = [
        'id' => 'int(10) unsigned', // ID
        'name' => 'varchar(32)', // 用户昵称
        'phone' => 'varchar(32)', // 手机号码
        'password' => 'varchar(255)', // 密码
        'money' => 'decimal(14,2)', // 金额
        'remark' => 'varchar(255)', // 备注
        'status' => 'tinyint(4)', // 1: 有效，2：无效
        'create_time' => 'int(11)', // 创建时间
        'update_time' => 'int(11)', // 更新时间
    ];
}
```

### 3.1 insert 插入一行记录

`insertRow` 插入一行数据到数据库的表中

```
/**
 * 插入一行数据到数据库中
 *
 * @param $insertData array 插入的数组
 * @param bool $isReturnId 是否返回插入的ID，默认是返回
 * @return int
 */
public function insertRow($insertData, $isReturnId = true);
```

示例代码：

```
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
```


### 3.2 update 更新记录

- (1). `updateRow` 通过ID（主键）更新特定的一行记录

```
/**
 * 通过ID（主键）更新特定的一行记录
 *
 * @param int $id 主键ID
 * @param array $updateData 更新的数据
 * @return bool
 */
public function updateRow($id, $updateData);
```

- (2). `updateMultiRows` 通过ID（主键）列表来更新特定的多行记录


```
/**
 * 通过ID（主键）列表来更新特定的多行记录
 *
 * @param array $idList ID（主键）列表
 * @param array $updateData  更新的数据
 * @param bool $isReturnEffectRows 是否返回影响的行数
 * @return bool
 */
public function updateMultiRows($idList, $updateData, $isReturnEffectRows = true);
```


示例代码：

```
$userDao = \Library\Dao\Test\UserDao::getInstance();
$updateData = [
    'phone' => '13688888877',
    'status' => 1,
    'update_time' => time(),
];
$res = $userDao->updateMultiRows([11, 12], $updateData);
```


### 3.3 delete 删除一行记录

```
/**
 * 通过ID（主键）删除特定的一行记录
 * @param int $id 主键ID
 * @return bool
 */
public function deleteRow($id = 0);
```


示例代码：

```
$userDao = \Library\Dao\Test\UserDao::getInstance();
$res = $userDao->deleteRow(11);
```

### 3.4 select 查询记录

#### 3.4.1 查询方法定义

主要有以下几个查询方法，函数定义如下：

- (1). `findCount` 查询符合条件的某一个对象

```
/**
 * 查询符合条件的某一个对象
 * $conditions 的详细用法参见findAllCore
 * @param array $conditions 查询where条件语句
 * @param string $sort 排序方式，默认"id desc"
 * @return \stdClass|null
 */
public function findOne($conditions = [], $sort = "id desc");
```

- (2). `findCount` 获取该查询条件下的总数

```
/**
 * 获取该查询条件下的总数
 * @param array $conditions 查询where条件语句
 * @return int
 */
public function findCount($conditions = []);
```

- (3). `findAllRecordCore` 进行查询获取多行记录的方法

```
/**
 * 用来进行查询获取记录的方法
 * @param array $conditions 查询where条件语句
 * @param string $sort 可选 排序方式 默认为根据主键降序，当 $sort 为空字符串的时候，表示不进行 order by。
 * @param int $offset 可选 偏移量 默认：0
 * @param int $limit 可选 单次查询出的个数 默认：20，特殊情况当limit 等于 -1 时表示查找全部。
 * @param array $fieldList 可选 查询的字段列表 默认查出所有字段
 * @return array
 */
public function findAllRecordCore($conditions = [], $sort = "id desc", $offset = 0, $limit = 20, $fieldList = []);
```

- (4). `createGenerator` 获取迭代器，可用于循环获取所有行的功能

```
/**
 * 可以实现循环获取所有行的功能（适用于在定时任务中扫描一整张表）
 *
 * 生成指定条件的迭代器
 * @param array $conditions 查询where条件语句
 * @param int $numPerTime 单次获取记录的个数
 * @param bool $isBatch 默认true, false: 一次返回1个结果，true：一次批量返回 {$numPerTime} 个结果
 * @return \Generator
 */
public function createGenerator($conditions = [], $numPerTime = 100, $isBatch = true);
```

示例代码：

```
$userDao = \Library\Dao\Test\UserDao::getInstance();
foreach ($userDao->createGenerator(["id" => [">" => 1]], 10, true) as $userObjList) {
    foreach ($userObjList as $userObj) {
        //... 
    }
}
```


#### 3.4.2 `$conditions` 查询where条件语句语法介绍

- (1). 基本的 AND 语句

```
    SQL: `name` = 'admin' AND `status` = 1
    $conditions = ['name' => 'admin', 'status' => 1];
```

- (2). 某个字段带有不等式的查询

```
    SQL: `status` = 1 AND `create_time` >= 1514713145
    $conditions = [
        'status' => 1,
        'create_time' => [
            ">=" => 1514713145,
        ],
    ];
```

- (3). 某个字段同时带有大于和小于的范围查询

```
    SQL: `status` = 1 AND `create_time` >= 1514713145 AND `create_time` <= 1514813145
    $conditions = [
        'status' => 1,
        'create_time' => [
            ">=" => 1514713145,
            "<=" => 1514813145
        ]
    ];
```

- (4). 某个字段带有 IN 范围查询

```
    SQL: `name` = 'admin' AND `status` IN (1, 2)
    $conditions = [
        'name' => 'admin',
        'status' => [
            "IN" => [1, 2], // 支持整型、浮点型和字符串的IN查询
        ],
    ];
```

- (5). 某个字段带有 LIKE 范围查询

```
    SQL: `name` LIKE '%adm%' AND `status` IN (1, 2)
    $conditions = [
        'name' => [
            'LIKE' => '%adm%'
        ],
        'status' => [
            "IN" => [1, 2], // 支持整型、浮点型和字符串的IN查询
        ],
    ];
```

- (6). 带有某个或者多个字段的 OR 语句 (必须以"single_or_condition"为前缀)

```
    SQL: `name` = 'admin' AND (`status` = 1 OR `id` IN (1, 2) OR `id` >= 20 )
    $conditions = [
        'name' => 'admin',
        'single_or_condition1' => [
            'status' => 1,
            'id' => [
                "IN" => [1, 2], // 只支持数组元素的IN查询
                ">=" => 20,
            ],
        ],
    ];
```

- (7). 带有某个或者多个字段的 AND 语句 (必须以"single_and_condition"为前缀)

```
    SQL: (`status` = 2 AND (`status` = 1 OR `id` >= 20))
    $conditions = [
        'single_and_condition1' => [
            'status' => 2,
            'single_or_condition1' => [
                'status' => 1,
                'id' => [">=" => 20],
            ],
        ],
    ];
```
- (8). 带有GROUP BY 语句的聚合查询语句
```
    SQL: (`status` >= 1 AND `id` >= 20) GROUP BY `status`
    $conditions = [
        'status' => [">=" => 1],
        'id' => [">=" => 20],
        'group by' => 'status',
    ];
```


