( function ( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.scale = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.scale.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.scale = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-scale-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:min_label', this.onMinLabelChange );
			this.listenTo( this.model, 'change:max_label', this.onMaxLabelChange );
			this.listenTo( this.model, 'change:multiple', this.onMultipleChange );
			this.listenTo( justwpforms.form, 'save', this.onFormSave );
		},

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
				'change [data-bind=default_value]': 'onDefaultValueChange',
				'change [data-bind=min_value]': 'onMinValueChange',
				'change [data-bind=max_value]': 'onMaxValueChange',
				'change [data-bind=default_range_from]': 'onDefaultRangeFromChange',
				'change [data-bind=default_range_to]': 'onDefaultRangeToChange',
				'change [data-bind=default_range_from]': 'refreshPart',
				'change [data-bind=default_range_to]': 'refreshPart',
				'change [data-bind=step]': 'onSliderStepIntervalChange',
		} ),

		onMinValueChange: function( e ) {
			var model = this.model;
			var value = $(e.target).val();

			model.set('min_value', value);

			if ( parseInt( value, 10 ) > parseInt( model.get( 'default_range_from' ), 10 ) ) {
				model.set( 'default_range_from', value );
			}

			$( '[data-bind=default_value]', this.$el ).val( model.get( 'default_value' ) );
			$( '[data-bind=default_range_from]', this.$el ).val( model.get( 'default_range_from' ) );

			this.refreshPart();
		},

		onMaxValueChange: function (e) {
			var model = this.model;
			var value = $(e.target).val();
			var intValue = parseInt(value, 10);

			if ( intValue < parseInt( model.get('default_value'), 10 ) ) {
				model.set('default_value', value);
			}

			if ( intValue < parseInt( model.get('default_range_to'), 10 ) ) {
				model.set('default_range_to', value );
			}

			$( '[data-bind=default_value]', this.$el ).val( model.get( 'default_value' ) );
			$( '[data-bind=default_range_to]', this.$el ).val( model.get( 'default_range_to' ) );

			this.refreshPart();
		},

		onDefaultValueChange: function( model, value ) {
			this.refreshPart();
		},

		onDefaultRangeFromChange: function( e ) {
			var value = $(e.target).val();
			var model = this.model;

			if ( parseInt( value, 10 ) < parseInt( model.get('min_value'), 10 ) ) {
				model.set('default_range_from', model.get('min_value'));
				$('[data-bind=default_range_from]', this.$el).val(model.get('min_value'));
			}
		},

		onDefaultRangeToChange: function( e ) {
			var value = $(e.target).val();
			var model = this.model;

			if (parseInt(value, 10) > parseInt(model.get('max_value'), 10)) {
				model.set('default_range_to', model.get('max_value'));
				$('[data-bind=default_range_to]', this.$el).val(model.get('max_value'));
			}
		},

		onSliderStepIntervalChange: function( e ) {
			if ( $( e.target ).val() <= 0 ) {
				$('[data-bind=step]', this.$el).val( 1 );
				this.model.set( 'step', 1 );
			}

			var data = {
				id: this.model.get( 'id' ),
				callback: 'onSliderStepIntervalChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onMinLabelChange: function( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onScaleMinLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onMaxLabelChange: function ( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onScaleMaxLabelChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onMultipleChange: function ( model, value ) {
			var $multipleOptions = $( '.justwpforms-nested-settings[data-trigger="multiple"]', this.$el );

			if ( 1 === value ) {
				$multipleOptions.show();
				this.$el.find('.scale-single-options').hide();
			} else {
				this.$el.find('.scale-single-options').show();
				$multipleOptions.hide();
			}

			this.refreshPart();
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
			$( '[data-bind="default_range_from"]', this.$el ).val( part.default_range_from );
			$( '[data-bind="default_value"]', this.$el ).val( part.default_value );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onSliderStepIntervalChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $input = this.$( 'input', $part );

			$input.attr( 'step', part.get( 'step' ) );
		},

		onScaleMinLabelChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $label = this.$( '.label-min', $part );

			$label.text( part.get( 'min_label' ) );
		},

		onScaleMaxLabelChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $label = this.$( '.label-max', $part );

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
