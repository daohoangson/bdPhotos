<?php

class bdPhotos_Route_Prefix_Photos implements XenForo_Route_Interface
{
    protected $_subComponents = array(
        'albums' => array(
            'intId' => 'album_id',
            'title' => 'album_name',
            'controller' => 'bdPhotos_ControllerPublic_Album',
        ),
        'devices' => array(
            'intId' => 'device_id',
            'title' => 'device_name',
            'controller' => 'bdPhotos_ControllerPublic_Device',
        ),
        'locations' => array(
            'intId' => 'location_id',
            'title' => 'locationNameFormatted',
            'controller' => 'bdPhotos_ControllerPublic_Location',
        ),
    );

    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $controller = 'bdPhotos_ControllerPublic_Photo';
        $action = $router->getSubComponentAction($this->_subComponents, $routePath, $request, $controller);

        if ($action === false) {
            $request->setParam('setString', $request->getParam('s'));
            $request->setParam('setTitle', $request->getParam('st'));

            $action = $router->resolveActionWithIntegerParam($routePath, $request, 'photo_id');
        }

        return $router->getRouteMatch($controller, $action, bdPhotos_Option::get('navTabId'));
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if (!empty($data['location_id'])) {
            $data['locationNameFormatted'] = XenForo_Template_Helper_Core::callHelper('bdPhotos_formatLocationName', array($data));
        }

        $link = XenForo_Link::buildSubComponentLink($this->_subComponents, $outputPrefix, $action, $extension, $data);
        if (!$link) {
            if (isset($extraParams['setTitle']) OR isset($extraParams['setString'])) {
                if (!empty($extraParams['setTitle']) AND !empty($extraParams['setString'])) {
                    $extraParams['s'] = $extraParams['setString'];
                    $extraParams['st'] = utf8_trim($extraParams['setTitle']);
                }

                unset($extraParams['setTitle']);
                unset($extraParams['setString']);
            }

            $link = XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'photo_id');
        }

        return $link;
    }

}
