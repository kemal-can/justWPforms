(function ($, _, Backbone, api, paymentsSettings, settings ) {

	justwpforms.classes.models.parts.payments = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.payments.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.payments = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-payments-template',
		editor: null,

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'change [data-bind=user_price_step]': 'onPaymentStepIntervalChange',
		} ),

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:show_user_price_field', this.onUserPriceFieldChange );

			this.listenTo( this.model, 'change:price_label', this.onPriceLabelChange );
			this.listenTo( this.model, 'change:price', this.onPriceChange );
			this.listenTo( this.model, 'change:user_price_placeholder', this.onUserPricePlaceholderChange );
			this.listenTo( this.model, 'change:currency', this.onCurrencyChange );
		},

		ready: function () {
			justwpforms.classes.views.Part.prototype.ready.apply(this, arguments);
		},

		onUserPriceFieldChange: function( model, value ) {
			var $labelField = $( '[data-trigger=show_user_price_field]', this.$el );

			if ( 1 == value ) {
				$( '.price-field', this.$el ).hide();
				$( '[data-logic-id=price]', this.$el ).hide();

				$labelField.show();
			} else {
				$( '.price-field', this.$el ).show();
				$( '[data-logic-id=price]', this.$el ).show();

				$labelField.hide();
			}

			this.refreshPart();
		},

		onPriceChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onPaymentsPriceChange'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onPriceLabelChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onPaymentsPriceLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onUserPricePlaceholderChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onPaymentsUserPricePlaceholderChange'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onCurrencyChange: function( model, value ) {
			var currencyData = paymentsSettings.currencies[value];
			var symbol = currencyData.symbol;

			var data = {
				id: this.model.get( 'id' ),
				callback: 'onPaymentsCurrencyChange',
				options: {
					symbol: symbol
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onPaymentStepIntervalChange: function( e ) {
			if ( $( e.target ).val() <= 0 ) {
				$('[data-bind=user_price_step]', this.$el).val( 1 );
				this.model.set( 'user_price_step', 1 );
			}

			var data = {
				id: this.model.get( 'id' ),
				callback: 'onPaymentStepIntervalChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onPaymentsPriceLabelChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $label = this.$( '.justwpforms-payments__price .label', $part );

			$label.text( part.get( 'price_label' ) );
		},

		onPaymentsPriceChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $price = this.$( '.justwpforms-payments__price .price strong', $part );
			var $priceInput = this.$( '.justwpforms-payments__price input', $part );

			var language = navigator.language ? navigator.language : 'en-US';
			var formattedPrice = new Intl.NumberFormat(
				language,
				{
					style: 'decimal',
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				}
			).format( part.get( 'price' ) );

			$priceInput.attr( 'data-default', part.get( 'price' ) );
			$price.text( formattedPrice );
		},

		onPaymentsMethodChoiceLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'payment_method_choice_label' );

			$( '.justwpforms-part--payments [data-subpart="payment_method"] > label span.label', $form ).text( label );
		},

		onPaymentsRedirectHintLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'paypal_redirect_hint' );

			$( '.justwpforms-part--payments .justwpforms-payments-service--paypal > .justwpforms-part-wrap p', $form ).text( label );
		},

		onPayPalOptionLabelChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'paypal_option_label' );

			$( '.justwpforms-payments__choice-paypal .option-label span.label ', $form ).text( label );
		},

		onStripeOptionLabelChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'stripe_option_label' );

			$( '.justwpforms-payments__choice-stripe .option-label span.label ', $form ).text( label );
		},

		onUserPriceLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'user_price_label' );

			$( '[data-subpart="user_price"] .justwpforms-part__label span.label ', $form ).text( label );
		},

		cardLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'card_label' );

			$( '.justwpforms-payments__card .justwpforms-stripe-card-label span.label', $form ).text( label );
		},

		cardNumberLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'card_number_label' );

			$( '.justwpforms-payments__card .justwpforms-stripe-card-number-label span.label', $form ).text( label );
		},

		cardExpiryLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'card_expiry_label' );

			$( '.justwpforms-payments__card .justwpforms-stripe-card-expiry-label span.label', $form ).text( label );
		},

		cardCvcLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'card_cvc_label' );

			$( '.justwpforms-payments__card .justwpforms-stripe-card-cvc-label span.label', $form ).text( label );
		},

		onPaymentsUserPricePlaceholderChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $input = this.$( '.justwpforms-payments__user-price input', $part );

			$input.attr( 'placeholder', part.get( 'user_price_placeholder' ) );
		},

		onPaymentsCurrencyChange: function( id, html, options ) {
			var $part = this.getPartElement( html );

			var $currencySymbol = this.$( '.justwpforms-payments__price .price span', $part );

			if ( $currencySymbol.length ) {
				$currencySymbol.html( options.symbol );
			}

			var $currencySymbolUserPrefix = this.$( '.justwpforms-payments__user-price .justwpforms-input-group__prefix span', $part );

			if ( $currencySymbolUserPrefix.length ) {
				$currencySymbolUserPrefix.html( options.symbol );
			}
		},

		onPaymentStepIntervalChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $input = this.$( 'input', $part );

			$input.attr( 'step', part.get( 'user_price_step' ) );
		},
	} );

	var FormBuild = justwpforms.classes.views.FormBuild;

	justwpforms.classes.views.FormBuild = FormBuild.extend( {
		ready: function() {
			FormBuild.prototype.ready.apply( this, arguments );

			var paymentPart = justwpforms.form.get( 'parts' ).findWhere( { type: 'payments' } );

			if ( paymentPart ) {
				this.$el.addClass( 'has-payments-part' );
				this.drawer.$el.addClass( 'has-payments-part' );
			}
		},

		onPartAdd: function( type, options ) {
			if ( 'payments' === type ) {
				var paymentPart = justwpforms.form.get( 'parts' ).findWhere( { type: 'payments' } );

				if ( paymentPart ) {
					return;
				}

				this.$el.addClass( 'has-payments-part' );
				this.drawer.$el.addClass( 'has-payments-part' );
			}

			FormBuild.prototype.onPartAdd.apply( this, arguments );
		},

		onPartModelRemove: function( model ) {
			FormBuild.prototype.onPartModelRemove.apply( this, arguments );

			if ( 'payments' === model.get( 'type' ) ) {
				this.$el.removeClass( 'has-payments-part' );
				this.drawer.$el.removeClass( 'has-payments-part' );
			}
		}
	} );

	var FormMessages = justwpforms.classes.views.FormMessages;

	justwpforms.classes.views.FormMessages = FormMessages.extend ( {

		events: _.extend( {}, FormMessages.prototype.events, {
			'keyup [data-attribute]': 'onInputChange',
			'keyup [data-attribute="payment_method_choice_label"]': 'onPaymentsMethodChoiceLabelChange',
			'keyup [data-attribute="paypal_redirect_hint"]': 'onPaymentsRedirectHintLabelChange',
			'keyup [data-attribute="paypal_option_label"]' : 'onPayPalOptionLabelChange',
			'keyup [data-attribute="stripe_option_label"]' : 'onStripeOptionLabelChange',
			'keyup [data-attribute="user_price_label"]' : 'onUserPriceLabelChange',
			'keyup [data-attribute="card_label"]' : 'cardLabelChange',
			'keyup [data-attribute="card_number_label"]' : 'cardNumberLabelChange',
			'keyup [data-attribute="card_expiry_label"]' : 'cardExpiryLabelChange',
			'keyup [data-attribute="card_cvc_label"]' : 'cardCvcLabelChange',
		} ),

		applyMsgConditionClasses: function() {
			var self = this;

			var hasPayWhatYouWant = justwpforms.form.get( 'parts' ).findWhere( {
				show_user_price_field: 1
			} );

			if ( hasPayWhatYouWant ) {
				self.$el.addClass( 'has-pay-what-you-want' );
			}
			FormMessages.prototype.applyMsgConditionClasses.apply( this, arguments );
		},

		onPaymentsMethodChoiceLabelChange: function( e ) {
			var data = {
				callback: 'onPaymentsMethodChoiceLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onPaymentsRedirectHintLabelChange: function( e ) {
			var data = {
				callback: 'onPaymentsRedirectHintLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onPayPalOptionLabelChange: function( e ) {
			var data = {
				callback: 'onPayPalOptionLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onStripeOptionLabelChange: function( e ) {
			var data = {
				callback: 'onStripeOptionLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onUserPriceLabelChange: function( e ) {
			var data = {
				callback: 'onUserPriceLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		cardLabelChange: function( e ) {
			var data = {
				callback: 'cardLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		cardNumberLabelChange: function( e ) {
			var data = {
				callback: 'cardNumberLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		cardExpiryLabelChange: function( e ) {
			var data = {
				callback: 'cardExpiryLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		cardCvcLabelChange: function( e ) {
			var data = {
				callback: 'cardCvcLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsPaymentsPartSettings, _justwpformsSettings );
