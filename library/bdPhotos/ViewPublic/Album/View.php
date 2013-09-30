<?php

class bdPhotos_ViewPublic_Album_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::prepareAlbumForDisplay($this->_params['album'], array(
			'objKey' => 'ogObj',
			'template' => '%1$s',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this->_params['photos'], array(
			'objKey' => 'ogObj',
			'template' => '%1$s',
		));

		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this->_params['photos']);
	}

}
