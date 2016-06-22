<?php

class bdPhotos_ViewPublic_Helper_Photo
{
    const SIZE_ORIGINAL = -1;
    const SIZE_PRESET_THUMBNAIL = 'thumbnail';
    const SIZE_PRESET_EDITOR = 'editor';
    const SIZE_PRESET_VIEW = 'view';

    const GENERATED_2X_PER_REQUEST = 5;

    public static $defaultTemplate = 'bdphotos_common_photo';

    protected static $_cachedData = array();
    protected static $_generated2xCount = 0;

    protected $_view;
    protected $_photoId;
    protected $_data;
    protected $_template;
    protected $_outputAttributes;
    protected $_size = null;
    protected $_sizePreset = self::SIZE_PRESET_THUMBNAIL;
    protected $_options = array();

    protected $_str = false;

    public function __construct(XenForo_View $view, $photoId, $options)
    {
        self::$_cachedData[$photoId] = false;
        $this->_view = $view;
        $this->_photoId = $photoId;
        $this->_template = self::$defaultTemplate;

        if (isset($options['data'])) {
            $this->setData($options['data']);
            unset($options['data']);
        }

        if (isset($options['template'])) {
            $this->setTemplate($options['template']);
            unset($options['template']);
        } else {
            $this->setTemplate(self::$defaultTemplate);
        }

        if (isset($options['outputAttributes'])) {
            $this->setOutputAttributes($options['outputAttributes']);
            unset($options['outputAttributes']);
        }

        if (isset($options['size'])) {
            $this->setSize($options['size']);
            unset($options['size']);
        } elseif (isset($options['size_preset'])) {
            $this->setSizePreset($options['size_preset']);
            unset($options['size_preset']);
        }

        $this->_options = $options;
    }

    public function render()
    {
        if (empty($this->_data)) {
            $this->_fetchData();
        }

        if (!empty($this->_template)) {
            list($url, $width, $height, $url2x) = $this->_prepareImage();

            if (!empty($url)) {
                $templateObj = $this->_view->createTemplateObject($this->_template, array(
                    'url' => $url,
                    'width' => $width,
                    'height' => $height,
                    'url2x' => $url2x,
                    'attributes' => $this->_outputAttributes,
                ));

                return $templateObj->render();
            }
        }

        return '';
    }

    public function setData(array $data)
    {
        $this->_data = $data;

        if (!empty($data['attachment_id']) AND !empty($data['thumbnailUrl'])) {
            self::$_cachedData[$data['attachment_id']] = $data;
        }
    }

    public function setTemplate($template)
    {
        $this->_template = $template;
        $this->_view->preLoadTemplate($template);
    }

    public function setOutputAttributes($outputAttributes)
    {
        $this->_outputAttributes = $outputAttributes;
    }

    public function setSize($size)
    {
        $this->_size = $size;
    }

    public function setSizePreset($sizePreset)
    {
        $this->_sizePreset = $sizePreset;
    }

    public function __toString()
    {
        if ($this->_str === false) {
            $this->_str = '';

            try {
                $this->_str = $this->render();
            } catch (Exception $e) {
                XenForo_Error::logException($e, false);
            }
        }

        return $this->_str;
    }

