<?php

class bdPhotos_ViewPublic_Location_Photos extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        bdPhotos_ViewPublic_Helper_Photo::preparePhotosForDisplay($this, $this->_params['photos']);
    }

}
