<?php

class bdPhotos_Helper_Image
{
	const OPTION_MANUAL_ORIENTATION = 'manualOrientation';
	
	const OPTION_ROI = 'roi';

	public static function detectROI($path, $extension, array $options = array())
	{
		// ideas from
		// https://github.com/reddit/reddit/blob/a6a4da72a1a0f44e0174b2ad0a865b9f68d3c1cd/r2/r2/lib/scraper.py#L57-84
		$inputType = self::getInputTypeFromExtension($extension);
		if (empty($inputType))
		{
			return false;
		}

		$image = XenForo_Image_Abstract::createFromFile($path, $inputType);
		if (empty($image))
		{
			return false;
		}

		self::_configureImageFromOptions($image, $options);

		$originalWidth = $image->getWidth();
		$originalHeight = $image->getHeight();
		$image->thumbnailFixedShorterSide(100);
		$_x = 0;
		$_y = 0;
		$_width = $image->getWidth();
		$_height = $image->getHeight();
		$isTall = ($_height > $_width);

		while ($_width != $_height)
		{
			if ($isTall)
			{
				$sliceHeight = min($_height - $_width, 10);
				$sliceWidth = 0;
			}
			else
			{
				$sliceWidth = min($_width - $_height, 10);
				$sliceHeight = 0;
			}

			$entropy1 = $image->bdPhotos_getEntropy($_x, $_y, $_width - $sliceWidth, $_height - $sliceHeight);
			$entropy2 = $image->bdPhotos_getEntropy($_x + $sliceWidth, $_y + $sliceHeight, $_width - $sliceWidth, $_height - $sliceHeight);

			if ($entropy1 > $entropy2)
			{
				// take 1st
			}
			else
			{
				// take 2nd
				$_x += $sliceWidth;
				$_y += $sliceHeight;
			}

			$_width -= $sliceWidth;
			$_height -= $sliceHeight;
		}

		$ratio = $image->getWidth() / $originalWidth;
		$projectedX = floor($_x / $ratio);
		$projectedY = floor($_y / $ratio);
		$projectedWidth = floor($_width / $ratio);

		$roiX = $projectedX + ($projectedWidth / 2);
		$roiY = $projectedY + ($projectedWidth / 2);

		return array(
			0 => round($roiX / $originalWidth, 2),
			1 => round($roiY / $originalHeight, 2),
			'_revision' => 1,
		);
	}

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

