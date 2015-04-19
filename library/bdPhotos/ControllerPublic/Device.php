<?php

class bdPhotos_ControllerPublic_Device extends bdPhotos_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        return $this->responseReroute(__CLASS__, 'photos');
    }

    public function actionPhotos()
    {
        $deviceId = $this->_input->filterSingle('device_id', XenForo_Input::UINT);
        $device = $this->_getDeviceOrError($deviceId);

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/devices', $device, array('page' => $page)));

        $conditions = array(
            'device_id' => $device['device_id'],
            'is_published' => true,
        );
        $fetchOptions = array(
            'join' => bdPhotos_Model_Photo::FETCH_UPLOADER + bdPhotos_Model_Photo::FETCH_ALBUM,
            'order' => 'publish_date',
            'direction' => 'desc',

            'likeUserId' => XenForo_Visitor::getUserId(),

            'page' => $page,
            'perPage' => bdPhotos_Option::get('photosPerPage'),
        );

        $totalPhotos = $this->_getPhotoModel()->countPhotos($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('photosPerPage'), $totalPhotos, 'photos/devices', $device);

        $photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

        foreach ($photos as &$photo) {
            $photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
        }

        $viewParams = array(
            'device' => $device,
            'photos' => $photos,

            'pageNavLink' => 'photos/devices',
            'pageNavData' => $device,
            'page' => $page,
            'totalPhotos' => $totalPhotos,
        );

        return $this->responseView('bdPhotos_ViewPublic_Device_Photos', 'bdphotos_device_photos', $viewParams);
    }

    public function actionFind()
    {
        $q = ltrim($this->_input->filterSingle('q', XenForo_Input::STRING, array('noTrim' => true)));

        if ($q !== '' && utf8_strlen($q) >= 2)
        {
            $devices = $this->_getDeviceModel()->getDevices(
                array(
                    'device_name_like' => array($q , 'r'),
                ),
                array('limit' => 10)
            );
        }
        else
        {
            $devices = array();
        }

        $viewParams = array(
            'devices' => $devices
        );

        return $this->responseView(
            'bdPhotos_ViewPublic_Device_Find',
            '',
            $viewParams
        );
    }
}
