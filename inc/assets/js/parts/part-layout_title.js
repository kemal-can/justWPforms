( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.layout_title = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.layout_title.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.layout_title = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-layout_title-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:label', this.onLabelChange );
			this.listenTo( this.model, 'change:level', this.onHeadingLevel );
		},

		onLabelChange: function( model, value ) {
			var data = {
				id: this.model.id,
				callback: 'onLayoutTitlePartLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onHeadingLevel: function( model, value ) {
			var data = {
				id: this.model.id,
				callback: 'onHeadingLevelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		}

	} );

	var Previewer = justwpforms.previewer;

	justwpforms.previewer = _.extend( {}, Previewer, {
		onLayoutTitlePartLabelChange: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var $label = this.$( '.justwpforms-layout-title', $part );

			$label.text( part.get( 'label' ) );
		},

		onHeadingLevelChange: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var $headinglevel = part.get( 'level' );
			var $label = this.$( '.justwpforms-layout-title', $part );

			$label.replaceWith( function() {
				return $( '<' + $headinglevel + '/>', {
						html: this.innerHTML,
						class: $( this ).attr( 'class' )
			    } );
			} );

		},

	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
