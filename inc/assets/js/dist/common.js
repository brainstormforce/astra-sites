(function($){

    $scope = {};

    AstraImageCommon = {

        images: {},
        image: {},
        action: '',
        offset: 0,
        loadingStatus: true,
        config: {
            key            : astraImages.integration['pixabay_api_key'],
            q              : '',
            lang           : 'en',
            image_type     : 'all',
            orientation    : 'all',
            category       : '',
            min_width      : 0,
            min_height     : 0,
            colors         : '',
            editors_choice : false,
            safesearch     : false,
            order          : 'popular',
            page           : $( 'body' ).data( 'page' ),
            per_page       : 30,
            callback       : '',
            pretty         : true
        },
        canSave: false,
        infiniteLoad: false,
        uploader: {},
        file: {},
        frame: {},
        isPreview: false,
        apiStatus: true,
        id : '',
        isValidating: false,

        init: function() {
            this._bind();
        },

        /**
         * Binds events for the Astra Sites.
         *
         * @since 1.0.0
         * @access private
         * @method _bind
         */
        _bind: function() {

            if ( 200 !== astraImages.api_status['code'] ) {
                AstraImageCommon.apiStatus = false;
            } else {
                AstraImageCommon.apiStatus = true;
            }

            // Triggers.
            $( document ).on( "ast-image__refresh", AstraImageCommon._initImages );
            $( document ).on( "ast-image__set-scope", AstraImageCommon._setScope );

            $( document ).on( "click", ".ast-image__list-img-overlay", AstraImageCommon._preview );
            $( document ).on( "click", ".ast-image__go-back-text", AstraImageCommon._goBack );
            $( document ).on( "click", ".ast-image__save", AstraImageCommon._save );
            $( document ).on( "click", ".ast-image__validate-btn" , AstraImageCommon._validate );
            $( document ).on( "change", ".ast-image__filter select", AstraImageCommon._filter );
            $( document ).on( "click", ".ast-image__edit-api", AstraImageCommon._editAPI );
            $( document ).on( "click", ".ast-image__browse-images", AstraImageCommon._browse );
        },

        _browse: function() {
            AstraImageCommon.apiStatus = true;
            $scope.find( '.ast-image__search' ).trigger( 'keyup' );
        },

        _editAPI: function( event ) {
            event.stopPropagation();
            AstraImageCommon.apiStatus = false;
            wp.media.view.AstraAttachmentsBrowser.images = [];
            $scope.find( '.ast-image__loader-wrap' ).show();
            $scope.find( '.ast-image__skeleton' ).html( '' );
            $scope.find( '.ast-image__skeleton' ).attr( 'style', '' );
            $scope.find( '.ast-image__search' ).trigger( 'keyup' );
            $scope.find( '.ast-image__loader-wrap' ).hide();
        },

        _filter: function() {
            let category = $scope.find( '.ast-image__filter-category select' ).val();
            let orientation = $scope.find( '.ast-image__filter-orientation select' ).val();
            let order = $scope.find( '.ast-image__filter-order select' ).val();

            AstraImageCommon.config.orientation = orientation;
            AstraImageCommon.config.category = category;
            AstraImageCommon.config.order = order;

            $scope.find( '.ast-image__search' ).trigger( 'keyup' );
            $scope.find( '.ast-image__loader-wrap' ).show();
        },

        _save: function() {

            if ( ! AstraImageCommon.canSave ) {
                return;
            }

            let thisBtn = $( this )

            if ( thisBtn.data( 'import-status' ) ) {
                return;
            }

            thisBtn.text( astraImages.downloading );
            thisBtn.addClass( 'installing' );

            AstraImageCommon.canSave = false;

            // Work with JSON page here
            $.ajax({
                url: astraImages.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    'action' : 'astra-sites-create-image',
                    'url' : AstraImageCommon.image.largeImageURL,
                    'name' : AstraImageCommon.image.tags,
                    'id' : AstraImageCommon.image.id,
                },
            })
            .fail(function( jqXHR ){
                console.log( jqXHR );
            })
            .done(function ( data ) {
                console.log(data.data);
                if ( undefined == data.data ) {
                    return;
                }
                astraImages.saved_images = data.data['updated-saved-images'];
                wp.media.view.AstraAttachmentsBrowser.object.photoUploadComplete( data.data );
                thisBtn.text( 'Done' );
                thisBtn.removeClass( 'installing' );
                AstraImageCommon._empty();
            });
        },

        _validate: function() {

            if ( AstraImageCommon.isValidating ) {
                return;
            }

            AstraImageCommon.isValidating = true;

            let thisBtn = $( this )
            let errWrap = $scope.find( '.ast-image__license-input-inner-wrap .ast-image__license-msg' );
            let type = $scope.find( '.ast-image__license' ).data( 'type' );
            let key = $scope.find( '.ast-image__license' ).val();
            $scope.find( '.ast-image-valid-license' ).hide();
            $scope.find( '.ast-image__browse-images' ).hide();

            if ( '' == key ) {
                AstraImageCommon.isValidating = false;
                errWrap.show();
                errWrap.find( 'span' ).text( astraImages.empty_api_key );
                setTimeout( function() {
                    errWrap.hide();
                    errWrap.find( 'span' ).text( '' );
                }, 2000 );
                return;
            }

            thisBtn.text( astraImages.validating );

            // Work with JSON page here
            $.ajax({
                url: astraImages.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    'action' : 'astra-sites-validate-license',
                    'key' : key,
                    'type' : type,
                },
            })
            .fail(function( jqXHR ){
                console.log( jqXHR );
            })
            .done(function ( response ) {
                console.log(response.data);
                astraImages.api_status = response.data;
                AstraImageCommon.isValidating = false;
                if ( 200 !== astraImages.api_status['code'] ) {
                    AstraImageCommon.apiStatus = false;
                    thisBtn.text( 'Validate Key' );
                    errWrap.show();
                    errWrap.find( 'span' ).text( astraImages.error_api_key + '"' + astraImages.api_status['code'] + '"' );
                    setTimeout( function() {
                        errWrap.hide();
                        errWrap.find( 'span' ).text( '' );
                    }, 2000 );
                } else {
                    AstraImageCommon.apiStatus = true;
                    AstraImageCommon.config.key = key;
                    astraImages.integration[ type + '_api_key'] = key;
                    $scope.find( '.ast-image__search' ).trigger( 'keyup' );
                }
            });
        },

        _empty: function() {

            AstraImageCommon.image = {};
            AstraImageCommon.canSave = false;
            AstraImageCommon.uploader = {};
            AstraImageCommon.file = {};
            AstraImageCommon.isPreview = false;
        },

        _goBack: function() {

            AstraImageCommon._empty();

            $( document ).trigger( 'ast-image__refresh' );

            $scope.find( '.ast-image__skeleton' ).show();
            $scope.removeClass( 'preview-mode' );
            $scope.find( '.ast-attachments-search-wrap' ).children().show();
            $scope.find( '.ast-image__go-back' ).remove();
            $scope.find( '.ast-image__save-wrap' ).remove();
            $scope.find( '.ast-image__preview-skeleton' ).hide();
            $scope.find( '.ast-image__preview-skeleton' ).html( '' );

            let wrapHeight = ( AstraImageCommon.offset - 210 );
            $scope.find( '.ast-image__skeleton-inner-wrap' ).css( 'height', wrapHeight );
        },

        _preview: function() {

            AstraImageCommon.isPreview = true;

            let height = ( AstraImageCommon.offset - 190 );
            $scope.find( '.ast-image__skeleton-inner-wrap' ).css( 'height', height );

            setTimeout( function() {
                $scope.find( '.ast-image__loader-wrap' ).hide();
            }, 200 );

            AstraImageCommon.image = $( this ).data( 'img-info' );

            let preview = wp.template( 'ast-image-single' );
            let single_html = preview( AstraImageCommon.image );

            let save_btn = wp.template( 'ast-image-save' );
            let single_btn = save_btn( AstraImageCommon.image );

            let wrapHeight = $scope.find( '.ast-image__skeleton-inner-wrap' ).outerHeight();
            wrapHeight = ( wrapHeight - 60 );

            $scope.find( '.ast-image__skeleton' ).hide();
            $scope.addClass( 'preview-mode' );
            $scope.find( '.ast-attachments-search-wrap' ).children().hide();
            $scope.find( '.ast-image__search-wrap' ).before( $( '#tmpl-ast-image-go-back' ).text() );
            $scope.find( '.ast-image__search-wrap' ).after( single_btn );
            $scope.find( '.ast-image__preview-skeleton' ).html( single_html );
            $scope.find( '.ast-image__preview-skeleton' ).show();
            $scope.find( '.single-site-preview' ).css( 'max-height', wrapHeight );

            AstraImageCommon.canSave = true;
        },

        _setScope: function() {

            AstraImageCommon.frame = wp.media.view.AstraAttachmentsBrowser.object.$el.closest( '.media-frame' );
            $scope = AstraImageCommon.frame.find( '.ast-attachments-browser' );

            if ( undefined == $scope ) {
                return;
            }
            
            $( 'body' ).data( 'page', 1 );
            let skeleton = $( '#tmpl-ast-image-skeleton' ).text();
            $scope.append( skeleton );

            let pixabay_filter = wp.template( 'ast-image-filters' );
            $scope.find( '.ast-attachments-search-wrap' ).append( pixabay_filter() );

            AstraImageCommon.offset = AstraImageCommon.frame.outerHeight();
            let wrapHeight = ( AstraImageCommon.offset - 210 );
            $scope.find( '.ast-image__skeleton-inner-wrap' ).css( 'height', wrapHeight );
            $scope.find( '.ast-image__search' ).trigger( 'keyup' );
            if ( AstraImageCommon.apiStatus ) {
                $scope.find( '.ast-image__loader-wrap' ).show();
            }
            $scope.find( '.ast-image__skeleton-inner-wrap' ).scroll( AstraImageCommon._loadMore );
        },

        _initImages: function() {  

            let loop = wp.template( 'ast-image-list' );
            let list_html = loop( wp.media.view.AstraAttachmentsBrowser.images );

            let masonryObj;
            let container = document.querySelector( '.ast-image__skeleton' );
            $scope.find( '.ast-image__loader-wrap' ).show();
            
            if ( AstraImageCommon.infiniteLoad ) {
                AstraImageCommon.images.push( wp.media.view.AstraAttachmentsBrowser.images );
                $scope.find( '.ast-image__skeleton' ).append( list_html );
            } else {
                AstraImageCommon.images = wp.media.view.AstraAttachmentsBrowser.images;
                $scope.find( '.ast-image__skeleton' ).html( list_html );
            }
            AstraImageCommon.loadingStatus = true;
            if ( $scope.find( '.ast-image__list-wrap' ).length ) {
                imagesLoaded( container, function() {
                    $scope.find( '.ast-image__list-wrap' ).each( function( index ) {
                        $( this ).removeClass( 'loading' );
                        $( this ).addClass( 'loaded' );
                    } );
                    $scope.find( '.ast-image__loader-wrap' ).hide();
                    masonryObj = new Masonry( container, {
                        itemSelector: '.ast-image__list-wrap'
                    });
                });
            } else {
                $scope.find( '.ast-image__loader-wrap' ).hide();
            }
        },

        _loadMore: function() {

            if( AstraImageCommon.isPreview ) {
                return;
            }

            if ( !AstraImageCommon.apiStatus ) {
                return;
            }

            let page = $( 'body' ).data( 'page' );
            page = ( undefined == page ) ? 2 : ( page + 1 );

            if ( undefined != $scope.find( '.ast-image__list-wrap:last' ).offset() ) {

                if( ( $( window ).scrollTop() + AstraImageCommon.offset ) >= ( $scope.find( '.ast-image__list-wrap:last' ).offset().top ) ) {

                    if ( AstraImageCommon.loadingStatus ) {

                        $scope.find( '.ast-image__loader-wrap' ).show();

                        AstraImageCommon.loadingStatus = false;
                        AstraImageCommon.infiniteLoad = true;
                        AstraImageCommon.config.page = page;
                         
                        $( 'body' ).data( 'page', page );

                        $scope.find( '.ast-image__search' ).trigger( 'infinite' );
                    }
                }
            }
        },
    };

    /**
     * Initialize AstraImageCommon
     */
    $(function(){
        AstraImageCommon.init();
    });

})(jQuery);