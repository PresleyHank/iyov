<?php
namespace Applications\iyov\Lib;

/**
 * Http协议解析
 */
class Http {
	/**
	 *
	 */
	public static $supportCharset = array('UTF-8','GBK');
	
	/**
	 * 检验请求包是否完整
	 *
	 * @param string $data
	 * @return int
	 */
	public static function input($data)
	{
		if (strpos($data, "\r\n\r\n") === false) {
			return 0;
		}

		list($header, $body) = explode("\r\n\r\n", $data);
		if (0 === strpos($header, "POST")) {
            $match = array();
            if (preg_match("/\r\nContent-Length: ?(\d+)/i", $header, $match)) {
                $contentLength = $match[1];
                if ($contentLength <= strlen($body)) {
                	return strlen($header) + 4 + $contentLength;
                }
                return 0;
            }
            return 0;
        }

        return strlen($header) + 4;
	}

	/**
	 * 检查响应包是否完整
	 *
	 * @param string $data
	 * @return int
	 */
	public static function output($data)
	{
		if (strpos($data, "\r\n\r\n") === false) {
			return 0;
		}

		list($header, $body) = explode("\r\n\r\n", $data, 2);
		if (FALSE !== strpos($header, 'Transfer-Encoding: chunked')) {
			$spices = explode("\r\n", $body);
			for ($last = array_pop($spices); $last == "" && !empty($spices); $last = array_pop($spices)) {
				continue;
			}
			if (!is_numeric($last) || $last != 0) {
				return 0;
			}
			return strlen($header) + 4 + strlen($body);
		} else if (preg_match("/\r\nContent-Length: ?(\d+)/i", $header, $match)) {
			$contentLength = $match[1];
			if ($contentLength <= strlen($body)) {
				return strlen($header) + 4 + $contentLength;
			}
			return 0;
		}
        return strlen($header) + 4;
		
	}

	/**
	 * 返回Content-Type
	 *
	 * @param string $header
	 * @return string 
	 */
	public static function contentType($header = '')
	{
		if (preg_match("/(?<=Content-Type: )\S+[\s]*[\w=-]*(?=\r\n)/", $header, $match)) {
			$spices = explode(";", $match[0]);
			return array_shift($spices);
		}

		return "";
	}

	public static function contentEncoding($header)
	{
		if (preg_match("/Content-Encoding: (\w+)/", $header, $match)) {
			$spices = explode(" ", $match[0]);
			return $spices[1];
		}

		return "";
	}

	/**
	 * Gzip解压缩
	 * 补充说明：如果为chunkded，则需要特殊内容，chunked数据格式
	 * [Chunk大小][\r\n][Chunk数据体][\r\n]...[0][\r\n][footer（可能有）][\r\n][\r\n]
	 * 0 表示数据体的结束，并不表示footer长度，如果有footer还有追加到数据缓存中
	 * Chunk大小为16进制
	 *
	 * @param string $data
	 * @param bool   $chunked 是否为chunked传输
	 * @return string
	 */
	public static function unGzip($data = '', $chunked = false)
	{
		if ($data == '') {
			return '';
		}

		if ($chunked) {
			$temp = '';
			list($length16, $data) = explode("\r\n", $data, 2);
			for (; hexdec($length16) != 0; list($length16, $data) = explode("\r\n", $data, 2)) {
				$temp .= substr($data, 0, hexdec($length16));
				// 2 表示去掉Chunk大小前的\r\n字符
				$data = substr($data, hexdec($length16) + 2, strlen($data));
			}
			// 可能有footer存在
			$data = trim($data, "\r\n") == "" ? $temp : $temp . substr($temp, 0, strlen($temp) - 2);
		}

		return gzdecode($data);
	}
}