	public static function getSize($path)
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
			$options[self::OPTION_MANUAL_ORIENTATION] = $exifOrientation;
		}
	}

	public static function resizeAndCrop($inPath, $extension, &$width, &$height, $outPath, array $options = array())
	{
		$inputType = self::getInputTypeFromExtension($extension);
		$crop = true;
		$thumbnailFixedShorterSide = false;
		$dropFramesLeavingThree = false;

		$_imageHasBeenChanged = false;

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

			if (isset($array['thumbnailFixedShorterSide']))
			{
				$thumbnailFixedShorterSide = $array['thumbnailFixedShorterSide'];
			}

			if (isset($array['dropFramesLeavingThree']))
			{
				$dropFramesLeavingThree = $array['dropFramesLeavingThree'];
			}
		}

		if (empty($inputType))
		{
			return false;
		}

		if (file_exists($outPath))
		{
			// the output file exists already, yay!
			// still need to update the width and height variables though...
			if ($crop)
			{
				// no need to update
			}
			else
			{
				// mark as uncalculated
				$width = 0;
				$height = 0;
			}

			return true;
		}

		$image = XenForo_Image_Abstract::createFromFile($inPath, $inputType);
		if (empty($image))
		{
			return false;
		}

		self::_configureImageFromOptions($image, $options);

		// try to request longer time limit
		@set_time_limit(60);

		if ($dropFramesLeavingThree)
		{
			// TODO: check for method availability?
			if ($image->bdPhotos_dropFramesLeavingThree())
			{
				$_imageHasBeenChanged = true;
			}
		}

		if ($width > 0 AND $height > 0)
		{
			if ($crop)
			{
				$origRatio = round($image->getWidth() / $image->getHeight(), 1);
				$cropRatio = round($width / $height, 1);

				// crop mode
				if ($origRatio != $cropRatio AND !empty($options['roi']))
				{
					// smart cropping using ROI information
					$roiX = floor($image->getWidth() * $options['roi'][0]);
					$roiY = floor($image->getHeight() * $options['roi'][1]);

					if ($origRatio > $cropRatio)
					{
						$cropHeight = $image->getHeight();
						$cropWidth = floor($cropHeight * $cropRatio);
					}
					else
					{
						$cropWidth = $image->getWidth();
						$cropHeight = floor($cropWidth / $cropRatio);
					}

					$cropX = min(max(0, floor($roiX - $cropWidth / 2)), $image->getWidth() - $cropWidth);
					$cropY = min(max(0, floor($roiY - $cropHeight / 2)), $image->getHeight() - $cropHeight);

					if ($cropX != 0 OR $cropY != 0 OR $cropWidth != $image->getWidth() OR $cropHeight != $image->getHeight())
					{
						$image->crop($cropX, $cropY, $cropWidth, $cropHeight);
						$_imageHasBeenChanged = true;
					}

					if ($width != $image->getWidth() OR $height != $image->getHeight())
					{
						$image->bdPhotos_thumbnail($width, $height);
						$_imageHasBeenChanged = true;
					}
				}
				else
				{
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

					if ($thumWidth != $image->getWidth() OR $thumHeight != $image->getHeight())
					{
						$image->bdPhotos_thumbnail($thumWidth, $thumHeight);
						$_imageHasBeenChanged = true;
					}

					if ($width != $image->getWidth() OR $height != $image->getHeight())
					{
						$image->crop(0, 0, $width, $height);
						$_imageHasBeenChanged = true;
					}
				}
			}
			else
			{
				if ($thumbnailFixedShorterSide)
				{
					if ($image->getWidth() > $width OR $image->getHeight() > $height)
					{
						$image->thumbnailFixedShorterSide($width);
						$_imageHasBeenChanged = true;
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

					if ($thumWidth != $image->getWidth() OR $thumHeight != $image->getHeight())
					{
						$image->bdPhotos_thumbnail($thumWidth, $thumHeight);
						$_imageHasBeenChanged = true;
					}
				}
			}
		}
		elseif ($height > 0)
		{
			$targetHeight = $height;
			$targetWidth = $targetHeight / $image->getHeight() * $image->getWidth();

			if ($targetWidth != $image->getWidth() OR $targetHeight != $image->getHeight())
			{
				$image->bdPhotos_thumbnail($targetWidth, $targetHeight);
				$_imageHasBeenChanged = true;
			}
		}
		elseif ($width > 0)
		{
			$targetWidth = $width;
			$targetHeight = $targetWidth / $image->getWidth() * $image->getHeight();

			if ($targetWidth != $image->getWidth() OR $targetHeight != $image->getHeight())
			{
				$image->bdPhotos_thumbnail($targetWidth, $targetHeight);
				$_imageHasBeenChanged = true;
			}
		}
		else
		{
			return false;
		}

		XenForo_Helper_File::createDirectory(dirname($outPath), true);

		$width = $image->getWidth();
		$height = $image->getHeight();

		if ($_imageHasBeenChanged)
		{
			$image->output($inputType, $outPath);
		}
		else
		{
			@copy($inPath, $outPath);
		}

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

		self::_configureImageFromOptions($image, $options);

		$image->crop(0, 0, $image->getWidth(), $image->getHeight());

		$image->bdPhotos_strip();

		if (!$image->output(IMAGETYPE_JPEG, $tempFile))
		{
			return false;
		}

		return $tempFile;
	}

	protected static function _configureImageFromOptions(XenForo_Image_Abstract $image, array $options)
	{
		if (!empty($options[self::OPTION_MANUAL_ORIENTATION]))
		{
			// TODO: check for method availability?
			$image->bdPhotos_setManualOrientation($options[self::OPTION_MANUAL_ORIENTATION]);
		}
	}

}
