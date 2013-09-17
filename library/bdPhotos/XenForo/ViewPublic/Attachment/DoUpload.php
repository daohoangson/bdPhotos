<?php

class bdPhotos_XenForo_ViewPublic_Attachment_DoUpload extends XFCP_bdPhotos_XenForo_ViewPublic_Attachment_DoUpload
{
	protected function _prepareAttachmentForJson(array $attachment)
	{
		if (!empty($this->_params['content_type']) AND $this->_params['content_type'] == 'bdphotos_album')
		{
			$attachment['_bdPhotos_content_type'] = $this->_params['content_type'];

			$attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');

			$filePath = $attachmentModel->getAttachmentDataFilePath($this->_params['attachment']);
			if (is_readable($filePath))
			{
				$attachment['metadataArray'] = bdPhotos_Helper_Metadata::readFromFile($filePath);

				if (!empty($attachment['metadataArray']['lat']) AND !empty($attachment['metadataArray']['lng']))
				{
					$location = $attachmentModel->getModelFromCache('bdPhotos_Model_Location')->getLocationNear($attachment['metadataArray']['lat'], $attachment['metadataArray']['lng']);
					if (!empty($location))
					{
						$attachment = array_merge($attachment, $location);
					}
				}

				if (!empty($attachment['metadataArray']['manufacture']) AND !empty($attachment['metadataArray']['code']))
				{
					$device = $attachmentModel->getModelFromCache('bdPhotos_Model_Device')->getDeviceByCode($attachment['metadataArray']['manufacture'], $attachment['metadataArray']['code']);
					if (!empty($device))
					{
						$attachment = array_merge($attachment, $device);
					}
				}
			}

			bdPhotos_ViewPublic_Helper_Photo::preparePhotoForDisplay($attachment);
		}

		return parent::_prepareAttachmentForJson($attachment);
	}

}
