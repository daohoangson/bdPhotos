<?php

class bdPhotos_DataWriter_PhotoComment extends XenForo_DataWriter
{
    const OPTION_SET_IP_ADDRESS = 'setIpAddress';

    protected function _getDefaultOptions()
    {
        return array(self::OPTION_SET_IP_ADDRESS => true);
    }

    protected function _postSave()
    {
        if ($this->isInsert()) {
            $photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
            $photoDw->setExistingData($this->get('photo_id'));
            $photoDw->set('photo_comment_count', $photoDw->get('photo_comment_count') + 1);
            $photoDw->save();

            $photo = $photoDw->getMergedData();

            $userModel = $this->_getUserModel();

            if ($photo['user_id'] != $this->get('user_id')) {
                // alert photo uploader
                $user = $userModel->getUserById($photo['user_id'], array('join' => XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PROFILE));
                if ($user && !$userModel->isUserIgnored($user, $this->get('user_id')) && XenForo_Model_Alert::userReceivesAlert($user, 'bdphotos_photo', 'comment_your_photo')) {
                    XenForo_Model_Alert::alert($user['user_id'], $this->get('user_id'), $this->get('username'), 'bdphotos_photo', $photo['photo_id'], 'comment_your_photo');
                }
            }

            if ($this->getOption(self::OPTION_SET_IP_ADDRESS) && !$this->get('ip_id')) {
                $this->_updateIpData();
            }
        }
    }

    protected function _postDelete()
    {
        $photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
        $photoDw->setExistingData($this->get('photo_id'));
        $photoDw->set('photo_comment_count', max(0, $photoDw->get('photo_comment_count') - 1));
        $photoDw->save();
    }

    protected function _updateIpData()
    {
        if (!empty($this->_extraData['ipAddress'])) {
            $ipAddress = $this->_extraData['ipAddress'];
        } else {
            $ipAddress = null;
        }

        $ipId = XenForo_Model_Ip::log($this->get('user_id'), 'bdphotos_photo_comment', $this->get('photo_comment_id'), 'insert', $ipAddress);
        $this->set('ip_id', $ipId, '', array('setAfterPreSave' => true));

        $this->_db->update('xf_bdphotos_photo_comment', array('ip_id' => $ipId), 'photo_comment_id = ' . $this->_db->quote($this->get('photo_comment_id')));
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected function _getFields()
    {
        return array('xf_bdphotos_photo_comment' => array(
            'photo_comment_id' => array(
                'type' => 'uint',
                'autoIncrement' => true
            ),
            'photo_id' => array(
                'type' => 'uint',
                'required' => true
            ),
            'user_id' => array(
                'type' => 'uint',
                'required' => true
            ),
            'username' => array(
                'type' => 'string',
                'required' => true,
                'maxLength' => 50
            ),
            'comment_date' => array(
                'type' => 'uint',
                'required' => true
            ),
            'message' => array('type' => 'string'),
            'ip_id' => array(
                'type' => 'uint',
                'required' => true
            ),
        ));
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'photo_comment_id')) {
            return false;
        }

        return array('xf_bdphotos_photo_comment' => $this->_getPhotoCommentModel()->getPhotoCommentById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        $conditions = array();

        foreach (array('photo_comment_id') as $field) {
            $conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
        }

        return implode(' AND ', $conditions);
    }

    /**
     * @return bdPhotos_Model_PhotoComment
     */
    protected function _getPhotoCommentModel()
    {
        return $this->getModelFromCache('bdPhotos_Model_PhotoComment');
    }

    /* End auto-generated lines of code. Feel free to make changes below */

}
