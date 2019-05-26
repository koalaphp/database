<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/11/11
 * Time: 16:17
 */

class CustomDaoTpl
{
	public static $template = <<<EOT
<?php
namespace Library\Dao\%dbNamespace%;
use Koala\Database\SimpleDao;
/**
 * Created by Koala Command Tool.
 * Author: %author%
 * Date: %date%
 * 
 * @method %tableModelName% findOne(\$conditions = [], \$sort = "id desc")
 * @method %tableModelName%[] findAllRecordCore(\$conditions = [],  \$sort = "id desc", \$offset = 0, \$limit = 20, \$fieldList = [])
 * @method \\Generator|%tableModelName%[]|%tableModelName%[][] createGenerator(\$conditions = [], \$numPerTime = 100, \$isBatch = false)
 * 
 * %tableComment% Dao 类，提供基本的增删改查功能
 */
class %tableDaoName% extends SimpleDao
{
	// 连接的数据库
    protected \$database = '%dbName%';
    // 表名
    protected \$table = '%tableName%';
    // 主键字段名
    protected \$primaryKey = '%primaryKey%';
    
    // select查询的时候是否使用master，默认select也是查询master
    protected \$isMaster = true;
    
    // select查询出来的结果映射的Model类
    protected \$modelClass = %tableModelName%::class;
    
%fieldList%
}
EOT;
}