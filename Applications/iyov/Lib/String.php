<?php
namespace Applications\iyov\Lib;

/**
 * 字符串处理工具类
 */
class String {
	/**
	 * 是否为json数据
	 */
	public static function isValidJson($data)
	{
		if (is_null(json_decode($data, true))) {
			return false;
		}

		return true;
	}
}