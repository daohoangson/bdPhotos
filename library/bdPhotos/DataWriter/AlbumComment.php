<?php

class bdPhotos_DataWriter_AlbumComment extends XenForo_DataWriter
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields()
	{
		return array(
				'xf_bdphotos_album_comment' => array(
				'album_comment_id' => array('type' => 'uint', 'autoIncrement' => true),
				'album_id' => array('type' => 'uint', 'required' => true),
				'user_id' => array('type' => 'uint', 'required' => true),
				'comment_date' => array('type' => 'uint', 'required' => true),
				'message' => array('type' => 'string'),
				'ip_id' => array('type' => 'uint', 'required' => true),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'photo_comment_id'))
		{
			return false;
		}

		return array('xf_bdphotos_album_comment' => $this->_getAlbumCommentModel()->getAlbumCommentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('photo_comment_id') as $field)
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