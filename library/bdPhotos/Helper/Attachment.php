<?php

class bdPhotos_Helper_Attachment
{
	public static function getAttachmentDataFilePath(XenForo_Model_Attachment $attachmentModel, array $attachment)
	{
		if (is_callable(array(
			$attachmentModel,
			'bdAttachmentStore_useTempFile'
		)))
		{
			// supports [bd] Attachment Store
			$attachmentModel->bdAttachmentStore_useTempFile(true);
		}

		$filePath = $attachmentModel->getAttachmentDataFilePath($attachment);

		if (is_callable(array(
			$attachmentModel,
			'bdAttachmentStore_useTempFile'
		)))
		{
			// supports [bd] Attachment Store
			call_user_func(array($attachmentModel, 'bdAttachmentStore_useTempFile'), false);
		}

		return $filePath;
	}

	public static function getUsableFilePath(XenForo_Model_Attachment $attachmentModel, array $attachment, array $metadata)
	{
		$usableFilePath = self::_getUsableFilePath($attachment);

		if (!file_exists($usableFilePath))
		{
			$filePath = self::getAttachmentDataFilePath($attachmentModel, $attachment);

			return self::prepareUsableFilePath($filePath, $attachment, $metadata);
		}

		return $usableFilePath;
	}

	public static function prepareUsableFilePath($filePath, array $attachment, array $metadata)
	{
		$usableFilePath = self::_getUsableFilePath($attachment);
		if (file_exists($usableFilePath))
		{
			return $usableFilePath;
		}

		if (!file_exists($filePath))
		{
			return false;
		}

		if (empty($attachment['filename']))
		{
			return false;
		}
		$extension = XenForo_Helper_File::getFileExtension($attachment['filename']);

		$options = $metadata;
		if (!empty($metadata['exif']))
		{
			bdPhotos_Helper_Image::prepareOptionsFromExifData($options, $metadata['exif']);
		}

		$width = array(
			'width' => 1024,
			'height' => 1024,
			'crop' => false,
			'thumbnailFixedShorterSide' => true,
		);
		$height = 0;

		if (bdPhotos_Helper_Image::resizeAndCrop($filePath, $extension, $width, $height, $usableFilePath, $options))
		{
			return $usableFilePath;
		}

		return false;
	}

	protected static function _getUsableFilePath(array $attachment)
	{
		$divider = floor($attachment['attachment_id'] / 1000);

		return sprintf('%s/bdPhotos/%d/%d.data', XenForo_Helper_File::getInternalDataPath(), $divider, $attachment['attachment_id']);
	}

}
