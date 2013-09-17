<?php

class bdPhotos_Model_Uploader extends XenForo_Model
{
	public function canView(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_view');
	}
	
	public function canViewAll(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_viewAll');
	}
	
	public function canUpload(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id'] AND XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_upload'));
	}
	
	public function canEditAll(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id'] AND XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPhotos_editAll'));
	}
}
