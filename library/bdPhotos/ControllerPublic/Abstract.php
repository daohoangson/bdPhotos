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

	protected function _assertCanView()
	{
		if (!$this->_getUploaderModel()->canView($errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
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
	 * @return bdPhotos_ControllerHelper_Set
	 */
	protected function _getSetHelper()
	{
		return $this->getHelper('bdPhotos_ControllerHelper_Set');
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
