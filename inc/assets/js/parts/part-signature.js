(function ($, _, Backbone, api, settings) {

	justwpforms.classes.models.parts.signature = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.signature.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.signature = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-signature-template',
		editor: null,

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'sort-stop': 'onSortStop',
		} ),

		ready: function () {
			justwpforms.classes.views.Part.prototype.ready.apply( this, arguments );

			this.listenTo( this.model, 'change:description_mode', this.onDescriptionModeChange );
			this.listenTo( this.model, 'change:signature_type', this.onSignatureTypeChange );

			this.initEditor();
		},

		initEditor: function() {
			var $textarea = $( 'textarea[name=intent_text]', this.$el );
			var editorId = $textarea.attr( 'id' );
			var editorSettings = {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link',
					setup: this.onEditorInit.bind( this )
				},
			};

			wp.editor.initialize( editorId, editorSettings );
		},

		removeEditor: function() {
			var $textarea = $( 'textarea[name=intent_text]', this.$el );
			var editorId = $textarea.attr( 'id' );

			wp.editor.remove( editorId );
		},

		onSortStop: function () {
			this.removeEditor();
			this.initEditor();
		},

		/**
		 * Triggere previewer event on each `keyup` and `change` event in the WP editor.
		 *
		 * @since 1.0.0.
		 *
		 * @param {object} editor TinyMCE editor JS object.
		 *
		 * @return void
		 */
		onEditorInit: function ( editor ) {
			editor.on( 'keyup change', function () {
				this.model.set( 'intent_text', editor.getContent() );

				var data = {
					id: this.model.get( 'id' ),
					callback: 'onSignatureIntentTextChange',
				};

				justwpforms.previewSend( 'justwpforms-part-dom-update', data );
			}.bind( this ) );
		},

		/**
		 * Add a special treatment for removing WP editor when the part is removed.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		remove: function () {
			var $textarea = $( 'textarea[name=intent_text]', this.$el );
			var editorId = $textarea.attr( 'id' );

			wp.editor.remove( editorId );

			justwpforms.classes.views.Part.prototype.remove.apply( this, arguments );
		},

		onInputLabelChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onSignatureInputLabelChange'
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onDescriptionModeChange: function( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onSignatureDescriptionModeChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onSignatureTypeChange: function( model, value ) {
			var $placeholderField = $( '.justwpforms-placeholder-option', this.$el );

			if ( 'type' === value ) {
				$placeholderField.show();
			} else {
				$placeholderField.hide();
			}

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onSignatureIntentTextChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			this.$( '.option-label .label', $part ).html( part.get( 'intent_text' ) );
		},

		onSignatureDescriptionModeChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			if ( 'above_signature' === part.get( 'description_mode' ) ) {
				$part.addClass( 'justwpforms-part--description-above-signature' );
			} else {
				$part.removeClass( 'justwpforms-part--description-above-signature' );
			}
		},
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
