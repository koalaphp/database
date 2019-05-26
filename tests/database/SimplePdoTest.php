<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/11/12
 * Time: 00:40
 */

use Koala\Database\Connection;
use PHPUnit\Framework\TestCase;

class SimplePdoTest extends TestCase
{
	public function testGetConn() {
		$conn = \Koala\Database\Connection::getConnection("test");
		$tmpPdo = $conn->getMasterHandler();
		$this->assertNotNull($tmpPdo);
		$aSlave = $conn->getSlaveHandler();
		$bSlave = $conn->getSlaveHandler();
		if ($aSlave === $bSlave) {
			echo "two slave is same" . PHP_EOL;
		}
		$tmpPdoSecond = $conn->getMasterHandler();
		if ($tmpPdo === $tmpPdoSecond) {
			echo "two master is same" . PHP_EOL;
		}
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testConnectionError() {
		$conn = \Koala\Database\Connection::getConnection("koala");
		$conn->getMasterHandler();
	}
}
