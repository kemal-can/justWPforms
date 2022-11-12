( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.media = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.media.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.media = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-media-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:attachment', this.onAttachmentChange );
		},

		ready: function() {
			justwpforms.classes.views.Part.prototype.ready.apply( this, arguments );

			$( '.justwpforms-media-upload' ).justwpformsMediaHandle( this.model, {
				'mediaTypes': [ 'image', 'audio', 'video' ]
			} );
		},

		onAttachmentChange: function( model, value ) {
			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
