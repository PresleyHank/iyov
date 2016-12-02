<?php
namespace Applications\iyov\Lib;

/**
 * 字符串处理工具类
 */
class String {
	
	/**
	 * 是否为json数据
	 *
	 * @var string $data
	 * @return bool
	 */
	public static function isValidJson($data)
	{
		if (is_null(json_decode($data, true))) {
			return false;
		}

		return true;
	}

	/**
	 * 格式化显示毫秒时间
	 *
	 * @var string $time
	 * @return string
	 */
	public static function formatMicroTime($time)
	{
		if (!is_numeric($time)) {
			return "";
		}

		$spices = explode(".",  $time);
		return count($spices) == 1 ? date('Y-m-d h:i:s', $spices[0]) : str_replace("x", $spices[1], date('Y-m-d h:i:s.x', $time));
	}
}