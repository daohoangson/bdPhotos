<?php

class bdPhotos_ViewPublic_Album_LikeConfirmed extends XenForo_ViewPublic_Base
{
    public function renderJson()
    {
        $album = $this->_params['album'];

        if (!empty($album['albumLikeUsers'])) {
            /** @var XenForo_ViewRenderer_Json $renderer */
            $renderer = $this->_renderer;
            $output = $renderer->getDefaultOutputArray(__CLASS__, $this->_params, 'bdphotos_album_likes_summary');
        } else {
            $output = array(
                'templateHtml' => '',
                'js' => '',
                'css' => ''
            );
        }

        $output += XenForo_ViewPublic_Helper_Like::getLikeViewParams($this->_params['liked']);

        if (!empty($this->_params['_list'])) {
            // for bdphotos_photo_list_photo template, use like count as the term
            $output['term'] = strval($album['album_like_count']);
        }

        return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
    }

}
