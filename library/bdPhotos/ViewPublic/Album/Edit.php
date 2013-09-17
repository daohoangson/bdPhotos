<?php

class bdPhotos_ViewPublic_Album_Edit extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this->_params['photos']);
	}

}
