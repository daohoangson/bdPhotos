<?php

class bdPhotos_ViewPublic_Photo_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$photo = &$this->_params['photo'];
		$uploader = &$this->_params['uploader'];

		if (empty($uploader))
		{
			$uploader = array();
		}

		$photoCaption = array_merge($uploader, array(
			'message' => $photo['photo_caption'],
			'messageHtml' => $photo['photo_caption'],
		));

		$this->_params['photoCaption'] = $photoCaption;

		bdPhotos_ViewPublic_Helper_Photo::preparePhotoForDisplay($this->_params['photo'], array(
			'objKey' => 'ogObj',
			'template' => '%1$s',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotoForDisplay($this->_params['photo'], array('size_preset' => 'view'));
	}

}
