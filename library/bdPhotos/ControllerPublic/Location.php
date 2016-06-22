<?php

class bdPhotos_ControllerPublic_Location extends bdPhotos_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        return $this->responseReroute(__CLASS__, 'photos');
    }

    public function actionPhotos()
    {
        $locationId = $this->_input->filterSingle('location_id', XenForo_Input::UINT);
        $location = $this->_getLocationOrError($locationId);

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-locations', $location, array('page' => $page)));

        $conditions = array(
            'location_id' => $location['location_id'],
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
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('photosPerPage'), $totalPhotos, 'photo-locations', $location);

        $photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

        foreach ($photos as &$photo) {
            $photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
        }

        $viewParams = array(
            'location' => $location,
            'photos' => $photos,

            'pageNavLink' => 'photo-locations',
            'pageNavData' => $location,
            'page' => $page,
            'totalPhotos' => $totalPhotos,
        );

        return $this->responseView('bdPhotos_ViewPublic_Location_Photos', 'bdphotos_location_photos', $viewParams);
    }

}
