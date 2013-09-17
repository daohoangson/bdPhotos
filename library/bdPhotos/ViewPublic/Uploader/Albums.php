<?php

class bdPhotos_ViewPublic_Uploader_Albums extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::prepareAlbumsForDisplay($this->_params['albums']);
	}
}
