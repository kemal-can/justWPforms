( function( $, _, Backbone, api, settings ) {

	var FormEmail = justwpforms.classes.views.FormEmail;

	justwpforms.classes.views.FormEmail = FormEmail.extend( {
		ready: function() {
			FormEmail.prototype.ready.apply( this, arguments );

			this.initMediaUploads();
		},

		initMediaUploads: function() {
			$( '.justwpforms-media-upload' ).justwpformsMediaHandle( this.model );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
