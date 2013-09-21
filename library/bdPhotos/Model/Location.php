<?php

class bdPhotos_Model_Location extends XenForo_Model
{
	public function getLocationIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT location_id
			FROM xf_bdphotos_location
			WHERE location_id > ?
			ORDER BY location_id
		', $limit), $start);
	}

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
			$location = false;
			$apiKey = bdPhotos_Option::get('googleMapsApiKey');

			try
			{
				$location = bdPhotos_Helper_GoogleMapsApi::reverseDecoding($lat, $lng);
			}
			catch (Exception $e)
			{
				XenForo_Error::logException($e, false);
				$location = false;
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
					$albumUpdated = 0;
					$photoUpdated = 0;

					if (count($mergeIds) > 1)
					{
						// update location for associated albums and photos
						$photoUpdated += $this->_getDb()->update('xf_bdphotos_album', array('location_id' => $mergeId), array('location_id IN (' . $this->_getDb()->quote($mergeId) . ')'));
						$albumUpdated += $this->_getDb()->update('xf_bdphotos_photo', array('location_id' => $mergeId), array('location_id IN (' . $this->_getDb()->quote($mergeId) . ')'));
					}

					// delete merged locations
					foreach ($locations as $existingLocation)
					{
						if ($existingLocation['location_id'] != $mergeId)
						{
							$existingDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
							$existingDw->setExistingData($existingLocation, true);
							$existingDw->delete();
						}
					}

					// update merged locations with expanded information
					$locationDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
					$locationDw->setExistingData($locations[$mergeId], true);
					$locationDw->bulkSet($location);
					$locationDw->set('location_album_count', $locationDw->get('location_album_count') + $albumUpdated);
					$locationDw->set('location_photo_count', $locationDw->get('location_photo_count') + $photoUpdated);
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
		$nearestLocation = false;
		$nearestDistance = -1;

		foreach ($locations as $location)
		{
			$latLocation = ($location['ne_lat'] + $location['sw_lat']) / 2;
			$lngLocation = ($location['ne_lng'] + $location['sw_lng']) / 2;

			// simplified calculation
			$distanceLocation = sqrt(pow($lat - $latLocation, 2) + pow($lng - $lngLocation, 2));

			if ($nearestDistance == -1 OR $nearestDistance > $distanceLocation)
			{
				$nearestLocation = $location;
				$nearestDistance = $distanceLocation;
			}
		}

		return $nearestLocation;
	}

	public function isLocationsNear($location1, $location2)
	{
		$lat1 = ($location1['ne_lat'] + $location1['sw_lat']) / 2;
		$lng1 = ($location1['ne_lng'] + $location1['sw_lng']) / 2;

		$lat2 = ($location2['ne_lat'] + $location2['sw_lat']) / 2;
		$lng2 = ($location2['ne_lng'] + $location2['sw_lng']) / 2;

		$latDelta = max($location1['ne_lat'] - $location1['sw_lat'], $location2['ne_lat'] - $location2['sw_lat']);
		$lngDelta = max($location1['ne_lng'] - $location1['sw_lng'], $location2['ne_lng'] - $location2['sw_lng']);

		if (abs($lat1 - $lat2) < $latDelta AND abs($lng1 - $lng2) < $lngDelta)
		{
			return true;
		}

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

		if (isset($conditions['location_album_count']))
		{
			if (is_array($conditions['location_album_count']))
			{
				if (!empty($conditions['location_album_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.location_album_count IN (" . $db->quote($conditions['location_album_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.location_album_count = " . $db->quote($conditions['location_album_count']);
			}
		}

		if (isset($conditions['location_photo_count']))
		{
			if (is_array($conditions['location_photo_count']))
			{
				if (!empty($conditions['location_photo_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "location.location_photo_count IN (" . $db->quote($conditions['location_photo_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "location.location_photo_count = " . $db->quote($conditions['location_photo_count']);
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
		$db = $this->_getDb();

		if (isset($conditions['near']))
		{
			if (is_array($conditions['near']) AND count($conditions['near']) == 2)
			{
				$lat = intval($conditions['near'][0]);
				$lng = intval($conditions['near'][1]);

				$sqlConditions[] = "location.ne_lat > $lat";
				$sqlConditions[] = "location.ne_lng > $lng";
				$sqlConditions[] = "location.sw_lat < $lat";
				$sqlConditions[] = "location.sw_lng > $lng";
			}
		}

		if (!empty($conditions['location_name_like']))
		{
			if (is_array($conditions['location_name_like']))
			{
				$sqlConditions[] = 'location.location_name LIKE ' . XenForo_Db::quoteLike($conditions['location_name_like'][0], $conditions['location_name_like'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'location.location_name LIKE ' . XenForo_Db::quoteLike($conditions['location_name_like'], 'lr', $db);
			}
		}

		if (!empty($conditions['location_name_before']))
		{
			$sqlConditions[] = 'location.location_name < ' . $db->quote($conditions['location_name_before']);
		}
	}

	protected function _prepareLocationFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareLocationOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		$choices['location_name'] = 'location.location_name';
	}

}
