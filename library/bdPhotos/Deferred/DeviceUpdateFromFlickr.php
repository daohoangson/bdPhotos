<?php

class bdPhotos_Deferred_DeviceUpdateFromFlickr extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $manufactures = &$data['manufactures'];

        $manufacture = array_shift($manufactures);

        $apiKey = bdPhotos_Option::get('flickrApiKey');
        if (!empty($apiKey)) {
            try {
                $flickrDevices = bdPhotos_Helper_FlickrApi::getDevices($apiKey, $manufacture['manufacture']);

                $flickrManufactures = array();
                foreach ($flickrDevices as $flickrDevice) {
                    $flickrManufactures[] = $flickrDevice['manufacture'];
                }
                $flickrManufactures = array_unique($flickrManufactures);

                /** @var bdPhotos_Model_Device $deviceModel */
                $deviceModel = XenForo_Model::create('bdPhotos_Model_Device');
                $existingDevices = $deviceModel->getDevices(array('manufacture' => $flickrManufactures), array('join' => bdPhotos_Model_Device::FETCH_CODE));

                foreach ($flickrDevices as $flickrDevice) {
                    $foundDevice = false;

                    foreach ($existingDevices as $existingDevice) {
                        if ($existingDevice['manufacture'] == $flickrDevice['manufacture'] AND $existingDevice['code'] == $flickrDevice['code']) {
                            $foundDevice = $existingDevice;
                        }
                    }

                    if (!empty($foundDevice)) {
                        // TODO: update existing device (and has data) with fresh data?
                        if (empty($foundDevice['device_info'])) {
                            $deviceDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
                            $deviceDw->setExistingData($foundDevice, true);
                            $deviceDw->set('device_name', $flickrDevice['device_name']);
                            $deviceDw->set('device_info', $flickrDevice['device_info']);
                            $deviceDw->save();
                            $device = $deviceDw->getMergedData();

                            XenForo_Helper_File::log('bdPhotos_device', call_user_func_array('sprintf', array(
                                'updated device with Flickr data #%d (%s %s)',
                                $device['device_id'],
                                $flickrDevice['manufacture'],
                                $flickrDevice['code'],
                            )));
                        }
                    } else {
                        $deviceDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
                        $deviceDw->set('device_name', $flickrDevice['device_name']);
                        $deviceDw->set('device_info', $flickrDevice['device_info']);
                        $deviceDw->save();
                        $device = $deviceDw->getMergedData();

                        $dcDw = XenForo_DataWriter::create('bdPhotos_DataWriter_DeviceCode');
                        $dcDw->set('manufacture', $flickrDevice['manufacture']);
                        $dcDw->set('code', $flickrDevice['code']);
                        $dcDw->set('device_id', $device['device_id']);
                        $dcDw->save();

                        XenForo_Helper_File::log('bdPhotos_device', call_user_func_array('sprintf', array(
                            'created device from Flickr data #%d (%s %s)',
                            $device['device_id'],
                            $flickrDevice['manufacture'],
                            $flickrDevice['code'],
                        )));
                    }
                }
            } catch (Zend_Exception $ze) {
                XenForo_Error::logException($ze, false);

                $manufactures[] = $manufacture;
            }
        }

        if (!empty($manufactures)) {
            return $data;
        } else {
            return false;
        }
    }

}
