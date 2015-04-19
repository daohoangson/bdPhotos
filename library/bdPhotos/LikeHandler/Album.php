<?php

class bdPhotos_LikeHandler_Album extends XenForo_LikeHandler_Abstract
{
    public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
    {
        $dw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
        $dw->setExistingData($contentId);
        $dw->set('album_like_count', $dw->get('album_like_count') + $adjustAmount);
        $dw->set('album_like_users', $latestLikes);
        $dw->save();
    }

    public function getContentData(array $contentIds, array $viewingUser)
    {
        /** @var bdPhotos_Model_Album $albumModel */
        $albumModel = XenForo_Model::create('bdPhotos_Model_Album');
        $albums = $albumModel->getAlbums(array('album_id' => $contentIds));

        $output = array();
        foreach ($albums AS $albumId => $album) {
            if (!$albumModel->canViewAlbum($album, $null, $viewingUser)) {
                continue;
            }

            $output[$albumId] = $album;
        }

        return $output;
    }

    public function getListTemplateName()
    {
        // TODO
        return 'bdphotos_news_feed_item_album_like';
    }

}
