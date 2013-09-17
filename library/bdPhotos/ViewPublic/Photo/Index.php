<?php

class bdPhotos_ViewPublic_Photo_Index extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this->_params['photos']);
	}
}
