!function ($, window, document, _undefined) {

    XenForo.bdPhotos_CommentPoster = function ($element) {
        this.__construct($element);
    };

    XenForo.bdPhotos_CommentPoster.prototype =
    {
        __construct: function ($link) {
            this.$link = $link;
            this.$commentArea = $($link.data('commentarea'));

            if (this.$commentArea.data('submiturl')) {
                this.submitUrl = this.$commentArea.data('submiturl');
            }
            else {
                this.submitUrl = $link.attr('href');
            }

            $link.click($.context(this, 'click'));

            this.$commentArea.find('input:submit, button').click($.context(this, 'submit'));
        },

        click: function (e) {
            e.preventDefault();

            this.$commentArea.xfFadeDown(XenForo.speed.fast, function () {
                $(this).find('textarea[name="message"]').focus();
            });
        },

        submit: function (e) {
            e.preventDefault();

            XenForo.ajax(
                this.submitUrl,
                {message: this.$commentArea.find('textarea[name="message"]').val()},
                $.context(this, 'submitSuccess')
            );
        },

        submitSuccess: function (ajaxData) {
            if (XenForo.hasResponseError(ajaxData)) {
                return false;
            }

            if (ajaxData.comment_insertAfter) {
                $(ajaxData.comment_insertAfter).xfInsert('insertAfter', this.$commentArea);
            }

            this.$commentArea.find('textarea[name="message"]').val('');
        }
    };

    // *********************************************************************

    XenForo.register('a.bdPhotos_CommentPoster', 'XenForo.bdPhotos_CommentPoster');

}(jQuery, this, document);
