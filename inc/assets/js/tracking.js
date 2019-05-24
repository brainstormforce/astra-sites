(function($){

	AstraSitesTracking = {

		init: function()
		{
			console.log(trackingData);
			$( document ).on( 'astra-sites-tracking-preview', AstraSitesTracking._trackPreview );

			$( document ).on( 'astra-sites-tracking-import', AstraSitesTracking._trackImport );
		},

		_trackImport: function() {
			let params = trackingData.params
			AstraSitesTracking._track( params, 'import' );
		},

		_trackPreview: function() {
			let params = trackingData.params
			AstraSitesTracking._track( params, 'preview' );
		},

		_track: function( data, type ) {

			console.log( trackingData.url );
			console.log( AstraSitesAdmin );
			console.log( AstraSitesAdmin['templateData'] );

			let post_data = {
				type: type,
				url: AstraSitesAdmin.templateData.astra_demo_url,
				id: AstraSitesAdmin.templateData.id,
				params: data
			}

			console.log( post_data );

			$.ajax({
				url  : trackingData.url,
				type : 'POST',
				data : post_data
			})
			.fail(function( jqXHR ){
				//console.log( jqXHR );
		    })
			.done(function ( data ) {
				//console.log( data );
			});
		}

	};

	/**
	 * Initialize AstraSitesTracking
	 */
	$(function(){
		AstraSitesTracking.init();
	});

})(jQuery);