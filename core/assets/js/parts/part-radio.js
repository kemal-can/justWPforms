( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.radio = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.radio.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},

		initialize: function( attrs, options ) {
			justwpforms.classes.models.Part.prototype.initialize.apply( this, arguments );

			this.attributes.options = new OptionCollection( this.get( 'options' ), options );
		},

		toJSON: function() {
			var json = Backbone.Model.prototype.toJSON.apply( this, arguments );
			json.options = json.options.toJSON();

			return json;
		},
	} );

	var OptionModel = justwpforms.classes.models.Option.extend( {
		defaults: function() {
			return _.extend( {
				is_default: false,
				label: '',
				description: '',
				is_heading: false,
			}, _.result( justwpforms.classes.models.Option.prototype, 'defaults' ) );
		},
	} );

	var OptionCollection = Backbone.Collection.extend( {
		model: OptionModel,
	} );

	justwpforms.classes.views.radioOptionItem = justwpforms.classes.views.OptionItem.extend( {
		template: '#customize-justwpforms-radio-item-template',
	} );

	justwpforms.classes.views.radioOptionHeading = justwpforms.classes.views.OptionHeading.extend( {
		template: '#customize-justwpforms-radio-item-heading-template',
	} );

	justwpforms.classes.views.parts.radio = justwpforms.classes.views.ChoiceField.extend( {
		template: '#customize-justwpforms-radio-template',

		events: _.extend( {}, justwpforms.classes.views.ChoiceField.prototype.events, {
			'change [data-bind=display_type]': 'onDisplayTypeChange',
		} ),

		getOptionItemView: function( optionModel, options ) {
			var view = new justwpforms.classes.views.radioOptionItem( _.extend( {
				model: optionModel,
				part: this.model,
			}, options ) );

			return view;
		},

		getOptionHeadingView: function( optionModel, options ) {
			var view = new justwpforms.classes.views.radioOptionHeading( _.extend( {
				model: optionModel,
				part: this.model,
			}, options ) );

			return view;
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );