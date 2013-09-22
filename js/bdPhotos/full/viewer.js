!
function($, window, document, _undefined)
{
	var $bdPhotos_NavigationLink_firstState = null;

	$(window).bind('popstate', function(e)
	{
		var state = e.originalEvent.state;

		if (!state || !state._owner || state._owner != 'bdPhotos_NavigationLink_updateHtml')
		{
			state = $bdPhotos_NavigationLink_firstState;
		}

		if (state && state._owner && state._owner == 'bdPhotos_NavigationLink_updateHtml')
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
					'_owner': 'bdPhotos_NavigationLink_updateHtml',
					'title': document.title,
					'href': window.location.href,
					'photoHtml': oldPhotoHtml,
					'sidebarHtml': oldSidebarHtml
				};
			}

			window.history.pushState(
			{
				'_owner': 'bdPhotos_NavigationLink_updateHtml',
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
			// abort if the event has a modifier key
			if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey)
			{
				return true;
			}

			// abort if the event is a middle or right-button click
			if (e.which > 1)
			{
				return true;
			}

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

	XenForo.bdPhotos_OverlayTrigger = function($trigger)
	{
		this.__construct($trigger);
	};
	XenForo.bdPhotos_OverlayTrigger.prototype =
	{
		__construct: function($trigger)
		{
			this.$trigger = $trigger.click($.context(this, 'show'));
			this.options =
			{
				className: 'primaryContent bdPhotos_Overlay',
				closeOnResize: true,
				mask:
				{
					color: 'black'
				}
			};

			this.$trigger.bind(
			{
				onLoad: $.context(this, 'overlayLoad'),
				onClose: $.context(this, 'overlayClose'),
			});

			this.historyCount = window.history.length;
			this.documentTitle = document.title;
		},

		overlayCallback: function()
		{
			var api = this.$trigger.data('overlay');
			var $overlay = api.getOverlay();

			var $photoWrapperWrapper = $overlay.find('.bdPhotos_PhotoWrapperWrapper');
			var $photo = $overlay.find('.bdPhotos_PhotoWrapper img');

			var windowWidth = $(window).width();
			var photoWidth = Math.floor(windowWidth / 5 * 3);
			var photoHeight = Math.floor($(window).height() / 5 * 4);

			var css = '.bdPhotos_Overlay .bdPhotos_Photo .bdPhotos_PhotoWrapperWrapper table { width: ' + photoWidth + 'px; }';
			css += '.bdPhotos_Overlay .bdPhotos_PhotoWrapper { height: ' + (photoHeight + 5) + 'px; }';
			css += '.bdPhotos_Overlay .bdPhotos_PhotoWrapper img { max-width: ' + photoWidth + 'px; max-height: ' + photoHeight + 'px; }';
			css += '.bdPhotos_Overlay .bdPhotos_PhotoComments { margin-left: ' + (photoWidth + 5) + 'px; }';

			var $style = $('#bdPhotos_OverlayTrigger_Css');
			if ($style.length > 0)
			{
				$style.empty().remove();
			}
			$('<style id="bdPhotos_OverlayTrigger_Css">' + css + '</style>').appendTo('head');
		},

		overlayClose: function(e)
		{
			if (window.history.pushState)
			{
				var count = window.history.length;

				window.history.go(this.historyCount - count);
				document.title = this.documentTitle;
			}

			$(window).unbind('popstate', $.context(this, 'windowPopState'));
		},

		overlayLoad: function(e)
		{
			if (window.history.pushState)
			{
				$bdPhotos_NavigationLink_firstState = null;

				window.history.pushState(
				{
					'_owner': 'bdPhotos_OverlayTrigger',
				}, '', this.$trigger.attr('href'));

				document.title = this.$trigger.data('overlay').getConf().title;
			}

			$(window).bind('popstate', $.context(this, 'windowPopState'));
		},

		windowPopState: function(e)
		{
			var state = e.originalEvent.state;

			if (!state)
			{
				this.$trigger.data('overlay').close();
			}
		},

		show: function(e)
		{
			if (this.$trigger.hasClass('NoOverlay'))
			{
				return true;
			}

			// abort if the event has a modifier key
			if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey)
			{
				return true;
			}

			// abort if the event is a middle or right-button click
			if (e.which > 1)
			{
				return true;
			}

			// abort if the window is too small
			if ($(window).width() < 500)
			{
				return true;
			}

			e.preventDefault();

			options = $.extend(this.options, this.$trigger.data('overlayoptions'));

			this.OverlayLoader = new XenForo.OverlayLoader(this.$trigger, false, options);
			this.OverlayLoader.load($.context(this, 'overlayCallback'));

			return true;

		}
	};

	// *********************************************************************

	XenForo.register('a.bdPhotos_NavigationLink', 'XenForo.bdPhotos_NavigationLink');
	XenForo.register('a.bdPhotos_OverlayTrigger', 'XenForo.bdPhotos_OverlayTrigger');

}(jQuery, this, document);
