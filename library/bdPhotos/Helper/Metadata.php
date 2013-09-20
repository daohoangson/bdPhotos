<?php

class bdPhotos_Helper_Metadata
{

	public static function cleanUpExifDataAfterStripping(array $exifData)
	{
		if (!empty($exifData['Orientation']))
		{
			unset($exifData['Orientation']);
		}

		return $exifData;
	}

	public static function extractDeviceCodeFromExifData(array $exifData)
	{
		$manufacture = false;
		$code = false;

		if (!empty($exifData['Make']) AND !empty($exifData['Model']))
		{
			list($manufacture, $code) = self::normalizeDeviceCode($exifData['Make'], $exifData['Model']);
		}

		return array(
			$manufacture,
			$code
		);
	}

	public static function extractLatLngFromExifData(array $exifData)
	{
		$lat = false;
		$lng = false;

		if (!empty($exifData['GPSLatitude']) AND !empty($exifData['GPSLongitude']) AND !empty($exifData['GPSLatitudeRef']) AND !empty($exifData['GPSLongitudeRef']))
		{
			$lat_deg = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLatitude'][0]);
			$lat_min = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLatitude'][1]);
			$lat_sec = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLatitude'][2]);
			$lat_hemi = $exifData['GPSLatitudeRef'];

			$lng_deg = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLongitude'][0]);
			$lng_min = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLongitude'][1]);
			$lng_sec = self::_extractLatLngFromExifData_parseFraction($exifData['GPSLongitude'][2]);
			$lng_hemi = $exifData['GPSLongitudeRef'];

			$lat = self::_extractLatLngFromExifData_toDecimal($lat_deg, $lat_min, $lat_sec, $lat_hemi);
			$lng = self::_extractLatLngFromExifData_toDecimal($lng_deg, $lng_min, $lng_sec, $lng_hemi);

			// use 10e6 values of lat and lng to make them easy to store
			$lat = floor($lat * pow(10, 6));
			$lng = floor($lng * pow(10, 6));
		}

		return array(
			$lat,
			$lng
		);
	}

	public static function extractOrientationFromExifData(array $exifData)
	{
		if (!empty($exifData['Orientation']))
		{
			return $exifData['Orientation'];
		}

		return false;
	}

	public static function normalizeDeviceCode($manufacture, $code)
	{
		$manufacture = utf8_strtolower($manufacture);
		$code = utf8_strtolower($code);

		if (strpos($code, $manufacture) === 0)
		{
			// manufacture includes in code, drop that part
			$code = utf8_substr($code, utf8_strlen($manufacture));
		}

		$manufacture = preg_replace('/[^a-z0-9]/i', '_', $manufacture);
		$code = preg_replace('/[^a-z0-9]/i', '_', $code);

		$manufacture = utf8_trim($manufacture, '_');
		$code = utf8_trim($code, '_');

		return array(
			$manufacture,
			$code
		);
	}

	public static function readExifDataFromFile($path)
	{
		$exifData = false;

		if (function_exists('exif_read_data'))
		{
			$exifData = @exif_read_data($path, 0);

			if (!empty($exifData))
			{
				foreach ($exifData as $key => &$value)
				{
					if (is_array($value))
					{
						// array, do not process array value
						continue;
					}

					if (is_string($value))
					{
						$value = utf8_bad_replace($value, '');
					}

					switch ($key)
					{
						case 'DateTime':
						case 'DateTimeOriginal':
						case 'DateTimeDigitized':
							try
							{
								$valueDate = new DateTime($value);
								$value = $valueDate->format('U');
							}
							catch (Exception $e)
							{
								$value = 0;
							}
							break;
					}
				}
			}
		}

		return $exifData;
	}

	public static function readFromFile($path)
	{
		$metadata = array();

		$exifData = self::readExifDataFromFile($path);
		if (!empty($exifData))
		{
			$metadata['exif'] = $exifData;

			list($lat, $lng) = self::extractLatLngFromExifData($exifData);
			if ($lat !== false AND $lng !== false)
			{
				$metadata['lat'] = $lat;
				$metadata['lng'] = $lng;
			}

			list($manufacture, $code) = self::extractDeviceCodeFromExifData($exifData);
			if ($manufacture !== false AND $code !== false)
			{
				$metadata['manufacture'] = $manufacture;
				$metadata['code'] = $code;
			}
		}

		return $metadata;
	}

	protected static function _extractLatLngFromExifData_parseFraction($fraction)
	{
		$parts = explode('/', $fraction);

		if (count($parts) != 2 OR !is_numeric($parts[0]) OR !is_numeric($parts[1]) OR empty($parts[1]))
		{
			return 0;
		}
		else
		{
			return $parts[0] / $parts[1];
		}
	}

	protected static function _extractLatLngFromExifData_toDecimal($deg, $min, $sec, $hemi)
	{
		$value = $deg + $min / 60 + $sec / 3600;
		return ($hemi == 'S' OR $hemi == 'W') ? $value *= -1 : $value;
	}

}
