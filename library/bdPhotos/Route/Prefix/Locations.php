<?php

class bdPhotos_Route_Prefix_Locations implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'location_id');
        $action = $router->resolveActionAsPageNumber($action, $request);

        return $router->getRouteMatch('bdPhotos_ControllerPublic_Location', $action, bdPhotos_Option::get('navTabId'));
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

        if (isset($data['location_id'])) {
            $data['locationNameFormatted'] = XenForo_Template_Helper_Core::callHelper(
                'bdPhotos_formatLocationName', array($data));
            return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix,
                $action, $extension, $data, 'location_id', 'locationNameFormatted');
        } else {
            return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
        }
    }

}
