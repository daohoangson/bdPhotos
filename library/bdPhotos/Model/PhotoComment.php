<?php

class bdPhotos_Model_PhotoComment extends XenForo_Model
{
    const FETCH_COMMENT_USER = 0x01;

    /* Start auto-generated lines of code. Change made will be overwriten... */

    public function getList(array $conditions = array(), array $fetchOptions = array())
    {
        $photoComments = $this->getPhotoComments($conditions, $fetchOptions);
        $list = array();

        foreach ($photoComments as $id => $photoComment) {
            $list[$id] = $photoComment['message'];
        }

        return $list;
    }

    public function getPhotoCommentById($id, array $fetchOptions = array())
    {
        $photoComments = $this->getPhotoComments(array('photo_comment_id' => $id), $fetchOptions);

        return reset($photoComments);
    }

    public function getPhotoComments(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->preparePhotoCommentConditions($conditions, $fetchOptions);

        $orderClause = $this->preparePhotoCommentOrderOptions($fetchOptions);
        $joinOptions = $this->preparePhotoCommentFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $photoComments = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT photo_comment.*
				$joinOptions[selectFields]
			FROM `xf_bdphotos_photo_comment` AS photo_comment
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'photo_comment_id');

        $this->_getPhotoCommentsCustomized($photoComments, $fetchOptions);

        return $photoComments;
    }

    public function countPhotoComments(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->preparePhotoCommentConditions($conditions, $fetchOptions);

        $orderClause = $this->preparePhotoCommentOrderOptions($fetchOptions);
        $joinOptions = $this->preparePhotoCommentFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdphotos_photo_comment` AS photo_comment
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
    }

    public function preparePhotoCommentConditions(array $conditions = array(), array $fetchOptions = array())
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if (isset($conditions['photo_comment_id'])) {
            if (is_array($conditions['photo_comment_id'])) {
                if (!empty($conditions['photo_comment_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.photo_comment_id IN (" . $db->quote($conditions['photo_comment_id']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.photo_comment_id = " . $db->quote($conditions['photo_comment_id']);
            }
        }

        if (isset($conditions['photo_id'])) {
            if (is_array($conditions['photo_id'])) {
                if (!empty($conditions['photo_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.photo_id IN (" . $db->quote($conditions['photo_id']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.photo_id = " . $db->quote($conditions['photo_id']);
            }
        }

        if (isset($conditions['user_id'])) {
            if (is_array($conditions['user_id'])) {
                if (!empty($conditions['user_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.user_id IN (" . $db->quote($conditions['user_id']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.user_id = " . $db->quote($conditions['user_id']);
            }
        }

        if (isset($conditions['username'])) {
            if (is_array($conditions['username'])) {
                if (!empty($conditions['username'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.username IN (" . $db->quote($conditions['username']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.username = " . $db->quote($conditions['username']);
            }
        }

        if (isset($conditions['comment_date'])) {
            if (is_array($conditions['comment_date'])) {
                if (!empty($conditions['comment_date'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.comment_date IN (" . $db->quote($conditions['comment_date']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.comment_date = " . $db->quote($conditions['comment_date']);
            }
        }

        if (isset($conditions['ip_id'])) {
            if (is_array($conditions['ip_id'])) {
                if (!empty($conditions['ip_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "photo_comment.ip_id IN (" . $db->quote($conditions['ip_id']) . ")";
                }
            } else {
                $sqlConditions[] = "photo_comment.ip_id = " . $db->quote($conditions['ip_id']);
            }
        }

        $this->_preparePhotoCommentConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

        return $this->getConditionsForClause($sqlConditions);
    }

    public function preparePhotoCommentFetchOptions(array $fetchOptions = array())
    {
        $selectFields = '';
        $joinTables = '';

        $this->_preparePhotoCommentFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    public function preparePhotoCommentOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
    {
        $choices = array();

        $this->_preparePhotoCommentOrderOptionsCustomized($choices, $fetchOptions);

        return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    protected function _getPhotoCommentsCustomized(array &$data, array $fetchOptions)
    {
        // customized code goes here
    }

    protected function _preparePhotoCommentConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
    {
        // customized code goes here
    }

    protected function _preparePhotoCommentFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
    {
        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_COMMENT_USER) {
                $selectFields .= '
					,user.*
				';

                $joinTables .= '
					LEFT JOIN `xf_user` AS user
						ON (user.user_id = photo_comment.user_id)
				';
            }
        }
    }

    protected function _preparePhotoCommentOrderOptionsCustomized(array &$choices, array &$fetchOptions)
    {
        $choices['comment_date'] = 'photo_comment.comment_date';
    }

}
