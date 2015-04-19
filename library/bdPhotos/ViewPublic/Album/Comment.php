<?php

class bdPhotos_ViewPublic_Album_Comment extends XenForo_ViewPublic_Base
{
    public function renderJson()
    {
        return array('comment_insertAfter' => $this->createTemplateObject('bdphotos_album_or_photo_comment', $this->_params));
    }

}
