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
                ,`device_photo_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
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
                ,`location_album_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
                ,`location_photo_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
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

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (empty($existed)) {
                $db->query($patch['alterTableAddColumnQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (!empty($existed)) {
                $db->query($patch['alterTableDropColumnQuery']);
            }
        }

        foreach (self::$_tables as $table) {
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

        if (empty($existingAddOn)) {
            $effectiveVersionId = 0;
        } else {
            $effectiveVersionId = $existingAddOn['version_id'];
        }

        if ($effectiveVersionId < 1) {
            self::_installDemo(XenForo_Visitor::getInstance()->toArray());
        }

        self::_permissionsSetup($effectiveVersionId);
    }

    public static function uninstallCustomized()
    {
        $db = XenForo_Application::getDb();

        $albumIds = $db->fetchCol('SELECT DISTINCT content_id FROM `xf_attachment` WHERE content_type = "bdphotos_album"');

        /** @var XenForo_Model_Attachment $attachmentModel */
        $attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');
        $attachmentModel->deleteAttachmentsFromContentIds('bdphotos_album', $albumIds);

        $db->query('DROP TABLE IF EXISTS `xf_bdphotos_album_view`');
        $db->query('DROP TABLE IF EXISTS `xf_bdphotos_photo_view`');

        // delete content type related records
        $contentTypes = array(
            'bdphotos_album',
            'bdphotos_photo'
        );
        $contentTypesQuoted = $db->quote($contentTypes);
        $contentTypeTables = array(
            'xf_liked_content',
            'xf_news_feed',
            'xf_user_alert',
        );
        foreach ($contentTypeTables AS $table) {
            $db->delete($table, 'content_type IN (' . $contentTypesQuoted . ')');
        }

        // delete permission entry
        $permissions = self::_permissionsGet();
        foreach ($permissions as $permission) {
            $db->query("
				DELETE FROM `xf_permission_entry`
				WHERE permission_group_id = ? 
					AND permission_id = ?
			", array(
                $permission['permission_group_id'],
                $permission['permission_id']
            ));
        }

        // delete admin permission entry
        $db->delete('xf_admin_permission_entry', "admin_permission_id = 'bdPhotos_device'");
        $db->delete('xf_admin_permission_entry', "admin_permission_id = 'bdPhotos_location'");
    }

    protected static function _installDemo(array $user)
    {
        $demoPhotoPaths = glob(sprintf('%s/_demo/*.jpg', dirname(__FILE__)));

        if (empty($demoPhotoPaths)) {
            return;
        }

        /* @var $attachmentModel XenForo_Model_Attachment */
        $attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');

        $attachmentHash = md5(uniqid('', true));

        $deviceDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
        $deviceDw->set('device_name', 'Demo Device');
        $deviceDw->save();
        $device = $deviceDw->getMergedData();

        $locationDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
        $locationDw->set('location_name', 'Demo Location');
        $locationDw->set('ne_lat', 37421972);
        $locationDw->set('ne_lng', -122084103);
        $locationDw->set('sw_lat', 37421972);
        $locationDw->set('sw_lng', -122084103);
        $locationDw->save();
        $location = $locationDw->getMergedData();

        $albumDw = XenForo_DataWriter::create('bdPhotos_DataWriter_Album');
        $albumDw->set('album_user_id', $user['user_id']);
        $albumDw->set('album_username', $user['username']);
        $albumDw->set('album_name', 'Demo');
        $albumDw->set('album_description', 'This is a demo album.');
        $albumDw->set('album_publish_date', XenForo_Application::$time);

        $photoInput = array(
            'attachment_hash' => $attachmentHash,

            'photo_caption' => array(),
            'photo_position' => array(),
            'device_id' => array(),
            'location_id' => array(),
        );

        foreach ($demoPhotoPaths as $path) {
            $caption = file_get_contents(preg_replace('/\.jpg$/', '.txt', $path));
            if (empty($caption)) {
                continue;
            }

            $tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');

            if (!@copy($path, $tempFile)) {
                continue;
            }

            $upload = new XenForo_Upload(basename($path), $tempFile);
            $dataId = $attachmentModel->insertUploadedAttachmentData($upload, $user['user_id']);
            $attachmentId = $attachmentModel->insertTemporaryAttachment($dataId, $attachmentHash);

            $photoInput['photo_caption'][$attachmentId] = $caption;
            $photoInput['photo_position'][$attachmentId] = count($photoInput['photo_position']) + 1;
            $photoInput['device_id'][$attachmentId] = $device['device_id'];
            $photoInput['location_id'][$attachmentId] = $location['location_id'];
        }

        $albumDw->setExtraData(bdPhotos_DataWriter_Album::EXTRA_DATA_PHOTO_INPUT, $photoInput);

        $albumDw->save();
    }

    protected static function _permissionsGet()
    {
        return array(
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_view',
                'copy_from_permission_id' => 'view',
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_upload',
                'copy_from_permission_id' => array(
                    'forum',
                    'uploadAttachment'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_albumComment',
                'copy_from_permission_id' => array(
                    'forum',
                    'postReply'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_albumLike',
                'copy_from_permission_id' => array(
                    'forum',
                    'like'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_photoComment',
                'copy_from_permission_id' => array(
                    'forum',
                    'postReply'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_photoLike',
                'copy_from_permission_id' => array(
                    'forum',
                    'like'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_downloadFull',
                'copy_from_permission_id' => array(
                    'forum',
                    'uploadAttachment'
                ),
                'since_version_id' => 1,
            ),
            // moderator permissions
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_viewAll',
                'copy_from_permission_id' => array(
                    'forum',
                    'manageAnyThread'
                ),
                'since_version_id' => 1,
            ),
            array(
                'permission_group_id' => 'general',
                'permission_id' => 'bdPhotos_editAll',
                'copy_from_permission_id' => array(
                    'forum',
                    'manageAnyThread'
                ),
                'since_version_id' => 1,
            ),
        );
    }

    protected static function _permissionsSetup($effectiveVersionId)
    {
        $db = XenForo_Application::getDb();
        $permissions = self::_permissionsGet();

        foreach ($permissions as $permission) {
            if ($effectiveVersionId < $permission['since_version_id']) {
                if (is_array($permission['copy_from_permission_id'])) {
                    list($copyFromPermissionGroupId, $copyFromPermissionId) = $permission['copy_from_permission_id'];
                } else {
                    $copyFromPermissionGroupId = $permission['permission_group_id'];
                    $copyFromPermissionId = $permission['copy_from_permission_id'];
                }

                $db->query("
					INSERT IGNORE INTO xf_permission_entry
						(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
					SELECT user_group_id, user_id,
					    '{$permission['permission_group_id']}',
					    '{$permission['permission_id']}',
					    permission_value, permission_value_int
					FROM xf_permission_entry
					WHERE permission_group_id = ? AND permission_id = ?
				", array(
                    $copyFromPermissionGroupId,
                    $copyFromPermissionId
                ));
            }
        }
    }

}
