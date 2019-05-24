(function($){

	AstraSitesTracking = {

		init: function()
		{
			//$( document ).on('click', '.theme-browser .theme-screenshot, .theme-browser .more-details, .theme-browser .install-theme-preview', AstraSitesTracking._preview);
			console.log(trackingData);
			
			$( document ).on('click', '.astra-demo-import', AstraSitesTracking._importDemo);

			$( document ).on('click', '.install-now', AstraSitesTracking._installNow);
			
			$( document ).on('site-pages-import-wpforms-done', AstraSitesTracking._importPage );

			$( document ).on( 'astra-sites-tracking-preview', AstraSitesTracking._trackPreview );
		},

		_trackPreview: function() {
			let params = trackingData.params
			AstraSitesTracking._track( params, 'preview' );
		},

		_track: function( data, type ) {

			console.log(trackingData.url);
			console.log( data );

			$.ajax({
				url  : trackingData.url,
				type : 'POST',
				data : {
					type: type,
					params: data
				}
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