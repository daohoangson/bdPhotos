<?php

class bdPhotos_Model_AlbumComment extends XenForo_Model
{
	const FETCH_COMMENT_USER = 0x01;

	/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$albumComments = $this->getAlbumComments($conditions, $fetchOptions);
		$list = array();

		foreach ($albumComments as $id => $albumComment)
		{
			$list[$id] = $albumComment['message'];
		}

		return $list;
	}

	public function getAlbumCommentById($id, array $fetchOptions = array())
	{
		$albumComments = $this->getAlbumComments(array ('album_comment_id' => $id), $fetchOptions);

		return reset($albumComments);
	}

	public function getAlbumComments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAlbumCommentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAlbumCommentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAlbumCommentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$albumComments = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT album_comment.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_album_comment` AS album_comment
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'album_comment_id');

		$this->_getAlbumCommentsCustomized($albumComments, $fetchOptions);

		return $albumComments;
	}

	public function countAlbumComments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAlbumCommentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAlbumCommentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAlbumCommentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_album_comment` AS album_comment
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareAlbumCommentConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['album_comment_id']))
		{
			if (is_array($conditions['album_comment_id']))
			{
				if (!empty($conditions['album_comment_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album_comment.album_comment_id IN (" . $db->quote($conditions['album_comment_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album_comment.album_comment_id = " . $db->quote($conditions['album_comment_id']);
			}
		}

		if (isset($conditions['album_id']))
		{
			if (is_array($conditions['album_id']))
			{
				if (!empty($conditions['album_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album_comment.album_id IN (" . $db->quote($conditions['album_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album_comment.album_id = " . $db->quote($conditions['album_id']);
			}
		}

		if (isset($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				if (!empty($conditions['user_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album_comment.user_id IN (" . $db->quote($conditions['user_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album_comment.user_id = " . $db->quote($conditions['user_id']);
			}
		}

		if (isset($conditions['comment_date']))
		{
			if (is_array($conditions['comment_date']))
			{
				if (!empty($conditions['comment_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album_comment.comment_date IN (" . $db->quote($conditions['comment_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album_comment.comment_date = " . $db->quote($conditions['comment_date']);
			}
		}

		if (isset($conditions['ip_id']))
		{
			if (is_array($conditions['ip_id']))
			{
				if (!empty($conditions['ip_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "album_comment.ip_id IN (" . $db->quote($conditions['ip_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "album_comment.ip_id = " . $db->quote($conditions['ip_id']);
			}
		}

		$this->_prepareAlbumCommentConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareAlbumCommentFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_prepareAlbumCommentFetchOptionsCustomized($selectFields,  $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	public function prepareAlbumCommentOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_prepareAlbumCommentOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getAlbumCommentsCustomized(array &$data, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareAlbumCommentConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _prepareAlbumCommentFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_COMMENT_USER)
			{
				$selectFields .= '
					,user.*
				';

				$joinTables .= '
					LEFT JOIN `xf_user` AS user
						ON (user.user_id = album_comment.user_id)
				';
			}
		}
	}

	protected function _prepareAlbumCommentOrderOptionsCustomized(array &$choices, array &$fetchOptions)
	{
		$choices['comment_date'] = 'album_comment.comment_date';
	}

}
