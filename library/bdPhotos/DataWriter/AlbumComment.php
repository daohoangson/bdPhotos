<?php

class bdPhotos_DataWriter_AlbumComment extends XenForo_DataWriter
{
	const OPTION_SET_IP_ADDRESS = 'setIpAddress';

	protected function _getDefaultOptions()
	{
		return array(self::OPTION_SET_IP_ADDRESS => true);
	}

	protected function _postSave()
	{
		if ($this->isInsert())
		{
			$albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
			$albumDw->setExistingData($this->get('album_id'));
			$albumDw->set('album_comment_count', $albumDw->get('album_comment_count') + 1);
			$albumDw->save();

			$album = $albumDw->getMergedData();

			$userModel = $this->_getUserModel();

			if ($album['album_user_id'] != $this->get('user_id'))
			{
				// alert album uploader
				$user = $userModel->getUserById($album['album_user_id'], array('join' => XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PROFILE));
				if ($user && !$userModel->isUserIgnored($user, $this->get('user_id')) && XenForo_Model_Alert::userReceivesAlert($user, 'bdphotos_album', 'comment_your_album'))
				{
					XenForo_Model_Alert::alert($user['user_id'], $this->get('user_id'), $this->get('username'), 'bdphotos_album', $album['album_id'], 'comment_your_album');
				}
			}

			if ($this->getOption(self::OPTION_SET_IP_ADDRESS) && !$this->get('ip_id'))
			{
				$this->_updateIpData();
			}
		}
	}

	protected function _postDelete()
	{
		$albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
		$albumDw->setExistingData($this->get('album_id'));
		$albumDw->set('album_comment_count', max(0, $albumDw->get('album_comment_count') - 1));
		$albumDw->save();
	}

	protected function _updateIpData()
	{
		if (!empty($this->_extraData['ipAddress']))
		{
			$ipAddress = $this->_extraData['ipAddress'];
		}
		else
		{
			$ipAddress = null;
		}

		$ipId = XenForo_Model_Ip::log($this->get('user_id'), 'bdphotos_album_comment', $this->get('album_comment_id'), 'insert', $ipAddress);
		$this->set('ip_id', $ipId, '', array('setAfterPreSave' => true));

		$this->_db->update('xf_bdphotos_album_comment', array('ip_id' => $ipId), 'album_comment_id = ' . $this->_db->quote($this->get('album_comment_id')));
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array('xf_bdphotos_album_comment' => array(
				'album_comment_id' => array(
					'type' => 'uint',
					'autoIncrement' => true
				),
				'album_id' => array(
					'type' => 'uint',
					'required' => true
				),
				'user_id' => array(
					'type' => 'uint',
					'required' => true
				),
				'username' => array(
					'type' => 'string',
					'required' => true,
					'maxLength' => 50
				),
				'comment_date' => array(
					'type' => 'uint',
					'required' => true
				),
				'message' => array('type' => 'string'),
				'ip_id' => array(
					'type' => 'uint',
					'required' => true
				),
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'album_comment_id'))
		{
			return false;
		}

		return array('xf_bdphotos_album_comment' => $this->_getAlbumCommentModel()->getAlbumCommentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('album_comment_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getAlbumCommentModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_AlbumComment');
	}

	/* End auto-generated lines of code. Feel free to make changes below */

}
