<?php

class bdPhotos_ControllerAdmin_Device extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 100;

		$conditions = array();
		$fetchOptions = array(
			'page' => $page,
			'perPage' => $perPage,
		);

		$filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
		if ($filter && isset($filter['value']))
		{
			$conditions['device_name_like'] = array(
				$filter['value'],
				empty($filter['prefix']) ? 'lr' : 'r'
			);
			$filterView = true;
		}
		else
		{
			$filterView = false;
		}

		$deviceModel = $this->_getDeviceModel();

		$devices = $deviceModel->getDevices($conditions, $fetchOptions);
		$totalDevices = $deviceModel->countDevices($conditions, $fetchOptions);

		$viewParams = array(
			'devices' => $devices,

			'page' => $page,
			'perPage' => $perPage,
			'totalDevices' => $totalDevices,

			'filterView' => $filterView,
			'filterMore' => ($filterView && $totalDevices > $perPage)
		);

		return $this->responseView('bdPhotos_ViewAdmin_Device_List', 'bdphotos_device_list', $viewParams);
	}

	public function actionAdd()
	{
		$viewParams = array('device' => array());

		return $this->responseView('bdPhotos_ViewAdmin_Device_Edit', 'bdphotos_device_edit', $viewParams);
	}

	public function actionEdit()
	{
		$id = $this->_input->filterSingle('device_id', XenForo_Input::UINT);
		$device = $this->_getDeviceOrError($id);

		$viewParams = array('device' => $device);

		return $this->responseView('bdPhotos_ViewAdmin_Device_Edit', 'bdphotos_device_edit', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$id = $this->_input->filterSingle('device_id', XenForo_Input::UINT);
		$dw = $this->_getDeviceDataWriter();
		if ($id)
		{
			$dw->setExistingData($id);
		}

		// get regular fields from input data
		$dwInput = $this->_input->filter(array('device_name' => 'string'));
		$dw->bulkSet($dwInput);

		$dw->save();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('photo-devices'));
	}

	public function actionDelete()
	{
		$id = $this->_input->filterSingle('device_id', XenForo_Input::UINT);
		$device = $this->_getDeviceOrError($id);

		if ($this->isConfirmedPost())
		{
			$dw = $this->_getDeviceDataWriter();
			$dw->setExistingData($id);
			$dw->delete();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('photo-devices'));
		}
		else
		{
			$viewParams = array('device' => $device);

			return $this->responseView('bdPhotos_ViewAdmin_Device_Delete', 'bdphotos_device_delete', $viewParams);
		}
	}

	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('bdPhotos_device');
	}

	protected function _getDeviceOrError($id, array $fetchOptions = array())
	{
		$device = $this->_getDeviceModel()->getDeviceById($id, $fetchOptions);

		if (empty($device))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_device_not_found'), 404));
		}

		return $device;
	}

	/**
	 * @return bdPhotos_Model_Device
	 */
	protected function _getDeviceModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Device');
	}

	/**
	 * @return bdPhotos_DataWriter_Device
	 */
	protected function _getDeviceDataWriter()
	{
		return XenForo_DataWriter::create('bdPhotos_DataWriter_Device');
	}

}
