(function($){

    AstraImages = {

        init: function() {

            if ( undefined != wp && wp.media ) {

                var View = wp.media.View,
                    mediaTrash = wp.media.view.settings.mediaTrash,
                    l10n = wp.media.view.l10n,
                    Frame = wp.media.view.Frame,
                    $ = jQuery,
                    newVar = {},
                    Select = wp.media.view.MediaFrame.Select;

                wp.media.view.AstraAttachmentsBrowser = require( './frame.js' );

                Select.prototype.bindHandlers = function() {

                	this.on("router:create:browse", this.createRouter, this);
                    this.on("router:render:browse", this.browseRouter, this);
                    this.on("content:create:browse", this.browseContent, this);
                    this.on("content:create:astraimages", this.astraimages, this);
                    this.on("content:render:upload", this.uploadContent, this);
                    this.on("toolbar:create:select", this.createSelectToolbar, this);
                }

                Select.prototype.browseRouter = function( routerView ) {

                    routerView.set({
                        upload: {
                            text:     l10n.uploadFilesTitle,
                            priority: 20
                        },
                        browse: {
                            text:     l10n.mediaLibraryTitle,
                            priority: 40
                        },
                        astraimages: {
                            text:     astraImages.title,
                            priority: 70
                        }
                    });
                }

                Select.prototype.astraimages = function( contentRegion ) {
                    var state = this.state();

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

