<?php

namespace Applications\iyov;

use Applications\iyov\Lib\Http;
use Applications\iyov\Lib\String;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * HTTP 代理
 *
 */

class HttpProxy extends Proxy {

	/**
	 * Max length of body(bytes)
	 */
	const MAX_BODY_LENGTH = 3072;

	/**
	 * Max udp lengtg(65537)
	 */
	const MAX_UDP_LENGTH = 64000;

	/**
	 * data
	 */
	public $data = '';

	/**
	 * Http request
	 */
	public $request = '';

	/**
	 * Http response
	 */
	public $response = '';

	/**
	 * 异步链接
	 */
	public $asyncConnection = null;

	/**
	 * Url scheme
	 */
	public $scheme = '';

	/**
	 * Http request header.
	 */
	public $requestHeader;

	/**
	 * Http Response header.
	 */
	public $responseHeader = '';

	/**
	 * Http method
	 */
	public $method;

	/**
	 * Http url
	 */
	public $url;

	/**
	 *
	 */
	public $query = '';

	/**
	 * Http host
	 */
	public $host;
	
	/**
	 * Host with protocol prefix
	 */
	public $entityHost;
	/**
	 * Http request body
	 */
	public $requestBody = 'nil';

	/**
	 * Http response body
	 */
	public $responseBody = 'nil';

	/**
	 * Http response status
	 */
	public $status;

	/**
	 * Http resonse message
	 */
	public $message;

	/**
	 * Http request path
	 */
	public $path = '/';

	/**
	 * Http 协议版本号
	 */
	public $protocol = '';

	/**
	 * Response start time.
	 */
	public $responseStartTime = 0;

	public function __construct()
	{
		$this->protocol = 'Http';
		$this->startTime =  !$this->startTime ? microtime(true) : $this->startTime;
	}

	/**
	* 代理流程入口
	*
	* @param string $buffer 客户端发来的第一个包
	*/
	public function process($data)
	{
		$this->data .= $data;
		echo $data."\n";
		if (!($length = Http::input($this->data))) {
			return ;
		}
		$this->request = substr($this->data, 0, $length);
		$this->data = substr($this->data, $length, strlen($this->data));
		// 解析请求数据
		$this->requestStatistic();

		if (!$this->asyncConnection) {
			// 初始化目标服务器，异步链接
			$this->initAsyncConection();

			// 建立客户端和目标服务器的管道
			$this->pipe();
		}
	}

	public function initAsyncConection()
	{
		//  初始化异步链接，并建立通信管道
    	$this->asyncConnection = new AsyncTcpConnection("tcp://{$this->host}:{$this->port}");

    	// 设置目标服务器数据捕获回调
    	$proxy = $this;
		$this->asyncConnection->onMessageCapture = function($data) use (&$proxy) {
			// 缓存Response数据，因为代理链接时tcp的，http的数据实际时字符串，在网络中可能被拆包了
			$proxy->responseStartTime = !$proxy->responseStartTime ? microtime(true) : $proxy->responseStartTime;
			$proxy->response .= $data;
			if (!Http::output($proxy->response)) {
				return ;
			}
			
			// 解析统计response数据
			$proxy->responseStatistic();
			$proxy->response = '';
			$proxy->responseStartTime = 0;
		};
	}

	public function pipe()
	{
		if (strcmp($this->method,'CONNECT') === 0) {
			// HTTPS
    		$this->connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
    	} else {
    		// HTTP
			$this->asyncConnection->send($this->request);
    	}
     	// 建立管道
		$this->asyncConnection->pipe($this->connection);
		$this->connection->pipe($this->asyncConnection);
		// 链接至目标服务器
		$this->asyncConnection->connect();
	}

	/**
	 * 解析Http请求包头
	 */
	protected function requestDecode($data)
	{
		list($this->requestHeader, $body) = explode("\r\n\r\n", $data, 2);
		$this->requestBody = !$body ? $this->requestBody : ($body < self::MAX_BODY_LENGTH ? $body : $this->requestBody);
		list($firstLine, $this->requestHeader) = explode("\r\n", $this->requestHeader, 2);
		$this->requestHeader = str_replace("\r\n", "<br />", $this->requestHeader);

		return $firstLine;
	}

