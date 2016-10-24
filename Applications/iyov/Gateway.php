<?php
namespace Applications\iyov;

use \Workerman\Worker;
use \Workerman\Connection\UdpConnection;
use \Workerman\Lib\Timer;

class Gateway {

	/**
	 * 内部通信worker
	 */
	static $internalWorker = null;

	/**
	 * 统计数据
	 */
	public static $globalData = array();

	/**
	 * gatewayworker
	 */
	public static $gatewayworker = array();

	public static function init($worker)
	{
		static::$gatewayworker = $worker;
		Timer::add(1, array('\Applications\iyov\Gateway', 'broad'), array(), true);
		self::initInternalWorker();
	}

	public static function broad()
	{
		if (empty(static::$gatewayworker->connections)) {
			return ;
		}
		foreach(static::$gatewayworker->connections as $connection) {
			if (!isset(self::$globalData[$connection->getRemoteIp()])) {
				continue ;
			}
			$connection->send(json_encode(self::$globalData[$connection->getRemoteIp()]));
			unset(static::$globalData[$connection->getRemoteIp()]);
		}
		self::$globalData = array();
	}

	protected static function initInternalWorker()
	{
		static::$internalWorker = new Worker('tcp://0.0.0.0:9388');
		static::$internalWorker->onMessage = function($connection,$data) {
			$data = json_decode($data, true);
			if (empty($data)) {
				return ;
			}
			// self::$globalData = self::$globalData + $data;
			foreach($data as $clientIp => $ipData) {
				$tmp = !isset(static::$globalData[$clientIp]) ? $ipData : (static::$globalData[$clientIp] + $ipData);
				ksort($tmp);
				static::$globalData[$clientIp] = $tmp;
			}
		};
		static::$internalWorker->listen();
		static::$internalWorker->run();
	}
}