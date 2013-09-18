<?php

class bdPhotos_ViewPublic_Helper_Photo
{
	const SIZE_ORIGINAL = -1;
	const SIZE_PRESET_THUMBNAIL = 'thumbnail';
	const SIZE_PRESET_EDITOR = 'editor';
	const SIZE_PRESET_VIEW = 'view';

	public static $defaultTemplate = '<img src="%1$s" %2$s %3$s class="%4$s" %5$s />';

	protected static $_cachedData = array();

	protected $_photoId;
	protected $_data;
	protected $_template;
	protected $_outputAttributes;
	protected $_width = 0;
	protected $_height = 0;
	protected $_sizePreset = self::SIZE_PRESET_THUMBNAIL;

	protected $_str = false;

	public function __construct($photoId, $options)
	{
		self::$_cachedData[$photoId] = false;
		$this->_photoId = $photoId;
		$this->_template = self::$defaultTemplate;

		if (isset($options['data']))
		{
			$this->setData($options['data']);
		}

		if (isset($options['template']))
		{
			$this->setTemplate($options['template']);
		}

		if (isset($options['outputAttributes']))
		{
			$this->setOutputAttributes($options['outputAttributes']);
		}

		if (isset($options['size']))
		{
			$this->setSize($options['size'][0], $options['size'][1]);
		}
		elseif (isset($options['size_preset']))
		{
			$this->setSizePreset($options['size_preset']);
		}
	}

	public function render()
	{
		if (empty($this->_data))
		{
			$this->_fetchData();
		}

		if (!empty($this->_template))
		{
			list($url, $width, $height) = $this->_prepareImage();

			if (!empty($url))
			{
				$params = array();
				$params[] = $this->_template;

				$params[] = $url;

				if ($width > 0)
				{
					$params[] = sprintf('width="%d"', $width);
				}
				else
				{
					$params[] = '';
				}

				if ($height > 0)
				{
					$params[] = sprintf('height="%d"', $height);
				}
				else
				{
					$params[] = '';
				}

				$params[] = 'bdPhotos_ViewPublic_Helper_Photo';

				$params[] = $this->_outputAttributes;

				return call_user_func_array('sprintf', $params);
			}
		}

		return '';
	}

	public function setData(array $data)
	{
		$this->_data = $data;

		if (!empty($data['attachment_id']) AND !empty($data['thumbnailUrl']))
		{
			self::$_cachedData[$data['attachment_id']] = $data;
		}
	}

	public function setTemplate($template)
	{
		$this->_template = $template;
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
		if ($this->_str === false)
		{
			$this->_str = '';

			try
			{
				$this->_str = $this->render();
			}
			catch (Exception $e)
			{
				XenForo_Error::logException($e, false);
			}
		}

		return $this->_str;
	}

	protected function _prepareImage()
	{
		$url = false;
		$width = 0;
		$height = 0;

		if ($this->_width === self::SIZE_ORIGINAL AND $this->_height === self::SIZE_ORIGINAL)
		{
			$url = XenForo_Link::buildPublicLink('full:attachments', $this->_data);
			$width = $this->_data['width'];
			$height = $this->_data['height'];
		}
		elseif (!($this->_width > 0) AND !($this->_height > 0))
		{
			switch ($this->_sizePreset)
			{
				case self::SIZE_PRESET_THUMBNAIL:
					$spThumbnailWidth = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth');
					$spThumbnailHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight');

					if (!empty($spThumbnailWidth) AND !empty($spThumbnailHeight))
					{
						$this->_width = $spThumbnailWidth;
						$this->_height = $spThumbnailHeight;
					}
					break;
				case self::SIZE_PRESET_VIEW:
					$spViewWidthOrHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_viewWidthOrHeight');

					if (!empty($spViewWidthOrHeight))
					{
						$this->_width = array(
							'width' => $spViewWidthOrHeight,
							'height' => $spViewWidthOrHeight,
							'crop' => false
						);
						$this->_height = 0;
					}
					break;
				case self::SIZE_PRESET_EDITOR:
					$spThumbnailWidth = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailWidth');
					$spThumbnailHeight = XenForo_Template_Helper_Core::styleProperty('bdPhotos_thumbnailHeight');

					$this->_width = array(
						'width' => max($spThumbnailWidth, $spThumbnailHeight),
						'height' => max($spThumbnailWidth, $spThumbnailHeight),
						'crop' => false,
						'thumbnailFixedShorterSide' => true,
					);
					$this->_height = 0;
					break;
			}

		}

		if (!empty($url))
		{
			// nothing to do
		}
		elseif (empty($this->_width) AND empty($this->_height))
		{
			if (!empty($this->_data['thumbnailUrl']))
			{
				$url = XenForo_Link::convertUriToAbsoluteUri($this->_data['thumbnailUrl'], true);
				$width = $this->_data['thumbnail_width'];
				$height = $this->_data['thumbnail_height'];
			}
		}
		else
		{
			$filePath = $this->_getAttachmentModel()->getAttachmentDataFilePath($this->_data);
			if (file_exists($filePath) AND !empty($this->_data['filename']))
			{
				$extension = XenForo_Helper_File::getFileExtension($this->_data['filename']);
				$cachePath = self::_getCachePath($filePath, $extension, $this->_width, $this->_height);

				if (!empty($this->_data['metadataArray']))
				{
					$options = $this->_data['metadataArray'];
					if (!empty($this->_data['metadataArray']['exif']))
					{
						bdPhotos_Helper_Image::prepareOptionsFromExifData($options, $this->_data['metadataArray']['exif']);
					}
				}
				else
				{
					$options = array();
				}

				if (file_exists($cachePath) OR bdPhotos_Helper_Image::resizeAndCrop($filePath, $extension, $this->_width, $this->_height, $cachePath, $options))
				{
					$url = self::_getCacheUrl($filePath, $extension, $this->_width, $this->_height);
					$width = $this->_width;
					$height = $this->_height;
				}
			}
		}

		if (!is_numeric($width) OR !is_numeric($height))
		{
			$path = self::_tryToConvertUrlToPath($url);

			list($width, $height) = bdPhotos_Helper_Image::getSize($path, $this->_width, $this->_height);
		}

		return array(
			$url,
			$width,
			$height
		);
	}

