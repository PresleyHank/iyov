<?php
namespace Applications\iyov;

use \Workerman\Worker;
use \Workerman\Connection\UdpConnection;

class Gateway {

	/**
	 * 内部通信worker
	 */
	static $internalWorker = null;

	/**
	 * gatewayworker
	 */
	public static $gatewayworker = array();

	public static function init($worker)
	{
		static::$gatewayworker = $worker;
		static::$internalWorker = new Worker('udp://0.0.0.0:9388');
		static::$internalWorker->onMessage = function($connection,$data) {
			Gateway::broad($data);
		};
		static::$internalWorker->listen();
		static::$internalWorker->run();
	}

	public static function broad($data)
	{
		if (empty(static::$gatewayworker->connections)) {
			return ;
		}

		foreach(static::$gatewayworker->connections as $connection) {
			$connection->send($data);
		}
	}
}