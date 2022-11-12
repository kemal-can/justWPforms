( function( $, _, Backbone, api, settings ) {

	var AddressView = justwpforms.classes.views.parts.address;

	justwpforms.classes.views.parts.address = AddressView.extend( {
		events: _.extend( {}, AddressView.prototype.events, {} ),

		initialize: function() {
			AddressView.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:mode', this.onModeChange );
			this.listenTo( this.model, 'change:has_geolocation', this.onGeolocationChange );
		},

		ready: function() {},

		onGeolocationChange: function( e ) {
			var model = this.model;

			this.model.set( 'has_geolocation', this.model.get( 'has_geolocation' ) );

			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
