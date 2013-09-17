<?php

class bdPhotos_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerPublic_Member',
			'XenForo_Image_Gd',
			'XenForo_Image_Imagemagick_Pecl',
			'XenForo_ViewPublic_Attachment_DoUpload',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'bdPhotos_' . $class;
		}
	}

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		// TODO: put this into install code maybe?
		$contentTypes = XenForo_Application::get('contentTypes');
		$contentTypes['bdphotos_album'] = array('attachment_handler_class' => 'bdPhotos_AttachmentHandler_Album');
		$contentTypes['bdphotos_photo'] = array(
			'alert_handler_class' => 'bdPhotos_AlertHandler_Photo',
			'like_handler_class' => 'bdPhotos_LikeHandler_Photo',
		);
		XenForo_Application::set('contentTypes', $contentTypes);

		XenForo_Template_Helper_Core::$helperCallbacks['bdphotos_formatcssproperty'] = array(
			'bdPhotos_Helper_Template',
			'formatCssProperty'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['bdphotos_formatlocationname'] = array(
			'bdPhotos_Helper_Template',
			'formatLocationName'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['bdphotos_getstaticmapforlocation'] = array(
			'bdPhotos_Helper_Template',
			'getStaticMapForLocation'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['bdphotos_snippetordefault'] = array(
			'bdPhotos_Helper_Template',
			'snippetOrDefault'
		);

		XenForo_Template_Helper_Core::$helperCallbacks['bdphotos_getoption'] = array(
			'bdPhotos_Option',
			'get'
		);
	}

	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'attachment_editor_attachment':
				if (!empty($params['attachment']) AND !empty($params['attachment']['_bdPhotos_content_type']))
				{
					if ($params['attachment']['_bdPhotos_content_type'] === 'bdphotos_album')
					{
						$templateName = 'bdphotos_attachment_editor_attachment';
					}
				}
				break;
			case 'PAGE_CONTAINER':
				if ($template instanceof XenForo_Template_Public)
				{
					if (!!bdPhotos_Option::get('navTabPosition'))
					{
						$template->preloadTemplate('bdphotos_navigation_tab_links');
					}
				}
				break;
		}
	}

	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		$id = bdPhotos_Option::get('navTabId');
		$position = bdPhotos_Option::get('navTabPosition');

		if (empty($position))
		{
			return;
		}

		$canView = XenForo_Model::create('bdPhotos_Model_Uploader')->canView();

		if ($canView)
		{
			$extraTabs[$id] = array(
				'title' => new XenForo_Phrase('bdphotos_navigation_tab_title'),
				'href' => XenForo_Link::buildPublicLink('photos'),
				'position' => $position,
				'linksTemplate' => 'bdphotos_navigation_tab_links',
				'selected' => ($id == $selectedTabId),
			);
		}
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdPhotos_FileSums::getHashes();
	}

}
