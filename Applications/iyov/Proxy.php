<?php
namespace Applications\iyov;

use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Connection\TcpConnection;
use \Workerman\Protocols\Http;


/**
 * 代理类，父类
 */
class Proxy {

	/**
	 *
	 */
	public $filterHost = array('0.0.0.0:4355','iyov.io:8080');
	/**
	 * udp address
	 */
	static $udpAddr = 'tcp://0.0.0.0:9388';
	/**
	 * 到统计进程的socket
	 */
	protected static $innerConnection = null;

	/**
	 * 链接实例
	 * 
	 * @var array array(connection => HttpProxy)
	 */
	protected static $instances = array();

	/**
	 * 异步链接到代理实例的映射
	 */
	protected static $asyncConnToProxy = array();

	/**
	 * 客户端链接链接
	 */
	public $connection = null;

	/**
	 * Http response Buffer
	 */
	public $responseBuffer = '';

	/**
	 * Http response content length
	 */
	public $responseContentLength = 0;

	/**
	 * Statistic data
	 */
	public static $statisticData = array();

	/**
	 * Request start time
	 */
	public $startTime = 0;

	/**
	 * 应用层协议，用于解包，生成统计数据
	 * @var string
	 */
	public $protocol = '';
	

	public static function init()
	{
		$stream = stream_socket_client('tcp://0.0.0.0:9388');

		static::$innerConnection = new TcpConnection($stream, static::$innerConnection);
	}

	public static function instance($connection)
	{
		if (!isset(static::$instances[$connection->id])) {
			static::$instances[$connection->id] = new static;
			static::$instances[$connection->id]->connection = $connection;
		}
		
		return static::$instances[$connection->id];
	}

	public static function unsetInstance($connection)
	{
		static::$instances[$connection->id]->AsyncTcpConnection->close();
		unset(static::$instances[$connection->id]);
	}

	/**
	 * 将数据发送给统计进程
	 */
	public static function Broadcast()
	{
		static::$innerConnection->send(json_encode(static::$statisticData)); //JSON_UNESCAPED_SLASHES|
		static::$statisticData = array();
	}

	/**
	 * 解析
	 * @param string $data
	 */
	protected function decode($data) {}

	/**
	 * 过滤掉不统计的域名信息
	 */
	public function filter($host)
	{
		return in_array($host, $this->filterHost);
	}
}