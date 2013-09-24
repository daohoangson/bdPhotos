<?php

class bdPhotos_Helper_Crypt
{
	public static function encrypt($data, $key = '')
	{
		return base64_encode(self::aes128_encrypt(json_encode($data), $key . XenForo_Application::getConfig()->get('globalSalt')));
	}

	public static function decrypt($data, $key = '')
	{
		return json_decode(self::aes128_decrypt(base64_decode($data), $key . XenForo_Application::getConfig()->get('globalSalt')), true);
	}

	public static function aes128_encrypt($data, $key)
	{
		$key = md5($key, true);
		$padding = 16 - (strlen($data) % 16);
		$data .= str_repeat(chr($padding), $padding);
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB);
	}

	public static function aes128_decrypt($data, $key)
	{
		$key = md5($key, true);
		$data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB);
		$padding = ord($data[strlen($data) - 1]);
		return substr($data, 0, -$padding);
	}

}
