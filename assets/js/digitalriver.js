/* global wc_digitalriver_params */

jQuery(function($) {
	'use strict';

	try {
		var drjs = new DigitalRiver(wc_digitalriver_params.key);
	} catch (error) {
		console.log(error);
		return;
	}

	var drjs_card;
	var drjs_exp;
	var drjs_cvc;

	/**
	 * Object to handle Digital River elements payment form.
	 */
	var wc_digitalriver_form = {
		/**
		 * Get WC AJAX endpoint URL.
		 *
		 * @param  {String} endpoint Endpoint.
		 * @return {String}
		 */
		getAjaxURL: function(endpoint) {
			return wc_digitalriver_params.ajaxurl.toString().replace('%%endpoint%%', 'wc_digitalriver_' + endpoint);
		},

		/**
		 * Unmounts all Stripe elements when the checkout page is being updated.
		 */
		unmountElements: function() {
			drjs_card.unmount('drjs-card-element');
			drjs_exp.unmount('drjs-exp-element');
			drjs_cvc.unmount('drjs-cvc-element');
		},

		/**
		 * Mounts all elements to their DOM nodes on initial loads and updates.
		 */
		mountElements: function() {
			if (!$('#drjs-card-element').length) {
				return;
			}

			drjs_card.mount('drjs-card-element');
			drjs_exp.mount('drjs-exp-element');
			drjs_cvc.mount('drjs-cvc-element');
		},

		/**
		 * Creates all Stripe elements that will be used to enter cards or IBANs.
		 */
		createElements: function() {
			var options = {
        style: {
          base: {
            fontFamily: 'Arial, Helvetica, sans-serif',
						fontSize: '14px'
          }
        }
      }

			drjs_card = drjs.createElement('cardnumber', Object.assign({}, options, {placeholderText: '' }));
			drjs_exp = drjs.createElement('cardexpiration', Object.assign({}, options, {placeholderText: 'MM/YY'}));
			drjs_cvc = drjs.createElement('cardcvv', Object.assign({}, options, {placeholderText: ''}));

			drjs_card.on('change', function(event) {
				wc_digitalriver_form.onCCFormChange();
				wc_digitalriver_form.updateCardBrand(event.brand);

				if (event.error) {
					$(document.body).trigger('digitalriverError', event);
				}
			});

			drjs_exp.on('change', function(event) {
				wc_digitalriver_form.onCCFormChange();

				if (event.error) {
					$(document.body).trigger('digitalriverError', event);
				}
			});

			drjs_cvc.on('change', function(event) {
				wc_digitalriver_form.onCCFormChange();

				if (event.error) {
					$(document.body).trigger('digitalriverError', event);
				}
			});

			/**
			 * Only in checkout page we need to delay the mounting of the
			 * card as some AJAX process needs to happen before we do.
			 */
			if ('yes' === wc_digitalriver_params.is_checkout) {
				$(document.body).on('updated_checkout', function() {
					// Don't re-mount if already mounted in DOM.
					if ($('#drjs-card-element').children().length) {
						return;
					}

					// Unmount prior to re-mounting.
					if (drjs_card) {
						//wc_digitalriver_form.unmountElements();
					}

					wc_digitalriver_form.mountElements();
				});
			} else if ($('form#add_payment_method').length || $('form#order_review').length) {
				wc_digitalriver_form.mountElements();
			}
		},

		/**
		 * Updates the card brand logo with non-inline CC forms.
		 *
		 * @param {string} brand The identifier of the chosen brand.
		 */
		updateCardBrand: function(brand) {
			var brandClass = {
				'visa': 'drjs-visa-brand',
				'mastercard': 'drjs-mastercard-brand',
				'amex': 'drjs-amex-brand',
				'discover': 'drjs-discover-brand',
				'diners': 'drjs-diners-brand',
				'jcb': 'drjs-jcb-brand',
				'unknown': 'drjs-credit-card-brand'
			};

			var imageElement = $('.drjs-card-brand'),
				imageClass = 'drjs-credit-card-brand';

			if (brand in brandClass) {
				imageClass = brandClass[brand];
			}

			// Remove existing card brand class.
			$.each(brandClass, function(index, el) {
				imageElement.removeClass(el);
			});

			imageElement.addClass(imageClass);
		},

		/**
		 * Initialize event handlers and UI state.
		 */
		init: function() {
			// Initialize tokenization script if on change payment method page and pay for order page.
			if ('yes' === wc_digitalriver_params.is_change_payment_page || 'yes' === wc_digitalriver_params.is_pay_for_order_page) {
				$(document.body).trigger('wc-credit-card-form-init');
			}

			// checkout page
			if ($('form.woocommerce-checkout').length) {
				this.form = $('form.woocommerce-checkout');
			}

			$('form.woocommerce-checkout').on('checkout_place_order_digitalriver', this.onSubmit);

			// pay order page
			if ($('form#order_review').length) {
				this.form = $('form#order_review');
			}

			$('form#order_review, form#add_payment_method').on('submit', this.onSubmit);

			// add payment method page
			if ($('form#add_payment_method').length) {
				this.form = $('form#add_payment_method');
			}

			$('form.woocommerce-checkout').on('change', this.reset);

			$(document).on('digitalriverError', this.onError).on('checkout_error',	this.reset);

			wc_digitalriver_form.createElements();
		},

		/**
		 * Check to see if Digital River in general is being used for checkout.
		 *
		 * @return {boolean}
		 */
		isDigitalRiverChosen: function() {
			return $('#payment_method_digitalriver').is(':checked') || ($('#payment_method_digitalriver').is(':checked') && 'new' === $('input[name="wc-digitalriver-payment-token"]:checked').val());
		},

		/**
		 * Currently only support saved cards via credit cards and SEPA. No other payment method.
		 *
		 * @return {boolean}
		 */
		isDigitalRiverSaveCardChosen: function() {
			return (
				$('#payment_method_digitalriver').is(':checked')
				&& $('input[name="wc-digitalriver-payment-token"]').is(':checked')
				&& 'new' !== $('input[name="wc-digitalriver-payment-token"]:checked').val()
			);
		},

		/**
		 * Check if Digital River credit card is being used used.
		 *
		 * @return {boolean}
		 */
		isDigitalRiverCardChosen: function() {
			return $('#payment_method_digitalriver').is(':checked');
		},

		/**
		 * Checks if a source ID is present as a hidden input.
		 * Only used when SEPA Direct Debit is chosen.
		 *
		 * @return {boolean}
		 */
		hasSource: function() {
			return 0 < $('input.drjs-source').length;
		},

		/**
		 * Check whether a mobile device is being used.
		 *
		 * @return {boolean}
		 */
		isMobile: function() {
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent)) {
				return true;
			}

			return false;
		},

		/**
		 * Blocks payment forms with an overlay while being submitted.
		 */
		block: function() {
			if (!wc_digitalriver_form.isMobile()) {
				wc_digitalriver_form.form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			}
		},

		/**
		 * Removes overlays from payment forms.
		 */
		unblock: function() {
			wc_digitalriver_form.form && wc_digitalriver_form.form.unblock();
		},

		/**
		 * Returns the selected payment method HTML element.
		 *
		 * @return {HTMLElement}
		 */
		getSelectedPaymentElement: function() {
			return $('.payment_methods input[name="payment_method"]:checked');
		},

		/**
		 * Retrieves "owner" data from either the billing fields in a form or preset settings.
		 *
		 * @return {Object}
		 */
		getOwnerDetails: function() {
			var first_name = $('#billing_first_name').length ? $('#billing_first_name').val() : wc_digitalriver_params.billing_first_name;
			var last_name = $('#billing_last_name').length ? $('#billing_last_name').val() : wc_digitalriver_params.billing_last_name;
			var owner = {name: '', address: {}, email: '', phone: ''};

			owner.name = first_name;

			if (first_name && last_name) {
				owner.name = first_name + ' ' + last_name;
			} else {
				owner.name = $('#digitalriver-payment-data').data('full-name');
			}

			owner.email = $('#billing_email').val();
			owner.phone = $('#billing_phone').val();

			if (typeof owner.phone === 'undefined' || 0 >= owner.phone.length) {
				delete owner.phone;
			}

			if (typeof owner.email === 'undefined' || 0 >= owner.email.length) {
				if ($('#digitalriver-payment-data').data('email').length) {
					owner.email = $('#digitalriver-payment-data').data('email');
				} else {
					delete owner.email;
				}
			}

			if (typeof owner.name === 'undefined' || 0 >= owner.name.length) {
				delete owner.name;
			}

			owner.address.line1 = $('#billing_address_1').val() || wc_digitalriver_params.billing_address_1;
			owner.address.line2 = $('#billing_address_2').val() || wc_digitalriver_params.billing_address_2;
			owner.address.state = $('#billing_state').val()     || wc_digitalriver_params.billing_state;
			owner.address.city = $('#billing_city').val()      || wc_digitalriver_params.billing_city;
			owner.address.postal_code = $('#billing_postcode').val()  || wc_digitalriver_params.billing_postcode;
			owner.address.country = $('#billing_country').val()   || wc_digitalriver_params.billing_country;

			return {
				owner: owner,
			};
		},

		/**
		 * Initiates the creation of a Source object.
		 */
		createSource: function() {

		},

		/**
		 * Handles responses, based on source object.
		 *
		 * @param {Object} response The `stripe.createSource` response.
		 */
		sourceResponse: function( response ) {
			wc_digitalriver_form.reset();
			wc_digitalriver_form.form.append($('<input type="hidden" />').addClass('drjs-source').attr('name', 'drjs_source').val(response.source.id));

			if ($('form#add_payment_method').length) {
				$(wc_digitalriver_form.form).off('submit', wc_digitalriver_form.form.onSubmit);
			}

			wc_digitalriver_form.form.submit();
		},

		/**
		 * Performs payment-related actions when a checkout/payment form is being submitted.
		 *
		 * @return {boolean} An indicator whether the submission should proceed.
		 *                   WooCommerce's checkout.js stops only on `false`, so this needs to be explicit.
		 */
		onSubmit: function() {
			if (!wc_digitalriver_form.isDigitalRiverChosen()) {
				return true;
			}

			// If a source is already in place, submit the form as usual.
			if (wc_digitalriver_form.isStripeSaveCardChosen() || wc_digitalriver_form.hasSource()) {
				return true;
			}

			wc_digitalriver_form.block();
			wc_digitalriver_form.createSource();

			return false;
		},

		/**
		 * If a new credit card is entered, reset sources.
		 */
		onCCFormChange: function() {
			wc_digitalriver_form.reset();
		},

		/**
		 * Displays stripe-related errors.
		 *
		 * @param {Event}  e      The jQuery event.
		 * @param {Object} result The result of Stripe call.
		 */
		onError: function( e, result ) {

		},

		/**
		 * Removes all Stripe errors and hidden fields with IDs from the form.
		 */
		reset: function() {
			$('.wc-digitalriver-error .drjs-source').remove();
		},
	};

	wc_digitalriver_form.init();
});