<?php

class bdPhotos_XenForo_Image_Gd extends XFCP_bdPhotos_XenForo_Image_Gd
{
    protected $_bdPhotos_manualOrientation = false;

    /**
     * @return bdPhotos_XenForo_Image_Gd
     */
    public function bdPhotos_copy()
    {
        $class = get_class($this);

        $image = imagecreatetruecolor($this->_width, $this->_height);
        imagecopy($image, $this->_image, 0, 0, 0, 0, $this->_width, $this->_height);

        return new $class($image);
    }

    public function bdPhotos_dropFramesLeavingThree()
    {
        // gd doesn't support frames
        return false;
    }

    public function bdPhotos_removeBorder()
    {
        $this->_bdPhotos_fixOrientation();

        static $delta = 5;
        static $maxBorderThreshold = 0.2;

        if ($this->_width < $delta / $maxBorderThreshold
            || $this->_height < $delta / $maxBorderThreshold
        ) {
            // image is too small, do not process
            return false;
        }

        $rgbAtZeroZero = imagecolorat($this->_image, 0, 0);
        $diffCount = 0;
        if ($rgbAtZeroZero !== imagecolorat($this->_image, $this->_width - 1, 0)) {
            $diffCount++;
        }
        if ($rgbAtZeroZero !== imagecolorat($this->_image, 0, $this->_height - 1)) {
            $diffCount++;
        }
        if ($rgbAtZeroZero !== imagecolorat($this->_image, $this->_width - 1, $this->_height - 1)) {
            $diffCount++;
        }
        if ($diffCount > 1) {
            // 3 corners should have the same color to continue
            return false;
        }

        $halfWidth = floor($this->_width / 2);
        $thresholdWidth = floor($this->_width * $maxBorderThreshold);
        $halfHeight = floor($this->_height / 2);
        $thresholdHeight = floor($this->_height * $maxBorderThreshold);

        $topThickness = $this->_bdPhotos_removeBorder_getThickness(
            0,
            $thresholdHeight,
            $halfWidth - $delta,
            $halfWidth + $delta,
            true
        );
        $leftThickness = $this->_bdPhotos_removeBorder_getThickness(
            0,
            $thresholdWidth,
            $halfHeight - $delta,
            $halfHeight + $delta,
            false
        );
        $bottomThickness = $this->_bdPhotos_removeBorder_getThickness(
            $this->_height - 1,
            $this->_height - $thresholdHeight,
            $halfWidth - $delta,
            $halfWidth + $delta,
            true
        );
        $rightThickness = $this->_bdPhotos_removeBorder_getThickness(
            $this->_width - 1,
            $this->_width - $thresholdWidth,
            $halfHeight - $delta,
            $halfHeight + $delta,
            false
        );

        if ($topThickness === 0
            && $leftThickness === 0
            && $bottomThickness === 0
            && $rightThickness === 0
        ) {
            return false;
        }

        $this->crop(
            $leftThickness,
            $topThickness,
            $this->_width - $leftThickness - $rightThickness,
            $this->_height - $topThickness - $bottomThickness
        );

        return true;
    }

    protected function _bdPhotos_removeBorder_getThickness($i0, $i1, $j0, $j1, $horizontalThickness)
    {
        $iDelta = $i1 > $i0 ? 1 : -1;
        $jDelta = $j1 > $j0 ? 1 : -1;

        for ($i = $i0; $i != $i1; $i += $iDelta) {
            $firstRgb = null;
            for ($j = $j0; $j != $j1; $j += $jDelta) {
                if ($horizontalThickness) {
                    $rgb = imagecolorat($this->_image, $j, $i);
                } else {
                    $rgb = imagecolorat($this->_image, $i, $j);
                }

                if ($firstRgb === null) {
                    $firstRgb = $rgb;
                } elseif ($firstRgb !== $rgb) {
                    return ceil(abs($i - $i0) * 1.1);
                }
            }
        }

        return ceil(abs($i - $i0) * 1.3);
    }

    public function bdPhotos_getEntropy()
    {
        $this->_bdPhotos_fixOrientation();

        $histSize = 0;
        $histogram = array();

        for ($i = 0; $i < $this->_height; $i++) {
            for ($j = 0; $j < $this->_width; $j++) {
                $rgb = imagecolorat($this->_image, $j, $i);
                $grayscale = intval(floor(min(255, max(0, (($rgb >> 16) & 0xFF) * 0.2989 + (($rgb >> 8) & 0xFF) * 0.5870 + ($rgb & 0xFF) * 0.1140))));
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
        // gd always strips!'
        return false;
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
        if (!empty($this->_bdPhotos_manualOrientation)) {
            switch ($this->_bdPhotos_manualOrientation) {
                case bdPhotos_Helper_Metadata::ORIENTATION_UP_SIDE_DOWN:
                    $rotated = imagerotate($this->_image, 180, 0);
                    break;
                case bdPhotos_Helper_Metadata::ORIENTATION_LEFT:
                    $rotated = imagerotate($this->_image, -90, 0);
                    break;
                case bdPhotos_Helper_Metadata::ORIENTATION_RIGHT:
                    $rotated = imagerotate($this->_image, 90, 0);
                    break;
            }

            if (!empty($rotated)) {
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

        parent::thumbnailFixedShorterSide($shortSideWidth);
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
