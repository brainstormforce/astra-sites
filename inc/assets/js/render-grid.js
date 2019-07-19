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
			this._load_large_images();
			//this._loadParentPageCategory();
			// this._loadPageBuilders();
		},

		/**
		 * load large image
		 * 
		 * @return {[type]} [description]
		 */
		_load_large_images: function() {
			$('.theme-screenshot').each(function( key, el) {
				var large_img_url = $(el).data('src') || '';
				var imgLarge = new Image();
				imgLarge.src = large_img_url; 
				imgLarge.onload = function () {
					$(el).removeClass('loading');
					$(el).css('background-image', 'url(\''+imgLarge.src+'\'' );
				};
			});
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
			// $( document ).on('astra-sites-api-request-error'   , AstraRender._addSuggestionBox );
			// $( document ).on('astra-sites-api-request-fail'    , AstraRender._addSuggestionBox );
			// $( document ).on('astra-api-post-loaded-on-scroll' , AstraRender._reinitGridScrolled );
			// $( document ).on('astra-api-post-loaded'           , AstraRender._reinitGrid );
			// $( document ).on('astra-api-page-builder-loaded'       , AstraRender._addPageBuilders );
			// $( document ).on('astra-api-page-parent-loaded'       , AstraRender._addParentCategory );
			// $( document ).on('astra-api-category-loaded'   			, AstraRender._loadFirstGrid );
			// $( document ).on('astra-pages-fetched', AstraRender._pagesFetched );
			$( document ).on('click', '#astra-sites > .astra-theme .theme-screenshot', AstraRender._pagesFetched );
			
			// // Event's for API request.
			// $( document ).on('click'                           , '.filter-links a', AstraRender._filterClick );
			// $( document ).on('click'                     , '#astra-sites .theme-screenshot', AstraRender._previewPages);
			$( document ).on('click'                     , '#single-pages .site-single', AstraRender._change_site_preview_screenshot);
			$( document ).on('click'                     , '.favorite-action-wrap', AstraRender._favoriteAction);
			$( document ).on('keyup input'                     , '#wp-filter-search-input', AstraRender._search );
			// $( document ).on('scroll'                          , AstraRender._scroll );
			// $( document ).on('astra-sites-api-request-fail', AstraRender._site_unreachable );
			$( document ).on('click', '.astra-pages-back', AstraRender._go_back );
			$( document ).on('keydown', AstraRender._next_and_previous_sites );

			$( document ).on('focus'                     , '#wp-filter-search-input', AstraRender._show_filters );
			$( document ).on('blur'                      , '#wp-filter-search-input', AstraRender._hide_filters );
			$( document ).on('click', '.astra-site-category a', AstraRender._filterSites );
			$( document ).on('click', '.astra-sites-sync-library-button', AstraRender._sync_library );
		},

		_sync_library: function( event ) {
			event.preventDefault();
			var button = $(this);

			if( button.hasClass( 'updating-message') ) {
				return;
			}

			button.addClass( 'updating-message');

			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				data : {
					action : 'astra-sites-update-library',
				},
			})
			.fail(function( jqXHR ){
				console.log( jqXHR );
		    })
			.done(function ( response ) {
				console.log(response);
				button.removeClass( 'updating-message');
			});
		},

		_filterSites: function( event ) {
			event.preventDefault();
			var current_class = $(this).attr('data-group') || '';
			console.log( current_class );
			$( this ).parents('.filter-links').find('a').removeClass( 'current' );
			$( this ).addClass( 'current' );

			var search_term = $( this ).text() || '';

			if( current_class ) {
				if( $('#astra-sites .astra-theme.'+current_class).length ) {
					$('#wp-filter-search-input').val( search_term );

					// $('#astra-sites .astra-theme').removeClass('astra-show-site astra-hide-site');
					$('#astra-sites .astra-theme').addClass( 'astra-hide-site' );
					$('#astra-sites .astra-theme.'+current_class).removeClass( 'astra-hide-site' ).addClass( 'astra-show-site');
				}
			} else {
				$('#astra-sites .astra-theme').removeClass( 'astra-hide-site' ).addClass( 'astra-show-site');
			}

			$('.filters-wrap-page-categories').removeClass('show');
		},

		_show_filters: function(){
			$('#wp-filter-search-input').val( '' );
			$('.filters-wrap-page-categories').addClass('show');
		},

		_hide_filters: function( event ){
			event.preventDefault();
			setTimeout(function() {
				$('.filters-wrap-page-categories').removeClass('show');
			}, 100);
		},

		_next_and_previous_sites: function(e) {

	        if( ! $('body').addClass('astra-previewing-single-pages') ) {
	        	return;
	        }

	        if( e.key === "Escape") {
	        	AstraRender.close_pages_popup();
	        	return;
	        }

	        switch(e.which) {
	
	            // Left Key Pressed
	            case 37:
	            		if( $('#astra-sites > .astra-theme.current').prev().length ) {
		            		$('#astra-sites > .astra-theme.current').prev().addClass('current').siblings().removeClass('current');
		  					var site_id = $('#astra-sites > .astra-theme.current').prev().attr('data-site-id') || '';
		  					if( site_id ) {
		  						AstraRender.show_pages_by_site_id( site_id );
		  					}
	            		}
	                break;

	            // Right Key Pressed
	            case 39:
	            		if( $('#astra-sites > .astra-theme.current').next().length ) {
		            		$('#astra-sites > .astra-theme.current').next().addClass('current').siblings().removeClass('current');
		  					var site_id = $('#astra-sites > .astra-theme.current').next().attr('data-site-id') || '';
		  					if( site_id ) {
		  						console.log( site_id );
		  						AstraRender.show_pages_by_site_id( site_id );
		  					}
	            		}
	       //          console.log('next');
	                break;
	        }


		},

		_favoriteAction: function( event ) {

			let is_favorite = $( this ).data( 'favorite' );
			let site_id = $( this ).parents( '.astra-theme' ).data( 'site-id' ).toString();
			let new_array = Array();

			$( this ).toggleClass( 'is-favorite' );
			$( this ).data( 'favorite', ! is_favorite );

			if ( ! is_favorite ) {
				// Add.
				for ( value in AstraSitesAPI._favorite_data ) {
					new_array.push( AstraSitesAPI._favorite_data[value] );
				}
				new_array.push( site_id );
			} else {
				// Remove.
				for ( value in AstraSitesAPI._favorite_data ) {
					if ( site_id != AstraSitesAPI._favorite_data[value].toString() ) {
						new_array.push( AstraSitesAPI._favorite_data[value] );
					}
				}
			}
			AstraSitesAPI._favorite_data = new_array;

			$.ajax({
				url  : astraSitesAdmin.ajaxurl,
				type : 'POST',
				dataType: 'json',
				data : {
					action          : 'astra-sites-favorite',
					is_favorite 	: !is_favorite,
					site_id 		: site_id
				},
			})
			.fail(function( jqXHR ){
				AstraSitesAdmin._log_title( jqXHR.status + ' ' + jqXHR.responseText + ' ' + jqXHR.statusText, true );
		    })
			.done(function ( response ) {
				console.log(response);
			});
		},

		/**
		 * Preview Inner Pages for the Site
		 *
		 * @since x.x.x
		 * @return null
		 */
		_change_site_preview_screenshot: function( event ) {
			event.preventDefault();

			var url = $(this).find( '.theme-screenshot' ).attr( 'data-src' ) || '';
			var demo_id = $(this).attr( 'data-demo-id' ) || '';
			var page_name = $(this).find('.theme-name').text() || '';

			$( this ).siblings().removeClass( 'current_page' );
			$( this ).addClass( 'current_page' );

			$( '.site-import-layout-button' ).removeClass( 'disabled' ).attr('data-demo-id', demo_id);
			if( page_name ) {
				$( '.site-import-layout-button' ).text('Import "'+page_name.trim()+'" Layout');
			}

			if( url ) {
				$('.single-site-preview img').attr( 'src', url );
			}

		},

		// _previewPages: function( event ) {

		// 	event.preventDefault();

		// 	var site_id = $( this ).parents( '.astra-theme' ).data('demo-id') || '';
		// 	var demo_id = $( this ).parents( '.astra-theme' ).data( 'demo-parent' ) || '';

		// 	AstraSitesAdmin.current_site = AstraSitesAdmin._get_site_details( site_id );

		// 	if( 'undefined' !== typeof demo_id && '' !== demo_id ) {

		// 		// API Request.
		// 		var api_post = {
		// 			id: 'site-pages',
		// 			slug: 'site-pages' + '?site-pages-parent-site=' + demo_id,
		// 			trigger: 'astra-pages-fetched',
		// 		};

		// 		AstraSitesAPI._api_request( api_post );

		// 	} else {
		// 		$( document ).trigger( 'astra-pages-fetched', [ { items: {} } ] );
		// 	}

		// },

		/**
		 * Set the pages into HTML
		 *
		 * @since x.x.x
		 * @return null
		 */
		_pagesFetched: function( event ) {

			var perent = $(this).parents('.astra-theme');
			perent.siblings().removeClass('current');
			perent.addClass('current');

			var site_id = perent.attr('data-site-id') || '';
			AstraRender.show_pages_by_site_id( site_id );
		},

		show_pages_by_site_id: function( site_id ) {

			var sites = astraRenderGrid.api_sites_and_pages || [];

			var data = sites[site_id];
			console.log( site_id );
			console.log( sites );
			console.log( data );

			var site_template  = wp.template('astra-sites-single-site-preview');
			var pages_template = wp.template('astra-sites-list');
			data['type'] = 'site-pages';

			// $('body').addClass( 'child-site-selected' );
			// $('body').removeClass( 'loading-content' );

			$('#astra-sites-filters').hide();
			$('#astra-sites').hide();
			// $('html,body').animate({scrollTop:0},50);
			$('#astra-pages-back-wrap').show();

			$('#site-pages').show().html( site_template( data ) );
			$('body').addClass('astra-previewing-single-pages');
			$('#site-pages').attr( 'data-site-id', site_id);

			AstraRender._load_large_images();

			// if( data ) {
			// 	$('.single-site-pages-wrap .astra-site-title').html( AstraSitesAdmin.current_site.title.rendered );
			// 	$('.single-site-pages-wrap .count').show().html( data.items_count + ' layouts' );
			// 	$('#single-pages').html( pages_template( data ) );
			// }
		},

		/**
		 * Go back to all sites view
		 *
		 * @since x.x.x
		 * @return null
		 */
		_go_back: function( event ) {

			event.preventDefault();
	
			AstraRender.close_pages_popup();
		},

		close_pages_popup: function( ) {
			astraSitesApi.cpt_slug = 'astra-sites';

			$('#astra-sites-filters').show();
			$('#astra-sites').show();
			$('#astra-pages-back-wrap').hide();
			$('#site-pages').hide().html( '' );
			$('body').removeClass('astra-previewing-single-pages');
			// $('html, body').animate({
		 //        scrollTop: $('#astra-sites > .astra-theme.current').offset().top - 80
		 //    }, 800);
			$('#astra-sites > .astra-theme').removeClass('current');			
		},

		/**
		 * Website is Down
		 *
		 * @since 1.2.11
		 * @return null
		 */
		// _site_unreachable: function( event, jqXHR, textStatus, args ) {
		// 	event.preventDefault();
		// 	if ( 'astra-site-page-builder' === args.id ) {
		// 		$('#astra-sites-admin').html( wp.template('astra-site-down') )
		// 	}
		// },

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
		// _filterClick: function( event ) {

		// 	event.preventDefault();

		// 	if ( $( this ).closest( '.filters-wrap' ).hasClass( 'favorite-filters-wrap' ) ) {

		// 		$( this ).toggleClass( "current" );
		// 		$('#astra-sites').hide().css('height', '');

		// 		$('body').addClass('loading-content');
		// 		$('#astra-sites-admin').find('.spinner').removeClass('hide-me');

		// 		// Show Favorite sites.
		// 		AstraRender._showFavoriteSites();
		// 		return;
		// 	}

		// 	if( $( this ).parents( '.filter-links[data-category="' + astraSitesApi.category_slug + '"]' ).length && ! $('body').hasClass('page-builder-selected') ) {
		// 		return;
		// 	}

		// 	$(this).parents('.filter-links').find('a').removeClass('current');
		// 	$( '.favorite-filters-wrap' ).find('a').removeClass('current');
		// 	$(this).addClass('current');

		// 	// Prepare Before Search.
		// 	$('.no-more-demos').addClass('hide-me');
		// 	$('.astra-sites-suggestions').remove();

		// 	// Empty the search input only click on category filter not on page builder filter.
		// 	if( $( this ).parents( '.filter-links[data-category="' + astraSitesApi.category_slug + '"]' ).length > 0 ) {
		// 		$('#wp-filter-search-input').val('');
		// 	}
		// 	$('#astra-sites').hide().css('height', '');

		// 	$('body').addClass('loading-content');
		// 	$('#astra-sites-admin').find('.spinner').removeClass('hide-me');

		// 	// Show sites.
		// 	AstraRender._showSites();
		// },

		/**
		 * Show all favorite sites
		 */
		_showFavoriteSites : function () {

			let fav_array = AstraSitesAPI._favorite_data;
			let all_sites = AstraSitesAPI._stored_data['astra-sites'];
			let data = Array();
			let check_array = Array();

			data['items'] = Array();
			data['args'] = Array();
			data['args']['favorites'] = Array();

			for( let i = 0; i < all_sites.length; i++ ) {

				for ( value in fav_array ) {
					if ( fav_array[value] == all_sites[i].id ) {

						if ( ! check_array.includes( all_sites[i].id ) ) {
							data['items'].push( all_sites[i] );
							check_array.push( all_sites[i].id );
							data['args']['favorites'].push( fav_array[value] );
						}
					}
				};

			}

			data['items_count'] = data['items'].length;

			let template = wp.template('astra-sites-list');

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

			var search_term   = $( this ).val() || '',
				sites         = $('#astra-sites > .astra-theme'),
				titles = $('#astra-sites > .astra-theme .theme-name');

			$('.filters-wrap-page-categories').removeClass('show');

			var current_class = $('.astra-site-category').find('.current' ).attr('data-group') || '';

			if( search_term.length ) {

				// Hide all sites. Because, Below we show those sites
				// which have search term in the site title.
				sites.addClass('astra-hide-site').removeClass('astra-show-site');

				// Search site and ONLY show these sites which "contain" the site title.
				var rex = new RegExp( search_term, 'i');
		        titles = titles.filter(function () {
					var site_name = $.trim( $(this).text() ) || '';
		        	return rex.test( site_name );
		        });

		        // Has selected category filter? Then only show selected category sites.
		        if( current_class ) {
		        	titles.parents('.astra-theme.'+current_class).removeClass('astra-hide-site').addClass('astra-show-site');
		        } else {
		        	titles.parents('.astra-theme').removeClass('astra-hide-site').addClass('astra-show-site');
		        }

		        if( $('.astra-show-site').length ) {
		        	$('.astra-sites-no-sites').hide();
		        } else {
		        	$('.astra-sites-no-sites').show();
		        }

			} else {

		        // Has selected category filter? Then only show selected category sites.
				if( current_class ) {
					$('#astra-sites > .astra-theme.'+current_class).removeClass('astra-hide-site').addClass('astra-show-site');
		        } else {
					// Show all sites.
					sites.removeClass('astra-hide-site').addClass('astra-show-site');
		        }

		        $('.astra-sites-no-sites').hide();
			}
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
			if( astraRenderGrid.sites && astraRenderGrid.sites["per-page"] ) {
				per_page_val = parseInt( astraRenderGrid.sites["per-page"] );
			}
			AstraRender._api_params['per_page'] = per_page_val;
		},

		_apiAddParam_astra_site_category: function() {
			// Add 'astra-site-category'
			var selected_category_id = jQuery( '.filter-links[data-category="' + astraSitesApi.category_slug + '"]' ).find('.current').data('group') || '';
			if( '' !== selected_category_id && 'all' !== selected_category_id ) {
				AstraRender._api_params[astraSitesApi.category_slug] =  selected_category_id;
			} else if( astraRenderGrid.sites && astraRenderGrid['categories'].include ) {
				if( AstraRender._isArray( astraRenderGrid['categories'].include ) ) {
					AstraRender._api_params[astraSitesApi.category_slug] = astraRenderGrid['categories'].include.join(',');
				} else {
					AstraRender._api_params[astraSitesApi.category_slug] = astraRenderGrid['categories'].include;
				}
			}
		},

		_apiAddParam_astra_page_parent_category: function() {

			// Add 'site-pages-parent-category'
			if ( '' == astraSitesApi.parent_category) {
				return;
			}

			var selected_category_id = jQuery( '.filter-links[data-category="' + astraSitesApi.parent_category + '"]' ).find('.current').data('group') || '';
			if( '' !== selected_category_id && 'all' !== selected_category_id ) {
				AstraRender._api_params[astraSitesApi.parent_category] =  selected_category_id;
			} else if( astraRenderGrid.sites && astraRenderGrid['categories'].include ) {
				if( AstraRender._isArray( astraRenderGrid['categories'].include ) ) {
					AstraRender._api_params[astraSitesApi.parent_category] = astraRenderGrid['categories'].include.join(',');
				} else {
					AstraRender._api_params[astraSitesApi.parent_category] = astraRenderGrid['categories'].include;
				}
			}
		},

		_apiAddParam_astra_site_page_builder: function() {
			// Add 'astra-site-page-builder'
			var selected_page_builder_id = jQuery( '.filter-links[data-category="' + astraSitesApi.page_builder + '"]' ).find('.current').data('group') || '';
			if( '' !== selected_page_builder_id && 'all' !== selected_page_builder_id ) {
				AstraRender._api_params[astraSitesApi.page_builder] =  selected_page_builder_id;
			} else if( astraRenderGrid.sites && astraRenderGrid['page-builders'].include ) {
				if( AstraRender._isArray( astraRenderGrid['page-builders'].include ) ) {
					AstraRender._api_params[astraSitesApi.page_builder] = astraRenderGrid['page-builders'].include.join(',');
				} else {
					AstraRender._api_params[astraSitesApi.page_builder] = astraRenderGrid['page-builders'].include;
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
		// _showSites: function( resetPagedCount, trigger ) {

		// 	if( undefined === resetPagedCount ) {
		// 		resetPagedCount = true
		// 	}

		// 	if( undefined === trigger ) {
		// 		trigger = 'astra-api-post-loaded';
		// 	}

		// 	if( resetPagedCount ) {
		// 		AstraRender._resetPagedCount();
		// 	}

		// 	// Add Params for API request.
		// 	AstraRender._api_params = {};

		// 	AstraRender._apiAddParam_status();
		// 	AstraRender._apiAddParam_search();
		// 	AstraRender._apiAddParam_per_page();
		// 	AstraRender._apiAddParam_astra_site_category();
		// 	AstraRender._apiAddParam_page();
		// 	AstraRender._apiAddParam_astra_site_page_builder();
		// 	AstraRender._apiAddParam_astra_page_parent_category();
		// 	AstraRender._apiAddParam_site_url();
		// 	AstraRender._apiAddParam_purchase_key();
		// 	// API Request.
		// 	var api_post = {
		// 		id: astraSitesApi.cpt_slug,
		// 		slug: astraSitesApi.cpt_slug + '?' + decodeURIComponent( $.param( AstraRender._api_params ) ),
		// 		trigger: trigger,
		// 	};

		// 	AstraSitesAPI._api_request( api_post );

		// },

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
		 * Get Parent Category Params
		 *
		 * @param  {string} category_slug Category Slug.
		 * @return {mixed}               Add `include=<category-ids>` in API request.
		 */
		_getParentCategoryParams: function( category_slug ) {

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
		_loadParentPageCategory: function() {

			if ( '' !== astraSitesApi.parent_category ) {

				/**
				 * Page Parent Site Category
				 */
				var parent_category = {
					slug          : astraSitesApi.parent_category + AstraRender._getParentCategoryParams(),
					id            : astraSitesApi.parent_category,
					class         : astraSitesApi.parent_category,
					trigger       : 'astra-api-page-parent-loaded',
					wrapper_class : 'filter-links',
					show_all      : AstraRender._getCategoryAllSelectStatus( astraSitesApi.parent_category ),
				};

				AstraSitesAPI._api_request( parent_category );
			}
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
				var category = {
					slug          : astraSitesApi.page_builder + AstraRender._getPageBuilderParams(),
					id            : astraSitesApi.page_builder,
					class         : astraSitesApi.page_builder,
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
		// _loadFirstGrid: function( event, data ) {

		// 	event.preventDefault();

		// 	if( $( '.filters-slug[data-id="' + data.args.id + '"]' ).length ) {
		// 		var template = wp.template('astra-site-filters');
		// 		$( '.filters-slug[data-id="' + data.args.id + '"]' ).html(template( data ));

		// 		if( 'true' === $('body').attr( 'data-default-page-builder-selected' ) ) {
		// 			$( '.filters-slug[data-id="' + data.args.id + '"]' ).find('li:first a').addClass('current');
		// 			AstraRender._showSites();
		// 		} else {
		// 			$('body').removeClass('loading-content');
		// 			if( ! $('#astra-sites-admin .astra-site-select-page-builder').length ) {
		// 				$('#astra-sites-admin').append( wp.template( 'astra-site-select-page-builder' ) );
		// 			}
		// 		}
		// 	} else {
		// 		AstraRender._showSites();
		// 	}

		// },

		/**
		 * Append filters for Parent Category.
		 *
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		// _addParentCategory: function(  event, data  ) {

		// 	event.preventDefault();

		// 	if( $( '.page-filters-slug[data-id="' + data.args.id + '"]' ).length ) {
		// 		var template = wp.template('astra-site-filters');
		// 		$( '.page-filters-slug[data-id="' + data.args.id + '"]' ).html(template( data ));
		// 		$( '.page-filters-slug[data-id="' + data.args.id + '"]' ).find('li:first a').addClass('current');
		// 	}
		// },

		/**
		 * Append filters.
		 *
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		// _addPageBuilders: function( event, data ) {
		// 	event.preventDefault();

		// 	if( $( '.filters-slug[data-id="' + data.args.id + '"]' ).length ) {
		// 		var template = wp.template('astra-site-filters');
		// 		$( '.filters-slug[data-id="' + data.args.id + '"]' ).html(template( data ));

		// 		if( 1 === parseInt( data.items_count ) ) {
		// 			$('body').attr( 'data-default-page-builder-selected', true );
		// 			$( '.filters-slug[data-id="' + data.args.id + '"]' ).find('li:first a').addClass('current');
		// 		}
		// 	}

		// 	*
		// 	 * Categories
			 
		// 	var category = {
		// 		slug          : astraSitesApi.category_slug + AstraRender._getCategoryParams( astraSitesApi.category_slug ),
		// 		id            : astraSitesApi.category_slug,
		// 		class         : astraSitesApi.category_slug,
		// 		trigger       : 'astra-api-category-loaded',
		// 		wrapper_class : 'filter-links',
		// 		show_all      : AstraRender._getCategoryAllSelectStatus( astraSitesApi.category_slug ),
		// 	};

		// 	AstraSitesAPI._api_request( category );

		// },


		/**
		 * Append sites on scroll.
		 *
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		// _reinitGridScrolled: function( event, data ) {

		// 	var template = wp.template('astra-sites-list');

		// 	if( data.items.length > 0 ) {

		// 		$('body').removeClass( 'loading-content' );
		// 		$('.filter-count .count').text( data.items_count );

		// 		setTimeout(function() {
		// 			jQuery('#astra-sites').append(template( data ));

		// 			AstraRender._imagesLoaded();
		// 		}, 800);
		// 	} else {
		// 		$('body').addClass('listed-all-sites');
		// 	}

		// },

		/**
		 * Update Astra sites list.
		 *
		 * @param  {object} event Object.
		 * @param  {object} data  API response data.
		 */
		// _reinitGrid: function( event, data ) {

		// 	var template = wp.template('astra-sites-list');

		// 	$('body').addClass( 'page-builder-selected' );
		// 	$('body').removeClass( 'loading-content' );
		// 	$('.filter-count .count').text( data.items_count );

		// 	jQuery('body').attr('data-astra-demo-last-request', data.items_count);

		// 	jQuery('#astra-sites').show().html(template( data ));

		// 	AstraRender._imagesLoaded();

		// 	$('#astra-sites-admin').find('.spinner').removeClass('is-active');

		// 	if( data.items_count <= 0 ) {
		// 		$('#astra-sites-admin').find('.spinner').removeClass('is-active');
		// 		$('.no-more-demos').addClass('hide-me');
		// 		$('.astra-sites-suggestions').remove();

		// 	} else {
		// 		$('body').removeClass('listed-all-sites');
		// 	}

		// 	// Re-Send `categories` sites request to avoid the loader.
		// 	var categories = AstraSitesAPI._stored_data['astra-site-category'];
		// 	if( categories && AstraRender._first_time_loaded ) {

		// 		var per_page_val = 30;
		// 		if( astraRenderGrid.sites && astraRenderGrid.sites["par-page"] ) {
		// 			per_page_val = parseInt( astraRenderGrid.sites["par-page"] );
		// 		}

		// 		var api_params = {
		// 			per_page : per_page_val,
		// 		};

		// 		var page_builder_id = $('#astra-site-page-builder').find('.current').data('group') || '';

		// 		$.each( categories, function( index, category ) {

		// 			api_params['astra-site-category'] =  category.id;

		// 			api_params['page'] = 1;

		// 			if( page_builder_id ) {
		// 				api_params['astra-site-page-builder'] = page_builder_id;
		// 			}

		// 			if( astraRenderGrid.sites && astraRenderGrid.sites.site_url ) {
		// 				api_params['site_url'] = astraRenderGrid.sites.site_url;
		// 			}
		// 			if( astraRenderGrid.sites && astraRenderGrid.sites.purchase_key ) {
		// 				api_params['purchase_key'] = astraRenderGrid.sites.purchase_key;
		// 			}

		// 			// API Request.
		// 			var api_post = {
		// 				id: 'astra-sites',
		// 				slug: 'astra-sites?' + decodeURIComponent( $.param( api_params ) ),
		// 			};

		// 			AstraSitesAPI._api_request( api_post );
		// 		} );

		// 		AstraRender._first_time_loaded = false;
		// 	}

		// },

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
		// _addSuggestionBox: function() {
		// 	$('#astra-sites-admin').find('.spinner').removeClass('is-active').addClass('hide-me');

		// 	$('#astra-sites-admin').find('.no-more-demos').removeClass('hide-me');
		// 	var template = wp.template('astra-sites-suggestions');
		// 	if( ! $( '.astra-sites-suggestions').length ) {
		// 		$('#astra-sites').append( template );
		// 	}
		// },

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