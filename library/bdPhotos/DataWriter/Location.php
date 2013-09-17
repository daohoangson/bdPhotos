<?php

class bdPhotos_DataWriter_Location extends XenForo_DataWriter
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array(
				'xf_bdphotos_location' => array(
				'location_id' => array('type' => 'uint', 'autoIncrement' => true),
				'location_name' => array('type' => 'string', 'required' => true, 'maxLength' => 255),
				'ne_lat' => array('type' => 'int', 'required' => true),
				'ne_lng' => array('type' => 'int', 'required' => true),
				'sw_lat' => array('type' => 'int', 'required' => true),
				'sw_lng' => array('type' => 'int', 'required' => true),
				'location_info' => array('type' => 'serialized'),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'location_id'))
		{
			return false;
		}

		return array('xf_bdphotos_location' => $this->_getLocationModel()->getLocationById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('location_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getLocationModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Location');
	}

/* End auto-generated lines of code. Feel free to make changes below */

}