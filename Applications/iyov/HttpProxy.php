<?php

namespace Applications\iyov;

use Applications\iyov\Lib\Http;
use Applications\iyov\Lib\String;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * HTTP 代理
 */

class HttpProxy extends Proxy {

	/**
	 * 需要接收的类型
	 *
	 * @var array
	 */
	private $acceptedContentType = array('text/html','text/xml', 'application/json');

	/**
	 * 客户端请求数据buffer
	 *
	 * @var string
	 */
	public $data = '';

	/**
	 * 服务端返回数据buffer
	 *
	 * @var string
	 */
	public $response = '';

	/**
	 * 与目标服务器的异步链接
	 *
	 * @var object
	 */
	public $asyncTcpConnection = null;

	/**
	 * HTTP SCHEMA
	 *
	 * @var string
	 */
	public $scheme = '';

	/**
	 * HTTP 请求头部
	 *
	 * @var string
	 */
	public $requestHeader;

	/**
	 * HTTP 返回头部
	 *
	 * @var string
	 */
	public $responseHeader = '';

	/**
	 * HTTP 请求方法
	 *
	 * @var string
	 */
	public $method;

	/**
	 * 请求URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * HTTP 请求GET参数
	 *
	 * @var string
	 */
	public $query = '';

	/**
	 * HTTP 请求域名地址
	 *
	 * @var string
	 */
	public $host;

	/**
	 * 带SCHEMA和HOST的完整地址
	 *
	 * @var string
	 */
	public $entityHost;

	/**
	 * 请求数据包长度
	 *
	 * @var interge
	 */
	public $requestSize = 0;

	/**
	 * 返回数据包长度
	 *
	 * @var interge
	 */
	public $responseSize = 0;

	/**
	 * 返回状态码
	 *
	 * @var string
	 */
	public $responseCode = '';

	/**
	 * Http request body
	 *
	 * @var string
	 */
	public $requestBody;

	/**
	 * Http response body
	 *
	 * @var string
	 */
	public $responseBody;

	/**
	 * Http request path
	 *
	 * @var string
	 */
	public $path = '/';

	/**
	 * Response start time.
	 *
	 * @var string
	 */
	public $responseStartTime = 0;

	/**
	* 客户端请求数据处理入口
	* 用于初始化远程异步链接，统计请求数据等
	*
	* @param string $buffer 客户端发来的第一个包
	* @return void
	*/
	public function requestProcess($request)
	{
		$this->startTime =  microtime(true);
		$this->startTimeInt = (int)str_replace('.', '', $this->startTime);
		$this->requestSize = strlen($request);
		$this->requestDecode($request);
		if (!$this->asyncTcpConnection) {
			// 建议与目标服务器的异步连接
			$this->initRemoteConnection();
			$this->Pipe($request);
		}
		if ($this->protocol == 'HTTPS' || $this->filter("{$this->host}:{$this->port}")) {
			return ;
		}
		$this->requestStatistic();
	}

	/**
	 * 服务端数据处理
	 *
	 * @var string $response
	 * @return void
	 */
	public function responseProcess($response)
	{
		$this->responseStartTime = !$this->responseStartTime ? microtime(true) : $this->responseStartTime;
		$this->responseSize = strlen($response);
		$this->responseDecode($response);
		$this->responseStatistic();
	}

	/**
	 * 建立到目标服务器连接
	 */
	public function initRemoteConnection()
	{
		//  初始化异步链接，并建立通信管道
    	$this->asyncTcpConnection = new AsyncTcpConnection("tcp://{$this->host}:{$this->port}");

    	// 建立管道
		$this->asyncTcpConnection->pipe($this->connection);
		$this->connection->pipe($this->asyncTcpConnection);
  		$this->initRemoteCapture();
		// 链接至目标服务器
		$this->asyncTcpConnection->connect();
	}

	/**
	 * 设置客户端数据处理回调
	 */
	public function initClientCapture()
	{
		$proxy = $this;
		$this->connection->onMessageCapture = function($data) use (&$proxy) {
			if ($proxy->protocol == 'HTTPS') {
				return ;
			}
    		$proxy->data .= $data;
			if (!($length = Http::input($proxy->data))) {
				return ;
			}
			$request = substr($proxy->data, 0, $length);
			$proxy->data = substr($proxy->data, $length, strlen($proxy->data));
			$proxy->requestProcess($request);
    	};
	}
	
	/**
	 * 设置服务端数据处理回调
	 */
	public function initRemoteCapture()
	{
    	$proxy = $this;
		$this->asyncTcpConnection->onMessageCapture = function($data) use (&$proxy) {
			if ($proxy->protocol == 'HTTPS' || $proxy->filter($proxy->host)) {
				return ;
			}
			$proxy->response .= $data;
			if (!($length = Http::output($proxy->response))) {
				return ;
			}
			$response = substr($proxy->response, 0,$length);
			$proxy->response = substr($proxy->response, $length, strlen($proxy->data));
			$proxy->responseProcess($response);
		};
	}

