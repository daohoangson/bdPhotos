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

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('photos/devices', $device));

		$conditions = array(
			'device_id' => $device['device_id'],
			'is_published' => true,
		);
		$fetchOptions = array(
			'join' => bdPhotos_Model_Photo::FETCH_UPLOADER + bdPhotos_Model_Photo::FETCH_ALBUM,
			'order' => 'publish_date',
			'direction' => 'desc',

			'likeUserId' => XenForo_Visitor::getUserId(),
		);

		$photos = $this->_getPhotoModel()->getPhotos($conditions, $fetchOptions);

		foreach ($photos as &$photo)
		{
			$photo = $this->_getPhotoModel()->preparePhoto($photo, $photo);
		}

		$viewParams = array(
			'device' => $device,
			'photos' => $photos,
		);

		return $this->responseView('bdPhotos_ViewPublic_Device_Photos', 'bdphotos_device_photos', $viewParams);
	}

}
