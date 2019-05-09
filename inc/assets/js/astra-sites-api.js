(function($){

	AstraSitesAPI = {

		_api_url      : astraSitesApi.ApiURL,
		_stored_data  : {
			'astra-site-category' : [],
			'astra-site-page-builder': [],
			'astra-sites' : [],
		},

		/**
		 * API Request
		 */
		_api_request: function( args, callback ) {

			var params = {
				method: 'GET',
	            cache: 'default',
           	};

			if( astraRenderGrid.headers ) {
				params['headers'] = astraRenderGrid.headers;
			}

			fetch( AstraSitesAPI._api_url + args.slug, params).then(response => {
				if ( response.status === 200 ) {
					return response.json().then(items => ({
						items 		: items,
						items_count	: response.headers.get( 'x-wp-total' ),
						item_pages	: response.headers.get( 'x-wp-totalpages' ),
					}))
				} else {
					$(document).trigger( 'astra-sites-api-request-error' );
					return response.json();
				}
			})
			.then(data => {
				if( 'object' === typeof data ) {
					data['args'] = args;
					if( data.args.id ) {
						AstraSitesAPI._stored_data[ args.id ] = $.merge( AstraSitesAPI._stored_data[ data.args.id ], data.items );
					}

					if( 'undefined' !== typeof args.trigger && '' !== args.trigger ) {
						$(document).trigger( args.trigger, [data] );
					}

					if( callback && typeof callback == "function"){
						callback( data );
				    }
			   	}
			});

		},

	};

})(jQuery);
