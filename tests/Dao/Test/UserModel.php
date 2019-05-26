<?php
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