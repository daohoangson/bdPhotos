<?php

class bdPhotos_DataWriter_Photo extends XenForo_DataWriter
{
	const OPTION_UPDATE_ALBUM_PHOTO_COUNT = 'updateAlbum_photoCount';
	const OPTION_DELETE_ATTACHMENT = 'deleteAttachment';
	const OPTION_DELETE_LIKES = 'deleteLikes';
	const OPTION_DELETE_COMMENTS = 'deleteComments';
	const OPTION_DELETE_ALERTS = 'deleteAlerts';

	const EXTRA_DATA_ATTACHMENT = 'attachment';
	const EXTRA_DATA_ROI = 'roi';

	public function bulkSetFromPhotoInput(array $photoInput)
	{
		$photoId = $this->get('photo_id');

		foreach ($photoInput as $key => $value)
		{
			if (is_array($value) AND isset($value[$photoId]) AND strval($value[$photoId]) !== '')
			{
				if ($key == self::EXTRA_DATA_ROI)
				{
					$this->setExtraData(self::EXTRA_DATA_ROI, $value[$photoId]);

					if ($this->isUpdate())
					{
						// this function will be called again in _readMetadata
						// which only runs when it isInsert()
						$this->set('metadata', $this->_getMetadataWithRoi());
					}
				}
				else
				{
					$this->set($key, $value[$photoId], '', array('ignoreInvalidFields' => true));
				}
			}
		}
	}

	protected function _getDefaultOptions()
	{
		return array(
			self::OPTION_UPDATE_ALBUM_PHOTO_COUNT => true,
			self::OPTION_DELETE_ATTACHMENT => true,
			self::OPTION_DELETE_LIKES => true,
			self::OPTION_DELETE_COMMENTS => true,
			self::OPTION_DELETE_ALERTS => true,
		);
	}

	protected function _getMetadataWithRoi()
	{
		$metadata = $this->get('metadata');

		$roi = $this->getExtraData(self::EXTRA_DATA_ROI);
		if (!empty($roi))
		{
			$metadata = @unserialize($metadata);
			if (empty($metadata))
			{
				$metadata = array();
			}
			$metadata[bdPhotos_Helper_Image::OPTION_ROI] = $roi;
		}

		return $metadata;
	}

	protected function _preSave()
	{
		if ($this->isInsert())
		{
			if (!($this->get('photo_position') > 0))
			{
				$albumPhotos = $this->_getPhotoModel()->getPhotos(array('album_id' => $this->get('album_id')));
				$maxPosition = 0;
				foreach ($albumPhotos as $albumPhoto)
				{
					$maxPosition = max($maxPosition, $albumPhoto['photo_position']);
				}
				$this->set('photo_position', $maxPosition + 1);
			}

			if (!$this->get('metadata'))
			{
				$this->_readMetadata();
			}

			$this->set('metadata', $this->_getMetadataWithRoi());
		}
	}

	protected function _postSave()
	{
		if ($this->isInsert())
		{
			if ($this->getOption(self::OPTION_UPDATE_ALBUM_PHOTO_COUNT))
			{
				$this->_db->query('UPDATE xf_bdphotos_album SET photo_count = photo_count + 1 WHERE album_id = ?', array($this->get('album_id')));
			}
		}

		if ($this->get('device_id') != $this->getExisting('device_id'))
		{
			$this->_updateDevice();
		}

		if ($this->get('location_id') != $this->getExisting('location_id'))
		{
			$this->_updateLocation();
		}
	}

	protected function _postDelete()
	{
		if ($this->getOption(self::OPTION_UPDATE_ALBUM_PHOTO_COUNT))
		{
			$this->_db->query('UPDATE xf_bdphotos_album SET photo_count = IF(photo_count > 0, photo_count - 1, 0) WHERE album_id = ?', array($this->get('album_id')));
		}

		$this->_deleteAttachment();

		$this->_deleteLikes();

		$this->_deleteComments();

		$this->_deleteAlerts();

		$this->_updateDevice(true);

		$this->_updateLocation(true);
	}

