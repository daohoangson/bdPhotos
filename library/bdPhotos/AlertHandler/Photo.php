<?php

class bdPhotos_AlertHandler_Photo extends XenForo_AlertHandler_Abstract
{
    protected $_photoModel = null;

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        return $this->_getPhotoModel()->getPhotos(array('photo_id' => $contentIds), array('join' => bdPhotos_Model_Photo::FETCH_ALBUM));
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return $this->_getPhotoModel()->canViewPhoto($content, $content, $null, $viewingUser);
    }

    protected function _getDefaultTemplateTitle($contentType, $action)
    {
        return 'bdphotos_' . parent::_getDefaultTemplateTitle('photo', $action);
    }

    /**
     * @return bdPhotos_Model_Photo
     */
    protected function _getPhotoModel()
    {
        if (!$this->_photoModel) {
            $this->_photoModel = XenForo_Model::create('bdPhotos_Model_Photo');
        }

        return $this->_photoModel;
    }

}
