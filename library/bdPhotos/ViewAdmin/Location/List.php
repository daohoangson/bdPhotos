<?php

class bdPhotos_ViewAdmin_Location_List extends XenForo_ViewAdmin_Base
{
    public function renderJson()
    {
        if (!empty($this->_params['filterView'])) {
            $this->_templateName = 'bdphotos_location_list_items';
        }

        return null;
    }

}
