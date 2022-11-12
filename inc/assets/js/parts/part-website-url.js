( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.website_url = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.website_url.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.website_url = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-website-url-template'
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
