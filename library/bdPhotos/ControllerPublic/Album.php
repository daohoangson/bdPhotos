<?php

class bdPhotos_ControllerPublic_Album extends bdPhotos_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
		if ($albumId > 0)
		{
			return $this->responseReroute(__CLASS__, 'view');
		}

		$albums = $this->_getAlbumModel()->getAlbums(array('album_is_published' => 1), array(
			'join' => bdPhotos_Model_Album::FETCH_UPLOADER,
			'order' => 'update_date',
			'direction' => 'desc',
		));

		$viewParams = array(
			'albums' => $albums,

			'canUpload' => $this->_getUploaderModel()->canUpload(),
		);

		return $this->responseView('bdPhotos_ViewPublic_Album_Index', 'bdphotos_album_index', $viewParams);
	}

	public function actionView()
	{
		$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
		$album = $this->_getAlbumOrError($albumId);

		$this->_assertCanViewAlbum($album);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/albums', $album));

		$uploader = $this->_getUserModel()->getUserById($album['album_user_id']);
		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']), array(
			'join' => bdPhotos_Model_Photo::FETCH_ATTACHMENT,
			'order' => 'position',

			'likeUserId' => XenForo_Visitor::getUserId(),
		));

		$photos = $this->_getPhotoModel()->preparePhotos($album, $photos);

		$this->_getAlbumModel()->logAlbumView($album['album_id']);

		$viewParams = array(
			'album' => $album,
			'uploader' => $uploader,
			'photos' => $photos,

			'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader),
			'canEditAlbum' => $this->_getAlbumModel()->canEditAlbum($album),
		);

		return $this->responseView('bdPhotos_ViewPublic_Album_View', 'bdphotos_album_view', $viewParams);
	}

	public function actionNew()
	{
		$this->_assertRegistrationRequired();
		$this->_assertCanUpload();

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/albums/new'));

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

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/albums/edit', $album));

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

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/albums/add-photo', $album));

		return $this->_actionNewOrEdit($album, array(
			'template_name' => 'bdphotos_album_add_photo',
			'breadcrumbs_include_self' => true,
		));
	}

	public function actionSave()
	{
		$this->_assertPostOnly();
		$this->_assertCanUpload();

		$albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');

		$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
		if (!empty($albumId))
		{
			$album = $this->_getAlbumOrError($albumId);
			$this->_assertCanEditAlbum($album);

			$albumDw->setExistingData($album, true);
		}
		else
		{
			$albumDw->set('album_user_id', XenForo_Visitor::getUserId());
		}

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			return $this->responseCaptchaFailed();
		}

		if ($this->_input->filterSingle('is_album_edit', XenForo_Input::UINT))
		{
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

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('photos/albums', $album));
	}

	public function actionDelete()
	{
		$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
		$album = $this->_getAlbumOrError($albumId);

		$this->_assertCanDeleteAlbum($album);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/albums/delete', $album));

		if (!empty($album['album_user_id']))
		{
			$uploader = $this->_getUserModel()->getUserById($album['album_user_id']);
		}
		else
		{
			$uploader = false;
		}

		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']));
		$photos = $this->_getPhotoModel()->preparePhotos($album, $photos);

		if ($this->isConfirmedPost())
		{
			$albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
			$albumDw->setExistingData($album, true);
			$albumDw->delete();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('members/albums', $uploader));
		}
		else
		{
			$viewParams = array(
				'album' => $album,
				'uploader' => $uploader,

				'photos' => $photos,

				'breadcrumbs' => $this->_getAlbumModel()->getBreadcrumbs($album, $uploader, true),
			);

			return $this->responseView('bdPhotos_ViewPublic_Album_Delete', 'bdphotos_album_delete', $viewParams);
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

		if (!empty($album['album_user_id']))
		{
			$uploader = $this->_getUserModel()->getUserById($album['album_user_id']);
		}
		else
		{
			$uploader = false;
		}

		if (!empty($options['get_photos']) AND !empty($album['album_id']))
		{
			$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $album['album_id']), $options['get_photos_fetchOptions']);
			$photos = $this->_getPhotoModel()->preparePhotos($album, $photos);
		}
		else
		{
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

		if (!empty($options['template_name']))
		{
			$templateName = $options['template_name'];
		}
		else
		{
			$templateName = 'bdphotos_album_edit';
		}

		return $this->responseView('bdPhotos_ViewPublic_Album_Edit', $templateName, $viewParams);
	}

}
