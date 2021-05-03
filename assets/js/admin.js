(function ($) {
    /**
     * Admin
     * 
     * @since x.x.x 
     */
    Admin = {
        /**
         * Initializes Events.
         *
         * @since x.x.x
         * @method init
         */
        init: function () {
            this._bind();
        },

        /**
         * Binds events for the BSF Quick Links
         *
         * @since x.x.x
         * @access private
         * @method _bind
         */
        _bind: function () {
            $(window).on('scroll', Admin._addCustomCTAInfobar);
        },

        /**
         * Show Custom CTA on scroll.
         */
        _addCustomCTAInfobar: function () {
            var scroll = $(window).scrollTop();

            if (scroll > 70) {
                $(".astra-sites-custom-cta-wrap").addClass("show");
            } else {
                $(".astra-sites-custom-cta-wrap").removeClass("show");
            }
        },

    };

    $(function () {
        Admin.init();
    });
})(jQuery);