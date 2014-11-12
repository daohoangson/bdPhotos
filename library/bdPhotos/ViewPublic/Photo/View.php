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

		bdPhotos_ViewPublic_Helper_Photo::preparePhotoForDisplay($this, $this->_params['photo'], array(
			'objKey' => 'ogObj',
			'template' => 'bdphotos_common_photo_url',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotoForDisplay($this, $this->_params['photo'], array('size_preset' => 'view'));
	}

	public function renderJson()
	{
		$output = $this->_renderer->getDefaultOutputArray(__CLASS__, $this->_params, $this->_templateName);

		$output['canonicalUrl'] = $this->_params['canonicalUrl'];

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}

}
