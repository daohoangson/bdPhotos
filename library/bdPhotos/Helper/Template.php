<?php

class bdPhotos_Helper_Template
{
	public static function formatCssProperty($type, $value)
	{
		switch ($type)
		{
			case 'pixel':
				if (is_numeric($value))
				{
					return sprintf('%dpx', $value);
				}
				break;
		}

		return $value;
	}

	public static function formatLocationName($location)
	{
		if (empty($location['location_info']))
		{
			return $location['location_name'];
		}

		$info = @unserialize($location['location_info']);

		$parts = array();

		if (!empty($info['_source']) AND $info['_source'] == 'maps.googleapis.com')
		{
			foreach ($info['address_components'] as $component)
			{
				$include = false;

				foreach ($component['types'] as $type)
				{
					if (in_array($type, array(
						'locality',
						'administrative_area_level_2',
						'administrative_area_level_1'
					)))
					{
						$include = true;
					}
				}

				if ($include)
				{
					$parts[] = $component['long_name'];
				}
				else
				{
					if (in_array('country', $component['types']))
					{
						$parts[] = $component['short_name'];
					}
				}
			}

		}

		if (count($parts) > 1 AND count($parts) < 4)
		{
			return implode(', ', $parts);
		}
		else
		{
			return $location['location_name'];
		}
	}

	public static function getStaticMapForLocation($width, $height, $location)
	{
		$apiKey = bdPhotos_Option::get('googleMapsApiKey');

		if (!empty($apiKey))
		{
			$staticMap = bdPhotos_Helper_GoogleMapsApi::getStaticMapForBounds($apiKey, $width, $height, $location['ne_lat'], $location['ne_lng'], $location['sw_lat'], $location['sw_lng']);

			if (is_callable(array(
				'bdImage_Integration',
				'buildThumbnailLink'
			)))
			{
				$staticMap = bdImage_Integration::buildThumbnailLink($staticMap, $width, $height);
			}

			return $staticMap;
		}

		return false;
	}

	public static function snippetOrDefault($text, $default)
	{
		if (empty($text))
		{
			return $default;
		}
		else
		{
			$args = func_get_args();
			$text = array_shift($args);
			$default = array_shift($args);
			array_unshift($args, $text);

			return XenForo_Template_Helper_Core::callHelper('snippet', $args);
		}
	}

}
