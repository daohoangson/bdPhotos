! function($, window, document, _undefined)
{
	if (XenForo.CommentPoster)
	{
		var parentSubmitSuccess = XenForo.CommentPoster.prototype.submitSuccess;
		XenForo.CommentPoster.prototype.submitSuccess = function(ajaxData)
		{
			parentSubmitSuccess.call(this, ajaxData);

			if (ajaxData.comment_insertAfter)
			{
				$(ajaxData.comment_insertAfter).xfInsert('insertAfter', this.$commentArea);
			}
		};
	}

}(jQuery, this, document);
