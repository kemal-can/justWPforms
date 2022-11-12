( function( $, _, Backbone, api, settings ) {

	var FormStyle = justwpforms.classes.views.FormStyle;
	var FormSetup = justwpforms.classes.views.FormSetup;
	var Previewer = justwpforms.previewer;

	justwpforms.classes.views.FormSetup = FormSetup.extend( {
		initialize: function() {
			FormSetup.prototype.initialize.apply( this, arguments );
		},

		onMuteStylesChange: function( model, value ) {
			var data = {
				callback: 'onMuteStylesCheckboxChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-class-update', data );
		}
	} );

	justwpforms.classes.views.FormStyle = FormStyle.extend( {
		events: _.extend({}, FormStyle.prototype.events, {
			'change [name=mute_styles]': 'onMuteStylesChange',
		}),

		initialize: function() {
			FormStyle.prototype.initialize.apply( this, arguments );

			this.listenTo( justwpforms.form, 'change:mute_styles', this.onMuteStylesChange );
		},

		onMuteStylesChange: function( e ) {
			var mutedStyles = ( 1 == justwpforms.form.get( 'mute_styles' ) );

			if ( mutedStyles ) {
				this.$el.addClass( 'muted-styles' );
			} else {
				this.$el.removeClass( 'muted-styles' );
			}

			var data = {
				callback: 'onMuteStylesCheckboxChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-class-update', data );
		},

		applyConditionClasses: function() {
			FormStyle.prototype.applyConditionClasses.apply( this, arguments );

			var mutedStyles = ( 1 == justwpforms.form.get( 'mute_styles' ) );

			if ( mutedStyles ) {
				this.$el.addClass( 'muted-styles' );
			}
		},
	} );

	justwpforms.previewer = _.extend( {}, Previewer, {
		onMuteStylesCheckboxChangeCallback: function( attribute, html ) {
			var $formContainer = this.$( html );
			var value = justwpforms.form.get( 'mute_styles' );

			if ( 1 == value ) {
				$formContainer.removeClass( 'justwpforms-styles' );
			} else {
				$formContainer.addClass( 'justwpforms-styles' );
			}
		}
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
