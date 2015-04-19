<?php

class bdPhotos_ViewAdmin_Device_List extends XenForo_ViewAdmin_Base
{
    public function renderJson()
    {
        if (!empty($this->_params['filterView'])) {
            $this->_templateName = 'bdphotos_device_list_items';
        }

        return null;
    }
}