	protected function _fetchData()
	{
		if (empty($this->_data))
		{
			if (empty(self::$_cachedData[$this->_photoId]))
			{
				$photoIds = array();
				foreach (array_keys(self::$_cachedData) as $photoId)
				{
					if (empty(self::$_cachedData[$photoId]))
					{
						$photoIds[] = $photoId;
					}
				}

				if (!empty($photoIds))
				{
					$photos = $this->_getPhotoModel()->getPhotos(array('photo_id' => $photoIds), array('join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT + bdPhotos_Model_Photo::FETCH_ALBUM));
					foreach ($photos as &$photo)
					{
						$photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
						self::$_cachedData[$photo['photo_id']] = $photo;
					}
				}
			}

			if (!empty(self::$_cachedData[$this->_photoId]))
			{
				$this->setData(self::$_cachedData[$this->_photoId]);
				return true;
			}

			return false;
		}
		else
		{
			return true;
		}

	}

	/**
	 * @return bdPhotos_Model_Photo
	 */
	protected function _getPhotoModel()
	{
		static $photoModel = false;

		if ($photoModel === false)
		{
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

	public static function preparePhotoForDisplay(array &$photo, array $options = array())
	{
		if (!empty($photo['photo_id']))
		{
			$photoId = $photo['photo_id'];
		}
		elseif (!empty($photo['attachment_id']))
		{
			$photoId = $photo['attachment_id'];
		}

		if (!empty($photoId))
		{
			$class = XenForo_Application::resolveDynamicClass(__CLASS__);
			$photoObj = new $class($photoId, $options);

			if (!empty($photo['attachment_id']) AND !empty($photo['thumbnailUrl']))
			{
				$photoObj->setData($photo);
			}

			if (!empty($options['objKey']))
			{
				$photo[$options['objKey']] = $photoObj;
			}
			else
			{
				$photo['photoObj'] = $photoObj;
			}
		}
	}

	public static function preparePhotosForDisplay(array &$photos, array $options = array())
	{
		foreach ($photos as &$photo)
		{
			self::preparePhotoForDisplay($photo, $options);
		}
	}

	public static function prepareAlbumForDisplay(array &$album, array $options = array())
	{
		$class = XenForo_Application::resolveDynamicClass(__CLASS__);
		$photoObj = new $class($album['cover_photo_id'], $options);

		if (!empty($options['objKey']))
		{
			$album[$options['objKey']] = $photoObj;
		}
		else
		{
			$album['coverObj'] = $photoObj;
		}
	}

	public static function prepareAlbumsForDisplay(array &$albums, array $options = array())
	{
		foreach ($albums as &$album)
		{
			self::prepareAlbumForDisplay($album);
		}
	}

	protected static function _tryToConvertUrlToPath($url)
	{
		if (strpos($url, sprintf('%s/bdPhotos', XenForo_Application::$externalDataUrl)) === 0)
		{
			return substr_replace($url, XenForo_Application::$externalDataPath, 0, strlen(XenForo_Application::$externalDataUrl));
		}

		return $url;
	}

	protected static function _getCachePath($filePath, $extension, $width, $height)
	{
		return sprintf('%s/%s', XenForo_Application::$externalDataPath, self::_getCachePartialPath($filePath, $extension, $width, $height));
	}

	protected static function _getCacheUrl($filePath, $extension, $width, $height)
	{
		return sprintf('%s/%s', XenForo_Application::$externalDataUrl, self::_getCachePartialPath($filePath, $extension, $width, $height));
	}

	protected static function _getCachePartialPath($filePath, $extension, $width, $height)
	{
		$filePathHash = md5($filePath);
		$divider = substr(md5($filePathHash), 0, 1);

		if (is_numeric($width) AND is_numeric($height))
		{
			return sprintf('bdPhotos/%5$s/%1$s_%3$d_%4$d.%2$s', $filePathHash, $extension, $width, $height, $divider);
		}
		else
		{
			return sprintf('bdPhotos/%4$s/%1$s_%3$s.%2$s', $filePathHash, $extension, md5(serialize(array(
				$width,
				$height
			))), $divider);
		}
	}

}
