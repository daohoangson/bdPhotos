<?php

class bdPhotos_ControllerPublic_Album extends bdPhotos_ControllerPublic_Abstract
{
    public function actionIndex()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        if ($albumId > 0) {
            return $this->responseReroute(__CLASS__, 'view');
        }

        $this->_assertCanView();

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums', '', array('page' => $page)));

        $conditions = array(
            'album_is_published' => 1,
            'photo_count_cutoff' => array('>', 0),
        );
        $fetchOptions = array(
            'join' => bdPhotos_Model_Album::FETCH_UPLOADER,
            'order' => 'update_date',
            'direction' => 'desc',

            'page' => $page,
            'perPage' => bdPhotos_Option::get('albumsPerPage'),
        );

        $totalAlbums = $this->_getAlbumModel()->countAlbums($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('albumsPerPage'), $totalAlbums, 'photo-albums');

        $albums = $this->_getAlbumModel()->getAlbums($conditions, $fetchOptions);

        $viewParams = array(
            'albums' => $albums,

            'canUpload' => $this->_getUploaderModel()->canUpload(),

            'pageNavLink' => 'photo-albums',
            'page' => $page,
            'totalAlbums' => $totalAlbums,
        );

        return $this->responseView('bdPhotos_ViewPublic_Album_Index', 'bdphotos_album_index', $viewParams);
    }

    public function actionView()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanViewAlbum($album);

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums', $album, array('page' => $page)));

        $uploader = $this->_getUserModel()->getUserById($album['album_user_id']);

        $conditions = array('album_id' => $album['album_id']);
        $fetchOptions = array(
            'join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT,
            'order' => 'position',

            'likeUserId' => XenForo_Visitor::getUserId(),

            'page' => $page,
            'perPage' => bdPhotos_Option::get('photosPerPage'),
        );

        $totalPhotos = $this->_getPhotoModel()->countPhotos($conditions, $fetchOptions);
        $this->canonicalizePageNumber($page, bdPhotos_Option::get('photosPerPage'), $totalPhotos, 'photo-albums', $album);

        $photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

        $comments = $this->_getAlbumCommentModel()->getAlbumComments(array('album_id' => $album['album_id']), array(
            'join' => bdPhotos_Model_AlbumComment::FETCH_COMMENT_USER,
            'order' => 'comment_date',
            'direction' => 'desc',
            'limit' => bdPhotos_Option::get('commentsPerPage'),
        ));

        $album = $this->_getAlbumModel()->prepareAlbum($album);
        $photos = $this->_getPhotoModel()->preparePhotos($album, $photos);

        $this->_getAlbumModel()->logAlbumView($album['album_id']);

        $viewParams = array(
            'album' => $album,
            'uploader' => $uploader,
            'photos' => $photos,
            'comments' => $comments,

            'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader),
            'canEditAlbum' => $this->_getAlbumModel()->canEditAlbum($album),

            'pageNavLink' => 'photo-albums',
            'pageNavData' => $album,
            'page' => $page,
            'totalPhotos' => $totalPhotos,
        );

        return $this->responseView('bdPhotos_ViewPublic_Album_View', 'bdphotos_album_view', $viewParams);
    }

    public function actionNew()
    {
        $this->_assertRegistrationRequired();
        $this->_assertCanUpload();

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums/new'));

        return $this->_actionNewOrEdit(array(
            'album_id' => 0,
            'album_name' => '',
        ));
    }

    public function actionEdit()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanEditAlbum($album);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums/edit', $album));

        return $this->_actionNewOrEdit($album, array(
            'get_photos' => true,
            'breadcrumbs_include_self' => true,
        ));
    }

    public function actionAddPhoto()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanEditAlbum($album);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums/add-photo', $album));

        return $this->_actionNewOrEdit($album, array(
            'template_name' => 'bdphotos_album_add_photo',
            'breadcrumbs_include_self' => true,
        ));
    }

    public function actionSave()
    {
        $this->_assertPostOnly();
        $this->_assertCanUpload();

        $visitor = XenForo_Visitor::getInstance();

        $albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');

        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        if (!empty($albumId)) {
            $album = $this->_getAlbumOrError($albumId);
            $this->_assertCanEditAlbum($album);

            $albumDw->setExistingData($album, true);
        } else {
            $albumDw->set('album_user_id', $visitor['user_id']);
            $albumDw->set('album_username', $visitor['username']);
        }

        if (!XenForo_Captcha_Abstract::validateDefault($this->_input)) {
            return $this->responseCaptchaFailed();
        }

        if ($this->_input->filterSingle('is_album_edit', XenForo_Input::UINT)) {
            $input = $this->_input->filter(array(
                'album_name' => XenForo_Input::STRING,
                'album_description' => XenForo_Input::STRING,
                'album_publish_date' => XenForo_Input::UINT,
                'cover_photo_id' => XenForo_Input::UINT,
            ));
            $albumDw->bulkSet($input);
        }

        $photoInput = $this->_input->filter(array(
            'attachment_hash' => XenForo_Input::STRING,

            'photo_caption' => XenForo_Input::ARRAY_SIMPLE,
            'photo_position' => XenForo_Input::ARRAY_SIMPLE,
            'device_id' => XenForo_Input::ARRAY_SIMPLE,
            'location_id' => XenForo_Input::ARRAY_SIMPLE,
            bdPhotos_DataWriter_Photo::EXTRA_DATA_ROI => XenForo_Input::ARRAY_SIMPLE,
        ));

        $albumDw->setExtraData(bdPhotos_DataWriter_Album::EXTRA_DATA_PHOTO_INPUT, $photoInput);

        $albumDw->save();

        $album = $albumDw->getMergedData();

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photo-albums', $album));
    }

    public function actionDelete()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanDeleteAlbum($album);

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums/delete', $album));

        if (!empty($album['album_user_id'])) {
            $uploader = $this->_getUserModel()->getUserById($album['album_user_id']);
        } else {
            $uploader = false;
        }

        $photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']));
        $photos = $this->_getPhotoModel()->preparePhotos($album, $photos);

        if ($this->isConfirmedPost()) {
            $albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
            $albumDw->setExistingData($album, true);
            $albumDw->delete();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('members/albums', $uploader));
        } else {
            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,

                'photos' => $photos,

                'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
            );

            return $this->responseView('bdPhotos_ViewPublic_Album_Delete', 'bdphotos_album_delete', $viewParams);
        }
    }

    public function actionLike()
    {
        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanLikeAlbum($album);

        $uploader = $this->_getUserModel()->getUserById($album['album_user_id']);

        $likeModel = $this->_getLikeModel();

        $existingLike = $likeModel->getContentLikeByLikeUser('bdphotos_album', $album['album_id'], XenForo_Visitor::getUserId());

        if ($this->_request->isPost()) {
            if ($existingLike) {
                $latestUsers = $likeModel->unlikeContent($existingLike);
            } else {
                $latestUsers = $likeModel->likeContent('bdphotos_album', $album['album_id'], $uploader['user_id']);
            }

            $liked = ($existingLike ? false : true);

            if ($this->_noRedirect() && $latestUsers !== false) {
                $album['albumLikeUsers'] = $latestUsers;
                $album['album_like_count'] += ($liked ? 1 : -1);
                $album['album_like_date'] = ($liked ? XenForo_Application::$time : 0);

                $viewParams = array(
                    'album' => $album,

                    'liked' => $liked,
                    '_list' => $this->_input->filterSingle('_list', XenForo_Input::UINT),
                );

                return $this->responseView('bdPhotos_ViewPublic_Album_LikeConfirmed', '', $viewParams);
            } else {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photo-albums', $album));
            }
        } else {
            $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photo-albums/like', $album));

            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,

                'existingLike' => $existingLike,
                'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
            );

            return $this->responseView('bdPhotos_ViewPublic_Album_Like', 'bdphotos_album_like', $viewParams);
        }
    }

    public function actionComment()
    {
        $this->_assertPostOnly();

        $albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
        $album = $this->_getAlbumOrError($albumId);

        $this->_assertCanCommentAlbum($album);

        $uploader = $this->_getUserModel()->getUserById($album['album_user_id']);

        $message = $this->_input->filterSingle('message', XenForo_Input::STRING);
        $visitor = XenForo_Visitor::getInstance();

        $dw = XenForo_DataWriter::create('bdPhotos_DataWriter_AlbumComment');
        $dw->bulkSet(array(
            'album_id' => $album['album_id'],
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
            $comment = $this->_getAlbumCommentModel()->getAlbumCommentById($dw->get('album_comment_id'), array('join' => bdPhotos_Model_AlbumComment::FETCH_COMMENT_USER));

            $viewParams = array(
                'album' => $album,
                'uploader' => $uploader,

                'comment' => $comment,
            );

            return $this->responseView('bdPhotos_ViewPublic_Album_Comment', '', $viewParams);
        } else {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photo-albums', $album));
        }
    }

    protected function _actionNewOrEdit($album = array(), array $options = array())
    {
        $options = array_merge(array(
            'get_photos' => false,
            'get_photos_fetchOptions' => array(
                'join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT + bdPhotos_Model_Photo::FETCH_DEVICE + bdPhotos_Model_Photo::FETCH_LOCATION,
                'order' => 'position',
            ),
            'template_name' => false,
            'breadcrumbs_include_self' => false,
        ), $options);

        if (!empty($album['album_user_id'])) {
            $uploader = $this->_getUserModel()->getUserById($album['album_user_id']);
        } else {
            $uploader = false;
        }

        if (!empty($options['get_photos']) AND !empty($album['album_id'])) {
            $photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']), $options['get_photos_fetchOptions']);
            $photos = $this->_getPhotoModel()->preparePhotos($album, $photos);
        } else {
            $photos = array();
        }

        $attachmentHash = $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING);
        $attachmentParams = $this->_getAlbumModel()->getAttachmentParams($album, null, $attachmentHash);

        $viewParams = array(
            'album' => $album,
            'uploader' => $uploader,

            'photos' => $photos,
            'attachmentParams' => $attachmentParams,
            'attachmentConstraints' => $this->_getAlbumModel()->getAttachmentConstraints(),

            'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, !empty($options['breadcrumbs_include_self'])),
        );

        if (!empty($options['template_name'])) {
            $templateName = $options['template_name'];
        } else {
            $templateName = 'bdphotos_album_edit';
        }

        return $this->responseView('bdPhotos_ViewPublic_Album_Edit', $templateName, $viewParams);
    }

}
