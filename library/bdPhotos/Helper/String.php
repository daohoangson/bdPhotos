<?php

class bdPhotos_Helper_String
{
	public static function formatFloat($float)
	{
		$precision = 0;

		while ($float - round($float, $precision) > 0.0001)
		{
			$precision++;
		}

		return sprintf('%.' . $precision . 'f', $float);
	}

}
