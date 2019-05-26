<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/11/12
 * Time: 00:40
 */

use Koala\Database\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
	public function testGetConn() {
		$conn = \Koala\Database\Connection::getConnection("test");
		$tmpPdo = $conn->getMasterHandler();
		$this->assertNotNull($tmpPdo);
		$anotherConn = \Koala\Database\Connection::getConnection("test");
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testConnectionConfigError() {
		$conn = \Koala\Database\Connection::getConnection("koalaconfigerror");
		$conn->getMasterHandler();
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testNotFoundDB() {
		$conn = \Koala\Database\Connection::getConnection("noconfigdb");
	}
}
