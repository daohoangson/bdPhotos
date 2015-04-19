<?php

class bdPhotos_Model_Device extends XenForo_Model
{
    const FETCH_CODE = 0x01;

    public function getDeviceByCodeOrCreate($manufacture, $code)
    {
        /** @var bdPhotos_Model_DeviceCode $deviceCodeModel */
        $deviceCodeModel = $this->getModelFromCache('bdPhotos_Model_DeviceCode');
        $deviceCodes = $deviceCodeModel->getDeviceCodes(array(
            'manufacture' => $manufacture,
            'code' => $code,
        ));
        $deviceCode = false;
        $device = false;

        if (!empty($deviceCodes)) {
            $deviceCode = reset($deviceCodes);
            $device = $this->getDeviceById($deviceCode['device_id']);
        }

        if (empty($device)) {
            // create a new device, and a device code associate with it
            $deviceDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
            $deviceDw->set('device_name', utf8_strtoupper(str_replace('_', ' ', $code)));
            $deviceDw->save();
            $device = $deviceDw->getMergedData();

            $dcDw = XenForo_DataWriter::create('bdPhotos_DataWriter_DeviceCode');
            if (!empty($deviceCode)) {
                $dcDw->setExistingData($deviceCode, true);
            }
            $dcDw->set('manufacture', $manufacture);
            $dcDw->set('code', $code);
            $dcDw->set('device_id', $device['device_id']);
            $dcDw->save();
            $deviceCode = $dcDw->getMergedData();

            XenForo_Helper_File::log('bdPhotos_device', call_user_func_array('sprintf', array(
                'created fake device #%d (%s %s)',
                $device['device_id'],
                $manufacture,
                $code,
            )));
        }

        return array_merge($device, $deviceCode);
    }

    public function getDeviceByNameOrCreate($name)
    {
        $devices = $this->getDevices(array('device_name' => $name));
        if (!empty($devices)) {
            return reset($devices);
        }

        $deviceDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
        $deviceDw->set('device_name', $name);
        $deviceDw->save();
        $device = $deviceDw->getMergedData();

        XenForo_Helper_File::log('bdPhotos_device', call_user_func_array('sprintf', array(
            'created fake device #%d (%s)',
            $device['device_id'],
            $name,
        )));

        return $device;
    }

    public function getDeviceIdsInRange($start, $limit)
    {
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
			SELECT device_id
			FROM xf_bdphotos_device
			WHERE device_id > ?
			ORDER BY device_id
		', $limit), $start);
    }

    /* Start auto-generated lines of code. Change made will be overwriten... */

    public function getList(array $conditions = array(), array $fetchOptions = array())
    {
        $devices = $this->getDevices($conditions, $fetchOptions);
        $list = array();

        foreach ($devices as $id => $device) {
            $list[$id] = $device['device_name'];
        }

        return $list;
    }

    public function getDeviceById($id, array $fetchOptions = array())
    {
        $devices = $this->getDevices(array('device_id' => $id), $fetchOptions);

        return reset($devices);
    }

    public function getDevices(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareDeviceConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareDeviceOrderOptions($fetchOptions);
        $joinOptions = $this->prepareDeviceFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $devices = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT device.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_device` AS device
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'device_id');

        $this->_getDevicesCustomized($devices, $fetchOptions);

        return $devices;
    }

    public function countDevices(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareDeviceConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareDeviceOrderOptions($fetchOptions);
        $joinOptions = $this->prepareDeviceFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_device` AS device
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
    }

    public function prepareDeviceConditions(array $conditions = array(), array $fetchOptions = array())
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if (isset($conditions['device_id'])) {
            if (is_array($conditions['device_id'])) {
                if (!empty($conditions['device_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "device.device_id IN (" . $db->quote($conditions['device_id']) . ")";
                }
            } else {
                $sqlConditions[] = "device.device_id = " . $db->quote($conditions['device_id']);
            }
        }

        if (isset($conditions['device_name'])) {
            if (is_array($conditions['device_name'])) {
                if (!empty($conditions['device_name'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "device.device_name IN (" . $db->quote($conditions['device_name']) . ")";
                }
            } else {
                $sqlConditions[] = "device.device_name = " . $db->quote($conditions['device_name']);
            }
        }

        if (isset($conditions['device_photo_count'])) {
            if (is_array($conditions['device_photo_count'])) {
                if (!empty($conditions['device_photo_count'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "device.device_photo_count IN (" . $db->quote($conditions['device_photo_count']) . ")";
                }
            } else {
                $sqlConditions[] = "device.device_photo_count = " . $db->quote($conditions['device_photo_count']);
            }
        }

        $this->_prepareDeviceConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

        return $this->getConditionsForClause($sqlConditions);
    }

    public function prepareDeviceFetchOptions(array $fetchOptions = array())
    {
        $selectFields = '';
        $joinTables = '';

        $this->_prepareDeviceFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    public function prepareDeviceOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
    {
        $choices = array();

        $this->_prepareDeviceOrderOptionsCustomized($choices, $fetchOptions);

        return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    protected function _getDevicesCustomized(array &$data, array $fetchOptions)
    {
        // customized code goes here
    }

    protected function _prepareDeviceConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
    {
        $db = $this->_getDb();

        if (isset($conditions['manufacture'])) {
            if (is_array($conditions['manufacture'])) {
                if (!empty($conditions['manufacture'])) {
                    $sqlConditions[] = "device_code.manufacture IN (" . $db->quote($conditions['manufacture']) . ")";
                }
            } else {
                $sqlConditions[] = "device_code.manufacture = " . $db->quote($conditions['manufacture']);
            }
        }

        if (isset($conditions['code'])) {
            if (is_array($conditions['code'])) {
                if (!empty($conditions['code'])) {
                    $sqlConditions[] = "device_code.code IN (" . $db->quote($conditions['code']) . ")";
                }
            } else {
                $sqlConditions[] = "device_code.code = " . $db->quote($conditions['code']);
            }
        }

        if (!empty($conditions['device_name_like'])) {
            if (is_array($conditions['device_name_like'])) {
                $sqlConditions[] = 'device.device_name LIKE ' . XenForo_Db::quoteLike($conditions['device_name_like'][0], $conditions['device_name_like'][1], $db);
            } else {
                $sqlConditions[] = 'device.device_name LIKE ' . XenForo_Db::quoteLike($conditions['device_name_like'], 'lr', $db);
            }
        }

        if (!empty($conditions['device_name_before'])) {
            $sqlConditions[] = 'device.device_name < ' . $db->quote($conditions['device_name_before']);
        }
    }

    protected function _prepareDeviceFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
    {
        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_CODE) {
                $selectFields .= '
					,device_code.*
				';

                $joinTables .= '
					LEFT JOIN `xf_bdphotos_device_code` AS device_code
						ON (device_code.device_id = device.device_id)
				';
            }
        }
    }

    protected function _prepareDeviceOrderOptionsCustomized(array &$choices, array &$fetchOptions)
    {
        $choices['device_name'] = 'device.device_name';
    }

}
