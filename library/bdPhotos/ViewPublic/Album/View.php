<?php

class bdPhotos_ViewPublic_Album_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::prepareAlbumForDisplay($this, $this->_params['album'], array(
			'objKey' => 'ogObj',
			'template' => 'bdphotos_common_photo_url',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this, $this->_params['photos'], array(
			'objKey' => 'ogObj',
			'template' => 'bdphotos_common_photo_url',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this, $this->_params['photos']);
	}

}
