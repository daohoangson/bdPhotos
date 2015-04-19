!function ($, window, document, _undefined) {

    XenForo.bdPhotos_DeviceCodeNew = function ($element) {
        this.__construct($element);
    };
    XenForo.bdPhotos_DeviceCodeNew.prototype =
    {
        __construct: function ($element) {
            $element.one('keypress', $.context(this, 'createChoice'));

            this.$element = $element;
            if (!this.$base) {
                this.$base = $element.clone();
            }
        },

        createChoice: function () {
            var $new = this.$base.clone(), nextCounter = this.$element.parent().children().length;

            $new.find('input[name]').each(function () {
                var $this = $(this);
                $this.attr('name', $this.attr('name').replace(/\[(\d+)\]/, '[' + nextCounter + ']'));
            });

            $new.find('*[id]').each(function () {
                var $this = $(this);
                $this.removeAttr('id');
                XenForo.uniqueId($this);

                if (XenForo.formCtrl) {
                    XenForo.formCtrl.clean($this);
                }
            });

            $new.xfInsert('insertAfter', this.$element);

            this.__construct($new);
        }
    };

    // *********************************************************************

    XenForo.register('li.bdPhotos_DeviceCodeNew', 'XenForo.bdPhotos_DeviceCodeNew');

}(jQuery, this, document); 