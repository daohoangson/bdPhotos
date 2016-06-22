<?php

class bdPhotos_Route_Prefix_Albums implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'album_id');
        $action = $router->resolveActionAsPageNumber($action, $request);

        return $router->getRouteMatch('bdPhotos_ControllerPublic_Album', $action, bdPhotos_Option::get('navTabId'));
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

        if (isset($data['album_id'])) {
            return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix,
                $action, $extension, $data, 'album_id', 'album_name');
        } else {
            return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
        }
    }

}
