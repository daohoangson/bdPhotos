<?php

class bdPhotos_Helper_Image
{
    const OPTION_MANUAL_ORIENTATION = 'manualOrientation';

    const OPTION_INPUT_TYPE = 'inputType';
    const OPTION_DROP_FRAMES = 'dropFrames';
    const OPTION_REMOVE_BORDER = 'removeBorder';
    const OPTION_ROI = 'roi';
    const OPTION_GENERATE_2X = 'generate2x';

    const RESULT_THUMBNAIL_READY = 0x01;
    const RESULT_GENERATED_THUMBNAIL = 0x02;
    const RESULT_2X_READY = 0x04;
    const RESULT_GENERATED_2X = 0x08;

    public static function detectROI($path, $extension, array $options = array())
    {
        // ideas from
        // https://github.com/reddit/reddit/blob/a6a4da72a1a0f44e0174b2ad0a865b9f68d3c1cd/r2/r2/lib/scraper.py#L57-84
        $inputType = self::getInputTypeFromExtension($extension);
        if (empty($inputType)) {
            return false;
        }

        /** @var bdPhotos_XenForo_Image_Gd $image */
        $image = XenForo_Image_Abstract::createFromFile($path, $inputType);
        if (empty($image)) {
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

        while ($_width != $_height) {
            if ($isTall) {
                $sliceHeight = min($_height - $_width, 10);
                $sliceWidth = 0;
            } else {
                $sliceWidth = min($_width - $_height, 10);
                $sliceHeight = 0;
            }

            $entropy1 = $image->bdPhotos_getEntropy($_x, $_y, $_width - $sliceWidth, $_height - $sliceHeight);
            $entropy2 = $image->bdPhotos_getEntropy($_x + $sliceWidth, $_y + $sliceHeight, $_width - $sliceWidth, $_height - $sliceHeight);

            if ($entropy1 > $entropy2) {
                // take 1st
            } else {
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

        switch ($extension) {
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

    public static function prepareOptionsFromExifData(array &$options, array $exifData)
    {
        $exifOrientation = bdPhotos_Helper_Metadata::extractOrientationFromExifData($exifData);

        if (!empty($exifOrientation)) {
            $options[self::OPTION_MANUAL_ORIENTATION] = $exifOrientation;
        }
    }

    public static function getPath2x($path)
    {
        return substr($path, 0, strrpos($path, '.')) . '@2x' . substr($path, strrpos($path, '.'));
    }

    public static function calculateSizeForFixedShorterSize($width, $height, $shortSideLength)
    {
        if ($height == 0) {
            throw new XenForo_Exception('Invalid height.');
        }

        if ($shortSideLength < 10) {
            $shortSideLength = 10;
        }

        $ratio = $width / $height;
        if ($ratio > 1) {
            // landscape
            $width = $shortSideLength * $ratio;
            $height = $shortSideLength;
        } else {
            $width = $shortSideLength;
            $height = max(1, $shortSideLength / $ratio);
        }

        return array(intval($width), intval($height));
    }

    public static function calculateSizeForBoxed($width, $height, $boxWidth, $boxHeight)
    {
        if ($height == 0 || $boxHeight == 0) {
            throw new XenForo_Exception('Invalid height.');
        }

        $ratio = $width / $height;
        $boxRatio = $boxWidth / $boxHeight;

        if ($ratio > $boxRatio) {
            // too wide
            $width = min($width, $boxWidth);
            $height = max(1, $width / $ratio);
        } else {
            $height = min($height, $boxHeight);
            $width = max(1, $height * $ratio);
        }

        return array(intval($width), intval($height));
    }

    public static function calculateSizeForCrop($width, $height, $cropWidth, $cropHeight)
    {
        $width = min($width, $cropWidth);
        $height = min($height, $cropHeight);

        return array(intval($width), intval($height));
    }

    public static function prepareImage($inPath, $extension, &$width, &$height, $outPath, array $options = array())
    {
        $result = 0;

        $inputType = self::getInputTypeFromExtension($extension);
        if (isset($options[self::OPTION_INPUT_TYPE])) {
            $inputType = $options[self::OPTION_INPUT_TYPE];
        }
        if (empty($inputType)) {
            return $result;
        }

        $generateThumbnail = true;
        if (file_exists($outPath)) {
            $generateThumbnail = false;
            $result |= self::RESULT_THUMBNAIL_READY;
        }

        $width2x = $width * 2;
        $height2x = $height * 2;
        $outPath2x = self::getPath2x($outPath);
        $generate2x = false;
        if ($result & self::RESULT_THUMBNAIL_READY
            && file_exists($outPath2x)
        ) {
            $result |= self::RESULT_2X_READY;
        }
        if (!empty($options[self::OPTION_GENERATE_2X])
            && !($result & self::RESULT_2X_READY)
        ) {
            $generate2x = true;
        }

        if (!$generateThumbnail && !$generate2x) {
            return $result;
        }

        /** @var bdPhotos_XenForo_Image_Gd $image */
        $image = XenForo_Image_Abstract::createFromFile($inPath, $inputType);
        if (empty($image)) {
            return $result;
        }
        $imageHasBeenChanged = false;

        if ($generate2x) {
            if ($width2x > $image->getWidth()
                || $height2x > $image->getWidth()
            ) {
                $generate2x = false;
            }
        }

        // try to request long time limit
        @set_time_limit(60);

        self::_configureImageFromOptions($image, $options);

        if (!empty($options[self::OPTION_DROP_FRAMES])) {
            if ($image->bdPhotos_dropFramesLeavingThree()) {
                $imageHasBeenChanged = true;
            }
        }

        if (!empty($options[self::OPTION_REMOVE_BORDER])) {
            if (self::removeBorder($image)) {
                $imageHasBeenChanged = true;
            }
        }

        if ($generate2x) {
            $imageHasBeenChanged = self::cropImage($image, $width2x, $height2x, $options)
                || $imageHasBeenChanged;
            self::renameOrCopyImage($image, $inPath, $inputType,
                $outPath2x, $generate2x, $imageHasBeenChanged);
            $result |= self::RESULT_2X_READY;
            $result |= self::RESULT_GENERATED_2X;
        }

        if ($generateThumbnail) {
            $imageHasBeenChanged = self::cropImage($image, $width, $height, $options)
                || $imageHasBeenChanged;
            self::renameOrCopyImage($image, $inPath, $inputType,
                $outPath, $generateThumbnail, $imageHasBeenChanged);
            $result |= self::RESULT_THUMBNAIL_READY;
            $result |= self::RESULT_GENERATED_THUMBNAIL;
        }

        return $result;
    }

    public static function cropImage(&$image, $width, $height, array $options = array())
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        $result = false;

        $origRatio = round($image->getWidth() / $image->getHeight(), 1);
        $cropRatio = round($width / $height, 1);

        // crop mode
        if ($origRatio != $cropRatio
            && !empty($options[self::OPTION_ROI])
        ) {
            // smart cropping using ROI information
            $roiX = floor($image->getWidth() * $options[self::OPTION_ROI][0]);
            $roiY = floor($image->getHeight() * $options[self::OPTION_ROI][1]);

            if ($origRatio > $cropRatio) {
                $cropHeight = $image->getHeight();
                $cropWidth = floor($cropHeight * $cropRatio);
            } else {
                $cropWidth = $image->getWidth();
                $cropHeight = floor($cropWidth / $cropRatio);
            }

            $cropX = min(max(0, floor($roiX - $cropWidth / 2)), $image->getWidth() - $cropWidth);
            $cropY = min(max(0, floor($roiY - $cropHeight / 2)), $image->getHeight() - $cropHeight);

            if ($cropX != 0
                || $cropY != 0
                || $cropWidth != $image->getWidth()
                || $cropHeight != $image->getHeight()
            ) {
                $image->crop($cropX, $cropY, $cropWidth, $cropHeight);
                $result = true;
            }

            if ($width != $image->getWidth()
                || $height != $image->getHeight()
            ) {
                $image->bdPhotos_thumbnail($width, $height);
                $result = true;
            }
        } else {
            if ($origRatio > $cropRatio) {
                $thumbHeight = $height;
                $thumbWidth = $height * $origRatio;
            } else {
                $thumbWidth = $width;
                $thumbHeight = $width / $origRatio;
            }

            if ($thumbWidth != $image->getWidth()
                || $thumbHeight != $image->getHeight()
            ) {
                $image->bdPhotos_thumbnail($thumbWidth, $thumbHeight);
                $result = true;
            }

            if ($width != $image->getWidth() OR $height != $image->getHeight()) {
                $image->crop(0, 0, $width, $height);
                $result = true;
            }
        }

        return $result;
    }

    public static function renameOrCopyImage(&$image, $inPath, $inputType, $outPath, $generate, $changed)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        XenForo_Helper_File::createDirectory(dirname($outPath), true);

        if ($changed) {
            $tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
            $image->output($inputType, $tempFile);

            XenForo_Helper_File::safeRename($tempFile, $outPath);
        } elseif ($generate) {
            @copy($inPath, $outPath);
        }
    }

    public static function stripJpeg($path, array $options = array())
    {
        $tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');

        /** @var bdPhotos_XenForo_Image_Gd $image */
        $image = XenForo_Image_Abstract::createFromFile($path, IMAGETYPE_JPEG);
        if (empty($image)) {
            return false;
        }

        self::_configureImageFromOptions($image, $options);

        $image->crop(0, 0, $image->getWidth(), $image->getHeight());

        $image->bdPhotos_strip();

        if (!$image->output(IMAGETYPE_JPEG, $tempFile)) {
            return false;
        }

        return $tempFile;
    }

    public static function removeBorder(&$image)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        $image->bdPhotos_fixOrientation();

        static $delta = 5;
        static $maxBorderThreshold = 0.2;

        if ($image->getWidth() < $delta / $maxBorderThreshold
            || $image->getHeight() < $delta / $maxBorderThreshold
        ) {
            // image is too small, do not process
            return false;
        }

        $pixelAtZeroZero = $image->bdPhotos_getPixelValue(0, 0);
        $diffCount = 0;
        if ($pixelAtZeroZero !== $image->bdPhotos_getPixelValue($image->getWidth() - 1, 0)) {
            $diffCount++;
        }
        if ($pixelAtZeroZero !== $image->bdPhotos_getPixelValue(0, $image->getHeight() - 1)) {
            $diffCount++;
        }
        if ($pixelAtZeroZero !== $image->bdPhotos_getPixelValue($image->getWidth() - 1, $image->getHeight() - 1)) {
            $diffCount++;
        }
        if ($diffCount > 1) {
            // 3 corners should have the same color to continue
            return false;
        }

        $halfWidth = floor($image->getWidth() / 2);
        $thresholdWidth = floor($image->getWidth() * $maxBorderThreshold);
        $halfHeight = floor($image->getHeight() / 2);
        $thresholdHeight = floor($image->getHeight() * $maxBorderThreshold);

        $topThickness = self::_removeBorder_getThickness(
            $image,
            0,
            $thresholdHeight,
            $halfWidth - $delta,
            $halfWidth + $delta,
            true
        );
        $leftThickness = self::_removeBorder_getThickness(
            $image,
            0,
            $thresholdWidth,
            $halfHeight - $delta,
            $halfHeight + $delta,
            false
        );
        $bottomThickness = self::_removeBorder_getThickness(
            $image,
            $image->getHeight() - 1,
            $image->getHeight() - $thresholdHeight,
            $halfWidth - $delta,
            $halfWidth + $delta,
            true
        );
        $rightThickness = self::_removeBorder_getThickness(
            $image,
            $image->getWidth() - 1,
            $image->getWidth() - $thresholdWidth,
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

        $image->crop(
            $leftThickness,
            $topThickness,
            $image->getWidth() - $leftThickness - $rightThickness,
            $image->getHeight() - $topThickness - $bottomThickness
        );

        return true;
    }

    protected static function _removeBorder_getThickness(&$image, $i0, $i1, $j0, $j1, $horizontalThickness)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        $iDelta = $i1 > $i0 ? 1 : -1;
        $jDelta = $j1 > $j0 ? 1 : -1;

        for ($i = $i0; $i != $i1; $i += $iDelta) {
            $firstRgb = null;
            for ($j = $j0; $j != $j1; $j += $jDelta) {
                if ($horizontalThickness) {
                    $rgb = $image->bdPhotos_getPixelValue($j, $i);
                } else {
                    $rgb = $image->bdPhotos_getPixelValue($i, $j);
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

    protected static function _configureImageFromOptions(XenForo_Image_Abstract $image, array $options)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        if (!empty($options[self::OPTION_MANUAL_ORIENTATION])) {
            // TODO: check for method availability?
            $image->bdPhotos_setManualOrientation($options[self::OPTION_MANUAL_ORIENTATION]);
        }
    }

}
