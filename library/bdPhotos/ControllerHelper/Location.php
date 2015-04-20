<?php

class bdPhotos_ControllerHelper_Location extends XenForo_ControllerHelper_Abstract
{
    public function getLocationId($name)
    {
        $input = $this->_controller->getInput();
        $locationNew = $input->filterSingle($name, XenForo_Input::STRING);
        $locationExisting = $input->filterSingle($name . '_existing', XenForo_Input::STRING);
        if ($locationNew == $locationExisting) {
            return $input->filterSingle($name . '_existingId', XenForo_Input::UINT);
        }

        $lat = $input->filterSingle($name . '_lat', XenForo_Input::STRING);
        $lng = $input->filterSingle($name . '_lng', XenForo_Input::STRING);
        if (strlen($lat) == 0 || strlen($lng) == 0) {
            // user disabled js or the script encountered some error
            // TODO
            return $input->filterSingle($name . '_existingId', XenForo_Input::UINT);
        }

        /** @var bdPhotos_Model_Location $locationModel */
        $locationModel = $this->_controller->getModelFromCache('bdPhotos_Model_Location');
        $_10e6 = pow(10, 6);
        $location = $locationModel->getLocationNear($lat * $_10e6, $lng * $_10e6);

        if (!empty($location)) {
            return $location['location_id'];
        } else {
            return 0;
        }
    }
}