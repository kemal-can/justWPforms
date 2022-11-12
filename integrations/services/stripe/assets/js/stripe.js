( function( $, Cookies, settings ) {
	$( function() {
		var justwpformsStripe = function( el ) {
			this.el = el;
			this.$el = $( el );
			this.$form = $( 'form', this.$el );
			this.$paymentsPart = $( '[data-justwpforms-type=payments]', this.$form );

			this.formID = $( '[name=justwpforms_form_id]', this.$form ).val();
			this.stripe = '';
			this.elements = {};
			this.checkoutDataKey = 'justwpforms_' + this.formID + '_stripe_checkout';
			this.isComplete = { cardNumber: false, cardExpiry: false, cardCvc: false };

			if ( ! this.$paymentsPart.is( '[data-justwpforms-has-stripe]' ) ) {
				return;
			}

			justwpforms.scripts.fetch( 'stripe', settings.libraryURL, this.init.bind( this ) );
		}

		justwpformsStripe.prototype.init = function() {
			if ( ! settings.key || ! this.$paymentsPart.length ) {
				return;
			}

			if ( this.$el.attr( 'data-stripe-enabled' ) ) {
				return;
			}

			// init stripe
			this.stripe = Stripe( settings.key, { locale: this.$paymentsPart.data( 'justwpforms-stripe-locale' ) } );

			// set attribute so we know next time that Stripe init already happend here
			this.$el.attr( 'data-stripe-enabled', '' );
			
			$( '.credit-card-filled', this.$paymentsPart ).val( '' );

			this.createCheckoutData();
			this.initStripeElements();

			this.$form.on( 'justwpforms.submitted', this.handlePayment.bind( this ) );
			this.$paymentsPart.on( 'justwpforms.cssvar', this.updatePlaceholderColor.bind( this ) );
		},

		justwpformsStripe.prototype.destroy = function() {
			this.$form.off( 'justwpforms.submitted' );
			this.$paymentsPart.off( 'justwpforms.cssvar' );
			this.$el.data( 'justwpformsStripe', false );
		},

		justwpformsStripe.prototype.updatePlaceholderColor = function( event, data ) {
			if ( ! data ) {
				return;
			}

			if ( '--justwpforms-color-part-placeholder' === data.name ) {
				var args = {
					'placeholder': data.value
				};

				this.initStripeElements( args );
			}
		},

		/**
		 * Initialize Stripe Card Element and let it inherit form styles.
		 *
		 * @param {array} options Extra options.
		 */
		justwpformsStripe.prototype.initStripeElements = function( options ) {
			var fontSources = [];

			for ( var styleSheet of document.styleSheets ) {
				try {
					if ( ! styleSheet.cssRules ) {
						continue;
					}

					for ( var cssRule of styleSheet.cssRules ) {
						if ( ! cssRule.cssText.startsWith('@font-face' ) ) {
							continue;
						}

						fontSources.push( {
							family: cssRule.style.fontFamily,
							src: cssRule.style.src,
							display: 'auto',
							style: cssRule.style.fontStyle,
							unicodeRange: cssRule.style.unicodeRange,
							weight: cssRule.style.fontWeight,
						} );
					}
				} catch ( e ) {
					console.log( e );
				}
			}
			var elements = this.stripe.elements( {
				fonts: fontSources,
			} );

			var formComputedStyle = window.getComputedStyle( this.el );
			var partComputedStyle = window.getComputedStyle( this.$paymentsPart[0] );

			var placeholderColor = formComputedStyle.getPropertyValue( '--justwpforms-color-part-placeholder' );

			if ( options && options.placeholder ) {
				placeholderColor = options.placeholder;
			}
			var fontSize = formComputedStyle.getPropertyValue( '--justwpforms-part-value-font-size' ).trim();
			var fontFamily = partComputedStyle.getPropertyValue( 'font-family' );
			var fontColor = formComputedStyle.getPropertyValue( '--justwpforms-color-part-value' );
			var textAlign = 'left';

			if ( this.$el.hasClass( 'justwpforms-form--part-value-text-align-center' ) ) {
				textAlign = 'center';
			}

			if ( this.$el.hasClass( 'justwpforms-form--part-value-text-align-right' ) ) {
				textAlign = 'right';
			}

			var elementsStyle = {
				base: {
					fontSize: fontSize,
					fontFamily: fontFamily,
					color: fontColor,
					'::placeholder': {
						color: placeholderColor
					},
					textAlign: textAlign,
				},
				invalid: {
					color: fontColor
				}
			};

			var paymentsPartID = this.$paymentsPart.data( 'justwpforms-id' );
			var partID = 'justwpforms-' + this.formID + '_' + paymentsPartID;

			this.elements['card'] = elements.create( 'cardNumber', {
				hidePostalCode: ( 1 == settings.hidePostalCode ) ? true : false,
				style: elementsStyle,
				placeholder: '',
			} );
			this.elements['card'].mount( '#' + partID + '_stripe_card' );
			this.elements['card'].on( 'change', this.onElementChange.bind( this ) );

			this.elements['cardExpiry'] = elements.create( 'cardExpiry', {
				style: elementsStyle,
				placeholder: '',
			} );
			this.elements['cardExpiry'].mount( '#' + partID + '_stripe_card_expiry' );
			this.elements['cardExpiry'].on( 'change', this.onElementChange.bind( this ) );

			this.elements['cardCvc'] = elements.create( 'cardCvc', {
				style: elementsStyle,
				placeholder: '',
			} );
			this.elements['cardCvc'].mount( '#' + partID + '_stripe_card_cvc' );
			this.elements['cardCvc'].on( 'change', this.onElementChange.bind( this ) );
		},

		/**
		 * Set error state on Stripe element so it has a look and feel of our proprietary messages.
		 *
		 * @param {string} message Message as returned by Stripe API.
		 */
		justwpformsStripe.prototype.setError = function( message ) {
			$error = $( '.justwpforms-part-error-notice__realtime', this.$paymentsPart );
			$( '.credit-card-filled', this.$paymentsPart ).val( 0 );

			$( 'span', $error ).text( message );
			$error.show();
		},

		/**
		 * Hides error from Stripe element.
		 */
		justwpformsStripe.prototype.hideError = function() {
			$error = $( '.justwpforms-part-error-notice__realtime', this.$paymentsPart );
			$( 'span', $error ).text( '' );
			$error.hide();
		},

		justwpformsStripe.prototype.onElementChange = function( e ) {
			this.hideError();

			if ( e.complete ) {
				this.isComplete[ e.elementType ] = true;
			}

			if ( this.isComplete.cardNumber && this.isComplete.cardExpiry && this.isComplete.cardCvc ) {
				this.setPaymentMethod();
				return;
			}

			if ( ! e.error || e.empty ) {
				return;
			}

			// this.setError( e.error.message );
		},

		/**
		 * Creates a cookie with payment method ID for future reference.
		 */
		justwpformsStripe.prototype.createCheckoutData = function() {
			var data = {
				payment_method: '',
			};

			if ( ! Cookies.get( this.checkoutDataKey ) ) {
				Cookies.set( this.checkoutDataKey, data );
			}
		},

		/**
		 * Update checkout data cookie with new data.
		 *
		 * @param {string} key Key of the data to be updated.
		 * @param {string} value New value.
		 */
		justwpformsStripe.prototype.updateCheckoutData = function( key, value ) {
			if ( ! Cookies.get( this.checkoutDataKey ) ) {
				this.createCheckoutData();
			}

			var checkoutData = JSON.parse( Cookies.get( this.checkoutDataKey ) );

			checkoutData[key] = value;

			Cookies.set( this.checkoutDataKey, checkoutData );
		},

		/**
		 * Gets checkout data as a JSON. If no checkout data are present, it creates one.
		 */
		justwpformsStripe.prototype.getCheckoutData = function() {
			if ( ! Cookies.get( this.checkoutDataKey ) ) {
				this.createCheckoutData();
			}

			var data = JSON.parse( Cookies.get( this.checkoutDataKey ) );

			return data;
		},

		/**
		 * Remove checkout data cookie. This is called when payment is complete.
		 */
		justwpformsStripe.prototype.clearCheckoutData = function() {
			Cookies.remove( this.checkoutDataKey );
		},

		/**
		 * Sets payment method (ID) to checkout data and changes value of credit-card-filled element to `1`.
		 */
		justwpformsStripe.prototype.setPaymentMethod = async function() {
			var paymentMethod = await this.createPaymentMethod();
			var $submitButton = $( '.justwpforms-button--submit', this.$form );

			$submitButton.attr( 'disabled', 'disabled' );

			if ( ! paymentMethod ) {
				return;
			}

			$( '.credit-card-filled', this.$paymentsPart ).val( 1 );
			$submitButton.prop( 'disabled', false );

			this.updateCheckoutData( 'payment_method', paymentMethod.id );
		},

		/**
		 * Handles payment on form submit.
		 *
		 * @param {object} e Event.
		 * @param {json} response Form response data.
		 */
		justwpformsStripe.prototype.handlePayment = async function( e, response ) {
			if ( ! response.success ) {
				return;
			}

			if ( ! response.data.stripe || ! response.data.stripe.intent ) {
				return;
			}

			var self = this;
			var paymentSuccess = false;
			var checkoutData = this.getCheckoutData();

			if ( ! checkoutData.payment_method ) {
				return;
			}

			// Call Stripe API to handle card payment using payment intent's secret and payment method ID.
			await this.stripe.handleCardPayment(
				response.data.stripe.intent.secret,
				{
					payment_method: checkoutData.payment_method
				}
			).then( function( result ) {
				if ( result.paymentIntent && 'succeeded' === result.paymentIntent.status ) {
					paymentSuccess = true;
				}

				// If payment succeeded in Stripe API, call backend to handle post-payment procedures.
				$.ajax( {
					url: settings.ajaxurl,
					data: {
						action: 'justwpforms_stripe_authorize_payment',
						form_id: self.formID,
						response_id: response.data.stripe.response_id,
						intent_id: response.data.stripe.intent.id,
						nonce: settings.nonce,
						success: paymentSuccess
					}
				} ).done( self.onPaymentComplete.bind( self ) );
			} );
		},

		/**
		 * Clean up, set success/error message to form and re-initiate on successful payment.
		 *
		 * @param {json} response Form response data.
		 */
		justwpformsStripe.prototype.onPaymentComplete = function( response ) {
			if ( ! response.data ) {
				return;
			}

			var self = this;

			setTimeout( function() {
				self.clearCheckoutData();
			}, 500 );

			var $loadingNotice = $( '.justwpforms-stripe-authorization-notices' );
			$loadingNotice.replaceWith( response.data );
			this.$el.justwpformsStripe( 'destroy' );
			this.$el.justwpformsStripe();
		},

		justwpformsStripe.prototype.createPaymentMethod = async function() {
			if ( ! this.elements.card ) {
				return;
			}

			var paymentMethod = '';

			await this.stripe.createPaymentMethod( 'card', this.elements.card ).then( function( result ) {
				paymentMethod = result.paymentMethod;
			} );

			return paymentMethod;
		}

		$.fn.justwpformsStripe = function( method ) {
			if ( 'string' === typeof method ) {
				var instance = $( this ).data( 'justwpformsStripe' );

				if ( instance && instance[method] ) {
					return instance[method].apply( instance, Array.prototype.slice.call( arguments, 1 ) );
				}
			} else {
				this.each( function() {
					$.data( this, 'justwpformsStripe', new justwpformsStripe( this ) );
				} );
			}
		}

		$( document ).on( 'justwpforms-init', '.justwpforms-form', function( e ) {
			$( this ).justwpformsStripe();
		} );
	} );
} )( jQuery, Cookies, _justwpformsSettings.stripe );
