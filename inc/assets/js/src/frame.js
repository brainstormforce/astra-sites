var Frame = wp.media.view.Frame,
    AstraAttachmentsBrowser;

wp.media.view.AstraContent = require( './content.js' );

AstraAttachmentsBrowser = Frame.extend({
    tagName:   'div',
    className: 'attachments-browser ast-attachments-browser',
    images : [],
    object: [], 
    initialize: function() {
        _.defaults( this.options, {
            filters: false,
            search:  true,
            date:    true,
            display: false,
            sidebar: true,
            AttachmentView: wp.media.view.Attachment.Library
        });

        // Add a heading before the attachments list.
        this.createContent();
    },

    createContent: function() {

        this.attachmentsHeading = new wp.media.view.Heading( {
            text: astraImages.title,
            level: 'h3',
            className: 'ast-media-views-heading'
        } );
        // this.views.add( this.attachmentsHeading );
        this.views.add( new wp.media.view.AstraContent );
        this.$el.find( '.ast-image__search' ).wrapAll( '<div class="ast-image__search-wrap">' ).parent().html();
        this.$el.find( '.ast-image__search-wrap' ).append( '<span class="icon-search search-icon"></span>' );
    },

    photoUploadComplete: function( savedImage ) {
        if ( savedImage && savedImage.attachmentData) {
            this.model.frame.content.mode("browse")
            this.model.get("selection").add( savedImage.attachmentData )
            this.model.frame.trigger("library:selection:add")
            this.model.get("selection")
            jQuery(".media-frame .media-button-select").click()
        }
    }
});

module.exports = AstraAttachmentsBrowser;