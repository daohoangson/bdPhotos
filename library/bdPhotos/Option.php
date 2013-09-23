<?php

class bdPhotos_Option
{
	public static function get($key, $subKey = null)
	{
		$options = XenForo_Application::getOptions();

		switch ($key)
		{
			case 'navTabId':
				return 'bdPhotos';
			case 'navTabPosition':
				return 'middle';
			case 'devicesPerPage':
			case 'locationsPerPage':
				return 100;
			case 'albumsPerPage':
			case 'photosPerPage':
				return 20;

			// TODO
			case 'commentsPerPage':
				return 20;
		}

		return $options->get('bdPhotos_' . $key, $subKey);
	}

}
