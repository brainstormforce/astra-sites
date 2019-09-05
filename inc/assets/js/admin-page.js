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

	var AstraSSEImport = {
		complete: {
			posts: 0,
			media: 0,
			users: 0,
			comments: 0,
			terms: 0,
		},

		updateDelta: function (type, delta) {
			this.complete[ type ] += delta;

			var self = this;
			requestAnimationFrame(function () {
				self.render();
			});
		},
		updateProgress: function ( type, complete, total ) {
			var text = complete + '/' + total;

			if( 'undefined' !== type && 'undefined' !== text ) {
				total = parseInt( total, 10 );
				if ( 0 === total || isNaN( total ) ) {
					total = 1;
				}
				var percent = parseInt( complete, 10 ) / total;
				var progress     = Math.round( percent * 100 ) + '%';
				var progress_bar = percent * 100;

				if( progress_bar <= 100 ) {
					var process_bars = document.getElementsByClassName( 'astra-site-import-process' );
					for ( var i = 0; i < process_bars.length; i++ ) {
						process_bars[i].value = progress_bar;
					}
					AstraSitesAdmin._log_title( 'Importing Content.. ' + progress );
				}
			}
		},
		render: function () {
			var types = Object.keys( this.complete );
			var complete = 0;
			var total = 0;

			for (var i = types.length - 1; i >= 0; i--) {
				var type = types[i];
				this.updateProgress( type, this.complete[ type ], this.data.count[ type ] );

				complete += this.complete[ type ];
				total += this.data.count[ type ];
			}

			this.updateProgress( 'total', complete, total );
		}
	};

	AstraSitesAdmin = {

		reset_remaining_posts: 0,
		reset_remaining_wp_forms: 0,
		reset_remaining_terms: 0,
		reset_processed_posts: 0,
		reset_processed_wp_forms: 0,
		reset_processed_terms: 0,
		site_imported_data: null,

		backup_taken: false,

		current_site: [],
		current_screen: '',

		templateData: {},

		log_file        : '',
		customizer_data : '',
		wxr_url         : '',
		wpforms_url     : '',
		options_data    : '',
		widgets_data    : '',
		import_start_time  : '',
		import_end_time    : '',

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
					console.log('%c ' + JSON.stringify( data ) + ' ' + time, 'background: #ededed; color: #444');
				} else {
					console.log('%c ' + data + ' ' + time, 'background: #ededed; color: #444');
				}


			}
		},

		_log_title: function( data, append ) {

			var markup = '<p>' +  data + '</p>';
			if (typeof data == 'object' ) {
				var markup = '<p>'  + JSON.stringify( data ) + '</p>';
			}

			if ( append ) {
				$('.current-importing-status-title').append( markup );
			} else {
				$('.current-importing-status-title').html( markup );
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
			$( document ).on( 'click'					 , '.astra-sites-reset-data .checkbox', AstraSitesAdmin._toggle_reset_notice );
			$( document ).on( 'click'					 , '.page-builders li', AstraSitesAdmin._toggle_reset_notice );
			$( document ).on('click'                     , '#astra-sites-welcome-form .submit', AstraSitesAdmin._show_page_builder_notice);
			$( document ).on('click'                     , '#astra-sites-welcome-form li', AstraSitesAdmin._show_next_button);
			$( document ).on('change'                    , '#astra-sites-welcome-form-inline select', AstraSitesAdmin._change_page_builder);
			$( document ).on('click'                     , '.astra-sites-tooltip-icon', AstraSitesAdmin._toggle_tooltip);
			$( document ).on('click'                     , '.astra-sites-advanced-options-button', AstraSitesAdmin._toggle_advanced_options);

			$( document ).on('click'                     , '.astra-import-settings', AstraSitesAdmin._import_settings);
			$( document ).on('click'					 , '.devices button', AstraSitesAdmin._previewDevice);
			$( document ).on('click'                     , '.theme-browser .theme-screenshot, .theme-browser .more-details, .theme-browser .install-theme-preview', AstraSitesAdmin._preview);
			$( document ).on('click'                     , '.next-theme', AstraSitesAdmin._nextTheme);
			$( document ).on('click'                     , '.previous-theme', AstraSitesAdmin._previousTheme);
			$( document ).on('click'                     , '.collapse-sidebar', AstraSitesAdmin._collapse);
			$( document ).on('click'                     , '.astra-demo-import', AstraSitesAdmin._importDemo);
			
			$( document ).on('astra-sites-install-and-activate-required-plugins-done'       , AstraSitesAdmin._process_import );

			$( document ).on('click'                     , '.install-now', AstraSitesAdmin._installNow);
			$( document ).on('click'                     , '.close-full-overlay', AstraSitesAdmin._fullOverlay);
			$( document ).on('click'                     , '.activate-now', AstraSitesAdmin._activateNow);
			$( document ).on('wp-plugin-installing'      , AstraSitesAdmin._pluginInstalling);
			$( document ).on('wp-plugin-install-error'   , AstraSitesAdmin._installError);
			$( document ).on('wp-plugin-install-success' , AstraSitesAdmin._installSuccess);

			$( document ).on( 'astra-sites-import-set-site-data-done'   		, AstraSitesAdmin._resetData );
			$( document ).on( 'astra-sites-reset-data'							, AstraSitesAdmin._backup_before_rest_options );
			$( document ).on( 'astra-sites-backup-settings-before-reset-done'	, AstraSitesAdmin._reset_customizer_data );
			$( document ).on( 'astra-sites-reset-customizer-data-done'			, AstraSitesAdmin._reset_site_options );
			$( document ).on( 'astra-sites-reset-site-options-done'				, AstraSitesAdmin._reset_widgets_data );
			$( document ).on( 'astra-sites-reset-widgets-data-done'				, AstraSitesAdmin._reset_terms );
			$( document ).on( 'astra-sites-delete-terms-done'					, AstraSitesAdmin._reset_wp_forms );
			$( document ).on( 'astra-sites-delete-wp-forms-done'				, AstraSitesAdmin._reset_posts );

			$( document ).on('astra-sites-reset-data-done'       		   , AstraSitesAdmin._recheck_backup_options );
			$( document ).on('astra-sites-backup-settings-done'       	   , AstraSitesAdmin._importWPForms );
			$( document ).on('astra-sites-import-wpforms-done'       	   , AstraSitesAdmin._importCustomizerSettings );
			$( document ).on('astra-sites-import-customizer-settings-done' , AstraSitesAdmin._importXML );
			$( document ).on('astra-sites-import-xml-done'                 , AstraSitesAdmin._importSiteOptions );
			$( document ).on('astra-sites-import-options-done'             , AstraSitesAdmin._importWidgets );
			$( document ).on('astra-sites-import-widgets-done'             , AstraSitesAdmin._importEnd );

		},

		_show_next_button: function() {
			$( this ).parents('.page-builders').find('img').removeClass('wp-ui-highlight');
			$( this ).find('img').addClass('wp-ui-highlight');

			$('#submit').parent().removeClass('submit');
			$('#submit').removeClass('disabled');
			$('.astra-sites-page-builder-notice').hide();
		},

		_show_page_builder_notice: function() {
			$('.astra-sites-page-builder-notice').show();
		},

		_change_page_builder: function() {
		    $(this).closest('form').submit();
		},

		_toggle_tooltip: function( event ) {
			event.preventDefault();
			var tip_id = $( this ).data('tip-id') || '';
			if( tip_id && $( '#' + tip_id ).length ) {
				$( '#' + tip_id ).toggle();
			}
		},

		_toggle_advanced_options: function( event ) {
			event.preventDefault();
			$('.astra-sites-advanced-options').toggle();
		},

		_resetData: function( event ) {
			event.preventDefault();

			if ( $( '.astra-sites-reset-data' ).find('.checkbox').is(':checked') ) {
				$(document).trigger( 'astra-sites-reset-data' );
			} else {
				$(document).trigger( 'astra-sites-reset-data-done' );
			}
		},

		_reset_customizer_data: function() {
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-reset-customizer-data'
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Reseting Customizer Data..' );
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( data ) {
				AstraSitesAdmin._log_title( 'Complete Resetting Customizer Data..' );
				$(document).trigger( 'astra-sites-reset-customizer-data-done' );
			});
		},

		_reset_site_options: function() {
			// Site Options.
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-reset-site-options'
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Reseting Site Options..' );
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( data ) {
				AstraSitesAdmin._log_title( 'Complete Reseting Site Options..' );
				$(document).trigger( 'astra-sites-reset-site-options-done' );
			});			
		},

		_reset_widgets_data: function() {
			// Widgets.
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-reset-widgets-data'
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Reseting Widgets..' );
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( data ) {
				AstraSitesAdmin._log_title( 'Complete Reseting Widgets..' );
				$(document).trigger( 'astra-sites-reset-widgets-data-done' );
			});
		},

		_reset_posts: function() {
			if( AstraSitesAdmin.site_imported_data['reset_posts'].length ) {

				AstraSitesAdmin.reset_remaining_posts = AstraSitesAdmin.site_imported_data['reset_posts'].length;

				$.each( AstraSitesAdmin.site_imported_data['reset_posts'], function(index, post_id) {

					AstraSitesAdmin._log_title( 'Deleting Posts..' );

					AstraSitesAjaxQueue.add({
						url: astraSitesAdmin.ajaxurl,
						type: 'POST',
						data: {
							action  : 'astra-sites-delete-posts',
							post_id : post_id,
						},
						success: function( result ){

							if( AstraSitesAdmin.reset_processed_posts < AstraSitesAdmin.site_imported_data['reset_posts'].length ) {
								AstraSitesAdmin.reset_processed_posts+=1;
							}

							AstraSitesAdmin._log_title( 'Deleting Post ' + AstraSitesAdmin.reset_processed_posts + ' of ' + AstraSitesAdmin.site_imported_data['reset_posts'].length + '<br/>' + result.data );

							AstraSitesAdmin.reset_remaining_posts-=1;
							if( 0 == AstraSitesAdmin.reset_remaining_posts ) {
								$(document).trigger( 'astra-sites-delete-posts-done' );
								$(document).trigger( 'astra-sites-reset-data-done' );
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();

			} else {
				$(document).trigger( 'astra-sites-delete-posts-done' );
				$(document).trigger( 'astra-sites-reset-data-done' );
			}
		},

		_reset_wp_forms: function() {

			if( AstraSitesAdmin.site_imported_data['reset_wp_forms'].length ) {
				AstraSitesAdmin.reset_remaining_wp_forms = AstraSitesAdmin.site_imported_data['reset_wp_forms'].length;

				$.each( AstraSitesAdmin.site_imported_data['reset_wp_forms'], function(index, post_id) {
					AstraSitesAdmin._log_title( 'Deleting WP Forms..' );
					AstraSitesAjaxQueue.add({
						url: astraSitesAdmin.ajaxurl,
						type: 'POST',
						data: {
							action  : 'astra-sites-delete-wp-forms',
							post_id : post_id,
						},
						success: function( result ){

							if( AstraSitesAdmin.reset_processed_wp_forms < AstraSitesAdmin.site_imported_data['reset_wp_forms'].length ) {
								AstraSitesAdmin.reset_processed_wp_forms+=1;
							}

							AstraSitesAdmin._log_title( 'Deleting Form ' + AstraSitesAdmin.reset_processed_wp_forms + ' of ' + AstraSitesAdmin.site_imported_data['reset_wp_forms'].length + '<br/>' + result.data );

							AstraSitesAdmin.reset_remaining_wp_forms-=1;
							if( 0 == AstraSitesAdmin.reset_remaining_wp_forms ) {
								$(document).trigger( 'astra-sites-delete-wp-forms-done' );
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();

			} else {
				$(document).trigger( 'astra-sites-delete-wp-forms-done' );
			}
		},

		
		_reset_terms: function() {


			if( AstraSitesAdmin.site_imported_data['reset_terms'].length ) {
				AstraSitesAdmin.reset_remaining_terms = AstraSitesAdmin.site_imported_data['reset_terms'].length;

				$.each( AstraSitesAdmin.site_imported_data['reset_terms'], function(index, term_id) {
					AstraSitesAdmin._log_title( 'Deleting Terms..' );
					AstraSitesAjaxQueue.add({
						url: astraSitesAdmin.ajaxurl,
						type: 'POST',
						data: {
							action  : 'astra-sites-delete-terms',
							term_id : term_id,
						},
						success: function( result ){
							if( AstraSitesAdmin.reset_processed_terms < AstraSitesAdmin.site_imported_data['reset_terms'].length ) {
								AstraSitesAdmin.reset_processed_terms+=1;
							}

							AstraSitesAdmin._log_title( 'Deleting Term ' + AstraSitesAdmin.reset_processed_terms + ' of ' + AstraSitesAdmin.site_imported_data['reset_terms'].length + '<br/>' + result.data );

							AstraSitesAdmin.reset_remaining_terms-=1;
							if( 0 == AstraSitesAdmin.reset_remaining_terms ) {
								$(document).trigger( 'astra-sites-delete-terms-done' );
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();

			} else {
				$(document).trigger( 'astra-sites-delete-terms-done' );
			}

		},

		_toggle_reset_notice: function() {
			if ( $( this ).is(':checked') ) {
				$('#astra-sites-tooltip-reset-data').show();
			} else {
				$('#astra-sites-tooltip-reset-data').hide();
			}
		},

		_backup_before_rest_options: function() {
			AstraSitesAdmin._backupOptions( 'astra-sites-backup-settings-before-reset-done' );
			AstraSitesAdmin.backup_taken = true;
		},

		_recheck_backup_options: function() {
			AstraSitesAdmin._backupOptions( 'astra-sites-backup-settings-done' );
			AstraSitesAdmin.backup_taken = true;
		},

		_backupOptions: function( trigger_name ) {
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-backup-settings',
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Processing Customizer Settings Backup..' );
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
		    })
			.done(function ( data ) {

				// 1. Pass - Import Customizer Options.
				AstraSitesAdmin._log_title( 'Customizer Settings Backup Done..' );

				// Custom trigger.
				$(document).trigger( trigger_name );
			});
		},

		_import_settings: function( event ) {
			event.preventDefault();

			var btn = $(this);

			btn.addClass('updating-message');


			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				dataType: 'json',
				data : {
					action          : 'astra-sites-import-customizer-settings',
					customizer_data : AstraSitesAdmin.current_site['astra-site-customizer-data'],
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Importing Customizer Settings..' );
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( customizer_data ) {

				btn.removeClass( 'updating-message' );

				// 1. Fail - Import Customizer Options.
				if( false === customizer_data.success ) {
					AstraSitesAdmin._log_title( customizer_data.data );
				} else {

					// 1. Pass - Import Customizer Options.
					AstraSitesAdmin._log_title( 'Imported Customizer Settings..' );

					$(document).trigger( 'astra-sites-import-customizer-settings-done' );
				}
			});
		},

		/**
		 * 5. Import Complete.
		 */
		_importEnd: function( event ) {

			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				dataType: 'json',
				data : {
					action : 'astra-sites-import-end',
				},
				beforeSend: function() {
					AstraSitesAdmin._log_title( 'Import Complete!' );
				}
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( data ) {

				// 5. Fail - Import Complete.
				if( false === data.success ) {
					AstraSitesAdmin._log_title( data.data );
				} else {

					$('body').removeClass('importing-site');
					$('.previous-theme, .next-theme').removeClass('disabled');

					var date = new Date();

					AstraSitesAdmin.import_end_time = new Date();
					var diff    = ( AstraSitesAdmin.import_end_time.getTime() - AstraSitesAdmin.import_start_time.getTime() );

					var time    = '';
					var seconds = Math.floor( diff / 1000 );
					var minutes = Math.floor( seconds / 60 );
					var hours   = Math.floor( minutes / 60 );

					minutes = minutes - ( hours * 60 );
					seconds = seconds - ( minutes * 60 );

					if( hours ) {
						time += hours + ' Hours ';
					}
					if( minutes ) {
						time += minutes + ' Minutes ';
					}
					if( seconds ) {
						time += seconds + ' Seconds';
					}

					var	output  = '<h2>Done 🎉</h2>';
						output += '<p>Your starter site has been imported successfully in '+time+'! Now go ahead, customize the text, images, and design to make it yours!</p>';
						output += '<p>You can now start making changes according to your requirements.</p>';
						output += '<p><a class="button button-primary button-hero" href="'+astraSitesAdmin.siteURL+'" target="_blank">View Site <i class="dashicons dashicons-external"></i></a></p>';

					$('.rotating,.current-importing-status-wrap,.notice-warning').remove();
					$('.astra-sites-result-preview .inner').html(output);

					// 5. Pass - Import Complete.
					AstraSitesAdmin._importSuccessButton();
				}
			});
		},

		/**
		 * 4. Import Widgets.
		 */
		_importWidgets: function( event ) {
			if ( AstraSitesAdmin._is_process_widgets() ) {
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					dataType: 'json',
					data : {
						action       : 'astra-sites-import-widgets',
						widgets_data : AstraSitesAdmin.widgets_data,
					},
					beforeSend: function() {
						AstraSitesAdmin._log_title( 'Importing Widgets..' );
					},
				})
				.fail(function( jqXHR ){
					AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
			    })
				.done(function ( widgets_data ) {

					// 4. Fail - Import Widgets.
					if( false === widgets_data.success ) {
						AstraSitesAdmin._log_title( widgets_data.data );

					} else {
						
						// 4. Pass - Import Widgets.
						AstraSitesAdmin._log_title( 'Imported Widgets!' );
						$(document).trigger( 'astra-sites-import-widgets-done' );					
					}
				});
			} else {
				$(document).trigger( 'astra-sites-import-widgets-done' );
			}
		},

		/**
		 * 3. Import Site Options.
		 */
		_importSiteOptions: function( event ) {

			if ( AstraSitesAdmin._is_process_xml() ) {
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					dataType: 'json',
					data : {
						action       : 'astra-sites-import-options',
						options_data : AstraSitesAdmin.options_data,
					},
					beforeSend: function() {
						AstraSitesAdmin._log_title( 'Importing Options..' );
						$('.astra-demo-import .percent').html('');
					},
				})
				.fail(function( jqXHR ){
					AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
			    })
				.done(function ( options_data ) {

					// 3. Fail - Import Site Options.
					if( false === options_data.success ) {
						AstraSitesAdmin._log_title( options_data );
					} else {

						// 3. Pass - Import Site Options.
						$(document).trigger( 'astra-sites-import-options-done' );
					}
				});
			} else {
				$(document).trigger( 'astra-sites-import-options-done' );				
			}
		},

		/**
		 * 2. Prepare XML Data.
		 */
		_importXML: function() {

			if ( AstraSitesAdmin._is_process_xml() ) {
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					dataType: 'json',
					data : {
						action  : 'astra-sites-import-prepare-xml',
						wxr_url : AstraSitesAdmin.current_site['astra-site-wxr-path'],
					},
					beforeSend: function() {
						$('.astra-site-import-process-wrap').show();
						AstraSitesAdmin._log_title( 'Importing Content..' );
					},
				})
				.fail(function( jqXHR ){
					AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
			    })
				.done(function ( xml_data ) {

					// 2. Fail - Prepare XML Data.
					if( false === xml_data.success ) {
						AstraSitesAdmin._log_title( xml_data );
						var error_msg = xml_data.data.error || xml_data.data;
						AstraSitesAdmin._log_title( error_msg );

					} else {

						var xml_processing = $('.astra-demo-import').attr( 'data-xml-processing' );

						if( 'yes' === xml_processing ) {
							return;
						}

						$('.astra-demo-import').attr( 'data-xml-processing', 'yes' );

						// 2. Pass - Prepare XML Data.

						// Import XML though Event Source.
						AstraSSEImport.data = xml_data.data;
						AstraSSEImport.render();

						$('.current-importing-status-description').html('').show();

						$('.astra-sites-result-preview .inner').append('<div class="astra-site-import-process-wrap"><progress class="astra-site-import-process" max="100" value="0"></progress></div>');
						
						var evtSource = new EventSource( AstraSSEImport.data.url );
						evtSource.onmessage = function ( message ) {
							var data = JSON.parse( message.data );
							switch ( data.action ) {
								case 'updateDelta':

										AstraSSEImport.updateDelta( data.type, data.delta );
									break;

								case 'complete':
									evtSource.close();

									$('.current-importing-status-description').hide();
									$('.astra-demo-import').removeAttr( 'data-xml-processing' );

									document.getElementsByClassName("astra-site-import-process").value = '100';

									$('.astra-site-import-process-wrap').hide();

									$(document).trigger( 'astra-sites-import-xml-done' );

									break;
							}
						};
						evtSource.addEventListener( 'log', function ( message ) {
							var data = JSON.parse( message.data );
							var message = data.message || '';
							if( message && 'info' === data.level ) {
								message = message.replace(/"/g, function(letter) {
								    return '';
								});
								$('.current-importing-status-description').html( message );
							}
						});
					}
				});
			} else {
				$(document).trigger( 'astra-sites-import-xml-done' );
			}

			
		},

		_is_process_xml: function() {
			if ( $( '.astra-sites-import-xml' ).find('.checkbox').is(':checked') ) {
				return true;
			}
			return false;
		},

		_is_process_customizer: function() {
			if ( $( '.astra-sites-import-customizer' ).find('.checkbox').is(':checked') ) {
				return true;
			}
			return false;
		},

		_is_process_widgets: function() {
			if ( $( '.astra-sites-import-widgets' ).find('.checkbox').is(':checked') ) {
				return true;
			}
			return false;
		},

		/**
		 * 1. Import WPForms Options.
		 */
		_importWPForms: function( event ) {
			if ( AstraSitesAdmin._is_process_customizer() ) {
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					dataType: 'json',
					data : {
						action      : 'astra-sites-import-wpforms',
						wpforms_url : AstraSitesAdmin.wpforms_url,
					},
					beforeSend: function() {
						AstraSitesAdmin._log_title( 'Importing WP Forms..' );
					},
				})
				.fail(function( jqXHR ){
					AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
			    })
				.done(function ( forms ) {

					// 1. Fail - Import WPForms Options.
					if( false === forms.success ) {
						AstraSitesAdmin._log_title( forms.data );
					} else {

						// 1. Pass - Import Customizer Options.
						$(document).trigger( 'astra-sites-import-wpforms-done' );
					}
				});
			} else {
				$(document).trigger( 'astra-sites-import-wpforms-done' );				
			}
		},

		/**
		 * 1. Import Customizer Options.
		 */
		_importCustomizerSettings: function( event ) {
			if ( AstraSitesAdmin._is_process_customizer() ) {
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					dataType: 'json',
					data : {
						action          : 'astra-sites-import-customizer-settings',
						customizer_data : AstraSitesAdmin.customizer_data,
					},
					beforeSend: function() {
					},
				})
				.fail(function( jqXHR ){
					AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText, true );
			    })
				.done(function ( customizer_data ) {

					// 1. Fail - Import Customizer Options.
					if( false === customizer_data.success ) {
						AstraSitesAdmin._log_title( customizer_data.data );
					} else {

						// 1. Pass - Import Customizer Options.
						$(document).trigger( 'astra-sites-import-customizer-settings-done' );
					}
				});
			} else {
				$(document).trigger( 'astra-sites-import-customizer-settings-done' );
			}

		},

		/**
		 * Import Success Button.
		 * 
		 * @param  {string} data Error message.
		 */
		_importSuccessButton: function() {

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
		 * Preview Device
		 */
		_previewDevice: function( event ) {
			var device = $( event.currentTarget ).data( 'device' );

			$('.theme-install-overlay')
				.removeClass( 'preview-desktop preview-tablet preview-mobile' )
				.addClass( 'preview-' + device )
				.data( 'current-preview-device', device );

			AstraSitesAdmin._tooglePreviewDeviceButtons( device );
		},

		/**
		 * Toggle Preview Buttons
		 */
		_tooglePreviewDeviceButtons: function( newDevice ) {
			var $devices = $( '.wp-full-overlay-footer .devices' );

			$devices.find( 'button' )
				.removeClass( 'active' )
				.attr( 'aria-pressed', false );

			$devices.find( 'button.preview-' + newDevice )
				.addClass( 'active' )
				.attr( 'aria-pressed', true );
		},

		/**
		 * Import Error Button.
		 * 
		 * @param  {string} data Error message.
		 */
		_importFailMessage: function( message ) {

			$('.astra-demo-import')
				.addClass('go-pro button-primary')
				.removeClass('updating-message installing')
				.removeAttr('data-import')
				.attr('target', '_blank')
				.append('<i class="dashicons dashicons-external"></i>')
				.removeClass('astra-demo-import');

			AstraSitesAdmin._log_title( message );

			$('.wp-full-overlay-header .go-pro').text( astraSitesAdmin.strings.importFailBtn );
			$('.wp-full-overlay-footer .go-pro').text( astraSitesAdmin.strings.importFailBtnLarge )
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

			AstraSitesAdmin._log_title( 'Installing Required Plugin..' );

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

			AstraSitesAdmin._log_title( 'Installing Plugin - ' + AstraSitesAdmin.ucwords( $button.data( 'name' ) ) );

			wp.updates.installPlugin( {
				slug:    $button.data( 'slug' )
			} );
		},

		ucwords: function( str ) {
			if( ! str ) {
				return '';
			}

			str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
			    return letter.toUpperCase();
			});

			str = str.replace(/-/g, function(letter) {
			    return ' ';
			});

			return str;
		},

		/**
		 * Install Success
		 */
		_installSuccess: function( event, response ) {

			event.preventDefault();


			var $siteOptions = $( '.wp-full-overlay-header').find('.astra-site-options').val();

			var $enabledExtensions = $( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();

			// Transform the 'Install' button into an 'Activate' button.
			var $init = $( '.plugin-card-' + response.slug ).data('init');
			var $name = $( '.plugin-card-' + response.slug ).data('name');

			// Reset not installed plugins list.
			var pluginsList = astraSitesAdmin.requiredPlugins.notinstalled;
			astraSitesAdmin.requiredPlugins.notinstalled = AstraSitesAdmin._removePluginFromQueue( response.slug, pluginsList );

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				AstraSitesAdmin._log_title( 'Installing Plugin - ' + AstraSitesAdmin.ucwords($name) );

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

						AstraSitesAdmin._log_title( 'Installed Plugin - ' + AstraSitesAdmin.ucwords($name) );

						// Reset not installed plugins list.
						astraSitesAdmin.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue( response.slug, pluginsList );

						// Enable Demo Import Button
						AstraSitesAdmin._enable_demo_import_button();

					}
				});

			}, 1200 );

		},

		/**
		 * Plugin Installation Error.
		 */
		_installError: function( event, response ) {

			var $card = $( '.plugin-card-' + response.slug );
			var $name = $card.data('name');

			AstraSitesAdmin._log_title( response.errorMessage + ' ' + AstraSitesAdmin.ucwords($name) );


			$card
				.removeClass( 'button-primary' )
				.addClass( 'disabled' )
				.html( wp.updates.l10n.installFailedShort );

		},

		/**
		 * Installing Plugin
		 */
		_pluginInstalling: function(event, args) {
			event.preventDefault();

			var $card = $( '.plugin-card-' + args.slug );
			var $name = $card.data('name');

			AstraSitesAdmin._log_title( 'Installing Plugin - ' + AstraSitesAdmin.ucwords( $name ));

			$card.addClass('updating-message');

		},

		/**
		 * Render Demo Preview
		 */
		_activateNow: function( eventn ) {

			event.preventDefault();

			var $button = jQuery( event.target ),
				$init 	= $button.data( 'init' ),
				$slug 	= $button.data( 'slug' );
				$name 	= $button.data( 'name' );

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			AstraSitesAdmin._log_title( 'Activating Plugin - ' + AstraSitesAdmin.ucwords( $name ) );

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

					AstraSitesAdmin._log_title( 'Activated Plugin - ' + AstraSitesAdmin.ucwords($name)  );

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

			// Import process is started?
			// And Closing the window? Then showing the warning confirm message.
			if( $('body').hasClass('importing-site') && ! confirm( astraSitesAdmin.strings.warningBeforeCloseWindow ) ) {
				return;
			}

			$('body').removeClass('importing-site');
			$('.previous-theme, .next-theme').removeClass('disabled');
			$('.theme-install-overlay').css('display', 'none');
			$('.theme-install-overlay').remove();
			$('.theme-preview-on').removeClass('theme-preview-on');
			$('html').removeClass('astra-site-preview-on');
		},

		/**
		 * Bulk Plugin Active & Install
		 */
		_bulkPluginInstallActivate: function()
		{
			if( 0 === astraSitesAdmin.requiredPlugins.length ) {
				return;
			}

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

			if( activate_plugins.length <= 0 && not_installed.length <= 0 ) {
				AstraSitesAdmin._enable_demo_import_button();
			}

		},

		/**
		 * Activate All Plugins.
		 */
		_activateAllPlugins: function( activate_plugins ) {

			AstraSitesAdmin._log_title( 'Activating Required Plugins..' );

			$.each( activate_plugins, function(index, single_plugin) {

				var $card    	 = $( '.plugin-card-' + single_plugin.slug ),
					$siteOptions = $( '.wp-full-overlay-header').find('.astra-site-options').val(),
					$enabledExtensions = $( '.wp-full-overlay-header').find('.astra-enabled-extensions').val();


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

							var pluginsList = astraSitesAdmin.requiredPlugins.inactive;

							// Reset not installed plugins list.
							astraSitesAdmin.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue( single_plugin.slug, pluginsList );

							// Enable Demo Import Button
							AstraSitesAdmin._enable_demo_import_button();
						} else {
						}
					}
				});
			});
			AstraSitesAjaxQueue.run();
		},

		/**
		 * Install All Plugins.
		 */
		_installAllPlugins: function( not_installed ) {

			AstraSitesAdmin._log_title( 'Installing Required Plugins..' );
			
			$.each( not_installed, function(index, single_plugin) {

				AstraSitesAdmin._log_title( 'Installing Plugin - ' + AstraSitesAdmin.ucwords( single_plugin.name ));

				var $card = $( '.plugin-card-' + single_plugin.slug );

				// Add each plugin activate request in Ajax queue.
				// @see wp-admin/js/updates.js
				wp.updates.queue.push( {
					action: 'install-plugin', // Required action.
					data:   {
						slug: single_plugin.slug
					}
				} );
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
		_importDemo: function(event) {
			event.preventDefault();

			var date = new Date();

			AstraSitesAdmin.import_start_time = new Date();

			var disabled = $(this).attr('data-import');

			if ( typeof disabled !== 'undefined' && disabled === 'disabled' || $this.hasClass('disabled') ) {

				$('.astra-demo-import').addClass('updating-message installing')
					.text( wp.updates.l10n.installing );

				$('.astra-sites-result-preview').show();
				var output = '<div class="current-importing-status-title"></div><div class="current-importing-status-description"></div>';
				$('.current-importing-status').html( output );

				/**
				 * Process Bulk Plugin Install & Activate
				 */
				AstraSitesAdmin._bulkPluginInstallActivate();
			}
		},

		_process_import: function() {

			var $theme  = $('.astra-sites-preview').find('.wp-full-overlay-header'),
				apiURL  = $theme.data('demo-api') || '';

			$('body').addClass('importing-site');
			$('.previous-theme, .next-theme').addClass('disabled');

			// Remove all notices before import start.
			$('.install-theme-info > .notice').remove();

			$('.astra-demo-import').attr('data-import', 'disabled')
				.addClass('updating-message installing')
				.text( astraSitesAdmin.strings.importingDemo );
		
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

			AstraSitesAdmin._log_title( 'Started Importing..' );

			// 1. Request Site Import
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				dataType: 'json',
				data : {
					'action'  : 'astra-sites-import-set-site-data',
					'api_url' : apiURL,
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( demo_data ) {

				// 1. Fail - Request Site Import
				if( false === demo_data.success ) {
					AstraSitesAdmin._importFailMessage( demo_data.data );
				} else {

					// Set log file URL.
					if( 'log_file' in demo_data.data ){
						AstraSitesAdmin.log_file_url  = decodeURIComponent( demo_data.data.log_file ) || '';
					}

					// 1. Pass - Request Site Import
					AstraSitesAdmin.customizer_data = JSON.stringify( demo_data.data['astra-site-customizer-data'] ) || '';
					AstraSitesAdmin.wxr_url         = encodeURI( demo_data.data['astra-site-wxr-path'] ) || '';
					AstraSitesAdmin.wpforms_url     = encodeURI( demo_data.data['astra-site-wpforms-path'] ) || '';
					AstraSitesAdmin.options_data    = JSON.stringify( demo_data.data['astra-site-options-data'] ) || '';
					AstraSitesAdmin.widgets_data    = JSON.stringify( demo_data.data['astra-site-widgets-data'] ) || '';

					$(document).trigger( 'astra-sites-import-set-site-data-done' );
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

			var site_id = $(this).parents('.wp-full-overlay-header').data('demo-id') || '';

			if( AstraSitesAPI._stored_data ) {
				var site_data = AstraSitesAdmin._get_site_details( site_id );


				if( site_data ) {
					// Set current site details.
					AstraSitesAdmin.current_site = site_data;
				}
			}

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

			var site_id = $(this).parents('.wp-full-overlay-header').data('demo-id') || '';

			if( AstraSitesAPI._stored_data ) {
				var site_data = AstraSitesAdmin._get_site_details( site_id );

				if( site_data ) {
					// Set current site details.
					AstraSitesAdmin.current_site = site_data;
				}
			}

			AstraSitesAdmin._renderDemoPreview( nextDemo );
		},

		_set_current_screen: function( screen ) {
			AstraSitesAdmin.current_screen = screen;
			var old_screen = $('.astra-sites-preview').attr( 'screen' ) || '';


			if( old_screen ) {
				$('.astra-sites-preview').removeClass( 'screen-' + old_screen );
			}

			$('.astra-sites-preview').attr( 'screen', screen );
			$('.astra-sites-preview').addClass( 'screen-' + screen );
		},

		/**
		 * Individual Site Preview
		 *
		 * On click on image, more link & preview button.
		 */
		_preview: function( event ) {

			event.preventDefault();

			var site_id = $(this).parents('.site-single').data('demo-id') || '';

			if( AstraSitesAPI._stored_data ) {
				var site_data = AstraSitesAdmin._get_site_details( site_id );

				if( site_data ) {
					// Set current site details.
					AstraSitesAdmin.current_site = site_data;

					// Set current screen.
					AstraSitesAdmin._set_current_screen( 'get-started' );
				}
			}

			var self = $(this).parents('.theme');
			self.addClass('theme-preview-on');

			$('html').addClass('astra-site-preview-on');

			AstraSitesAdmin._renderDemoPreview( self );
		},

		_get_site_details: function( site_id ) {
			var all_sites = AstraSitesAPI._stored_data['astra-sites'] || [];

			if( ! all_sites ) {
				return false;
			}

			var single_site = all_sites.filter(function (site) { return site.id == site_id });
			if( ! single_site ) {
				return false;
			}

			if( ! $.isArray( single_site ) ) {
				return false;
			}

			return single_site[0];
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

			var demoId             	   = anchor.data('demo-id') || '',
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
			$('.theme-install-overlay').remove();

			$('#astra-sites-menu-page').append(template(templateData[0]));
			$('.theme-install-overlay').css('display', 'block');
			AstraSitesAdmin._checkNextPrevButtons();

			var desc       = $('.theme-details');
			var descHeight = parseInt( desc.outerHeight() );
			var descBtn    = $('.theme-details-read-more');

			// Check is site imported recently and set flag.
			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-set-reset-data',
				},
			})
			.done(function ( response ) {
				if( response.success ) {
					AstraSitesAdmin.site_imported_data = response.data;
				}
			});

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
				var $pluginsFilter  = $( '#plugin-filter' ),
					data 			= {
										action           : 'astra-required-plugins',
										_ajax_nonce      : astraSitesAdmin._ajax_nonce,
										required_plugins : requiredPlugins
									};

				// Add disabled class from import button.
				$('.astra-demo-import')
					.addClass('disabled not-click-able')
					.removeAttr('data-import');

				$('.required-plugins').addClass('loading').html('<span class="spinner is-active"></span>');

			 	// Required Required.
				$.ajax({
					url  : astraSitesAdmin.ajaxurl,
					type : 'POST',
					data : data,
				})
				.fail(function( jqXHR ){

					// Remove loader.
					$('.required-plugins').removeClass('loading').html('');

				})
				.done(function ( response ) {
					required_plugins = response.data['required_plugins'];

					if( response.data['third_party_required_plugins'].length ) {
						$('.astra-demo-import').removeClass('button-primary').addClass('disabled');

						$('.astra-sites-third-party-required-plugins-wrap').remove();
						var template = wp.template('astra-sites-third-party-required-plugins');
						$('.astra-sites-advanced-options-wrap').html( template( response.data['third_party_required_plugins'] ) );
					} else {
						// Release disabled class from import button.
						$('.astra-demo-import')
							.removeClass('disabled not-click-able')
							.attr('data-import', 'disabled');
					}

					// Remove loader.
					$('.required-plugins').removeClass('loading').html('');
					$('.required-plugins-list').html('');

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
					if ( typeof required_plugins.notinstalled !== 'undefined' ) {

						// Add not have installed plugins count.
						remaining_plugins += parseInt( required_plugins.notinstalled.length );

						$( required_plugins.notinstalled ).each(function( index, plugin ) {
							$('.required-plugins-list').append('<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>');
						});
					}

					/**
					 * Inactive
					 *
					 * List of not inactive required plugins.
					 */
					if ( typeof required_plugins.inactive !== 'undefined' ) {

						// Add inactive plugins count.
						remaining_plugins += parseInt( required_plugins.inactive.length );

						$( required_plugins.inactive ).each(function( index, plugin ) {
							$('.required-plugins-list').append('<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>');
						});
					}

					/**
					 * Active
					 *
					 * List of not active required plugins.
					 */
					if ( typeof required_plugins.active !== 'undefined' ) {

						$( required_plugins.active ).each(function( index, plugin ) {
							$('.required-plugins-list').append('<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>');
						});
					}

					/**
					 * Enable Demo Import Button
					 * @type number
					 */
					astraSitesAdmin.requiredPlugins = required_plugins;
				});

			} else {

				// Enable Demo Import Button
				AstraSitesAdmin._enable_demo_import_button( demoType );
				$('.astra-sites-advanced-options-wrap').remove();
			}

			return;
		},

		/**
		 * Enable Demo Import Button.
		 */
		_enable_demo_import_button: function( type ) {

			type = ( undefined !== type ) ? type : 'free';

			$('.install-theme-info .theme-details .site-description').remove();

			switch( type ) {

				case 'free':

							var notinstalled = astraSitesAdmin.requiredPlugins.notinstalled || 0;
							var inactive     = astraSitesAdmin.requiredPlugins.inactive || 0;

							if( notinstalled.length === inactive.length ) {

								// XML reader not available notice.
								if( astraSitesAdmin.XMLReaderDisabled ) {
									if( ! $('.install-theme-info .astra-sites-xml-notice').length ) {
										$('.install-theme-info').prepend( astraSitesAdmin.strings.warningXMLReader );
									}
									$('.astra-demo-import')
										.removeClass('installing updating-message')
										.addClass('disabled')
										.text( astraSitesAdmin.strings.importDemo );	
								} else {
									$(document).trigger( 'astra-sites-install-and-activate-required-plugins-done' );
								}
							}
					break;

				case 'upgrade':
							var demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

							$('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraSitesAdmin.getUpgradeURL + demo_slug )
									.text( astraSitesAdmin.getUpgradeText )
									.append('<i class="dashicons dashicons-external"></i>');
					break;

				default:
							var demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

							$('.astra-demo-import')
									.addClass('go-pro button-primary')
									.removeClass('astra-demo-import')
									.attr('target', '_blank')
									.attr('href', astraSitesAdmin.getProURL )
									.text( astraSitesAdmin.getProText )
									.append('<i class="dashicons dashicons-external"></i>');

							$('.wp-full-overlay-header').find('.go-pro').remove();

							if( false == astraSitesAdmin.isWhiteLabeled ) {
								if( astraSitesAdmin.isPro ) {
									$('.install-theme-info .theme-details').prepend( wp.template('astra-sites-pro-inactive-site-description') );
								} else {
									$('.install-theme-info .theme-details').prepend( wp.template('astra-sites-pro-site-description') );
								}
							}

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