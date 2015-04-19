<?php

class bdPhotos_CronEntry_Device
{
    public static function updateFromFlickr()
    {
        $apiKey = bdPhotos_Option::get('flickrApiKey');

        if (!empty($apiKey)) {
            $manufactures = bdPhotos_Helper_FlickrApi::getManufactures($apiKey);

            XenForo_Application::defer('bdPhotos_Deferred_DeviceUpdateFromFlickr', array(
                'manufactures' => $manufactures,
                'fromCron' => true,
            ), 'bdPhotos_DeviceUpdate');
        }
    }

}
