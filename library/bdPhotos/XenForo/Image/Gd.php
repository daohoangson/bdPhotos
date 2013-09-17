<?php

class bdPhotos_XenForo_Image_Gd extends XFCP_bdPhotos_XenForo_Image_Gd
{
	protected $_bdPhotos_manualOrientation = false;

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
	
	public function bdPhotos_getEntropy()
	{
		// TODO
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
