<?php

class bdPhotos_Model_Location extends XenForo_Model
{
	public function getLocationNear($lat, $lng)
	{
		$locations = $this->getLocations(array('near' => array(
				$lat,
				$lng
			)));

		if (!empty($locations))
		{
			$location = $this->getNearestLocationFromArray($locations, $lat, $lng);
		}
		else
		{
			$apiKey = bdPhotos_Option::get('googleMapsApiKey');

			if (!empty($apiKey))
			{
				try
				{
					$location = bdPhotos_Helper_GoogleMapsApi::reverseDecoding($apiKey, $lat, $lng);
				}
				catch (Exception $e)
				{
					XenForo_Error::logException($e, false);
					$location = false;
				}
			}

			if (!empty($location))
			{
				$mergeIds = array();

				$locations = $this->getLocations(array('location_name' => $location['location_name']));
				foreach ($locations as $existingLocation)
				{
					if ($this->isLocationsNear($location, $existingLocation))
					{
						$mergeIds[] = $existingLocation['location_id'];
						$location['ne_lat'] = max($location['ne_lat'], $existingLocation['ne_lat']);
						$location['ne_lng'] = max($location['ne_lng'], $existingLocation['ne_lng']);
						$location['sw_lat'] = min($location['sw_lat'], $existingLocation['sw_lat']);
						$location['sw_lng'] = min($location['sw_lng'], $existingLocation['sw_lng']);
					}
				}

				if (empty($mergeIds))
				{
					$locationDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
					$locationDw->bulkSet($location);
					$locationDw->save();

					$location = $locationDw->getMergedData();
				}
				else
				{
					$mergeId = reset($mergeIds);

					if (count($mergeIds) > 1)
					{
						// update location for associated albums and photos
						$this->_getDb()->update('xf_bdphotos_albums', array('location_id' => $mergeId), array('location_id IN (' . $this->_getDb()->quote($mergeId) . ')'));
						$this->_getDb()->update('xf_bdphotos_photos', array('location_id' => $mergeId), array('location_id IN (' . $this->_getDb()->quote($mergeId) . ')'));
					}

					// delete merged locations
					foreach ($locations as $existingLocation)
					{
						if ($existingLocation['location_id'] != $mergeId)
						{
							$existingDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
							$existingDw->setExistingData($existingLocation, true);
							$locationDw->delete();
						}
					}

					// update merged locations with expanded information
					$locationDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
					$locationDw->setExistingData($locations[$mergeId], true);
					$locationDw->bulkSet($location);
					if ($locationDw->hasChanges())
					{
						$locationDw->save();

						XenForo_Helper_File::log('bdPhotos_location', call_user_func_array('sprintf', array(
							'expanded location #%d (%d, %d, %d, %d) -> (%d, %d, %d, %d)',
							$mergeId,
							$locations[$mergeId]['ne_lat'],
							$locations[$mergeId]['ne_lng'],
							$locations[$mergeId]['sw_lat'],
							$locations[$mergeId]['sw_lng'],
							$location['ne_lat'],
							$location['ne_lng'],
							$location['sw_lat'],
							$location['sw_lng']
						)));
					}

					$location = $locationDw->getMergedData();
				}
			}
		}

		return $location;
	}

	public function getNearestLocationFromArray(array $locations, $lat, $lng)
	{
		// TODO
		return reset($locations);
	}

	public function isLocationsNear($location1, $location2)
	{
		// TODO
		return false;
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$locations = $this->getLocations($conditions, $fetchOptions);
		$list = array();

		foreach ($locations as $id => $location)
		{
			$list[$id] = $location['location_name'];
		}

		return $list;
	}

	public function getLocationById($id, array $fetchOptions = array())
	{
		$locations = $this->getLocations(array('location_id' => $id), $fetchOptions);

		return reset($locations);
	}

	public function getLocations(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareLocationConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareLocationOrderOptions($fetchOptions);
		$joinOptions = $this->prepareLocationFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$locations = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT location.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_location` AS location
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'location_id');

		$this->_getLocationsCustomized($locations, $fetchOptions);

		return $locations;
	}

	public function countLocations(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareLocationConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareLocationOrderOptions($fetchOptions);
		$joinOptions = $this->prepareLocationFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_location` AS location
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareLocationConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['location_id']))
		{
			if (is_array($conditions['location_id']))
			{
				if (!empty($conditions['location_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.location_id IN (" . $db->quote($conditions['location_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.location_id = " . $db->quote($conditions['location_id']);
			}
		}

		if (isset($conditions['location_name']))
		{
			if (is_array($conditions['location_name']))
			{
				if (!empty($conditions['location_name']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.location_name IN (" . $db->quote($conditions['location_name']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.location_name = " . $db->quote($conditions['location_name']);
			}
		}

		if (isset($conditions['ne_lat']))
		{
			if (is_array($conditions['ne_lat']))
			{
				if (!empty($conditions['ne_lat']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.ne_lat IN (" . $db->quote($conditions['ne_lat']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.ne_lat = " . $db->quote($conditions['ne_lat']);
			}
		}

		if (isset($conditions['ne_lng']))
		{
			if (is_array($conditions['ne_lng']))
			{
				if (!empty($conditions['ne_lng']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.ne_lng IN (" . $db->quote($conditions['ne_lng']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.ne_lng = " . $db->quote($conditions['ne_lng']);
			}
		}

		if (isset($conditions['sw_lat']))
		{
			if (is_array($conditions['sw_lat']))
			{
				if (!empty($conditions['sw_lat']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.sw_lat IN (" . $db->quote($conditions['sw_lat']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.sw_lat = " . $db->quote($conditions['sw_lat']);
			}
		}

		if (isset($conditions['sw_lng']))
		{
			if (is_array($conditions['sw_lng']))
			{
				if (!empty($conditions['sw_lng']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.sw_lng IN (" . $db->quote($conditions['sw_lng']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.sw_lng = " . $db->quote($conditions['sw_lng']);
			}
		}

		$this->_prepareLocationConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareLocationFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_prepareLocationFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareLocationOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_prepareLocationOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getLocationsCustomized(array &$data, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareLocationConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		if (isset($conditions['near']))
		{
			if (is_array($conditions['near']) AND count($conditions['near']) == 2)
			{
				$latLower = intval($conditions['near'][0]);
				$latHigher = intval($conditions['near'][0]);
				$lngLower = intval($conditions['near'][1]);
				$lngHigher = intval($conditions['near'][1]);

				$sqlConditions[] = "location.ne_lat > $latHigher";
				$sqlConditions[] = "location.ne_lng > $lngHigher";
				$sqlConditions[] = "location.sw_lat < $latLower";
				$sqlConditions[] = "location.sw_lng < $lngLower";
			}
		}
	}

	protected function _prepareLocationFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareLocationOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		// customized code goes here
	}

}
