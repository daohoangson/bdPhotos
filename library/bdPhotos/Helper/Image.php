<?php

class bdPhotos_Helper_Image
{
	const OPTION_MANUAL_ORIENTATION = 'manualOrientation';
	const ORIENTATION_UP_SIDE_DOWN = 'upSideDown';
	const ORIENTATION_LEFT = 'left';
	const ORIENTATION_RIGHT = 'right';

	public static function getInputTypeFromExtension($extension)
	{
		$inputType = false;

		switch ($extension)
		{
			case 'gif':
				$inputType = IMAGETYPE_GIF;
				break;
			case 'jpg':
			case 'jpeg':
				$inputType = IMAGETYPE_JPEG;
				break;
			case 'png':
				$inputType = IMAGETYPE_PNG;
				break;
		}

		return $inputType;
	}

	public static function getSize($path, $width, $height)
	{
		$size = array(
			0,
			0
		);

		if (file_exists($path))
		{
			$extension = XenForo_Helper_File::getFileExtension($path);
			$inputType = self::getInputTypeFromExtension($extension);
			if (!empty($inputType))
			{
				$image = XenForo_Image_Abstract::createFromFile($path, $inputType);
				if (!empty($image))
				{
					$size[0] = $image->getWidth();
					$size[1] = $image->getHeight();
				}
			}
		}

		return $size;
	}

	public static function prepareOptionsFromExifData(array &$options, array $exifData)
	{
		$exifOrientation = bdPhotos_Helper_Metadata::extractOrientationFromExifData($exifData);

		if (!empty($exifOrientation))
		{
			switch ($exifOrientation)
			{
				case 3:
					$options[self::OPTION_MANUAL_ORIENTATION] = self::ORIENTATION_UP_SIDE_DOWN;
					break;
				case 6:
					$options[self::OPTION_MANUAL_ORIENTATION] = self::ORIENTATION_LEFT;
					break;
				case 8:
					$options[self::OPTION_MANUAL_ORIENTATION] = self::ORIENTATION_RIGHT;
					break;
			}
		}
	}

	public static function resizeAndCrop($inPath, $extension, $width, $height, $outPath, array $options = array())
	{
		$inputType = self::getInputTypeFromExtension($extension);
		$crop = true;

		if (is_array($width) AND empty($height))
		{
			// support setting additional data via an array as $width
			$array = $width;
			$width = $array['width'];
			$height = $array['height'];

			if (isset($array['inputType']))
			{
				$inputType = $array['inputType'];
			}

			if (isset($array['crop']))
			{
				$crop = $array['crop'];
			}
		}

		if (empty($inputType))
		{
			return false;
		}

		$image = XenForo_Image_Abstract::createFromFile($inPath, $inputType);
		if (empty($image))
		{
			return false;
		}

		if (!empty($options[self::OPTION_MANUAL_ORIENTATION]))
		{
			// TODO: check for method availability?
			$image->bdPhotos_setManualOrientation($options[self::OPTION_MANUAL_ORIENTATION]);
		}

		if ($width > 0 AND $height > 0)
		{
			if ($crop)
			{
				// crop mode
				$origRatio = $image->getWidth() / $image->getHeight();
				$cropRatio = $width / $height;
				if ($origRatio > $cropRatio)
				{
					$thumHeight = $height;
					$thumWidth = $height * $origRatio;
				}
				else
				{
					$thumWidth = $width;
					$thumHeight = $width / $origRatio;
				}

				if ($thumWidth <= $image->getWidth() AND $thumHeight <= $image->getHeight())
				{
					$image->thumbnail($thumWidth, $thumHeight);
					$image->crop(0, 0, $width, $height);
				}
				else
				{
					// thumbnail requested is larger then the image size
					if ($origRatio > $cropRatio)
					{
						$image->crop(0, 0, $image->getHeight() * $cropRatio, $image->getHeight());
					}
					else
					{
						$image->crop(0, 0, $image->getWidth(), $image->getWidth() / $cropRatio);
					}
				}
			}
			else
			{
				// resize and make sure both width and height don't exceed the configured values
				$origRatio = $image->getWidth() / $image->getHeight();

				$thumWidth = $width;
				$thumHeight = $thumWidth / $origRatio;

				if ($thumHeight > $height)
				{
					$thumHeight = $height;
					$thumWidth = $thumHeight * $origRatio;
				}

				$image->thumbnail($thumWidth, $thumHeight);
			}
		}
		elseif ($height > 0)
		{
			$targetHeight = $height;
			$targetWidth = $targetHeight / $image->getHeight() * $image->getWidth();
			$image->thumbnail($targetWidth, $targetHeight);
		}
		elseif ($width > 0)
		{
			$targetWidth = $width;
			$targetHeight = $targetWidth / $image->getWidth() * $image->getHeight();
			$image->thumbnail($targetWidth, $targetHeight);
		}
		else
		{
			return false;
		}

		XenForo_Helper_File::createDirectory(dirname($outPath), true);
		$image->output($inputType, $outPath);
		return true;
	}

	public static function stripJpeg($path, array $options = array())
	{
		$tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');

		$image = XenForo_Image_Abstract::createFromFile($path, IMAGETYPE_JPEG);
		if (empty($image))
		{
			return false;
		}

		if (!empty($options[self::OPTION_MANUAL_ORIENTATION]))
		{
			// TODO: check for method availability?
			$image->bdPhotos_setManualOrientation($options[self::OPTION_MANUAL_ORIENTATION]);
		}

		$image->crop(0, 0, $image->getWidth(), $image->getHeight());

		$image->bdPhotos_strip();

		if (!$image->output(IMAGETYPE_JPEG, $tempFile))
		{
			return false;
		}

		return $tempFile;
	}

}
