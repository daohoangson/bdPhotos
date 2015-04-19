!function ($, window, document, _undefined) {
    XenForo.bdPhotos_AttachmentUploader = function ($uploader) {
        $uploader.bind(
            {
                AttachmentUploaded: function (e) {
                    if (e.file)// SWFupload method
                    {
                        window.setTimeout(function () {
                            var $attachment = $('#attachment' + e.ajaxData.attachment_id);
                            var $templateHtml = $(e.ajaxData.templateHtml);

                            var $controlsTop = $attachment.find('.controls.top');
                            $templateHtml.find('.controls.top').xfInsert('insertBefore', $controlsTop, 'show');
                            $controlsTop.xfRemove();
                        }, 500);
                    }
                }
            });
    };

    // *********************************************************************

    XenForo.bdPhotos_RoiEditor = function ($container) {
        var self = this;

        window.setTimeout(function () {
            self.__construct($container);
        }, 100);
    };
    XenForo.bdPhotos_RoiEditor.prototype =
    {
        __construct: function ($container) {
            var $photo = $container.parents('.bdPhotos_PhotoList_Photo');
            if ($photo.length == 0) {
                return;
            }

            this.$photo = $photo;

            this.$imgContainer = $photo.find('.photo');
            this.imgContainerWidth = this.$imgContainer.width();
            this.imgContainerHeight = this.$imgContainer.height();
            if (this.$imgContainer.length == 0 || this.imgContainerWidth == 0 || this.imgContainerHeight == 0) {
                return;
            }

            this.$img = $photo.find('.photo img').bind(
                {
                    dragstart: $.context(this, 'dragStart'),
                    dragend: $.context(this, 'dragEnd'),
                    drag: $.context(this, 'drag')
                });
            this.imgWidth = this.$img.width();
            this.imgHeight = this.$img.height();
            if (this.$img.length == 0 || this.imgWidth == 0 || this.imgHeight == 0) {
                return;
            }

            this.$roi0 = $photo.find('input.roi0');
            this.$roi1 = $photo.find('input.roi1');

            this.setupCss();
            this.updateImgPosition();
        },

        dragStart: function (e) {
            // TODO
        },

        drag: function (e) {
            var offset = this.$img.offset(), pos = this.$img.position();

            var left = e.offsetX - offset.left + pos.left, top = e.offsetY - offset.top + pos.top;

            left = Math.max(this.imgContainerWidth - this.imgWidth, Math.min(0, left));
            top = Math.max(this.imgContainerHeight - this.imgHeight, Math.min(0, top));

            this.$img.css('left', left + 'px');
            this.$img.css('top', top + 'px');

            var roiX = (this.imgContainerWidth / 2) - left, roiY = (this.imgContainerHeight / 2) - top;
            this.$roi0.val(roiX / this.imgWidth);
            this.$roi1.val(roiY / this.imgHeight);
        },

        dragEnd: function (e) {
            // TODO
        },

        setupCss: function () {
            this.$imgContainer.css('position', 'relative');
            this.$img.css('position', 'absolute');
        },

        updateImgPosition: function () {
            var roiX = this.$roi0.val() * this.imgWidth, roiY = this.$roi1.val() * this.imgHeight;

            var left = (this.imgContainerWidth / 2) - roiX, top = (this.imgContainerWidth / 2) - roiY;

            left = Math.max(this.imgContainerWidth - this.imgWidth, Math.min(0, left));
            top = Math.max(this.imgContainerHeight - this.imgHeight, Math.min(0, top));

            this.$img.css('left', left + 'px');
            this.$img.css('top', top + 'px');
        }
    };

    // *********************************************************************

    XenForo.register('.bdPhotos_AttachmentEditor', 'XenForo.AttachmentEditor');
    XenForo.register('#AttachmentUploader', 'XenForo.bdPhotos_AttachmentUploader');
    XenForo.register('.bdPhotos_RoiEditor', 'XenForo.bdPhotos_RoiEditor');

}(jQuery, this, document);
