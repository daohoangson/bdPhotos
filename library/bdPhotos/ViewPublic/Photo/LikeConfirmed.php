<?php

class bdPhotos_ViewPublic_Photo_LikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$photo = $this->_params['photo'];

		if (!empty($photo['photoLikeUsers']))
		{
			$output = $this->_renderer->getDefaultOutputArray(__CLASS__, $this->_params, 'bdphotos_photo_likes_summary');
		}
		else
		{
			$output = array(
				'templateHtml' => '',
				'js' => '',
				'css' => ''
			);
		}

		$output += XenForo_ViewPublic_Helper_Like::getLikeViewParams($this->_params['liked']);

		if (!empty($this->_params['_list']))
		{
			// for bdphotos_photo_list_photo template, use like count as the term
			$output['term'] = strval($photo['photo_like_count']);
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}

}
