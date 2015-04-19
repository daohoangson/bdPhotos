<?php

class bdPhotos_Helper_Image
{
    const OPTION_MANUAL_ORIENTATION = 'manualOrientation';

    const OPTION_ROI = 'roi';
    const OPTION_GENERATE_2X = 'generate2x';

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

    public static function resizeAndCrop($inPath, $extension, &$width, &$height, $outPath, array $options = array())
    {
        $inputType = self::getInputTypeFromExtension($extension);

        $_generateThumbnail = true;
        $_imageHasBeenChanged = false;

        $outPath2x = '';
        $_generateThumbnail2x = true;
        $_image2xHasBeenChanged = false;

        if (is_array($width) AND empty($height)) {
            // support setting additional data via an array as $width
            $array = $width;
            $width = $array['width'];
            $height = $array['height'];

            if (isset($array['inputType'])) {
                $inputType = $array['inputType'];
            }

            $options = array_merge($options, array(
                'crop' => true,
                'thumbnailFixedShorterSide' => false,
                'dropFramesLeavingThree' => false,
            ), $array);
        }

        if (empty($inputType)) {
            return false;
        }

        if (file_exists($outPath)) {
            $_generateThumbnail = false;
        }

        if (!empty($options[self::OPTION_GENERATE_2X])) {
            $outPath2x = self::getPath2x($outPath);
            if (file_exists($outPath2x)) {
                $_generateThumbnail2x = false;
            }
        } else {
            $_generateThumbnail2x = false;
        }

        if (!$_generateThumbnail && !$_generateThumbnail2x) {
            // nothing to do
            return true;
        }

        /** @var bdPhotos_XenForo_Image_Gd $image */
        $image = XenForo_Image_Abstract::createFromFile($inPath, $inputType);
        if (empty($image)) {
            return false;
        }

        $image2x = null;
        $width2x = $width * 2;
        $height2x = $height * 2;
        if ($outPath2x AND $_generateThumbnail2x) {
            $image2x = $image->bdPhotos_copy();
        }

        self::_configureImageFromOptions($image, $options);
        if (!empty($image2x)) {
            self::_configureImageFromOptions($image2x, $options);
        }

        // try to request longer time limit
        @set_time_limit(60);

        if (!empty($options['dropFramesLeavingThree'])) {
            if ($_generateThumbnail) {
                if ($image->bdPhotos_dropFramesLeavingThree()) {
                    $_imageHasBeenChanged = true;
                }
            }
            if (!empty($image2x)) {
                if ($image2x->bdPhotos_dropFramesLeavingThree()) {
                    $_image2xHasBeenChanged = true;
                }
            }
        }

        $_imageHasBeenChanged = $_imageHasBeenChanged || self::resizeAndCropImage($image, $width, $height, $_generateThumbnail, $options);
        if (!empty($image2x)) {
            $_image2xHasBeenChanged = $_image2xHasBeenChanged || self::resizeAndCropImage($image2x, $width2x, $height2x, $_generateThumbnail2x, $options);
        }

        self::renameOrCopyImage($image, $inPath, $inputType, $outPath, $_generateThumbnail, $_imageHasBeenChanged);
        if (!empty($image2x)) {
            self::renameOrCopyImage($image2x, $inPath, $inputType, $outPath2x, $_generateThumbnail2x, $_image2xHasBeenChanged);
        }

        return true;
    }

    public static function resizeAndCropImage(&$image, &$width, &$height, $generate = true, array $options = array())
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        $generated = false;
        $options = array_merge(array(
            'crop' => false,
            'roi' => false,
            'thumbnailFixedShorterSide' => false,
        ), $options);