    protected function _prepareImage()
    {
        $url = false;
        $url2x = false;
        $width = 0;
        $height = 0;

        $metadataArray = array();
        if (!empty($this->_data['metadataArray'])) {
            $metadataArray = $this->_data['metadataArray'];
        }

        if ($this->_size === self::SIZE_ORIGINAL) {
            $url = XenForo_Link::buildPublicLink('full:attachments', $this->_data);
            $width = $this->_data['width'];
            $height = $this->_data['height'];
        } elseif ($this->_size === null) {
            switch ($this->_sizePreset) {
                case self::SIZE_PRESET_THUMBNAIL:
                    $this->_size = bdPhotos_Helper_Image::calculateSizeForCrop(
                        $this->_data['width'], $this->_data['height'],
                        XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth'),
                        XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight')
                    );

                    $this->_options += array(
                        bdPhotos_Helper_Image::OPTION_DROP_FRAMES => true,
                        bdPhotos_Helper_Image::OPTION_REMOVE_BORDER =>
                            !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailRemoveBorder')
                    );

                    if (!empty($metadataArray[bdPhotos_Helper_Image::OPTION_ROI])) {
                        $this->_options += array(
                            bdPhotos_Helper_Image::OPTION_ROI =>
                                $metadataArray[bdPhotos_Helper_Image::OPTION_ROI]
                        );
                    }
                    break;
                case self::SIZE_PRESET_VIEW:
                    $this->_size = bdPhotos_Helper_Image::calculateSizeForBoxed(
                        $this->_data['width'], $this->_data['height'],
                        XenForo_Template_Helper_Core::styleProperty('bdPhotos_viewWidth'),
                        XenForo_Template_Helper_Core::styleProperty('bdPhotos_viewHeight')
                    );
                    break;
                case self::SIZE_PRESET_EDITOR:
                    $this->_size = bdPhotos_Helper_Image::calculateSizeForFixedShorterSize(
                        $this->_data['width'],
                        $this->_data['height'],
                        max(XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth'),
                            XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight'))
                    );

                    $this->_options += array(
                        bdPhotos_Helper_Image::OPTION_DROP_FRAMES => true,
                        bdPhotos_Helper_Image::OPTION_REMOVE_BORDER =>
                            !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailRemoveBorder')
                    );
                    break;
            }

        }

        if (!empty($url)) {
            // nothing to do
        } elseif ($this->_size === null) {
            if (!empty($this->_data['thumbnailUrl'])) {
                $url = XenForo_Link::convertUriToAbsoluteUri($this->_data['thumbnailUrl'], true);
                $width = $this->_data['thumbnail_width'];
                $height = $this->_data['thumbnail_height'];
            }
        } elseif (!empty($this->_data['filename'])) {
            $filePath = bdPhotos_Helper_Attachment::getUsableFilePath(
                $this->_getAttachmentModel(), $this->_data, $metadataArray);
            if (file_exists($filePath)) {
                $extension = XenForo_Helper_File::getFileExtension($this->_data['filename']);
                $cachePath = self::_getCachePath($filePath, $extension, $this->_size, $this->_options);
                $url = self::_getCacheUrl($filePath, $extension, $this->_size, $this->_options);

                if ($this->_template === self::$defaultTemplate
                    && !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_view2x')
                    && self::$_generated2xCount < self::GENERATED_2X_PER_REQUEST
                ) {
                    $this->_options += array(bdPhotos_Helper_Image::OPTION_GENERATE_2X => true);
                }

                list($width, $height) = $this->_size;
                $result = bdPhotos_Helper_Image::prepareImage($filePath,
                    $extension, $width, $height, $cachePath, $this->_options);

                if (!($result & bdPhotos_Helper_Image::RESULT_THUMBNAIL_READY)) {
                    $url = false;
                }
                if ($result & bdPhotos_Helper_Image::RESULT_2X_READY) {
                    $url2x = bdPhotos_Helper_Image::getPath2x($url);
                }
                if ($result & bdPhotos_Helper_Image::RESULT_GENERATED_2X) {
                    self::$_generated2xCount++;
                }
            }
        }

        if (!empty($url)) {
            $url = XenForo_Link::convertUriToAbsoluteUri($url, true);
        }

        if (!empty($url2x)) {
            $url2x = XenForo_Link::convertUriToAbsoluteUri($url2x, true);
        }

        return array(
            $url,
            $width,
            $height,
            $url2x,
        );
    }

    protected function _fetchData()
    {
        if (empty($this->_data)) {
            if (empty(self::$_cachedData[$this->_photoId])) {
                $photoIds = array();
                foreach (array_keys(self::$_cachedData) as $photoId) {
                    if (empty(self::$_cachedData[$photoId])) {
                        $photoIds[] = $photoId;
                    }
                }

                if (!empty($photoIds)) {
                    $photos = $this->_getPhotoModel()->getPhotos(array('photo_id' => $photoIds),
                        array('join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT + bdPhotos_Model_Photo::FETCH_ALBUM));
                    foreach ($photos as &$photo) {
                        $photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
                        self::$_cachedData[$photo['photo_id']] = $photo;
                    }
                }
            }

            if (!empty(self::$_cachedData[$this->_photoId])) {
                $this->setData(self::$_cachedData[$this->_photoId]);
                return true;
            }

            return false;
        } else {
            return true;
        }

    }

    /**
     * @return bdPhotos_Model_Photo
     */
    protected function _getPhotoModel()
    {
        static $photoModel = false;

        if ($photoModel === false) {
            $photoModel = XenForo_Model::create('bdPhotos_Model_Photo');
        }

        return $photoModel;
    }

    /**
     * @return XenForo_Model_Attachment
     */
    protected function _getAttachmentModel()
    {
        return $this->_getPhotoModel()->getModelFromCache('XenForo_Model_Attachment');
    }

    public static function preparePhotoForDisplay(XenForo_View $view, array &$photo, array $options = array())
    {
        if (!empty($photo['photo_id'])) {
            $photoId = $photo['photo_id'];
        } elseif (!empty($photo['attachment_id'])) {
            $photoId = $photo['attachment_id'];
        }

        if (!empty($photoId)) {
            $class = XenForo_Application::resolveDynamicClass(__CLASS__);
            /** @var bdPhotos_ViewPublic_Helper_Photo $photoObj */
            $photoObj = new $class($view, $photoId, $options);

            if (!empty($photo['attachment_id']) AND !empty($photo['thumbnailUrl'])) {
                $photoObj->setData($photo);
            }

            if (!empty($options['objKey'])) {
                $photo[$options['objKey']] = $photoObj;
            } else {
                $photo['photoObj'] = $photoObj;
            }
        }
    }

    public static function preparePhotosForDisplay(XenForo_View $view, array &$photos, array $options = array())
    {
        foreach ($photos as &$photo) {
            self::preparePhotoForDisplay($view, $photo, $options);
        }
    }

    public static function prepareAlbumForDisplay(XenForo_View $view, array &$album, array $options = array())
    {
        if (!empty($album['cover_photo_id'])) {
            $class = XenForo_Application::resolveDynamicClass(__CLASS__);
            $photoObj = new $class($view, $album['cover_photo_id'], $options);

            if (!empty($options['objKey'])) {
                $album[$options['objKey']] = $photoObj;
            } else {
                $album['coverObj'] = $photoObj;
            }
        }
    }

    public static function prepareAlbumsForDisplay(XenForo_View $view, array &$albums, array $options = array())
    {
        foreach ($albums as &$album) {
            self::prepareAlbumForDisplay($view, $album, $options);
        }
    }

    protected static function _tryToConvertUrlToPath($url)
    {
        if (strpos($url, sprintf('%s/bdPhotos', XenForo_Application::$externalDataUrl)) === 0) {
            return substr_replace($url, XenForo_Application::$externalDataPath, 0,
                strlen(XenForo_Application::$externalDataUrl));
        }

        return $url;
    }

    protected static function _getCachePath($filePath, $extension, array $size, array $options = array())
    {
        return sprintf('%s/%s', XenForo_Application::$externalDataPath,
            self::_getCachePartialPath($filePath, $extension, $size, $options));
    }

    protected static function _getCacheUrl($filePath, $extension, array $size, array $options = array())
    {
        return sprintf('%s/%s', XenForo_Application::$externalDataUrl,
            self::_getCachePartialPath($filePath, $extension, $size, $options));
    }

    protected static function _getCachePartialPath($filePath, $extension, array $size, array $options = array())
    {
        ksort($options);
        $optionsAsString = serialize($options);

        $filePathHash = md5($filePath . $optionsAsString);
        $divider = substr(md5($filePathHash), 0, 1);

        if (empty($options)) {
            return sprintf('bdPhotos/%5$s/%1$s_%3$d_%4$d.%2$s',
                $filePathHash, $extension, $size[0], $size[1], $divider);
        } else {
            return sprintf('bdPhotos/%4$s/%1$s_%3$s.%2$s',
                $filePathHash, $extension, md5($optionsAsString), $divider);
        }
    }

}
