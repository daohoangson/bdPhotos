<?php

class bdPhotos_DataWriter_Album extends XenForo_DataWriter
{
	const EXTRA_DATA_PHOTO_INPUT = 'photoInput';

	protected function _preSave()
	{
		if ($this->isInsert())
		{
			$this->set('create_date', XenForo_Application::$time);

			if (!($this->get('album_position') > 0))
			{
				$userAlbums = $this->_getAlbumModel()->getAlbums(array('album_user_id' => $this->get('album_user_id')));
				$maxPosition = 0;
				foreach ($userAlbums as $userAlbum)
				{
					$maxPosition = max($maxPosition, $userAlbum['album_position']);
				}
				$this->set('album_position', $maxPosition + 1);
			}
		}


		$this->set('update_date', XenForo_Application::$time);
	}

	protected function _postSave()
	{
		$photoInput = $this->getExtraData(self::EXTRA_DATA_PHOTO_INPUT);
		if (!empty($photoInput))
		{
			$this->_savePhotos($photoInput);
		}

		if ($this->isChanged('album_publish_date'))
		{
			if ($this->get('album_publish_date') == 0)
			{
				$this->_setPhotosPrivate();
			}
			else
			{
				$this->_setPhotosPublished();
			}
		}
	}

	protected function _postDelete()
	{
		$this->_deleteAttachments();

		$this->_deletePhotos();
	}

	protected function _savePhotos($photoInput)
	{
		$attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
		$attachmentHash = $photoInput['attachment_hash'];

		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $this->get('album_id')));
		if (!empty($attachmentHash))
		{
			$newAttachments = $attachmentModel->getAttachmentsByTempHash($attachmentHash);
		}
		else
		{
			$newAttachments = array();
		}

		$maxPhotoPosition = 0;
		foreach ($photos as $photo)
		{
			$maxPhotoPosition = max($maxPhotoPosition, $photo['photo_position']);

			$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
			$photoDw->setExistingData($photo, true);

			$photoDw->bulkSetFromPhotoInput($photoInput);

			if ($photoDw->hasChanges())
			{
				$photoDw->save();
			}
		}

		foreach ($newAttachments as $newAttachment)
		{
			$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
			$photoDw->setOption(bdPhotos_DataWriter_Photo::OPTION_UPDATE_ALBUM_PHOTO_COUNT, false);
			$photoDw->setExtraData(bdPhotos_DataWriter_Photo::EXTRA_DATA_ATTACHMENT, $newAttachment);

			$photoDw->set('photo_id', $newAttachment['attachment_id']);
			$photoDw->set('user_id', $this->get('album_user_id'));
			$photoDw->set('album_id', $this->get('album_id'));
			$photoDw->set('photo_position', ++$maxPhotoPosition);

			if ($this->get('album_publish_date') > 0)
			{
				$photoDw->set('publish_date', max(XenForo_Application::$time, $this->get('album_publish_date')));
			}

			$photoDw->bulkSetFromPhotoInput($photoInput);

			$photoDw->save();
		}

		// cannot call XenForo_DataWriter::set directly in _postSave
		$thisSet = array();

		if (!empty($newAttachments))
		{
			$newAttachmentIds = array_keys($newAttachments);

			$rows = $this->_db->update('xf_attachment', array(
				'content_type' => 'bdphotos_album',
				'content_id' => $this->get('album_id'),
				'temp_hash' => '',
				'unassociated' => 0
			), 'attachment_id IN (' . $this->_db->quote($newAttachmentIds) . ')');

			if ($this->get('cover_photo_id') == 0)
			{
				// get the first new photo as the cover photo
				// if there is no cover photo set
				$thisSet['cover_photo_id'] = reset($newAttachmentIds);
			}

			$thisSet['photo_count'] = $this->get('photo_count') + count($newAttachmentIds);
		}

		if (!empty($thisSet))
		{
			$this->_db->update('xf_bdphotos_album', $thisSet, array('album_id = ?' => $this->get('album_id')));
			foreach ($thisSet as $key => $value)
			{
				$this->set($key, $value, '', array('setAfterPreSave' => true));
			}
		}
	}

	protected function _setPhotosPrivate()
	{
		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $this->get('album_id')));
		foreach ($photos as $photo)
		{
			if ($photo['publish_date'] > 0)
			{
				$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
				$photoDw->setExistingData($photo, true);
				$photoDw->set('publish_date', 0);
				$photoDw->save();
			}
		}
	}

	protected function _setPhotosPublished()
	{
		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $this->get('album_id')));
		foreach ($photos as $photo)
		{
			if ($photo['publish_date'] == 0)
			{
				$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
				$photoDw->setExistingData($photo, true);
				$photoDw->set('publish_date', $this->get('album_publish_date'));
				$photoDw->save();
			}
		}
	}

	protected function _deleteAttachments()
	{
		$this->getModelFromCache('XenForo_Model_Attachment')->deleteAttachmentsFromContentIds('bdphotos_album', array($this->get('album_id')));
	}

	protected function _deletePhotos()
	{
		$photos = $this->_getPhotoModel()->getPhotos(array('album_id' => $this->get('album_id')));
		foreach ($photos as $photo)
		{
			$photoDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Photo');
			$photoDw->setOption(bdPhotos_DataWriter_Photo::OPTION_UPDATE_ALBUM_PHOTO_COUNT, false);
			$photoDw->setOption(bdPhotos_DataWriter_Photo::OPTION_DELETE_ATTACHMENT, false);

			$photoDw->setExistingData($photo, true);
			$photoDw->delete();
		}
	}

	/**
	 * @return bdPhotos_Model_Photo
	 */
	protected function _getPhotoModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Photo');
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array(
				'xf_bdphotos_album' => array(
				'album_id' => array('type' => 'uint', 'autoIncrement' => true),
				'album_user_id' => array('type' => 'uint', 'required' => true),
				'album_name' => array('type' => 'string', 'required' => true, 'maxLength' => 100),
				'album_description' => array('type' => 'string'),
				'album_position' => array('type' => 'uint', 'required' => true),
				'create_date' => array('type' => 'uint', 'required' => true),
				'update_date' => array('type' => 'uint', 'required' => true),
				'album_publish_date' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'photo_count' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'album_view_count' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'album_comment_count' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'album_like_count' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'album_like_users' => array('type' => 'serialized'),
				'location_id' => array('type' => 'uint', 'required' => true, 'default' => 0),
				'cover_photo_id' => array('type' => 'uint', 'required' => true, 'default' => 0),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'album_id'))
		{
			return false;
		}

		return array('xf_bdphotos_album' => $this->_getAlbumModel()->getAlbumById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('album_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Album');
	}

/* End auto-generated lines of code. Feel free to make changes below */

}
