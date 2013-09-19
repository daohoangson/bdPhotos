<?php

class bdPhotos_Installer
{
	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected static $_tables = array(
		'photo' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_photo` (
				`photo_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`user_id` INT(10) UNSIGNED NOT NULL
				,`username` VARCHAR(50) NOT NULL
				,`album_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_caption` TEXT
				,`photo_position` INT(10) UNSIGNED NOT NULL
				,`publish_date` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_view_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_comment_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_like_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_like_users` MEDIUMBLOB
				,`device_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`location_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`metadata` MEDIUMBLOB
				, PRIMARY KEY (`photo_id`)
				, INDEX `user_id_album_id_photo_position` (`user_id`,`album_id`,`photo_position`)
				, INDEX `publish_date` (`publish_date`)
				, INDEX `location_id` (`location_id`)
				, INDEX `device_id` (`device_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_photo`',
		),
		'album' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_album` (
				`album_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`album_user_id` INT(10) UNSIGNED NOT NULL
				,`album_username` VARCHAR(50) NOT NULL
				,`album_name` VARCHAR(100) NOT NULL
				,`album_description` TEXT
				,`album_position` INT(10) UNSIGNED NOT NULL
				,`create_date` INT(10) UNSIGNED NOT NULL
				,`update_date` INT(10) UNSIGNED NOT NULL
				,`album_publish_date` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`photo_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`album_view_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`album_comment_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`album_like_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`album_like_users` MEDIUMBLOB
				,`location_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				,`cover_photo_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
				, PRIMARY KEY (`album_id`)
				, INDEX `album_user_id` (`album_user_id`)
				, INDEX `album_publish_date` (`album_publish_date`)
				, INDEX `location_id` (`location_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_album`',
		),
		'photo_comment' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_photo_comment` (
				`photo_comment_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`photo_id` INT(10) UNSIGNED NOT NULL
				,`user_id` INT(10) UNSIGNED NOT NULL
				,`username` VARCHAR(50) NOT NULL
				,`comment_date` INT(10) UNSIGNED NOT NULL
				,`message` TEXT
				,`ip_id` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`photo_comment_id`)
				, INDEX `photo_id` (`photo_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_photo_comment`',
		),
		'album_comment' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_album_comment` (
				`album_comment_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`album_id` INT(10) UNSIGNED NOT NULL
				,`user_id` INT(10) UNSIGNED NOT NULL
				,`username` VARCHAR(50) NOT NULL
				,`comment_date` INT(10) UNSIGNED NOT NULL
				,`message` TEXT
				,`ip_id` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`album_comment_id`)
				, INDEX `album_id` (`album_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_album_comment`',
		),
		'device' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_device` (
				`device_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`device_name` VARCHAR(255) NOT NULL
				,`device_info` MEDIUMBLOB
				, PRIMARY KEY (`device_id`)
				
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_device`',
		),
		'device_code' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_device_code` (
				`device_code_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`manufacture` VARCHAR(100) NOT NULL
				,`code` VARCHAR(100) NOT NULL
				,`device_id` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`device_code_id`)
				,UNIQUE INDEX `manufacture_code` (`manufacture`,`code`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_device_code`',
		),
		'location' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdphotos_location` (
				`location_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`location_name` VARCHAR(255) NOT NULL
				,`ne_lat` INT(11) NOT NULL
				,`ne_lng` INT(11) NOT NULL
				,`sw_lat` INT(11) NOT NULL
				,`sw_lng` INT(11) NOT NULL
				,`location_info` MEDIUMBLOB
				, PRIMARY KEY (`location_id`)
				, INDEX `ne_lat_ne_lng_sw_lat_sw_lng` (`ne_lat`,`ne_lng`,`sw_lat`,`sw_lng`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdphotos_location`',
		),
	);
	protected static $_patches = array();

	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table)
		{
			$db->query($table['createQuery']);
		}

		foreach (self::$_patches as $patch)
		{
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted))
			{
				continue;
			}

			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (empty($existed))
			{
				$db->query($patch['alterTableAddColumnQuery']);
			}
		}
		
		self::installCustomized($existingAddOn, $addOnData);
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		foreach (self::$_patches as $patch)
		{
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted))
			{
				continue;
			}

			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (!empty($existed))
			{
				$db->query($patch['alterTableDropColumnQuery']);
			}
		}

		foreach (self::$_tables as $table)
		{
			$db->query($table['dropQuery']);
		}

		self::uninstallCustomized();
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	public static function installCustomized($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::getDb();

		$db->query('
			CREATE TABLE IF NOT EXISTS `xf_bdphotos_album_view` (
				`album_id` INT(10) UNSIGNED NOT NULL,
				KEY `album_id` (`album_id`)
			) ENGINE = MEMORY;
		');

		$db->query('
			CREATE TABLE IF NOT EXISTS `xf_bdphotos_photo_view` (
				`photo_id` INT(10) UNSIGNED NOT NULL,
				KEY `photo_id` (`photo_id`)
			) ENGINE = MEMORY;
		');
	}

	public static function uninstallCustomized()
	{
		$db = XenForo_Application::getDb();

		$albumIds = $db->fetchCol('SELECT DISTINCT content_id FROM xf_attachment WHERE content_type = "bdphotos_album"');
		XenForo_Model::create('XenForo_Model_Attachment')->deleteAttachmentsFromContentIds('bdphotos_album', $albumIds);

		$db->query('DROP TABLE IF EXISTS `xf_bdphotos_album_view`');
		$db->query('DROP TABLE IF EXISTS `xf_bdphotos_photo_view`');
	}

}
