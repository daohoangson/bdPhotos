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
    protected $_width = 0;
    protected $_height = 0;
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
            $this->setSize($options['size'][0], $options['size'][1]);
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

    public function setSize($width, $height)
    {
        $this->_width = $width;
        $this->_height = $height;
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

        if ($this->_width === self::SIZE_ORIGINAL AND $this->_height === self::SIZE_ORIGINAL) {
            $url = XenForo_Link::buildPublicLink('full:attachments', $this->_data);
            $width = $this->_data['width'];
            $height = $this->_data['height'];
        } elseif (!($this->_width > 0) AND !($this->_height > 0)) {
            switch ($this->_sizePreset) {
                case self::SIZE_PRESET_THUMBNAIL:
                    $spThumbnailWidth = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth');
                    $spThumbnailHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight');

                    if (!empty($spThumbnailWidth) AND !empty($spThumbnailHeight)) {
                        $this->_width = array(
                            'width' => $spThumbnailWidth,
                            'height' => $spThumbnailHeight,
                            bdPhotos_Helper_Image::OPTION_CROP => true,
                            bdPhotos_Helper_Image::OPTION_DROP_FRAMES => true,
                            bdPhotos_Helper_Image::OPTION_REMOVE_BORDER =>
                                !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailRemoveBorder'),
                        );
                        $this->_height = 0;

                    }
                    break;
                case self::SIZE_PRESET_VIEW:
                    $spViewWidth = XenForo_Template_Helper_Core::styleProperty('bdPhotos_viewWidth');
                    $spViewHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_viewHeight');

                    if (!empty($spViewWidth) AND !empty($spViewHeight)) {
                        $this->_width = array(
                            bdPhotos_Helper_Image::OPTION_WIDTH => $spViewWidth,
                            bdPhotos_Helper_Image::OPTION_HEIGHT => $spViewHeight,
                        );
                        $this->_height = 0;
                    }
                    break;
                case self::SIZE_PRESET_EDITOR:
                    $spThumbnailWidth = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth');
                    $spThumbnailHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight');

                    $this->_width = array(
                        bdPhotos_Helper_Image::OPTION_WIDTH => max($spThumbnailWidth, $spThumbnailHeight),
                        bdPhotos_Helper_Image::OPTION_HEIGHT => max($spThumbnailWidth, $spThumbnailHeight),
                        bdPhotos_Helper_Image::OPTION_THUMBNAIL_FIXED_SHORTER_SIDE => true,
                        bdPhotos_Helper_Image::OPTION_DROP_FRAMES => true,
                        bdPhotos_Helper_Image::OPTION_REMOVE_BORDER =>
                            !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailRemoveBorder'),
                    );
                    $this->_height = 0;
                    break;
            }

        }

        if (!empty($url)) {
            // nothing to do
        } elseif (empty($this->_width) AND empty($this->_height)) {
            if (!empty($this->_data['thumbnailUrl'])) {
                $url = XenForo_Link::convertUriToAbsoluteUri($this->_data['thumbnailUrl'], true);
                $width = $this->_data['thumbnail_width'];
                $height = $this->_data['thumbnail_height'];
            }
        } else {
            if (!empty($this->_data['metadataArray'])) {
                $metadata = $this->_data['metadataArray'];
            } else {
                $metadata = array();
            }

            $filePath = bdPhotos_Helper_Attachment::getUsableFilePath($this->_getAttachmentModel(), $this->_data, $metadata);
            if (file_exists($filePath) AND !empty($this->_data['filename'])) {
                $extension = XenForo_Helper_File::getFileExtension($this->_data['filename']);

                $options = array();
                if (!empty($metadata[bdPhotos_Helper_Image::OPTION_ROI])) {
                    $options[bdPhotos_Helper_Image::OPTION_ROI] = $metadata[bdPhotos_Helper_Image::OPTION_ROI];
                }

                $cachePath = self::_getCachePath($filePath, $extension, $this->_width, $this->_height, $options);
                $url = self::_getCacheUrl($filePath, $extension, $this->_width, $this->_height, $options);

                if (!isset($options[bdPhotos_Helper_Image::OPTION_GENERATE_2X])
                    && $this->_template === self::$defaultTemplate
                    && !!XenForo_Template_Helper_Core::styleProperty('bdPhotos_view2x')
                    && self::$_generated2xCount < self::GENERATED_2X_PER_REQUEST
                ) {
                    $options[bdPhotos_Helper_Image::OPTION_GENERATE_2X] = true;
                }

                $result = bdPhotos_Helper_Image::resizeAndCrop($filePath, $extension, $this->_width, $this->_height,
                    $cachePath, $options);

                if ($result & bdPhotos_Helper_Image::RESULT_THUMBNAIL_READY) {
                    $width = $this->_width;
                    $height = $this->_height;
                } else {
                    $url = false;
                }

                if ($result & bdPhotos_Helper_Image::RESULT_2X_READY) {
                    $url2x = bdPhotos_Helper_Image::getPath2x($url);

                    if ($result & bdPhotos_Helper_Image::RESULT_GENERATED_2X) {
                        self::$_generated2xCount++;
                    }
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
                    $photos = $this->_getPhotoModel()->getPhotos(array('photo_id' => $photoIds), array('join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT + bdPhotos_Model_Photo::FETCH_ALBUM));
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
            return substr_replace($url, XenForo_Application::$externalDataPath, 0, strlen(XenForo_Application::$externalDataUrl));
        }

        return $url;
    }

    protected static function _getCachePath($filePath, $extension, $width, $height, array $options = array())
    {
        return sprintf('%s/%s', XenForo_Application::$externalDataPath, self::_getCachePartialPath($filePath, $extension, $width, $height, $options));
    }

    protected static function _getCacheUrl($filePath, $extension, $width, $height, array $options = array())
    {
        return sprintf('%s/%s', XenForo_Application::$externalDataUrl, self::_getCachePartialPath($filePath, $extension, $width, $height, $options));
    }

    protected static function _getCachePartialPath($filePath, $extension, $width, $height, array $options = array())
    {
        $filePathHash = md5($filePath . serialize($options));
        $divider = substr(md5($filePathHash), 0, 1);

        if (is_numeric($width) AND is_numeric($height)) {
            return sprintf('bdPhotos/%5$s/%1$s_%3$d_%4$d.%2$s', $filePathHash, $extension, $width, $height, $divider);
        } else {
            return sprintf('bdPhotos/%4$s/%1$s_%3$s.%2$s', $filePathHash, $extension, md5(serialize(array(
                $width,
                $height
            ))), $divider);
        }
    }

}
