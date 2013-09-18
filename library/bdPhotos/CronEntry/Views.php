<?php

class bdPhotos_CronEntry_Views
{
	public static function update()
	{
		XenForo_Model::create('bdPhotos_Model_Album')->updateAlbumViews();
		XenForo_Model::create('bdPhotos_Model_Photo')->updatePhotoViews();
	}
}