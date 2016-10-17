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

	public static function formatMicroTime($time)
	{
		if (!is_numeric($time)) {
			return "";
		}

		$spices = explode(".",  $time);
		return count($spices) == 1 ? date('Y-m-d h:i:s', $spices[0]) : str_replace("x", $spices[1], date('Y-m-d h:i:s.x', $time));
	}
}