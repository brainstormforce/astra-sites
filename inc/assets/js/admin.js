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
var AstraSitesAjaxQueue = (function($) {

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
		    if( $.inArray(opt, requests) > -1 )
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

		        $.ajax(requests[0]);

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

}(jQuery));

(function($){

	AstraSites = {

		_ref: null,

		_iconUploader: null,

		init: function()
		{
			this._bind();
			this._resetPagedCount();
			this._initial_load_demos();
		},

		/**
		 * Binds events for the Astra Sites.
		 *
		 * @since 1.0.1
		 * @access private
		 * @method _bind
		 */
		_bind: function()
		{
			$( document ).on('scroll', AstraSites._scroll);
			$( document ).on('click', '.astra-demo-import', AstraSites._importDemo);
			$( document ).on('click', '.install-now', AstraSites._installNow);
			$( document ).on('click', '.theme-browser .theme-screenshot, .theme-browser .more-details, .theme-browser .install-theme-preview', AstraSites._preview);
			$( document ).on('click', '.collapse-sidebar', AstraSites._collapse);
			$( document ).on('click', '.filter-links li a', AstraSites._filter);
			$( document ).on('click', '.activate-now', AstraSites._activateNow);
			$( document ).on('click', '.close-full-overlay', AstraSites._fullOverlay);
			$( document ).on('click', '.next-theme', AstraSites._nextTheme);
			$( document ).on('click', '.previous-theme', AstraSites._previousTheme);
			$( document ).on('keyup input', '#wp-filter-search-input', AstraSites._serach);
			$( document ).on('wp-plugin-installing', AstraSites._pluginInstalling);
			$( document ).on('wp-plugin-install-error', AstraSites._installError);
			$( document ).on('wp-plugin-install-success', AstraSites._installSuccess);
		},

		/**
		 * Previous Theme.
		 */
		_previousTheme: function (event) {
			event.preventDefault();

			currentDemo = $('.theme-preview-on');
			currentDemo.removeClass('theme-preview-on');
			prevDemo = currentDemo.prev('.theme');
			prevDemo.addClass('theme-preview-on');

			AstraSites._renderDemoPreview(prevDemo);
		},

		_fullOverlay: function (event) {
			event.preventDefault();

			$('.theme-install-overlay').css('display', 'none');
			$('.theme-install-overlay').remove();
			$('.theme-preview-on').removeClass('theme-preview-on');
			$('html').removeClass('astra-site-preview-on');
		},

		/**
		 * Next Theme.
		 */
		_nextTheme: function (event) {
			event.preventDefault();
			currentDemo = $('.theme-preview-on');
			currentDemo.removeClass('theme-preview-on');
			nextDemo = currentDemo.next('.theme');
			nextDemo.addClass('theme-preview-on');

			AstraSites._renderDemoPreview( nextDemo );
		},

		/**
		 * Plugin Installation Error.
		 */
		_installError: function( event, response ) {

			var $card = $( '.plugin-card-' + response.slug );

			$card
				.addClass( 'button-primary' )
				.html( wp.updates.l10n.installNow );
		},

		_installSuccess: function( event, response ) {

			event.preventDefault();

			var $message     = $( '.plugin-card-' + response.slug ).find( '.button' );
			var $siteOptions = $( '.wp-full-overlay-header').find('.astra-site-options').val();
			var $enabledExtensions = $( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

			// Transform the 'Install' button into an 'Activate' button.
			var $init = $message.data('init');

			$message.removeClass( 'install-now installed button-disabled updated-message' )
				.addClass('updating-message')
				.html( astraDemo.strings.btnActivating );

			// Reset not installed plugins list.
			var pluginsList = astraDemo.requiredPlugins.notinstalled;
			astraDemo.requiredPlugins.notinstalled = AstraSites._removePluginFromQueue( response.slug, pluginsList );

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				$.ajax({
					url: astraDemo.ajaxurl,
					type: 'POST',
					data: {
						'action'	: 'astra-required-plugin-activate',
						'init'		: $init,
						'options'	: $siteOptions,
						'enabledExtensions' : $enabledExtensions,
					},
				})
				.done(function (result) {

					if( result.success ) {

						var pluginsList = astraDemo.requiredPlugins.inactive;

						// Reset not installed plugins list.
						astraDemo.requiredPlugins.inactive = AstraSites._removePluginFromQueue( response.slug, pluginsList );

						$message.removeClass( 'button-primary install-now activate-now updating-message' )
							.attr('disabled', 'disabled')
							.addClass('disabled')
							.text( astraDemo.strings.btnActive );

						// Enable Demo Import Button
						AstraSites._enable_demo_import_button();

					} else {

						$message.removeClass( 'updating-message' );

					}

				});

			}, 1200 );

		},

		/**
		 * Render Demo Preview
		 */
		_activateNow: function( event ) {

			event.preventDefault();

			var $button = $( event.target ),
				$init 	= $button.data( 'init' ),
				$slug 	= $button.data( 'slug' );

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			$button.addClass('updating-message button-primary')
				.html( astraDemo.strings.btnActivating );

			var $siteOptions = $( '.wp-full-overlay-header').find('.astra-site-options').val();
			var $enabledExtensions = $( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

			$.ajax({
				url: astraDemo.ajaxurl,
				type: 'POST',
				data: {
					'action'	: 'astra-required-plugin-activate',
					'init'		: $init,
					'options' 	: $siteOptions,
					'enabledExtensions' 	: $enabledExtensions,
				},
			})
			.done(function (result) {

				if( result.success ) {

					var pluginsList = astraDemo.requiredPlugins.inactive;

					// Reset not installed plugins list.
					astraDemo.requiredPlugins.inactive = AstraSites._removePluginFromQueue( $slug, pluginsList );

					$button.removeClass( 'button-primary install-now activate-now updating-message' )
						.attr('disabled', 'disabled')
						.addClass('disabled')
						.text( astraDemo.strings.btnActive );

					// Enable Demo Import Button
					AstraSites._enable_demo_import_button();

				}

			})
			.fail(function () {
			});

		},

		_renderDemoPreview: function(anchor) {

			var demoId           = anchor.data('id') || '',
				apiURL           = anchor.data('demo-api') || '',
				demoType         = anchor.data('demo-type') || '',
				demoURL          = anchor.data('demo-url') || '',
				screenshot       = anchor.data('screenshot') || '',
				demo_name        = anchor.data('demo-name') || '',
				demo_slug        = anchor.data('demo-slug') || '',
				content          = anchor.data('content') || '',
				requiredPlugins  = anchor.data('required-plugins') || '',
				astraSiteOptions = anchor.find('.astra-site-options').val() || '';
				astraEnabledExtensions = anchor.find('.astra-enabled-extensions').val() || '';

			var template = wp.template('astra-demo-preview');

			templateData = [{
				id                       : demoId,
				astra_demo_type          : demoType,
				astra_demo_url           : demoURL,
				demo_api                 : apiURL,
				screenshot               : screenshot,
				demo_name                : demo_name,
				slug               		 : demo_slug,
				content                  : content,
				required_plugins        : JSON.stringify(requiredPlugins),
				astra_site_options       : astraSiteOptions,
				astra_enabled_extensions : astraEnabledExtensions,
			}];

			// delete any earlier fullscreen preview before we render new one.
			$('.theme-install-overlay').remove();

			$('#astra-sites-menu-page').append(template(templateData[0]));
			$('.theme-install-overlay').css('display', 'block');
			AstraSites._checkNextPrevButtons();

			var desc       = $('.theme-details');
			var descHeight = parseInt( desc.outerHeight() );
			var descBtn    = $('.theme-details-read-more');

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
								descBtn.html( astraDemo.strings.DescExpand );
							});
						} else {
							desc.animate({ height: descHeight },
								300, function() {
								descBtn.addClass('open');
								descBtn.html( astraDemo.strings.DescCollapse );
							});
						}

					});
				}

				// or
				var $pluginsFilter    = $( '#plugin-filter' ),
					data 			= {
										_ajax_nonce		 : astraDemo._ajax_nonce,
										required_plugins : requiredPlugins
									};

				$('.required-plugins').addClass('loading').html('<span class="spinner is-active"></span>');

				wp.ajax.post( 'astra-required-plugins', data ).done( function( response ) {

					// Remove loader.
					$('.required-plugins').removeClass('loading').html('');

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

						$( response.notinstalled ).each(function( index, plugin ) {

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

							$('.required-plugins').append(output);

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

						$( response.inactive ).each(function( index, plugin ) {

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

							$('.required-plugins').append(output);

						});
					}

					/**
					 * Active
					 *
					 * List of not active required plugins.
					 */
					if ( typeof response.active !== 'undefined' ) {

						$( response.active ).each(function( index, plugin ) {

							var output  = '<div class="plugin-card ';
								output += ' 		plugin-card-'+plugin.slug+'"';
								output += ' 		data-slug="'+plugin.slug+'"';
								output += ' 		data-init="'+plugin.init+'">';
								output += '	<span class="title">'+plugin.name+'</span>';
								output += '	<button class="button disabled"';
								output += '			data-slug="' + plugin.slug + '"';
								output += '			data-name="' + plugin.name + '">';
								output += astraDemo.strings.btnActive;
								output += '	</button>';
								// output += '	<span class="dashicons-yes dashicons"></span>';
								output += '</div>';

							$('.required-plugins').append(output);

						});
					}

					/**
					 * Enable Demo Import Button
					 * @type number
					 */
					astraDemo.requiredPlugins = response;
					AstraSites._enable_demo_import_button();

				} );

			} else {

				// Enable Demo Import Button
				AstraSites._enable_demo_import_button( demoType );
				$('.required-plugins-wrap').remove();
			}

			return;
		},

		/**
		 * Check Next Previous Buttons.
		 */
		_checkNextPrevButtons: function() {
			currentDemo = $('.theme-preview-on');
			nextDemo = currentDemo.nextAll('.theme').length;
			prevDemo = currentDemo.prevAll('.theme').length;

			if (nextDemo == 0) {
				$('.next-theme').addClass('disabled');
			} else if (nextDemo != 0) {
				$('.next-theme').removeClass('disabled');
			}

			if (prevDemo == 0) {
				$('.previous-theme').addClass('disabled');
			} else if (prevDemo != 0) {
				$('.previous-theme').removeClass('disabled');
			}

			return;
		},

		/**
		 * Filter Demo by Category.
		 */
		_filter: function(event) {
			event.preventDefault();

			$this = $(this);
			$this.parent('li').siblings().find('.current').removeClass('current');
			$this.addClass('current');

			var astra_page_builder = $('.filter-links.astra-page-builder'),
				astra_category 	   = $('.filter-links.astra-category'),
				page_builder_id   	= astra_page_builder.find('.current').data('id'),
				category_id   		= astra_category.find('.current').data('id');

			AstraSites._resetPagedCount();

			paged = parseInt($('body').attr('data-astra-demo-paged'));

			$('body').addClass('loading-content');
			$('.theme-browser .theme').remove();
			$('.no-themes').remove();
			$('#wp-filter-search-input').val('');

			$.ajax({
				url: astraDemo.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'astra-list-sites',
					paged: paged,
					page_builder_id : page_builder_id,
					category_id : category_id,
				},
			})
			.done(function (demos) {

				$('.filter-count .count').text( demos.sites_count );
				$('body').removeClass('loading-content');

				if ( demos.sites_count > 0 ) {
					AstraSites._renderDemoGrid(demos.sites);
				} else {
					$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.searchNoFound+'</p>');
				}

			})
			.fail(function () {
				$('body').removeClass('loading-content');
				$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.responseError+'</p>');
			});

		},


		/**
		 * Search Site.
		 */
		_serach: function() {
			$this = $('#wp-filter-search-input').val();

			id = '';
			if ($this.length < 2) {
				id = 'all';
			}

			var astra_page_builder = $('.filter-links.astra-page-builder'),
				astra_category 	   = $('.filter-links.astra-category'),
				page_builder_id   	= astra_page_builder.find('.current').data('id'),
				category_id   		= astra_category.find('.current').data('id');


			window.clearTimeout(AstraSites._ref);
			AstraSites._ref = window.setTimeout(function () {
				AstraSites._ref = null;

				AstraSites._resetPagedCount();
				$('body').addClass('loading-content');
				$('.theme-browser .theme').remove();
				$('.no-themes').remove();
				$('body').attr('data-astra-demo-search', $this);

				$.ajax({
					url: astraDemo.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-list-sites',
						search: $this,
						page_builder_id : page_builder_id,
						category_id : category_id,
					},
				})
				.done(function (demos) {
					$('body').removeClass('loading-content');

					$('.filter-count .count').text( demos.sites_count );

					if ( demos.sites_count > 0 ) {
						AstraSites._renderDemoGrid(demos.sites);
					} else {
						$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.searchNoFound+'</p>');
					}

				})
				.fail(function () {
					$('body').removeClass('loading-content');
					$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.responseError+'.</p>');
				});


			}, 500);

		},

		/**
		 * Collapse Sidebar.
		 */
		_collapse: function(event) {
			event.preventDefault();

			overlay = $('.wp-full-overlay');

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
		 * Individual Site Preview
		 *
		 * On click on image, more link & preview button.
		 */
		_preview: function(event) {

			event.preventDefault();

			$this = $(this).parents('.theme');
			$this.addClass('theme-preview-on');

			$('html').addClass('astra-site-preview-on');

			AstraSites._renderDemoPreview($this);
		},

		_scroll: function(event) {

			var scrollDistance = $(window).scrollTop();

			var themesBottom = Math.abs($(window).height() - $('.themes').offset().top - $('.themes').height());
			themesBottom = themesBottom * 20 / 100;

			ajaxLoading = $('body').data('scrolling');

			if (scrollDistance > themesBottom && ajaxLoading == false) {
				AstraSites._updatedPagedCount();

				$('body').data('scrolling', true);

				var body   = $('body'),
					id     = body.attr('data-astra-site-category'),
					search = body.attr('data-astra-demo-search'),
					paged  = body.attr('data-astra-demo-paged');

				if (search !== '') {
					id = '';
				} else {
					search = '';
				}

				$('.no-themes').remove();

				var astra_page_builder = $('.filter-links.astra-page-builder'),
				astra_category 	   = $('.filter-links.astra-category'),
				page_builder_id   	= astra_page_builder.find('.current').data('id'),
				category_id   		= astra_category.find('.current').data('id');

				$.ajax({
					url: astraDemo.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-list-sites',
						paged: paged,
						search: search,
						page_builder_id : page_builder_id,
						category_id : category_id,
					},
				})
				.done(function (demos) {
					$('body').removeClass('loading-content');
					if ( demos.sites_count > 0 ) {
						AstraSites._renderDemoGrid(demos.sites);
					}
				})
				.fail(function () {
					$('body').removeClass('loading-content');
					$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.responseError+'</p>');
				});

			}
		},

		_installNow: function(event)
		{
			event.preventDefault();

			var $button 	= $( event.target ),
				$document   = $(document);

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

			wp.updates.installPlugin( {
				slug: $button.data( 'slug' )
			} );
		},

		/**
		 * Update Page Count.
		 */
		_updatedPagedCount: function() {
			paged = parseInt($('body').attr('data-astra-demo-paged'));
			$('body').attr('data-astra-demo-paged', paged + 1);
			window.setTimeout(function () {
				$('body').data('scrolling', false);
			}, 800);
		},

		_resetPagedCount: function() {

			categoryId = $('.astra-category.filter-links li .current').data('id');
			$('body').attr('data-astra-demo-paged', '1');
			$('body').attr('data-astra-site-category', categoryId);
			$('body').attr('data-astra-demo-search', '');
			$('body').attr('data-scrolling', false);
			$('body').attr( 'data-required-plugins', 0 );

		},

		_initial_load_demos: function() {

			$('body').addClass('loading-content');

			$.ajax({
				url: astraDemo.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action   : 'astra-list-sites',
					paged    : '1',
				},
			})
			.done(function (demos) {

				$('body').removeClass('loading-content');
				$('.filter-count .count').text( demos.sites_count );

				// Has sites?
				if ( demos.sites_count > 0 ) {
					AstraSites._renderDemoGrid( demos.sites );

				// Something is wrong in API request.
				} else {
					var template = wp.template('astra-no-demos');
					$('.themes').append( template );
				}

			})
			.fail(function () {
				$('body').removeClass('loading-content');
				$('.spinner').after('<p class="no-themes" style="display:block;">'+astraDemo.strings.responseError+'</p>');
			});
		},

		/**
		 * Render Demo Grid.
		 */
		_renderDemoGrid: function(demos) {

			$.each(demos, function (index, demo) {

				id               = demo.id;
				content          = demo.content;
				demo_api         = demo.demo_api;
				demo_name        = demo.title;
				demo_slug        = demo.slug;
				screenshot       = demo.featured_image_url;
				astra_demo_url   = demo.astra_demo_url;
				astra_demo_type  = demo.astra_demo_type;
				requiredPlugins  = demo.required_plugins;
				status  = demo.status;
				astraSiteOptions = demo.astra_site_options || '';
				astraEnabledExtensions = demo.astra_enabled_extensions || '';

				templateData = [{
					id: id,
					astra_demo_type: astra_demo_type,
					status: status,
					astra_demo_url: astra_demo_url,
					demo_api: demo_api,
					screenshot: screenshot,
					demo_name: demo_name,
					slug: demo_slug,
					content: content,
					required_plugins: requiredPlugins,
					astra_site_options: astraSiteOptions,
					astra_enabled_extensions: astraEnabledExtensions
				}];

				var template = wp.template('astra-single-demo');
				$('.themes').append(template(templateData[0]));
			});

		},

		_pluginInstalling: function(event, args) {
			event.preventDefault();

			var $card = $( '.plugin-card-' + args.slug );
			var $button = $card.find( '.button' );

			$card.addClass('updating-message');
			$button.addClass('already-started');

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
			var $this 	= $(this),
				$theme  = $this.closest('.astra-sites-preview').find('.wp-full-overlay-header'),
				apiURL  = $theme.data('demo-api') || '',
				plugins = $theme.data('required-plugins');

			var disabled = $this.attr('data-import');

			if ( typeof disabled !== 'undefined' && disabled === 'disabled' ) {

				$this.addClass('updating-message')
					.text( wp.updates.l10n.installing );

				/**
				 * Process Bulk Plugin Install & Activate
				 */
				AstraSites._bulkPluginInstallActivate();

				return;
			}

			// Proceed?
			if( ! confirm( astraDemo.strings.importWarning ) ) {
				return;
			}

			$('.astra-demo-import').attr('data-import', 'disabled')
				.addClass('updating-message installing')
				.text( astraDemo.strings.importingDemo );

			$this.closest('.theme').focus();

			$.ajax({
				url: astraDemo.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'astra-import-demo',
					api_url: apiURL
				},
			})
			.done(function ( demos ) {

				// Success?
				if( demos.success ) {
					$('.astra-demo-import').removeClass('updating-message installing')
						.removeAttr('data-import')
						.addClass('view-site')
						.removeClass('astra-demo-import')
						.text( astraDemo.strings.viewSite )
						.attr('target', '_blank')
						.append('<i class="dashicons dashicons-external"></i>')
						.attr('href', astraDemo.siteURL );

				} else {

					var output  = '<div class="astra-api-error notice notice-error notice-alt is-dismissible">';
						output += '	<p>'+demos.message+'</p>';
						output += '	<button type="button" class="notice-dismiss">';
						output += '		<span class="screen-reader-text">'+commonL10n.dismiss+'</span>';
						output += '	</button>';
						output += '</div>';

					$('.install-theme-info').prepend( output );

					// !important to add trigger.
					// Which reinitialize the dismiss error message events.
					$(document).trigger('wp-updates-notice-added');
				}

			})
			.fail(function ( demos ) {
				$('.astra-demo-import').removeClass('updating-message installing')
					.removeAttr('data-import')
					.addClass('view-site')
					.removeClass('astra-demo-import')
					.attr('target', '_blank')
					.attr('href', astraDemo.strings.importFailedURL );

				$('.wp-full-overlay-header .view-site').text( astraDemo.strings.importFailedBtnSmall ).append('<i class="dashicons dashicons-external"></i>');
				$('.footer-import-button-wrap .view-site').text( astraDemo.strings.importFailedBtnLarge ).append('<i class="dashicons dashicons-external"></i>');
			});
		},

		_bulkPluginInstallActivate: function()
		{
			if( 0 === astraDemo.requiredPlugins.length ) {
				return;
			}

			$('.required-plugins')
				.find('.install-now')
				.addClass( 'updating-message' )
				.removeClass( 'install-now' )
				.text( wp.updates.l10n.installing );

			$('.required-plugins')
				.find('.activate-now')
				.addClass('updating-message')
				.removeClass( 'activate-now' )
				.html( astraDemo.strings.btnActivating );

			var not_installed 	 = astraDemo.requiredPlugins.notinstalled || '';
			var activate_plugins = astraDemo.requiredPlugins.inactive || '';

			// First Install Bulk.
			if( not_installed.length > 0 ) {
				AstraSites._installAllPlugins( not_installed );
			}

			// Second Activate Bulk.
			if( activate_plugins.length > 0 ) {
				AstraSites._activateAllPlugins( activate_plugins );
			}

		},

		/**
		 * Install All Plugins.
		 */
		_installAllPlugins: function( not_installed ) {

			$.each( not_installed, function(index, single_plugin) {

				var $card   = $( '.plugin-card-' + single_plugin.slug ),
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
		 * Activate All Plugins.
		 */
		_activateAllPlugins: function( activate_plugins ) {

			// Process of cloud templates - (download, remove & fetch).
			AstraSitesAjaxQueue.run();

			$.each( activate_plugins, function(index, single_plugin) {

				var $card    	 = $( '.plugin-card-' + single_plugin.slug ),
					$button  	 = $card.find('.button'),
					$siteOptions = $( '.wp-full-overlay-header').find('.astra-site-options').val(),
					$enabledExtensions = $( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

				$button.addClass('updating-message');

				AstraSitesAjaxQueue.add({
					url: astraDemo.ajaxurl,
					type: 'POST',
					data: {
						'action'	: 'astra-required-plugin-activate',
						'init'		: single_plugin.init,
						'options'	: $siteOptions,
						'enabledExtensions' : $enabledExtensions,
					},
					success: function( result ){

						if( result.success ) {

							var $card = $( '.plugin-card-' + single_plugin.slug );
							var $button = $card.find( '.button' );
							if( ! $button.hasClass('already-started') ) {
								var pluginsList = astraDemo.requiredPlugins.inactive;

								// Reset not installed plugins list.
								astraDemo.requiredPlugins.inactive = AstraSites._removePluginFromQueue( single_plugin.slug, pluginsList );
							}

							$button.removeClass( 'button-primary install-now activate-now updating-message' )
								.attr('disabled', 'disabled')
								.addClass('disabled')
								.text( astraDemo.strings.btnActive );

							// Enable Demo Import Button
							AstraSites._enable_demo_import_button();
						}
					}
				});
			});
		},

		/**
		 * Enable Demo Import Button.
		 */
		_enable_demo_import_button: function( type ) {
			var demo_slug;

			if ( ! type ) {
				type = 'free';
			}

			switch( type ) {

				case 'free':
							var all_buttons      = parseInt( $( '.plugin-card .button' ).length ) || 0,
								disabled_buttons = parseInt( $( '.plugin-card .button.disabled' ).length ) || 0;

							if( all_buttons === disabled_buttons ) {

								$('.astra-demo-import')
									.removeAttr('data-import')
									.removeClass('updating-message')
									.addClass('button-primary')
									.text( astraDemo.strings.importDemo );
							}

					break;

				case 'upgrade':
							demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

							$('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraDemo.getUpgradeURL + demo_slug )
									.text( astraDemo.getUpgradeText )
									.append('<i class="dashicons dashicons-external"></i>');
					break;

				default:
							demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

							$('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraDemo.getProURL )
									.text( astraDemo.getProText )
									.append('<i class="dashicons dashicons-external"></i>');
					break;
			}

		},

		/**
		 * Remove plugin from the queue.
		 */
		_removePluginFromQueue: function( removeItem, pluginsList ) {
			return $.grep(pluginsList, function( value ) {
				return value.slug != removeItem;
			});
		}

	};

	/**
	 * Initialize AstraSites
	 */
	$(function(){
		AstraSites.init();
	});

})(jQuery);
