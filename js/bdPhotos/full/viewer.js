!
function($, window, document, _undefined)
{
	var $bdPhotos_NavigationLink_firstState = null;
	var bdPhotos_Overlay_pushStateCount = 0;

	$(window).bind('popstate', function(e)
	{
		var state = e.originalEvent.state;

		if (!state || !state._owner || state._owner != 'bdPhotos_NavigationLink_updateHtml')
		{
			state = $bdPhotos_NavigationLink_firstState;
		}

		if (state && state._owner && state._owner == 'bdPhotos_NavigationLink_updateHtml')
		{
			var photoSelector = '.bdPhotos_Photo';

			if (bdPhotos_Overlay_pushStateCount > 0 && $('.bdPhotos_Overlay').length == 1)
			{
				console.info('Find photo in overlay');
				photoSelector = '.bdPhotos_Overlay .bdPhotos_Photo';

				bdPhotos_Overlay_pushStateCount = state.pushStateCount;
			}

			bdPhotos_NavigationLink_updateHtml(photoSelector, state.href, state.title, state.photoHtml, state.sidebarHtml, false);
		}
	});

	var bdPhotos_setupOverlay = function($photo)
	{
		var $bdPhotos_PhotoComments = $photo.find('.bdPhotos_PhotoComments');
		var $messageInfo = $bdPhotos_PhotoComments.find('.messageInfo');
		var $messageResponse = $messageInfo.find('.messageResponse');

		$messageResponse.css('height', '0px');
		var messageResponseMaxHeight = ($bdPhotos_PhotoComments.height() - $messageInfo.height());

		if (messageResponseMaxHeight <= 0)
		{
			window.setTimeout(function()
			{
				bdPhotos_setupOverlay($photo);
			}, 100);
			return;
		}

		$messageResponse.css('height', '');
		$messageResponse.css('max-height', messageResponseMaxHeight + 'px');
		$messageResponse.show();
	};

	var bdPhotos_NavigationLink_updateHtml = function(photoSelector, href, title, photoHtml, sidebarHtml, pushState)
	{
		var $photo = $(photoSelector);
		if ($photo.length != 1)
		{
			console.warn('Unable to find photo', photoSelector);
			return;
		}
		var $photoOverlay = $photo.parents('.bdPhotos_Overlay');
		var photoHasOverlay = $photoOverlay.length > 0;

		var $html = $('<div />').html(photoHtml);
		var $newPhoto = $html.find('.bdPhotos_Photo');
		if ($newPhoto.length != 1)
		{
			console.warn('Unable to find new photo');
			return;
		}
		if ($photo.attr('id') == $newPhoto.attr('id'))
		{
			// same photo, nothing to do here
			return;
		}

		var $navigation = $photo.find('.bdPhotos_Navigation');
		var $wrapper = $photo.find('.bdPhotos_PhotoWrapper');
		var $comments = $photo.find('.bdPhotos_PhotoComments');
		if ($navigation.length == 0 || $wrapper.length == 0 || $comments.length == 0)
		{
			console.warn('Unable to find navigation/wraper/comments from photo');
			return;
		}

		var $newNavigation = $newPhoto.find('.bdPhotos_Navigation');
		var $newWrapper = $newPhoto.find('.bdPhotos_PhotoWrapper');
		var $newComents = $newPhoto.find('.bdPhotos_PhotoComments');

		$photo.attr('id', $newPhoto.attr('id'));
		var $navigationParent = $navigation.parent();
		$navigation.empty().remove();
		$newNavigation.xfInsert('prependTo', $navigationParent, 'show');

		$wrapper.html('');
		$newWrapper.children().xfInsert('appendTo', $wrapper, 'show');

		$newComents.xfInsert('insertBefore', $comments, 'show');
		$comments.empty().remove();

		if (photoHasOverlay)
		{
			bdPhotos_setupOverlay($photo);
		}
		else
		{
			var $sidebar = $('.sidebar').html('');

			$newComents.find('.bdPhotos_pseudoSidebar > div').each(function() {
				var $sidebarBlock = $(this).xfInsert('appendTo', $sidebar, 'show');
			});
		}

		if (pushState && window.history.pushState)
		{
			if ($photo.parents('.bdPhotos_Overlay').length > 0)
			{
				bdPhotos_Overlay_pushStateCount++;
			}

			window.history.pushState(
			{
				'_owner': 'bdPhotos_NavigationLink_updateHtml',
				'title': title,
				'href': href,
				'photoHtml': photoHtml,
				'sidebarHtml': sidebarHtml,
				'pushStateCount': bdPhotos_Overlay_pushStateCount
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
			this.$photo = this.$link.parents('.bdPhotos_Photo');
			this.href = $link.attr('href');
			this.hrefReal = $link.data('href') ? $link.data('href') : $link.attr('href');

			$link.click($.context(this, 'click'));

			if ($bdPhotos_NavigationLink_firstState === null)
			{
				var oldPhotoHtml = this.$photo.parent().html();
				var oldSidebarHtml = $('.sidebar').html();

				$bdPhotos_NavigationLink_firstState =
				{
					'_owner': 'bdPhotos_NavigationLink_updateHtml',
					'title': document.title,
					'href': window.location.href,
					'photoHtml': oldPhotoHtml,
					'sidebarHtml': oldSidebarHtml,
					'pushStateCount': bdPhotos_Overlay_pushStateCount
				};
			}
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

			this.xhr = XenForo.ajax(this.hrefReal,
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

			var photoSelector = '#' + this.$photo.attr('id');
			var href = this.href;

			new XenForo.ExtLoader(ajaxData, function()
			{
				bdPhotos_NavigationLink_updateHtml(photoSelector, href, ajaxData.title, ajaxData.templateHtml, ajaxData.sidebarHtml, true);
			});
		}
	};

	// *********************************************************************

	var bdPhotos_OverlayTrigger_postClosePopStateBinder = null;
	var bdPhotos_OverlayTrigger_postClosePopState = function(e)
	{
		var state = e.originalEvent.state;

		if (state && state._owner && state._owner == 'bdPhotos_OverlayTrigger')
		{
			if (bdPhotos_OverlayTrigger_postClosePopStateBinder && bdPhotos_OverlayTrigger_postClosePopStateBinder.href == state.href)
			{
				bdPhotos_OverlayTrigger_postClosePopStateBinder.shownByPostClose = true;
				bdPhotos_OverlayTrigger_postClosePopStateBinder.show(e);

				bdPhotos_Overlay_pushStateCount = state.pushStateCount;
			}
		}
	};

	XenForo.bdPhotos_OverlayTrigger = function($trigger)
	{
		this.__construct($trigger);
	};
	XenForo.bdPhotos_OverlayTrigger.prototype =
	{
		__construct: function($trigger)
		{
			this.$trigger = $trigger.click($.context(this, 'show'));
			this.href = this.$trigger.attr('href');
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

			this.documentTitle = document.title;

			this.shownByPostClose = false;
			this.windowPopStateContext = $.context(this, 'windowPopState');
		},

		overlayCallback: function()
		{
			var api = this.$trigger.data('overlay');
			var $overlay = api.getOverlay();

			var $photoWrapperWrapper = $overlay.find('.bdPhotos_PhotoWrapperWrapper');

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

			bdPhotos_setupOverlay($overlay.find('.bdPhotos_Photo'));
		},

		overlayClose: function(e)
		{
			$(window).unbind('popstate', this.windowPopStateContext);

			if (window.history.pushState)
			{
				if (bdPhotos_Overlay_pushStateCount > 0)
				{
					window.history.go(-bdPhotos_Overlay_pushStateCount);
					bdPhotos_Overlay_pushStateCount = 0;
				}

				document.title = this.documentTitle;
			}

			bdPhotos_OverlayTrigger_postClosePopStateBinder = this;
			$(window).bind('popstate', bdPhotos_OverlayTrigger_postClosePopState);

			if (this.OverlayLoader)
			{
				this.OverlayLoader.overlay.getTrigger().removeData('overlay');
				this.OverlayLoader.overlay.getOverlay().empty().remove();
			}
			delete (this.OverlayLoader);
		},

		overlayLoad: function(e)
		{
			$(window).unbind('popstate', bdPhotos_OverlayTrigger_postClosePopState);

			if (window.history.pushState)
			{
				$bdPhotos_NavigationLink_firstState = null;

				if (this.shownByPostClose)
				{
					this.shownByPostClose = false;
				}
				else
				{
					bdPhotos_Overlay_pushStateCount++;

					window.history.pushState(
					{
						'_owner': 'bdPhotos_OverlayTrigger',
						'href': this.href,
						'pushStateCount': bdPhotos_Overlay_pushStateCount
					}, '', this.href);
				}

				document.title = this.$trigger.data('overlay').getConf().title;
			}

			$(window).bind('popstate', this.windowPopStateContext);
		},

		windowPopState: function(e)
		{
			var state = e.originalEvent.state;

			if (!state)
			{
				bdPhotos_Overlay_pushStateCount = 0;

				var api = this.$trigger.data('overlay');
				if (api)
				{
					api.close();
				}
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

			options = $.extend(
			{
			}, this.options, this.$trigger.data('overlayoptions'));

			this.OverlayLoader = new XenForo.OverlayLoader(this.$trigger, false, options);
			this.OverlayLoader.load($.context(this, 'overlayCallback'));

			return true;

		}
	};

	// *********************************************************************

	$(window).bind('keyup', function(e)
	{
		var direction = 0;
		switch (e.which)
		{
			case 37:
				direction = -1;
				break;
			case 39:
				direction = 1;
				break;
		}

		if ($(e.target).attr('name'))
		{
			// easy way to determine an input element
			return false;
		}

		if (direction != 0)
		{
			var $link;

			if (direction < 0)
			{
				$link = $('.bdPhotos_NavigationLink.prev');
			}
			else
			{
				$link = $('.bdPhotos_NavigationLink.next');
			}

			if ($link.length > 0)
			{
				var $foundLink = null;

				$link.each(function()
				{
					var $this = $(this);

					var $thisOverlay = $this.parents('.xenOverlay');
					if ($thisOverlay.length == 0)
					{
						// this is a page level navigation link
					}
					else
					{
						// this is a navigation link within an overlay
						// need to check for overlay visibility
						if (!$thisOverlay.is(':visible'))
						{
							return;
						}
					}

					$foundLink = $this;
				});

				if ($foundLink != null)
				{
					$foundLink.trigger('click');
				}
			}
		}
	});

	// *********************************************************************

	XenForo.register('a.bdPhotos_NavigationLink', 'XenForo.bdPhotos_NavigationLink');
	XenForo.register('a.bdPhotos_OverlayTrigger', 'XenForo.bdPhotos_OverlayTrigger');

}(jQuery, this, document);
