<?php

class bdPhotos_ControllerAdmin_DeviceCode extends XenForo_ControllerAdmin_Abstract
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	public function actionSave()
	{
		$this->_assertPostOnly();

		$id = $this->_input->filterSingle('device_code_id', XenForo_Input::UINT);
		$dw = $this->_getDeviceCodeDataWriter();
		if ($id)
		{
			$dw->setExistingData($id);
		}

		// get regular fields from input data
		$dwInput = $this->_input->filter(array('manufacture' => 'string', 'code' => 'string', 'device_id' => 'uint'));
		$dw->bulkSet($dwInput);

		$this->_prepareDwBeforeSaving($dw);

		$dw->save();

		return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('photo-device-codes')
		);
	}

	public function actionIndex()
	{
		$conditions = array();
		$fetchOptions = array();

		$deviceCodeModel = $this->_getDeviceCodeModel();
		$deviceCodes = $deviceCodeModel->getDeviceCodes($conditions, $fetchOptions);

		$viewParams = array(
				'deviceCodes' => $deviceCodes,
		);

		return $this->responseView('bdPhotos_ViewAdmin_DeviceCode_List', 'bdphotos_device_code_list', $viewParams);
	}

	public function actionAdd()
	{
		$viewParams = array(
				'deviceCode' => array(),
		);

		return $this->responseView('bdPhotos_ViewAdmin_DeviceCode_Edit', 'bdphotos_device_code_edit', $viewParams);
	}

	public function actionEdit()
	{
		$id = $this->_input->filterSingle('device_code_id', XenForo_Input::UINT);
		$deviceCode = $this->_getDeviceCodeOrError($id);

		$viewParams = array(
				'deviceCode' => $deviceCode,
		);

		return $this->responseView('bdPhotos_ViewAdmin_DeviceCode_Edit', 'bdphotos_device_code_edit', $viewParams);
	}

	public function actionDelete()
	{
		$id = $this->_input->filterSingle('device_code_id', XenForo_Input::UINT);
		$deviceCode = $this->_getDeviceCodeOrError($id);

		if ($this->isConfirmedPost())
		{
			$dw = $this->_getDeviceCodeDataWriter();
			$dw->setExistingData($id);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildAdminLink('photo-device-codes')
			);
		} else {
			$viewParams = array(
					'deviceCode' => $deviceCode,
			);

			return $this->responseView('bdPhotos_ViewAdmin_DeviceCode_Delete', 'bdphotos_device_code_delete', $viewParams);
		}
	}

	protected function _getDeviceCodeOrError($id, array $fetchOptions = array())
	{
		$deviceCode = $this->_getDeviceCodeModel()->getDeviceCodeById($id, $fetchOptions);

		if (empty($deviceCode))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_device_code_not_found'), 404));
		}

		return $deviceCode;
	}

	protected function _getDeviceCodeModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_DeviceCode');
	}

	protected function  _getDeviceCodeDataWriter()
	{
		return XenForo_DataWriter::create('bdPhotos_DataWriter_DeviceCode');
	}

/* End auto-generated lines of code. Feel free to make changes below */

	protected function _prepareDwBeforeSaving(bdPhotos_DataWriter_DeviceCode $dw)
	{
		// customized code goes here
	}

}