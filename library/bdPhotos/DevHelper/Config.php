<?php
class bdPhotos_DevHelper_Config extends DevHelper_Config_Base
{
	protected $_dataClasses = array(
		'photo' => array(
			'name' => 'photo',
			'camelCase' => 'Photo',
			'camelCasePlural' => 'Photos',
			'camelCaseWSpace' => 'Photo',
			'camelCasePluralWSpace' => 'Photos',
			'fields' => array(
				'photo_id' => array('name' => 'photo_id', 'type' => 'uint', 'autoIncrement' => true),
				'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true),
				'album_id' => array('name' => 'album_id', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_caption' => array('name' => 'photo_caption', 'type' => 'string'),
				'photo_position' => array('name' => 'photo_position', 'type' => 'uint', 'required' => true),
				'publish_date' => array('name' => 'publish_date', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_view_count' => array('name' => 'photo_view_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_comment_count' => array('name' => 'photo_comment_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_like_count' => array('name' => 'photo_like_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_like_users' => array('name' => 'photo_like_users', 'type' => 'serialized'),
				'device_id' => array('name' => 'device_id', 'type' => 'uint', 'required' => true, 'default' => 0),
				'location_id' => array('name' => 'location_id', 'type' => 'uint', 'required' => true, 'default' => 0),
				'metadata' => array('name' => 'metadata', 'type' => 'serialized'),
			),
			'phrases' => array(),
			'id_field' => 'photo_id',
			'title_field' => 'photo_caption',
			'primaryKey' => array('photo_id'),
			'indeces' => array(
				'user_id_album_id_photo_position' => array(
					'name' => 'user_id_album_id_photo_position',
					'fields' => array('user_id', 'album_id', 'photo_position'),
					'type' => 'NORMAL',
				),
				'publish_date' => array('name' => 'publish_date', 'fields' => array('publish_date'), 'type' => 'NORMAL'),
				'location_id' => array('name' => 'location_id', 'fields' => array('location_id'), 'type' => 'NORMAL'),
				'device_id' => array('name' => 'device_id', 'fields' => array('device_id'), 'type' => 'NORMAL'),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_Photo', 'hash' => 'e7979d810028a846cbe16c928ebb5afd'),
				'model' => array('className' => 'bdPhotos_Model_Photo', 'hash' => '8f38a2207a7ca8a121b2455d2133837d'),
				'route_prefix_admin' => false,
				'controller_admin' => false,
			),
		),
		'album' => array(
			'name' => 'album',
			'camelCase' => 'Album',
			'camelCasePlural' => 'Albums',
			'camelCaseWSpace' => 'Album',
			'camelCasePluralWSpace' => 'Albums',
			'fields' => array(
				'album_id' => array('name' => 'album_id', 'type' => 'uint', 'autoIncrement' => true),
				'album_user_id' => array('name' => 'album_user_id', 'type' => 'uint', 'required' => true),
				'album_name' => array('name' => 'album_name', 'type' => 'string', 'length' => 100, 'required' => true),
				'album_description' => array('name' => 'album_description', 'type' => 'string'),
				'album_position' => array('name' => 'album_position', 'type' => 'uint', 'required' => true),
				'create_date' => array('name' => 'create_date', 'type' => 'uint', 'required' => true),
				'update_date' => array('name' => 'update_date', 'type' => 'uint', 'required' => true),
				'album_publish_date' => array('name' => 'album_publish_date', 'type' => 'uint', 'required' => true, 'default' => 0),
				'photo_count' => array('name' => 'photo_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'album_view_count' => array('name' => 'album_view_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'album_comment_count' => array('name' => 'album_comment_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'album_like_count' => array('name' => 'album_like_count', 'type' => 'uint', 'required' => true, 'default' => 0),
				'album_like_users' => array('name' => 'album_like_users', 'type' => 'serialized'),
				'location_id' => array('name' => 'location_id', 'type' => 'uint', 'required' => true, 'default' => 0),
				'cover_photo_id' => array('name' => 'cover_photo_id', 'type' => 'uint', 'required' => true, 'default' => 0),
			),
			'phrases' => array(),
			'id_field' => 'album_id',
			'title_field' => 'album_name',
			'primaryKey' => array('album_id'),
			'indeces' => array(
				'album_user_id' => array('name' => 'album_user_id', 'fields' => array('album_user_id'), 'type' => 'NORMAL'),
				'album_publish_date' => array('name' => 'album_publish_date', 'fields' => array('album_publish_date'), 'type' => 'NORMAL'),
				'location_id' => array('name' => 'location_id', 'fields' => array('location_id'), 'type' => 'NORMAL'),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_Album', 'hash' => '7fbd3838b21a8c80e91069ed1e9709b4'),
				'model' => array('className' => 'bdPhotos_Model_Album', 'hash' => 'd4f9623bd3fd3f381f34999d33b909cf'),
				'route_prefix_admin' => false,
				'controller_admin' => false,
			),
		),
		'photo_comment' => array(
			'name' => 'photo_comment',
			'camelCase' => 'PhotoComment',
			'camelCasePlural' => 'PhotoComments',
			'camelCaseWSpace' => 'Photo Comment',
			'camelCasePluralWSpace' => 'Photo Comments',
			'fields' => array(
				'photo_comment_id' => array('name' => 'photo_comment_id', 'type' => 'uint', 'autoIncrement' => true),
				'photo_id' => array('name' => 'photo_id', 'type' => 'uint', 'required' => true),
				'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true),
				'comment_date' => array('name' => 'comment_date', 'type' => 'uint', 'required' => true),
				'message' => array('name' => 'message', 'type' => 'string'),
				'ip_id' => array('name' => 'ip_id', 'type' => 'uint', 'required' => true),
			),
			'phrases' => array(),
			'id_field' => 'photo_comment_id',
			'title_field' => 'message',
			'primaryKey' => array('photo_comment_id'),
			'indeces' => array(
				'photo_id' => array('name' => 'photo_id', 'fields' => array('photo_id'), 'type' => 'NORMAL'),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_PhotoComment', 'hash' => '055983ad0a957e40ee235893850897ce'),
				'model' => array('className' => 'bdPhotos_Model_PhotoComment', 'hash' => 'ec4a70a6ae81ec35909217965d103257'),
				'route_prefix_admin' => false,
				'controller_admin' => false,
			),
		),
		'album_comment' => array(
			'name' => 'album_comment',
			'camelCase' => 'AlbumComment',
			'camelCasePlural' => 'AlbumComments',
			'camelCaseWSpace' => 'Album Comment',
			'camelCasePluralWSpace' => 'Album Comments',
			'fields' => array(
				'album_comment_id' => array('name' => 'album_comment_id', 'type' => 'uint', 'autoIncrement' => true),
				'album_id' => array('name' => 'album_id', 'type' => 'uint', 'required' => true),
				'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true),
				'comment_date' => array('name' => 'comment_date', 'type' => 'uint', 'required' => true),
				'message' => array('name' => 'message', 'type' => 'string'),
				'ip_id' => array('name' => 'ip_id', 'type' => 'uint', 'required' => true),
			),
			'phrases' => array(),
			'id_field' => 'album_comment_id',
			'title_field' => 'message',
			'primaryKey' => array('album_comment_id'),
			'indeces' => array(
				'album_id' => array('name' => 'album_id', 'fields' => array('album_id'), 'type' => 'NORMAL'),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_AlbumComment', 'hash' => '916f44fd1374c5bfb6b87324425d0857'),
				'model' => array('className' => 'bdPhotos_Model_AlbumComment', 'hash' => 'acd9af51f76f2d52942569ad1d7f92fb'),
				'route_prefix_admin' => false,
				'controller_admin' => false,
			),
		),
		'device' => array(
			'name' => 'device',
			'camelCase' => 'Device',
			'camelCasePlural' => 'Devices',
			'camelCaseWSpace' => 'Device',
			'camelCasePluralWSpace' => 'Devices',
			'fields' => array(
				'device_id' => array('name' => 'device_id', 'type' => 'uint', 'autoIncrement' => true),
				'device_name' => array('name' => 'device_name', 'type' => 'string', 'length' => 255, 'required' => true),
				'device_info' => array('name' => 'device_info', 'type' => 'serialized'),
			),
			'phrases' => array(),
			'id_field' => 'device_id',
			'title_field' => 'device_name',
			'primaryKey' => array('device_id'),
			'indeces' => array(),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_Device', 'hash' => 'c662c9542ce832c0d4f3ba185b1da22e'),
				'model' => array('className' => 'bdPhotos_Model_Device', 'hash' => 'cd8201ef56012a201e35bb54940cca61'),
				'route_prefix_admin' => false,
				'controller_admin' => false,
			),
		),
		'device_code' => array(
			'name' => 'device_code',
			'camelCase' => 'DeviceCode',
			'camelCasePlural' => 'DeviceCodes',
			'camelCaseWSpace' => 'Device Code',
			'camelCasePluralWSpace' => 'Device Codes',
			'fields' => array(
				'device_code_id' => array('name' => 'device_code_id', 'type' => 'uint', 'autoIncrement' => true),
				'manufacture' => array('name' => 'manufacture', 'type' => 'string', 'length' => 100, 'required' => true),
				'code' => array('name' => 'code', 'type' => 'string', 'length' => 100, 'required' => true),
				'device_id' => array('name' => 'device_id', 'type' => 'uint', 'required' => true),
			),
			'phrases' => array(),
			'id_field' => 'device_code_id',
			'title_field' => 'device_id',
			'primaryKey' => array('device_code_id'),
			'indeces' => array(
				'manufacture_code' => array(
					'name' => 'manufacture_code',
					'fields' => array('manufacture', 'code'),
					'type' => 'UNIQUE',
				),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_DeviceCode', 'hash' => 'b708c970688b9ee12c8fc94ec46be6d0'),
				'model' => array('className' => 'bdPhotos_Model_DeviceCode', 'hash' => '73b968b715df747b9253238970704bf3'),
				'route_prefix_admin' => array('className' => 'bdPhotos_Route_PrefixAdmin_DeviceCode', 'hash' => '2f02e6398564509dc682ec963cd6a727'),
				'controller_admin' => array('className' => 'bdPhotos_ControllerAdmin_DeviceCode', 'hash' => 'a0de9498eed5c506ecaff44798ca4763'),
			),
		),
		'location' => array(
			'name' => 'location',
			'camelCase' => 'Location',
			'camelCasePlural' => 'Locations',
			'camelCaseWSpace' => 'Location',
			'camelCasePluralWSpace' => 'Locations',
			'fields' => array(
				'location_id' => array('name' => 'location_id', 'type' => 'uint', 'autoIncrement' => true),
				'location_name' => array('name' => 'location_name', 'type' => 'string', 'length' => 255, 'required' => true),
				'ne_lat' => array('name' => 'ne_lat', 'type' => 'int', 'required' => true),
				'ne_lng' => array('name' => 'ne_lng', 'type' => 'int', 'required' => true),
				'sw_lat' => array('name' => 'sw_lat', 'type' => 'int', 'required' => true),
				'sw_lng' => array('name' => 'sw_lng', 'type' => 'int', 'required' => true),
				'location_info' => array('name' => 'location_info', 'type' => 'serialized'),
			),
			'phrases' => array(),
			'id_field' => 'location_id',
			'title_field' => 'location_name',
			'primaryKey' => array('location_id'),
			'indeces' => array(
				'ne_lat_ne_lng_sw_lat_sw_lng' => array(
					'name' => 'ne_lat_ne_lng_sw_lat_sw_lng',
					'fields' => array('ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'),
					'type' => 'NORMAL',
				),
			),
			'files' => array(
				'data_writer' => array('className' => 'bdPhotos_DataWriter_Location', 'hash' => '121e69aceffb43ce094bdd614056a584'),
				'model' => array('className' => 'bdPhotos_Model_Location', 'hash' => '321db29dda35fb0560f0e23248118e7a'),
				'route_prefix_admin' => array('className' => 'bdPhotos_Route_PrefixAdmin_Location', 'hash' => 'f84e267105835decceda1c6eea6513a6'),
				'controller_admin' => array('className' => 'bdPhotos_ControllerAdmin_Location', 'hash' => 'e38aef257822e4ca24f0eed5d74b2015'),
			),
		),
	);
	protected $_dataPatches = array();
	protected $_exportPath = '/Users/sondh/XenForo/bdPhotos';
	protected $_exportIncludes = array();

	/**
	 * Return false to trigger the upgrade!
	 * common use methods:
	 * 	public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array())
	 *	public function addDataPatch($table, array $field)
	 *	public function setExportPath($path)
	**/
	protected function _upgrade()
	{
		return true; // remove this line to trigger update

		/*
		$this->addDataClass(
				'name_here',
				array( // fields
						'field_here' => array(
								'type' => 'type_here',
								// 'length' => 'length_here',
								// 'required' => true,
								// 'allowedValues' => array('value_1', 'value_2'),
								// 'default' => 0,
								// 'autoIncrement' => true,
						),
						// other fields go here
				),
				'primary_key_field_here',
				array( // indeces
						array(
								'fields' => array('field_1', 'field_2'),
								'type' => 'NORMAL', // UNIQUE or FULLTEXT
						),
				),
		);
		*/
	}
}