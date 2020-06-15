(function($){

    var AstraImages = {

        init: function() {

            if ( undefined != wp && wp.media ) {

                var $ = jQuery,
                    oldMediaFrameSelect = wp.media.view.MediaFrame.Select;

                wp.media.view.AstraAttachmentsBrowser = require( './frame.js' );

                wp.media.view.MediaFrame.Select = oldMediaFrameSelect.extend( {

                    // Tab / Router
                    browseRouter( routerView ) {
                        oldMediaFrameSelect.prototype.browseRouter.apply( this, arguments );
                        routerView.set( {
                            astraimages: {
                                text: astraImages.title,
                                priority: 70,
                            },
                        } );
                    },
                
                    // Handlers
                    bindHandlers() {
                        oldMediaFrameSelect.prototype.bindHandlers.apply( this, arguments );
                        this.on( 'content:create:astraimages', this.astraimages, this );
                    },
                
                    /**
                     * Render callback for the content region in the `browse` mode.
                     *
                     * @param {wp.media.controller.Region} contentRegion
                     */
                    astraimages( contentRegion ) {
                        const state = this.state();
                        // Browse our library of attachments.
                        let thisView = new wp.media.view.AstraAttachmentsBrowser({
                            controller: this,
                            model:      state,
                            AttachmentView: state.get( 'AttachmentView' )
                        });
                        contentRegion.view = thisView
                        wp.media.view.AstraAttachmentsBrowser.object = thisView
                        setTimeout( function() {
                            $( document ).trigger( 'ast-image__set-scope' );
                        }, 100 );
                    }
                    
                });
            }
        },

    };
    

    /**
     * Initialize AstraImages
     */
    $( function(){

        AstraImages.init();

        if ( astraImages.is_bb_active && astraImages.is_bb_editor ) {
            if ( undefined !== FLBuilder ) {
                if ( null !== FLBuilder._singlePhotoSelector ) {
                    FLBuilder._singlePhotoSelector.on( 'open', function( event ) {
                        AstraImages.init();
                    } );
                }
            }
        }

    });

})(jQuery);

