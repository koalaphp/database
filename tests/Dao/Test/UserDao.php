<?php
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