	/**
	 * 解析Http相应包头
	 */
	protected function responseDecode($data)
	{
		list($this->responseHeader, $body) = explode("\r\n\r\n", $data, 2);
		$this->responseBody = !$body ? $this->responseBody : ($body < self::MAX_BODY_LENGTH ? $body : $this->responseBody);
		list($firstLine, $this->responseHeader) = explode("\r\n", $this->responseHeader, 2);
		$this->responseHeader = str_replace("\r\n", "<br />", $this->responseHeader);

		return $firstLine;
	}

	/**
	 * 统计Request数据
	 */
	protected function requestStatistic()
	{
		$requestLine = $this->requestDecode($this->request);
		// 解析请求头部信息
		list($this->method, $url, $this->protocol) = explode(" ", $requestLine);
		echo "Request: " . $url . "\n";
		$this->urlComponents($url);
		
		if (!isset(static::$statisticData[$this->entityHost])) {
			static::$statisticData[$this->entityHost] = array();
		}

		static::$statisticData[$this->entityHost][$this->url] = array(
			'Path' => $this->path,
			'Method' => $this->method,
			'Protocol' => $this->protocol,
			'ClientIP' => $this->connection->getRemoteIp(),
			'RequestSize' => strlen($this->request),
			'StartTime' => $this->startTime,
			'RequestHeader' => $this->requestHeader,
			// 'scheme'=> $this->scheme,
			'RequestBody' => $this->requestBody
			);
		if ($this->query) {
			static::$statisticData[$this->entityHost][$this->url]['Query'] = $this->query;
		}
	}

	/**
	 * 统计Response数据
	 */
	protected function responseStatistic()
	{
		echo "Response: ".$this->url."\n";
		$responseLine = $this->responseDecode($this->response);
		
		static::$statisticData[$this->entityHost][$this->url]['Path'] = $this->path;
		static::$statisticData[$this->entityHost][$this->url]['StartTime'] = $this->startTime;
		static::$statisticData[$this->entityHost][$this->url]['EndTime'] = microtime(true);
		static::$statisticData[$this->entityHost][$this->url]['ServerIP'] = $this->asyncConnection->getRemoteIp();
		static::$statisticData[$this->entityHost][$this->url]['ResponseSize'] = strlen($this->response);
		if ($responseLine) {
			// 不是资源文件，解析响应头部信息
			list(, $this->status, $this->message) = explode(" ", $responseLine, 3);
			static::$statisticData[$this->entityHost][$this->url]['RessponseCode'] = "$this->status [ $this->message ]";
			static::$statisticData[$this->entityHost][$this->url]['ResponseHeader'] = $this->responseHeader;
			// static::$statisticData[$this->entityHost][$this->url]['ResponseBody'] = $this->responseBody;
		}
	}

	public function urlComponents($url)
	{
		$components = parse_url($url);
		$this->host = $components['host'];
		$this->scheme = isset($components['scheme']) ? $components['scheme'] : $this->scheme;
		$this->port =  !isset($components['port']) ? '80' : $components['port'];
		$this->path = !isset($components['path']) ? $this->path : $components['path'];
		$this->host =  $this->port == 80 ? $this->host : $this->host . ':' . $this->port;
		$this->query = !isset($components['query']) ? $this->query : $components['query'];
		$this->entityHost = !$this->scheme ? $this->host : $this->scheme . '://' . $this->host;
		$this->url = $this->path == '/' ? 'default' : substr($this->path, 1, strlen($this->path));
	}

	/**
	 * 将数据发送给统计进程
	 */
	public static function Broadcast()
	{
		$data = array();
		$length = 0;
		foreach(static::$statisticData as $host => $urlData) {
			if ($length + strlen(json_encode($urlData)) > self::MAX_UDP_LENGTH) {
				break;
			}
			$data[$host] = $urlData;
			$length += strlen(json_encode($urlData));
			unset(static::$statisticData[$host]);
		}
		static::$udpConnection->send(json_encode($data));
	}
}
