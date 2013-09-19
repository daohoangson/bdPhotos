<?php

class bdPhotos_AlertHandler_Album extends XenForo_AlertHandler_Abstract
{
	protected $_albumModel = null;

	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		return $this->_getAlbumModel()->getAlbums(array('album_id' => $contentIds));
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return $this->_getAlbumModel()->canViewAlbum($content, $null, $viewingUser);
	}

	protected function _getDefaultTemplateTitle($contentType, $action)
	{
		return 'bdphotos_' . parent::_getDefaultTemplateTitle('album', $action);
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

}
