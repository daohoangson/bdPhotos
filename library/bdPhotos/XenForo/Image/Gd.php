<?php

class bdPhotos_XenForo_Image_Gd extends XFCP_bdPhotos_XenForo_Image_Gd
{
	protected $_bdPhotos_manualOrientation = false;

	public function bdPhotos_dropFramesLeavingThree()
	{
		// gd doesn't support frames
	}

	public function bdPhotos_getEntropy()
	{
		$this->_bdPhotos_fixOrientation();

		$histSize = 0;
		$histogram = array();

		for ($i = 0; $i < $this->_height; $i++)
		{
			for ($j = 0; $j < $this->_width; $j++)
			{
				$rgb = imagecolorat($this->_image, $j, $i);
				$grayscale = floor(min(255, max(0, (($rgb>>16) & 0xFF) * 0.2989 + (($rgb>>8) & 0xFF) * 0.5870 + ($rgb & 0xFF) * 0.1140)));
				if (empty($histogram[$grayscale]))
				{
					$histogram[$grayscale] = 1;
				}
				else
				{
					$histogram[$grayscale]++;
				}
				$histSize++;
			}
		}

		$sum = 0;
		foreach ($histogram as $p)
		{
			if ($p != 0)
			{
				$sum += ($p / $histSize) * log($p, 2);
			}
		}

		return -$sum;
	}

	public function bdPhotos_setManualOrientation($orientation)
	{
		if (!bdPhotos_Option::get('doExifRotate'))
		{
			return;
		}

		$this->_bdPhotos_manualOrientation = $orientation;

		if (in_array($orientation, array(
			bdPhotos_Helper_Image::ORIENTATION_LEFT,
			bdPhotos_Helper_Image::ORIENTATION_RIGHT
		)))
		{
			// update width and height for the image
			// we do not perform roting here because sometime the caller
			// only needs the dimensions
			// the image will be rotated before crop/thumbnail
			$tmp = $this->_width;
			$this->_width = $this->_height;
			$this->_height = $tmp;
		}
	}

	public function bdPhotos_strip()
	{
		// gd always strips!
	}

	public function bdPhotos_thumbnail($width, $height)
	{
		$this->_bdPhotos_fixOrientation();

		$newImage = imagecreatetruecolor($width, $height);
		$this->_preallocateBackground($newImage);

		imagecopyresampled($newImage, $this->_image, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
		$this->_setImage($newImage);

		return true;
	}

	protected function _bdPhotos_fixOrientation()
	{
		if (!empty($this->_bdPhotos_manualOrientation))
		{
			switch ($this->_bdPhotos_manualOrientation)
			{
				case bdPhotos_Helper_Image::ORIENTATION_UP_SIDE_DOWN:
					$rotated = imagerotate($this->_image, 180, 0);
					break;
				case bdPhotos_Helper_Image::ORIENTATION_LEFT:
					$rotated = imagerotate($this->_image, -90, 0);
					break;
				case bdPhotos_Helper_Image::ORIENTATION_RIGHT:
					$rotated = imagerotate($this->_image, 90, 0);
					break;
			}

			if (!empty($rotated))
			{
				// TODO: use _setImage?
				imagedestroy($this->_image);
				$this->_image = $rotated;
			}

			$this->_bdPhotos_manualOrientation = false;
		}
	}

	public function thumbnail($maxWidth, $maxHeight = 0)
	{
		$this->_bdPhotos_fixOrientation();

		return parent::thumbnail($maxWidth, $maxHeight);
	}

	public function thumbnailFixedShorterSide($shortSideWidth)
	{
		$this->_bdPhotos_fixOrientation();

		return parent::thumbnailFixedShorterSide($shortSideWidth);
	}

	public function crop($x, $y, $width, $height)
	{
		$this->_bdPhotos_fixOrientation();

		return parent::crop($x, $y, $width, $height);
	}

	public function output($outputType, $outputFile = null, $quality = 85)
	{
		$this->_bdPhotos_fixOrientation();

		return parent::output($outputType, $outputFile, $quality);
	}

}
