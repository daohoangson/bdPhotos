<?php

class bdPhotos_ControllerPublic_Uploader extends bdPhotos_ControllerPublic_Abstract
{
	public function actionAlbums()
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$uploader = $this->getHelper('UserProfile')->assertUserProfileValidAndViewable($userId);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('members/albums', $uploader));

		$conditions = array('album_user_id' => $uploader['user_id']);
		$fetchOptions = array('order' => 'position');

		if ($uploader['user_id'] == XenForo_Visitor::getUserId())
		{
			// show all albums

			// let user upload if has permission
			$canUpload = $this->_getUploaderModel()->canUpload();
		}
		else
		{
			// show published album only
			$conditions['album_is_published'] = true;

			// no upload in different user's page
			$canUpload = false;
		}

		$albums = $this->_getAlbumModel()->getAlbums($conditions, $fetchOptions);

		$viewParams = array(
			'uploader' => $uploader,
			'albums' => $albums,

			'canUpload' => $canUpload,
		);

		return $this->responseView('bdPhotos_ViewPublic_Uploader_Albums', 'bdphotos_uploader_albums', $viewParams);
	}

	public function actionPhotos()
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$uploader = $this->getHelper('UserProfile')->assertUserProfileValidAndViewable($userId);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('members/photos', $uploader));

		$conditions = array('user_id' => $uploader['user_id']);
		$fetchOptions = array(
			'join' => bdPhotos_Model_Photo::FETCH_UPLOADER + bdPhotos_Model_Photo::FETCH_ALBUM,
			'order' => 'publish_date',
			'direction' => 'desc',

			'likeUserId' => XenForo_Visitor::getUserId(),
		);

		if ($uploader['user_id'] == XenForo_Visitor::getUserId())
		{
			// show all photos

			// let user upload if has permission
			$canUpload = $this->_getUploaderModel()->canUpload();
		}
		else
		{
			// show published photos only
			$conditions['is_published'] = true;

			// no upload in different user's page
			$canUpload = false;
		}

		$photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

		foreach ($photos as &$photo)
		{
			$photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
		}

		$viewParams = array(
			'uploader' => $uploader,
			'photos' => $photos,

			'canUpload' => $canUpload,
		);

		return $this->responseView('bdPhotos_ViewPublic_Uploader_Photos', 'bdphotos_uploader_photos', $viewParams);
	}

	protected function _postDispatch($controllerResponse, $controllerName, $action)
	{
		$this->_routeMatch->setSections(bdPhotos_Option::get('navTabId'));

		return parent::_postDispatch($controllerResponse, $controllerName, $action);
	}

}
