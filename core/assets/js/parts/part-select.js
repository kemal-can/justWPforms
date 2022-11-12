( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.select = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.select.defaults,
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
				is_heading: false,
			}, _.result( justwpforms.classes.models.Option.prototype, 'defaults' ) );
		},
	} );

	var OptionCollection = Backbone.Collection.extend( {
		model: OptionModel,
	} );

	justwpforms.classes.views.selectOptionHeading = justwpforms.classes.views.OptionHeading.extend( {
		template: '#customize-justwpforms-select-item-heading-template',

		onLabelChange: function( e ) {
			var label = $( e.target ).val();
			this.model.set( 'label', label );
			this.part.trigger( 'change' );
			$('.justwpforms-item-choice-widget-title h3 .choice-in-widget-title span', this.$el ).text( label );

			var data = {
				id: this.part.get( 'id' ),
				callback: 'onSelectItemHeadingLabelChangeCallback',
				options: {
					itemID: this.model.get( 'id' ),
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},
	} );

	justwpforms.classes.views.selectOptionItem = justwpforms.classes.views.OptionItem.extend( {
		template: '#customize-justwpforms-select-item-template',

		onItemLabelChange: function( e ) {
			var label = $( e.target ).val();
			this.model.set( 'label', label );
			this.part.trigger( 'change' );
			$('.justwpforms-item-choice-widget-title h3 .choice-in-widget-title span', this.$el ).text( label );

			var data = {
				id: this.part.get( 'id' ),
				callback: 'onSelectItemLabelChangeCallback',
				options: {
					itemID: this.model.get( 'id' ),
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onItemDefaultChange: function( e ) {
			var isChecked = $( e.target ).is( ':checked' );

			this.model.collection.forEach( function( item ) {
				item.set( 'is_default', 0 );
			} );

			$( '[name=is_default]', this.$el.siblings() ).prop( 'checked', false );

			if ( isChecked ) {
				this.model.set( 'is_default', 1 );
				$( e.target ).prop( 'checked', true );
			}

			var data = {
				id: this.part.get( 'id' ),
				callback: 'onSelectItemDefaultChangeCallback',
				options: {
					itemID: this.model.get( 'id' ),
					checked: isChecked
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},
	} );

	justwpforms.classes.views.parts.select = justwpforms.classes.views.ChoiceField.extend( {
		template: '#customize-justwpforms-select-template',

		initialize: function() {
			justwpforms.classes.views.ChoiceField.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:placeholder', this.onPlaceholderChange );
		},

		getOptionItemView: function( optionModel, options ) {
			var view = new justwpforms.classes.views.selectOptionItem( _.extend( {
				model: optionModel,
				part: this.model,
			}, options ) );

			return view;
		},

		getOptionHeadingView: function( optionModel, options ) {
			var view = new justwpforms.classes.views.selectOptionHeading( _.extend( {
				model: optionModel,
				part: this.model,
			}, options ) );

			return view;
		},

		onPlaceholderChange: function( model, value ) {
			var data = {
				id: model.get( 'id' ),
				callback: 'onSelectPlaceholderChangeCallback',
				options: {
					label: value
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onOtherOptionLabelChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onSelectOtherLabelChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onOptionModelRemove: function( optionModel ) {
			this.model.trigger( 'change' );

			var optionViewModel = this.optionViews.find( function( viewModel ) {
				return viewModel.get( 'view' ).model.id === optionModel.id;
			}, this );

			this.optionViews.remove( optionViewModel );

			if ( this.model.get( 'options' ).length == 0 ) {
				$( '.options ul', this.$el ).html( '' );
			}

			var model = this.model;

			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onSelectItemLabelChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var option = part.get( 'options' ).get( options.itemID );

			this.$( '#' + options.itemID, $part ).text( option.get( 'label' ) );
		},

		onSelectItemHeadingLabelChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var option = part.get( 'options' ).get( options.itemID );

			this.$( '#' + options.itemID, $part ).attr( 'label', option.get( 'label' ) );
		},

		onSelectItemDefaultChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var option = part.get( 'options' ).get( options.itemID );

			this.$( 'select option', $part ).removeAttr( 'selected' );

			if ( options.checked ) {
				this.$( '#' + options.itemID, $part ).prop( 'selected', 'selected' );
			}
		},

		onSelectPlaceholderChangeCallback: function( id, html, options, $ ) {
			var $part = this.getPartElement( html );

			$( 'select option.justwpforms-placeholder-option', $part ).text( options.label );
		},

		onSelectOtherLabelChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			this.$( '.justwpforms-select option#other-option', $part ).text( part.get( 'other_option_label' ) );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
