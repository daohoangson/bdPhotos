<?php

class bdPhotos_LikeHandler_Photo extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
		$dw->setExistingData($contentId);
		$dw->set('photo_like_count', $dw->get('photo_like_count') + $adjustAmount);
		$dw->set('photo_like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$photoModel = XenForo_Model::create('bdPhotos_Model_Photo');
		$photos = $photoModel->getPhotos(array('photo_id' => $contentIds), array('join' => bdPhotos_Model_Photo::FETCH_ALBUM));

		$output = array();
		foreach ($photos AS $photoId => $photo)
		{
			if (!$photoModel->canViewPhoto($photo, $photo, $null, $viewingUser))
			{
				continue;
			}

			$output[$photoId] = $photo;
		}

		return $output;
	}

	public function getListTemplateName()
	{
		// TODO
		return 'bdphotos_news_feed_item_photo_like';
	}

}
