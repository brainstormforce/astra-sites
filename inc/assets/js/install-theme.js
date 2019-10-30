(function($){

	AstraSitesInstallTheme = {

		/**
		 * Init
		 */
		init: function() {
			this._auto_close_notice();
			this._bind();
		},

		/**
		 * Binds events for the Astra Sites.
		 *
		 * @since 1.3.2
		 * 
		 * @access private
		 * @method _bind
		 */
		_bind: function()
		{
			$( document ).on( 'click', '.astra-sites-theme-not-installed', AstraSitesInstallTheme._install_and_activate );
			$( document ).on( 'click', '.astra-sites-theme-installed-but-inactive', AstraSitesInstallTheme._activateTheme );
			$( document ).on('wp-theme-install-success' , AstraSitesInstallTheme._activateTheme);
		},

		/**
		 * Close Getting Started Notice
		 * 
		 * @param  {object} event
		 * @return void
		 */
		_auto_close_notice: function() {
			
			if( $( '.astra-sites-getting-started-btn' ).length ) {
				$.ajax({
					url: AstraSitesInstallThemeVars.ajaxurl,
					type: 'POST',
					data: {
						'action' : 'astra-sites-getting-started-notice',
						'_ajax_nonce' : AstraSitesInstallThemeVars._ajax_nonce,
					},
				})
				.done(function (result) {
				});
			}

		},

		/**
		 * Activate Theme
		 *
		 * @since 1.3.2
		 */
		_activateTheme: function( event, response ) {
			event.preventDefault();

			$('#astra-theme-activation-nag a').addClass('processing');

			if( response ) {
				$('#astra-theme-activation-nag a').text( AstraSitesInstallThemeVars.installed );
			} else {
				$('#astra-theme-activation-nag a').text( AstraSitesInstallThemeVars.activating );
			}

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				$.ajax({
					url: AstraSitesInstallThemeVars.ajaxurl,
					type: 'POST',
					data: {
						'action' : 'astra-sites-activate-theme',
						'_ajax_nonce' : AstraSitesInstallThemeVars._ajax_nonce,
					},
				})
				.done(function (result) {
					if( result.success ) {
						$('#astra-theme-activation-nag a').text( AstraSitesInstallThemeVars.activated );

						setTimeout(function() {
							location.reload();
						}, 1000);
					}

				});

			}, 3000 );

		},

		/**
		 * Install and activate
		 *
		 * @since 1.3.2
		 * 
		 * @param  {object} event Current event.
		 * @return void
		 */
		_install_and_activate: function(event ) {
			event.preventDefault();
			var theme_slug = $(this).data('theme-slug') || '';
			var btn = $( event.target );

			if ( btn.hasClass( 'processing' ) ) {
				return;
			}

			btn.text( AstraSitesInstallThemeVars.installing ).addClass('processing');

			if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.ajaxLocked ) {
				wp.updates.requestFilesystemCredentials( event );
			}
			
			wp.updates.installTheme( {
				slug: theme_slug
			});
		}

	};

	/**
	 * Initialize
	 */
	$(function(){
		AstraSitesInstallTheme.init();
	});

})(jQuery);