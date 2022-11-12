( function ( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.rating = justwpforms.classes.models.Part.extend({
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.rating.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.rating = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-rating-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo( this.model, 'change:rating_type', this.onRatingTypeChange );
			this.listenTo( this.model, 'change:rating_visuals', this.onRatingVisualsChange );
		},

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'keyup input.rating-label': 'updateRatingLabels',
			'change input.rating-label': 'updateRatingLabels',
		} ),

		onRatingTypeChange: function( model, value ) {
			var $ratingVisualsDropdown = $( 'select[data-bind=rating_visuals]', this.$el );
			var newValue = $( 'option.' + value + '-default', $ratingVisualsDropdown ).attr( 'value' );

			$( '[data-allowed-for]', $ratingVisualsDropdown ).prop('disabled', true);
			$( '[data-allowed-for*=' + value + ']', $ratingVisualsDropdown ).prop( 'disabled', false );

			if ( model.get( 'rating_visuals' ) !== newValue ) {
				$ratingVisualsDropdown.val( newValue ).trigger('change');
			} else {
				this.refreshRatingPart();
			}

			this.toggleRatingLabels();
		},

		onRatingVisualsChange: function() {
			this.toggleRatingLabels();
			this.refreshRatingPart();
		},

		toggleRatingLabels: function() {
			var ratingType = this.model.get( 'rating_type' );
			var ratingVisuals = this.model.get( 'rating_visuals' );
			var $scaleRatingSettings = $( '.justwpforms-nested-settings[data-trigger="rating_type:scale"]', this.$el );
			var $yesnoRatingSettings = $( '.justwpforms-nested-settings[data-trigger="rating_type:yesno"]', this.$el );

			if ( 'smileys' === ratingVisuals || 'thumbs' === ratingVisuals ) {
				switch( ratingType ) {
					case 'scale':
						$yesnoRatingSettings.hide();
						$scaleRatingSettings.show();
						break;
					case 'yesno':
						$scaleRatingSettings.hide();
						$yesnoRatingSettings.show();
						break;
				}
			} else {
				$scaleRatingSettings.hide();
				$yesnoRatingSettings.hide();
			}
		},

		refreshRatingPart: function () {
			var model = this.model;

			model.fetchHtml(function (response) {
				var data = {
					id: model.get('id'),
					html: response
				};

				justwpforms.previewSend('justwpforms-form-part-refresh', data);
			});
		},

		updateRatingLabels: function( e ) {
			var $input = $( e.target );
			var attribute = $input.data( 'attribute' );
			var labels = this.model.get( attribute );

			labels[$input.data('index')] = $input.val();

			this.model.set( attribute, labels ).trigger('change');

			var data = {
				id: this.model.id,
				callback: 'onRatingLabelUpdate',
				options: {
					attribute: attribute,
					index: $input.data('index')
				}
			};

			justwpforms.previewSend('justwpforms-part-dom-update', data );
		}
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onRatingLabelUpdate: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $partWrap = $( '.justwpforms-part__el', $part );

			$( 'label:eq('+ options.index + ') .justwpforms-rating__item-label', $partWrap ).text( part.get( options.attribute )[options.index] );
		}
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
