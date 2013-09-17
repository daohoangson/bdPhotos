<?php

class bdPhotos_Route_PrefixAdmin_DeviceCode implements XenForo_Route_Interface
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		if (in_array($routePath, array('add', 'save')))
		{
			$action = $routePath;			
		}
		else
		{
			$action = $router->resolveActionWithIntegerParam($routePath, $request, 'device_code_id');
		}
		return $router->getRouteMatch('bdPhotos_ControllerAdmin_DeviceCode', $action, 'bdPhotos');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		if (is_array($data))
		{
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'device_code_id');
		}
		else
		{
			return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
		}
	}

/* End auto-generated lines of code. Feel free to make changes below */

}