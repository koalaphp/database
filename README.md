# KoalaPHP Database Component

## 1. 建立数据库连接

```php
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




