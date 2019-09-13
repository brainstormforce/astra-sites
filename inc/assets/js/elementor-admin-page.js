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

	$scope = {};

	$.fn.isInViewport = function() {

		// If not have the element then return false!
		if( ! $( this ).length ) {
			return false;
		}

		var elementTop = $( this ).offset().top;
		var elementBottom = elementTop + $( this ).outerHeight();

		var viewportTop = $( window ).scrollTop();
		var viewportBottom = viewportTop + $( window ).height();

		return elementBottom > viewportTop && elementTop < viewportBottom;
	};

	AstraElementorSitesAdmin = {

		visited_pages: [],
		reset_remaining_posts: 0,
		site_imported_data: null,
		backup_taken: false,
		templateData: {},
		insertData: {},
		log_file        : '',
		pages_list      : '',
		insertActionFlag : false,
		page_id : 0,
		site_id : 0,
		block_id : 0,
		requiredPlugins : [],
		canImport : false,
		canInsert : false,
		type : 'pages',
		action : '',
		masonryObj : [],
		index : 0,

		init: function() {
			this._bind();
		},

		/**
		 * Binds events for the Astra Sites.
		 *
		 * @since 1.0.0
		 * @access private
		 * @method _bind
		 */
		_bind: function() {

			if ( elementorCommon ) {

				let add_section_tmpl = $( "#tmpl-elementor-add-section" );

				if ( add_section_tmpl.length > 0 ) {

					let action_for_add_section = add_section_tmpl.text();
					
					action_for_add_section = action_for_add_section.replace('<div class="elementor-add-section-drag-title', '<div class="elementor-add-section-area-button elementor-add-ast-site-button" title="Astra Starters"> <i class="eicon-folder"></i> </div><div class="elementor-add-section-drag-title');

					add_section_tmpl.text( action_for_add_section );

					elementor.on( "preview:loaded", function() {

						let base_skeleton = $( '#tmpl-ast-template-base-skeleton' ).text();
						let header_template = $( '#tmpl-ast-template-modal__header' ).text();

						$( 'body' ).append( base_skeleton );
						$scope = $( '#ast-sites-modal' );
						$scope.find( '.astra-sites-content-wrap' ).before( header_template );

						$( elementor.$previewContents[0].body ).on( "click", ".elementor-add-ast-site-button", AstraElementorSitesAdmin._open );

						// Click events.
						$( 'body' ).on( "click", ".ast-sites-modal__header__close", AstraElementorSitesAdmin._close );
						$( 'body' ).on( "click", "#ast-sites-modal .elementor-template-library-menu-item", AstraElementorSitesAdmin._libraryClick );
						$( 'body' ).on( "click", "#ast-sites-modal .theme-screenshot", AstraElementorSitesAdmin._preview );
						$( 'body' ).on( "click", "#ast-sites-modal .back-to-layout", AstraElementorSitesAdmin._goBack );
						$( 'body' ).on( "click", AstraElementorSitesAdmin._closeTooltip );

						$( document ).on( "click", "#ast-sites-modal .ast-library-template-insert", AstraElementorSitesAdmin._insert );
						$( document ).on( "click", ".ast-import-elementor-template", AstraElementorSitesAdmin._importTemplate );
						$( 'body' ).on( "click", "#ast-sites-modal .astra-sites-tooltip-icon", AstraElementorSitesAdmin._toggleTooltip );
						$( document ).on( "click", ".elementor-template-library-menu-item", AstraElementorSitesAdmin._toggle );
						$( document ).on( 'click', '#ast-sites-modal .astra-sites__sync-wrap', AstraElementorSitesAdmin._sync );
						$( document ).on( 'click', '#ast-sites-modal .ast-sites-modal__header__logo__icon-wrapper, #ast-sites-modal .back-to-layout-button', AstraElementorSitesAdmin._home );
						$( document ).on( 'click', '#ast-sites-modal .notice-dismiss', AstraElementorSitesAdmin._dismiss );

						// Other events.
						$scope.find( '.astra-sites-content-wrap' ).scroll( AstraElementorSitesAdmin._loadLargeImages );
						$( document ).on( 'keyup input' , '#ast-sites-modal #wp-filter-search-input', AstraElementorSitesAdmin._search );

						// Triggers.
						$( document ).on( "astra-sites__elementor-open-after", AstraElementorSitesAdmin._initSites );
						$( document ).on( "astra-sites__elementor-open-before", AstraElementorSitesAdmin._beforeOpen );
						$( document ).on( "astra-sites__elementor-plugin-check", AstraElementorSitesAdmin._pluginCheck );
						$( document ).on( 'astra-sites__elementor-close-before', AstraElementorSitesAdmin._beforeClose );

						$( document ).on( 'astra-sites__elementor-do-step-1', AstraElementorSitesAdmin._step1 );
						$( document ).on( 'astra-sites__elementor-do-step-2', AstraElementorSitesAdmin._step2 );

						$( document ).on( 'astra-sites__elementor-goback-step-1', AstraElementorSitesAdmin._goStep1 );
						$( document ).on( 'astra-sites__elementor-goback-step-2', AstraElementorSitesAdmin._goStep2 );

						// Plugin install & activate.
						$( document ).on( 'wp-plugin-installing' , AstraElementorSitesAdmin._pluginInstalling );
						$( document ).on( 'wp-plugin-install-error' , AstraElementorSitesAdmin._installError );
						$( document ).on( 'wp-plugin-install-success' , AstraElementorSitesAdmin._installSuccess );

					});
				}
			}

		},

		_dismiss: function() {

			$( this ).closest( '.ast-sites-floating-notice-wrap' ).removeClass( 'slide-in' );
			$( this ).closest( '.ast-sites-floating-notice-wrap' ).addClass( 'slide-out' );

			setTimeout( function() {
				$( this ).closest( '.ast-sites-floating-notice-wrap' ).removeClass( 'slide-out' );
			}, 200 );

			if ( $( this ).closest( '.ast-sites-floating-notice-wrap' ).hasClass( 'refreshed-notice' ) ) {
				$.ajax({
					url  : astraElementorSites.ajaxurl,
					type : 'POST',
					data : {
						action : 'astra-sites-update-library-complete',
					},
				});
			}
		},

		_done: function( data ) {

			var str = ( AstraElementorSitesAdmin.type == 'pages' ) ? 'Template' : 'Block';
			$scope.find( '.ast-import-elementor-template' ).removeClass( 'installing' );
			$scope.find( '.ast-import-elementor-template' ).attr( 'data-demo-link', data.data.link );
			setTimeout( function() {
				$scope.find( '.ast-import-elementor-template' ).text( 'View Saved ' + str );
				$scope.find( '.ast-import-elementor-template' ).addClass( 'action-done' );
			}, 200 );
		},

		_beforeClose: function() {
			if ( AstraElementorSitesAdmin.action == 'insert' ) {
				$scope.find( '.ast-library-template-insert' ).removeClass( 'installing' );
				$scope.find( '.ast-library-template-insert' ).text( 'Imported' );
				$scope.find( '.ast-library-template-insert' ).addClass( 'action-done' );
			}
		},

		_closeTooltip: function( event ) {

			if(
				event.target.className !== "ast-tooltip-wrap" &&
				event.target.className !== "dashicons dashicons-editor-help"
			) {
				var wrap = $scope.find( '.ast-tooltip-wrap' );
				if ( wrap.hasClass( 'ast-show-tooltip' ) ) {
					$scope.find( '.ast-tooltip-wrap' ).removeClass( 'ast-show-tooltip' );
				}
			}
		},

		_sync: function( event ) {

			event.preventDefault();
			var button = $( this ).find( '.astra-sites-sync-library-button' );

			if( button.hasClass( 'updating-message') ) {
				return;
			}

			button.addClass( 'updating-message');
			$scope.find( '#ast-sites-floating-notice-wrap-id .ast-sites-floating-notice' ).html( 'Syncing template library in the background! We will notify you once it is done.<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' );
			$scope.find( '#ast-sites-floating-notice-wrap-id' ).addClass( 'slide-in' );

			$.ajax({
				url  : astraElementorSites.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-update-library',
				},
			})
			.fail(function( jqXHR ){
				console.log( jqXHR );
		    })
			.done(function ( response ) {
				button.removeClass( 'updating-message');

				setTimeout( function() {
					$scope.find( '#ast-sites-floating-notice-wrap-id' ).removeClass( 'slide-in' );
					$scope.find( '#ast-sites-floating-notice-wrap-id' ).addClass( 'slide-out' );
				}, 5000 );

				setTimeout( function() {
					$scope.find( '#ast-sites-floating-notice-wrap-id' ).removeClass( 'slide-out' );
				}, 5200 );

				if( 'ajax' === response.data ) {

					// Import categories.
					$.ajax({
						url  : astraElementorSites.ajaxurl,
						type : 'POST',
						data : {
							action : 'astra-sites-import-categories',
						},
					})
					.fail(function( jqXHR ){
						console.log( jqXHR );
					});

					// Import Blocks.
					$.ajax({
						url  : astraElementorSites.ajaxurl,
						type : 'POST',
						data : {
							action : 'astra-sites-import-blocks',
						},
					})
					.fail(function( jqXHR ){
						console.log( jqXHR );
					});

					$.ajax({
						url  : astraElementorSites.ajaxurl,
						type : 'POST',
						data : {
							action : 'astra-sites-get-sites-request-count',
						},
					})
					.fail(function( jqXHR ){
						console.log( jqXHR );
				    })
					.done(function ( response ) {
						if( response.success ) {
							var total = response.data;

							for( let i = 1; i <= total; i++ ) {
								AstraSitesAjaxQueue.add({
									url: astraElementorSites.ajaxurl,
									type: 'POST',
									data: {
										action  : 'astra-sites-import-sites',
										page_no : i,
									}
								});
							}

							// Run the AJAX queue.
							AstraSitesAjaxQueue.run();
						}
					});
				}
			});
		},

		_toggleTooltip: function( e ) {

			var wrap = $scope.find( '.ast-tooltip-wrap' );


			if ( wrap.hasClass( 'ast-show-tooltip' ) ) {
				$scope.find( '.ast-tooltip-wrap' ).removeClass( 'ast-show-tooltip' );
			} else {
				$scope.find( '.ast-tooltip-wrap' ).addClass( 'ast-show-tooltip' );
			}
		},

		_toggle: function( e ) {
			$scope.find( '.elementor-template-library-menu-item' ).removeClass( 'elementor-active' );

			$scope.find( '.dialog-lightbox-content' ).hide();

			$scope.find( '.theme-preview' ).hide();
			$scope.find( '.theme-preview' ).html( '' );
			$scope.find( '.theme-preview-block' ).hide();
			$scope.find( '.theme-preview-block' ).html( '' );

			$scope.find( '.dialog-lightbox-content' ).hide();
			$scope.find( '.dialog-lightbox-content-block' ).hide();

			$( this ).addClass( 'elementor-active' );
			let data_type = $( this ).data( 'template-type' );

			AstraElementorSitesAdmin.type = data_type;
			AstraElementorSitesAdmin._switchTo( data_type );
		},

		_home: function() {
			$scope.find( '.elementor-template-library-menu-item:first-child' ).trigger( 'click' );
		},

		_switchTo: function( type ) {
			if ( 'pages' == type ) {
				AstraElementorSitesAdmin._initSites();
				$scope.find( '.dialog-lightbox-content' ).show();
			} else {
				AstraElementorSitesAdmin._initBlocks();
				$scope.find( '.dialog-lightbox-content-block' ).show();
			}
			$scope.find( '.astra-sites-content-wrap' ).trigger( 'scroll' );
		},

		_importWPForm: function( wpforms_url, callback ) {

			if ( '' == wpforms_url ) {
				if( callback && typeof callback == "function"){
					callback( '' );
			    }
			    return;
			}

			$.ajax({
				url  : astraElementorSites.ajaxurl,
				type : 'POST',
				dataType: 'json',
				data : {
					action      : 'astra-sites-import-wpforms',
					wpforms_url : wpforms_url,
				},
				beforeSend: function() {
					console.log( 'Importing WP Forms..' );
				},
			})
			.fail(function( jqXHR ){
				console.log( jqXHR.status + ' ' + jqXHR.responseText, true );
		    })
			.done(function ( data ) {

				// 1. Fail - Import WPForms Options.
				if( false === data.success ) {
					console.log( data.data );
				} else {
					if( callback && typeof callback == "function"){
						callback( data );
				    }
				}
			});
		},

		_createTemplate: function( data ) {

			// Work with JSON page here
			$.ajax({
				url: astraElementorSites.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					'action' : 'astra-sites-create-template',
					'data'   : data,
					'title'  : ( AstraElementorSitesAdmin.type == 'pages' ) ? astraElementorSites.default_page_builder_sites[ AstraElementorSitesAdmin.site_id ]['title'] : '',
					'type'   : AstraElementorSitesAdmin.type
				},
			})
			.fail(function( jqXHR ){
				console.log( jqXHR );
			})
			.done(function ( data ) {
				AstraElementorSitesAdmin._done( data );
			});
		},

		/**
		 * Install All Plugins.
		 */
		_installAllPlugins: function( not_installed ) {

			$.each( not_installed, function(index, single_plugin) {

				console.log( 'Installing Plugin - ' + single_plugin.name );

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
		 * Activate All Plugins.
		 */
		_activateAllPlugins: function( activate_plugins ) {

			$.each( activate_plugins, function(index, single_plugin) {

				console.log( 'Activating Plugin - ' + single_plugin.name );

				AstraSitesAjaxQueue.add({
					url: astraElementorSites.ajaxurl,
					type: 'POST',
					data: {
						'action' : 'astra-required-plugin-activate',
						'init' : single_plugin.init,
					},
					success: function( result ){

						if( result.success ) {

							var pluginsList = AstraElementorSitesAdmin.requiredPlugins.inactive;

							// Reset not installed plugins list.
							AstraElementorSitesAdmin.requiredPlugins.inactive = AstraElementorSitesAdmin._removePluginFromQueue( single_plugin.slug, pluginsList );

							// Enable Demo Import Button
							AstraElementorSitesAdmin._enableImport();
						}
					}
				});
			});
			AstraSitesAjaxQueue.run();
		},

		/**
		 * Remove plugin from the queue.
		 */
		_removePluginFromQueue: function( removeItem, pluginsList ) {
			return jQuery.grep(pluginsList, function( value ) {
				return value.slug != removeItem;
			});
		},

		/**
		 * Get plugin from the queue.
		 */
		_getPluginFromQueue: function( item, pluginsList ) {

			var match = '';
			for ( ind in pluginsList ) {
				if( item == pluginsList[ind].slug ) {
					match = pluginsList[ind];
				}
			}
			return match;
		},

		_bulkPluginInstallActivate: function() {

			if( 0 === AstraElementorSitesAdmin.requiredPlugins.length ) {
				return;
			}

			// If has class the skip-plugins then,
			// Avoid installing 3rd party plugins.
			var not_installed = AstraElementorSitesAdmin.requiredPlugins.notinstalled || '';
			var activate_plugins = AstraElementorSitesAdmin.requiredPlugins.inactive || '';

			// First Install Bulk.
			if( not_installed.length > 0 ) {
				AstraElementorSitesAdmin._installAllPlugins( not_installed );
			}

			// Second Activate Bulk.
			if( activate_plugins.length > 0 ) {
				AstraElementorSitesAdmin._activateAllPlugins( activate_plugins );
			}

			if( activate_plugins.length <= 0 && not_installed.length <= 0 ) {
				AstraElementorSitesAdmin._enableImport();
			}
		},

		_importTemplate: function( e ) {

			if ( ! AstraElementorSitesAdmin.canImport ) {
				if ( $( this ).attr( 'data-demo-link' ) != undefined ) {
					window.open( $( this ).attr( 'data-demo-link' ), '_blank' );
				}
				return;
			}

			AstraElementorSitesAdmin.canImport = false;

			var str = ( AstraElementorSitesAdmin.type == 'pages' ) ? 'Template' : 'Block';

			$( this ).addClass( 'installing' );
			$( this ).text( 'Saving ' + str + '...' );

			AstraElementorSitesAdmin.action = 'import';

			AstraElementorSitesAdmin._bulkPluginInstallActivate();
		},

		_unescape( input_string ) {
			var title = _.unescape( input_string );

			// @todo check why below character not escape with function _.unescape();
			title = title.replace('&#8211;', '-' );

			return title;
		},

		_unescape_lower( input_string ) {
			input_string = $( "<textarea/>") .html( input_string ).text()
			var input_string = AstraElementorSitesAdmin._unescape( input_string );
			return input_string.toLowerCase();
		},

		_search: function() {

			let search_term = $( this ).val() || '';
			search_term = search_term.toLowerCase();

			if ( 'pages' == AstraElementorSitesAdmin.type ) {

				var items = AstraElementorSitesAdmin._getSearchedPages( search_term );

				if( search_term.length ) {
					$( this ).addClass( 'has-input' );
					AstraElementorSitesAdmin._addSites( items );
				} else {
					$( this ).removeClass( 'has-input' );
					AstraElementorSitesAdmin._appendSites( astraElementorSites.default_page_builder_sites );
				}
			} else {

				var items = AstraElementorSitesAdmin._getSearchedBlocks( search_term );

				if( search_term.length ) {
					$( this ).addClass( 'has-input' );
					AstraElementorSitesAdmin._appendBlocks( items );
				} else {
					$( this ).removeClass( 'has-input' );
					AstraElementorSitesAdmin._appendBlocks( astraElementorSites.astra_blocks );
				}
			}
		},

		_getSearchedPages: function( search_term ) {
			var items = [];
			search_term = search_term.toLowerCase();

			for( site_id in astraElementorSites.default_page_builder_sites ) {

				var current_site = astraElementorSites.default_page_builder_sites[site_id];

				// Check in site title.
				if( current_site['title'] ) {
					var site_title = AstraElementorSitesAdmin._unescape_lower( current_site['title'] );

					if( site_title.toLowerCase().includes( search_term ) ) {

						for( page_id in current_site['pages'] ) {

							items[page_id] = current_site['pages'][page_id];
							items[page_id]['type'] = 'page';
							items[page_id]['site_id'] = site_id;
							items[page_id]['astra-sites-type'] = current_site['astra-sites-type'] || '';
							items[page_id]['parent-site-name'] = current_site['title'] || '';
							items[page_id]['pages-count'] = 0;
						}
					}
				}

				// Check in site tags.
				if ( undefined != current_site['astra-sites-tag'] ) {

					if( Object.keys( current_site['astra-sites-tag'] ).length ) {
						for( site_tag_id in current_site['astra-sites-tag'] ) {
							var tag_title = current_site['astra-sites-tag'][site_tag_id];
								tag_title = AstraElementorSitesAdmin._unescape_lower( tag_title.replace('-', ' ') );

							if( tag_title.toLowerCase().includes( search_term ) ) {

								for( page_id in current_site['pages'] ) {

									items[page_id] = current_site['pages'][page_id];
									items[page_id]['type'] = 'page';
									items[page_id]['site_id'] = site_id;
									items[page_id]['astra-sites-type'] = current_site['astra-sites-type'] || '';
									items[page_id]['parent-site-name'] = current_site['title'] || '';
									items[page_id]['pages-count'] = 0;
								}
							}
						}
					}
				}

				// Check in page title.
				if( Object.keys( current_site['pages'] ).length ) {
					var pages = current_site['pages'];

					for( page_id in pages ) {

						// Check in site title.
						if( pages[page_id]['title'] ) {

							var page_title = AstraElementorSitesAdmin._unescape_lower( pages[page_id]['title'] );

							if( page_title.toLowerCase().includes( search_term ) ) {
								items[page_id] = pages[page_id];
								items[page_id]['type'] = 'page';
								items[page_id]['site_id'] = site_id;
								items[page_id]['astra-sites-type'] = current_site['astra-sites-type'] || '';
								items[page_id]['parent-site-name'] = current_site['title'] || '';
								items[page_id]['pages-count'] = 0;
							}
						}

						// Check in site tags.
						if ( undefined != pages[page_id]['astra-sites-tag'] ) {

							if( Object.keys( pages[page_id]['astra-sites-tag'] ).length ) {
								for( page_tag_id in pages[page_id]['astra-sites-tag'] ) {
									var page_tag_title = pages[page_id]['astra-sites-tag'][page_tag_id];
										page_tag_title = AstraElementorSitesAdmin._unescape_lower( page_tag_title.replace('-', ' ') );
									if( page_tag_title.toLowerCase().includes( search_term ) ) {
										items[page_id] = pages[page_id];
										items[page_id]['type'] = 'page';
										items[page_id]['site_id'] = site_id;
										items[page_id]['astra-sites-type'] = current_site['astra-sites-type'] || '';
										items[page_id]['parent-site-name'] = current_site['title'] || '';
										items[page_id]['pages-count'] = 0;
									}
								}
							}
						}

					}
				}
			}

			return items;
		},

		_getSearchedBlocks: function( search_term ) {

			var items = [];

			if( search_term.length ) {

				for( block_id in astraElementorSites.astra_blocks ) {

					var current_site = astraElementorSites.astra_blocks[block_id];

					// Check in site title.
					if( current_site['title'] ) {
						var site_title = AstraElementorSitesAdmin._unescape_lower( current_site['title'] );

						if( site_title.toLowerCase().includes( search_term ) ) {
							items[block_id] = current_site;
							items[block_id]['type'] = 'site';
							items[block_id]['site_id'] = block_id;
						}
					}

					// Check in site tags.
					if( Object.keys( current_site['tag'] ).length ) {
						for( site_tag_id in current_site['tag'] ) {
							var tag_title = AstraElementorSitesAdmin._unescape_lower( current_site['tag'][site_tag_id] );

							if( tag_title.toLowerCase().includes( search_term ) ) {
								items[block_id] = current_site;
								items[block_id]['type'] = 'site';
								items[block_id]['site_id'] = block_id;
							}
						}
					}
				}
			}

			return items;
		},

		_addSites: function( data ) {

			if ( data ) {
				let single_template = wp.template( 'astra-sites-search' );
				pages_list = single_template( data );
				$scope.find( '.dialog-lightbox-content' ).html( pages_list );
				AstraElementorSitesAdmin._loadLargeImages();

			} else {
				$scope.find( '.dialog-lightbox-content' ).html( wp.template('astra-sites-no-sites') );
			}
		},

		_appendSites: function( data ) {

			let single_template = wp.template( 'astra-sites-list' );
			pages_list = single_template( data );
			$scope.find( '.dialog-lightbox-message-block' ).hide();
			$scope.find( '.dialog-lightbox-message' ).show();
			$scope.find( '.dialog-lightbox-content' ).html( pages_list );
			AstraElementorSitesAdmin._loadLargeImages();
			AstraElementorSitesAdmin._autocomplete();
		},

		_appendBlocks: function( data ) {

			let single_template = wp.template( 'astra-blocks-list' );
			blocks_list = single_template( data );
			$scope.find( '.dialog-lightbox-message' ).hide();
			$scope.find( '.dialog-lightbox-message-block' ).show();
			$scope.find( '.dialog-lightbox-content-block' ).html( blocks_list );
			AstraElementorSitesAdmin._masonry();
			AstraElementorSitesAdmin._autocomplete();
		},

		_masonry: function() {

			//create empty var masonryObj
			var masonryObj;
			var container = document.querySelector( '.dialog-lightbox-content-block' );
			// initialize Masonry after all images have loaded
			imagesLoaded( container, function() {
				masonryObj = new Masonry( container, {
					itemSelector: '.astra-sites-library-template'
				});
			});
		},

		_autocomplete: function() {

			return;

			var tags = astraElementorSites.api_sites_and_pages_tags || [];
			var sites = astraElementorSites.default_page_builder_sites || [];

			// Add site & pages tags in autocomplete.
			var strings = [];
			for( tag_index in tags ) {
				strings.push( _.unescape( tags[ tag_index ]['name'] ));
			}

			// Add site title's in autocomplete.
			for( site_id in sites ) {
				var title = _.unescape( sites[ site_id ]['title'] );

				// @todo check why below character not escape with function _.unescape();
				title = title.replace('&#8211;', '-' );

				strings.push( title );
			}
			
			strings = strings.filter(function(item, pos) {
			    return strings.indexOf(item) == pos;
			})
			strings = _.sortBy( strings );

		    $scope.find( "#wp-filter-search-input" ).autocomplete({
		    	appendTo: ".astra-sites-autocomplete-result",
		    	classes: {
				    "ui-autocomplete": "astra-sites-auto-suggest"
				},
		    	source: function(request, response) {
			        var results = $.ui.autocomplete.filter(strings, request.term);

			        // Show only 10 results.
			        response(results.slice(0, 15));
			    },
		    	open: function( event, ui ) {
		    		$('.search-form').addClass( 'Searching' );
		    	},
		    	close: function( event, ui ) {
		    		$('.search-form').removeClass( 'Searching' );
		    	}
		    });

		    $scope.find( "#wp-filter-search-input" ).focus();
		},

		_enableImport: function() {

			if ( 'pages' == AstraElementorSitesAdmin.type ) {

				AstraElementorSitesAdmin._importWPForm( AstraElementorSitesAdmin.templateData['astra-site-wpforms-path'], function( form_response ) {

					fetch( AstraElementorSitesAdmin.templateData['astra-page-api-url'] ).then(response => {
						return response.json();
					}).then( data => {
						AstraElementorSitesAdmin.insertData = data;
						if ( 'insert' == AstraElementorSitesAdmin.action ) {
							AstraElementorSitesAdmin._insertDemo( data );
						} else {
							AstraElementorSitesAdmin._createTemplate( data );
						}
					}).catch( err => {
						console.log( err );
					});
				});

			} else {

				AstraElementorSitesAdmin._importWPForm( AstraElementorSitesAdmin.templateData['post-meta']['astra-site-wpforms-path'], function( form_response ) {
					AstraElementorSitesAdmin.insertData = AstraElementorSitesAdmin.templateData;
					if ( 'insert' == AstraElementorSitesAdmin.action ) {
						AstraElementorSitesAdmin._insertDemo( AstraElementorSitesAdmin.templateData );
					} else {
						AstraElementorSitesAdmin._createTemplate( AstraElementorSitesAdmin.templateData );
					}
				});


			}
		},

		_insert: function( e ) {

			if ( ! AstraElementorSitesAdmin.canInsert ) {
				return;
			}

			AstraElementorSitesAdmin.canInsert = false;
			var str = ( AstraElementorSitesAdmin.type == 'pages' ) ? 'Template' : 'Block';

			$( this ).addClass( 'installing' );
			$( this ).text( 'Importing ' + str + '...' );

			AstraElementorSitesAdmin.action = 'insert';

			AstraElementorSitesAdmin._bulkPluginInstallActivate();
		},

		_insertDemo: function( data ) {

			if ( undefined !== data && undefined !== data[ 'post-meta' ][ '_elementor_data' ] ) {

				// let templateModel = elementor.getPanelView().getCurrentPageView();
				let page_content = JSON.parse( data[ 'post-meta' ][ '_elementor_data' ]);
				let page_settings = '';
				let api_url = '';

				if ( 'blocks' == AstraElementorSitesAdmin.type ) {
					api_url = astraElementorSites.ApiURL + 'astra-blocks/' + data['id'];
				} else {
					api_url = AstraElementorSitesAdmin.templateData['astra-page-api-url'];
				}

				$.ajax({
					url  : astraElementorSites.ajaxurl,
					type : 'POST',
					data : {
						action : 'astra-page-elementor-batch-process',
						id : elementor.config.document.id,
						url : api_url
					},
				})
				.fail(function( jqXHR ){
					console.log( jqXHR );
				})
				.done(function ( response ) {

					page_content = response.data;

					console.log(page_content);

					if ( undefined !== data[ 'post-meta' ][ '_elementor_page_settings' ] ) {
						page_settings = PHP.parse( data[ 'post-meta' ][ '_elementor_page_settings' ] );
					}

					if ( '' != page_settings ) {
						if ( undefined != page_settings.astra_sites_page_setting_enable ) {
								page_settings.astra_sites_page_setting_enable = 'yes';
						}

						if ( undefined != page_settings.astra_sites_body_font_family ) {
							page_settings.astra_sites_body_font_family = page_settings.astra_sites_body_font_family.replace( /'/g, '' );
						}

						for ( var i = 1; i < 7; i++ ) {

							if ( undefined != page_settings['astra_sites_heading_' + i + '_font_family'] ) {
								page_settings['astra_sites_heading_' + i + '_font_family'] = page_settings['astra_sites_heading_' + i + '_font_family'].replace( /'/g, '' );
							}
						}
					}

					if ( undefined !== page_content && '' !== page_content ) {
						//elementor.channels.data.trigger('template:before:insert', templateModel);
						elementor.getPreviewView().addChildModel( page_content, { at : AstraElementorSitesAdmin.index } || {} );
						//elementor.channels.data.trigger('template:after:insert', templateModel);
						elementor.settings.page.model.setExternalChange( page_settings );
					}
					AstraElementorSitesAdmin.insertActionFlag = true;
					AstraElementorSitesAdmin._close();
				});
			}
		},

		_goBack: function( e ) {

			let step = $( this ).attr( 'data-step' );

			$scope.find( '.astra-sites-step-1-wrap' ).show();
			$scope.find( '.astra-preview-actions-wrap' ).remove();

			$scope.find( '#wp-filter-search-input' ).val( '' );

			if ( 'pages' == AstraElementorSitesAdmin.type ) {

				if ( 3 == step ) {
					$( this ).attr( 'data-step', 2 );
					$( document ).trigger( 'astra-sites__elementor-goback-step-2' );
				} else if ( 2 == step ) {
					$( this ).attr( 'data-step', 1 );
					$( document ).trigger( 'astra-sites__elementor-goback-step-1' );
				}
			} else {
				$( this ).attr( 'data-step', 1 );
				$( document ).trigger( 'astra-sites__elementor-goback-step-1' );
			}

			$scope.find( '.astra-sites-content-wrap' ).trigger( 'scroll' );
		},

		_goStep1: function( e ) {


			// Reset site and page ids to null.
			AstraElementorSitesAdmin.site_id = '';
			AstraElementorSitesAdmin.page_id = '';
			AstraElementorSitesAdmin.block_id = '';
			AstraElementorSitesAdmin.requiredPlugins = [];
			AstraElementorSitesAdmin.templateData = {};
			AstraElementorSitesAdmin.canImport = false;
			AstraElementorSitesAdmin.canInsert = false;

			// Hide Back button.
			$scope.find( '.back-to-layout' ).css( 'visibility', 'hidden' );
			$scope.find( '.back-to-layout' ).css( 'opacity', '0' );

			// Hide Preview Page.
			$scope.find( '.theme-preview' ).hide();
			$scope.find( '.theme-preview' ).html( '' );
			$scope.find( '.theme-preview-block' ).hide();
			$scope.find( '.theme-preview-block' ).html( '' );

			// Show listing page.
			if( AstraElementorSitesAdmin.type == 'pages' ) {

				$scope.find( '.dialog-lightbox-content' ).show();
				$scope.find( '.dialog-lightbox-content-block' ).hide();

				// Set listing HTML.
				AstraElementorSitesAdmin._appendSites( astraElementorSites.default_page_builder_sites );
			} else {

				// Set listing HTML.
				AstraElementorSitesAdmin._appendBlocks( astraElementorSites.astra_blocks );

				$scope.find( '.dialog-lightbox-content-block' ).show();
				$scope.find( '.dialog-lightbox-content' ).hide();
			}
		},

		_goStep2: function( e ) {

			// Set page and site ids.
			AstraElementorSitesAdmin.site_id = $scope.find( '#astra-blocks' ).data( 'site-id' );
			AstraElementorSitesAdmin.page_id = '';

			// Single Preview template.
			let single_template = wp.template( 'astra-sites-list-search' );
			let passing_data = astraElementorSites.default_page_builder_sites[ AstraElementorSitesAdmin.site_id ]['pages'];
			passing_data['site_id'] = AstraElementorSitesAdmin.site_id;
			pages_list = single_template( passing_data );
			$scope.find( '.dialog-lightbox-content' ).html( pages_list );

			// Hide Preview page.
			$scope.find( '.theme-preview' ).hide();
			$scope.find( '.theme-preview' ).html( '' );
			$scope.find( '.theme-preview-block' ).hide();
			$scope.find( '.theme-preview-block' ).html( '' );

			// Show listing page.
			$scope.find( '.dialog-lightbox-content' ).show();
			$scope.find( '.dialog-lightbox-content-block' ).hide();

			AstraElementorSitesAdmin._loadLargeImages();
		},

		_step1: function( e ) {

			if ( 'pages' == AstraElementorSitesAdmin.type ) {

				let passing_data = astraElementorSites.default_page_builder_sites[ AstraElementorSitesAdmin.site_id ]['pages'];

				var count = 0;
				var one_page = [];
				var one_page_id = '';

				for ( key in passing_data ) {
					if ( undefined == passing_data[ key ]['site-pages-type'] ) {
						continue;
					}
					if ( 'gutenberg' == passing_data[key]['site-pages-page-builder'] ) {
						continue;
					}
					count++;
					one_page = passing_data[ key ];
					one_page_id = key;
				}

				if ( count == 1 ) {

					// Logic for one page sites.
					AstraElementorSitesAdmin.page_id = one_page_id;

					$scope.find( '.back-to-layout' ).css( 'visibility', 'visible' );
					$scope.find( '.back-to-layout' ).css( 'opacity', '1' );

					$scope.find( '.back-to-layout' ).attr( 'data-step', 2 );
					$( document ).trigger( 'astra-sites__elementor-do-step-2' );

					return;
				}


				let single_template = wp.template( 'astra-sites-list-search' );
				passing_data['site_id'] = AstraElementorSitesAdmin.site_id;
				pages_list = single_template( passing_data );
				$scope.find( '.dialog-lightbox-content-block' ).hide();
				$scope.find( '.astra-sites-step-1-wrap' ).show();
				$scope.find( '.astra-preview-actions-wrap' ).remove();
				$scope.find( '.theme-preview' ).hide();
				$scope.find( '.theme-preview' ).html( '' );
				$scope.find( '.theme-preview-block' ).hide();
				$scope.find( '.theme-preview-block' ).html( '' );
				$scope.find( '.dialog-lightbox-content' ).show();
				$scope.find( '.dialog-lightbox-content' ).html( pages_list );

				AstraElementorSitesAdmin._loadLargeImages();

			} else {

				$scope.find( '.dialog-lightbox-content' ).hide();
				$scope.find( '.dialog-lightbox-content-block' ).hide();
				$scope.find( '.dialog-lightbox-message' ).animate({ scrollTop: 0 }, 50 );
				$scope.find( '.theme-preview-block' ).show();

				// Hide.
				$scope.find( '.theme-preview' ).hide();
				$scope.find( '.theme-preview' ).html( '' );

				let import_template = wp.template( 'astra-sites-elementor-preview' );
				let import_template_header = wp.template( 'astra-sites-elementor-preview-actions' );
				let template_object = astraElementorSites.astra_blocks[ AstraElementorSitesAdmin.block_id ];

				template_object['id'] = AstraElementorSitesAdmin.block_id;

				preview_page_html = import_template( template_object );
				$scope.find( '.theme-preview-block' ).html( preview_page_html );

				$scope.find( '.astra-sites-step-1-wrap' ).hide();

				preview_action_html = import_template_header( template_object );
				$scope.find( '.elementor-templates-modal__header__items-area' ).before( preview_action_html );
				AstraElementorSitesAdmin._masonry();

				let actual_id = AstraElementorSitesAdmin.block_id.replace( 'id-', '' );
				$( document ).trigger( 'astra-sites__elementor-plugin-check', { 'id': actual_id } );
			}
		},

		_step2: function( e ) {

			$scope.find( '.dialog-lightbox-content' ).hide();
			$scope.find( '.dialog-lightbox-message' ).animate({ scrollTop: 0 }, 50 );
			$scope.find( '.theme-preview' ).show();

			let import_template = wp.template( 'astra-sites-elementor-preview' );
			let import_template_header = wp.template( 'astra-sites-elementor-preview-actions' );
			let template_object = astraElementorSites.default_page_builder_sites[ AstraElementorSitesAdmin.site_id ]['pages'][ AstraElementorSitesAdmin.page_id ];

			template_object['id'] = AstraElementorSitesAdmin.site_id;

			preview_page_html = import_template( template_object );
			$scope.find( '.theme-preview' ).html( preview_page_html );

			$scope.find( '.astra-sites-step-1-wrap' ).hide();

			preview_action_html = import_template_header( template_object );
				$scope.find( '.elementor-templates-modal__header__items-area' ).before( preview_action_html );

			let actual_id = AstraElementorSitesAdmin.page_id.replace( 'id-', '' );
			$( document ).trigger( 'astra-sites__elementor-plugin-check', { 'id': actual_id } );
		},

		_preview : function( e ) {

			let step = $( this ).attr( 'data-step' );

			AstraElementorSitesAdmin.site_id = $( this ).closest( '.astra-theme' ).data( 'site-id' );
			AstraElementorSitesAdmin.page_id = $( this ).closest( '.astra-theme' ).data( 'template-id' );
			AstraElementorSitesAdmin.block_id = $( this ).closest( '.astra-theme' ).data( 'block-id' );

			$scope.find( '.back-to-layout' ).css( 'visibility', 'visible' );
			$scope.find( '.back-to-layout' ).css( 'opacity', '1' );

			if ( 1 == step ) {

				$scope.find( '.back-to-layout' ).attr( 'data-step', 2 );
				$( document ).trigger( 'astra-sites__elementor-do-step-1' );

			} else {

				$scope.find( '.back-to-layout' ).attr( 'data-step', 3 );
				$( document ).trigger( 'astra-sites__elementor-do-step-2' );

			}
		},

		_pluginCheck : function( e, data ) {

			var api_post = {
				slug: 'site-pages' + '/' + data['id']
			};

			if ( 'blocks' == AstraElementorSitesAdmin.type ) {
				api_post = {
					slug: 'astra-blocks' + '/' + data['id']
				};
			}


			var params = {
				method: 'GET',
				cache: 'default',
			};

			fetch( astraElementorSites.ApiURL + api_post.slug, params ).then( response => {
				if ( response.status === 200 ) {
					return response.json().then(items => ({
						items 		: items,
						items_count	: response.headers.get( 'x-wp-total' ),
						item_pages	: response.headers.get( 'x-wp-totalpages' ),
					}))
				} else {
					//$(document).trigger( 'astra-sites-api-request-error' );
					return response.json();
				}
			})
			.then(data => {
				if( 'object' === typeof data ) {
					if ( undefined !== data && undefined !== data['items'] ) {
						AstraElementorSitesAdmin.templateData = data['items'];
						AstraElementorSitesAdmin._requiredPluginsMarkup( data['items']['site-pages-required-plugins'] );
					}
			   	}
			});
		},

		_requiredPluginsMarkup: function( requiredPlugins ) {

			if( '' === requiredPlugins ) {
				return;
			}

			if (
				AstraElementorSitesAdmin.type == 'pages' &&
				astraElementorSites.default_page_builder_sites[AstraElementorSitesAdmin.site_id]['astra-sites-type'] != undefined &&
				astraElementorSites.default_page_builder_sites[AstraElementorSitesAdmin.site_id]['astra-sites-type'] != 'free'
			) {

				if ( ! astraElementorSites.license_status ) {

					output = '<p class="ast-validate">' + astraElementorSites.license_msg + '</p>';

					$scope.find('.required-plugins-list').html( output );
					$scope.find('.ast-tooltip-wrap').css( 'opacity', 1 );
					$scope.find('.astra-sites-tooltip').css( 'opacity', 1 );

					/**
					 * Enable Demo Import Button
					 * @type number
					 */
					AstraElementorSitesAdmin.requiredPlugins = [];
					AstraElementorSitesAdmin.canImport = true;
					AstraElementorSitesAdmin.canInsert = true;
					$scope.find( '.astra-sites-import-template-action > div' ).removeClass( 'disabled' );
					return;
				}

			}

		 	// Required Required.
			$.ajax({
				url  : astraElementorSites.ajaxurl,
				type : 'POST',
				data : {
					action           : 'astra-required-plugins',
					_ajax_nonce      : astraElementorSites._ajax_nonce,
					required_plugins : requiredPlugins
				},
			})
			.fail(function( jqXHR ){
				console.log( jqXHR );
			})
			.done(function ( response ) {

				var output = '';

				/**
				 * Count remaining plugins.
				 * @type number
				 */
				var remaining_plugins = 0;
				var required_plugins_markup = '';

				required_plugins = response.data['required_plugins'];				

				if( response.data['third_party_required_plugins'].length ) {
					output += '<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>';
				}

				/**
				 * Not Installed
				 *
				 * List of not installed required plugins.
				 */
				if ( typeof required_plugins.notinstalled !== 'undefined' ) {

					// Add not have installed plugins count.
					remaining_plugins += parseInt( required_plugins.notinstalled.length );

					$( required_plugins.notinstalled ).each(function( index, plugin ) {
						if ( 'elementor' == plugin.slug ) {
							return;
						}
						output += '<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>';
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
						if ( 'elementor' == plugin.slug ) {
							return;
						}
						output += '<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>';
					});
				}

				/**
				 * Active
				 *
				 * List of not active required plugins.
				 */
				if ( typeof required_plugins.active !== 'undefined' ) {

					$( required_plugins.active ).each(function( index, plugin ) {
						if ( 'elementor' == plugin.slug ) {
							return;
						}
						output += '<li class="plugin-card plugin-card-'+plugin.slug+'" data-slug="'+plugin.slug+'" data-init="'+plugin.init+'" data-name="'+plugin.name+'">'+plugin.name+'</li>';
					});
				}

				if ( '' != output ) {
					output = '<li class="plugin-card-head"><strong>Install Required Plugins</strong></li>' + output;
					$scope.find('.required-plugins-list').html( output );
					$scope.find('.ast-tooltip-wrap').css( 'opacity', 1 );
					$scope.find('.astra-sites-tooltip').css( 'opacity', 1 );
				}


				/**
				 * Enable Demo Import Button
				 * @type number
				 */
				AstraElementorSitesAdmin.requiredPlugins = response.data['required_plugins'];
				AstraElementorSitesAdmin.canImport = true;
				AstraElementorSitesAdmin.canInsert = true;
				$scope.find( '.astra-sites-import-template-action > div' ).removeClass( 'disabled' );
			});
		},

		_libraryClick: function( e ) {
			$scope.find( ".elementor-template-library-menu-item" ).each( function() {
				$(this).removeClass( 'elementor-active' );
			} );
			$( this ).addClass( 'elementor-active' );
		},

		_loadLargeImage: function( el ) {

			if( el.hasClass('loaded') ) {
				return;
			}

			if( el.parents('.astra-theme').isInViewport() ) {
				var large_img_url = el.data('src') || '';
				var imgLarge = new Image();
				imgLarge.src = large_img_url; 
				imgLarge.onload = function () {
					el.removeClass('loading');
					el.addClass('loaded');
					el.css('background-image', 'url(\''+imgLarge.src+'\'' );
				};
			}
		},

		_loadLargeImages: function() {
			$scope.find('.theme-screenshot').each(function( key, el ) {
				AstraElementorSitesAdmin._loadLargeImage( $(el) );
			});
		},

		_close: function( e ) {
			$( document ).trigger( 'astra-sites__elementor-close-before' );
			setTimeout( function() { $scope.fadeOut(); }, 300 );
			$( document ).trigger( 'astra-sites__elementor-close-after' );
		},

		_open: function( e ) {
			$( document ).trigger( 'astra-sites__elementor-open-before' );
			AstraElementorSitesAdmin.index = $( this ).closest( '.elementor-add-section' ).prevAll().length;
			AstraElementorSitesAdmin._home();
			$scope.fadeIn();
			$( document ).trigger( 'astra-sites__elementor-open-after' );
		},

		_beforeOpen: function( e ) {

			// Hide preview page.
			$scope.find( '.theme-preview' ).hide();
			$scope.find( '.theme-preview' ).html( '' );

			// Show site listing page.
			$scope.find( '.dialog-lightbox-content' ).show();

			// Hide Back button.
			$scope.find( '.back-to-layout' ).css( 'visibility', 'hidden' );
			$scope.find( '.back-to-layout' ).css( 'opacity', '0' );
		},

		_initSites: function( e ) {
			AstraElementorSitesAdmin._appendSites( astraElementorSites.default_page_builder_sites );
			AstraElementorSitesAdmin._goBack();
		},

		_initBlocks: function( e ) {
			AstraElementorSitesAdmin._appendBlocks( astraElementorSites.astra_blocks );
			AstraElementorSitesAdmin._goBack();
		},

		/**
		 * Install Success
		 */
		_installSuccess: function( event, response ) {

			event.preventDefault();

			// Transform the 'Install' button into an 'Activate' button.
			var $init = $( '.plugin-card-' + response.slug ).data('init');
			var $name = $( '.plugin-card-' + response.slug ).data('name');

			// Reset not installed plugins list.
			var pluginsList = AstraElementorSitesAdmin.requiredPlugins.notinstalled;
			var curr_plugin = AstraElementorSitesAdmin._getPluginFromQueue( response.slug, pluginsList );

			AstraElementorSitesAdmin.requiredPlugins.notinstalled = AstraElementorSitesAdmin._removePluginFromQueue( response.slug, pluginsList );


			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				console.log( 'Activating Plugin - ' + curr_plugin.name );

				$.ajax({
					url: astraElementorSites.ajaxurl,
					type: 'POST',
					data: {
						'action' : 'astra-required-plugin-activate',
						'init' : curr_plugin.init,
					},
				})
				.done(function (result) {

					if( result.success ) {
						var pluginsList = AstraElementorSitesAdmin.requiredPlugins.inactive;

						console.log( 'Activated Plugin - ' + curr_plugin.name );

						// Reset not installed plugins list.
						AstraElementorSitesAdmin.requiredPlugins.inactive = AstraElementorSitesAdmin._removePluginFromQueue( response.slug, pluginsList );

						// Enable Demo Import Button
						AstraElementorSitesAdmin._enableImport();

					}
				});

			}, 1200 );

		},

		/**
		 * Plugin Installation Error.
		 */
		_installError: function( event, response ) {

			// var $card = $( '.plugin-card-' + response.slug );
			// var $name = $card.data('name');

			// AstraElementorSitesAdmin._log_title( response.errorMessage + ' ' + AstraElementorSitesAdmin.ucwords($name) );


			// $card
			// 	.removeClass( 'button-primary' )
			// 	.addClass( 'disabled' )
			// 	.html( wp.updates.l10n.installFailedShort );

		},

		/**
		 * Installing Plugin
		 */
		_pluginInstalling: function(event, args) {
			// event.preventDefault();

			// var $card = $( '.plugin-card-' + args.slug );
			// var $name = $card.data('name');

			// AstraElementorSitesAdmin._log_title( 'Installing Plugin - ' + AstraElementorSitesAdmin.ucwords( $name ));

			// $card.addClass('updating-message');

		},
	};

	/**
	 * Initialize AstraElementorSitesAdmin
	 */
	$(function(){
		AstraElementorSitesAdmin.init();
	});

})(jQuery);
