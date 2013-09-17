<?php

class bdPhotos_Model_DeviceCode extends XenForo_Model
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$deviceCodes = $this->getDeviceCodes($conditions, $fetchOptions);
		$list = array();

		foreach ($deviceCodes as $id => $deviceCode)
		{
			$list[$id] = $deviceCode['device_id'];
		}

		return $list;
	}

	public function getDeviceCodeById($id, array $fetchOptions = array())
	{
		$deviceCodes = $this->getDeviceCodes(array ('device_code_id' => $id), $fetchOptions);

		return reset($deviceCodes);
	}

	public function getDeviceCodes(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareDeviceCodeConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareDeviceCodeOrderOptions($fetchOptions);
		$joinOptions = $this->prepareDeviceCodeFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$deviceCodes = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT device_code.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_device_code` AS device_code
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'device_code_id');

		$this->_getDeviceCodesCustomized($deviceCodes, $fetchOptions);

		return $deviceCodes;
	}

	public function countDeviceCodes(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareDeviceCodeConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareDeviceCodeOrderOptions($fetchOptions);
		$joinOptions = $this->prepareDeviceCodeFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_device_code` AS device_code
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareDeviceCodeConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['device_code_id']))
		{
			if (is_array($conditions['device_code_id']))
			{
				if (!empty($conditions['device_code_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "device_code.device_code_id IN (" . $db->quote($conditions['device_code_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "device_code.device_code_id = " . $db->quote($conditions['device_code_id']);
			}
		}

		if (isset($conditions['manufacture']))
		{
			if (is_array($conditions['manufacture']))
			{
				if (!empty($conditions['manufacture']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "device_code.manufacture IN (" . $db->quote($conditions['manufacture']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "device_code.manufacture = " . $db->quote($conditions['manufacture']);
			}
		}

		if (isset($conditions['code']))
		{
			if (is_array($conditions['code']))
			{
				if (!empty($conditions['code']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "device_code.code IN (" . $db->quote($conditions['code']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "device_code.code = " . $db->quote($conditions['code']);
			}
		}

		if (isset($conditions['device_id']))
		{
			if (is_array($conditions['device_id']))
			{
				if (!empty($conditions['device_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "device_code.device_id IN (" . $db->quote($conditions['device_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "device_code.device_id = " . $db->quote($conditions['device_id']);
			}
		}

		$this->_prepareDeviceCodeConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareDeviceCodeFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_prepareDeviceCodeFetchOptionsCustomized($selectFields,  $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	public function prepareDeviceCodeOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_prepareDeviceCodeOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getDeviceCodesCustomized(array &$data, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareDeviceCodeConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareDeviceCodeFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareDeviceCodeOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		// customized code goes here
	}

}