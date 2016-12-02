<?php
namespace Applications\iyov;

use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Connection\TcpConnection;
use \Workerman\Protocols\Http;


/**
 * 代理服务基类
 */
class Proxy {

	/**
	 * 需要过滤掉的域名地址
	 *
	 * @var array
	 */
	public $filterHost = array('test.iyov.io:4355','test.iyov.io:8080');

	/**
	 * 统计进程地址
	 *
	 * @var string
	 */
	static $innerAddress = 'tcp://0.0.0.0:9388';

	/**
	 * 到统计进程的内容链接
	 *
	 * @param object
	 */
	protected static $innerConnection = null;

	/**
	 * 链接实例
	 * 
	 * @var array array(connection => proxyInstance)
	 */
	protected static $instances = array();

	/**
	 * 客户端链接链接
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * 全局数据统计，并发送给统计进程
	 *
	 * @var array
	 */
	public static $statisticData = array();

	/**
	 * 请求开始时间
	 *
	 * @var interge
	 */
	public $startTime = 0;

	/**
	 * 应用层协议
	 *
	 * @var string
	 */
	public $protocol = '';

	/**
	 * 初始化内部链接
	 */
	public static function init()
	{
		static::$innerConnection = new TcpConnection(stream_socket_client(self::$innerAddress), static::$innerConnection);
	}

	/**
	 * 初始化代理实例
	 *
	 * @param object $connection
	 * @return object Proxy
	 */
	public static function instance($connection)
	{
		if (!isset(static::$instances[$connection->id])) {
			static::$instances[$connection->id] = new static;
			static::$instances[$connection->id]->connection = $connection;
		}
		
		return static::$instances[$connection->id];
	}

	/**
	 * 销毁代理实例
	 *
	 * @param object $connection
	 */
	public static function unInstance($connection)
	{
		unset(static::$instances[$connection->id]);
	}

	/**
	 * 将数据发送给统计进程
	 */
	public static function Broadcast()
	{
		if (static::$innerConnection == null) {
			self::init();
		}
		// 按时间排序
		ksort(static::$statisticData);
		static::$innerConnection->send(json_encode(static::$statisticData)); //JSON_UNESCAPED_SLASHES|
		static::$statisticData = array();
	}

	/**
	 * 解析
	 *
	 * @param string $data
	 */
	protected function decode($data) {}

	/**
	 * 过滤掉不统计的域名信息
	 *
	 * @param string $host
	 * @return bool
	 */
	public function filter($host)
	{
		return in_array($host, $this->filterHost);
	}
}