	/**
	 * 管道函数，用于处理鉴别HTTPS和HTTP请求
	 *
	 * @param string $data
	 * @return void
	 */
	public function Pipe($data)
	{
		if (strcmp($this->method,'CONNECT') === 0) {
			$this->protocol = 'HTTPS';
    		return $this->connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
    	}

		return $this->asyncTcpConnection->send($data);
     	
	}

	/**
	 * 解析HTTP请求数据
	 *
	 * @param string $data
	 * @return void
	 */
	protected function requestDecode($data)
	{
		list($this->requestHeader, $body) = explode("\r\n\r\n", $data, 2);
		list($firstLine, $this->requestHeader) = explode("\r\n", $this->requestHeader, 2);
		list($this->method, $url, $this->protocol) = explode(" ", $firstLine);

		$this->requestBody = !$body ? '[null]' : $body;
		$this->urlComponents($url);
	}

	/**
	 * 解析HTTP响应数据
	 *
	 * @param string $data
	 * @return void
	 */
	protected function responseDecode($data)
	{
		list($this->responseHeader, $body) = explode("\r\n\r\n", $data, 2);
		list($firstLine, $this->responseHeader) = explode("\r\n", $this->responseHeader, 2);
		list(, $status, $message) = explode(" ", $firstLine, 3);

		$this->responseCode = "$status [ $message ]";
		$this->responseBody = $this->getBody($body);
	}

	/**
	 * 服务端返回数据BODY处理
	 * 图片资源不做统计
	 * 由于json只支持UTF-8，所以转码为UTF-8数据
	 * gzip 数据解压
	 *
	 * @param string $body
	 * @return string
	 */
	protected function getBody($body = '')
	{
		if ($body == '') {
			return '[null]';
		}
		$contentType = Http::contentType($this->responseHeader);
		if (!$contentType || !in_array($contentType, $this->acceptedContentType)) {
			return '[unknown]';
		}
		
		$contentEncoding = Http::contentEncoding($this->responseHeader);
		if ($contentEncoding == 'gzip') {
			// gzip 解压
			$body = Http::unGzip($body, (bool)strpos($this->responseHeader, 'Transfer-Encoding: chunked'));
		}

		// json_encode 仅支持UTF-8编码的数据
		return stripslashes(mb_convert_encoding($body, 'UTF-8', Http::$supportCharset));
	}

	/**
	 * 解析Url
	 *
	 * @param string $url
	 * @return void
	 */
	public function urlComponents($url)
	{
		$components = parse_url($url);
		$this->host = $components['host'];
		$this->scheme = isset($components['scheme']) ? $components['scheme'] : $this->scheme;
		$this->port =  !isset($components['port']) ? '80' : $components['port'];
		$this->path = !isset($components['path']) ? $this->path : $components['path'];
		// $this->host =  $this->host . ':' . $this->port;
		$this->query = !isset($components['query']) ? $this->query : $components['query'];
		$this->entityHost = !$this->scheme ? $this->host : $this->scheme . '://' . $this->host;
		$this->url = $this->path == '/' ? 'default' : substr($this->path, 1, strlen($this->path));
	}

	/**
	 * 统计客户端请求数据
	 */
	protected function requestStatistic()
	{
		if (!isset(static::$statisticData[$this->startTimeInt])) {
			static::$statisticData[$this->startTimeInt] = array();
		}

		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url] = array(
			'Path'          => $this->path,
			'Method'        => $this->method,
			'Query'         => $this->query,
			'Protocol'      => $this->protocol,
			'ClientIP'      => $this->connection->getRemoteIp(),
			'RequestSize'   => $this->requestSize,
			'StartTime'     => String::formatMicroTime($this->startTime),
			'RequestHeader' => str_replace("\r\n", "<br />", $this->requestHeader),
			'RequestBody'   => $this->requestBody
			);
	}

	/**
	 * 统计服务端返回数据
	 */
	protected function responseStatistic()
	{
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['Path'] = $this->path;
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['StartTime'] = String::formatMicroTime($this->startTime);
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['EndTime'] = String::formatMicroTime(microtime(true));
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['ServerIP'] = $this->asyncTcpConnection->getRemoteIp();
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['ResponseSize'] = $this->responseSize;
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['ResponseCode'] = $this->responseCode;
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['ResponseHeader'] = str_replace("\r\n", "<br />", $this->responseHeader);;
		static::$statisticData[$this->startTimeInt][$this->entityHost][$this->url]['ResponseBody'] = htmlspecialchars($this->responseBody);
	}
}
