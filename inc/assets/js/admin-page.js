/**
 * AJAX Request Queue
 *
 * - add()
 * - remove()
 * - run()
 * - stop()
 *
 * @since 1.0.0
 */
var AstraSitesAjaxQueue = (function () {

	var requests = [];

	return {

		/**
		 * Add AJAX request
		 *
		 * @since 1.0.0
		 */
		add: function (opt) {
			requests.push(opt);
		},

		/**
		 * Remove AJAX request
		 *
		 * @since 1.0.0
		 */
		remove: function (opt) {
			if (jQuery.inArray(opt, requests) > -1) {
				requests.splice($.inArray(opt, requests), 1);
			}
		},

		/**
		 * Run / Process AJAX request
		 *
		 * @since 1.0.0
		 */
		run: function () {
			var self = this,
				oriSuc;


			if (requests.length) {
				oriSuc = requests[0].complete;

				requests[0].complete = function () {
					if (typeof (oriSuc) === 'function') oriSuc();
					requests.shift();
					self.run.apply(self, []);
				};

				jQuery.ajax(requests[0]);
			} else {
				self.tid = setTimeout(function () {
					self.run.apply(self, []);
				}, 1000);
			}

		},

		/**
		 * Stop AJAX request
		 *
		 * @since 1.0.0
		 */
		stop: function () {
			requests = [];
			clearTimeout(this.tid);
		},

		/**
		 * Debugging.
		 *
		 * @param  {mixed} data Mixed data.
		 */
		_log: function (data, level) {
			var date = new Date();
			var time = date.toLocaleTimeString();

			var color = '#444';

			if (typeof data == 'object') {
				console.log(data);
			} else {
				console.log(data + ' ' + time);
			}
		},
	};

}());

