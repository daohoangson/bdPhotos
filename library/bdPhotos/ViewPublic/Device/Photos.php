<?php

class bdPhotos_ViewPublic_Device_Photos extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this->_params['photos']);
	}

}