	protected function _readMetadata()
	{
		$attachment = $this->_getAttachment();
		$filePath = bdPhotos_Helper_Attachment::getAttachmentDataFilePath($this->_getAttachmentModel(), $attachment);
		if (is_readable($filePath))
		{
			$metadata = bdPhotos_Helper_Metadata::readFromFile($filePath);

			if (!empty($metadata['exif']) AND bdPhotos_Option::get('doStrip'))
			{
				// EXIF data found, we should proceed to strip off EXIF data from the data file
				$options = array();
				bdPhotos_Helper_Image::prepareOptionsFromExifData($options, $metadata['exif']);

				$stripped = bdPhotos_Helper_Image::stripJpeg($filePath, $options);

				if (!empty($stripped))
				{
					$upload = new XenForo_Upload($attachment['filename'], $stripped);
					$dataId = $this->_getAttachmentModel()->insertUploadedAttachmentData($upload, $this->get('user_id'));

					// update the attachment to use the new data
					$this->_db->update('xf_attachment', array('data_id' => $dataId), array('attachment_id = ?' => $attachment['attachment_id']));
					$this->_db->query('UPDATE xf_attachment_data SET attach_count = attach_count + 1 WHERE data_id = ?', array($dataId));
					$this->setExtraData(self::EXTRA_DATA_ATTACHMENT, false);

					// decrease the attach count for the old data
					$this->_db->query('UPDATE xf_attachment_data SET attach_count = IF(attach_count > 0, attach_count - 1, 0) WHERE data_id = ?', array($attachment['data_id']));

					// update EXIF data
					$metadata['exif'] = bdPhotos_Helper_Metadata::cleanUpExifDataAfterStripping($metadata['exif']);
				}
			}

			bdPhotos_Helper_Attachment::prepareUsableFilePath($filePath, $attachment, $metadata);

			$this->set('metadata', $metadata);
		}
	}

	protected function _getAttachment()
	{
		if (!$this->getExtraData(self::EXTRA_DATA_ATTACHMENT))
		{
			$attachment = $this->_getAttachmentModel()->getAttachmentById($this->get('photo_id'));
			$this->setExtraData(self::EXTRA_DATA_ATTACHMENT, $attachment);
		}

		return $this->getExtraData(self::EXTRA_DATA_ATTACHMENT);
	}

	protected function _deleteAttachment()
	{
		if ($this->getOption(self::OPTION_DELETE_ATTACHMENT))
		{
			$attachmentDw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
			$attachmentDw->setExistingData($this->get('photo_id'));
			$attachmentDw->delete();
		}
	}

	protected function _deleteLikes()
	{
		if ($this->getOption(self::OPTION_DELETE_LIKES))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes('bdphotos_photo', $this->get('photo_id'), true);
		}
	}

	protected function _deleteComments()
	{
		if ($this->getOption(self::OPTION_DELETE_COMMENTS))
		{
			$this->_db->delete('xf_bdphotos_photo_comment', 'photo_id = ' . $this->_db->quote($this->get('photo_id')));
		}
	}

	protected function _deleteAlerts()
	{
		if ($this->getOption(self::OPTION_DELETE_ALERTS))
		{
			$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('bdphotos_photo', $this->get('photo_id'));
		}
	}

	protected function _updateDevice($isDelete = false)
	{
		$existingDeviceId = $this->getExisting('device_id');
		$deviceId = $this->get('device_id');

		if (!$isDelete)
		{
			if ($existingDeviceId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_device` SET device_photo_count = IF(device_photo_count > 0, device_photo_count - 1, 0) WHERE device_id = ?', array($existingDeviceId));
			}

			if ($deviceId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_device` SET device_photo_count = device_photo_count + 1 WHERE device_id = ?', array($deviceId));
			}
		}
		else
		{
			if ($deviceId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_device` SET device_photo_count = IF(device_photo_count > 0, device_photo_count - 1, 0) WHERE device_id = ?', array($deviceId));
			}
		}
	}

	protected function _updateLocation($isDelete = false)
	{
		$existingLocationId = $this->getExisting('location_id');
		$locationId = $this->get('location_id');

		if (!$isDelete)
		{
			if ($existingLocationId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_location` SET location_photo_count = IF(location_photo_count > 0, location_photo_count - 1, 0) WHERE location_id = ?', array($existingLocationId));
			}

			if ($locationId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_location` SET location_photo_count = location_photo_count + 1 WHERE location_id = ?', array($locationId));
			}
		}
		else
		{
			if ($locationId > 0)
			{
				$this->_db->query('UPDATE `xf_bdphotos_location` SET location_photo_count = IF(location_photo_count > 0, location_photo_count - 1, 0) WHERE location_id = ?', array($locationId));
			}
		}
	}

	/**
	 * @return XenForo_Model_Attachment
	 */
	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array('xf_bdphotos_photo' => array(
				'photo_id' => array(
					'type' => 'uint',
					'autoIncrement' => true
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
				'album_id' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'photo_caption' => array('type' => 'string'),
				'photo_position' => array(
					'type' => 'uint',
					'required' => true
				),
				'publish_date' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'photo_view_count' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'photo_comment_count' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'photo_like_count' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'photo_like_users' => array('type' => 'serialized'),
				'device_id' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'location_id' => array(
					'type' => 'uint',
					'required' => true,
					'default' => 0
				),
				'metadata' => array('type' => 'serialized'),
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'photo_id'))
		{
			return false;
		}

		return array('xf_bdphotos_photo' => $this->_getPhotoModel()->getPhotoById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('photo_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getPhotoModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Photo');
	}

	/* End auto-generated lines of code. Feel free to make changes below */

}
