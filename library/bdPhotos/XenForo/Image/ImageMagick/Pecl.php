<?php

class bdPhotos_XenForo_Image_Imagemagick_Pecl extends XFCP_bdPhotos_XenForo_Image_Imagemagick_Pecl
{
    protected $_bdPhotos_manualOrientation = false;

    public function bdPhotos_dropFramesLeavingThree()
    {
        $this->_bdPhotos_fixOrientation();

        $count = $this->_image->getNumberImages();
        if ($count <= 3) {
            return false;
        }

        $keepCount = min(15, max(3, floor($count / 10)));
        $keepStep = floor($count / $keepCount);
        $keep = array();
        $i = 0;
        foreach ($this->_image as $frame) {
            if ($i % $keepStep == 0) {
                // keep track of the frame delay
                $keep[$i] = $frame->getImageDelay();
            } else {
                // get sum of delays of skipped frames
                $keep[intval(floor($i / $keepStep) * $keepStep)] += $frame->getImageDelay();
            }

            $i++;
        }

        // make sure the last frame has quite long delay
        $keepKeys = array_keys($keep);
        $keepKeyFirst = array_shift($keepKeys);
        $keepKeyLast = array_pop($keepKeys);
        $keep[$keepKeyLast] += $keep[$keepKeyFirst];

        $newImage = new Imagick();

        $i = 0;
        foreach ($this->_image as $frame) {
            if (!empty($keep[$i])) {
                $frame->setImageDelay(min($keep[$i], $keep[$i] / $keepCount * 2));
                $newImage->addImage($frame->getImage());
            }

            $i++;
        }

        $this->_image->destroy();
        $this->_image = $newImage;

        return true;
    }

    public function bdPhotos_removeBorder()
    {
        // TODO
    }

    public function bdPhotos_getEntropy($x, $y, $width, $height)
    {
        $this->_bdPhotos_fixOrientation();

        $pixels = array();
        foreach ($this->_image AS $frame) {
            $pixels = $frame->getPixelRegionIterator(intval($x), intval($y), intval($width), intval($height));
            break;
        }

        $histSize = 0;
        $histogram = array();

        foreach ($pixels as $rowPixels) {
            /** @var ImagickPixel $pixel */
            foreach ($rowPixels as $pixel) {
                $color = $pixel->getColor();
                $grayscale = intval(floor(min(255, max(0, $color['r'] * 0.2989 + $color['g'] * 0.5870 + $color['b'] * 0.1140))));
                if (empty($histogram[$grayscale])) {
                    $histogram[$grayscale] = 1;
                } else {
                    $histogram[$grayscale]++;
                }
                $histSize++;
            }
        }

        $sum = 0;
        foreach ($histogram as $p) {
            if ($p != 0) {
                $sum += ($p / $histSize) * log($p, 2);
            }
        }

        return -$sum;
    }

    public function bdPhotos_setManualOrientation($orientation)
    {
        if (!bdPhotos_Option::get('doExifRotate')) {
            return;
        }

        $this->_bdPhotos_manualOrientation = $orientation;

        if (in_array($orientation, array(
            bdPhotos_Helper_Metadata::ORIENTATION_LEFT,
            bdPhotos_Helper_Metadata::ORIENTATION_RIGHT
        ))) {
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

        foreach ($this->_image AS $frame) {
            $frame->stripImage();
        }

        return true;
    }

    public function bdPhotos_thumbnail($width, $height)
    {
        $this->_bdPhotos_fixOrientation();

        try {
            foreach ($this->_image AS $frame) {
                $frame->thumbnailImage($width, $height, true);
                $frame->setImagePage($frame->getImageWidth(), $frame->getImageHeight(), 0, 0);
            }
            $this->_updateDimensionCache();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function _bdPhotos_fixOrientation()
    {
        if (!empty($this->_bdPhotos_manualOrientation)) {
            $pixel = new ImagickPixel();

            foreach ($this->_image AS $frame) {
                switch ($this->_bdPhotos_manualOrientation) {
                    case bdPhotos_Helper_Metadata::ORIENTATION_UP_SIDE_DOWN:
                        $frame->rotateImage($pixel, 180);
                        break;
                    case bdPhotos_Helper_Metadata::ORIENTATION_LEFT:
                        $frame->rotateImage($pixel, 90);
                        break;
                    case bdPhotos_Helper_Metadata::ORIENTATION_RIGHT:
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

        parent::crop($x, $y, $width, $height);
    }

    public function output($outputType, $outputFile = null, $quality = 85)
    {
        $this->_bdPhotos_fixOrientation();

        return parent::output($outputType, $outputFile, $quality);
    }

}
