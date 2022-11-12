( function ( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.phone = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.phone.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.phone = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-phone-template',

		events: _.extend({}, justwpforms.classes.views.Part.prototype.events, {
			'change [data-bind=masked]': 'onMaskedChange',
		}),

		/**
		 * Toggle masked input configuration on `Mask this input` checkbox change.
		 *
		 * @since 1.0.0.
		 *
		 * @param {object} e JS event.
		 *
		 * @return void
		 */
		onMaskedChange: function (e) {
			var $input = $(e.target);
			var attribute = $input.data('bind');
			var model = this.model;

			this.model.set( attribute, parseInt( $input.val() ) );

			this.model.fetchHtml( function ( response ) {
				var data = {
					id: model.get('id'),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		onDefaultValueChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onPhoneDefaultValueChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onPlaceholderChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onPhonePlaceholderChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onPhoneDefaultValueChangeCallback: function( id, $part ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var default_value = part.get( 'default_value' );

			$part.find( '.justwpforms-part-phone-wrap > .justwpforms-input input' ).val( default_value );
		},

		onPhonePlaceholderChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );

			$part.find( '.justwpforms-part-phone-wrap > .justwpforms-input input' ).attr( 'placeholder', part.get( 'placeholder' ) );
		},
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
