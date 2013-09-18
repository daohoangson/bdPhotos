<?php

class bdPhotos_XenForo_Image_Imagemagick_Pecl extends XFCP_bdPhotos_XenForo_Image_Imagemagick_Pecl
{
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
		$this->_bdPhotos_fixOrientation();

		foreach ($this->_image AS $frame)
		{
			$frame->stripImage();
		}
	}

	public function bdPhotos_getEntropy($x, $y, $width, $height)
	{
		$this->_bdPhotos_fixOrientation();

		foreach ($this->_image AS $frame)
		{
			$pixels = $frame->getPixelRegionIterator(intval($x), intval($y), intval($width), intval($height));
			break;
		}

		$histSize = 0;
		$histogram = array();

		foreach ($pixels as $rowPixels)
		{
			foreach ($rowPixels as $pixel)
			{
				$color = $pixel->getColor();
				$grayscale = floor(min(255, max(0, $color['r'] * 0.2989 + $color['g'] * 0.5870 + $color['b'] * 0.1140)));
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

	protected function _bdPhotos_fixOrientation()
	{
		if (!empty($this->_bdPhotos_manualOrientation))
		{
			$pixel = new ImagickPixel();

			foreach ($this->_image AS $frame)
			{
				switch ($this->_bdPhotos_manualOrientation)
				{
					case bdPhotos_Helper_Image::ORIENTATION_UP_SIDE_DOWN:
						$frame->rotateImage($pixel, 180);
						break;
					case bdPhotos_Helper_Image::ORIENTATION_LEFT:
						$frame->rotateImage($pixel, 90);
						break;
					case bdPhotos_Helper_Image::ORIENTATION_RIGHT:
						$frame->rotateImage($pixel, -90);
						break;
				}

				$frame->setImagePage($this->_width, $this->_height, 0, 0);
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
