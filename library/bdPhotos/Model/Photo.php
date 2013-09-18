<?php

class bdPhotos_Model_Photo extends XenForo_Model
{
	const FETCH_ATTACHMENT = 0x01;
	const FETCH_ALBUM = 0x02;
	const FETCH_DEVICE = 0x04;
	const FETCH_LOCATION = 0x08;
	const FETCH_UPLOADER = 0x10;

	public function canViewPhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$this->_getAlbumModel()->canViewAlbum($album, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		if ($photo['publish_date'] == 0 OR $photo['publish_date'] > XenForo_Application::$time)
		{
			if ($photo['user_id'] == $viewingUser['user_id'])
			{
				// uploader can view his/her photo
			}
			elseif ($this->_getUploaderModel()->canViewAll($errorPhraseKey, $viewingUser))
			{
				// moderator can view all photos
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	public function canEditPhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($viewingUser['user_id'] > 0 AND $photo['user_id'] == $viewingUser['user_id'])
		{
			return true;
		}

		if ($this->canViewPhoto($album, $photo, $errorPhraseKey, $viewingUser) AND $this->_getAlbumModel()->canEditAlbum($album, $errorPhraseKey, $viewingUser))
		{
			return true;
		}

		return false;
	}

	public function canDeletePhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		return $this->canEditPhoto($album, $photo, $errorPhraseKey, $viewingUser);
	}

	public function canLikePhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']) OR $photo['user_id'] == $viewingUser['user_id'])
		{
			return false;
		}

		if (!$this->canViewPhoto($album, $photo, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_photoLike');
	}

	public function canCommentPhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']))
		{
			return false;
		}

		if (!$this->canViewPhoto($album, $photo, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_photoComment');
	}

	public function canDownloadFullPhoto(array $album, array $photo, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($viewingUser['user_id'] > 0 AND $photo['user_id'] == $viewingUser['user_id'])
		{
			return true;
		}

		if (!$this->canViewPhoto($album, $photo, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_downloadFull');
	}

	public function logPhotoView($photoId)
	{
		$this->_getDb()->query('
			INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '') . ' INTO xf_bdphotos_photo_view
				(photo_id)
			VALUES
				(?)
		', $photoId);
	}

	public function preparePhoto(array $album, array $photo, array $viewingUser = null)
	{
		if (isset($photo['attachment_id']))
		{
			$photo = $this->getModelFromCache('XenForo_Model_Attachment')->prepareAttachment($photo);
		}

		if (!empty($photo['photo_like_users']))
		{
			$photo['photoLikeUsers'] = unserialize($photo['photo_like_users']);
		}

		$photo['photo_view_count'] = max($photo['photo_view_count'], $photo['photo_comment_count'] + $photo['photo_like_count']);

		$photo['canEdit'] = $this->canEditPhoto($album, $photo, $null, $viewingUser);
		$photo['canDelete'] = $this->canDeletePhoto($album, $photo, $null, $viewingUser);
		$photo['canLike'] = $this->canLikePhoto($album, $photo, $null, $viewingUser);
		$photo['canComment'] = $this->canCommentPhoto($album, $photo, $null, $viewingUser);
		$photo['canDownloadFull'] = $this->canDownloadFullPhoto($album, $photo, $null, $viewingUser);

		return $photo;
	}

	public function preparePhotos(array $album, array $photos, array $viewingUser = null)
	{
		foreach ($photos as &$photo)
		{
			$photo = $this->preparePhoto($album, $photo, $viewingUser);
		}

		return $photos;
	}
	
	public function updatePhotoViews()
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_bdphotos_photo
			INNER JOIN (
				SELECT photo_id, COUNT(*) AS total
				FROM xf_bdphotos_photo_view
				GROUP BY photo_id
			) AS xf_pv ON (xf_pv.photo_id = xf_bdphotos_photo.photo_id)
			SET xf_bdphotos_photo.photo_view_count = xf_bdphotos_photo.photo_view_count + xf_pv.total
		');

		$db->query('TRUNCATE TABLE xf_bdphotos_photo_view');
	}

	/**
	 * @return bdPhotos_Model_Album
	 */
	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Album');
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$photos = $this->getPhotos($conditions, $fetchOptions);
		$list = array();

		foreach ($photos as $id => $photo)
		{
			$list[$id] = $photo['photo_caption'];
		}

		return $list;
	}

	public function getPhotoById($id, array $fetchOptions = array())
	{
		$photos = $this->getPhotos(array('photo_id' => $id), $fetchOptions);

		return reset($photos);
	}

	public function getPhotos(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePhotoConditions($conditions, $fetchOptions);

		$orderClause = $this->preparePhotoOrderOptions($fetchOptions);
		$joinOptions = $this->preparePhotoFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$photos = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT photo.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_photo` AS photo
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'photo_id');

		$this->_getPhotosCustomized($photos, $fetchOptions);

		return $photos;
	}

	public function countPhotos(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePhotoConditions($conditions, $fetchOptions);

		$orderClause = $this->preparePhotoOrderOptions($fetchOptions);
		$joinOptions = $this->preparePhotoFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_photo` AS photo
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function preparePhotoConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['photo_id']))
		{
			if (is_array($conditions['photo_id']))
			{
				if (!empty($conditions['photo_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_id IN (" . $db->quote($conditions['photo_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_id = " . $db->quote($conditions['photo_id']);
			}
		}

		if (isset($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				if (!empty($conditions['user_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.user_id IN (" . $db->quote($conditions['user_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.user_id = " . $db->quote($conditions['user_id']);
			}
		}

		if (isset($conditions['album_id']))
		{
			if (is_array($conditions['album_id']))
			{
				if (!empty($conditions['album_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.album_id IN (" . $db->quote($conditions['album_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.album_id = " . $db->quote($conditions['album_id']);
			}
		}

		if (isset($conditions['photo_position']))
		{
			if (is_array($conditions['photo_position']))
			{
				if (!empty($conditions['photo_position']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_position IN (" . $db->quote($conditions['photo_position']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_position = " . $db->quote($conditions['photo_position']);
			}
		}

		if (isset($conditions['publish_date']))
		{
			if (is_array($conditions['publish_date']))
			{
				if (!empty($conditions['publish_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.publish_date IN (" . $db->quote($conditions['publish_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.publish_date = " . $db->quote($conditions['publish_date']);
			}
		}

		if (isset($conditions['photo_view_count']))
		{
			if (is_array($conditions['photo_view_count']))
			{
				if (!empty($conditions['photo_view_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_view_count IN (" . $db->quote($conditions['photo_view_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_view_count = " . $db->quote($conditions['photo_view_count']);
			}
		}

		if (isset($conditions['photo_comment_count']))
		{
			if (is_array($conditions['photo_comment_count']))
			{
				if (!empty($conditions['photo_comment_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_comment_count IN (" . $db->quote($conditions['photo_comment_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_comment_count = " . $db->quote($conditions['photo_comment_count']);
			}
		}

		if (isset($conditions['photo_like_count']))
		{
			if (is_array($conditions['photo_like_count']))
			{
				if (!empty($conditions['photo_like_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_like_count IN (" . $db->quote($conditions['photo_like_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_like_count = " . $db->quote($conditions['photo_like_count']);
			}
		}

		if (isset($conditions['photo_score']))
		{
			if (is_array($conditions['photo_score']))
			{
				if (!empty($conditions['photo_score']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.photo_score IN (" . $db->quote($conditions['photo_score']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.photo_score = " . $db->quote($conditions['photo_score']);
			}
		}

		if (isset($conditions['score_date']))
		{
			if (is_array($conditions['score_date']))
			{
				if (!empty($conditions['score_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.score_date IN (" . $db->quote($conditions['score_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.score_date = " . $db->quote($conditions['score_date']);
			}
		}

		if (isset($conditions['device_id']))
		{
			if (is_array($conditions['device_id']))
			{
				if (!empty($conditions['device_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.device_id IN (" . $db->quote($conditions['device_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.device_id = " . $db->quote($conditions['device_id']);
			}
		}

		if (isset($conditions['location_id']))
		{
			if (is_array($conditions['location_id']))
			{
				if (!empty($conditions['location_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "photo.location_id IN (" . $db->quote($conditions['location_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "photo.location_id = " . $db->quote($conditions['location_id']);
			}
		}

		$this->_preparePhotoConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function preparePhotoFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_preparePhotoFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function preparePhotoOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_preparePhotoOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getPhotosCustomized(array &$data, array $fetchOptions)
	{
		foreach ($data as &$photo)
		{
			if (!empty($photo['metadata']))
			{
				$photo['metadataArray'] = unserialize($photo['metadata']);
			}

			if (!empty($photo['device_info']))
			{
				$photo['deviceInfo'] = unserialize($photo['device_info']);
			}

			if (!empty($photo['location_info']))
			{
				$photo['locationInfo'] = unserialize($photo['location_info']);
			}
		}
	}

	protected function _preparePhotoConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		if (isset($conditions['photo_is_published']))
		{
			if (empty($conditions['photo_is_published']))
			{
				$sqlConditions[] = 'photo.publish_date = 0';
			}
			else
			{
				$sqlConditions[] = '(photo.publish_date > 0 AND photo.publish_date < ' . $this->_db->quote(XenForo_Application::$time) . ')';
			}
		}
	}

	protected function _preparePhotoFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_ATTACHMENT)
			{
				$this->getModelFromCache('XenForo_Model_Attachment');

				$selectFields .= '
					,attachment.*
					,' . XenForo_Model_Attachment::$dataColumns . '
				';

				$joinTables .= '
					LEFT JOIN `xf_attachment` AS attachment
						ON (attachment.attachment_id = photo.photo_id)
					LEFT JOIN `xf_attachment_data` AS data
						ON (data.data_id = attachment.data_id)
				';
			}

			if ($fetchOptions['join'] & self::FETCH_ALBUM)
			{
				$selectFields .= '
					,album.album_id, album.album_user_id,
					album.album_name, album.album_description,
					album.album_publish_date
				';
				$joinTables .= '
					LEFT JOIN `xf_bdphotos_album` AS album
						ON (album.album_id = photo.album_id)
				';
			}

			if ($fetchOptions['join'] & self::FETCH_DEVICE)
			{
				$selectFields .= '
					,device.*
				';
				$joinTables .= '
					LEFT JOIN `xf_bdphotos_device` AS device
						ON (device.device_id = photo.device_id)
				';
			}

			if ($fetchOptions['join'] & self::FETCH_LOCATION)
			{
				$selectFields .= '
					,location.*
				';
				$joinTables .= '
					LEFT JOIN `xf_bdphotos_location` AS location
						ON (location.location_id = photo.location_id)
				';
			}

			if ($fetchOptions['join'] & self::FETCH_UPLOADER)
			{
				$selectFields .= '
					,user.*
				';

				$joinTables .= '
					LEFT JOIN `xf_user` AS user
						ON (user.user_id = photo.user_id)
				';
			}
		}

		if (isset($fetchOptions['likeUserId']))
		{
			if (empty($fetchOptions['likeUserId']))
			{
				$selectFields .= ',
					0 AS photo_like_date';
			}
			else
			{
				$selectFields .= ',
					liked_content.like_date AS photo_like_date';
				$joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'bdphotos_photo\'
							AND liked_content.content_id = photo.photo_id
							AND liked_content.like_user_id = ' . $this->_getDb()->quote($fetchOptions['likeUserId']) . ')';
			}
		}
	}

	protected function _preparePhotoOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		$choices['position'] = 'photo.photo_position %s, photo.photo_id';
		$choices['publish_date'] = 'photo.publish_date';
	}

}
