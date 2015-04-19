<?php

class bdPhotos_ControllerPublic_Metadata extends XenForo_ControllerPublic_Abstract
{
    public function actionGeoReverseDecoding()
    {
        $this->_assertPostOnly();

        $input = $this->_input->filter(array(
            'lat' => XenForo_Input::INT,
            'lng' => XenForo_Input::INT,
            'hash' => XenForo_Input::STRING,
        ));

        if ($input['hash'] != bdPhotos_Route_Prefix_Metadata::getHashForGeoReverseDecoding($input['lat'], $input['lng'])) {
            return $this->responseNoPermission();
        }

        $location = $this->_getLocationModel()->getLocationNear($input['lat'], $input['lng']);

        $viewParams = array(
            'lat' => $input['lat'],
            'lng' => $input['lng'],
            'location' => $location,
        );

        return $this->responseView('bdPhotos_ViewPublic_Metadata_Geo_ReverseDecoding', '', $viewParams);
    }

    /**
     * @return bdPhotos_Model_Location
     */
    protected function _getLocationModel()
    {
        return $this->getModelFromCache('bdPhotos_Model_Location');
    }

}
