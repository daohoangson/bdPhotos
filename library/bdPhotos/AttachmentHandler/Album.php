<?php

class bdPhotos_AttachmentHandler_Album extends XenForo_AttachmentHandler_Abstract
{
	protected $_albumModel = null;

	protected $_contentIdKey = 'album_id';

	protected $_contentRoute = 'photos/albums';

	protected $_contentTypePhraseKey = 'bdphotos_album';

	public function getAttachmentConstraints()
	{
		return $this->_getAlbumModel()->getAttachmentConstraints();
	}

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		if (!empty($contentData['album_id']))
		{
			$album = $this->_getAlbumModel()->getAlbumById($contentData['album_id']);

			if (!empty($album))
			{
				return ($this->_getAlbumModel()->canViewAlbum($album, $null, $viewingUser) AND $this->_getAlbumModel()->canEditAlbum($album, $null, $viewingUser));
			}
		}
		else
		{
			return $this->_getAlbumModel()->canEditAlbum(array(), $null, $viewingUser);
		}

		return false;
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$photo = $this->_getPhotoModel()->getPhotoById($attachment['attachment_id']);
		if (empty($photo))
		{
			return false;
		}

		$album = $this->_getAlbumModel()->getAlbumById($photo['album_id']);
		if (empty($album))
		{
			return false;
		}

		return $this->_getPhotoModel()->canDownloadFullPhoto($album, $photo, $null, $viewingUser);
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
		$photoDw->setOption(bdPhotos_DataWriter_Photo::OPTION_DELETE_ATTACHMENT, false);

		$photoDw->setExistingData($attachment['attachment_id']);
		$photoDw->delete();
	}

	/**
	 * @return bdPhotos_Model_Album
	 */
	protected function _getAlbumModel()
	{
		if (!$this->_albumModel)
		{
			$this->_albumModel = XenForo_Model::create('bdPhotos_Model_Album');
		}

		return $this->_albumModel;
	}

	/**
	 * @return bdPhotos_Model_Photo
	 */
	protected function _getPhotoModel()
	{
		return $this->_getAlbumModel()->getModelFromCache('bdPhotos_Model_Photo');
	}

}
