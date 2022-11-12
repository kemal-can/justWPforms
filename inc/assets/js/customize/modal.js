( function( $, _, Backbone, api, settings ) {

	var FormStyle = justwpforms.classes.views.FormStyle;
	var FormSetup = justwpforms.classes.views.FormSetup;
	var Previewer = justwpforms.previewer;

	justwpforms.classes.views.FormSetup = FormSetup.extend( {
		initialize: function() {
			FormSetup.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:modal', this.onModalCheckboxChange );
		},

		onModalCheckboxChange: function( model, value ) {
			var data = {
				attribute: 'modal',
				callback: 'onModalCheckboxChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-class-update', data );
		}
	} );

	justwpforms.classes.views.FormStyle = FormStyle.extend( {
		events: _.extend( {}, FormStyle.prototype.events, {
			'change [data-target="modal_class"] input[type=radio]': 'onButtonSetModalClassChange',
		} ),

		applyConditionClasses: function() {
			FormStyle.prototype.applyConditionClasses.apply( this, arguments );

			var hasOverlay = ( 1 == justwpforms.form.get( 'modal' ) );

			if ( hasOverlay ) {
				this.$el.addClass( 'has-overlay' );
			}
		},

		onButtonSetModalClassChange: function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var attribute = $target.data( 'attribute' );
			var value = $target.val();

			justwpforms.form.set( attribute, value );
		},
	} );

	justwpforms.previewer = _.extend( {}, Previewer, {
		onModalCheckboxChangeCallback: function( attribute, html ) {
			var $formContainer = this.$( html );
			var value = justwpforms.form.get( 'modal' );

			if ( 1 == value ) {
				$formContainer.addClass( 'justwpforms-form--modal' );
			} else {
				$formContainer.removeClass( 'justwpforms-form--modal' );
			}
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );