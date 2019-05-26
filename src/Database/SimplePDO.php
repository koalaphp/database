<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 18/12/2017
 * Time: 01:40
 */

namespace Koala\Database;

use Koala\Database\Exception\DBConnectionException;
use Koala\Database\Exception\ErrorCode;

class SimplePDO
{
	/**
	 * 当前连接的数据库配置的名字
	 * @var string $dbConfigName
	 */
	protected $dbConfigName = null;
	/**
	 * 当前激活的slave连接
	 * @var \PDO $curSlaveConn
	 */
	protected $curSlaveConn = null;

	/**
	 * 当前激活的master连接
	 * @var \PDO $curMasterConn
	 */
	protected $curMasterConn = null;

	/**
	 * 主库配置
	 * @var array
	 */
	protected $master = [];
	/**
	 * 从库配置
	 * @var array
	 */
	protected $slaves = [];

	/**
	 * 连接池
	 * @var array
	 */
	protected static $connectPool = [];
	public function __construct($dbConfigName, $config = [])
	{
		$this->dbConfigName = $dbConfigName;
		$this->master = $config['master'];
		$this->slaves = $config['slaves'];
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __destruct()
	{
		// 进行连接释放
		if ($this->curMasterConn !== null) {
			$this->curMasterConn = null;
		}
		if ($this->curSlaveConn !== null) {
			$this->curSlaveConn = null;
		}
	}

	/**
	 * 根据配置获取数据库连接
	 * @param array $singleConfig
	 * @return \PDO
	 * @throws DBConnectionException
	 */
	private function connectToDatabase($singleConfig = [])
	{
		$curConnHash = $singleConfig['hashCode'];
		// 如果两个连接完全一样，那么从连接池中打开
		if (isset(self::$connectPool[$curConnHash])) {
			return self::$connectPool[$curConnHash];
		}
		$attribute = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
		try {
			$curDsn = 'mysql:host=' . $singleConfig['host'] . ';port=' . $singleConfig['port'] . ';dbname=' . $singleConfig['dbname'] . ';charset=' . $singleConfig['charset'];
			$handler = new \PDO(
				$curDsn,
				$singleConfig['user'],
				$singleConfig['pass'],
				$attribute
			);
		} catch (\PDOException $e) {
			throw new DBConnectionException($e->getMessage(), ErrorCode::INVALID_PARAM);
		}
		self::$connectPool[$curConnHash] = $handler;
		return $handler;
	}

	/**
	 * 获取主库的PDO连接
	 * @return \PDO
	 */
	public function getMasterHandler() {
		if ($this->curMasterConn !== null) {
			return $this->curMasterConn;
		}

		$chosenMaster = $this->master;
		$chosenMaster['hashCode'] = $this->calDBConfigHash($chosenMaster);
		$this->curMasterConn = $this->connectToDatabase($chosenMaster);
		return $this->curMasterConn;
	}

	/**
	 * 获取从库的PDO连接
	 * @return \PDO
	 */
	public function getSlaveHandler() {
		if ($this->curSlaveConn !== null) {
			return $this->curSlaveConn;
		}
		$allSlaveConfig = $this->slaves;
		$chosenSlave = $allSlaveConfig[array_rand($allSlaveConfig, 1)];
		$chosenSlave['hashCode'] = $this->calDBConfigHash($chosenSlave);
		$this->curSlaveConn = $this->connectToDatabase($chosenSlave);
		return $this->curSlaveConn;
	}

	/**
	 * 获取连接的hash值
	 * @param $singleConfig
	 * @return string
	 */
	protected function calDBConfigHash($singleConfig) {
		$curConnHash = array(
			'dbname' => $singleConfig['dbname'],
			'host' => $singleConfig['host'],
			'port' => $singleConfig['port'],
			'user' => $singleConfig['user'],
			'pass' => $singleConfig['pass'],
		);
		$curConnHash = md5($this->dbConfigName . json_encode($curConnHash));
		return $curConnHash;
	}
}