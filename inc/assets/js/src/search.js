var l10n = wp.media.view.l10n
$ = jQuery
// Search input view controller.
AstraSearch = wp.Backbone.View.extend({

    tagName: 'input',
    className: 'ast-image__search',
    id: 'ast-image-search-input',
    searching: false,
    images: [],
    attributes: {
        placeholder: astraImages.search_placeholder,
        type: 'search',
        'aria-describedby': 'live-search-desc'
    },

    events: {
        'search' : 'search',
        'keyup': 'search',
        'blur': 'pushState',
        'infinite': 'infinite',
    },

    initialize: function( options ) {

        this.parent = options.parent;
    },

    infinite: function( event ) {

        // Since doSearch is debounced, it will only run when user input comes to a rest.
        this.doSearch( event );
    },

    search: function( event ) {

        // Clear on escape.
        if ( event.type === 'keyup' && event.which === 27 ) {
            event.target.value = '';
        }
        if( '' == event.target.value ) {
            this.$el.removeClass('has-input');
        } else {
            this.$el.addClass('has-input');
        }

        $scope.find( '.ast-image__skeleton' ).animate({ scrollTop: 0 }, 0 );
        $( 'body' ).data( 'page', 1 );
        AstraImageCommon.infiniteLoad = false;

        let thisObject = this;
        setTimeout( function(){
            thisObject.doSearch( event );
        }, 1000 );
    },

    // Runs a search on the theme collection.
    doSearch: function( event ) {

        if ( this.searching ) {
            return;
        }

        var options = {};
        let thisObject = this;
        thisObject.searching = true;
        AstraImageCommon.config.q = event.target.value;
        var url = astraImages.pixabay_url + '?' + $.param( AstraImageCommon.config );

        if ( url ) {
            fetch( url ).then(function (response) {
                return response.json();
            }).then(function (result) {
                thisObject.searching = false;
                this.images = result.hits;
                wp.media.view.AstraAttachmentsBrowser.images = this.images;
                $( document ).trigger( 'ast-image__refresh' );
            });
        }
    },

    pushState: function( event ) {
        $( document ).trigger( 'ast-image__refresh' );
    }
});

module.exports = AstraSearch;