<?php

class bdPhotos_Deferred_PhotoCounters extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'content_type' => 'devices',
            'batch' => 500,
            'position' => 0,
        ), $data);

        /* @var $photoModel bdPhotos_Model_Photo */
        $photoModel = XenForo_Model::create('bdPhotos_Model_Photo');

        $db = XenForo_Application::getDb();

        switch ($data['content_type']) {
            case 'locations':
                /** @var bdPhotos_Model_Location $locationModel */
                $locationModel = $photoModel->getModelFromCache('bdPhotos_Model_Location');
                $targetIds = $locationModel->getLocationIdsInRange($data['position'], $data['batch']);
                $photoConditions = array('location_id' => 'targetId');
                $targetTable = 'xf_bdphotos_location';
                $targetField = 'location_photo_count';
                $targetConditions = array('location_id = ?' => 'targetId');
                $typePhrase = new XenForo_Phrase('bdphotos_locations');
                break;
            case 'devices':
            default:
                /** @var bdPhotos_Model_Device $deviceModel */
                $deviceModel = $photoModel->getModelFromCache('bdPhotos_Model_Device');
                $targetIds = $deviceModel->getDeviceIdsInRange($data['position'], $data['batch']);
                $photoConditions = array('device_id' => 'targetId');
                $targetTable = 'xf_bdphotos_device';
                $targetField = 'device_photo_count';
                $targetConditions = array('device_id = ?' => 'targetId');
                $typePhrase = new XenForo_Phrase('bdphotos_devices');
                break;
        }

        if (sizeof($targetIds) == 0) {
            return false;
        }

        foreach ($targetIds AS $targetId) {
            $data['position'] = $targetId;

            $conditionsData = compact('targetId');

            $photoCount = $photoModel->countPhotos($this->_prepareConditions($photoConditions, $conditionsData));

            $db->update($targetTable, array($targetField => $photoCount), $this->_prepareConditions($targetConditions, $conditionsData));
        }

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

    protected function _prepareConditions(array $conditions, array $data)
    {
        $prepared = array();

        foreach ($conditions as $condition => $value) {
            if (is_string($value) AND isset($data[$value])) {
                $prepared[$condition] = $data[$value];
            } else {
                $prepared[$condition] = $value;
            }
        }

        return $prepared;
    }

}
