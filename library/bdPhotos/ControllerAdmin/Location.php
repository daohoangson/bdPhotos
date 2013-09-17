<?php

class bdPhotos_ControllerAdmin_Location extends XenForo_ControllerAdmin_Abstract
{

/* Start auto-generated lines of code. Change made will be overwriten... */

	public function actionSave()
	{
		$this->_assertPostOnly();

		$id = $this->_input->filterSingle('location_id', XenForo_Input::UINT);
		$dw = $this->_getLocationDataWriter();
		if ($id)
		{
			$dw->setExistingData($id);
		}

		// get regular fields from input data
		$dwInput = $this->_input->filter(array('location_name' => 'string', 'location_lat' => 'int', 'location_lng' => 'int'));
		$dw->bulkSet($dwInput);

		$this->_prepareDwBeforeSaving($dw);

		$dw->save();

		return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('photo-locations')
		);
	}

	public function actionIndex()
	{
		$conditions = array();
		$fetchOptions = array();

		$locationModel = $this->_getLocationModel();
		$locations = $locationModel->getLocations($conditions, $fetchOptions);

		$viewParams = array(
				'locations' => $locations,
		);

		return $this->responseView('bdPhotos_ViewAdmin_Location_List', 'bdphotos_location_list', $viewParams);
	}

	public function actionAdd()
	{
		$viewParams = array(
				'location' => array(),
		);

		return $this->responseView('bdPhotos_ViewAdmin_Location_Edit', 'bdphotos_location_edit', $viewParams);
	}

	public function actionEdit()
	{
		$id = $this->_input->filterSingle('location_id', XenForo_Input::UINT);
		$location = $this->_getLocationOrError($id);

		$viewParams = array(
				'location' => $location,
		);

		return $this->responseView('bdPhotos_ViewAdmin_Location_Edit', 'bdphotos_location_edit', $viewParams);
	}

	public function actionDelete()
	{
		$id = $this->_input->filterSingle('location_id', XenForo_Input::UINT);
		$location = $this->_getLocationOrError($id);

		if ($this->isConfirmedPost())
		{
			$dw = $this->_getLocationDataWriter();
			$dw->setExistingData($id);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildAdminLink('photo-locations')
			);
		} else {
			$viewParams = array(
					'location' => $location,
			);

			return $this->responseView('bdPhotos_ViewAdmin_Location_Delete', 'bdphotos_location_delete', $viewParams);
		}
	}

	protected function _getLocationOrError($id, array $fetchOptions = array())
	{
		$location = $this->_getLocationModel()->getLocationById($id, $fetchOptions);

		if (empty($location))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdphotos_location_not_found'), 404));
		}

		return $location;
	}

	protected function _getLocationModel()
	{
		return $this->getModelFromCache('bdPhotos_Model_Location');
	}

	protected function  _getLocationDataWriter()
	{
		return XenForo_DataWriter::create('bdPhotos_DataWriter_Location');
	}

/* End auto-generated lines of code. Feel free to make changes below */

	protected function _prepareDwBeforeSaving(bdPhotos_DataWriter_Location $dw)
	{
		// customized code goes here
	}

}