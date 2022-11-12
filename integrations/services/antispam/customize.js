( function( $, _, Backbone, api, settings, integrationsSettings ) {

	var FormStyle = justwpforms.classes.views.FormStyle;
	var FormSetup = justwpforms.classes.views.FormSetup;
	var Previewer = justwpforms.previewer;

	justwpforms.classes.views.FormSetup = FormSetup.extend( {
		initialize: function() {
			FormSetup.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:captcha', this.onChangeCaptcha );
			this.listenTo( this.model, 'change:captcha_label', this.onChangeCaptchaLabel );
			this.listenTo( this.model, 'change:captcha_theme', this.onChangeCaptcha );
		},

		ready: function() {
			FormSetup.prototype.ready.apply( this, arguments );

			this.onChangeCaptcha();
		},

		onChangeCaptcha: function() {
			if ( 'recaptchav3' == integrationsSettings.spam ) {
				return;
			}

			var data = {
				callback: 'onRecaptchaUpdateCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-recaptcha-update', data );
		},

		onChangeCaptchaLabel: function() {
			var data = {
				callback: 'onRecaptchaLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},
	} );

	justwpforms.classes.views.FormStyle = FormStyle.extend( {
		events: _.extend( {}, FormStyle.prototype.events, {
			'change [data-target="recaptcha"]': 'onRecaptchaChange',
		} ),

		onRecaptchaChange: function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var attribute = $target.data( 'attribute' );
			var value = $target.val();

			justwpforms.form.set( attribute, value );

			var data = {
				callback: 'onRecaptchaUpdateCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-recaptcha-update', data );
		},
	} );

	justwpforms.previewer = _.extend( {}, Previewer, {
		onRecaptchaUpdateCallback: function( $recaptcha, $ ) {
			var captcha = justwpforms.form.get( 'captcha' );

			if ( captcha ) {
				var siteKey = justwpforms.form.get( 'captcha_site_key' ) || 'null';
				$recaptcha.attr( 'data-sitekey', siteKey );
				var theme = justwpforms.form.get( 'captcha_theme' ) || 'light';
				$recaptcha.attr( 'data-theme', theme );
				$recaptcha.show();
				$recaptcha.justwpformPart( 'render' );
			} else {
				$recaptcha.hide();
				$recaptcha.justwpformPart( 'reset' );
			}
		},

		onRecaptchaLabelChangeCallback: function( $form ) {
			var recaptchaLabel = justwpforms.form.get( 'captcha_label' );
			$( '.justwpforms-part--recaptcha .label', $form ).text( recaptchaLabel );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings, _justwpformsIntegrations );
