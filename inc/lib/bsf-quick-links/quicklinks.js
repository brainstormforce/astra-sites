(function($) {
    /**
	 * BSFQuickLinks
	 * 
     * @since 1.0.0 
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
            $(document).on("click", BSFQuickLinks._onClickOutside);
            $(document).on("click", BSFQuickLinks._movePopUpToTop);
            $(document).ready(BSFQuickLinks._movePopUpToTop);
        },
        
        _movePopUpToTop: function(e) {  
            if( $('.single-site-footer').length ) {
                $(".bsf-quick-link").css("bottom", "80px");
                $(".bsf-quick-link-items-wrap").css("bottom", "11em");        
                $(".bsf-quick-link-title").css("bottom", "8em");        
            } else {
                $(".bsf-quick-link").css("bottom", "40px");
                $(".bsf-quick-link-items-wrap").css("bottom", "8em");
                $(".bsf-quick-link-title").css("bottom", "4.5em");        
            }
            if( $('.astra-sites-result-preview').length  && !$('.astra-sites-result-preview').is(':empty') ) {
                $(".bsf-quick-link").css("z-index", "-10");
            } else {
                $(".bsf-quick-link").css("z-index", "10");
            }
        },

        _onClickOutside: function(e) {
            if ( $(e.target).closest('.bsf-quick-link-wrap').length === 0 ) { 
                $('.bsf-quick-link-items-wrap').removeClass('show-popup');
            }
        },

        _showTooltip: function(event) {
            if (!$('.bsf-quick-link-items-wrap').hasClass('show-popup')) {
                $('.bsf-quick-link-title').show();
            }
        },

        _hideTooltip: function(event) {
            $('.bsf-quick-link-title').hide();
        },

        _toggle: function(event) {
            event.preventDefault();
            var wrap = $('.bsf-quick-link-items-wrap');
            $('.bsf-quick-link-title').hide();
            if (wrap.hasClass('show-popup')) {
                $('.bsf-quick-link-items-wrap').removeClass('show-popup');
            } else {
                $('.bsf-quick-link-items-wrap').addClass('show-popup');
            }
            var logoWrapper = $('.bsf-quick-link');           
            if (logoWrapper.hasClass('show')) {
                $('.bsf-quick-link').removeClass('show');
            } else {
                $('.bsf-quick-link').addClass('show');
            }
        }
    };

    $(function() {
        BSFQuickLinks.init();
    });
})(jQuery);