<?php

abstract class bdPhotos_ControllerPublic_Abstract extends XenForo_ControllerPublic_Abstract
{
	protected function _getAlbumOrError($albumId, array $fetchOptions = array())
	{
		$album = $this->_getAlbumModel()->getAlbumById($albumId, $fetchOptions);

		if (empty($album))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_album_not_found')));
		}

		return $album;
	}

	protected function _getDeviceOrError($deviceId, array $fetchOptions = array())
	{
		$device = $this->_getDeviceModel()->getDeviceById($deviceId, $fetchOptions);

		if (empty($device))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_device_not_found')));
		}

		return $device;
	}

	protected function _getLocationOrError($locationId, array $fetchOptions = array())
	{
		$location = $this->_getLocationModel()->getLocationById($locationId, $fetchOptions);

		if (empty($location))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_location_not_found')));
		}

		return $location;
	}

	protected function _getPhotoOrError($photoId, array $fetchOptions = array())
	{
		$photo = $this->_getPhotoModel()->getPhotoById($photoId, $fetchOptions);

		if (empty($photo))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_photo_not_found')));
		}

		return $photo;
	}

	protected function _getViewParamsForSet($album, $photo)
	{
		$set = $this->_input->filterSingle('set', XenForo_Input::STRING);
		$viewParams = array();
		$photos = array();

		switch ($set)
		{
			default:
				// no set specified, use photos from the same album
				$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']), array('order' => 'position'));
				break;
		}

		if (!empty($photos))
		{
			$prev = false;
			$next = false;
			$found = false;

			if (count($photos) > 1)
			{
				foreach ($photos as $_photo)
				{
					if ($_photo['photo_id'] == $photo['photo_id'])
					{
						$found = true;
						continue;
					}

					if (!$found)
					{
						$prev = $_photo;
					}
					else
					{
						$next = $_photo;
						break;
					}
				}

				if ($found)
				{
					if ($prev === false)
					{
						// the photo is the first one in the set
						// use the last one as $prev
						$keys = array_keys($photos);
						$lastKey = array_pop($keys);
						$prev = $photos[$lastKey];
					}
					elseif ($next === false)
					{
						// the photo is the last one in the set
						// use the first one as $next
						$keys = array_keys($photos);
						$firstKey = array_shift($keys);
						$next = $photos[$firstKey];
					}

					if (!empty($prev) AND !empty($next) AND $prev['photo_id'] == $next['photo_id'])
					{
						// this happens if there are 2 photos in the set
						$prev = false;
					}
				}
			}

			$viewParams['setPhotos'] = $photos;
			$viewParams['setPrev'] = $prev;
			$viewParams['setNext'] = $next;
		}

		return $viewParams;
	}

	protected function _assertCanUpload()
	{
		if (!$this->_getUploaderModel()->canUpload($errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanViewAlbum(array $album)
	{
		if (!$this->_getAlbumModel()->canViewAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanEditAlbum(array $album)
	{
		if (!$this->_getAlbumModel()->canEditAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanDeleteAlbum(array $album)
	{
		if (!$this->_getAlbumModel()->canDeleteAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanLikeAlbum(array $album)
	{
		if (!$this->_getAlbumModel()->canLikeAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanCommentAlbum(array $album)
	{
		if (!$this->_getAlbumModel()->canCommentAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanViewPhoto(array $album, array $photo)
	{
		if (!$this->_getPhotoModel()->canViewPhoto($album, $photo, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanEditPhoto(array $album, array $photo)
	{
		if (!$this->_getPhotoModel()->canEditPhoto($album, $photo, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanDeletePhoto(array $album, array $photo)
	{
		if (!$this->_getPhotoModel()->canDeletePhoto($album, $photo, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanLikePhoto(array $album, array $photo)
	{
		if (!$this->_getPhotoModel()->canLikePhoto($album, $photo, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _assertCanCommentPhoto(array $album, array $photo)
	{
		if (!$this->_getPhotoModel()->canCommentPhoto($album, $photo, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
	}

	protected function _preDispatch($action)
	{
		if (!$this->_getUploaderModel()->canView())
		{
			throw $this->getNoPermissionResponseException();
		}

		return parent::_preDispatch($action);
	}

	/**
	 * @return bdPhotos_Model_Album
	 */
	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Album');
	}

	/**
	 * @return bdPhotos_Model_AlbumComment
	 */
	protected function _getAlbumCommentModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_AlbumComment');
	}

	/**
	 * @return bdPhotos_Model_Device
	 */
	protected function _getDeviceModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Device');
	}

	/**
	 * @return XenForo_Model_Like
	 */
	protected function _getLikeModel()
	{
		return $this->getModelFromCache('XenForo_Model_Like');
	}

	/**
	 * @return bdPhotos_Model_Location
	 */
	protected function _getLocationModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Location');
	}

	/**
	 * @return bdPhotos_Model_Photo
	 */
	protected function _getPhotoModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Photo');
	}

	/**
	 * @return bdPhotos_Model_PhotoComment
	 */
	protected function _getPhotoCommentModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_PhotoComment');
	}

	/**
	 * @return bdPhotos_Model_Uploader
	 */
	protected function _getUploaderModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Uploader');
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

}
