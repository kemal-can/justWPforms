( function ( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.likert_scale = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.likert_scale.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.likert_scale = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-likert-scale-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:step', this.onStepChange );
			this.listenTo( this.model, 'change:min_value', this.refreshPart );
			this.listenTo( this.model, 'change:max_value', this.refreshPart );
			this.listenTo( this.model, 'change:min_label', this.onMinLabelChange );
			this.listenTo( this.model, 'change:max_label', this.onMaxLabelChange );
			this.listenTo( justwpforms.form, 'save', this.onFormSave );
		},

		onMinLabelChange: function( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onLikertScaleMinLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onMaxLabelChange: function ( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onLikertScaleMaxLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onFormSave: function( form ) {
			var part = _.findWhere( form.parts, {
				id: this.model.get( 'id' )
			} );

			if ( ! part ) {
				return;
			}

			$( '[data-bind="min_value"]', this.$el ).val( part.min_value );
			$( '[data-bind="max_value"]', this.$el ).val( part.max_value );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onLikertScaleMinLabelChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $label = this.$( '.justwpforms-likert-scale-label--min', $part );

			$label.text( part.get( 'min_label' ) );
		},

		onLikertScaleMaxLabelChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $label = this.$( '.justwpforms-likert-scale-label--max', $part );

			$label.text( part.get( 'max_label' ) );
		},
	} );

	api.bind( 'ready', function () {
		api.previewer.bind( 'justwpforms-part-render', function ( $el ) {
			if ( ! $el.is( '.justwpforms-part--scale' ) ) {
				return;
			}

			$('input', $el).justwpformsScale();
		} );

		api.previewer.bind( 'justwpforms-part-dom-updated', function ( $el ) {
			if ( ! $el.is( '.justwpforms-part--scale' ) ) {
				return;
			}

			$( 'input', $el ).justwpformsScale();
		} );
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
