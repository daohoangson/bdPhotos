<?php
class bdPhotos_XenForo_ControllerPublic_Member extends XFCP_bdPhotos_XenForo_ControllerPublic_Member
{
	public function actionAlbums()
	{
		return $this->responseReroute('bdPhotos_ControllerPublic_Uploader', 'albums');
	}

	public function actionPhotos()
	{
		return $this->responseReroute('bdPhotos_ControllerPublic_Uploader', 'photos');
	}

}
