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
	 * Accect Content Type
	 */
	private $acceptedContentType = array('text/html','text/xml', 'application/json');

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

	public $requestSize = 0;
	public $responseSize = 0;
	public $responseCode = '';

	/**
	 * Http request body
	 */
	public $requestBody;

	/**
	 * Http response body
	 */
	public $responseBody;

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
	}

	/**
	* 代理流程入口
	*
	* @param string $buffer 客户端发来的第一个包
	*/
	public function requestProcess($request)
	{
		$this->startTime =  microtime(true);
		$this->startTimeInt = (int)str_replace('.', '', $this->startTime);
		$this->requestSize = strlen($request);
		$this->requestDecode($request);
		if (!$this->asyncConnection) {
			// 建议与目标服务器的异步连接
			$this->initRemoteConnection();
			$this->pipe($request);
		}
		// 解析请求数据
		if ($this->filter("{$this->host}:{$this->port}")) {
			return ;
		}
		$this->requestStatistic();
	}

	public function responseProcess($response)
	{
		$this->responseStartTime = !$this->responseStartTime ? microtime(true) : $this->responseStartTime;
		$this->responseSize = strlen($response);
		$this->responseDecode($response);
		$this->responseStatistic();
	}

	public function initRemoteConnection()
	{
		//  初始化异步链接，并建立通信管道
    	$this->asyncConnection = new AsyncTcpConnection("tcp://{$this->host}:{$this->port}");

    	// 建立管道
		$this->asyncConnection->pipe($this->connection);
		$this->connection->pipe($this->asyncConnection);
  		$this->initRemoteCapture();
		// 链接至目标服务器
		$this->asyncConnection->connect();
	}

	public function initClientCapture()
	{
		$proxy = $this;
		$this->connection->onMessageCapture = function($data) use ($proxy) {
    		$proxy->data .= $data;
			if (!($length = Http::input($proxy->data))) {
				return ;
			}
			$request = substr($this->data, 0, $length);
			$this->data = substr($this->data, $length, strlen($this->data));
			$proxy->requestProcess($request);
    	};
	}
	
	public function initRemoteCapture()
	{
    	$proxy = $this;
		$this->asyncConnection->onMessageCapture = function($data) use (&$proxy) {
			$proxy->response .= $data;
			if (!($length = Http::output($proxy->response))) {
				return ;
			}
			$response = substr($proxy->response, 0,$length);
			$proxy->response = substr($proxy->response, $length, strlen($proxy->data));
			$proxy->responseProcess($response);
		};
	}

	public function pipe($data)
	{
		if (strcmp($this->method,'CONNECT') === 0) {
			// HTTPS
    		return $this->connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
    	}
    	// HTTP
		return $this->asyncConnection->send($data);
     	
	}

	/**
	 * 解析Http请求包头
	 */
	protected function requestDecode($data)
	{
		list($this->requestHeader, $body) = explode("\r\n\r\n", $data, 2);
		$this->requestBody = !$body ? '' : $body;
		list($firstLine, $this->requestHeader) = explode("\r\n", $this->requestHeader, 2);
		$this->requestHeader = str_replace("\r\n", "<br />", $this->requestHeader);

		list($this->method, $url, $this->protocol) = explode(" ", $firstLine);
		$this->urlComponents($url);
	}

	/**
	 * 解析Http相应包头
	 */
	protected function responseDecode($data)
	{
		list($this->responseHeader, $body) = explode("\r\n\r\n", $data, 2);
		list($firstLine, $this->responseHeader) = explode("\r\n", $this->responseHeader, 2);
		list(, $status, $message) = explode(" ", $firstLine, 3);

		$this->responseCode = "$status [ $message ]";
		$this->responseBody = $this->getBody($body);
		$this->responseHeader = str_replace("\r\n", "<br />", $this->responseHeader);
	}

	/**
	 * 统计Request数据
	 */
	protected function requestStatistic()
	{
		if (!isset(static::$statisticData[$this->getClientIp()])) {
			static::$statisticData[$this->getClientIp()] = array();
		}

		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url] = array(
			'Path'          => $this->path,
			'Method'        => $this->method,
			'Query'         => $this->query,
			'Protocol'      => $this->protocol,
			'ClientIP'      => $this->getClientIp(),
			'RequestSize'   => $this->requestSize,
			'StartTime'     => String::formatMicroTime($this->startTime),
			'RequestHeader' => $this->requestHeader,
			'RequestBody'   => $this->requestBody
			);
	}

	/**
	 * 统计Response数据
	 */
	protected function responseStatistic()
	{
		
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['Path'] = $this->path;
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['StartTime'] = String::formatMicroTime($this->startTime);
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['EndTime'] = String::formatMicroTime(microtime(true));
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['ServerIP'] = $this->getServerIp();
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['ResponseSize'] = $this->responseSize;
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['ResponseCode'] = $this->responseCode;
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['ResponseHeader'] = $this->responseHeader;
		static::$statisticData[$this->getClientIp()][$this->startTimeInt][$this->entityHost][$this->url]['ResponseBody'] = $this->responseBody;
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

	protected function getBody($body)
	{
		if (!$this->validBody($this->responseHeader)) {
			return '[unknown]';
		}

		return stripslashes(mb_convert_encoding($body, 'UTF-8', Http::$supportCharset));
	}

	protected function validBody($header)
	{
		return $this->validContentEncoding($header) & $this->validContentType($header);
	}

	

	protected function validContentEncoding($header)
	{
		return !Http::contentEncoding($header);
	}

	protected function validContentType($header)
	{
		$contentType = Http::contentType($header);
		if (!$contentType || !in_array($contentType, $this->acceptedContentType)) {
			return false;
		}

		return true;
	}

	/**
	 * 获取客户端IP
	 * @return string
	 */
	public function getClientIp()
	{
		return $this->connection->getRemoteIp();
	}

	/**
	 * 获取目标服务器IP
	 * @return string
	 */
	public function getServerIp()
	{
		return $this->asyncConnection->getRemoteIp();
	}
}
