/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/*!************************************!*\
  !*** ./inc/assets/js/src/index.js ***!
  \************************************/
/*! dynamic exports provided */
/*! all exports used */
/***/ (function(module, exports, __webpack_require__) {

(function ($) {

    var AstraImages = {

        init: function init() {

            if (undefined != wp && wp.media) {

                var $ = jQuery,
                    oldMediaFrameSelect = wp.media.view.MediaFrame.Select;

                wp.media.view.AstraAttachmentsBrowser = __webpack_require__(/*! ./frame.js */ 1);

                wp.media.view.MediaFrame.Select = oldMediaFrameSelect.extend({

                    // Tab / Router
                    browseRouter: function browseRouter(routerView) {
                        oldMediaFrameSelect.prototype.browseRouter.apply(this, arguments);
                        routerView.set({
                            astraimages: {
                                text: astraImages.title,
                                priority: 70
                            }
                        });
                    },


                    // Handlers
                    bindHandlers: function bindHandlers() {
                        oldMediaFrameSelect.prototype.bindHandlers.apply(this, arguments);
                        this.on('content:create:astraimages', this.astraimages, this);
                    },


                    /**
                     * Render callback for the content region in the `browse` mode.
                     *
                     * @param {wp.media.controller.Region} contentRegion
                     */
                    astraimages: function astraimages(contentRegion) {
                        var state = this.state();
                        // Browse our library of attachments.
                        var thisView = new wp.media.view.AstraAttachmentsBrowser({
                            controller: this,
                            model: state,
                            AttachmentView: state.get('AttachmentView')
                        });
                        contentRegion.view = thisView;
                        wp.media.view.AstraAttachmentsBrowser.object = thisView;
                        setTimeout(function () {
                            $(document).trigger('ast-image__set-scope');
                        }, 100);
                    }
                });
            }
        }

    };

    /**
     * Initialize AstraImages
     */
    $(function () {

        AstraImages.init();

        if (astraImages.is_bb_active && astraImages.is_bb_editor) {
            if (undefined !== FLBuilder) {
                if (null !== FLBuilder._singlePhotoSelector) {
                    FLBuilder._singlePhotoSelector.on('open', function (event) {
                        AstraImages.init();
                    });
                }
            }
        }
    });
})(jQuery);

/***/ }),
/* 1 */
/*!************************************!*\
  !*** ./inc/assets/js/src/frame.js ***!
  \************************************/
/*! dynamic exports provided */
/*! all exports used */
/***/ (function(module, exports, __webpack_require__) {

var Frame = wp.media.view.Frame,
    AstraAttachmentsBrowser;

wp.media.view.AstraContent = __webpack_require__(/*! ./content.js */ 2);

AstraAttachmentsBrowser = Frame.extend({
    tagName: 'div',
    className: 'attachments-browser ast-attachments-browser',
    images: [],
    object: [],
    initialize: function initialize() {
        _.defaults(this.options, {
            filters: false,
            search: true,
            date: true,
            display: false,
            sidebar: true,
            AttachmentView: wp.media.view.Attachment.Library
        });

        // Add a heading before the attachments list.
        this.createContent();
    },

    createContent: function createContent() {

        this.attachmentsHeading = new wp.media.view.Heading({
            text: astraImages.title,
            level: 'h3',
            className: 'ast-media-views-heading'
        });
        // this.views.add( this.attachmentsHeading );
        this.views.add(new wp.media.view.AstraContent());
        this.$el.find('.ast-image__search').wrapAll('<div class="ast-image__search-wrap">').parent().html();
        this.$el.find('.ast-image__search-wrap').append('<span class="icon-search search-icon"></span>');
    },

    photoUploadComplete: function photoUploadComplete(savedImage) {
        if (savedImage && savedImage.attachmentData) {
            this.model.frame.content.mode("browse");
            this.model.get("selection").add(savedImage.attachmentData);
            this.model.frame.trigger("library:selection:add");
            this.model.get("selection");
            jQuery(".media-frame .media-button-select").click();
        }
    }
});

module.exports = AstraAttachmentsBrowser;

/***/ }),
/* 2 */
/*!**************************************!*\
  !*** ./inc/assets/js/src/content.js ***!
  \**************************************/
/*! dynamic exports provided */
/*! all exports used */
/***/ (function(module, exports, __webpack_require__) {

wp.media.view.AstraSearch = __webpack_require__(/*! ./search.js */ 3);

var AstraContent = wp.media.View.extend({

    tagName: 'div',
    className: 'ast-attachments-search-wrap',
    initialize: function initialize() {
        this.value = this.options.value;
    },

    render: function render() {

        var search = new wp.media.view.AstraSearch({
            controller: this.controller,
            model: this.model
        });
        this.views.add(search);
        return this;
    }
});

module.exports = AstraContent;

/***/ }),
/* 3 */
/*!*************************************!*\
  !*** ./inc/assets/js/src/search.js ***!
  \*************************************/
/*! dynamic exports provided */
/*! all exports used */
/***/ (function(module, exports) {

$ = jQuery;
// Search input view controller.
var AstraSearch = wp.Backbone.View.extend({

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
        'search': 'search',
        'keyup': 'search',
        'blur': 'pushState',
        'infinite': 'infinite'
    },

    initialize: function initialize(options) {

        this.parent = options.parent;
    },

    infinite: function infinite(event) {

        // Since doSearch is debounced, it will only run when user input comes to a rest.
        this.doSearch(event);
    },

    search: function search(event) {

        // Clear on escape.
        if (event.type === 'keyup' && event.which === 27) {
            event.target.value = '';
        }
        if ('' == event.target.value) {
            this.$el.removeClass('has-input');
        } else {
            this.$el.addClass('has-input');
        }

        $scope.find('.ast-image__skeleton').animate({ scrollTop: 0 }, 0);
        $('body').data('page', 1);
        AstraImageCommon.infiniteLoad = false;

        var thisObject = this;
        setTimeout(function () {
            thisObject.doSearch(event);
        }, 1000);
    },

    // Runs a search on the theme collection.
    doSearch: function doSearch(event) {

        if (this.searching) {
            return;
        }

        var thisObject = this;
        thisObject.searching = true;
        AstraImageCommon.config.q = event.target.value;
        var url = astraImages.pixabay_url + '?' + $.param(AstraImageCommon.config);

        if (url) {
            fetch(url).then(function (response) {
                return response.json();
            }).then(function (result) {
                thisObject.searching = false;
                this.images = result.hits;
                wp.media.view.AstraAttachmentsBrowser.images = this.images;
                $(document).trigger('ast-image__refresh');
            });
        }
    },

    pushState: function pushState(event) {
        $(document).trigger('ast-image__refresh');
    }
});

module.exports = AstraSearch;

/***/ })
/******/ ]);