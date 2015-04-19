<?php

class bdPhotos_ControllerPublic_Uploader extends bdPhotos_ControllerPublic_Abstract
{
    public function actionAlbums()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        /** @var XenForo_ControllerHelper_UserProfile $helper */
        $helper = $this->getHelper('UserProfile');
        $uploader = $helper->assertUserProfileValidAndViewable($userId);

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('members/albums', $uploader, array('page' => $page)));

        $conditions = array('album_user_id' => $uploader['user_id']);
        $fetchOptions = array(
            'order' => 'position',

            'page' => $page,
            'perPage' => bdPhotos_Option::get('albumsPerPage'),
        );

        if ($uploader['user_id'] == XenForo_Visitor::getUserId()) {
            // show all albums

            // let user upload if has permission
            $canUpload = $this->_getUploaderModel()->canUpload();
        } else {
            // show published album only
            $conditions['album_is_published'] = true;

            // no upload in different user's page
            $canUpload = false;
        }

        $totalAlbums = $this->_getAlbumModel()->countAlbums($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('albumsPerPage'), $totalAlbums, 'members/albums', $uploader);

        $albums = $this->_getAlbumModel()->getAlbums($conditions, $fetchOptions);

        $viewParams = array(
            'uploader' => $uploader,
            'albums' => $albums,

            'canUpload' => $canUpload,

            'pageNavLink' => 'members/albums',
            'pageNavData' => $uploader,
            'page' => $page,
            'totalAlbums' => $totalAlbums,
        );

        return $this->responseView('bdPhotos_ViewPublic_Uploader_Albums', 'bdphotos_uploader_albums', $viewParams);
    }

    public function actionPhotos()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        /** @var XenForo_ControllerHelper_UserProfile $helper */
        $helper = $this->getHelper('UserProfile');
        $uploader = $helper->assertUserProfileValidAndViewable($userId);

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('members/photos', $uploader, array('page' => $page)));

        $conditions = array('user_id' => $uploader['user_id']);
        $fetchOptions = array(
            'join' => bdPhotos_Model_Photo::FETCH_UPLOADER + bdPhotos_Model_Photo::FETCH_ALBUM,
            'order' => 'publish_date',
            'direction' => 'desc',

            'likeUserId' => XenForo_Visitor::getUserId(),

            'page' => $page,
            'perPage' => bdPhotos_Option::get('photosPerPage'),
        );

        if ($uploader['user_id'] == XenForo_Visitor::getUserId()) {
            // show all photos

            // let user upload if has permission
            $canUpload = $this->_getUploaderModel()->canUpload();
        } else {
            // show published photos only
            $conditions['is_published'] = true;

            // no upload in different user's page
            $canUpload = false;
        }

        $totalPhotos = $this->_getPhotoModel()->countPhotos($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('photosPerPage'), $totalPhotos, 'members/photos', $uploader);

        $photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

        foreach ($photos as &$photo) {
            $photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
        }

        $viewParams = array(
            'uploader' => $uploader,
            'photos' => $photos,

            'canUpload' => $canUpload,

            'pageNavLink' => 'members/photos',
            'pageNavData' => $uploader,
            'page' => $page,
            'totalPhotos' => $totalPhotos,
        );

        $viewParams = array_merge($viewParams, $this->_getSetHelper()->getViewParamsForPhotoList($conditions, $fetchOptions));

        return $this->responseView('bdPhotos_ViewPublic_Uploader_Photos', 'bdphotos_uploader_photos', $viewParams);
    }

    protected function _postDispatch($controllerResponse, $controllerName, $action)
    {
        $this->_routeMatch->setSections(bdPhotos_Option::get('navTabId'));

        parent::_postDispatch($controllerResponse, $controllerName, $action);
    }

}
