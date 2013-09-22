!
function($, window, document, _undefined)
{
	var $bdPhotos_NavigationLink_firstState = null;

	$(window).bind('popstate', function(e)
	{
		var state = (e.originalEvent.state ? e.originalEvent.state : $bdPhotos_NavigationLink_firstState);

		if (state)
		{
			var $photo = $('.bdPhotos_Photo');

			XenForo.bdPhotos_NavigationLink_updateHtml($photo, state.href, state.title, state.photoHtml, state.sidebarHtml, false);
		}
	});

	XenForo.bdPhotos_NavigationLink_updateHtml = function($photo, href, title, photoHtml, sidebarHtml, pushState)
	{
		var $html = $('<div />').html(photoHtml);
		var $sidebar = $('<div />').html(sidebarHtml);

		var $navigation = $photo.find('.bdPhotos_Navigation');
		var $wrapper = $photo.find('.bdPhotos_PhotoWrapper');
		var $comments = $photo.find('.bdPhotos_PhotoComments');
		var $sidebarInfo = $('.sidebar .bdPhotos_sidebarPhotoInfo');

		var oldPhotoHtml = $photo.parent().html();
		var oldSidebarHtml = $('.sidebar').html();

		var $newPhoto = $html.find('.bdPhotos_Photo');
		var $newNavigation = $newPhoto.find('.bdPhotos_Navigation');
		var $newWrapper = $newPhoto.find('.bdPhotos_PhotoWrapper');
		var $newComents = $newPhoto.find('.bdPhotos_PhotoComments');
		var $newSidebarInfo = $sidebar.find('.bdPhotos_sidebarPhotoInfo');
		console.log($navigation, $newNavigation);
		$photo.attr('id', $newPhoto.attr('id'));

		$newNavigation.xfInsert('insertBefore', $navigation, 'show');
		$navigation.hide().xfRemove();

		$wrapper.html('');
		$newWrapper.children().xfInsert('appendTo', $wrapper, 'show');

		$newComents.xfInsert('insertBefore', $comments, 'show');
		$comments.hide().xfRemove();

		// intentionally let it fade down
		$newSidebarInfo.xfInsert('insertBefore', $sidebarInfo);
		$sidebarInfo.hide().xfRemove();

		if (pushState && window.history.pushState)
		{
			if ($bdPhotos_NavigationLink_firstState === null)
			{
				$bdPhotos_NavigationLink_firstState =
				{
					'title': document.title,
					'href': window.location.href,
					'photoHtml': oldPhotoHtml,
					'sidebarHtml': oldSidebarHtml
				};
			}

			window.history.pushState(
			{
				'title': title,
				'href': href,
				'photoHtml': photoHtml,
				'sidebarHtml': sidebarHtml
			}, title, href);

			var $title = $('<p />').html(title);
			document.title = $title.text();

		}
	};

	XenForo.bdPhotos_NavigationLink = function($link)
	{
		this.__construct($link);
	};
	XenForo.bdPhotos_NavigationLink.prototype =
	{
		__construct: function($link)
		{
			this.$link = $link;
			this.href = $link.attr('href');

			$link.click($.context(this, 'click'));
		},

		click: function(e)
		{
			if (this.xhr)
			{
				this.xhr.abort();
			}

			this.xhr = XenForo.ajax(this.href,
			{
				'_navigationLink': 1
			}, $.context(this, 'loadSuccess'));

			e.preventDefault();
		},

		loadSuccess: function(ajaxData, textStatus)
		{
			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			var $photo = this.$link.parents('.bdPhotos_Photo');
			var href = this.href;

			new XenForo.ExtLoader(ajaxData, function()
			{
				XenForo.bdPhotos_NavigationLink_updateHtml($photo, href, ajaxData.title, ajaxData.templateHtml, ajaxData.sidebarHtml, true);
			});
		}
	};

	// *********************************************************************

	XenForo.register('a.bdPhotos_NavigationLink', 'XenForo.bdPhotos_NavigationLink');

}(jQuery, this, document);
