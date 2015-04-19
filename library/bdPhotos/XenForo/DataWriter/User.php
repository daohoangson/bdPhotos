<?php

class bdPhotos_XenForo_DataWriter_User extends XFCP_bdPhotos_XenForo_DataWriter_User
{
    protected function _postDelete()
    {
        $this->_db->update('xf_bdphotos_album', array('album_user_id' => 0), array('album_user_id = ?' => $this->get('user_id')));
        $this->_db->update('xf_bdphotos_photo', array('user_id' => 0), array('user_id = ?' => $this->get('user_id')));
        $this->_db->update('xf_bdphotos_album_comment', array('user_id' => 0), array('user_id = ?' => $this->get('user_id')));
        $this->_db->update('xf_bdphotos_photo_comment', array('user_id' => 0), array('user_id = ?' => $this->get('user_id')));

        parent::_postDelete();
    }

}
