<?php
namespace Applications\iyov;

use \Workerman\Worker;
use \Workerman\Connection\UdpConnection;
use \Workerman\Lib\Timer;

class Gateway {

	/**
	 * 内部通信worker，接收来自代理进程的数据
	 *
	 * @var object
	 */
	static $internalWorker = null;

	/**
	 * 统计数据汇总
	 *
	 * @var array
	 */
	public static $globalData = array();

	/**
	 * Gatewayworker，与PC端建立websocket连接
	 *
	 * @var object
	 */
	public static $gatewayworker = null;

	public static function Init($worker)
	{
		static::$gatewayworker = $worker;

		//  每秒广播一次统计数据
		Timer::add(1, array('\Applications\iyov\Gateway', 'Broad'), array(), true);

		// 初始化内部通信
		self::initInternalWorker();
	}

	public static function Broad()
	{
		if (empty(static::$gatewayworker->connections)) {
			// 清空
			self::$globalData = array();
			return ;
		}

		// 向所有连接广播数据
		foreach(static::$gatewayworker->connections as $connection) {
			$connection->send(json_encode(self::$globalData));
		}

		// 清空
		self::$globalData = array();
	}

	/**
	 * 初始化内部通信Worker
	 */
	protected static function initInternalWorker()
	{
		static::$internalWorker = new Worker('tcp://0.0.0.0:9388');
		static::$internalWorker->onMessage = function($connection,$data) {
			$data = json_decode($data, true);
			if (empty($data)) {
				return ;
			}
			self::$globalData = self::$globalData + $data;
		};
		static::$internalWorker->listen();
		static::$internalWorker->run();
	}
}