(function ($) {

	/** Checking the element is in viewport? */
	$.fn.isInViewport = function () {

		// If not have the element then return false!
		if (!$(this).length) {
			return false;
		}

		var elementTop = $(this).offset().top;
		var elementBottom = elementTop + $(this).outerHeight();

		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();

		return elementBottom > viewportTop && elementTop < viewportBottom;
	};

	var AstraSSEImport = {
		complete: {
			posts: 0,
			media: 0,
			users: 0,
			comments: 0,
			terms: 0,
		},

		updateDelta: function (type, delta) {
			this.complete[type] += delta;

			var self = this;
			requestAnimationFrame(function () {
				self.render();
			});
		},
		updateProgress: function (type, complete, total) {
			var text = complete + '/' + total;

			if ('undefined' !== type && 'undefined' !== text) {
				total = parseInt(total, 10);
				if (0 === total || isNaN(total)) {
					total = 1;
				}

				var percent = parseInt(complete, 10) / total;
				var progress = Math.round(percent * 100) + '%';
				var progress_bar = percent * 100;

				if (progress_bar <= 100) {
					var process_bars = document.getElementsByClassName('astra-site-import-process');
					for (var i = 0; i < process_bars.length; i++) {
						process_bars[i].value = progress_bar;
					}
					AstraSitesAdmin._log_title('Importing Content.. ' + progress, false, false);
				}
			}
		},
		render: function () {
			var types = Object.keys(this.complete);
			var complete = 0;
			var total = 0;

			for (var i = types.length - 1; i >= 0; i--) {
				var type = types[i];
				this.updateProgress(type, this.complete[type], this.data.count[type]);

				complete += this.complete[type];
				total += this.data.count[type];
			}

			this.updateProgress('total', complete, total);
		}
	};

	AstraSitesAdmin = {

		import_source: 'legacy',
		wpcontent_left_margin: $('#wpcontent').css('margin-left'),
		header: $('#astra-sites-menu-page .nav-tab-wrapper'),
		header_offset: 0,
		header_gutter: null,
		header_stick_after: null,

		subscribe_status: false,
		subscribe_skiped: false,
		site_import_status: false,
		page_import_status: false,
		imported_page_data: null,
		first_import_complete: astraSitesVars.first_import_complete,
		remaining_activate_plugins: [],
		required_plugins_original_list: [],
		subscription_form_submitted: astraSitesVars.subscribed,

		compatibilities: [],

		skip_and_import_popups: [],
		required_plugins: [],

		_ref: null,

		/**
		 * _api_params = {
		 * 		'search'                  : '',
		 * 		'per_page'                : '',
		 * 		'astra-site-category'     : '',
		 * 		'astra-site-page-builder' : '',
		 * 		'page'                    : '',
		 *   };
		 *
		 * E.g. per_page=<page-id>&astra-site-category=<category-ids>&astra-site-page-builder=<page-builder-ids>&page=<page>
		 */
		_api_params: {},
		_breakpoint: 768,
		_has_default_page_builder: false,
		_first_time_loaded: true,

		visited_sites_and_pages: [],

		reset_remaining_posts: 0,
		reset_remaining_wp_forms: 0,
		reset_remaining_terms: 0,
		reset_processed_posts: 0,
		reset_processed_wp_forms: 0,
		reset_processed_terms: 0,
		site_imported_data: null,

		backup_taken: false,
		filter_array: [],
		autocompleteTags: [],
		templateData: {},
		mouseLocation: false,
		log_file: '',
		customizer_data: '',
		wxr_url: '',
		wpforms_url: '',
		cartflows_url: '',
		options_data: '',
		widgets_data: '',
		enabled_extensions: '',
		action_slug: '',
		import_start_time: '',
		import_end_time: '',
		search_terms: [],
		page_settings_flag: true,
		delay_in_request: false,
		delay_value : 10000, // 10 seconds.

		init: function () {
			this._show_default_page_builder_sites();
			this._bind();
			this._addAutocomplete();
			this._autocomplete();
			this._load_large_images();
			this._prepare_markup();
		},

		_prepare_markup: function () {
			var WPAdminbarOuterHeight = parseFloat($('#wpadminbar').outerHeight());
			var HeaderOuterHeight = parseFloat(AstraSitesAdmin.header.outerHeight());
			AstraSitesAdmin.header
				.wrap('<div></div>')
				.parent().css('min-height', HeaderOuterHeight);

			$('.single-site-footer').css('margin-left', AstraSitesAdmin.wpcontent_left_margin);
			$('.single-site-pages-wrap').css('margin-right', AstraSitesAdmin.wpcontent_left_margin);

			AstraSitesAdmin.header_gutter = WPAdminbarOuterHeight;
			AstraSitesAdmin.header_offset = WPAdminbarOuterHeight + HeaderOuterHeight;
			AstraSitesAdmin.header_stick_after = WPAdminbarOuterHeight - HeaderOuterHeight;
			AstraSitesAdmin._stick_header();
		},

		/**
		 * load large image
		 *
		 * @return {[type]} [description]
		 */
		_load_large_image: function (el) {
			if (el.hasClass('loaded')) {
				return;
			}

			if (el.parents('.astra-theme').isInViewport()) {
				var large_img_url = el.data('src') || '';
				var imgLarge = new Image();
				imgLarge.src = large_img_url;
				imgLarge.onload = function () {
					el.removeClass('loading');
					el.addClass('loaded');
					el.css('background-image', 'url(\'' + imgLarge.src + '\'');
				};
			}
		},

		_load_large_images: function () {
			$('.theme-screenshot').each(function (key, el) {
				AstraSitesAdmin._load_large_image($(el));
			});
		},

		_addAutocomplete: function () {

			var tags = astraSitesVars.api_sites_and_pages_tags || [];
			var sites = astraSitesVars.default_page_builder_sites || [];
			var strings = [];

			for (tag_index in tags) {
				strings.push(AstraSitesAdmin._unescape_lower(tags[tag_index]['name']));
			}

			// Add site title's in autocomplete.
			for (site_id in sites) {

				if (astraSitesVars.default_page_builder === sites[site_id]['astra-site-page-builder']) {
					var title = AstraSitesAdmin._unescape(sites[site_id]['title']);

					// @todo check why below character not escape with function _.unescape();
					title = title.toLowerCase().replace('&#8211;', '-');

					strings.push(title);
				}
			}

			AstraSitesAdmin.autocompleteTags = strings;
		},

		_autocomplete: function () {

			var strings = AstraSitesAdmin.autocompleteTags;
			strings = _.uniq(strings);
			strings = _.sortBy(strings);

			$("#wp-filter-search-input").autocomplete({
				appendTo: ".astra-sites-autocomplete-result",
				classes: {
					"ui-autocomplete": "astra-sites-auto-suggest"
				},
				source: function (request, response) {
					var results = $.ui.autocomplete.filter(strings, request.term);

					// Show only 10 results.
					response(results.slice(0, 15));
				},
				open: function (event, ui) {
					$('.search-form').addClass('searching');
				},
				close: function (event, ui) {
					$('.search-form').removeClass('searching');
				}
			});

			$("#wp-filter-search-input").focus();
		},

		/**
		 * Debugging.
		 *
		 * @param  {mixed} data Mixed data.
		 */
		_log: function (data, level) {
			var date = new Date();
			var time = date.toLocaleTimeString();

			var color = '#444';

			switch (level) {
				case 'emergency': 	// color = '#f44336';
				case 'critical': 	// color = '#f44336';
				case 'alert': 		// color = '#f44336';
				case 'error': 		// color = '#f44336';
					if (typeof data == 'object') {
						console.error(data);
					} else {
						console.error(data + ' ' + time);
					}
					break;
				case 'warning': 	// color = '#ffc107';
				case 'notice': 		// color = '#ffc107';
					if (typeof data == 'object') {
						console.warn(data);
					} else {
						console.warn(data + ' ' + time);
					}
					break;
				default:
					if (typeof data == 'object') {
						console.log(data);
					} else {
						console.log(data + ' ' + time);
					}
					break;
				// case 'info': color = '#03a9f4';
				// break;
				// case 'debug': color = '#ffc107';
			}
		},

		_log_title: function (data, append) {

			var markup = '<p>' + data + '</p>';
			if (typeof data == 'object') {
				var markup = '<p>' + JSON.stringify(data) + '</p>';
			}

			var selector = $('.ast-importing-wrap');
			if ($('.current-importing-status-title').length) {
				selector = $('.current-importing-status-title');
			}

			if (append) {
				selector.append(markup);
			} else {
				selector.html(markup);
			}

		},

		/**
		 * Binds events for the Astra Sites.
		 *
		 * @since 1.0.0
		 * @access private
		 * @method _bind
		 */
		_bind: function () {
			$(window).on('resize scroll', AstraSitesAdmin._load_large_images);

			$('.astra-sites__category-filter-anchor, .astra-sites__category-filter-items').hover(function () {
				AstraSitesAdmin.mouseLocation = true;
			}, function () {
				AstraSitesAdmin.mouseLocation = false;
			});

			$("body").mouseup(function () {
				if (!AstraSitesAdmin.mouseLocation) AstraSitesAdmin._closeFilter();
			});

			// Change page builder.
			$(document).on('click', '.nav-tab-wrapper .page-builders li', AstraSitesAdmin._ajax_change_page_builder);
			$(document).on('click', '#astra-sites-welcome-form .page-builders li', AstraSitesAdmin._change_page_builder);

			// Open & Close Popup.
			$(document).on('click', '.site-import-cancel, .astra-sites-result-preview .close, .astra-sites-popup .close', AstraSitesAdmin._close_popup);
			$(document).on('click', '.astra-sites-popup .overlay, .astra-sites-result-preview .overlay', AstraSitesAdmin._close_popup_by_overlay);

			$(document).on('click', '.ast-sites__filter-wrap-checkbox, .ast-sites__filter-wrap', AstraSitesAdmin._filterClick);

			// Page.
			$(document).on('click', '.site-import-layout-button', AstraSitesAdmin.show_page_popup_from_sites);
			$(document).on('click', '#astra-sites .astra-sites-previewing-page .theme-screenshot, #astra-sites .astra-sites-previewing-page .theme-name', AstraSitesAdmin.show_page_popup_from_search);
			$(document).on('click', '.astra-sites-page-import-popup .site-install-site-button, .preview-page-from-search-result .site-install-site-button', AstraSitesAdmin.import_page_process);
			$(document).on('astra-sites-after-site-pages-required-plugins', AstraSitesAdmin._page_api_call);

			// Site reset warning.
			$(document).on('click', '.astra-sites-reset-data .checkbox', AstraSitesAdmin._toggle_reset_notice);

			// Theme Activation warning.
			$(document).on('click', '.astra-sites-theme-activation .checkbox', AstraSitesAdmin._toggle_theme_notice);

			$(document).on('wp-theme-install-success', AstraSitesAdmin._activateTheme);

			// Site.
			$(document).on('click', '.site-import-site-button', AstraSitesAdmin._show_site_popup);
			$(document).on('click', '.astra-sites-get-agency-bundle-button', AstraSitesAdmin._show_get_agency_bundle_notice);
			$(document).on('click', '.astra-sites-activate-license-button', AstraSitesAdmin._show_activate_license_notice);
			$(document).on('click', '.astra-sites-invalid-mini-agency-license-button', AstraSitesAdmin._show_invalid_mini_agency_license);
			$(document).on('click', '.astra-sites-site-import-popup .site-install-site-button', AstraSitesAdmin._resetData);

			// Skip.
			$(document).on('click', '.astra-sites-skip-and-import-step', AstraSitesAdmin._remove_skip_and_import_popup);

			// Skip & Import.
			$(document).on('astra-sites-after-astra-sites-required-plugins', AstraSitesAdmin._start_site_import);

			$(document).on('astra-sites-reset-data', AstraSitesAdmin._backup_before_rest_options);
			$(document).on('astra-sites-backup-settings-before-reset-done', AstraSitesAdmin._reset_customizer_data);
			$(document).on('astra-sites-reset-customizer-data-done', AstraSitesAdmin._reset_site_options);
			$(document).on('astra-sites-reset-site-options-done', AstraSitesAdmin._reset_widgets_data);
			$(document).on('astra-sites-reset-widgets-data-done', AstraSitesAdmin._reset_terms);
			$(document).on('astra-sites-delete-terms-done', AstraSitesAdmin._reset_wp_forms);
			$(document).on('astra-sites-delete-wp-forms-done', AstraSitesAdmin._reset_posts);

			$(document).on('astra-sites-reset-data-done', AstraSitesAdmin._recheck_backup_options);
			$(document).on('astra-sites-backup-settings-done', AstraSitesAdmin._startImportCartFlows);
			$(document).on('astra-sites-import-cartflows-done', AstraSitesAdmin._startImportWPForms);
			$(document).on('astra-sites-import-wpforms-done', AstraSitesAdmin._importCustomizerSettings);
			$(document).on('astra-sites-import-customizer-settings-done', AstraSitesAdmin._importXML);
			$(document).on('astra-sites-import-xml-done', AstraSitesAdmin.import_siteOptions);
			$(document).on('astra-sites-import-options-done', AstraSitesAdmin._importWidgets);
			$(document).on('astra-sites-import-widgets-done', AstraSitesAdmin._importEnd);

			// Try again.
			$(document).on('click', '.ast-try-again', AstraSitesAdmin.tryAgain );

			$(document).on('click', '.astra-sites__category-filter-anchor', AstraSitesAdmin._toggleFilter);

			// Tooltip.
			$(document).on('click', '.astra-sites-tooltip-icon', AstraSitesAdmin._toggle_tooltip);

			// Plugin install & activate.
			$(document).on('wp-plugin-installing', AstraSitesAdmin._pluginInstalling);
			$(document).on('wp-plugin-install-error', AstraSitesAdmin._installError);
			$(document).on('wp-plugin-install-success', AstraSitesAdmin._installSuccess);

			$(document).on('click', '#astra-sites .astra-sites-previewing-site .theme-screenshot, #astra-sites .astra-sites-previewing-site .theme-name', AstraSitesAdmin._show_pages);
			$(document).on('click', '#single-pages .site-single', AstraSitesAdmin._change_site_preview_screenshot);
			$(document).on('click', '.astra-sites-show-favorite-button', AstraSitesAdmin._show_favorite);

			$(document).on('click', '.favorite-action-wrap', AstraSitesAdmin._toggle_favorite);
			$(document).on('click', '.astra-previewing-single-pages .back-to-layout', AstraSitesAdmin._go_back);
			$(document).on('click', '.astra-sites-showing-favorites .back-to-layout, .astra-sites-no-search-result .back-to-layout, .logo, .astra-sites-back', AstraSitesAdmin._show_sites);

			$(document).on('keydown', AstraSitesAdmin._next_and_previous_sites);

			$(document).on('click', '.astra-site-category a', AstraSitesAdmin._filterSites);

			$(document).on('click', '.astra-sites-sync-library-button', AstraSitesAdmin._sync_library);
			$(document).on('click', '.astra-sites-sync-library-message .notice-dismiss', AstraSitesAdmin._sync_library_complete);
			$(document).on('click', '.page-builder-icon', AstraSitesAdmin._toggle_page_builder_list);
			$(document).on('click', '.showing-page-builders #wpbody-content', AstraSitesAdmin._close_page_builder_list);
			$(document).on('keyup input', '#wp-filter-search-input', AstraSitesAdmin._search);
			$(document).on('keyup', '#wp-filter-search-input', _.debounce(AstraSitesAdmin._searchPost, 1500));
			$(document).on('heartbeat-send', AstraSitesAdmin._sendHeartbeat);
			$(document).on('heartbeat-tick', AstraSitesAdmin._heartbeatDone);
			$(document).on('click', '.ui-autocomplete .ui-menu-item', AstraSitesAdmin._show_search_term);

			$(document).on('click', '.button-subscription-submit', AstraSitesAdmin._subscribe);
			$(document).on('click', '.button-subscription-skip', AstraSitesAdmin._hide_subscription_popup);
			$(document).on('focusout change', '.subscription-input', AstraSitesAdmin.validate_single_field);
			$(document).on('click input', '.subscription-input', AstraSitesAdmin._animate_fields);
			$(document).on('click', '.astra-sites-advanced-options-heading', AstraSitesAdmin.toggle_advanced);

			$(window).on('scroll', AstraSitesAdmin._stick_header);
			$(document).on('wp-collapse-menu', AstraSitesAdmin._manage_wp_collapse_menu);
			$(document).on('astra-sites-added-pages', AstraSitesAdmin._stick_header);
			$(document).on('astra-sites-added-pages', AstraSitesAdmin._manage_wp_collapse_menu);

		},

		/**
		 * Try again for import
		 * @param {*} event
		 */
		tryAgain: function( event ) {
			event.preventDefault();
			AstraSitesAdmin.delay_in_request = true;
			$( '.site-import-site-button' ).trigger( 'click' );
		},

		/**
		 * Stick Header
		 */
		_stick_header: function () {
			if ($(window).outerWidth() > 768 && $(window).scrollTop() > AstraSitesAdmin.header_stick_after) {
				AstraSitesAdmin.header.addClass('stick').stop().css({
					'top': AstraSitesAdmin.header_gutter,
					'margin-left': AstraSitesAdmin.wpcontent_left_margin,
				});
			} else {
				AstraSitesAdmin.header.removeClass('stick').stop().css({
					'top': '',
				});
			}

		},

		/**
		 * Manage WP COllapse Menu
		 */
		_manage_wp_collapse_menu: function (event, state) {

			AstraSitesAdmin.wpcontent_left_margin = $('#wpcontent').css('margin-left');

			if (AstraSitesAdmin.header.hasClass('stick')) {
				AstraSitesAdmin.header.css('margin-left', AstraSitesAdmin.wpcontent_left_margin);
			}

			$('.single-site-footer').css('margin-left', AstraSitesAdmin.wpcontent_left_margin);
			$('.single-site-pages-wrap').css('margin-right', AstraSitesAdmin.wpcontent_left_margin);

		},

		toggle_advanced: function (event) {
			const elScope = $('.astra-sites-advanced-options-heading span')
			if (elScope.hasClass('dashicons-arrow-right-alt2')) {
				elScope.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
			} else {
				elScope.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
			}
			$('.astra-sites-advanced-options').toggle();
		},

		_animate_fields: function (event) {
			event.preventDefault();
			event.stopPropagation();
			var parentWrapper = $(this).parents('.subscription-field-wrap');
			parentWrapper.addClass('subscription-anim');
		},

		validate_single_field: function (event) {
			event.preventDefault();
			event.stopPropagation();
			AstraSitesAdmin._validate_field(event.target);
		},

		_validate_field: function (target) {

			var field = $(target);
			var fieldValue = field.val() || '';
			var parentWrapper = $(target).parents('.subscription-field-wrap');
			var fieldStatus = fieldValue.length ? true : false;

			if ((field.hasClass('subscription-input-email') && false === AstraSitesAdmin.isValidEmail(fieldValue))) {
				fieldStatus = false;
			}

			if (fieldStatus) {
				parentWrapper
					.removeClass('subscription-error')
					.addClass('subscription-success');

			} else {
				parentWrapper
					.removeClass('subscription-success subscription-anim')
					.addClass('subscription-error');

				if (field.hasClass('subscription-input-email') && fieldValue.length) {
					parentWrapper
						.addClass('subscription-anim')
				}
			}

		},

		_hide_subscription_popup: function (event) {
			$('.subscription-popup').hide();
			$('.astra-sites-result-preview .default').show();

			AstraSitesAdmin.subscribe_status = true;
			AstraSitesAdmin.subscribe_skiped = true;

			if ('astra-sites' === AstraSitesAdmin.action_slug) {
				$('.ast-importing-wrap').show();
				$('.astra-sites-result-preview').removeClass('astra-sites-subscription-popup');
				$('.ast-actioms-wrap .button').hide();
				$('.ast-actioms-wrap .site-install-site-button').show();
				$('.ast-actioms-wrap .site-import-cancel').show();

				$('.astra-sites-result-preview .heading h3').html(astraSitesVars.headings.site_import);

				if (true === AstraSitesAdmin.site_import_status) {
					AstraSitesAdmin.import_complete();
				}
			} else {
				$('.astra-sites-result-preview .heading h3').html(astraSitesVars.headings.page_import);

				if (true === AstraSitesAdmin.page_import_status) {
					AstraSitesAdmin.page_import_complete();
				}
			}
			if (event && event.target.classList.value == 'button-subscription-skip') {
				astraSitesVars.subscribed = '';
				AstraSitesAdmin.subscription_form_submitted = '';
			} else {
				astraSitesVars.subscribed = 'yes';
				AstraSitesAdmin.subscription_form_submitted = 'yes';
			}
		},

		_subscribe: function (event) {
			event.preventDefault();

			var submit_button = $(this);

			if (submit_button.hasClass('submitting')) {
				return;
			}

			var first_name_field = $('.subscription-input-name[name="first_name"]');
			var email_field = $('.subscription-input-email[name="email"]');
			var user_type_field = $('.subscription-input-wp-user-type[name="wp_user_type"]');
			var build_for_field = $('.subscription-input-build-website-for[name="build_website_for"]');

			var subscription_first_name = first_name_field.val() || '';
			var subscription_email = email_field.val() || '';
			var subscription_user_type = user_type_field.val() || '';
			var subscription_build_for = build_for_field.val() || '';

			AstraSitesAdmin._validate_field(first_name_field);
			AstraSitesAdmin._validate_field(email_field);
			if ($('#astra-sites-subscription-form-two').length) {
				AstraSitesAdmin._validate_field(user_type_field);
				AstraSitesAdmin._validate_field(build_for_field);
			}

			if ($('.subscription-field-wrap').hasClass('subscription-error')) {
				return;
			}

			submit_button.addClass('submitting');

			var subscription_fields = {
				EMAIL: subscription_email,
				FIRSTNAME: subscription_first_name,
				PAGE_BUILDER: astraSitesVars.default_page_builder_data.name,
				WP_USER_TYPE: subscription_user_type,
				BUILD_WEBSITE_FOR: subscription_build_for,
			};

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-update-subscription',
					_ajax_nonce: astraSitesVars._ajax_nonce,
					data: JSON.stringify(subscription_fields),
				},
				beforeSend: function () {
					console.groupCollapsed('Email Subscription');
				},
			})
				.done(function (response) {
					AstraSitesAdmin._log(response);

					submit_button
						.text(astraSitesVars.subscriptionSuccessMessage)
						.removeClass('submitting')
						.addClass('submitted')
						.find('.dashicons')
						.removeClass('dashicons-update')
						.addClass('dashicons-yes')
					setTimeout(function () {
						AstraSitesAdmin._hide_subscription_popup();
					}, 5000);
					console.groupEnd();
				});

		},

		isValidEmail: function (eMail) {
			if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(eMail)) {
				return true;
			}

			return false;
		},

		_heartbeatDone: function (e, data) {
			// Check for our data, and use it.
			if (!data['ast-sites-search-terms']) {
				return;
			}
			AstraSitesAdmin.search_terms = [];
		},

		_sendHeartbeat: function (e, data) {
			// Add additional data to Heartbeat data.
			if (AstraSitesAdmin.search_terms.length > 0) {
				data['ast-sites-search-terms'] = AstraSitesAdmin.search_terms;
			}
		},

		_searchPost: function (e) {
			var term = $(this).val().toLowerCase();
			// Skip blank words and words smaller than 3 characters.
			if ('' === term || term.length < 3 ) {
				return;
			}

			if (!AstraSitesAdmin.search_terms.includes(term)) {
				AstraSitesAdmin.search_terms.push(term);
			}
		},

		_toggleFilter: function (e) {

			var items = $('.astra-sites__category-filter-items');

			if (items.hasClass('visible')) {
				items.removeClass('visible');
				items.hide();
			} else {
				items.addClass('visible');
				items.show();
			}
		},

		_closeFilter: function (e) {

			var items = $('.astra-sites__category-filter-items');
			items.removeClass('visible');
			items.hide();
		},

		_filterClick: function (e) {

			AstraSitesAdmin.filter_array = [];

			if ($(this).hasClass('ast-sites__filter-wrap')) {
				$('.astra-sites__category-filter-anchor').attr('data-slug', $(this).data('slug'));
				$('.astra-sites__category-filter-items').find('.ast-sites__filter-wrap').removeClass('category-active');
				$(this).addClass('category-active');
				$('.astra-sites__category-filter-anchor').text($(this).text());
				$('.astra-sites__category-filter-anchor').trigger('click');
				$('#wp-filter-search-input').val('');

				if ($('.astra-sites-show-favorite-button').hasClass('active')) {
					$('.astra-sites-show-favorite-button').removeClass('active');
					$('body').removeClass('astra-sites-showing-favorites');
					AstraSitesAdmin._clean_url_params('favorites');
				}
			}

			var $filter_name = $('.astra-sites__category-filter-anchor').attr('data-slug');

			if ('' != $filter_name) {
				AstraSitesAdmin.filter_array.push($filter_name);
			}

			if ($('.ast-sites__filter-wrap-checkbox input[name=ast-sites-radio]:checked').length) {
				$('.ast-sites__filter-wrap-checkbox input[name=ast-sites-radio]').removeClass('active');
				$('.ast-sites__filter-wrap-checkbox input[name=ast-sites-radio]:checked').addClass('active');
			}
			var $filter_type = $('.ast-sites__filter-wrap-checkbox input[name=ast-sites-radio]:checked').val();

			if ('' != $filter_type) {
				AstraSitesAdmin.filter_array.push($filter_type);
			}

			AstraSitesAdmin._closeFilter();
			$('#wp-filter-search-input').trigger('keyup');
		},

		_show_search_term: function () {
			var search_term = $(this).text() || '';
			$('#wp-filter-search-input').val(search_term);
			$('#wp-filter-search-input').trigger('keyup');
		},

		_search: function (event) {

			var search_input = $(this),
				search_term = $.trim(search_input.val()) || '';

			if (13 === event.keyCode) {
				$('.astra-sites-autocomplete-result .ui-autocomplete').hide();
				$('.search-form').removeClass('searching');
				$('#astra-sites-admin').removeClass('searching');
			}

			$('body').removeClass('astra-sites-no-search-result');

			var sites = $('#astra-sites .astra-theme'),
				titles = $('#astra-sites .astra-theme .theme-name'),
				searchTemplateFlag = false,
				items = [];

			AstraSitesAdmin.close_pages_popup();

			if (search_term.length) {
				search_input.addClass('has-input');
				$('#astra-sites-admin').addClass('searching');
				searchTemplateFlag = true;
			} else {
				search_input.removeClass('has-input');
				$('#astra-sites-admin').removeClass('searching');
			}

			items = AstraSitesAdmin._get_sites_and_pages_by_search_term(search_term);

			if (!AstraSitesAdmin.isEmpty(items)) {
				if (searchTemplateFlag) {
					AstraSitesAdmin.add_sites_after_search(items);
				} else {
					AstraSitesAdmin.add_sites(items);
				}
			} else {
				if (search_term.length) {
					$('body').addClass('astra-sites-no-search-result');
				}
				$('#astra-sites').html(wp.template('astra-sites-no-sites'));
			}
		},

		/**
		 * Change URL
		 */
		_changeAndSetURL: function (url_params) {
			var current_url = window.location.href;
			var current_url_separator = (window.location.href.indexOf("?") === -1) ? "?" : "&";
			var new_url = current_url + current_url_separator + decodeURIComponent($.param(url_params));
			AstraSitesAdmin._changeURL(new_url);
		},

		/**
		 * Clean the URL.
		 *
		 * @param  string url URL string.
		 * @return string     Change the current URL.
		 */
		_changeURL: function (url) {
			History.pushState(null, astraSitesVars.whiteLabelName, url);
		},

		/**
		 * Get URL param.
		 */
		_getParamFromURL: function (name, url) {
			if (!url) url = window.location.href;
			name = name.replace(/[\[\]]/g, "\\$&");
			var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
				results = regex.exec(url);
			if (!results) return null;
			if (!results[2]) return '';
			return decodeURIComponent(results[2].replace(/\+/g, " "));
		},

		_clean_url_params: function (single_param) {
			var url_params = AstraSitesAdmin._getQueryStrings();
			delete url_params[single_param];
			delete url_params[''];		// Removed extra empty object.

			var current_url = window.location.href;
			var root_url = current_url.substr(0, current_url.indexOf('?'));
			if ($.isEmptyObject(url_params)) {
				var new_url = root_url + decodeURIComponent($.param(url_params));
			} else {
				var current_url_separator = (root_url.indexOf("?") === -1) ? "?" : "&";
				var new_url = root_url + current_url_separator + decodeURIComponent($.param(url_params));
			}

			AstraSitesAdmin._changeURL(new_url);
		},

		/**
		 * Get query strings.
		 *
		 * @param  string string Query string.
		 * @return string     	 Check and return query string.
		 */
		_getQueryStrings: function (string) {
			return (string || document.location.search).replace(/(^\?)/, '').split("&").map(function (n) { return n = n.split("="), this[n[0]] = n[1], this }.bind({}))[0];
		},

		isEmpty: function (obj) {
			for (var key in obj) {
				if (obj.hasOwnProperty(key))
					return false;
			}
			return true;
		},

		_unescape: function (input_string) {
			var title = _.unescape(input_string);

			// @todo check why below character not escape with function _.unescape();
			title = title.replace('&#8211;', '-');
			title = title.replace('&#8217;', "'");

			return title;
		},

		_unescape_lower: function (input_string) {
			var input_string = AstraSitesAdmin._unescape(input_string);
			return input_string.toLowerCase();
		},

		_get_sites_and_pages_by_search_term: function (search_term) {

			var items = [],
				tags_strings = [];
			search_term = search_term.toLowerCase();

			if (search_term == '' && AstraSitesAdmin.filter_array.length == 0) {
				return astraSitesVars.default_page_builder_sites;
			}

			var $filter_type = $('.ast-sites__filter-wrap-checkbox input[name=ast-sites-radio]:checked').val();
			var $filter_name = $('.astra-sites__category-filter-anchor').attr('data-slug');

			for (site_id in astraSitesVars.default_page_builder_sites) {

				var current_site = astraSitesVars.default_page_builder_sites[site_id];
				var text_match = true;
				var free_match = true;
				var category_match = true;
				var match_id = '';

				if ('' != search_term) {
					text_match = false;
				}

				if ('' != $filter_name) {
					category_match = false;
				}

				if ('' != $filter_type) {
					free_match = false;
				}

				// Check in site title.
				if (current_site['title']) {
					var site_title = AstraSitesAdmin._unescape_lower(current_site['title']);

					if (site_title.toLowerCase().includes(search_term)) {
						text_match = true;
						match_id = site_id;
					}
				}

				// Check in site tags.
				if (Object.keys(current_site['astra-sites-tag']).length) {
					for (site_tag_id in current_site['astra-sites-tag']) {
						var tag_title = current_site['astra-sites-tag'][site_tag_id];
						tag_title = AstraSitesAdmin._unescape_lower(tag_title.replace('-', ' '));
						if (tag_title.toLowerCase().includes(search_term)) {
							text_match = true;
							match_id = site_id;
						}
					}
				}

				for (filter_id in AstraSitesAdmin.filter_array) {
					var slug = AstraSitesAdmin.filter_array[filter_id];
					if (slug == 'free' && 'free' == current_site['astra-sites-type']) {
						free_match = true;
						match_id = site_id;
					}
					if (slug == 'agency' && 'free' != current_site['astra-sites-type']) {
						free_match = true;
						match_id = site_id;
					}
					if (slug != 'free' && slug != 'agency' && undefined != slug) {
						for (cat_id in current_site['astra-site-category']) {
							if (slug.toLowerCase() == current_site['astra-site-category'][cat_id]) {
								category_match = true;
								match_id = site_id;
							}
						}
					}
				}

				if ('' != match_id) {
					if (text_match && category_match && free_match) {
						items[site_id] = current_site;
						items[site_id]['type'] = 'site';
						items[site_id]['site_id'] = site_id;
						items[site_id]['pages-count'] = (undefined != current_site['pages']) ? Object.keys(current_site['pages']).length : 0;
						tags_strings.push(AstraSitesAdmin._unescape_lower(current_site['title']));

						for (site_tag_id in current_site['astra-sites-tag']) {
							var tag_title = current_site['astra-sites-tag'][site_tag_id];
							tag_title = AstraSitesAdmin._unescape_lower(tag_title.replace('-', ' '));
							if (tag_title.toLowerCase().includes(search_term)) {
								tags_strings.push(AstraSitesAdmin._unescape_lower(tag_title));
							}
						}
					}
				}

				if (search_term != '') {

					// Check in page title.
					if (Object.keys(current_site['pages']).length) {

						var pages = current_site['pages'];

						for (page_id in pages) {

							var page_text_match = false;
							var page_free_match = true;
							var page_category_match = true;
							var page_match_id = '';

							if ('' != $filter_name) {
								page_category_match = false;
							}

							if ('' != $filter_type) {
								page_free_match = false;
							}
							// Check in site title.
							if (pages[page_id]['title']) {
								var page_title = AstraSitesAdmin._unescape_lower(pages[page_id]['title']);

								if (page_title.includes(search_term)) {
									page_text_match = true;
									page_match_id = page_id;
								}
							}

							// Check in site tags.
							if (Object.keys(pages[page_id]['astra-sites-tag']).length) {
								for (page_tag_id in pages[page_id]['astra-sites-tag']) {
									var tag_title = pages[page_id]['astra-sites-tag'][page_tag_id];
									tag_title = AstraSitesAdmin._unescape_lower(tag_title.replace('-', ' '));
									if (tag_title.toLowerCase().includes(search_term)) {
										page_text_match = true;
										page_match_id = page_id;
									}
								}
							}

							for (filter_id in AstraSitesAdmin.filter_array) {
								var pslug = AstraSitesAdmin.filter_array[filter_id];
								if (pslug == 'free' && 'free' == pages[page_id]['astra-sites-type']) {
									page_free_match = true;
									page_match_id = page_id;
								}
								if (pslug == 'agency' && 'free' != pages[page_id]['astra-sites-type']) {
									page_free_match = true;
									page_match_id = page_id;
								}
								if (pslug != 'free' && pslug != 'agency' && undefined != pslug) {
									for (cat_id in current_site['astra-site-category']) {
										if (pslug.toLowerCase() == current_site['astra-site-category'][cat_id]) {
											page_category_match = true;
											page_match_id = page_id;
										}
									}
								}
							}

							if ('' != page_match_id) {
								if (page_text_match && page_category_match && page_free_match) {
									items[page_id] = pages[page_id];
									items[page_id]['type'] = 'page';
									items[page_id]['site_id'] = site_id;
									items[page_id]['astra-sites-type'] = current_site['astra-sites-type'] || '';
									items[page_id]['site-title'] = current_site['title'] || '';
									items[page_id]['pages-count'] = 0;

									tags_strings.push(AstraSitesAdmin._unescape_lower(current_site['title']));

									for (site_tag_id in pages[page_id]['astra-sites-tag']) {
										var tag_title = pages[page_id]['astra-sites-tag'][site_tag_id];
										tag_title = AstraSitesAdmin._unescape_lower(tag_title.replace('-', ' '));
										if (tag_title.toLowerCase().includes(search_term)) {
											tags_strings.push(AstraSitesAdmin._unescape_lower(tag_title));
										}
									}
								}
							}
						}
					}
				}
			}

			if (search_term != '') {
				console.groupCollapsed('Search for "' + search_term + '"');
				AstraSitesAdmin._log(items);
				console.groupEnd();
			}

			if (tags_strings.length > 0) {
				AstraSitesAdmin.autocompleteTags = tags_strings;
				AstraSitesAdmin._autocomplete();
			}

			return items;
		},

		_close_page_builder_list: function (event) {
			event.preventDefault();
			$('body').removeClass('showing-page-builders');
			$('.page-builder-icon').removeClass('active');
		},

		_toggle_page_builder_list: function (event) {
			event.preventDefault();
			$(this).toggleClass('active');
			$('body').toggleClass('showing-page-builders');
		},

		_sync_library_complete: function () {
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-update-library-complete',
				},
			}).done(function (response) {
				AstraSitesAdmin._log(response);
				console.groupEnd('Update Library Request');
				$('.astra-sites-sync-library-message').remove();
			});
		},

		_sync_library_with_ajax: function (is_append) {

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-get-sites-request-count',
				},
				beforeSend: function () {
					console.groupCollapsed('Sync Library');
					AstraSitesAdmin._log('Sync Library..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR, 'error');
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Site Count Request Failed!', jqXHR);
					console.groupEnd('Sync Library');
				})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					if (response.success) {
						var total = response.data;

						AstraSitesAdmin._log(total);

						for (let i = 1; i <= total; i++) {

							AstraSitesAjaxQueue.add({
								url: astraSitesVars.ajaxurl,
								type: 'POST',
								data: {
									action: 'astra-sites-import-sites',
									page_no: i,
								},
								success: function (result) {
									AstraSitesAdmin._log(result);

									if (is_append) {
										if (!AstraSitesAdmin.isEmpty(result.data)) {


											var template = wp.template('astra-sites-page-builder-sites');

											// First fill the placeholders and then append remaining sites.
											if ($('.placeholder-site').length) {
												for (site_id in result.data) {
													if ($('.placeholder-site').length) {
														$('.placeholder-site').first().remove();
													}
												}
												if ($('#astra-sites .site-single:not(.placeholder-site)').length) {
													$('#astra-sites .site-single:not(.placeholder-site)').last().after(template(result.data));
												} else {
													$('#astra-sites').prepend(template(result.data));
												}
											} else {
												$('#astra-sites').append(template(result.data));
											}

											astraSitesVars.default_page_builder_sites = $.extend({}, astraSitesVars.default_page_builder_sites, result.data);

											AstraSitesAdmin._load_large_images();
											$(document).trigger('astra-sites-added-pages');
										}

									}

									if (i === total && astraSitesVars.strings.syncCompleteMessage) {
										console.groupEnd('Sync Library');
										$('#wpbody-content').find('.astra-sites-sync-library-message').remove();
										var noticeContent = wp.updates.adminNotice({
											className: 'notice astra-sites-notice notice-success is-dismissible astra-sites-sync-library-message',
											message: astraSitesVars.strings.syncCompleteMessage + ' <button type="button" class="notice-dismiss"><span class="screen-reader-text">' + astraSitesVars.dismiss + '</span></button>',
										});
										$('#astra-sites-admin').before(noticeContent);
										$(document).trigger('wp-updates-notice-added');

										$('.astra-sites-sync-library-button').removeClass('updating-message');
									}
								}
							});
						}

						// Run the AJAX queue.
						AstraSitesAjaxQueue.run();
					} else {
						AstraSitesAdmin._importFailMessage(response.data, 'Site Count Request Failed!');
					}
				});

			// Import categories.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-import-categories',
				},
				beforeSend: function () {
					console.groupCollapsed('Importing Categories');
					AstraSitesAdmin._log('Importing Categories..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Category Import Failed!', jqXHR);
					console.groupEnd('Importing Categories');
				}).done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd('Importing Categories');
				});

			// Import Site Categories.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-import-site-categories',
				},
				beforeSend: function () {
					console.groupCollapsed('Importing Site Categories');
					AstraSitesAdmin._log('Importing Site Categories..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Category Import Failed!', jqXHR);
					console.groupCollapsed('Importing Site Categories');
				}).done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupCollapsed('Importing Site Categories');
				});

			// Import page builders.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-import-page-builders',
				},
				beforeSend: function () {
					console.groupCollapsed('Importing Page Builders');
					AstraSitesAdmin._log('Importing Page Builders..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Page Builder Import Failed!', jqXHR);
					console.groupEnd('Importing Page Builders');
				}).done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd('Importing Page Builders');
				});

			// Import Blocks.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-get-blocks-request-count',
				},
				beforeSend: function () {
					console.groupCollapsed('Updating Blocks');
					AstraSitesAdmin._log('Updating Blocks');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR, 'error');
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Blocks Count Request Failed!', jqXHR);
					console.groupEnd('Updating Blocks');
				})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					if (response.success) {
						var total = response.data;

						AstraSitesAdmin._log(total);

						for (let i = 1; i <= total; i++) {
							AstraSitesAjaxQueue.add({
								url: astraSitesVars.ajaxurl,
								type: 'POST',
								data: {
									action: 'astra-sites-import-blocks',
									page_no: i,
								},
								beforeSend: function () {
									console.groupCollapsed('Importing Blocks - Page ' + i);
									AstraSitesAdmin._log('Importing Blocks - Page ' + i);
								},
								success: function (response) {
									AstraSitesAdmin._log(response);
									console.groupEnd('Importing Blocks - Page ' + i);
								}
							});
						}

						// Run the AJAX queue.
						AstraSitesAjaxQueue.run();
					} else {
						AstraSitesAdmin._importFailMessage(response.data, 'Blocks Count Request Failed!');
					}
				});

			// Import Block Categories.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-import-block-categories',
				},
				beforeSend: function () {
					console.groupCollapsed('Importing Block Categories');
					AstraSitesAdmin._log('Importing Block Categories..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Category Import Failed!', jqXHR);
					console.groupEnd('Importing Block Categories');
				}).done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd('Importing Block Categories');
				});

			AstraSitesAdmin._sync_library_complete();
		},

		_sync_library: function (event) {
			event.preventDefault();
			var button = $(this);

			if (button.hasClass('updating-message')) {
				return;
			}

			button.addClass('updating-message');

			$('.astra-sites-sync-library-message').remove();

			var noticeContent = wp.updates.adminNotice({
				className: 'astra-sites-sync-library-message astra-sites-notice notice notice-info',
				message: astraSitesVars.syncLibraryStart + '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + astraSitesVars.dismiss + '</span></button>',
			});
			$('#astra-sites-admin').before(noticeContent);

			$(document).trigger('wp-updates-notice-added');

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-update-library',
				},
				beforeSend: function () {
					console.groupCollapsed('Update Library Request');
					AstraSitesAdmin._log('Updating Library..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Sync Library Failed!', jqXHR);
					console.groupEnd('Update Library Request');
				})
				.done(function (response) {
					console.log(response);

					if (response.success) {
						if ('updated' === response.data) {

							$('#wpbody-content').find('.astra-sites-sync-library-message').remove();
							var noticeContent = wp.updates.adminNotice({
								className: 'notice astra-sites-notice notice-success is-dismissible astra-sites-sync-library-message',
								message: astraSitesVars.strings.syncCompleteMessage + ' <button type="button" class="notice-dismiss"><span class="screen-reader-text">' + astraSitesVars.dismiss + '</span></button>',
							});
							$('#astra-sites-admin').before(noticeContent);
							$(document).trigger('wp-updates-notice-added');
							button.removeClass('updating-message');
							AstraSitesAdmin._log('Already sync all the sites.');
							console.groupEnd('Update Library Request');
						} else {
							AstraSitesAdmin._sync_library_with_ajax();
						}
					} else {
						$('#wpbody-content').find('.astra-sites-sync-library-message').remove();
						var noticeContent = wp.updates.adminNotice({
							className: 'notice astra-sites-notice notice-error is-dismissible astra-sites-sync-library-message',
							message: response.data + ' <button type="button" class="notice-dismiss"><span class="screen-reader-text">' + astraSitesVars.dismiss + '</span></button>',
						});
						$('#astra-sites-admin').before(noticeContent);
						$(document).trigger('wp-updates-notice-added');
						button.removeClass('updating-message');
						AstraSitesAdmin._log('Already sync all the sites.');
						console.groupEnd('Update Library Request');
					}
				});
		},

		_filterSites: function (event) {
			event.preventDefault();
			var current_class = $(this).attr('data-group') || '';
			$(this).parents('.filter-links').find('a').removeClass('current');
			$(this).addClass('current');

			var search_term = $(this).text() || '';

			if (current_class) {
				if ($('#astra-sites .astra-theme.' + current_class).length) {
					$('#wp-filter-search-input').val(search_term);

					// $('#astra-sites .astra-theme').removeClass('astra-show-site astra-hide-site');
					$('#astra-sites .astra-theme').addClass('astra-hide-site');
					$('#astra-sites .astra-theme.' + current_class).removeClass('astra-hide-site').addClass('astra-show-site');
				}
			} else {
				$('#astra-sites .astra-theme').removeClass('astra-hide-site').addClass('astra-show-site');
			}

			$('.filters-wrap-page-categories').removeClass('show');
		},

		_next_and_previous_sites: function (e) {

			if (!$('body').hasClass('astra-previewing-single-pages')) {
				return;
			}

			if (e.key === "Escape") {
				AstraSitesAdmin.close_pages_popup();
				return;
			}

			switch (e.which) {

				// Left Key Pressed
				case 37:
					if ($('#astra-sites .astra-theme.current').prev().length) {
						$('#astra-sites .astra-theme.current').prev().addClass('current').siblings().removeClass('current');
						var site_id = $('#astra-sites .astra-theme.current').prev().attr('data-site-id') || '';
						if (site_id) {
							AstraSitesAdmin.show_pages_by_site_id(site_id);
						}
					}
					break;

				// Right Key Pressed
				case 39:
					if ($('#astra-sites .astra-theme.current').next().length) {
						$('#astra-sites .astra-theme.current').next().addClass('current').siblings().removeClass('current');
						var site_id = $('#astra-sites .astra-theme.current').next().attr('data-site-id') || '';
						if (site_id) {
							AstraSitesAdmin.show_pages_by_site_id(site_id);
						}
					}
					break;
			}

		},

		show_pages_by_site_id: function (site_id, page_id) {

			var sites = astraSitesVars.default_page_builder_sites || [];

			var data = sites[site_id];

			if ('undefined' !== typeof data) {
				var site_template = wp.template('astra-sites-single-site-preview');

				if (!AstraSitesAdmin._getParamFromURL('astra-site')) {
					var url_params = {
						'astra-site': site_id,
					};
					AstraSitesAdmin._changeAndSetURL(url_params);
				}

				$('#astra-sites').hide();
				$('#site-pages').show().html(site_template(data)).removeClass('brizy elementor beaver-builder gutenberg').addClass(astraSitesVars.default_page_builder);

				$('body').addClass('astra-previewing-single-pages');
				$('#site-pages').attr('data-site-id', site_id);

				if (AstraSitesAdmin._getParamFromURL('astra-page')) {
					AstraSitesAdmin._set_preview_screenshot_by_page($('#single-pages .site-single[data-page-id="' + AstraSitesAdmin._getParamFromURL('astra-page') + '"]'));
					// Has first item?
					// Then set default screnshot in preview.
				} else if (page_id && $('#single-pages .site-single[data-page-id="' + page_id + '"]').length) {
					AstraSitesAdmin._set_preview_screenshot_by_page($('#single-pages .site-single[data-page-id="' + page_id + '"]'));
				} else if ($('#single-pages .site-single').eq(0).length) {
					AstraSitesAdmin._set_preview_screenshot_by_page($('#single-pages .site-single').eq(0));
				}

				if (!$('#single-pages .site-single').eq(0).length) {
					$('.site-import-layout-button').hide();
				}

				$(document).trigger('astra-sites-added-pages');

				AstraSitesAdmin._load_large_images();
			}

		},

		_show_sites: function (event) {

			event.preventDefault();

			$('.astra-sites-show-favorite-button').removeClass('active');
			$('body').removeClass('astra-sites-showing-favorites');
			$('body').removeClass('astra-sites-no-search-result');
			$('.astra-sites__category-filter-items').find('.ast-sites__filter-wrap').removeClass('category-active');
			$('.ast-sites__filter-wrap').first().addClass('category-active');
			$('.astra-sites__category-filter-anchor').attr('data-slug', '');
			AstraSitesAdmin.filter_array = [];
			$('.ast-sites__filter-wrap-checkbox input:radio').attr('checked', false);
			$('.ast-sites__filter-wrap-checkbox input:radio').removeClass('active');
			$('#radio-all').trigger('click');
			$('#radio-all').addClass('active');
			$('.astra-sites__category-filter-anchor').text('All');
			AstraSitesAdmin._closeFilter();
			$('#wp-filter-search-input').val('');
			$('#astra-sites-admin').removeClass('searching');
			AstraSitesAdmin.add_sites(astraSitesVars.default_page_builder_sites);
			AstraSitesAdmin.close_pages_popup();

			AstraSitesAdmin._clean_url_params('favorites');

			AstraSitesAdmin._load_large_images();
		},

		/**
		 * Go back to all sites view
		 *
		 * @since 2.0.0
		 * @return null
		 */
		_go_back: function (event) {

			event.preventDefault();

			AstraSitesAdmin._clean_url_params('search');
			AstraSitesAdmin._clean_url_params('favorites');
			AstraSitesAdmin._clean_url_params('license');
			AstraSitesAdmin.close_pages_popup();
			AstraSitesAdmin._load_large_images();
		},

		close_pages_popup: function () {
			astraSitesVars.cpt_slug = 'astra-sites';

			$('#astra-sites').show();
			$('#site-pages').hide().html('');
			$('body').removeClass('astra-previewing-single-pages');
			$('.astra-sites-result-preview').hide();

			$('#astra-sites .astra-theme').removeClass('current');

			AstraSitesAdmin._clean_url_params('astra-site');
			AstraSitesAdmin._clean_url_params('astra-page');
			AstraSitesAdmin._clean_url_params('license');
		},


		_toggle_favorite: function (event) {

			let is_favorite = $(this).data('favorite');
			let parent = $(this).parents('.astra-theme');
			let site_id = parent.data('site-id').toString();
			let new_array = Array();

			parent.toggleClass('is-favorite');
			$(this).data('favorite', !is_favorite);

			if (!is_favorite) {
				// Add.
				for (value in astraSitesVars.favorite_data) {
					new_array.push(astraSitesVars.favorite_data[value]);
				}
				new_array.push(site_id);
			} else {
				// Remove.
				for (value in astraSitesVars.favorite_data) {
					if (site_id != astraSitesVars.favorite_data[value].toString()) {
						new_array.push(astraSitesVars.favorite_data[value]);
					}
				}
			}
			astraSitesVars.favorite_data = new_array;

			// If in favorites preview window and unfavorite the item?
			if ($('body').hasClass('astra-sites-showing-favorites') && !parent.hasClass('is-favorite')) {

				// Then remove the favorite item from markup.
				parent.remove();

				// Show Empty Favorite message if there is not item in favorite.
				if (!$('#astra-sites .astra-theme').length) {
					$('#astra-sites').html(wp.template('astra-sites-no-favorites'));
				}
			}

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'astra-sites-favorite',
					is_favorite: !is_favorite,
					site_id: site_id
				},
				beforeSend: function () {
					console.groupCollapsed('Toggle Favorite');
					AstraSitesAdmin._log(!is_favorite);
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Favorite/Unfavorite Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd();
				});
		},


		_show_favorite: function (event) {

			if (event) {
				event.preventDefault();
			}

			AstraSitesAdmin.close_pages_popup();

			$('.astra-sites__category-filter-items').find('.ast-sites__filter-wrap').removeClass('category-active');
			$('.ast-sites__filter-wrap').first().addClass('category-active');
			$('.astra-sites__category-filter-anchor').attr('data-slug', '');
			AstraSitesAdmin.filter_array = [];
			$('.ast-sites__filter-wrap-checkbox input:radio').attr('checked', false);
			$('.ast-sites__filter-wrap-checkbox input:radio').removeClass('active');
			$('#radio-all').trigger('click');
			$('#radio-all').addClass('active');
			$('.astra-sites__category-filter-anchor').text('All');
			$('#wp-filter-search-input').val('');

			if ($('.astra-sites-show-favorite-button').hasClass('active')) {
				$('.astra-sites-show-favorite-button').removeClass('active');
				$('body').removeClass('astra-sites-showing-favorites');
				AstraSitesAdmin.add_sites(astraSitesVars.default_page_builder_sites);
				AstraSitesAdmin._clean_url_params('favorites');
			} else {
				AstraSitesAdmin._clean_url_params('search');
				AstraSitesAdmin._clean_url_params('astra-site');
				AstraSitesAdmin._clean_url_params('astra-page');
				AstraSitesAdmin._clean_url_params('license');
				AstraSitesAdmin.close_pages_popup();

				if (!AstraSitesAdmin._getParamFromURL('favorites')) {
					var url_params = {
						'favorites': 'show'
					};
					AstraSitesAdmin._changeAndSetURL(url_params);
				}

				$('.astra-sites-show-favorite-button').addClass('active');
				$('body').addClass('astra-sites-showing-favorites');
				var items = [];
				for (favorite_id in astraSitesVars.favorite_data) {
					var exist_data = astraSitesVars.default_page_builder_sites[astraSitesVars.favorite_data[favorite_id].toString()] || {};
					if (!$.isEmptyObject(exist_data)) {
						items[astraSitesVars.favorite_data[favorite_id].toString()] = exist_data;
					}
				}

				if (!AstraSitesAdmin.isEmpty(items)) {
					AstraSitesAdmin.add_sites(items);
					$(document).trigger('astra-sites-added-sites');

				} else {
					$('#astra-sites').html(wp.template('astra-sites-no-favorites'));
				}
			}

		},

		_set_preview_screenshot_by_page: function (element) {
			var large_img_url = $(element).find('.theme-screenshot').attr('data-featured-src') || '';
			var url = $(element).find('.theme-screenshot').attr('data-src') || '';
			var page_name = $(element).find('.theme-name').text() || '';

			$(element).siblings().removeClass('current_page');
			$(element).addClass('current_page');

			var page_id = $(element).attr('data-page-id') || '';
			if (page_id) {

				AstraSitesAdmin._clean_url_params('astra-page');

				var url_params = {
					'astra-page': page_id,
				};
				AstraSitesAdmin._changeAndSetURL(url_params);
			}

			$('.site-import-layout-button').removeClass('disabled');
			if (page_name) {
				var title = astraSitesVars.strings.importSingleTemplate.replace('%s', page_name.trim());
				$('.site-import-layout-button').text(title);
			}

			if (url) {
				$('.single-site-preview').animate({
					scrollTop: 0
				}, 0);
				$('.single-site-preview img').addClass('loading').attr('src', url);
				var imgLarge = new Image();
				imgLarge.src = large_img_url;
				imgLarge.onload = function () {
					$('.single-site-preview img').removeClass('loading');
					$('.single-site-preview img').attr('src', imgLarge.src);
				};
			}
		},

		/**
		 * Preview Inner Pages for the Site
		 *
		 * @since 2.0.0
		 * @return null
		 */
		_change_site_preview_screenshot: function (event) {
			event.preventDefault();

			var item = $(this);

			AstraSitesAdmin._set_preview_screenshot_by_page(item);
		},

		_show_pages: function (event) {

			var perent = $(this).parents('.astra-theme');
			perent.siblings().removeClass('current');
			perent.addClass('current');

			var site_id = perent.attr('data-site-id') || '';
			AstraSitesAdmin.show_pages_by_site_id(site_id);
		},

		_apiAddParam_status: function () {
			if (astraSitesVars.sites && astraSitesVars.sites.status) {
				AstraSitesAdmin._api_params['status'] = astraSitesVars.sites.status;
			}
		},

		_apiAddParam_per_page: function () {
			// Add 'per_page'
			var per_page_val = 30;
			if (astraSitesVars.sites && astraSitesVars.sites["per-page"]) {
				per_page_val = parseInt(astraSitesVars.sites["per-page"]);
			}
			AstraSitesAdmin._api_params['per_page'] = per_page_val;
		},

		_apiAddParam_astra_site_category: function () {
			// Add 'astra-site-category'
			var selected_category_id = jQuery('.filter-links[data-category="' + astraSitesVars.category_slug + '"]').find('.current').data('group') || '';
			if ('' !== selected_category_id && 'all' !== selected_category_id) {
				AstraSitesAdmin._api_params[astraSitesVars.category_slug] = selected_category_id;
			} else if (astraSitesVars.sites && astraSitesVars['categories'].include) {
				if (AstraSitesAdmin._isArray(astraSitesVars['categories'].include)) {
					AstraSitesAdmin._api_params[astraSitesVars.category_slug] = astraSitesVars['categories'].include.join(',');
				} else {
					AstraSitesAdmin._api_params[astraSitesVars.category_slug] = astraSitesVars['categories'].include;
				}
			}
		},

		_apiAddParam_astra_page_parent_category: function () {

			// Add 'site-pages-parent-category'
			if ('' == astraSitesVars.parent_category) {
				return;
			}

			var selected_category_id = jQuery('.filter-links[data-category="' + astraSitesVars.parent_category + '"]').find('.current').data('group') || '';
			if ('' !== selected_category_id && 'all' !== selected_category_id) {
				AstraSitesAdmin._api_params[astraSitesVars.parent_category] = selected_category_id;
			} else if (astraSitesVars.sites && astraSitesVars['categories'].include) {
				if (AstraSitesAdmin._isArray(astraSitesVars['categories'].include)) {
					AstraSitesAdmin._api_params[astraSitesVars.parent_category] = astraSitesVars['categories'].include.join(',');
				} else {
					AstraSitesAdmin._api_params[astraSitesVars.parent_category] = astraSitesVars['categories'].include;
				}
			}
		},

		_apiAddParam_astra_site_page_builder: function () {
			// Add 'astra-site-page-builder'
			var selected_page_builder_id = jQuery('.filter-links[data-category="' + astraSitesVars.page_builder + '"]').find('.current').data('group') || '';
			if ('' !== selected_page_builder_id && 'all' !== selected_page_builder_id) {
				AstraSitesAdmin._api_params[astraSitesVars.page_builder] = selected_page_builder_id;
			} else if (astraSitesVars.sites && astraSitesVars['page-builders'].include) {
				if (AstraSitesAdmin._isArray(astraSitesVars['page-builders'].include)) {
					AstraSitesAdmin._api_params[astraSitesVars.page_builder] = astraSitesVars['page-builders'].include.join(',');
				} else {
					AstraSitesAdmin._api_params[astraSitesVars.page_builder] = astraSitesVars['page-builders'].include;
				}
			}
		},

		_apiAddParam_purchase_key: function () {
			if (astraSitesVars.sites && astraSitesVars.sites.purchase_key) {
				AstraSitesAdmin._api_params['purchase_key'] = astraSitesVars.sites.purchase_key;
			}
		},

		_apiAddParam_site_url: function () {
			if (astraSitesVars.sites && astraSitesVars.sites.site_url) {
				AstraSitesAdmin._api_params['site_url'] = astraSitesVars.sites.site_url;
			}
		},

		_show_default_page_builder_sites: function () {

			if (!$('#astra-sites').length) {
				return;
			}

			if (Object.keys(astraSitesVars.default_page_builder_sites).length) {
				var favorites = AstraSitesAdmin._getParamFromURL('favorites');
				var search_term = AstraSitesAdmin._getParamFromURL('search');
				if (search_term) {
					var items = AstraSitesAdmin._get_sites_and_pages_by_search_term(search_term);

					if (!AstraSitesAdmin.isEmpty(items)) {
						AstraSitesAdmin.add_sites(items);
						$('#wp-filter-search-input').val(search_term);
					} else {
						$('#astra-sites').html(astraSitesVars.default_page_builder_sites);
					}

				} else if (favorites) {
					AstraSitesAdmin._show_favorite();
				} else {
					AstraSitesAdmin.add_sites(astraSitesVars.default_page_builder_sites);
				}

				// Show single site preview.
				var site_id = AstraSitesAdmin._getParamFromURL('astra-site');

				if (site_id) {
					AstraSitesAdmin.show_pages_by_site_id(site_id);
				}
			} else {

				var temp = [];
				for (var i = 0; i < 8; i++) {
					temp['id-' + i] = {
						'title': 'Lorem Ipsum',
						'class': 'placeholder-site',
					};
				}

				AstraSitesAdmin.add_sites(temp);
				$('#astra-sites').addClass('temp');

				AstraSitesAdmin._sync_library_with_ajax(true);
			}

			var show_license = AstraSitesAdmin._getParamFromURL('license');
			if (show_license) {
				AstraSitesAdmin._show_activate_license_notice();
			}
		},

		_change_page_builder: function () {
			var page_builder = $(this).attr('data-page-builder') || '';

			$(this).parents('.page-builders').find('img').removeClass('active');
			$(this).find('img').addClass('active');

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-change-page-builder',
					page_builder: page_builder,
				},
				beforeSend: function () {
					console.groupCollapsed('Change Page Builder');
					AstraSitesAdmin._log('Change Page Builder..');
				},
			})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					AstraSitesAdmin._clean_url_params('astra-site');
					AstraSitesAdmin._clean_url_params('astra-page');
					AstraSitesAdmin._clean_url_params('change-page-builder');
					AstraSitesAdmin._clean_url_params('license');
					console.groupEnd();
					location.reload();
				});
		},

		_ajax_change_page_builder: function () {

			var page_builder_slug = $(this).attr('data-page-builder') || '';
			var page_builder_img = $(this).find('img').attr('src') || '';
			var page_builder_title = $(this).find('.title').text() || '';
			if (page_builder_img) {
				$('.selected-page-builder').find('img').attr('src', page_builder_img);
			}
			if (page_builder_title) {
				$('.selected-page-builder').find('.page-builder-title').text(page_builder_title);
			}

			$('#wp-filter-search-input').val('');
			$('#astra-sites-admin').removeClass('searching');
			$('body').removeClass('astra-previewing-single-pages');

			if ($('.page-builders [data-page-builder="' + page_builder_slug + '"]').length) {
				$('.page-builders [data-page-builder="' + page_builder_slug + '"]').siblings().removeClass('active');
				$('.page-builders [data-page-builder="' + page_builder_slug + '"]').addClass('active');
			}

			if (page_builder_slug) {

				AstraSitesAdmin._clean_url_params('astra-site');
				AstraSitesAdmin._clean_url_params('astra-page');
				AstraSitesAdmin._clean_url_params('license');

				$('#astra-sites').show();
				$('#site-pages').hide();

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						action: 'astra-sites-change-page-builder',
						page_builder: page_builder_slug,
					},
					beforeSend: function () {
						console.groupCollapsed('Change Page Builder');
						AstraSitesAdmin._log('Change Page Builder..');
					},
				})
					.done(function (response) {
						AstraSitesAdmin._log(response);
						console.groupEnd();
						if (response.success) {
							$('.astra-sites__category-filter-items').find('.ast-sites__filter-wrap').removeClass('category-active');
							$('.ast-sites__filter-wrap').first().addClass('category-active');
							$('.astra-sites__category-filter-anchor').attr('data-slug', '');
							AstraSitesAdmin.filter_array = [];
							$('.ast-sites__filter-wrap-checkbox input:radio').attr('checked', false);
							$('.ast-sites__filter-wrap-checkbox input:radio').removeClass('active');
							$('#radio-all').trigger('click');
							$('#radio-all').addClass('active');
							$('.astra-sites__category-filter-anchor').text('All');
							AstraSitesAdmin._closeFilter();

							astraSitesVars.default_page_builder = page_builder_slug;

							// Set changed page builder data as a default page builder object.
							astraSitesVars.default_page_builder_sites = response.data;
							$('.astra-sites-show-favorite-button').removeClass('active');
							AstraSitesAdmin.add_sites(response.data);

							AstraSitesAdmin._autocomplete();
						}
					});

			}
		},

		add_sites_after_search: function (data) {
			var template = wp.template('astra-sites-page-builder-sites-search');
			$('#astra-sites').html(template(data));
			AstraSitesAdmin._load_large_images();
			$(document).trigger('astra-sites-added-sites');
		},

		add_sites: function (data) {
			var template = wp.template('astra-sites-page-builder-sites');
			$('#astra-sites').html(template(data));
			AstraSitesAdmin._load_large_images();
			$(document).trigger('astra-sites-added-sites');
		},

		_toggle_tooltip: function (event) {
			event.preventDefault();
			var tip_id = $(this).data('tip-id') || '';
			if (tip_id && $('#' + tip_id).length) {
				$('#' + tip_id).toggle();
			}
		},

		_resetData: function () {

			if ($(this).hasClass('updating-message')) {
				return;
			}
			if (AstraSitesAdmin.subscribe_skiped || AstraSitesAdmin.subscription_form_submitted == 'yes') {
				$('.user-building-for-title').hide();
				$('.astra-sites-advanced-options').show();
				$('.astra-sites-advanced-options-heading').hide();
				$('#astra-sites-subscription-form-one').hide();
			}
			if (false === AstraSitesAdmin.subscribe_skiped && $('.subscription-enabled').length && AstraSitesAdmin.subscription_form_submitted !== 'yes') {
				AstraSitesAdmin._validate_field($('.subscription-input-wp-user-type'));
				AstraSitesAdmin._validate_field($('.subscription-input-build-website-for'));

				if ($('.subscription-field-wrap').hasClass('subscription-error')) {
					console.log('error');
					return;
				}
			}

			$('.site-import-cancel').show();

			$('.install-theme-info').hide();

			if (false === AstraSitesAdmin.subscribe_skiped && $('.subscription-enabled').length && AstraSitesAdmin.subscription_form_submitted !== 'yes') {
				$('.subscription-popup').show();
				$('.astra-sites-result-preview .default').hide();
			} else {
				AstraSitesAdmin.subscribe_status = true;
				$('.ast-importing-wrap').show();
			}

			AstraSitesAdmin.import_start_time = new Date();

			$(this).addClass('updating-message installing').text('Importing..');
			$('body').addClass('importing-site');

			var output = '<div class="current-importing-status-title"></div><div class="current-importing-status-description"></div>';
			$('.current-importing-status').html(output);

			// Process Theme Activate and Install Process
			if ($('.astra-sites-theme-activation .checkbox').is(':checked')) {
				var status = $('.astra-sites-theme-activation .checkbox').data('status')
				AstraSitesAdmin._installAstra(status);
			}

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-set-reset-data',
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Site Reset Data');
				},
			})
				.done(function (response) {
					console.log('List of Reset Items:');
					AstraSitesAdmin._log(response);
					console.groupEnd();
					if (response.success) {
						AstraSitesAdmin.site_imported_data = response.data;

						// Process Bulk Plugin Install & Activate.
						AstraSitesAdmin._bulkPluginInstallActivate();
					}
				});

		},

		_remove_skip_and_import_popup: function (event) {
			event.preventDefault();

			$(this).parents('.skip-and-import').addClass('hide-me visited');

			if ($('.skip-and-import.hide-me').not('.visited').length) {
				$('.skip-and-import.hide-me').not('.visited').first().removeClass('hide-me');
			} else {
				$('.astra-sites-result-preview .default').removeClass('hide-me');

				if ($('.astra-sites-result-preview').hasClass('import-page')) {

					AstraSitesAdmin.skip_and_import_popups = [];

					var notinstalled = AstraSitesAdmin.required_plugins.notinstalled || 0;
					if (!notinstalled.length) {
						AstraSitesAdmin.import_page_process();
					}
				}
			}
		},

		_start_site_import: function () {

			if (AstraSitesAdmin._is_reset_data()) {
				$(document).trigger('astra-sites-reset-data');
			} else {
				$(document).trigger('astra-sites-reset-data-done');
			}
		},

		_reset_customizer_data: function () {
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-reset-customizer-data',
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Reseting Customizer Data');
					AstraSitesAdmin._log_title('Reseting Customizer Data..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Reset Customizer Settings Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (data) {
					AstraSitesAdmin._log(data);
					AstraSitesAdmin._log_title('Complete Resetting Customizer Data..');
					AstraSitesAdmin._log('Complete Resetting Customizer Data..');
					console.groupEnd();
					$(document).trigger('astra-sites-reset-customizer-data-done');
				});
		},

		_reset_site_options: function () {
			// Site Options.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-reset-site-options',
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Reseting Site Options');
					AstraSitesAdmin._log_title('Reseting Site Options..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Reset Site Options Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (data) {
					AstraSitesAdmin._log(data);
					AstraSitesAdmin._log_title('Complete Reseting Site Options..');
					console.groupEnd();
					$(document).trigger('astra-sites-reset-site-options-done');
				});
		},

		_reset_widgets_data: function () {
			// Widgets.
			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				data: {
					action: 'astra-sites-reset-widgets-data',
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Reseting Widgets');
					AstraSitesAdmin._log_title('Reseting Widgets..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Reset Widgets Data Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (data) {
					AstraSitesAdmin._log(data);
					AstraSitesAdmin._log_title('Complete Reseting Widgets..');
					console.groupEnd();
					$(document).trigger('astra-sites-reset-widgets-data-done');
				});
		},

		_reset_posts: function () {
			if (AstraSitesAdmin.site_imported_data['reset_posts'].length) {

				AstraSitesAdmin.reset_remaining_posts = AstraSitesAdmin.site_imported_data['reset_posts'].length;

				console.groupCollapsed('Deleting Posts');
				AstraSitesAdmin._log_title('Deleting Posts..');

				$.each(AstraSitesAdmin.site_imported_data['reset_posts'], function (index, post_id) {

					AstraSitesAjaxQueue.add({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						data: {
							action: 'astra-sites-delete-posts',
							post_id: post_id,
							_ajax_nonce: astraSitesVars._ajax_nonce,
						},
						success: function (result) {

							if (AstraSitesAdmin.reset_processed_posts < AstraSitesAdmin.site_imported_data['reset_posts'].length) {
								AstraSitesAdmin.reset_processed_posts += 1;
							}

							AstraSitesAdmin._log_title('Deleting Post ' + AstraSitesAdmin.reset_processed_posts + ' of ' + AstraSitesAdmin.site_imported_data['reset_posts'].length + '<br/>' + result.data);

							AstraSitesAdmin.reset_remaining_posts -= 1;
							if (0 == AstraSitesAdmin.reset_remaining_posts) {
								console.groupEnd();
								$(document).trigger('astra-sites-delete-posts-done');
								$(document).trigger('astra-sites-reset-data-done');
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();

			} else {
				$(document).trigger('astra-sites-delete-posts-done');
				$(document).trigger('astra-sites-reset-data-done');
			}
		},

		_reset_wp_forms: function () {
			if (AstraSitesAdmin.site_imported_data['reset_wp_forms'].length) {
				AstraSitesAdmin.reset_remaining_wp_forms = AstraSitesAdmin.site_imported_data['reset_wp_forms'].length;

				console.groupCollapsed('Deleting WP Forms');
				AstraSitesAdmin._log_title('Deleting WP Forms..');

				$.each(AstraSitesAdmin.site_imported_data['reset_wp_forms'], function (index, post_id) {
					AstraSitesAjaxQueue.add({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						data: {
							action: 'astra-sites-delete-wp-forms',
							post_id: post_id,
							_ajax_nonce: astraSitesVars._ajax_nonce,
						},
						success: function (result) {

							if (AstraSitesAdmin.reset_processed_wp_forms < AstraSitesAdmin.site_imported_data['reset_wp_forms'].length) {
								AstraSitesAdmin.reset_processed_wp_forms += 1;
							}

							AstraSitesAdmin._log_title('Deleting Form ' + AstraSitesAdmin.reset_processed_wp_forms + ' of ' + AstraSitesAdmin.site_imported_data['reset_wp_forms'].length + '<br/>' + result.data);
							AstraSitesAdmin._log('Deleting Form ' + AstraSitesAdmin.reset_processed_wp_forms + ' of ' + AstraSitesAdmin.site_imported_data['reset_wp_forms'].length + '<br/>' + result.data);

							AstraSitesAdmin.reset_remaining_wp_forms -= 1;
							if (0 == AstraSitesAdmin.reset_remaining_wp_forms) {
								console.groupEnd();
								$(document).trigger('astra-sites-delete-wp-forms-done');
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();

			} else {
				$(document).trigger('astra-sites-delete-wp-forms-done');
			}
		},

		_reset_terms: function () {

			if (AstraSitesAdmin.site_imported_data['reset_terms'].length) {
				AstraSitesAdmin.reset_remaining_terms = AstraSitesAdmin.site_imported_data['reset_terms'].length;

				console.groupCollapsed('Deleting Terms');
				AstraSitesAdmin._log_title('Deleting Terms..');

				$.each(AstraSitesAdmin.site_imported_data['reset_terms'], function (index, term_id) {
					AstraSitesAjaxQueue.add({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						data: {
							action: 'astra-sites-delete-terms',
							term_id: term_id,
							_ajax_nonce: astraSitesVars._ajax_nonce,
						},
						success: function (result) {
							if (AstraSitesAdmin.reset_processed_terms < AstraSitesAdmin.site_imported_data['reset_terms'].length) {
								AstraSitesAdmin.reset_processed_terms += 1;
							}

							AstraSitesAdmin._log_title('Deleting Term ' + AstraSitesAdmin.reset_processed_terms + ' of ' + AstraSitesAdmin.site_imported_data['reset_terms'].length + '<br/>' + result.data);
							AstraSitesAdmin._log('Deleting Term ' + AstraSitesAdmin.reset_processed_terms + ' of ' + AstraSitesAdmin.site_imported_data['reset_terms'].length + '<br/>' + result.data);

							AstraSitesAdmin.reset_remaining_terms -= 1;
							if (0 == AstraSitesAdmin.reset_remaining_terms) {
								console.groupEnd();
								$(document).trigger('astra-sites-delete-terms-done');
							}
						}
					});
				});
				AstraSitesAjaxQueue.run();
			} else {
				$(document).trigger('astra-sites-delete-terms-done');
			}

		},

		_toggle_reset_notice: function () {
			if ($(this).is(':checked')) {
				$('#astra-sites-tooltip-reset-data').show();
			} else {
				$('#astra-sites-tooltip-reset-data').hide();
			}
		},

		_toggle_theme_notice: function () {
			var astra_dependent_plugins = ['astra-addon'];

			if (AstraSitesAdmin.isEmpty(AstraSitesAdmin.required_plugins_original_list)) {
				AstraSitesAdmin.required_plugins_original_list = astraSitesVars.requiredPlugins;
			}

			var plugins = AstraSitesAdmin.required_plugins_original_list;

			$(this).parents('.astra-site-contents').addClass('required-plugins-count-' + $('.astra-sites-import-plugins .required-plugins-list > li').length);

			if ($(this).is(':checked')) {

				$('#astra-sites-tooltip-theme-activation').hide();
				$('.astra-site-contents .astra-theme-module').show();
				$(this).parents('.astra-site-contents').removeClass('dont-use-astra-theme');

				astraSitesVars.requiredPlugins = plugins;

			} else {
				$(this).parents('.astra-site-contents').addClass('dont-use-astra-theme');
				$('#astra-sites-tooltip-theme-activation').show();
				$('.astra-site-contents .astra-theme-module').hide();

				var new_plugins = [];
				for (plugin_group in plugins) {
					var temp = [];
					for (key in plugins[plugin_group]) {
						if (!astra_dependent_plugins.includes(plugins[plugin_group][key].slug)) {
							temp.push(plugins[plugin_group][key]);
						}
					}
					new_plugins[plugin_group] = temp;
				}

				astraSitesVars.requiredPlugins = new_plugins;
			}
		},

		_backup_before_rest_options: function () {
			AstraSitesAdmin._backupOptions('astra-sites-backup-settings-before-reset-done');
			AstraSitesAdmin.backup_taken = true;
		},

		_recheck_backup_options: function () {
			AstraSitesAdmin._backupOptions('astra-sites-backup-settings-done');
			AstraSitesAdmin.backup_taken = true;
		},

		_backupOptions: function (trigger_name) {

			// Customizer backup is already taken then return.
			if (AstraSitesAdmin.backup_taken) {
				$(document).trigger(trigger_name);
			} else {

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						action: 'astra-sites-backup-settings',
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Processing Customizer Settings Backup');
						AstraSitesAdmin._log_title('Processing Customizer Settings Backup..');
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Backup Customizer Settings Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (data) {
						AstraSitesAdmin._log(data);

						// 1. Pass - Import Customizer Options.
						AstraSitesAdmin._log_title('Customizer Settings Backup Done..');

						console.groupEnd();
						// Custom trigger.
						$(document).trigger(trigger_name);
					});
			}

		},

		/**
		 * 5. Import Complete.
		 */
		_importEnd: function (event) {

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'astra-sites-import-end',
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Import Complete!');
					AstraSitesAdmin._log_title('Import Complete!');
					// console.groupCollapsed( 'Import Complete!' );
				}
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import Complete Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd();

					// 5. Fail - Import Complete.
					if (false === response.success) {
						AstraSitesAdmin._importFailMessage(response.data, 'Import Complete Failed!');
					} else {
						AstraSitesAdmin.site_import_status = true;
						AstraSitesAdmin.import_complete();
					}
				});
		},

		page_import_complete: function () {
			if (false === AstraSitesAdmin.subscribe_status) {
				return;
			}

			$('body').removeClass('importing-site');
			$('.rotating, .current-importing-status-wrap,.notice-warning').remove();
			var template = wp.template('astra-sites-page-import-success');
			$('.astra-sites-result-preview .inner').html(template(AstraSitesAdmin.imported_page_data));

			AstraSitesAdmin.page_import_status = false;
			AstraSitesAdmin.subscribe_status = false;
		},

		import_complete: function () {

			if (false === AstraSitesAdmin.subscribe_status) {
				return;
			}

			$('body').removeClass('importing-site');

			var template = wp.template('astra-sites-site-import-success');
			$('.astra-sites-result-preview .inner').html(template());

			$('.rotating,.current-importing-status-wrap,.notice-warning').remove();
			$('.astra-sites-result-preview').addClass('astra-sites-result-preview');

			// 5. Pass - Import Complete.
			AstraSitesAdmin._importSuccessButton();

			AstraSitesAdmin.site_import_status = false;
			AstraSitesAdmin.subscribe_status = false;
			if (!AstraSitesAdmin.first_import_complete) {
				AstraSitesAdmin.first_import_complete = 'yes';
			}
		},

		/**
		 * 4. Import Widgets.
		 */
		_importWidgets: function (event) {
			if (AstraSitesAdmin._is_process_widgets()) {
				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-widgets',
						widgets_data: AstraSitesAdmin.widgets_data,
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing Widgets');
						AstraSitesAdmin._log_title('Importing Widgets..');
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import Widgets Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);
						console.groupEnd();

						// 4. Fail - Import Widgets.
						if (false === response.success) {
							AstraSitesAdmin._importFailMessage(response.data, 'Import Widgets Failed!');

						} else {

							// 4. Pass - Import Widgets.
							$(document).trigger('astra-sites-import-widgets-done');
						}
					});
			} else {
				$(document).trigger('astra-sites-import-widgets-done');
			}
		},

		/**
		 * 3. Import Site Options.
		 */
		import_siteOptions: function (event) {

			if (AstraSitesAdmin._is_process_xml()) {
				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-options',
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing Options');
						AstraSitesAdmin._log_title('Importing Options..');
						$('.astra-demo-import .percent').html('');
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import Site Options Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);
						// 3. Fail - Import Site Options.
						if (false === response.success) {
							AstraSitesAdmin._importFailMessage(response.data, 'Import Site Options Failed!');
							console.groupEnd();
						} else {
							console.groupEnd();

							// 3. Pass - Import Site Options.
							$(document).trigger('astra-sites-import-options-done');
						}
					});
			} else {
				$(document).trigger('astra-sites-import-options-done');
			}
		},

		/**
		 * 2. Prepare XML Data.
		 */
		_importXML: function () {

			if (AstraSitesAdmin._is_process_xml()) {
				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-prepare-xml',
						wxr_url: AstraSitesAdmin.wxr_url,
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing Content');
						AstraSitesAdmin._log_title('Importing Content..');
						AstraSitesAdmin._log(AstraSitesAdmin.wxr_url);
						$('.astra-site-import-process-wrap').show();
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Prepare Import XML Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {

						AstraSitesAdmin._log(response);

						// 2. Fail - Prepare XML Data.
						if (false === response.success) {
							var error_msg = response.data.error || response.data;

							AstraSitesAdmin._importFailMessage(astraSitesVars.xmlRequiredFilesMissing);

							console.groupEnd();
						} else {

							var xml_processing = $('.astra-demo-import').attr('data-xml-processing');

							if ('yes' === xml_processing) {
								return;
							}

							$('.astra-demo-import').attr('data-xml-processing', 'yes');

							// 2. Pass - Prepare XML Data.

							// Import XML though Event Source.
							AstraSSEImport.data = response.data;
							AstraSSEImport.render();

							$('.current-importing-status-description').html('').show();

							$('.current-importing-status-wrap').append('<div class="astra-site-import-process-wrap"><progress class="astra-site-import-process" max="100" value="0"></progress></div>');

							var evtSource = new EventSource(AstraSSEImport.data.url);
							evtSource.onmessage = function (message) {
								var data = JSON.parse(message.data);
								switch (data.action) {
									case 'updateDelta':

										AstraSSEImport.updateDelta(data.type, data.delta);
										break;

									case 'complete':
										if ( false == data.error ) {
											evtSource.close();

											$('.current-importing-status-description').hide();
											$('.astra-demo-import').removeAttr('data-xml-processing');

											document.getElementsByClassName("astra-site-import-process").value = '100';

											$('.astra-site-import-process-wrap').hide();
											console.groupEnd();

											$(document).trigger('astra-sites-import-xml-done');
										} else {
											evtSource.close();
											AstraSitesAdmin._importFailMessage(
												astraSitesVars.xml_import_interrupted_error,
												'Import Process Interrupted!',
												'',
												'<p>' + astraSitesVars.xml_import_interrupted_primary + '</p>',
												'<p>' + astraSitesVars.xml_import_interrupted_secondary + '</p>'
											);
										}

										break;
								}
							};
							evtSource.onerror = function (error) {
								evtSource.close();
								console.log(error);
								AstraSitesAdmin._importFailMessage('', 'Import Process Interrupted');
							};
							evtSource.addEventListener('log', function (message) {
								var data = JSON.parse(message.data);
								var message = data.message || '';
								if (message && 'info' === data.level) {
									message = message.replace(/"/g, function (letter) {
										return '';
									});
									$('.current-importing-status-description').html(message);
								}
								AstraSitesAdmin._log(message, data.level);
							});
						}
					});
			} else {
				$(document).trigger('astra-sites-import-xml-done');
			}
		},

		_is_reset_data: function () {
			if ($('.astra-sites-reset-data').find('.checkbox').is(':checked')) {
				return true;
			}
			return false;
		},

		_is_process_xml: function () {
			if ($('.astra-sites-import-xml').find('.checkbox').is(':checked')) {
				return true;
			}
			return false;
		},

		_is_process_customizer: function () {
			var theme_status = $('.astra-sites-theme-activation .checkbox').length ? $('.astra-sites-theme-activation .checkbox').is(':checked') : true;
			var customizer_status = $('.astra-sites-import-customizer').find('.checkbox').is(':checked');

			if (theme_status && customizer_status) {
				return true;
			}
			return false;
		},

		_is_process_widgets: function () {
			if ($('.astra-sites-import-widgets').find('.checkbox').is(':checked')) {
				return true;
			}
			return false;
		},

		_startImportCartFlows: function (event) {
			if (AstraSitesAdmin._is_process_xml() && '' !== AstraSitesAdmin.cartflows_url) {

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-cartflows',
						cartflows_url: AstraSitesAdmin.cartflows_url,
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing Flows & Steps');
						AstraSitesAdmin._log_title('Importing Flows & Steps..');
						AstraSitesAdmin._log(AstraSitesAdmin.cartflows_url);
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import Cartflows Flow Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);

						// 1. Fail - Import WPForms Options.
						if (false === response.success) {
							AstraSitesAdmin._importFailMessage(response.data, 'Import Cartflows Flow Failed!');
							console.groupEnd();
						} else {
							console.groupEnd();
							// 1. Pass - Import Customizer Options.
							$(document).trigger(AstraSitesAdmin.action_slug + '-import-cartflows-done');
						}
					});

			} else {
				$(document).trigger(AstraSitesAdmin.action_slug + '-import-cartflows-done');
			}

		},

		_startImportWPForms: function (event) {

			if (AstraSitesAdmin._is_process_xml() && '' !== AstraSitesAdmin.wpforms_url) {

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-wpforms',
						wpforms_url: AstraSitesAdmin.wpforms_url,
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing WP Forms');
						AstraSitesAdmin._log_title('Importing WP Forms..');
						AstraSitesAdmin._log(AstraSitesAdmin.wpforms_url);
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import WP Forms Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);

						// 1. Fail - Import WPForms Options.
						if (false === response.success) {
							AstraSitesAdmin._importFailMessage(response.data, 'Import WP Forms Failed!');
							console.groupEnd();
						} else {
							console.groupEnd();
							// 1. Pass - Import Customizer Options.
							$(document).trigger(AstraSitesAdmin.action_slug + '-import-wpforms-done');
						}
					});

			} else {
				$(document).trigger(AstraSitesAdmin.action_slug + '-import-wpforms-done');
			}

		},

		/**
		 * 1. Import Customizer Options.
		 */
		_importCustomizerSettings: function (event) {
			if (AstraSitesAdmin._is_process_customizer()) {
				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'astra-sites-import-customizer-settings',
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Importing Customizer Settings');
						AstraSitesAdmin._log_title('Importing Customizer Settings..');
						AstraSitesAdmin._log(JSON.parse(AstraSitesAdmin.customizer_data));
					},
				})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage(jqXHR.status + ' ' + jqXHR.statusText, 'Import Customizer Settings Failed!', jqXHR);
						console.groupEnd();
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);

						// 1. Fail - Import Customizer Options.
						if (false === response.success) {
							AstraSitesAdmin._importFailMessage(response.data, 'Import Customizer Settings Failed!');
							console.groupEnd();
						} else {
							console.groupEnd();
							// 1. Pass - Import Customizer Options.
							$(document).trigger('astra-sites-import-customizer-settings-done');
						}
					});
			} else {
				$(document).trigger('astra-sites-import-customizer-settings-done');
			}

		},

		/**
		 * Import Success Button.
		 *
		 * @param  {string} data Error message.
		 */
		_importSuccessButton: function () {

			$('.astra-demo-import').removeClass('updating-message installing')
				.removeAttr('data-import')
				.addClass('view-site')
				.removeClass('astra-demo-import')
				.text(astraSitesVars.strings.viewSite)
				.attr('target', '_blank')
				.append('<i class="dashicons dashicons-external"></i>')
				.attr('href', astraSitesVars.siteURL);
		},

		/**
		 * Import Error Button.
		 *
		 * @param  {string} data Error message.
		 */
		_importFailMessage: function (message, heading, jqXHR, topContent, bottomContent) {

			heading = heading || 'The import process interrupted';

			var status_code = '';
			if (jqXHR) {
				status_code = jqXHR.status ? parseInt(jqXHR.status) : '';
			}

			if (200 == status_code && astraSitesVars.debug) {
				var output = astraSitesVars.importFailedMessageDueToDebug;

			} else {
				var output = topContent || astraSitesVars.importFailedMessage;

				if (message) {
					output += '<div class="current-importing-status">Error: ' + message + '</div>';
				}

				output += bottomContent || '';
			}

			$('.astra-sites-import-content').html(output);
			$('.astra-sites-result-preview .heading h3').html(heading);

			$('.astra-demo-import').removeClass('updating-message installing button-primary').addClass('disabled').text('Import Failed!');
		},

		ucwords: function (str) {
			if (!str) {
				return '';
			}

			str = str.toLowerCase().replace(/\b[a-z]/g, function (letter) {
				return letter.toUpperCase();
			});

			str = str.replace(/-/g, function (letter) {
				return ' ';
			});

			return str;
		},

		/**
		 * Install Success
		 */
		_installSuccess: function (event, response) {

			event.preventDefault();

			console.groupEnd();

			// Reset not installed plugins list.
			var pluginsList = astraSitesVars.requiredPlugins.notinstalled;
			astraSitesVars.requiredPlugins.notinstalled = AstraSitesAdmin._removePluginFromQueue(response.slug, pluginsList);

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout(function () {

				console.groupCollapsed('Activating Plugin "' + response.name + '"');

				AstraSitesAdmin._log_title('Activating Plugin - ' + response.name);
				AstraSitesAdmin._log('Activating Plugin - ' + response.name);

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						'action': 'astra-required-plugin-activate',
						'init': response.init,
						'_ajax_nonce': astraSitesVars._ajax_nonce,
					},
				})
					.done(function (result) {
						AstraSitesAdmin._log(result);

						if (result.success) {
							var pluginsList = astraSitesVars.requiredPlugins.inactive;

							AstraSitesAdmin._log_title('Successfully Activated Plugin - ' + response.name);
							AstraSitesAdmin._log('Successfully Activated Plugin - ' + response.name);

							// Reset not installed plugins list.
							astraSitesVars.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue(response.slug, pluginsList);

							// Enable Demo Import Button
							AstraSitesAdmin._enable_demo_import_button();
						}
						console.groupEnd();
					});

			}, 1200);

		},

		/**
		 * Plugin Installation Error.
		 */
		_installError: function (event, response) {

			event.preventDefault();

			console.log(event);
			console.log(response);

			$('.astra-sites-result-preview .heading h3').text('Plugin Installation Failed');
			$('.astra-sites-import-content').html('<p>Plugin "<b>' + response.name + '</b>" installation failed.</p><p>There has been an error on your website. Read an article <a href="https://wpastra.com/docs/starter-templates-plugin-installation-failed/" target="blank">here</a> to solve the issue.</p>');

			$('.astra-demo-import').removeClass('updating-message installing button-primary').addClass('disabled').text('Import Failed!');

			wp.updates.queue = [];

			wp.updates.queueChecker();

			console.groupEnd();
		},

		/**
		 * Installing Plugin
		 */
		_pluginInstalling: function (event, args) {
			event.preventDefault();

			console.groupCollapsed('Installing Plugin "' + args.name + '"');

			AstraSitesAdmin._log_title('Installing Plugin - ' + args.name);

			console.log(args);
		},

		/**
		 * Bulk Plugin Active & Install
		 */
		_bulkPluginInstallActivate: function () {

			var not_installed = [];
			var activate_plugins = [];
			if( astraSitesVars.requiredPlugins ) {
				activate_plugins = astraSitesVars.requiredPlugins.inactive || [];
				not_installed = astraSitesVars.requiredPlugins.notinstalled || [];
			}

			// If has class the skip-plugins then,
			// Avoid installing 3rd party plugins.
			if ($('.astra-sites-result-preview').hasClass('skip-plugins')) {
				not_installed = [];
			}

			// First Install Bulk.
			if (not_installed.length > 0) {
				AstraSitesAdmin._installAllPlugins(not_installed);
			}

			// Second Activate Bulk.
			if (activate_plugins.length > 0) {
				AstraSitesAdmin._activateAllPlugins(activate_plugins);
			}

			if (activate_plugins.length <= 0 && not_installed.length <= 0) {
				AstraSitesAdmin._enable_demo_import_button();
			}

		},

		/**
		 * Activate All Plugins.
		 */
		_activateAllPlugins: function (activate_plugins) {

			AstraSitesAdmin.remaining_activate_plugins = activate_plugins.length;

			$.each(activate_plugins, function (index, single_plugin) {

				AstraSitesAjaxQueue.add({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						'action': 'astra-required-plugin-activate',
						'init': single_plugin.init,
						'options': AstraSitesAdmin.options_data,
						'enabledExtensions': AstraSitesAdmin.enabled_extensions,
						'_ajax_nonce': astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Activating Plugin "' + single_plugin.name + '"');
						AstraSitesAdmin._log_title('Activating Plugin "' + single_plugin.name + '"');
					},
					success: function (result) {
						console.log(result);
						console.groupEnd('Activating Plugin "' + single_plugin.name + '"');

						if (result.success) {
							var pluginsList = astraSitesVars.requiredPlugins.inactive;

							// Reset not installed plugins list.
							astraSitesVars.requiredPlugins.inactive = AstraSitesAdmin._removePluginFromQueue(single_plugin.slug, pluginsList);

							// Enable Demo Import Button
							AstraSitesAdmin._enable_demo_import_button();
						}

						AstraSitesAdmin.remaining_activate_plugins -= 1;

						if (0 === AstraSitesAdmin.remaining_activate_plugins) {
							console.groupEnd('Activating Required Plugins..');
						}
					}
				});
			});
			AstraSitesAjaxQueue.run();
		},

		/**
		 * Install All Plugins.
		 */
		_installAllPlugins: function (not_installed) {

			$.each(not_installed, function (index, single_plugin) {

				// Add each plugin activate request in Ajax queue.
				// @see wp-admin/js/updates.js
				wp.updates.queue.push({
					action: 'install-plugin', // Required action.
					data: {
						slug: single_plugin.slug,
						init: single_plugin.init,
						name: single_plugin.name,
						success: function () {
							$(document).trigger('wp-plugin-install-success', [single_plugin]);
						},
						error: function () {
							$(document).trigger('wp-plugin-install-error', [single_plugin]);
						},
					}
				});
			});

			// Required to set queue.
			wp.updates.queueChecker();
		},

		_show_get_agency_bundle_notice: function (event) {
			event.preventDefault();
			$('.astra-sites-result-preview')
				.removeClass('astra-sites-activate-license astra-sites-site-import-popup astra-sites-page-import-popup')
				.addClass('astra-sites-get-agency-bundle')
				.show();

			var template = wp.template('astra-sites-pro-site-description');
			var output = '<div class="overlay"></div>';
			output += '<div class="inner"><div class="heading"><h3>Liked This demo?</h3></div><span class="dashicons close dashicons-no-alt"></span><div class="astra-sites-import-content">';
			output += '</div></div>';
			$('.astra-sites-result-preview').html(output);
			$('.astra-sites-import-content').html(template);
		},

		_show_activate_license_notice: function (event) {

			if (event) {
				event.preventDefault();
			}

			if (!AstraSitesAdmin._getParamFromURL('license')) {
				var url_params = {
					'license': 'show'
				};
				AstraSitesAdmin._changeAndSetURL(url_params);
			}

			$('.astra-sites-result-preview')
				.removeClass('astra-sites-site-import-popup astra-sites-skip-templates astra-sites-page-import-popup')
				.addClass('astra-sites-activate-license')
				.show();

			var template = wp.template('astra-sites-activate-license');
			var output = '<div class="overlay"></div>';
			output += '<div class="inner"><div class="heading"><h3>Activate License for Premium Templates</h3></div><span class="dashicons close dashicons-no-alt"></span><div class="astra-sites-import-content">';
			output += '</div></div>';
			$('.astra-sites-result-preview').html(output);
			$('.astra-sites-import-content').html(template);
		},

		_show_invalid_mini_agency_license: function (event) {
			event.preventDefault();
			$('.astra-sites-result-preview')
				.removeClass('astra-sites-activate-license astra-sites-site-import-popup astra-sites-skip-templates astra-sites-page-import-popup')
				.addClass('astra-sites-invalid-mini-agency-license')
				.show();

			var template = wp.template('astra-sites-invalid-mini-agency-license');
			var output = '<div class="overlay"></div>';
			output += '<div class="inner"><div class="heading"><h3>Not Valid License</h3></div><span class="dashicons close dashicons-no-alt"></span><div class="astra-sites-import-content">';
			output += '</div></div>';
			$('.astra-sites-result-preview').html(output);
			$('.astra-sites-import-content').html(template);
		},

		_get_id: function (site_id) {
			return site_id.replace('id-', '');
		},

		/**
		 * Fires when a nav item is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method _show_site_popup
		 */
		_show_site_popup: function (event) {
			event.preventDefault();

			if ($(this).hasClass('updating-message')) {
				return;
			}

			var delay = 1;
			var retry_str = '';
			if ( AstraSitesAdmin.delay_in_request ) {
				delay = AstraSitesAdmin.delay_value;
				retry_str = '<p class="ast-retry-text">Retrying in <span class="ast-retry-sec">10</span>...</p>';

				var timeleft = AstraSitesAdmin.delay_value / 1000;
				var countdown = setInterval(function() {
					timeleft -= 1;
					$(".ast-retry-sec").html( timeleft );
					if ( timeleft <= 0 ) {
						clearInterval(countdown);
						$(".ast-retry-sec").html( "0" );
					}
				}, 1000);
			}

			$('.astra-sites-result-preview').addClass('import-site').removeClass('import-page');

			$('.astra-sites-result-preview')
				.removeClass('astra-sites-get-agency-bundle preview-page-from-search-result astra-sites-page-import-popup astra-sites-activate-license')
				.addClass('astra-sites-site-import-popup')
				.show();

			var template = wp.template('astra-sites-result-preview');
			$('.astra-sites-result-preview').html(template('astra-sites')).addClass('preparing');
			$('.astra-sites-import-content').append('<div class="astra-loading-wrap"><div class="astra-loading-icon"></div></div>'+retry_str);

			// .attr('data-slug', 'astra-sites');
			AstraSitesAdmin.action_slug = 'astra-sites';
			astraSitesVars.cpt_slug = 'astra-sites';

			var site_id = $('#site-pages').attr('data-site-id') || '';
			site_id = AstraSitesAdmin._get_id(site_id);

			// AstraSitesAdmin.required_plugins_list_markup();
			if (AstraSitesAdmin.visited_sites_and_pages[site_id]) {

				AstraSitesAdmin.templateData = AstraSitesAdmin.visited_sites_and_pages[site_id];

				AstraSitesAdmin.process_site_data(AstraSitesAdmin.templateData);
			} else {


				// AstraSitesAdmin.templateData
				// Add Params for API request.
				AstraSitesAdmin._api_params = {};

				AstraSitesAdmin._apiAddParam_status();
				AstraSitesAdmin._apiAddParam_astra_site_category();
				AstraSitesAdmin._apiAddParam_astra_site_page_builder();
				AstraSitesAdmin._apiAddParam_astra_page_parent_category();
				AstraSitesAdmin._apiAddParam_site_url();
				AstraSitesAdmin._apiAddParam_purchase_key();
				var api_post = {
					id: astraSitesVars.cpt_slug,
					slug: astraSitesVars.cpt_slug + '/' + site_id + '?' + decodeURIComponent($.param(AstraSitesAdmin._api_params)),
				};

				setTimeout(function() {
					$.ajax({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						data: {
							action: 'astra-sites-api-request',
							url: astraSitesVars.cpt_slug + '/' + site_id + '?' + decodeURIComponent($.param(AstraSitesAdmin._api_params)),
						},
						beforeSend: function () {
							console.groupCollapsed('Requesting API');
						}
					})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText, '', jqXHR, astraSitesVars.ajax_request_failed_primary, astraSitesVars.ajax_request_failed_secondary );
						console.groupEnd();
						AstraSitesAdmin.delay_in_request = false;
					})
					.done(function (response) {
						console.log('Template API Response:');
						AstraSitesAdmin.delay_in_request = false;
						AstraSitesAdmin._log(response);
						console.groupEnd();
						if (response.success) {
							AstraSitesAdmin.visited_sites_and_pages[response.data.id] = response.data;

							AstraSitesAdmin.templateData = response.data;

							AstraSitesAdmin.process_site_data(AstraSitesAdmin.templateData);
						} else {
							AstraSitesAdmin.handle_error( response, site_id );
						}
					});

				}, delay);

			}

		},

		handle_error: function( response, id ) {
			var template = 'astra-sites-request-failed-user';
			var template_data = {
				'primary' : '',
				'secondary' : '',
				'error' : response.data,
				'id' : id
			};
			if ( undefined !== response.data.code ) {
				var code = response.data.code.toString();
				switch( code ) {
					case '401':
					case '404':
					case '500':
						template_data.primary = astraSitesVars.server_import_primary_error;
						break;

					case 'WP_Error':
						template_data.primary = astraSitesVars.client_import_primary_error;
						break;

					case 'Cloudflare':
						template_data.primary = astraSitesVars.cloudflare_import_primary_error;
						break;

					default:
						template = 'astra-sites-request-failed';
						break;
				}
			}
			let err_template = wp.template( template );
			$('.astra-sites-result-preview .heading > h3').text('Import Process Interrupted');
			$('.astra-sites-import-content').find('.astra-loading-wrap').remove();
			$('.astra-sites-result-preview').removeClass('preparing');
			$('.astra-sites-import-content').html( err_template( template_data ) );
			$('.astra-demo-import').removeClass('updating-message installing button-primary').addClass('disabled').text('Import Failed!');
		},

		show_popup: function (heading, content, actions, classes) {
			if (classes) {
				$('.astra-sites-popup').addClass(classes);
			}
			if (heading) {
				$('.astra-sites-popup .heading h3').html(heading);
			}
			if (content) {
				$('.astra-sites-popup .astra-sites-import-content').html(content);
			}
			if (actions) {
				$('.astra-sites-popup .ast-actioms-wrap').html(actions);
			}

			$('.astra-sites-popup').show();
		},

		hide_popup: function () {
			$('.astra-sites-popup').hide();
		},

		show_page_popup: function () {

			AstraSitesAdmin.process_import_page();
		},

		process_import_page: function () {
			AstraSitesAdmin.hide_popup();

			var page_id = AstraSitesAdmin._get_id($('#single-pages').find('.current_page').attr('data-page-id')) || '';
			var site_id = AstraSitesAdmin._get_id($('#site-pages').attr('data-site-id')) || '';

			var delay = 1;
			var retry_str = '';
			if ( AstraSitesAdmin.delay_in_request ) {
				delay = AstraSitesAdmin.delay_value;
				retry_str = '<p class="ast-retry-text">Retrying in <span class="ast-retry-sec">10</span>...</p>';

				var timeleft = AstraSitesAdmin.delay_value / 1000;
				var countdown = setInterval(function() {
					timeleft -= 1;
					$(".ast-retry-sec").html( timeleft );
					if ( timeleft <= 0 ) {
						clearInterval(countdown);
						$(".ast-retry-sec").html( "0" );
					}
				}, 1000);
			}

			$('.astra-sites-result-preview')
				.removeClass('astra-sites-subscription-popup astra-sites-activate-license astra-sites-get-agency-bundle astra-sites-site-import-popup astra-sites-page-import-popup')
				.addClass('preview-page-from-search-result')
				.show();

			$('.astra-sites-result-preview').html(wp.template('astra-sites-result-preview')).addClass('preparing');
			$('.astra-sites-import-content').append('<div class="astra-loading-wrap"><div class="astra-loading-icon"></div></div>' + retry_str);

			AstraSitesAdmin.action_slug = 'site-pages';
			astraSitesVars.cpt_slug = 'site-pages';

			if (AstraSitesAdmin.visited_sites_and_pages[page_id]) {

				AstraSitesAdmin.templateData = AstraSitesAdmin.visited_sites_and_pages[page_id];

				AstraSitesAdmin.required_plugins_list_markup(AstraSitesAdmin.templateData['site-pages-required-plugins']);
			} else {

				// AstraSitesAdmin.templateData
				// Add Params for API request.
				AstraSitesAdmin._api_params = {};

				AstraSitesAdmin._apiAddParam_status();
				AstraSitesAdmin._apiAddParam_per_page();
				AstraSitesAdmin._apiAddParam_astra_site_category();
				AstraSitesAdmin._apiAddParam_astra_site_page_builder();
				AstraSitesAdmin._apiAddParam_astra_page_parent_category();
				AstraSitesAdmin._apiAddParam_site_url();
				AstraSitesAdmin._apiAddParam_purchase_key();

				setTimeout( function() {
					// Request.
					$.ajax({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						data: {
							action: 'astra-sites-api-request',
							url: astraSitesVars.cpt_slug + '/' + page_id + '?' + decodeURIComponent($.param(AstraSitesAdmin._api_params)),
						},
						beforeSend: function () {
							console.groupCollapsed('Requesting API URL');
							AstraSitesAdmin._log('Requesting API URL');
						}
					})
					.fail(function (jqXHR) {
						AstraSitesAdmin._log(jqXHR);
						AstraSitesAdmin._importFailMessage( jqXHR.status + ' ' + jqXHR.statusText, 'Page Import API Request Failed!', jqXHR, astraSitesVars.ajax_request_failed_primary, astraSitesVars.ajax_request_failed_secondary );
						console.groupEnd();
						AstraSitesAdmin.delay_in_request = false;
					})
					.done(function (response) {
						AstraSitesAdmin._log(response);
						console.groupEnd();
						AstraSitesAdmin.delay_in_request = false;

						if (response.success) {
							AstraSitesAdmin.visited_sites_and_pages[response.data.id] = response.data;

							AstraSitesAdmin.templateData = response.data;

							AstraSitesAdmin.required_plugins_list_markup(AstraSitesAdmin.templateData['site-pages-required-plugins']);
						} else {
							AstraSitesAdmin.handle_error( response, page_id );
						}
					});
				}, delay );
			}
		},

		show_page_popup_from_search: function (event) {
			event.preventDefault();
			var page_id = $(this).parents('.astra-theme').attr('data-page-id') || '';
			var site_id = $(this).parents('.astra-theme').attr('data-site-id') || '';

			// $('.astra-sites-result-preview').show();
			$('#astra-sites').hide();
			$('#site-pages').hide();
			AstraSitesAdmin.show_pages_by_site_id(site_id, page_id);
		},

		/**
		 * Fires when a nav item is clicked.
		 *
		 * @since 1.0
		 * @access private
		 * @method show_page_popup
		 */
		show_page_popup_from_sites: function (event) {
			event.preventDefault();

			if ($(this).hasClass('updating-message')) {
				return;
			}

			$('.astra-sites-result-preview').addClass('import-page').removeClass('import-site');

			AstraSitesAdmin.show_page_popup();
		},

		// Returns if a value is an array
		_isArray: function (value) {
			return value && typeof value === 'object' && value.constructor === Array;
		},

		add_skip_and_import_popups: function (templates) {
			if (Object.keys(templates).length) {
				for (template_id in templates) {
					var template = wp.template(template_id);
					var template_data = templates[template_id] || '';
					$('.astra-sites-result-preview .inner').append(template(template_data));
				}
				$('.astra-sites-result-preview .inner > .default').addClass('hide-me');
				$('.astra-sites-result-preview .inner > .skip-and-import:not(:last-child)').addClass('hide-me');
			}
		},

		start_import: function( response ) {

			if (AstraSitesAdmin.subscribe_skiped || AstraSitesAdmin.subscription_form_submitted == 'yes') {
				$('.user-building-for-title').hide();
				$('.astra-sites-advanced-options-heading').hide();
				$('.astra-sites-advanced-options').show();
				$('#astra-sites-subscription-form-one').hide();
			}

			if (false === AstraSitesAdmin.subscribe_skiped && $('.subscription-enabled').length && AstraSitesAdmin.subscription_form_submitted !== 'yes') {
				$('.astra-sites-result-preview .heading h3').html(astraSitesVars.headings.subscription);
				$('.site-import-cancel').hide();

				if ('site-pages' === AstraSitesAdmin.action_slug) {
					$('#astra-sites-subscription-form-two').html(wp.template('astra-sites-subscription-form-one'));
					$('#astra-sites-subscription-form-two').append(wp.template('astra-sites-subscription-form-two'));
				} else {
					$('#astra-sites-subscription-form-one').html(wp.template('astra-sites-subscription-form-one'));
					$('#astra-sites-subscription-form-two').html(wp.template('astra-sites-subscription-form-two'));
				}
			}

			// Set compatibilities.
			AstraSitesAdmin.skip_and_import_popups = [];
			var compatibilities = astraSitesVars.compatibilities;
			required_plugins = [];
			if( response ) {
				required_plugins = response.data['required_plugins'];
				AstraSitesAdmin.required_plugins = response.data['required_plugins'];

				if (response.data['third_party_required_plugins'].length) {
					AstraSitesAdmin.skip_and_import_popups['astra-sites-third-party-required-plugins'] = response.data['third_party_required_plugins'];
				}
			}

			var is_dynamic_page = $('#single-pages').find('.current_page').attr('data-dynamic-page') || 'no';

			if (('yes' === is_dynamic_page) && 'site-pages' === AstraSitesAdmin.action_slug) {
				AstraSitesAdmin.skip_and_import_popups['astra-sites-dynamic-page'] = '';
			}

			// Release disabled class from import button.
			$('.astra-demo-import')
				.removeClass('disabled not-click-able')
				.attr('data-import', 'disabled');

			// Remove loader.
			$('.required-plugins').removeClass('loading').html('');
			$('.required-plugins-list').html('');

			var output = '';

			/**
			 * Count remaining plugins.
			 * @type number
			 */
			var remaining_plugins = 0;
			var required_plugins_markup = '';

			/**
			 * Not Installed
			 *
			 * List of not installed required plugins.
			 */
			if ( required_plugins && typeof required_plugins.notinstalled !== 'undefined') {

				// Add not have installed plugins count.
				remaining_plugins += parseInt(required_plugins.notinstalled.length);

				$(required_plugins.notinstalled).each(function (index, plugin) {
					output += '<li class="plugin-card plugin-card-' + plugin.slug + '" data-slug="' + plugin.slug + '" data-init="' + plugin.init + '" data-name="' + plugin.name + '">' + plugin.name + '</li>';
				});
			}

			/**
			 * Inactive
			 *
			 * List of not inactive required plugins.
			 */
			if ( required_plugins && typeof required_plugins.inactive !== 'undefined') {

				// Add inactive plugins count.
				remaining_plugins += parseInt(required_plugins.inactive.length);

				$(required_plugins.inactive).each(function (index, plugin) {
					output += '<li class="plugin-card plugin-card-' + plugin.slug + '" data-slug="' + plugin.slug + '" data-init="' + plugin.init + '" data-name="' + plugin.name + '">' + plugin.name + '</li>';
				});
			}

			if ('' == output) {
				$('.astra-sites-result-preview').find('.astra-sites-import-plugins').hide();
			} else {
				$('.astra-sites-result-preview').find('.astra-sites-import-plugins').show();
				$('.astra-sites-result-preview').find('.required-plugins-list').html(output);
			}
			if ('yes' === AstraSitesAdmin.first_import_complete && !$('.astra-sites-result-preview').hasClass('import-page')) {
				$('.astra-sites-advanced-options').find('.astra-site-contents').prepend(wp.template('astra-sites-delete-previous-site'));
			}

			/**
			 * Enable Demo Import Button
			 * @type number
			 */
			astraSitesVars.requiredPlugins = required_plugins;

			$('.astra-sites-import-content').find('.astra-loading-wrap').remove();
			$('.astra-sites-result-preview').removeClass('preparing');

			// Compatibility.
			if (Object.keys(compatibilities.errors).length || Object.keys(compatibilities.warnings).length || Object.keys(AstraSitesAdmin.skip_and_import_popups).length) {

				if (Object.keys(compatibilities.errors).length || Object.keys(compatibilities.warnings).length) {
					AstraSitesAdmin.skip_and_import_popups['astra-sites-compatibility-messages'] = compatibilities;
				}

				if (Object.keys(AstraSitesAdmin.skip_and_import_popups).length) {
					AstraSitesAdmin.add_skip_and_import_popups(AstraSitesAdmin.skip_and_import_popups);
				}

			} else {

				// Avoid plugin activation, for pages only.
				if ('site-pages' === AstraSitesAdmin.action_slug) {

					var notinstalled = [];
					if( astraSitesVars && astraSitesVars.requiredPlugins && astraSitesVars.requiredPlugins.notinstalled ) {
						notinstalled = astraSitesVars.requiredPlugins.notinstalled;
					}

					if (!notinstalled.length) {
						AstraSitesAdmin.import_page_process();
					}
				}
			}
		},

		required_plugins_list_markup: function (requiredPlugins) {

			// Add disabled class from import button.
			$('.astra-demo-import')
				.addClass('disabled not-click-able')
				.removeAttr('data-import');

			if( '' === requiredPlugins ) {
				AstraSitesAdmin.start_import();
			} else {

				$('.required-plugins').addClass('loading').html('<span class="spinner is-active"></span>');

				// Required Required.
				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						action: 'astra-required-plugins',
						_ajax_nonce: astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.groupCollapsed('Required Plugins');
						console.log('Required Plugins of Template:');
						console.log(requiredPlugins);
					}
				})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);

					// Remove loader.
					$('.required-plugins').removeClass('loading').html('');
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Required Plugins Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (response) {
					console.log('Required Plugin Status From The Site:');
					AstraSitesAdmin._log(response);
					console.groupEnd();

					if (false === response.success) {
						AstraSitesAdmin._importFailMessage(response.data, 'Required Plugins Failed!', '', astraSitesVars.importFailedRequiredPluginsMessage);
					} else {
						AstraSitesAdmin.start_import( response );
					}
				});
			}
		},

		import_page_process: function () {

			if ($('.astra-sites-page-import-popup .site-install-site-button, .preview-page-from-search-result .site-install-site-button').hasClass('updating-message')) {
				return;
			}

			if (false === AstraSitesAdmin.subscribe_skiped && $('.subscription-enabled').length && AstraSitesAdmin.subscription_form_submitted !== 'yes') {
				$('.subscription-popup').show();
				$('.astra-sites-result-preview .default').hide();
			} else {
				AstraSitesAdmin.subscribe_status = true;
			}

			$('.astra-sites-page-import-popup .site-install-site-button, .preview-page-from-search-result .site-install-site-button').addClass('updating-message installing').text('Importing..');

			AstraSitesAdmin.import_start_time = new Date();

			$('.astra-sites-result-preview .inner > h3').text('We\'re importing your website.');
			$('.install-theme-info').hide();
			$('.ast-importing-wrap').show();
			var output = '<div class="current-importing-status-title"></div><div class="current-importing-status-description"></div>';
			$('.current-importing-status').html(output);

			// Process Bulk Plugin Install & Activate.
			AstraSitesAdmin._bulkPluginInstallActivate();
		},

		_installAstra: function (status) {

			var theme_slug = 'astra';

			AstraSitesAdmin._log_title(astraSitesVars.log.themeInstall);
			AstraSitesAdmin._log(astraSitesVars.log.themeInstall);

			if (status == 'not-installed') {
				if (wp.updates.shouldRequestFilesystemCredentials && !wp.updates.ajaxLocked) {
					wp.updates.requestFilesystemCredentials();
				}
				wp.updates.installTheme({
					slug: theme_slug
				});

			} else if (status == 'installed-but-inactive') {
				AstraSitesAdmin._activateTheme();
			}

		},

		_activateTheme: function (event, response) {

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout(function () {

				$.ajax({
					url: astraSitesVars.ajaxurl,
					type: 'POST',
					data: {
						'action': 'astra-sites-activate-theme',
						'_ajax_nonce': astraSitesVars._ajax_nonce,
					},
					beforeSend: function () {
						console.log('Activating Astra Theme..');
					}
				})
					.done(function (result) {
						AstraSitesAdmin._log(result);
						if (result.success) {
							AstraSitesAdmin._log_title(result.data.message);
							AstraSitesAdmin._log(result.data.message);
						}
					});

			}, 3000);
		},

		_close_popup_by_overlay: function (event) {
			if (this === event.target) {
				// Import process is started?
				// And Closing the window? Then showing the warning confirm message.
				if ($('body').hasClass('importing-site') && !confirm(astraSitesVars.strings.warningBeforeCloseWindow)) {
					return;
				}

				$('body').removeClass('importing-site');
				$('html').removeClass('astra-site-preview-on');

				AstraSitesAdmin._close_popup();
				AstraSitesAdmin.hide_popup();
			}
		},

		/**
		 * Close Popup
		 *
		 * @since 1.0
		 * @access private
		 * @method _importDemo
		 */
		_close_popup: function () {
			AstraSitesAdmin._clean_url_params('astra-site');
			AstraSitesAdmin._clean_url_params('astra-page');
			AstraSitesAdmin._clean_url_params('license');
			$('.astra-sites-result-preview').html('').hide();

			AstraSitesAdmin.hide_popup();
		},

		_page_api_call: function () {

			// Have any skip and import popup in queue then return.
			if (Object.keys(AstraSitesAdmin.skip_and_import_popups).length) {
				return;
			}

			// Has API data of pages.
			if (null == AstraSitesAdmin.templateData) {
				return;
			}

			AstraSitesAdmin.import_wpform(AstraSitesAdmin.templateData['astra-site-wpforms-path'], function (form_response) {

				$('body').addClass('importing-site');

				// Import Page Content
				$('.current-importing-status-wrap').remove();
				$('.astra-sites-result-preview .inner > h3').text('We are importing page!');

				fetch(AstraSitesAdmin.templateData['astra-page-api-url'] + '?&site_url=' + astraSitesVars.siteURL).then(response => {
					return response.json();
				}).then(data => {

					// Import Brizy images.
					if (Object.keys(data.brizy_media).length) {
						for (media_key in data.brizy_media) {
							AstraSitesAjaxQueue.add({
								url: astraSitesVars.ajaxurl,
								type: 'POST',
								data: {
									action: 'astra-sites-import-media',
									media: data.brizy_media[media_key],
									_ajax_nonce: astraSitesVars._ajax_nonce,
								},
								success: function (result) {
									AstraSitesAdmin._log(result);
								}
							});
						}

						AstraSitesAjaxQueue.run();
					}

					// Import Single Page.
					$.ajax({
						url: astraSitesVars.ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							'action': 'astra-sites-create-page',
							'_ajax_nonce': astraSitesVars._ajax_nonce,
							'page_settings_flag': AstraSitesAdmin.page_settings_flag,
							'data': data,
						},
						success: function (response) {
							if (response.success) {
								AstraSitesAdmin.page_import_status = true;
								AstraSitesAdmin.imported_page_data = response.data
								AstraSitesAdmin.page_import_complete();
							} else {
								AstraSitesAdmin._importFailMessage(response.data, 'Page Rest API Request Failed!');
							}
						}
					});

				}).catch(err => {
					AstraSitesAdmin._log(err);
					AstraSitesAdmin._importFailMessage(response.data, 'Page Rest API Request Failed!');
				});
			});
		},

		import_wpform: function (wpforms_url, callback) {

			if ('' == wpforms_url) {
				if (callback && typeof callback == "function") {
					callback('');
				}
				return;
			}

			$.ajax({
				url: astraSitesVars.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'astra-sites-import-wpforms',
					wpforms_url: wpforms_url,
					_ajax_nonce: astraSitesVars._ajax_nonce,
				},
				beforeSend: function () {
					console.groupCollapsed('Importing WP Forms');
					AstraSitesAdmin._log_title('Importing WP Forms..');
				},
			})
				.fail(function (jqXHR) {
					AstraSitesAdmin._log(jqXHR);
					AstraSitesAdmin._importFailMessage(jqXHR.status + jqXHR.statusText, 'Import WP Forms Failed!', jqXHR);
					console.groupEnd();
				})
				.done(function (response) {
					AstraSitesAdmin._log(response);
					console.groupEnd();

					// 1. Fail - Import WPForms Options.
					if (false === response.success) {
						AstraSitesAdmin._importFailMessage(response.data, 'Import WP Forms Failed!');
					} else {
						if (callback && typeof callback == "function") {
							callback(response);
						}
					}
				});
		},

		process_site_data: function (data) {

			if ('log_file' in data) {
				AstraSitesAdmin.log_file_url = decodeURIComponent(data.log_file) || '';
			}

			// 1. Pass - Request Site Import
			AstraSitesAdmin.customizer_data = JSON.stringify(data['astra-site-customizer-data']) || '';
			AstraSitesAdmin.wxr_url = encodeURI(data['astra-site-wxr-path']) || '';
			AstraSitesAdmin.wpforms_url = encodeURI(data['astra-site-wpforms-path']) || '';
			AstraSitesAdmin.cartflows_url = encodeURI(data['astra-site-cartflows-path']) || '';
			AstraSitesAdmin.options_data = JSON.stringify(data['astra-site-options-data']) || '';
			AstraSitesAdmin.enabled_extensions = JSON.stringify(data['astra-enabled-extensions']) || '';
			AstraSitesAdmin.widgets_data = data['astra-site-widgets-data'] || '';

			// Elementor Template Kit Markup.
			AstraSitesAdmin.template_kit_markup(data);

			// Required Plugins.
			AstraSitesAdmin.required_plugins_list_markup(data['required-plugins']);
		},

		template_kit_markup: function (data) {
			if ('elementor' != astraSitesVars.default_page_builder) {
				return;
			}
		},

		/**
		 * Enable Demo Import Button.
		 */
		_enable_demo_import_button: function (type) {

			type = (undefined !== type) ? type : 'free';

			$('.install-theme-info .theme-details .site-description').remove();

			switch (type) {

				case 'free':

					var notinstalled = [];
					var inactive = [];
					if( astraSitesVars.requiredPlugins ) {
						notinstalled = astraSitesVars.requiredPlugins.notinstalled || [];
						inactive = astraSitesVars.requiredPlugins.inactive || [];
					}
					if ($('.astra-sites-result-preview').hasClass('skip-plugins')) {
						notinstalled = [];
					}

					if (notinstalled.length === inactive.length) {
						$(document).trigger('astra-sites-after-' + AstraSitesAdmin.action_slug + '-required-plugins');
					}
					break;

				case 'upgrade':
					var demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

					$('.astra-demo-import')
						.addClass('go-pro button-primary')
						.removeClass('astra-demo-import')
						.attr('target', '_blank')
						.attr('href', astraSitesVars.getUpgradeURL + demo_slug)
						.text(astraSitesVars.getUpgradeText)
						.append('<i class="dashicons dashicons-external"></i>');
					break;

				default:
					var demo_slug = $('.wp-full-overlay-header').attr('data-demo-slug');

					$('.astra-demo-import')
						.addClass('go-pro button-primary')
						.removeClass('astra-demo-import')
						.attr('target', '_blank')
						.attr('href', astraSitesVars.getProURL)
						.text(astraSitesVars.getProText)
						.append('<i class="dashicons dashicons-external"></i>');

					$('.wp-full-overlay-header').find('.go-pro').remove();

					if (false == astraSitesVars.isWhiteLabeled) {
						if (astraSitesVars.isPro) {
							$('.install-theme-info .theme-details').prepend(wp.template('astra-sites-pro-inactive-site-description'));
						} else {
							$('.install-theme-info .theme-details').prepend(wp.template('astra-sites-pro-site-description'));
						}
					}

					break;
			}

		},

		/**
		 * Update Page Count.
		 */

		/**
		 * Remove plugin from the queue.
		 */
		_removePluginFromQueue: function (removeItem, pluginsList) {
			return jQuery.grep(pluginsList, function (value) {
				return value.slug != removeItem;
			});
		}

	};

	/**
	 * Initialize AstraSitesAdmin
	 */
	$(function () {
		AstraSitesAdmin.init();
	});

})(jQuery);
