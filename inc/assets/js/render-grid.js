(function($){

	AstraRender = {

		_ref			: null,

		/**
		 * _api_params = {
		 * 		'search'                  : '',
		 * 		'per_page'                : '',
		 * 		'astra-site-category'     : '',
		 * 		'astra-site-page-builder' : '',
		 * 		'page'                    : '',
		 *   };
		 *
		 * E.g. per_page=<page-id>&astra-site-category=<category-ids>&astra-site-page-builder=<page-builder-ids>&page=<page>
		 */
		_api_params		: {},
		_breakpoint		: 768,
		_has_default_page_builder : false,
		_first_time_loaded : true,
	
		init: function()
		{
			this._resetPagedCount();
			this._bind();
			this._loadPageBuilders();
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
			$( document ).on('astra-sites-api-request-error'   , AstraRender._addSuggestionBox );
			$( document ).on('astra-sites-api-request-fail'    , AstraRender._addSuggestionBox );
			$( document ).on('astra-api-post-loaded-on-scroll' , AstraRender._reinitGridScrolled );
			$( document ).on('astra-api-post-loaded'           , AstraRender._reinitGrid );
			$( document ).on('astra-api-page-builder-loaded'       , AstraRender._addPageBuilders );
			$( document ).on('astra-api-category-loaded'   			, AstraRender._loadFirstGrid );
			
			// Event's for API request.
			$( document ).on('click'                           , '.filter-links a', AstraRender._filterClick );
			$( document ).on('keyup input'                     , '#wp-filter-search-input', AstraRender._search );
			$( document ).on('scroll'                          , AstraRender._scroll );
			$( document ).on('astra-sites-api-request-fail', AstraRender._site_unreachable );
		},

		/**
		 * Website is Down
		 *
		 * @since 1.2.11
		 * @return null
		 */
		_site_unreachable: function( event, jqXHR, textStatus, args ) {
			event.preventDefault();
			if ( 'astra-site-page-builder' === args.id ) {
				$('#astra-sites-admin').html( wp.template('astra-site-down') )
			}
		},

		/**
		 * On Filter Clicked
		 *
		 * Prepare Before API Request:
		 * - Empty search input field to avoid search term on filter click.
		 * - Remove Inline Height
		 * - Added 'hide-me' class to hide the 'No more sites!' string.
		 * - Added 'loading-content' for body.
		 * - Show spinner.
		 */
		_filterClick: function( event ) {

			event.preventDefault();

			if( $( this ).parents('.astra-site-category').length && ! $('body').hasClass('page-builder-selected') ) {
				return;
			}

			$(this).parents('.filter-links').find('a').removeClass('current');
			$(this).addClass('current');

			// Prepare Before Search.
			$('.no-more-demos').addClass('hide-me');
			$('.astra-sites-suggestions').remove();

			// Empty the search input only click on category filter not on page builder filter.
			if( $(this).parents('.filter-links').hasClass('astra-site-category') ) {
				$('#wp-filter-search-input').val('');
			}
			$('#astra-sites').hide().css('height', '');

			$('body').addClass('loading-content');
			$('#astra-sites-admin').find('.spinner').removeClass('hide-me');

	        // Show sites.
			AstraRender._showSites();
		},

		/**
		 * Search Site.
		 *
		 * Prepare Before API Request:
		 * - Remove Inline Height
		 * - Added 'hide-me' class to hide the 'No more sites!' string.
		 * - Added 'loading-content' for body.
		 * - Show spinner.
		 */
		_search: function() {

			if( ! $('body').hasClass('page-builder-selected') ) {
				return;
			}

			$this = jQuery('#wp-filter-search-input').val();

			// Prepare Before Search.
			$('#astra-sites').hide().css('height', '');			
			$('.no-more-demos').addClass('hide-me');
			$('.astra-sites-suggestions').remove();

			$('body').addClass('loading-content');
			$('#astra-sites-admin').find('.spinner').removeClass('hide-me');

			window.clearTimeout(AstraRender._ref);
			AstraRender._ref = window.setTimeout(function () {
				AstraRender._ref = null;

				AstraRender._resetPagedCount();
				jQuery('body').addClass('loading-content');
				jQuery('body').attr('data-astra-demo-search', $this);

				AstraRender._showSites();

			}, 500);

		},

		/**
		 * On Scroll
		 */
		_scroll: function(event) {

			if( ! $('body').hasClass('page-builder-selected') ) {
				return;
			}

			if( ! $('body').hasClass('listed-all-sites') ) {

				var scrollDistance = jQuery(window).scrollTop();

				var themesBottom = Math.abs(jQuery(window).height() - jQuery('#astra-sites').offset().top - jQuery('#astra-sites').height());
				themesBottom = themesBottom - 100;

				ajaxLoading = jQuery('body').data('scrolling');

				if (scrollDistance > themesBottom && ajaxLoading == false) {
					AstraRender._updatedPagedCount();

					if( ! $('#astra-sites .no-themes').length ) {
						$('#astra-sites-admin').find('.spinner').addClass('is-active');
					}

					jQuery('body').data('scrolling', true);
					
					/**
					 * @see _reinitGridScrolled() which called in trigger 'astra-api-post-loaded-on-scroll'
					 */
					AstraRender._showSites( false, 'astra-api-post-loaded-on-scroll' );
				}
			}
		},

		_apiAddParam_status: function() {
			if( astraRenderGrid.sites && astraRenderGrid.sites.status ) {
				AstraRender._api_params['status'] = astraRenderGrid.sites.status;
			}
		},

		// Add 'search'
		_apiAddParam_search: function() {
			var search_val = jQuery('#wp-filter-search-input').val() || '';
			if( '' !== search_val ) {
				AstraRender._api_params['search'] = search_val;
			}
		},

		_apiAddParam_per_page: function() {
			// Add 'per_page'
			var per_page_val = 30;
			if( astraRenderGrid.sites && astraRenderGrid.sites["par-page"] ) {
				per_page_val = parseInt( astraRenderGrid.sites["par-page"] );
			}
			AstraRender._api_params['per_page'] = per_page_val;
		},

		_apiAddParam_astra_site_category: function() {
			// Add 'astra-site-category'
			var selected_category_id = jQuery('.filter-links.astra-site-category').find('.current').data('group') || '';
			if( '' !== selected_category_id && 'all' !== selected_category_id ) {
				AstraRender._api_params['astra-site-category'] =  selected_category_id;
			} else if( astraRenderGrid.sites && astraRenderGrid['categories'].include ) {
				if( AstraRender._isArray( astraRenderGrid['categories'].include ) ) {
					AstraRender._api_params['astra-site-category'] = astraRenderGrid['categories'].include.join(',');
				} else {
					AstraRender._api_params['astra-site-category'] = astraRenderGrid['categories'].include;
				}
			}
		},

		_apiAddParam_siteground: function() {
			if( astraRenderGrid['siteground'] ) {
				AstraRender._api_params['siteground'] = astraRenderGrid['siteground'];
			}
		},

		_apiAddParam_astra_site_page_builder: function() {
			// Add 'astra-site-page-builder'
			var selected_page_builder_id = jQuery('.filter-links.astra-site-page-builder').find('.current').data('group') || '';
			if( '' !== selected_page_builder_id && 'all' !== selected_page_builder_id ) {
				AstraRender._api_params['astra-site-page-builder'] =  selected_page_builder_id;
			} else if( astraRenderGrid.sites && astraRenderGrid['page-builders'].include ) {
				if( AstraRender._isArray( astraRenderGrid['page-builders'].include ) ) {
					AstraRender._api_params['astra-site-page-builder'] = astraRenderGrid['page-builders'].include.join(',');
				} else {
					AstraRender._api_params['astra-site-page-builder'] = astraRenderGrid['page-builders'].include;
				}
			}
		},

		_apiAddParam_page: function() {
			// Add 'page'
			var page_val = parseInt(jQuery('body').attr('data-astra-demo-paged')) || 1;
			AstraRender._api_params['page'] = page_val;
		},

		_apiAddParam_purchase_key: function() {
			if( astraRenderGrid.sites && astraRenderGrid.sites.purchase_key ) {
				AstraRender._api_params['purchase_key'] = astraRenderGrid.sites.purchase_key;
			}
		},

		_apiAddParam_site_url: function() {
			if( astraRenderGrid.sites && astraRenderGrid.sites.site_url ) {
				AstraRender._api_params['site_url'] = astraRenderGrid.sites.site_url;
			}
		},

		/**
		 * Show Sites
		 * 
		 * 	Params E.g. per_page=<page-id>&astra-site-category=<category-ids>&astra-site-page-builder=<page-builder-ids>&page=<page>
		 *
		 * @param  {Boolean} resetPagedCount Reset Paged Count.
		 * @param  {String}  trigger         Filtered Trigger.
		 */
		_showSites: function( resetPagedCount, trigger ) {

			if( undefined === resetPagedCount ) {
				resetPagedCount = true
			}

			if( undefined === trigger ) {
				trigger = 'astra-api-post-loaded';
			}

			if( resetPagedCount ) {
				AstraRender._resetPagedCount();
			}

			// Add Params for API request.
			AstraRender._api_params = {};

			AstraRender._apiAddParam_status();
			AstraRender._apiAddParam_search();
			AstraRender._apiAddParam_per_page();
			AstraRender._apiAddParam_astra_site_category();
			AstraRender._apiAddParam_siteground();
			AstraRender._apiAddParam_page();
			AstraRender._apiAddParam_astra_site_page_builder();
			AstraRender._apiAddParam_site_url();
			AstraRender._apiAddParam_purchase_key();

			// API Request.
			var api_post = {
				id: 'astra-sites',
				slug: 'astra-sites?' + decodeURIComponent( $.param( AstraRender._api_params ) ),
				trigger: trigger,
			};

			AstraSitesAPI._api_request( api_post );

		},

		/**
		 * Get Category Params
		 *
		 * @since 1.2.4
		 * @param  {string} category_slug Category Slug.
		 * @return {mixed}               Add `include=<category-ids>` in API request.
		 */
		_getPageBuilderParams: function()
		{
			var _params = {};

			if( astraRenderGrid.default_page_builder ) {
				_params['search'] = astraRenderGrid.default_page_builder;
			}

			if( astraRenderGrid.sites && astraRenderGrid.sites.purchase_key ) {
				_params['purchase_key'] = astraRenderGrid.sites.purchase_key;
			}

			if( astraRenderGrid.sites && astraRenderGrid.sites.site_url ) {
				_params['site_url'] = astraRenderGrid.sites.site_url;
			}

			if( astraRenderGrid.sites && astraRenderGrid['page-builders'].include ) {
				if( AstraRender._isArray( astraRenderGrid['page-builders'].include ) ) {
					_params['include'] = astraRenderGrid['page-builders'].include.join(',');
				} else {
					_params['include'] = astraRenderGrid['page-builders'].include;
				}
			}

			var decoded_params = decodeURIComponent( $.param( _params ) );

			if( decoded_params.length ) {
				return '/?' + decoded_params;
			}

			return '/';
		},

		/**
		 * Get Category Params
		 * 
		 * @param  {string} category_slug Category Slug.
		 * @return {mixed}               Add `include=<category-ids>` in API request.
		 */
		_getCategoryParams: function( category_slug ) {

			var _params = {};

			if( astraRenderGrid.sites && astraRenderGrid['categories'].include ) {
				if( AstraRender._isArray( astraRenderGrid['categories'].include ) ) {
					_params['include'] = astraRenderGrid['categories'].include.join(',');
				} else {
					_params['include'] = astraRenderGrid['categories'].include;
				}
			}

			var decoded_params = decodeURIComponent( $.param( _params ) );

			if( decoded_params.length ) {
				return '/?' + decoded_params;
			}

			return '/';
		},

		/**
		 * Get All Select Status
		 * 
		 * @param  {string} category_slug Category Slug.
		 * @return {boolean}              Return true/false.
		 */
		_getCategoryAllSelectStatus: function( category_slug ) {	

			// Has category?
			if( category_slug in astraRenderGrid.settings ) {

				// Has `all` in stored list?
				if( $.inArray('all', astraRenderGrid.settings[ category_slug ]) === -1 ) {
					return false;
				}
			}

			return true;
		},

		/**
		 * Show Filters
		 */
		_loadPageBuilders: function() {

			// Is Welcome screen?
			// Then pre-send the API request to avoid the loader.
			if( $('.astra-sites-welcome').length ) {

				var plugins = $('.astra-sites-welcome').attr( 'data-plugins' ) || '';
				var plugins = plugins.split(",");

				// Also, Send page builder request with `/?search=` parameter. Because, We send the selected page builder request 
				// Which does not cached due to extra parameter `/?search=`. For that we initially send all these requests.
				$.each(plugins, function( key, plugin) {
					var category_slug = 'astra-site-page-builder';
					var category = {
						slug          : category_slug + '/?search=' + plugin,
						id            : category_slug,
						class         : category_slug,
						trigger       : '',
						wrapper_class : 'filter-links',
						show_all      : false,
					};

					// Pre-Send `sites` request for each active page builder to avoid the loader.
					AstraSitesAPI._api_request( category, function( data ) {
						if( data.items ) {

							var per_page_val = 30;
							if( astraRenderGrid.sites && astraRenderGrid.sites["par-page"] ) {
								per_page_val = parseInt( astraRenderGrid.sites["par-page"] );
							}
							
							var api_params = {
												per_page : per_page_val,
												page : 1,
											};
							// Load `all` sites from each page builder.
							$.each(data.items, function(index, item) {

								if( item.id ) {
									api_params['astra-site-page-builder'] =  item.id;

									// API Request.
									var api_post = {
										id: 'astra-sites',
										slug: 'astra-sites?' + decodeURIComponent( $.param( api_params ) ),
									};

									AstraSitesAPI._api_request( api_post );
								}
							});
						}
					});

				} );

				// Pre-Send `category` request to avoid the loader.
				var category_slug = 'astra-site-category';
				var category = {
					slug          : category_slug + '/',
					id            : category_slug,
					class         : category_slug,
					trigger       : '',
					wrapper_class : 'filter-links',
					show_all      : false,
				};
				AstraSitesAPI._api_request( category );

			// Load `sites` from selected page builder.
			} else {
				var category_slug = 'astra-site-page-builder';
				var category = {
					slug          : category_slug + AstraRender._getPageBuilderParams(),
					id            : category_slug,
					class         : category_slug,
					trigger       : 'astra-api-page-builder-loaded',
					wrapper_class : 'filter-links',
					show_all      : false,
				};

				AstraSitesAPI._api_request( category );
			}
		},

		/**
		 * Load First Grid.
		 *
		 * This is triggered after all category loaded.
		 * 
		 * @param  {object} event Event Object.
		 */
		_loadFirstGrid: function( event, data ) {
			
			event.preventDefault();

			if( $('#' + data.args.id).length ) {
				var template = wp.template('astra-site-filters');
				$('#' + data.args.id).html(template( data ));

				if( 'true' === $('body').attr( 'data-default-page-builder-selected' ) ) {
					$('#' + data.args.id).find('li:first a').addClass('current');
					AstraRender._showSites();
				} else {
					$('body').removeClass('loading-content');
					if( ! $('#astra-sites-admin .astra-site-select-page-builder').length ) {
						$('#astra-sites-admin').append( wp.template( 'astra-site-select-page-builder' ) );
					}
				}
			} else {
				AstraRender._showSites();
			}

		},

		/**
		 * Append filters.
		 * 
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		_addPageBuilders: function( event, data ) {
			event.preventDefault();

			if( $('#' + data.args.id).length ) {
				var template = wp.template('astra-site-filters');
				$('#' + data.args.id).html(template( data ));

				if( 1 === parseInt( data.items_count ) ) {
					$('body').attr( 'data-default-page-builder-selected', true );
					$('#' + data.args.id).find('li:first a').addClass('current');
				}
			}

			/**
			 * Categories
			 */
			var category_slug = 'astra-site-category';
			var category = {
				slug          : category_slug + AstraRender._getCategoryParams( category_slug ),
				id            : category_slug,
				class         : category_slug,
				trigger       : 'astra-api-category-loaded',
				wrapper_class : 'filter-links',
				show_all      : AstraRender._getCategoryAllSelectStatus( category_slug ),
			};

			AstraSitesAPI._api_request( category );

		},
		

		/**
		 * Append sites on scroll.
		 * 
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		_reinitGridScrolled: function( event, data ) {

			var template = wp.template('astra-sites-list');

			if( data.items.length > 0 ) {

				$('body').removeClass( 'loading-content' );
				$('.filter-count .count').text( data.items_count );

				setTimeout(function() {
					jQuery('#astra-sites').append(template( data ));

					AstraRender._imagesLoaded();
				}, 800);
			} else {
				$('body').addClass('listed-all-sites');
			}

		},

		/**
		 * Update Astra sites list.
		 * 
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		_reinitGrid: function( event, data ) {

			var template = wp.template('astra-sites-list');

			$('body').addClass( 'page-builder-selected' );
			$('body').removeClass( 'loading-content' );
			$('.filter-count .count').text( data.items_count );

			jQuery('body').attr('data-astra-demo-last-request', data.items_count);

			jQuery('#astra-sites').show().html(template( data ));

			AstraRender._imagesLoaded();

			$('#astra-sites-admin').find('.spinner').removeClass('is-active');

			if( data.items_count <= 0 ) {
				$('#astra-sites-admin').find('.spinner').removeClass('is-active');
				$('.no-more-demos').addClass('hide-me');
				$('.astra-sites-suggestions').remove();

			} else {
				$('body').removeClass('listed-all-sites');
			}

			// Re-Send `categories` sites request to avoid the loader.
			var categories = AstraSitesAPI._stored_data['astra-site-category'];
			if( categories && AstraRender._first_time_loaded ) {
				
				var per_page_val = 30;
				if( astraRenderGrid.sites && astraRenderGrid.sites["par-page"] ) {
					per_page_val = parseInt( astraRenderGrid.sites["par-page"] );
				}
				
				var api_params = {
					per_page : per_page_val,
				};

				var page_builder_id = $('#astra-site-page-builder').find('.current').data('group') || '';

				$.each( categories, function( index, category ) {

					api_params['astra-site-category'] =  category.id;

					api_params['page'] = 1;

					if( page_builder_id ) {
						api_params['astra-site-page-builder'] = page_builder_id;
					}

					if( astraRenderGrid.sites && astraRenderGrid.sites.site_url ) {
						api_params['site_url'] = astraRenderGrid.sites.site_url;
					}
					if( astraRenderGrid.sites && astraRenderGrid.sites.purchase_key ) {
						api_params['purchase_key'] = astraRenderGrid.sites.purchase_key;
					}

					// API Request.
					var api_post = {
						id: 'astra-sites',
						slug: 'astra-sites?' + decodeURIComponent( $.param( api_params ) ),
					};

					AstraSitesAPI._api_request( api_post );
				} );

				AstraRender._first_time_loaded = false;
			}

		},

		/**
		 * Check image loaded with function `imagesLoaded()`
		 */
		_imagesLoaded: function() {

			var self = jQuery('#sites-filter.execute-only-one-time a');
			
			$('.astra-sites-grid').imagesLoaded()
			.always( function( instance ) {
				if( jQuery( window ).outerWidth() > AstraRender._breakpoint ) {
					// $('#astra-sites').masonry('reload');
				}

				$('#astra-sites-admin').find('.spinner').removeClass('is-active');
			})
			.progress( function( instance, image ) {
				var result = image.isLoaded ? 'loaded' : 'broken';
			});

		},

		/**
		 * Add Suggestion Box
		 */
		_addSuggestionBox: function() {
			$('#astra-sites-admin').find('.spinner').removeClass('is-active').addClass('hide-me');

			$('#astra-sites-admin').find('.no-more-demos').removeClass('hide-me');
			var template = wp.template('astra-sites-suggestions');
			if( ! $( '.astra-sites-suggestions').length ) {
				$('#astra-sites').append( template );
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

			jQuery('body').attr('data-astra-demo-last-request', '1');
			jQuery('body').attr('data-astra-demo-paged', '1');
			jQuery('body').attr('data-astra-demo-search', '');
			jQuery('body').attr('data-scrolling', false);

		},

		// Returns if a value is an array
		_isArray: function(value) {
			return value && typeof value === 'object' && value.constructor === Array;
		}

	};

	/**
	 * Initialize AstraRender
	 */
	$(function(){
		AstraRender.init();
	});

})(jQuery);