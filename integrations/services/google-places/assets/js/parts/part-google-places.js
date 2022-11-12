( function( $, _, Backbone, api, settings ) {

	var AddressView = justwpforms.classes.views.parts.address;

	justwpforms.classes.views.parts.address = AddressView.extend( {
		events: _.extend( {}, AddressView.prototype.events, {} ),

		initialize: function() {
			AddressView.prototype.initialize.apply( this, arguments );

			this.listenTo( this, 'ready', this.onReady );
			this.listenTo( this.model, 'change:mode', this.onModeChange );
		},

		onReady: function() {
			this.toggleAllowAutocomplete();
		},

		toggleAllowAutocomplete: function() {
			if ( 'simple' === this.model.get( 'mode' ) ) {
				$( '.justwpforms-customize-part-google_autocomplete', this.$el ).show();
			} else {
				$( '.justwpforms-customize-part-google_autocomplete', this.$el ).hide();
			}
		},

		onModeChange: function( model, value ) {
			this.toggleAllowAutocomplete();

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
