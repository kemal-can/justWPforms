( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.divider = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.divider.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.divider = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-divider-template',
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
