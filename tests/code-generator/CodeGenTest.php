<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/10/14
 * Time: 22:43
 */


class CodeGenTest extends PHPUnit_Framework_TestCase
{
	public function testCodeGen() {
		$simpleDao = Koala\Database\Connection::getConnection("test");
		$curMasterHandler = $simpleDao->getMasterHandler();

		require "CustomDaoTpl.php";

		// 配置个性化模板
		\Koala\CodeGenerator\Template\DaoTpl::$template = CustomDaoTpl::$template;
		$userDaoGenerator = new \Koala\CodeGenerator\DaoGenerator();
		$userDaoGenerator->setPdo($curMasterHandler); // $myMasterPDO 是连接到数据库的 PDO对象
		$userDaoGenerator->setFullParentDir(OUTPUT_PATH);
		$isSucc = $userDaoGenerator->genDaoCodeByDbNameAndTableName("test", "user"); // test是数据库名字，user是表名
		$isSucc = $userDaoGenerator->genDaoCodeByDbNameAndTableName("test", "test_user"); // test是数据库名字，test_user是表名

		$userModelGenerator = new \Koala\CodeGenerator\ModelGenerator();
		$userModelGenerator->setPdo($curMasterHandler); // $myMasterPDO 是连接到数据库的 PDO对象
		$userModelGenerator->setFullParentDir(OUTPUT_PATH);
		$isSucc = $userModelGenerator->genModelCodeByDbNameAndTableName("test", "user"); // test是数据库名字，user是表名
		$isSucc = $userModelGenerator->genModelCodeByDbNameAndTableName("test", "test_user"); // test是数据库名字，test_user是表名

		$this->assertTrue($isSucc);
	}
}
