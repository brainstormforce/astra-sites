(function($){

	AstraSitesAPI = {

		/**
		 * API Request
		 */
		_api_request: function( args, callback ) {

			var params = {
				method: 'GET',
	            cache: 'default',
           	};

			if( astraSitesVars.headers ) {
				params['headers'] = astraSitesVars.headers;
			}

			fetch( astraSitesVars.ApiURL + args.slug, params).then(response => {
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
						astraSitesVars.stored_data[ args.id ] = $.merge( astraSitesVars.stored_data[ data.args.id ], data.items );
					}
					data['args']['favorites'] = astraSitesVars.favorite_data;

					if( 'undefined' !== typeof args.trigger && '' !== args.trigger ) {
						$(document).trigger( args.trigger, [data] );
					}

					if( callback && typeof callback == "function"){
						callback( data );
				    }
			   	}
			});

		},

		/**
		 * API Request
		 */
		_api_single_request: function( args, callback ) {

			var params = {
				method: 'GET',
	            cache: 'default',
           	};

			if( astraSitesVars.headers ) {
				params['headers'] = astraSitesVars.headers;
			}

			fetch( astraSitesVars.ApiURL + args.slug, params).then(response => {
				if ( response.status === 200 ) {
					return response.json();
				} else {
					$(document).trigger( 'astra-sites-api-request-error' );
					return response.json();
				}
			})
			.then(data => {
				if( 'object' === typeof data ) {
					// data['args']['favorites'] = astraSitesVars.favorite_data;

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
