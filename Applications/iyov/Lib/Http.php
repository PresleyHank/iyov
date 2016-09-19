<?php
namespace Applications\iyov\Lib;

/**
 * Http协议解析
 */
class Http {
	/**
	 * 检验请求包是否完整
	 */
	public static function input($data)
	{
		if (strpos($data, "\r\n\r\n") === false) {
			return 0;
		}

		list($header, $body) = explode("\r\n\r\n", $data);
		if (0 === strpos($header, "POST")) {
            // find Content-Length
            $match = array();
            if (preg_match("/\r\nContent-Length: ?(\d+)/i", $header, $match)) {
                $contentLength = $match[1];
                if ($contentLength <= strlen($body)) {
                	return strlen($header) + 4 + $contentLength;
                }
            } else {
                return 0;
            }
        }

        return strlen($header) + 4;
	}

	/**
	 * 检查响应包是否完整
	 */
	public static function output($data)
	{
		if (strpos($data, "\r\n\r\n") === false) {
			return 0;
		}

		list($header, $body) = explode("\r\n\r\n", $data);
		if (preg_match("/\r\nContent-Length: ?(\d+)/i", $header, $match)) {
			$contentLength = $match[1];
			if ($contentLength <= strlen($body)) {
				return strlen($header) + 4 + $contentLength;
			}
		}
        
        return strlen($header) + 4;
		
	}
}