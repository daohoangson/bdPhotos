<?php

class bdPhotos_ViewPublic_Album_Edit extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this, $this->_params['photos'], array('size_preset' => bdPhotos_ViewPublic_Helper_Photo::SIZE_PRESET_EDITOR));
	}

}
