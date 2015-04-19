<?php

class bdPhotos_ControllerPublic_Photo extends bdPhotos_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        if (!empty($photoId)) {
            return $this->responseReroute(__CLASS__, 'view');
        }

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos', '', array('page' => $page)));

        $conditions = array('photo_is_published' => 1);
        $fetchOptions = array(
            'join' => bdPhotos_Model_Photo::FETCH_UPLOADER + bdPhotos_Model_Photo::FETCH_ALBUM,
            'order' => 'publish_date',
            'direction' => 'desc',

            'likeUserId' => XenForo_Visitor::getUserId(),

            'page' => $page,
            'perPage' => bdPhotos_Option::get('photosPerPage'),
        );

        $totalPhotos = $this->_getPhotoModel()->countPhotos($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('photosPerPage'), $totalPhotos, 'photos');

        $photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

        foreach ($photos as &$photo) {
            $photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
        }

        $viewParams = array(
            'photos' => $photos,

            'canUpload' => $this->_getUploaderModel()->canUpload(),

            'pageNavLink' => 'photos',
            'page' => $page,
            'totalPhotos' => $totalPhotos,
        );

        $viewParams = array_merge($viewParams, $this->_getSetHelper()->getViewParamsForPhotoList($conditions, $fetchOptions));

        return $this->responseView('bdPhotos_ViewPublic_Photo_Index', 'bdphotos_photo_index', $viewParams);
    }

    public function actionView()
    {
        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        $photo = $this->_getPhotoOrError($photoId, array(
            'join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT + bdPhotos_Model_Photo::FETCH_DEVICE + bdPhotos_Model_Photo::FETCH_LOCATION,

            'likeUserId' => XenForo_Visitor::getUserId(),
        ));
        $album = $this->_getAlbumOrError($photo['album_id']);
        $uploader = $this->_getUserModel()->getUserById($photo['user_id']);

        $this->_assertCanViewPhoto($album, $photo);

        $canonicalUrl = XenForo_Link::buildPublicLink('photos', $photo);
        $this->canonicalizeRequestUrl($canonicalUrl);

        $comments = $this->_getPhotoCommentModel()->getPhotoComments(array('photo_id' => $photo['photo_id']), array(
            'join' => bdPhotos_Model_PhotoComment::FETCH_COMMENT_USER,
            'order' => 'comment_date',
            'direction' => 'desc',
            'limit' => bdPhotos_Option::get('commentsPerPage'),
        ));

        $photo = $this->_getPhotoModel()->preparePhoto($album, $photo);
        $photo['preparedExif'] = $this->_getPhotoModel()->preparePhotoExif($photo);

        $this->_getPhotoModel()->logPhotoView($photo['photo_id']);

        $viewParams = array(
            'album' => $album,
            'uploader' => $uploader,
            'photo' => $photo,
            'comments' => $comments,

            'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
            'canonicalUrl' => $canonicalUrl,
            '_noRedirect' => $this->_noRedirect(),
        );

        $viewParams = array_merge($viewParams, $this->_getSetHelper()->getViewParamsForPhotoView($album, $photo));

        return $this->responseView('bdPhotos_ViewPublic_Photo_View', 'bdphotos_photo_view', $viewParams);
    }

    public function actionDownloadFull()
    {
        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        $this->_request->setParam('attachment_id', $photoId);

        return $this->responseReroute('XenForo_ControllerPublic_Attachment', 'index');
    }

    public function actionLike()
    {
        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        $photo = $this->_getPhotoOrError($photoId, array('join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT));
        $album = $this->_getAlbumOrError($photo['album_id']);
        $uploader = $this->_getUserModel()->getUserById($photo['user_id']);

        $this->_assertCanLikePhoto($album, $photo);

        $likeModel = $this->_getLikeModel();

        $existingLike = $likeModel->getContentLikeByLikeUser('bdphotos_photo', $photo['photo_id'], XenForo_Visitor::getUserId());

        if ($this->_request->isPost()) {
            if ($existingLike) {
                $latestUsers = $likeModel->unlikeContent($existingLike);
            } else {
                $latestUsers = $likeModel->likeContent('bdphotos_photo', $photo['photo_id'], $uploader['user_id']);
            }

            $liked = ($existingLike ? false : true);

            if ($this->_noRedirect() && $latestUsers !== false) {
                $photo['photoLikeUsers'] = $latestUsers;
                $photo['photo_like_count'] += ($liked ? 1 : -1);
                $photo['photo_like_date'] = ($liked ? XenForo_Application::$time : 0);

                $viewParams = array(
                    'album' => $album,
                    'photo' => $photo,

                    'liked' => $liked,
                    '_list' => $this->_input->filterSingle('_list', XenForo_Input::UINT),
                );

                return $this->responseView('bdPhotos_ViewPublic_Photo_LikeConfirmed', '', $viewParams);
            } else {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photos', $photo));
            }
        } else {
            $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/like', $photo));

            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,
                'photo' => $photo,

                'existingLike' => $existingLike,
                'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
            );

            return $this->responseView('bdPhotos_ViewPublic_Photo_Like', 'bdphotos_photo_like', $viewParams);
        }
    }

    public function actionComment()
    {
        $this->_assertPostOnly();

        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        $photo = $this->_getPhotoOrError($photoId, array('join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT));
        $album = $this->_getAlbumOrError($photo['album_id']);
        $uploader = $this->_getUserModel()->getUserById($photo['user_id']);

        $this->_assertCanCommentPhoto($album, $photo);

        $message = $this->_input->filterSingle('message', XenForo_Input::STRING);
        $visitor = XenForo_Visitor::getInstance();

        $dw = XenForo_DataWriter::create('bdPhotos_DataWriter_PhotoComment');
        $dw->bulkSet(array(
            'photo_id' => $photo['photo_id'],
            'user_id' => $visitor['user_id'],
            'username' => $visitor['username'],
            'message' => $message,
            'comment_date' => XenForo_Application::$time,
            'ip_id' => 0,
        ));
        $dw->preSave();

        if (!$dw->hasErrors()) {
            $this->assertNotFlooding('post');
        }

        $dw->save();

        if ($this->_noRedirect()) {
            $comment = $this->_getPhotoCommentModel()->getPhotoCommentById($dw->get('photo_comment_id'), array('join' => bdPhotos_Model_PhotoComment::FETCH_COMMENT_USER));

            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,
                'photo' => $photo,

                'comment' => $comment,
            );

            return $this->responseView('bdPhotos_ViewPublic_Photo_Comment', '', $viewParams);
        } else {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photos', $photo));
        }
    }

    public function actionEdit()
    {
        $photoId = $this->_input->filterSingle('photo_id', XenForo_Input::UINT);
        $photo = $this->_getPhotoOrError($photoId, array(
            'join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT
                | bdPhotos_Model_Photo::FETCH_DEVICE
                | bdPhotos_Model_Photo::FETCH_LOCATION,
        ));
        $album = $this->_getAlbumOrError($photo['album_id']);
        $uploader = $this->_getUserModel()->getUserById($photo['user_id']);

        $this->_assertCanEditPhoto($album, $photo);

        $photo = $this->_getPhotoModel()->preparePhoto($album, $photo);
        $photo['preparedExif'] = $this->_getPhotoModel()->preparePhotoExif($photo);

        if ($this->_request->isPost()) {
            $newMetadata = $photo['metadataArray'];

            if ($this->_input->filterSingle('exifReset', XenForo_Input::BOOLEAN)) {
                if (isset($newMetadata['manualExif'])) {
                    unset($newMetadata['manualExif']);
                }
            } else {
                $manualExifInput = new XenForo_Input($this->_input->filterSingle('exif', XenForo_Input::ARRAY_SIMPLE));
                $newMetadata['manualExif'] = $manualExifInput->filter(array(
                    'ExposureTime' => XenForo_Input::STRING,
                    'FNumber' => XenForo_Input::STRING,
                    'Flash' => XenForo_Input::STRING,
                    'ISOSpeedRatings' => XenForo_Input::STRING,
                    'FocalLength' => XenForo_Input::STRING,
                    'Software' => XenForo_Input::STRING,
                    'WhiteBalance' => XenForo_Input::STRING,
                ));
            }

            $deviceId = 0;
            $deviceName = $this->_input->filterSingle('device', XenForo_Input::STRING);
            if (!empty($deviceName)) {
                $device = $this->_getDeviceModel()->getDeviceByNameOrCreate($deviceName);
                if (!empty($device)) {
                    $deviceId = $device['device_id'];
                }
            }

            /** @var bdPhotos_DataWriter_Photo $dw */
            $dw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
            $dw->setExistingData($photo);
            $dw->set('photo_caption', $this->_input->filterSingle('photo_caption', XenForo_Input::STRING));
            $dw->set('device_id', $deviceId);
            $dw->set('metadata', $newMetadata);
            $dw->save();

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildPublicLink('photos', $photo)
            );
        } else {
            $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/edit', $photo));

            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,
                'photo' => $photo,

                'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
            );

            return $this->responseView('bdPhotos_ViewPublic_Photo_Edit', 'bdphotos_photo_edit', $viewParams);
        }
    }
}
