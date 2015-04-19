<?php

class bdPhotos_Helper_FlickrApi
{
    public static $apiUrl = 'http://api.flickr.com/services/rest';

    public static function getManufactures($apiKey)
    {
        $client = XenForo_Helper_Http::getClient(call_user_func_array('sprintf', array(
            '%s/?method=flickr.cameras.getBrands&api_key=%s&format=json&nojsoncallback=1',
            self::$apiUrl,
            $apiKey,
        )));
        $response = $client->request('GET')->getBody();
        $responseArray = json_decode($response, true);

        XenForo_Helper_File::log('bdPhotos_flickrApi', call_user_func_array('sprintf', array(
            'cameras.getBrands -> %s (%d results)',
            (!empty($responseArray['stat']) ? $responseArray['stat'] : 'N/A'),
            (!empty($responseArray['brands']['brand']) ? count($responseArray['brands']['brand']) : 0),
        )));

        $manufactures = array();

        if (!empty($responseArray['brands']['brand'])) {
            foreach ($responseArray['brands']['brand'] as $brand) {
                $manufactures[$brand['id']] = array(
                    'manufacture' => $brand['id'],
                    'manufacture_name' => $brand['name'],
                );
            }
        }

        return $manufactures;
    }

    public static function getDevices($apiKey, $manufacture)
    {
        $client = XenForo_Helper_Http::getClient(call_user_func_array('sprintf', array(
            '%s/?method=flickr.cameras.getBrandModels&api_key=%s&brand=%s&format=json&nojsoncallback=1',
            self::$apiUrl,
            $apiKey,
            $manufacture,
        )));
        $response = $client->request('GET')->getBody();
        $responseArray = json_decode($response, true);

        XenForo_Helper_File::log('bdPhotos_flickrApi', call_user_func_array('sprintf', array(
            'cameras.getBrandModels %s -> %s (%d results)',
            $manufacture,
            (!empty($responseArray['stat']) ? $responseArray['stat'] : 'N/A'),
            (!empty($responseArray['cameras']['camera']) ? count($responseArray['cameras']['camera']) : 0),
        )));

        $devices = array();

        if (!empty($responseArray['cameras']['camera'])) {
            foreach ($responseArray['cameras']['camera'] as $camera) {
                self::_simplifyData($camera);

                list($nManufacture, $nCode) = bdPhotos_Helper_Metadata::normalizeDeviceCode($manufacture, $camera['id']);

                $devices[$nCode] = array(
                    'manufacture' => $nManufacture,
                    'code' => $nCode,
                    'device_name' => $camera['name'],
                    'device_info' => array_merge($camera, array(
                        '_source' => 'api.flickr.com',
                        '_timestamp' => XenForo_Application::$time,
                        '_brandId' => $manufacture,
                        '_cameraId' => $camera['id'],
                        '_nManufacture' => $nManufacture,
                        '_nCode' => $nCode,
                    )),
                );
            }
        }

        return $devices;
    }

    protected static function _simplifyData(array &$data)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                if (count($value) == 1 AND isset($value['_content'])) {
                    $value = $value['_content'];
                } else {
                    self::_simplifyData($value);
                }
            }
        }
    }

}
