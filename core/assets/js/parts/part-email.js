( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.email = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.email.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.email = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-email-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);
		},

	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
