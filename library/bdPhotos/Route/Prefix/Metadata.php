<?php

class bdPhotos_Route_Prefix_Metadata implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('bdPhotos_ControllerPublic_Metadata', $routePath, bdPhotos_Option::get('navTabId'));
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        switch ($action) {
            case 'geo/reverse-decoding':
                $extraParams['hash'] = self::getHashForGeoReverseDecoding($data['lat'], $data['lng']);
                break;
            case 'device/lookup':
                $extraParams['hash'] = self::getHashForDeviceLookup($data['manufacture'], $data['code']);
                break;
        }

        return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
    }

    public static function getHashForGeoReverseDecoding($lat, $lng)
    {
        return md5(intval($lat) . intval($lng) . XenForo_Application::getConfig()->get('globalSalt'));
    }

    public static function getHashForDeviceLookup($manufacture, $code)
    {
        return md5($manufacture . $code . XenForo_Application::getConfig()->get('globalSalt'));
    }

}
