( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.single_line_text = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.single_line_text.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.single_line_text = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-single-line-text-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo( this.model, 'change:use_as_subject', this.onUseAsSubjectChange );
		},

		onUseAsSubjectChange: function( model, value ) {
			if ( 1 === value ) {
				var singleLineParts = justwpforms.form.get( 'parts' ).where({ type: 'single_line_text' });

				_(singleLineParts).each(function( partModel ) {
					if ( partModel.id !== model.id ) {
						partModel.set( 'use_as_subject', 0 );
					}
				});
			} else {
				$( '[data-bind=use_as_subject]', this.$el ).prop( 'checked', false );
			}
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
