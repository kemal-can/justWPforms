( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.number = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.number.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.number = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-number-template',

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'change [name=masked]': 'onMaskedChange',
			'change [data-bind=step]': 'onNumberStepIntervalChange',
		} ),

		initialize: function () {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:mask_numeric_thousands_delimiter', this.onThousandsDelimiterChange );
			this.listenTo( this.model, 'change:mask_numeric_decimal_mark', this.onDecimalMarkChange );
			this.listenTo( this.model, 'change:mask_numeric_prefix', this.onPrefixChange );
			this.listenTo( this.model, 'change:mask_numeric_suffix', this.onSuffixChange );
			this.listenTo( justwpforms.form, 'save', this.onFormSave );
		},

		/**
		 * Toggle masked input configuration on `Mask this input` checkbox change.
		 *
		 * @since 1.0.0.
		 *
		 * @param {object} e JS event.
		 *
		 * @return void
		 */
		onMaskedChange: function(e) {
			var $input = $( e.target );
			var attribute = $input.data( 'bind' );
			var $maskWrapper = this.$el.find( '.justwpforms-nested-settings[data-trigger="masked"]' );

			if ( $input.is( ':checked' ) ) {
				this.model.set( attribute, 1 );

				// show actual mask input
				$maskWrapper.show();
			} else {
				this.model.set( attribute, 0 );

				// empty mask input and hide
				$maskWrapper.hide();
			}

			var model = this.model;

			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		onNumberStepIntervalChange: function( e ) {
			if ( $( e.target ).val() <= 0 ) {
				$('[data-bind=step]', this.$el).val( 1 );
				this.model.set( 'step', 1 );
			}

			var data = {
				id: this.model.get( 'id' ),
				callback: 'onNumberStepIntervalChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onThousandsDelimiterChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onNumberThousandsDelimiterChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onDecimalMarkChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onNumberDecimalMarkChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onPrefixChange: function( model, value ) {
			var data;

			/**
			 * If prefix is empty or had no value before, trigger part refresh so it hides / shows itself.
			 */
			if ( ! value || ! model.previous( 'mask_numeric_prefix' ) ) {
				this.model.fetchHtml( function( response ) {
					data = {
						id: model.get( 'id' ),
						html: response,
					};

					justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
				} );
			/**
			 * Otherwise, update prefix by part dom update in preview.
			 */
			} else {
				data = {
					id: this.model.get( 'id' ),
					callback: 'onNumberPrefixChangeCallback',
				};

				justwpforms.previewSend( 'justwpforms-part-dom-update', data );
			}
		},

		onSuffixChange: function( model, value ) {
			var data;

			/**
			 * If suffix is empty or had no value before, trigger part refresh so it hides / shows itself.
			 */
			if ( ! value || ! model.previous( 'mask_numeric_suffix' ) ) {
				this.model.fetchHtml( function( response ) {
					data = {
						id: model.get( 'id' ),
						html: response,
					};

					justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
				} );
			/**
			 * Otherwise, update suffix by part dom update in preview.
			 */
			} else {
				data = {
					id: this.model.get( 'id' ),
					callback: 'onNumberSuffixChangeCallback',
				};

				justwpforms.previewSend( 'justwpforms-part-dom-update', data );
			}
		},

		onFormSave: function( form ) {
			var part = _.findWhere( form.parts, {
				id: this.model.get( 'id' )
			} );

			if ( ! part ) {
				return;
			}

			$( '[data-bind="min_value"]', this.$el ).val( part.min_value );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onNumberThousandsDelimiterChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			$part.attr( 'data-thousands-delimiter', part.get( 'mask_numeric_thousands_delimiter' ) );
			$.fn.justwpformPart.call( $part, 'reinit' );
		},

		onNumberDecimalMarkChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			$part.attr( 'data-decimal-mark', part.get( 'mask_numeric_decimal_mark' ) );
			$.fn.justwpformPart.call( $part, 'reinit' );
		},

		onNumberPrefixChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $prefix = this.$( '.justwpforms-input-group__prefix span', $part );

			$prefix.text( part.get( 'mask_numeric_prefix' ) );
		},

		onNumberSuffixChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $suffix = this.$( '.justwpforms-input-group__suffix span', $part );

			$suffix.text( part.get( 'mask_numeric_suffix' ) );
		},

		onNumberStepIntervalChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $input = this.$( 'input', $part );

			$input.attr( 'step', part.get( 'step' ) );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
