<?php

class bdPhotos_DataWriter_Device extends XenForo_DataWriter
{

	protected function _postDelete()
	{
		$this->_db->update('xf_bdphotos_photo', array('device_id' => 0), array('device_id = ?' => $this->get('device_id')));

		$this->_db->query('DELETE FROM `xf_bdphotos_device_code` WHERE device_id = ?', array($this->get('device_id')));
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array('xf_bdphotos_device' => array(
				'device_id' => array(
					'type' => 'uint',
					'autoIncrement' => true
				),
				'device_name' => array(
					'type' => 'string',
					'required' => true,
					'maxLength' => 255
				),
				'device_info' => array('type' => 'serialized'),
				'device_photo_count' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'device_id'))
		{
			return false;
		}

		return array('xf_bdphotos_device' => $this->_getDeviceModel()->getDeviceById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('device_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getDeviceModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Device');
	}

	/* End auto-generated lines of code. Feel free to make changes below */

}
