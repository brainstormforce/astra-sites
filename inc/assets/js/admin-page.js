/**
 * AJAX Request Queue
 *
 * - add()
 * - remove()
 * - run()
 * - stop()
 *
 * @since 1.0.0
 */
var AstraSitesAjaxQueue = (function() {

	var requests = [];

	return {

		/**
		 * Add AJAX request
		 *
		 * @since 1.0.0
		 */
		add:  function(opt) {
		    requests.push(opt);
		},

		/**
		 * Remove AJAX request
		 *
		 * @since 1.0.0
		 */
		remove:  function(opt) {
		    if( jQuery.inArray(opt, requests) > -1 )
		        requests.splice($.inArray(opt, requests), 1);
		},

		/**
		 * Run / Process AJAX request
		 *
		 * @since 1.0.0
		 */
		run: function() {
		    var self = this,
		        oriSuc;

		    if( requests.length ) {
		        oriSuc = requests[0].complete;

		        requests[0].complete = function() {
		             if( typeof(oriSuc) === 'function' ) oriSuc();
		             requests.shift();
		             self.run.apply(self, []);
		        };

		        jQuery.ajax(requests[0]);

		    } else {

		      self.tid = setTimeout(function() {
		         self.run.apply(self, []);
		      }, 1000);
		    }
		},

		/**
		 * Stop AJAX request
		 *
		 * @since 1.0.0
		 */
		stop:  function() {

		    requests = [];
		    clearTimeout(this.tid);
		}
	};

}());
(function($){

	AstraSitesAdmin = {

		init: function()
		{
			this._resetPagedCount();
			this._bind();
		},

		/**
		 * Debugging.
		 * 
		 * @param  {mixed} data Mixed data.
		 */
		_log: function( data ) {
			
			if( astraSitesAdmin.debug ) {

				var date = new Date();
				var time = date.toLocaleTimeString();

				if (typeof data == 'object') { 
					console.log('%c ' + JSON.stringify( data ) + ' ' + time, 'background: #222; color: #bada55');
				} else {
					console.log('%c ' + data + ' ' + time, 'background: #222; color: #bada55');
				}


			}
		},

		/**
		 * Binds events for the Astra Sites.
		 *
		 * @since 1.0.0
		 * @access private
		 * @method _bind
		 */
		_bind: function()
		{
			$( document ).on('click'                                           , '.theme-browser .theme-screenshot, .theme-browser .more-details, .theme-browser .install-theme-preview', AstraSitesAdmin._preview);
			$( document ).on('click'                                           , '.next-theme', AstraSitesAdmin._nextTheme);
			$( document ).on('click'                                           , '.previous-theme', AstraSitesAdmin._previousTheme);
			$( document ).on('click'                                           , '.collapse-sidebar', AstraSitesAdmin._collapse);
			$( document ).on('click'                                           , '.astra-demo-import', AstraSitesAdmin._importDemo);
			$( document ).on('click'                                           , '.install-now', AstraSitesAdmin._installNow);
			$( document ).on('click'                                           , '.close-full-overlay', AstraSitesAdmin._fullOverlay);
			$( document ).on('click'                                           , '.activate-now', AstraSitesAdmin._activateNow);
			$( document ).on('wp-plugin-installing'                            , AstraSitesAdmin._pluginInstalling);
			$( document ).on('wp-plugin-install-error'                         , AstraSitesAdmin._installError);
			$( document ).on('wp-plugin-install-success'                       , AstraSitesAdmin._installSuccess);
		},

		/**
		 * Import Success Button.
		 * 
		 * @param  {string} data Error message.
		 */
		_importSuccessMessage: function( data ) {

			if( astraSitesAdmin.debug && 'undefined' !== data.data.log_file.url ) {

				var abs_url = decodeURIComponent( data.data.log_file.abs_url ) || '',
					name    = data.data.log_file.name || '',
					url     = decodeURIComponent( data.data.log_file.url ) || '';

				$('.install-theme-info').prepend('<div class="notice notice-info"><p>Import Log: <a target="_blank" href="'+url+'">'+name+'</a></p></div>')
			}

			$('.astra-demo-import').removeClass('updating-message installing')
				.removeAttr('data-import')
				.addClass('view-site')
				.removeClass('astra-demo-import')
				.text( astraSitesAdmin.strings.viewSite )
				.attr('target', '_blank')
				.append('<i class="dashicons dashicons-external"></i>')
				.attr('href', astraSitesAdmin.siteURL );
		},

		/**
		 * Import Error Button.
		 * 
		 * @param  {string} data Error message.
		 */
		_importFailMessage: function( data ) {

			$('.astra-demo-import').removeClass('updating-message installing')
				.removeAttr('data-import')
				.text( astraSitesAdmin.strings.importAgain );

			var output  = '<div class="astra-api-error notice notice-error notice-alt is-dismissible">';
				output += '	<p>'+data+'</p>';
				output += '	<button type="button" class="notice-dismiss">';
				output += '		<span class="screen-reader-text">'+commonL10n.dismiss+'</span>';
				output += '	</button>';
				output += '</div>';

			$('.install-theme-info').prepend( output );

			// !important to add trigger.
			// Which reinitialize the dismiss error message events.
			$(document).trigger('wp-updates-notice-added');
		},


		/**
		 * Install Now
		 */
		_installNow: function(event)
		{
			event.preventDefault();

			var $button 	= jQuery( event.target ),
				$document   = jQuery(document);

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.ajaxLocked ) {
				wp.updates.requestFilesystemCredentials( event );

				$document.on( 'credential-modal-cancel', function() {
					var $message = $( '.install-now.updating-message' );

					$message
						.removeClass( 'updating-message' )
						.text( wp.updates.l10n.installNow );

					wp.a11y.speak( wp.updates.l10n.updateCancel, 'polite' );
				} );
			}

			AstraSitesAdmin._log( astraSitesAdmin.log.installingPlugin + ' ' + $button.data( 'slug' ) );

			wp.updates.installPlugin( {
				slug: $button.data( 'slug' )
			} );
		},

		/**
		 * Install Success
		 */
		_installSuccess: function( event, response ) {

			event.preventDefault();

			AstraSitesAdmin._log( astraSitesAdmin.log.installed + ' ' + response.slug );

			var $message     = jQuery( '.plugin-card-' + response.slug ).find( '.button' );
			var $siteOptions = jQuery( '.wp-full-overlay-header').find('.astra-site-options').val();
			var $enabledExtensions = jQuery( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

			// Transform the 'Install' button into an 'Activate' button.
			var $init = $message.data('init');

			$message.removeClass( 'install-now installed button-disabled updated-message' )
				.addClass('updating-message')
				.html( astraSitesAdmin.strings.btnActivating );

			// Reset not installed plugins list.
			var pluginsList = astraSitesAdmin.requiredPlugins.notinstalled;
			astraSitesAdmin.requiredPlugins.notinstalled = AstraSitesAdmin._removePluginFromQueue( response.slug, pluginsList );

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				$.ajax({
					url: astraSitesAdmin.ajaxurl,
					type: 'POST',
					data: {
						'action'            : 'astra-required-plugin-activate',
						'init'              : $init,
						'options'           : $siteOptions,
						'enabledExtensions' : $enabledExtensions,
					},
				})
				.done(function (result) {

					if( result.success ) {

						var pluginsList = astraSitesAdmin.requiredPlugins.inactive;

						// Reset not installed plugins list.
						astraSitesAdmin.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue( response.slug, pluginsList );

						$message.removeClass( 'button-primary install-now activate-now updating-message' )
							.attr('disabled', 'disabled')
							.addClass('disabled')
							.text( astraSitesAdmin.strings.btnActive );

						// Enable Demo Import Button
						AstraSitesAdmin._enable_demo_import_button();

					} else {

						$message.removeClass( 'updating-message' );

					}

				});

			}, 1200 );

		},

		/**
		 * Plugin Installation Error.
		 */
		_installError: function( event, response ) {

			var $card = jQuery( '.plugin-card-' + response.slug );

			AstraSitesAdmin._log( astraSitesAdmin.log.installError + ' ' + response.slug );

			$card
				.addClass( 'button-primary' )
				.html( wp.updates.l10n.installNow );
		},

		/**
		 * Installing Plugin
		 */
		_pluginInstalling: function(event, args) {
			event.preventDefault();

			var $card = jQuery( '.plugin-card-' + args.slug );
			var $button = $card.find( '.button' );

			AstraSitesAdmin._log( astraSitesAdmin.log.installingPlugin + ' ' + args.slug );

			$card.addClass('updating-message');
			$button.addClass('already-started');

		},

		/**
		 * Render Demo Preview
		 */
		_activateNow: function( eventn ) {

			event.preventDefault();

			var $button = jQuery( event.target ),
				$init 	= $button.data( 'init' ),
				$slug 	= $button.data( 'slug' );

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			AstraSitesAdmin._log( astraSitesAdmin.log.activating + ' ' + $slug );

			$button.addClass('updating-message button-primary')
				.html( astraSitesAdmin.strings.btnActivating );

			var $siteOptions = jQuery( '.wp-full-overlay-header').find('.astra-site-options').val();
			var $enabledExtensions = jQuery( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

			$.ajax({
				url: astraSitesAdmin.ajaxurl,
				type: 'POST',
				data: {
					'action'            : 'astra-required-plugin-activate',
					'init'              : $init,
					'options'           : $siteOptions,
					'enabledExtensions' : $enabledExtensions,
				},
			})
			.done(function (result) {

				if( result.success ) {

					AstraSitesAdmin._log( astraSitesAdmin.log.activated + ' ' + $slug );

					var pluginsList = astraSitesAdmin.requiredPlugins.inactive;

					// Reset not installed plugins list.
					astraSitesAdmin.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue( $slug, pluginsList );

					$button.removeClass( 'button-primary install-now activate-now updating-message' )
						.attr('disabled', 'disabled')
						.addClass('disabled')
						.text( astraSitesAdmin.strings.btnActive );

					// Enable Demo Import Button
					AstraSitesAdmin._enable_demo_import_button();

				}

			})
			.fail(function () {
			});

		},

		/**
		 * Full Overlay
		 */
		_fullOverlay: function (event) {
			event.preventDefault();

			jQuery('.theme-install-overlay').css('display', 'none');
			jQuery('.theme-install-overlay').remove();
			jQuery('.theme-preview-on').removeClass('theme-preview-on');
			jQuery('html').removeClass('astra-site-preview-on');
		},

		/**
		 * Bulk Plugin Active & Install
		 */
		_bulkPluginInstallActivate: function()
		{
			if( 0 === astraSitesAdmin.requiredPlugins.length ) {
				return;
			}

			jQuery('.required-plugins')
				.find('.install-now')
				.addClass( 'updating-message' )
				.removeClass( 'install-now' )
				.text( wp.updates.l10n.installing );

			jQuery('.required-plugins')
				.find('.activate-now')
				.addClass('updating-message')
				.removeClass( 'activate-now' )
				.html( astraSitesAdmin.strings.btnActivating );

			var not_installed 	 = astraSitesAdmin.requiredPlugins.notinstalled || '';
			var activate_plugins = astraSitesAdmin.requiredPlugins.inactive || '';

			// First Install Bulk.
			if( not_installed.length > 0 ) {
				AstraSitesAdmin._installAllPlugins( not_installed );
			}

			// Second Activate Bulk.
			if( activate_plugins.length > 0 ) {
				AstraSitesAdmin._activateAllPlugins( activate_plugins );
			}

		},

		/**
		 * Activate All Plugins.
		 */
		_activateAllPlugins: function( activate_plugins ) {

			// Process of cloud templates - (download, remove & fetch).
			AstraSitesAjaxQueue.stop();
			AstraSitesAjaxQueue.run();

			AstraSitesAdmin._log( astraSitesAdmin.log.bulkActivation );

			$.each( activate_plugins, function(index, single_plugin) {

				var $card    	 = jQuery( '.plugin-card-' + single_plugin.slug ),
					$button  	 = $card.find('.button'),
					$siteOptions = jQuery( '.wp-full-overlay-header').find('.astra-site-options').val(),
					$enabledExtensions = jQuery( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

				$button.addClass('updating-message');

				AstraSitesAjaxQueue.add({
					url: astraSitesAdmin.ajaxurl,
					type: 'POST',
					data: {
						'action'            : 'astra-required-plugin-activate',
						'init'              : single_plugin.init,
						'options'           : $siteOptions,
						'enabledExtensions' : $enabledExtensions,
					},
					success: function( result ){

						if( result.success ) {

							AstraSitesAdmin._log( astraSitesAdmin.log.activate + ' ' + single_plugin.slug );

							var $card = jQuery( '.plugin-card-' + single_plugin.slug );
							var $button = $card.find( '.button' );
							if( ! $button.hasClass('already-started') ) {
								var pluginsList = astraSitesAdmin.requiredPlugins.inactive;

								// Reset not installed plugins list.
								astraSitesAdmin.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue( single_plugin.slug, pluginsList );
							}

							$button.removeClass( 'button-primary install-now activate-now updating-message' )
								.attr('disabled', 'disabled')
								.addClass('disabled')
								.text( astraSitesAdmin.strings.btnActive );

							// Enable Demo Import Button
							AstraSitesAdmin._enable_demo_import_button();
						} else {
							AstraSitesAdmin._log( astraSitesAdmin.log.activationError + ' - ' + single_plugin.slug );
						}
					}
				});
			});
		},

		/**
		 * Install All Plugins.
		 */
		_installAllPlugins: function( not_installed ) {

			AstraSitesAdmin._log( astraSitesAdmin.log.bulkInstall );
			
			$.each( not_installed, function(index, single_plugin) {

				var $card   = jQuery( '.plugin-card-' + single_plugin.slug ),
					$button = $card.find('.button');

				if( ! $button.hasClass('already-started') ) {

					// Add each plugin activate request in Ajax queue.
					// @see wp-admin/js/updates.js
					wp.updates.queue.push( {
						action: 'install-plugin', // Required action.
						data:   {
							slug: single_plugin.slug
						}
					} );
				}
			});

			// Required to set queue.
			wp.updates.queueChecker();
		},

		/**
		 * Fires when a nav item is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _importDemo
		 */
		_importDemo: function()
		{
			var $this 	= jQuery(this),
				$theme  = $this.closest('.astra-sites-preview').find('.wp-full-overlay-header'),
				apiURL  = $theme.data('demo-api') || '',
				plugins = $theme.data('required-plugins');

			var disabled = $this.attr('data-import');

			if ( typeof disabled !== 'undefined' && disabled === 'disabled' ) {

				$('.astra-demo-import').addClass('updating-message installing')
					.text( wp.updates.l10n.installing );

				/**
				 * Process Bulk Plugin Install & Activate
				 */
				AstraSitesAdmin._bulkPluginInstallActivate();

				return;
			}

			// Proceed?
			if( ! confirm( astraSitesAdmin.strings.importWarning ) ) {
				return;
			}

			// Remove all notices before import start.
			$('.install-theme-info > .notice').remove();

			$('.astra-demo-import').attr('data-import', 'disabled')
				.addClass('updating-message installing')
				.text( astraSitesAdmin.strings.importingDemo );

			$this.closest('.theme').focus();

			var $theme = $this.closest('.astra-sites-preview').find('.wp-full-overlay-header');

			var apiURL = $theme.data('demo-api') || '';
			
			// Site Import by API URL.
			if( apiURL ) {
				AstraSitesAdmin._importSite( apiURL );
			}

		},

		/**
		 * Start Import Process by API URL.
		 * 
		 * @param  {string} apiURL Site API URL.
		 */
		_importSite: function( apiURL ) {

			AstraSitesAdmin._log( astraSitesAdmin.log.api + ' : ' + apiURL );
			AstraSitesAdmin._log( astraSitesAdmin.log.importing );

			$('.button-hero.astra-demo-import').text( 'Getting Import Data...' );

			// 1. Request Site Import
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					'action'  : 'astra-sites-import-start',
					'api_url' : apiURL,
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
				AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
		    })
			.done(function ( demo_data ) {

				// 1. Fail - Request Site Import
				if( false === demo_data.success ) {

					AstraSitesAdmin._importFailMessage( demo_data.data );

				} else {

					// 1. Pass - Request Site Import
					AstraSitesAdmin._log( astraSitesAdmin.log.processingRequest );
					$('.button-hero.astra-demo-import').text( 'Importing Customizer...' );

					var customizer_data = JSON.stringify( demo_data.data['astra-site-customizer-data'] ) || '',
						wxr_url         = encodeURI( demo_data.data['astra-site-wxr-path'] ) || '',
						options_data 	= JSON.stringify( demo_data.data['astra-site-options-data'] ) || '',
						widgets_data 	= JSON.stringify( demo_data.data['astra-site-widgets-data'] ) || '';

					// 2. Import Customizer Options.
					$.ajax({
						url  : astraSitesAdmin.ajaxurl,
						type : 'POST',
						data : {
							action          : 'astra-sites-import-customizer-settings',
							customizer_data : customizer_data,
						},
						beforeSend: function() {
							AstraSitesAdmin._log( astraSitesAdmin.log.importCustomizer );
						},
					})
					.fail(function( jqXHR ){
						AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
						AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
				    })
					.done(function ( customizer_data ) {

						// 2. Fail - Import Customizer Options.
						if( false === customizer_data.success ) {
							AstraSitesAdmin._importFailMessage( customizer_data.data );
							AstraSitesAdmin._log( customizer_data.data );

						} else {

							// 2. Pass - Import Customizer Options.
							AstraSitesAdmin._log( astraSitesAdmin.log.importCustomizerSuccess );
							$('.button-hero.astra-demo-import').text( 'Importing XML...' );
							
							// 3. Import XML.
							$.ajax({
								url  : astraSitesAdmin.ajaxurl,
								type : 'POST',
								data : {
									action  : 'astra-sites-import-xml',
									wxr_url : wxr_url,
								},
								beforeSend: function() {
									AstraSitesAdmin._log( astraSitesAdmin.log.importXML );
								},
							})
							.fail(function( jqXHR ){
								AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
								AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
						    })
							.done(function ( wxr_url ) {

								// 3. Fail - Import XML.
								if( false === wxr_url.success ) {
									AstraSitesAdmin._log( wxr_url );
									AstraSitesAdmin._importFailMessage( wxr_url.data );
									AstraSitesAdmin._log( wxr_url.data );

								} else {

									// 3. Pass - Import XML.
									AstraSitesAdmin._log( astraSitesAdmin.log.importXMLSuccess );
									$('.button-hero.astra-demo-import').text( 'Importing Site Options...' );
									
									// 4. Import Options.
									$.ajax({
										url  : astraSitesAdmin.ajaxurl,
										type : 'POST',
										data : {
											action       : 'astra-sites-import-options',
											options_data : options_data,
										},
										beforeSend: function() {
											AstraSitesAdmin._log( astraSitesAdmin.log.importOptions );
										},
									})
									.fail(function( jqXHR ){
										AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
										AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
								    })
									.done(function ( options_data ) {

										// 4. Fail - Import Options.
										if( false === options_data.success ) {
											AstraSitesAdmin._log( options_data );
											AstraSitesAdmin._importFailMessage( options_data.data );
											AstraSitesAdmin._log( options_data.data );

										} else {

											// 4. Pass - Import Options.
											AstraSitesAdmin._log( astraSitesAdmin.log.importOptionsSuccess );
											$('.button-hero.astra-demo-import').text( 'Importing Widgets...' );
											
											// 5. Import Widgets.
											$.ajax({
												url  : astraSitesAdmin.ajaxurl,
												type : 'POST',
												data : {
													action       : 'astra-sites-import-widgets',
													widgets_data : widgets_data,
												},
												beforeSend: function() {
													AstraSitesAdmin._log( astraSitesAdmin.log.importOptions );
												},
											})
											.fail(function( jqXHR ){
												AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
												AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
										    })
											.done(function ( widgets_data ) {

												// 5. Fail - Import Widgets.
												if( false === widgets_data.success ) {
													AstraSitesAdmin._importFailMessage( widgets_data.data );
													AstraSitesAdmin._log( widgets_data.data );

												} else {
													
													// 5. Pass - Import Widgets.
													AstraSitesAdmin._log( astraSitesAdmin.log.importWidgetsSuccess );
													$('.button-hero.astra-demo-import').text( 'Importing Complete...' );

													// 6. Import Complete.
													$.ajax({
														url  : astraSitesAdmin.ajaxurl,
														type : 'POST',
														data : {
															action    : 'astra-sites-import-end',
															demo_data : JSON.stringify( demo_data ),
														}
													})
													.fail(function( jqXHR ){
														AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
														AstraSitesAdmin._log( jqXHR.status + ' ' + jqXHR.statusText + '<br/><br/>' + astraSitesAdmin.log.serverConfiguration );
												    })
													.done(function ( demo_data ) {

														// 6. Fail - Import Complete.
														if( false === demo_data.success ) {
															AstraSitesAdmin._importFailMessage( demo_data.data );
															AstraSitesAdmin._log( demo_data.data );
														} else {
															
															// 6. Pass - Import Complete.
															AstraSitesAdmin._importSuccessMessage( demo_data );
															AstraSitesAdmin._log( astraSitesAdmin.log.success + ' ' + astraSitesAdmin.siteURL );
														}
													});
												}
											});
										}
									});
								}
							});
						}
					});
				}
			
			});

		},

		/**
		 * Collapse Sidebar.
		 */
		_collapse: function() {
			event.preventDefault();

			overlay = jQuery('.wp-full-overlay');

			if (overlay.hasClass('expanded')) {
				overlay.removeClass('expanded');
				overlay.addClass('collapsed');
				return;
			}

			if (overlay.hasClass('collapsed')) {
				overlay.removeClass('collapsed');
				overlay.addClass('expanded');
				return;
			}
		},

		/**
		 * Previous Theme.
		 */
		_previousTheme: function (event) {
			event.preventDefault();

			currentDemo = jQuery('.theme-preview-on');
			currentDemo.removeClass('theme-preview-on');
			prevDemo = currentDemo.prev('.theme');
			prevDemo.addClass('theme-preview-on');

			AstraSitesAdmin._renderDemoPreview(prevDemo);
		},

		/**
		 * Next Theme.
		 */
		_nextTheme: function (event) {
			event.preventDefault();
			currentDemo = jQuery('.theme-preview-on')
			currentDemo.removeClass('theme-preview-on');
			nextDemo = currentDemo.next('.theme');
			nextDemo.addClass('theme-preview-on');

			AstraSitesAdmin._renderDemoPreview( nextDemo );
		},

		/**
		 * Individual Site Preview
		 *
		 * On click on image, more link & preview button.
		 */
		_preview: function( event ) {

			event.preventDefault();

			var self = jQuery(this).parents('.theme');
			self.addClass('theme-preview-on');

			jQuery('html').addClass('astra-site-preview-on');

			AstraSitesAdmin._renderDemoPreview( self );
		},

		/**
		 * Check Next Previous Buttons.
		 */
		_checkNextPrevButtons: function() {
			currentDemo = jQuery('.theme-preview-on');
			nextDemo = currentDemo.nextAll('.theme').length;
			prevDemo = currentDemo.prevAll('.theme').length;

			if (nextDemo == 0) {
				jQuery('.next-theme').addClass('disabled');
			} else if (nextDemo != 0) {
				jQuery('.next-theme').removeClass('disabled');
			}

			if (prevDemo == 0) {
				jQuery('.previous-theme').addClass('disabled');
			} else if (prevDemo != 0) {
				jQuery('.previous-theme').removeClass('disabled');
			}

			return;
		},

		/**
		 * Render Demo Preview
		 */
		_renderDemoPreview: function(anchor) {

			var demoId             	   = anchor.data('id') || '',
				apiURL                 = anchor.data('demo-api') || '',
				demoType               = anchor.data('demo-type') || '',
				demoURL                = anchor.data('demo-url') || '',
				screenshot             = anchor.data('screenshot') || '',
				demo_name              = anchor.data('demo-name') || '',
				demo_slug              = anchor.data('demo-slug') || '',
				content                = anchor.data('content') || '',
				requiredPlugins        = anchor.data('required-plugins') || '',
				astraSiteOptions       = anchor.find('.astra-site-options').val() || '';
				astraEnabledExtensions = anchor.find('.astra-enabled-extensions').val() || '';

			AstraSitesAdmin._log( astraSitesAdmin.log.preview + ' "' + demo_name + '" URL : ' + demoURL );

			var template = wp.template('astra-site-preview');

			templateData = [{
				id                       : demoId,
				astra_demo_type          : demoType,
				astra_demo_url           : demoURL,
				demo_api                 : apiURL,
				screenshot               : screenshot,
				demo_name                : demo_name,
				slug                     : demo_slug,
				content                  : content,
				required_plugins         : JSON.stringify(requiredPlugins),
				astra_site_options       : astraSiteOptions,
				astra_enabled_extensions : astraEnabledExtensions,
			}];

			// delete any earlier fullscreen preview before we render new one.
			jQuery('.theme-install-overlay').remove();

			jQuery('#astra-sites-menu-page').append(template(templateData[0]));
			jQuery('.theme-install-overlay').css('display', 'block');
			AstraSitesAdmin._checkNextPrevButtons();

			var desc       = jQuery('.theme-details');
			var descHeight = parseInt( desc.outerHeight() );
			var descBtn    = jQuery('.theme-details-read-more');

			if( $.isArray( requiredPlugins ) ) {

				if( descHeight >= 55 ) {

					// Show button.
					descBtn.css( 'display', 'inline-block' );

					// Set height upto 3 line.
					desc.css( 'height', 57 );

					// Button Click.
					descBtn.click(function(event) {

						if( descBtn.hasClass('open') ) {
							desc.animate({ height: 57 },
								300, function() {
								descBtn.removeClass('open');
								descBtn.html( astraSitesAdmin.strings.DescExpand );
							});
						} else {
							desc.animate({ height: descHeight },
								300, function() {
								descBtn.addClass('open');
								descBtn.html( astraSitesAdmin.strings.DescCollapse );
							});
						}

					});
				}

				// or
				var $pluginsFilter    = jQuery( '#plugin-filter' ),
					data 			= {
										_ajax_nonce		 : astraSitesAdmin._ajax_nonce,
										required_plugins : requiredPlugins
									};

				jQuery('.required-plugins').addClass('loading').html('<span class="spinner is-active"></span>');

				wp.ajax.post( 'astra-required-plugins', data ).done( function( response ) {

					// Remove loader.
					jQuery('.required-plugins').removeClass('loading').html('');

					/**
					 * Count remaining plugins.
					 * @type number
					 */
					var remaining_plugins = 0;

					/**
					 * Not Installed
					 *
					 * List of not installed required plugins.
					 */
					if ( typeof response.notinstalled !== 'undefined' ) {

						// Add not have installed plugins count.
						remaining_plugins += parseInt( response.notinstalled.length );

						jQuery( response.notinstalled ).each(function( index, plugin ) {

							var output  = '<div class="plugin-card ';
								output += ' 		plugin-card-'+plugin.slug+'"';
								output += ' 		data-slug="'+plugin.slug+'"';
								output += ' 		data-init="'+plugin.init+'">';
								output += '	<span class="title">'+plugin.name+'</span>';
								output += '	<button class="button install-now"';
								output += '			data-init="' + plugin.init + '"';
								output += '			data-slug="' + plugin.slug + '"';
								output += '			data-name="' + plugin.name + '">';
								output += 	wp.updates.l10n.installNow;
								output += '	</button>';
								// output += '	<span class="dashicons-no dashicons"></span>';
								output += '</div>';

							jQuery('.required-plugins').append(output);

						});
					}

					/**
					 * Inactive
					 *
					 * List of not inactive required plugins.
					 */
					if ( typeof response.inactive !== 'undefined' ) {

						// Add inactive plugins count.
						remaining_plugins += parseInt( response.inactive.length );

						jQuery( response.inactive ).each(function( index, plugin ) {

							var output  = '<div class="plugin-card ';
								output += ' 		plugin-card-'+plugin.slug+'"';
								output += ' 		data-slug="'+plugin.slug+'"';
								output += ' 		data-init="'+plugin.init+'">';
								output += '	<span class="title">'+plugin.name+'</span>';
								output += '	<button class="button activate-now button-primary"';
								output += '		data-init="' + plugin.init + '"';
								output += '		data-slug="' + plugin.slug + '"';
								output += '		data-name="' + plugin.name + '">';
								output += 	wp.updates.l10n.activatePlugin;
								output += '	</button>';
								// output += '	<span class="dashicons-no dashicons"></span>';
								output += '</div>';

							jQuery('.required-plugins').append(output);

						});
					}

					/**
					 * Active
					 *
					 * List of not active required plugins.
					 */
					if ( typeof response.active !== 'undefined' ) {

						jQuery( response.active ).each(function( index, plugin ) {

							var output  = '<div class="plugin-card ';
								output += ' 		plugin-card-'+plugin.slug+'"';
								output += ' 		data-slug="'+plugin.slug+'"';
								output += ' 		data-init="'+plugin.init+'">';
								output += '	<span class="title">'+plugin.name+'</span>';
								output += '	<button class="button disabled"';
								output += '			data-slug="' + plugin.slug + '"';
								output += '			data-name="' + plugin.name + '">';
								output += astraSitesAdmin.strings.btnActive;
								output += '	</button>';
								// output += '	<span class="dashicons-yes dashicons"></span>';
								output += '</div>';

							jQuery('.required-plugins').append(output);

						});
					}

					/**
					 * Enable Demo Import Button
					 * @type number
					 */
					astraSitesAdmin.requiredPlugins = response;
					AstraSitesAdmin._enable_demo_import_button();

				} );

			} else {

				// Enable Demo Import Button
				AstraSitesAdmin._enable_demo_import_button( demoType );
				jQuery('.required-plugins-wrap').remove();
			}

			return;
		},

		/**
		 * Enable Demo Import Button.
		 */
		_enable_demo_import_button: function( type = 'free' ) {

			switch( type ) {

				case 'free':
							var all_buttons      = parseInt( jQuery( '.plugin-card .button' ).length ) || 0,
								disabled_buttons = parseInt( jQuery( '.plugin-card .button.disabled' ).length ) || 0;

							if( all_buttons === disabled_buttons ) {

								jQuery('.astra-demo-import')
									.removeAttr('data-import')
									.removeClass('installing updating-message')
									.addClass('button-primary')
									.text( astraSitesAdmin.strings.importDemo );
							}

					break;

				case 'upgrade':
							var demo_slug = jQuery('.wp-full-overlay-header').attr('data-demo-slug');

							jQuery('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraSitesAdmin.getUpgradeURL + demo_slug )
									.text( astraSitesAdmin.getUpgradeText )
									.append('<i class="dashicons dashicons-external"></i>');
					break;

				default:
							var demo_slug = jQuery('.wp-full-overlay-header').attr('data-demo-slug');

							jQuery('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraSitesAdmin.getProURL )
									.text( astraSitesAdmin.getProText )
									.append('<i class="dashicons dashicons-external"></i>');
					break;
			}

		},

		/**
		 * Update Page Count.
		 */
		_updatedPagedCount: function() {
			paged = parseInt(jQuery('body').attr('data-astra-demo-paged'));
			jQuery('body').attr('data-astra-demo-paged', paged + 1);
			window.setTimeout(function () {
				jQuery('body').data('scrolling', false);
			}, 800);
		},

		/**
		 * Reset Page Count.
		 */
		_resetPagedCount: function() {

			$('body').addClass('loading-content');
			$('body').attr('data-astra-demo-last-request', '1');
			$('body').attr('data-astra-demo-paged', '1');
			$('body').attr('data-astra-demo-search', '');
			$('body').attr('data-scrolling', false);

		},

		/**
		 * Remove plugin from the queue.
		 */
		_removePluginFromQueue: function( removeItem, pluginsList ) {
			return jQuery.grep(pluginsList, function( value ) {
				return value.slug != removeItem;
			});
		}

	};

	/**
	 * Initialize AstraSitesAdmin
	 */
	$(function(){
		AstraSitesAdmin.init();
	});

})(jQuery);