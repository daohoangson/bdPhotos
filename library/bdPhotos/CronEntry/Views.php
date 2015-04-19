<?php

class bdPhotos_CronEntry_Views
{
    public static function update()
    {
        /** @var bdPhotos_Model_Album $albumModel */
        $albumModel = XenForo_Model::create('bdPhotos_Model_Album');
        $albumModel->updateAlbumViews();

        /** @var bdPhotos_Model_Photo $photoModel */
        $photoModel = XenForo_Model::create('bdPhotos_Model_Photo');
        $photoModel->updatePhotoViews();
    }
}