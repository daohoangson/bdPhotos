<?php

class bdPhotos_DataWriter_DeviceCode extends XenForo_DataWriter
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array(
				'xf_bdphotos_device_code' => array(
				'device_code_id' => array('type' => 'uint', 'autoIncrement' => true),
				'manufacture' => array('type' => 'string', 'required' => true, 'maxLength' => 100),
				'code' => array('type' => 'string', 'required' => true, 'maxLength' => 100),
				'device_id' => array('type' => 'uint', 'required' => true),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'device_code_id'))
		{
			return false;
		}

		return array('xf_bdphotos_device_code' => $this->_getDeviceCodeModel()->getDeviceCodeById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('device_code_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getDeviceCodeModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_DeviceCode');
	}

/* End auto-generated lines of code. Feel free to make changes below */

}