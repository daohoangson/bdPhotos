<?php

class bdPhotos_ViewPublic_Album_Index extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        bdPhotos_ViewPublic_Helper_Photo::prepareAlbumsForDisplay($this, $this->_params['albums']);
    }
}
