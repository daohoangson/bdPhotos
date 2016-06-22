<?php

class bdPhotos_Route_Prefix_Photos implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $request->setParam('setString', $request->getParam('s'));
        $request->setParam('setTitle', $request->getParam('st'));

        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'photo_id');
        $action = $router->resolveActionAsPageNumber($action, $request);

        return $router->getRouteMatch('bdPhotos_ControllerPublic_Photo', $action, bdPhotos_Option::get('navTabId'));
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

        if (!empty($data['location_id'])) {
            $data['locationNameFormatted'] = XenForo_Template_Helper_Core::callHelper(
                'bdPhotos_formatLocationName', array($data));
        }

        if (isset($extraParams['setTitle'])
            || isset($extraParams['setString'])
        ) {
            if (!empty($extraParams['setTitle'])
                && !empty($extraParams['setString'])
            ) {
                $extraParams['s'] = $extraParams['setString'];
                $extraParams['st'] = utf8_trim($extraParams['setTitle']);
            }

            unset($extraParams['setTitle']);
            unset($extraParams['setString']);
        }

        if (isset($data['photo_id'])) {
            return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix,
                $action, $extension, $data, 'photo_id');
        } else {
            return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
        }
    }

}