        if ($width > 0 AND $height > 0) {
            if (!empty($options['crop'])) {
                $origRatio = round($image->getWidth() / $image->getHeight(), 1);
                $cropRatio = round($width / $height, 1);

                // crop mode
                if ($origRatio != $cropRatio AND !empty($options['roi'])) {
                    // smart cropping using ROI information
                    $roiX = floor($image->getWidth() * $options['roi'][0]);
                    $roiY = floor($image->getHeight() * $options['roi'][1]);

                    if ($origRatio > $cropRatio) {
                        $cropHeight = $image->getHeight();
                        $cropWidth = floor($cropHeight * $cropRatio);
                    } else {
                        $cropWidth = $image->getWidth();
                        $cropHeight = floor($cropWidth / $cropRatio);
                    }

                    $cropX = min(max(0, floor($roiX - $cropWidth / 2)), $image->getWidth() - $cropWidth);
                    $cropY = min(max(0, floor($roiY - $cropHeight / 2)), $image->getHeight() - $cropHeight);

                    if ($cropX != 0 OR $cropY != 0 OR $cropWidth != $image->getWidth() OR $cropHeight != $image->getHeight()) {
                        if ($generate) {
                            $image->crop($cropX, $cropY, $cropWidth, $cropHeight);
                            $generated = true;
                        }
                    }

                    if ($width != $image->getWidth() OR $height != $image->getHeight()) {
                        if ($generate) {
                            $image->bdPhotos_thumbnail($width, $height);
                            $generated = true;
                        }
                    }
                } else {
                    if ($origRatio > $cropRatio) {
                        $thumHeight = $height;
                        $thumWidth = $height * $origRatio;
                    } else {
                        $thumWidth = $width;
                        $thumHeight = $width / $origRatio;
                    }

                    if ($thumWidth != $image->getWidth() OR $thumHeight != $image->getHeight()) {
                        if ($generate) {
                            $image->bdPhotos_thumbnail($thumWidth, $thumHeight);
                            $generated = true;
                        }
                    }

                    if ($width != $image->getWidth() OR $height != $image->getHeight()) {
                        if ($generate) {
                            $image->crop(0, 0, $width, $height);
                            $generated = true;
                        }
                    }
                }
            } else {
                if (!empty($options['thumbnailFixedShorterSide'])) {
                    if ($image->getWidth() > $width OR $image->getHeight() > $height) {
                        if ($width < 10) {
                            $shortSideLength = 10;
                        } else {
                            $shortSideLength = $width;
                        }

                        if ($generate) {
                            $image->thumbnailFixedShorterSide($shortSideLength);
                            $generated = true;
                        }

                        $ratio = $image->getWidth() / $image->getHeight();
                        if ($ratio > 1) {
                            // landscape
                            $width = $shortSideLength * $ratio;
                            $height = $shortSideLength;
                        } else {
                            $width = $shortSideLength;
                            $height = max(1, $shortSideLength / $ratio);
                        }
                    }
                } else {
                    // resize and make sure both width and height don't exceed the configured values
                    $origRatio = $image->getWidth() / $image->getHeight();

                    $thumWidth = $width;
                    $thumHeight = $thumWidth / $origRatio;

                    if ($thumHeight > $height) {
                        $thumHeight = $height;
                        $thumWidth = $thumHeight * $origRatio;
                    }

                    if (($thumWidth != $image->getWidth() OR $thumHeight != $image->getHeight()) AND $generate) {
                        $image->bdPhotos_thumbnail($thumWidth, $thumHeight);
                        $generated = true;
                    }

                    $width = $thumWidth;
                    $height = $thumHeight;
                }
            }
        } elseif ($height > 0) {
            $targetHeight = $height;
            $targetWidth = $targetHeight / $image->getHeight() * $image->getWidth();

            if (($targetWidth != $image->getWidth() OR $targetHeight != $image->getHeight()) AND $generate) {
                $image->bdPhotos_thumbnail($targetWidth, $targetHeight);
                $generated = true;
            }

            $width = $targetWidth;
            $height = $targetHeight;
        } elseif ($width > 0) {
            $targetWidth = $width;
            $targetHeight = $targetWidth / $image->getWidth() * $image->getHeight();

            if (($targetWidth != $image->getWidth() OR $targetHeight != $image->getHeight()) AND $generate) {
                $image->bdPhotos_thumbnail($targetWidth, $targetHeight);
                $generated = true;
            }

            $width = $targetWidth;
            $height = $targetHeight;
        }

        return $generated;
    }

    public static function renameOrCopyImage(&$image, $inPath, $inputType, $outPath, $generate, $changed)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        if ($changed) {
            XenForo_Helper_File::createDirectory(dirname($outPath), true);

            $tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
            $image->output($inputType, $tempFile);

            XenForo_Helper_File::safeRename($tempFile, $outPath);
        } elseif ($generate) {
            XenForo_Helper_File::createDirectory(dirname($outPath), true);

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

    protected static function _configureImageFromOptions(XenForo_Image_Abstract $image, array $options)
    {
        /** @var bdPhotos_XenForo_Image_Gd $image */
        if (!empty($options[self::OPTION_MANUAL_ORIENTATION])) {
            // TODO: check for method availability?
            $image->bdPhotos_setManualOrientation($options[self::OPTION_MANUAL_ORIENTATION]);
        }
    }

}
