(function($) {

    /*
     * @since 1.0.0
     * 
     */
    BSFQuickLinks = {

        /**
         * Initializes Events.
         *
         * @since 1.0.0
         * @method init
         */
        init: function() {
            this._bind();
        },

        /**
         * Binds events for the BSF Quick Links
         *
         * @since 1.0.0
         * @access private
         * @method _bind
         */
        _bind: function() {
            $(document).on("mouseover", ".bsf-quick-link", BSFQuickLinks._showTooltip);
            $(document).on("mouseout", ".bsf-quick-link", BSFQuickLinks._hideTooltip);
            $(document).on("click", ".bsf-quick-link", BSFQuickLinks._toggle);

        },

        _showTooltip: function(event) {
            if (!$('.bsf-quick-link-items-wrap').hasClass('show-popup')) {
                $('label.bsf-quick-link-title').show();
            }
        },

        _hideTooltip: function(event) {
            $('label.bsf-quick-link-title').hide();
        },

        _toggle: function(event) {
            event.preventDefault();
            var wrap = $('.bsf-quick-link-items-wrap');

            if (wrap.hasClass('show-popup')) {
                $('.bsf-quick-link-items-wrap').removeClass('show-popup');
            } else {
                $('.bsf-quick-link-items-wrap').addClass('show-popup');
            }

        }




    };

    $(function() {
        BSFQuickLinks.init();
    });
})(jQuery);