<?php

class bdPhotos_ViewPublic_Device_Find extends XenForo_ViewPublic_Base
{
    public function renderJson()
    {
        $results = array();
        foreach ($this->_params['devices'] AS $device)
        {
            $results[$device['device_name']] = array(
                'username' => htmlspecialchars($device['device_name'])
            );
        }

        return array(
            'results' => $results
        );
    }
}