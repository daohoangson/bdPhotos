<?php

class bdPhotos_Model_Album extends XenForo_Model
{
	const FETCH_UPLOADER = 0x01;

	public function canViewAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$this->_getUploaderModel()->canView($errorPhraseKey, $viewingUser))
		{
			return false;
		}

		if (empty($album['album_publish_date']) OR $album['album_publish_date'] > XenForo_Application::$time)
		{
			if (empty($album['album_user_id']) OR $album['album_user_id'] == $viewingUser['user_id'])
			{
				// uploader can view his/her album
			}
			elseif ($this->_getUploaderModel()->canViewAll($errorPhraseKey, $viewingUser))
			{
				// moderator can view all albums
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	public function canEditAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($viewingUser['user_id'] > 0 AND !empty($album['album_user_id']) AND $album['album_user_id'] == $viewingUser['user_id'])
		{
			return true;
		}

		if ($this->canViewAlbum($album, $errorPhraseKey, $viewingUser) AND $this->_getUploaderModel()->canEditAll($errorPhraseKey, $viewingUser))
		{
			return true;
		}

		return false;
	}

	public function canDeleteAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null)
	{
		return $this->canEditAlbum($album, $errorPhraseKey, $viewingUser);
	}

	public function canLikeAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']) OR $album['album_user_id'] == $viewingUser['user_id'])
		{
			return false;
		}

		if (!$this->canViewAlbum($album, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_albumLike');
	}

	public function canCommentAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']))
		{
			return false;
		}

		if (!$this->canViewAlbum($album, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_albumComment');
	}

	public function logAlbumView($albumId)
	{
		$this->_getDb()->query('
			INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '') . ' INTO xf_bdphotos_album_view
				(album_id)
			VALUES
				(?)
		', $albumId);
	}

	public function getAttachmentParams(array $album, array $viewingUser = null, $tempHash = null)
	{
		$existing = is_string($tempHash) && strlen($tempHash) == 32;

		$contentData = array();
		if (!empty($album['album_id']))
		{
			$contentData['album_id'] = $album['album_id'];
		}

		$output = array(
			'hash' => $existing ? $tempHash : md5(uniqid('', true)),
			'content_type' => 'bdphotos_album',
			'content_data' => $contentData
		);

		if ($existing)
		{
			$attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
			$output['attachments'] = $attachmentModel->prepareAttachments($attachmentModel->getAttachmentsByTempHash($tempHash));
		}

		return $output;
	}

	public function getAttachmentConstraints()
	{
		return array(
			'extensions' => array(
				'png',
				'jpg',
				'jpeg',
				'jpe',
				'gif'
			),
			'size' => bdPhotos_Option::get('maxFileSize') * 1024,
			'width' => bdPhotos_Option::get('maxDimensions', 'width'),
			'height' => bdPhotos_Option::get('maxDimensions', 'height'),
			'count' => 0,
		);
	}

	public function getBreadcrumbs(array $album, $uploader, $includeSelf = false, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$breadcrumbs = array();

		if (!empty($uploader))
		{
			$breadcrumbs[] = array(
				'href' => XenForo_Link::buildPublicLink('full:members/albums', $uploader),
				'value' => ($viewingUser['user_id'] == $uploader['user_id'] ? new XenForo_Phrase('bdphotos_your_albums') : $uploader['username']),
			);
		}

		if ($includeSelf)
		{
			$breadcrumbs[] = array(
				'href' => XenForo_Link::buildPublicLink('full:photos/albums', $album),
				'value' => $album['album_name'],
			);
		}

		return $breadcrumbs;
	}

	public function prepareAlbum(array $album, array $viewingUser = null)
	{
		if (!empty($album['album_like_users']))
		{
			$album['albumLikeUsers'] = unserialize($album['album_like_users']);
		}

		$album['canEdit'] = $this->canEditAlbum($album, $null, $viewingUser);
		$album['canDelete'] = $this->canDeleteAlbum($album, $null, $viewingUser);
		$album['canLike'] = $this->canLikeAlbum($album, $null, $viewingUser);
		$album['canComment'] = $this->canCommentAlbum($album, $null, $viewingUser);

		return $album;
	}

	public function prepareAlbums(array $albums, array $viewingUser = null)
	{
		foreach ($albums as &$album)
		{
			$album = $this->prepareAlbum($album, $viewingUser);
		}

		return $albums;
	}

	public function updateAlbumViews()
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_bdphotos_album
			INNER JOIN (
				SELECT album_id, COUNT(*) AS total
				FROM xf_bdphotos_album_view
				GROUP BY album_id
			) AS xf_av ON (xf_av.album_id = xf_bdphotos_album.album_id)
			SET xf_bdphotos_album.album_view_count = xf_bdphotos_album.album_view_count + xf_av.total
		');

		$db->query('TRUNCATE TABLE xf_bdphotos_album_view');
	}

	/**
	 * @return bdPhotos_Model_Uploader
	 */
	protected function _getUploaderModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Uploader');
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$albums = $this->getAlbums($conditions, $fetchOptions);
		$list = array();

		foreach ($albums as $id => $album)
		{
			$list[$id] = $album['album_name'];
		}

		return $list;
	}

	public function getAlbumById($id, array $fetchOptions = array())
	{
		$albums = $this->getAlbums(array('album_id' => $id), $fetchOptions);

		return reset($albums);
	}

	public function getAlbums(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAlbumConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAlbumOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAlbumFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$albums = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT album.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_album` AS album
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'album_id');

		$this->_getAlbumsCustomized($albums, $fetchOptions);

		return $albums;
	}

	public function countAlbums(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAlbumConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAlbumOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAlbumFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_album` AS album
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareAlbumConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['album_id']))
		{
			if (is_array($conditions['album_id']))
			{
				if (!empty($conditions['album_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_id IN (" . $db->quote($conditions['album_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_id = " . $db->quote($conditions['album_id']);
			}
		}

		if (isset($conditions['album_user_id']))
		{
			if (is_array($conditions['album_user_id']))
			{
				if (!empty($conditions['album_user_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_user_id IN (" . $db->quote($conditions['album_user_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_user_id = " . $db->quote($conditions['album_user_id']);
			}
		}

		if (isset($conditions['album_username']))
		{
			if (is_array($conditions['album_username']))
			{
				if (!empty($conditions['album_username']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_username IN (" . $db->quote($conditions['album_username']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_username = " . $db->quote($conditions['album_username']);
			}
		}

		if (isset($conditions['album_name']))
		{
			if (is_array($conditions['album_name']))
			{
				if (!empty($conditions['album_name']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_name IN (" . $db->quote($conditions['album_name']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_name = " . $db->quote($conditions['album_name']);
			}
		}

		if (isset($conditions['album_position']))
		{
			if (is_array($conditions['album_position']))
			{
				if (!empty($conditions['album_position']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_position IN (" . $db->quote($conditions['album_position']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_position = " . $db->quote($conditions['album_position']);
			}
		}

		if (isset($conditions['create_date']))
		{
			if (is_array($conditions['create_date']))
			{
				if (!empty($conditions['create_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.create_date IN (" . $db->quote($conditions['create_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.create_date = " . $db->quote($conditions['create_date']);
			}
		}

		if (isset($conditions['update_date']))
		{
			if (is_array($conditions['update_date']))
			{
				if (!empty($conditions['update_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.update_date IN (" . $db->quote($conditions['update_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.update_date = " . $db->quote($conditions['update_date']);
			}
		}

		if (isset($conditions['album_publish_date']))
		{
			if (is_array($conditions['album_publish_date']))
			{
				if (!empty($conditions['album_publish_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_publish_date IN (" . $db->quote($conditions['album_publish_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_publish_date = " . $db->quote($conditions['album_publish_date']);
			}
		}

		if (isset($conditions['photo_count']))
		{
			if (is_array($conditions['photo_count']))
			{
				if (!empty($conditions['photo_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.photo_count IN (" . $db->quote($conditions['photo_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.photo_count = " . $db->quote($conditions['photo_count']);
			}
		}

		if (isset($conditions['album_view_count']))
		{
			if (is_array($conditions['album_view_count']))
			{
				if (!empty($conditions['album_view_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_view_count IN (" . $db->quote($conditions['album_view_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_view_count = " . $db->quote($conditions['album_view_count']);
			}
		}

		if (isset($conditions['album_comment_count']))
		{
			if (is_array($conditions['album_comment_count']))
			{
				if (!empty($conditions['album_comment_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_comment_count IN (" . $db->quote($conditions['album_comment_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_comment_count = " . $db->quote($conditions['album_comment_count']);
			}
		}

		if (isset($conditions['album_like_count']))
		{
			if (is_array($conditions['album_like_count']))
			{
				if (!empty($conditions['album_like_count']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.album_like_count IN (" . $db->quote($conditions['album_like_count']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.album_like_count = " . $db->quote($conditions['album_like_count']);
			}
		}

		if (isset($conditions['location_id']))
		{
			if (is_array($conditions['location_id']))
			{
				if (!empty($conditions['location_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.location_id IN (" . $db->quote($conditions['location_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.location_id = " . $db->quote($conditions['location_id']);
			}
		}

		if (isset($conditions['cover_photo_id']))
		{
			if (is_array($conditions['cover_photo_id']))
			{
				if (!empty($conditions['cover_photo_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album.cover_photo_id IN (" . $db->quote($conditions['cover_photo_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album.cover_photo_id = " . $db->quote($conditions['cover_photo_id']);
			}
		}

		$this->_prepareAlbumConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareAlbumFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_prepareAlbumFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareAlbumOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_prepareAlbumOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getAlbumsCustomized(array &$data, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareAlbumConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		if (isset($conditions['album_is_published']))
		{
			if (empty($conditions['album_is_published']))
			{
				$sqlConditions[] = 'album.album_publish_date = 0';
			}
			else
			{
				$sqlConditions[] = '(album.album_publish_date > 0 AND album.album_publish_date < ' . $this->_db->quote(XenForo_Application::$time) . ')';
			}
		}
	}

	protected function _prepareAlbumFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_UPLOADER)
			{
				$selectFields .= '
					,user.*
				';

				$joinTables .= '
					LEFT JOIN `xf_user` AS user
						ON (user.user_id = album.album_user_id)
				';
			}
		}
	}

	protected function _prepareAlbumOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		$choices['album_publish_date'] = 'album.album_publish_date';
		$choices['position'] = 'album.album_position %s, album.album_id';
		$choices['update_date'] = 'album.update_date';
	}

}
