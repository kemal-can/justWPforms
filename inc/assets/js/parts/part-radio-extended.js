( function( $, _, Backbone, api, settings ) {

	var RadioView = justwpforms.classes.views.parts.radio;
	var RadioOptionView = justwpforms.classes.views.parts.radioOption;

	justwpforms.classes.views.parts.radioOption = RadioOptionView.extend( {
		initialize: function() {
			RadioOptionView.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:show_submissions_amount', this.onChangeShowSubmissions );
			this.listenTo( this.model, 'change:limit_submissions_amount', this.onChangeMaxSubmissionsAmount );
		},

		events: _.extend( {}, RadioOptionView.prototype.events, {
			'change [name=limit_submissions]': 'onItemLimitSubmissionsChange',
			'keyup [name=limit_submissions_amount]': 'onItemLimitSubmissionsAmountChange',
			'change [name=limit_submissions_amount]': 'onItemLimitSubmissionsAmountChange',
			'change [name=show_submissions_amount]': 'onItemLimitShowSubmissionsAmountChange',
		} ),

		onItemLimitSubmissionsChange: function( e ) {
			var isChecked = $( e.target ).is( ':checked' );

			if ( ! isChecked ) {
				this.model.set( 'show_submissions_amount', 0 );
				$( "input[name='show_submissions_amount']", this.$el ).prop('checked',false);;
			}

			this.model.set( 'limit_submissions', isChecked ? 1 : 0 );
			$( '.justwpforms-part-item-limit-submission-settings', this.$el ).toggle();
		},

		onChangeShowSubmissions: function( e ) {

			var model = this.part;

			this.part.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );

			} );
		},

		onChangeMaxSubmissionsAmount: function( e ) {

			var model = this.part;

			if ( 1 != this.model.get('show_submissions_amount') ) {
				return;
			}

			this.part.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );

			} );
		},

		onItemLimitSubmissionsAmountChange: function( e ) {
			this.model.set( 'limit_submissions_amount', $( e.target ).val() );
			this.part.trigger( 'change' );
		},

		onItemLimitShowSubmissionsAmountChange: function( e ) {
			var isChecked = $( e.target ).is( ':checked' );

			this.model.set( 'show_submissions_amount', isChecked ? "1" : 0 );
			this.part.trigger( 'change' );
		},
	} );

	justwpforms.classes.views.parts.radio = RadioView.extend( {
		initialize: function() {
			RadioView.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:other_option', this.onAddOtherOption );
			this.listenTo( this.model, 'change:other_option_label', this.onOtherOptionLabelChange );
			this.listenTo( this.model, 'change:other_option_placeholder', this.onOtherOptionPlaceholderChange );
		},

		onAddOtherOption: function( model, value ) {
			var $otherOptionOptions = $( '.justwpforms-nested-settings[data-trigger="other_option"]', this.$el );

			if ( 1 == value ) {
				$otherOptionOptions.show();
			} else {
				$otherOptionOptions.hide();
			}

			this.refreshPart();
		},

		onOtherOptionLabelChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onRadioOtherOptionLabelChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onOtherOptionPlaceholderChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onRadioOtherOptionPlaceholderChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onRadioOtherOptionLabelChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $otherOptionLabel = $( '.justwpforms-part-option--other .label', $part );

			$otherOptionLabel.text( part.get( 'other_option_label' ) );
		},

		onRadioOtherOptionPlaceholderChangeCallback: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $otherOptionInput = $( '.justwpforms-part-option--other input[type=text]', $part );

			$otherOptionInput.attr( 'placeholder', part.get( 'other_option_placeholder' ) );
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
