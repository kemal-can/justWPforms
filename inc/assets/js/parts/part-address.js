( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.address = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.address.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.address = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-address-template',

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {} ),

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:mode', this.onModeChange );
		},

		onModeChange: function( model, value ) {
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
