(function ($, _, Backbone, api, settings) {

	justwpforms.classes.models.parts.email_integration = justwpforms.classes.models.Part.extend( {
		defaults: function () {
			return _.extend(
				{},
				settings.formParts.email_integration.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.models.parts.mailchimp = justwpforms.classes.models.parts.email_integration;

	justwpforms.classes.views.parts.email_integration = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-email_integration-template',
		editor: null,

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'sort-stop': 'onSortStop',
		} ),

		ready: function () {
			justwpforms.classes.views.Part.prototype.ready.apply(this, arguments);

			this.initEditor();
		},

		initEditor: function() {
			var $textarea = $('textarea[name=email_integration_text]', this.$el);
			var editorId = $textarea.attr('id');
			var editorSettings = {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link',
					setup: this.onEditorInit.bind(this)
				},
			};

			wp.editor.initialize(editorId, editorSettings);
		},

		removeEditor: function() {
			var $textarea = $('textarea[name=email_integration_text]', this.$el);
			var editorId = $textarea.attr('id');

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
			editor.on('keyup change', function () {
				this.model.set('email_integration_text', editor.getContent());

				var data = {
					id: this.model.get('id'),
					callback: 'onEmailIntegrationTextChange',
				};

				justwpforms.previewSend('justwpforms-part-dom-update', data);
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
			var $textarea = $('textarea[name=email_integration_text]', this.$el);
			var editorId = $textarea.attr('id');

			wp.editor.remove(editorId);

			justwpforms.classes.views.Part.prototype.remove.apply(this, arguments);
		}
	} );

	justwpforms.classes.views.parts.mailchimp = justwpforms.classes.views.parts.email_integration;

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onEmailIntegrationTextChange: function (id, html, options) {
			var part = this.getPartModel(id);
			var $part = this.getPartElement(html);

			this.$( '.justwpforms-part__el .label', $part ).html( part.get( 'email_integration_text' ) );
		},
	} );

	var FormBuild = justwpforms.classes.views.FormBuild;

	justwpforms.classes.views.FormBuild = FormBuild.extend( {
		ready: function() {
			FormBuild.prototype.ready.apply( this, arguments );

			var emailIntegrationPart = justwpforms.form.get( 'parts' ).findWhere( { type: 'email_integration' } );

			if ( emailIntegrationPart ) {
				this.drawer.$el.addClass( 'has-email_integration-part' );
			}
		},

		onPartAdd: function( type, options ) {
			if ( 'email_integration' === type ) {
				var emailIntegrationPart = justwpforms.form.get( 'parts' ).findWhere( { type: 'email_integration' } );

				if ( emailIntegrationPart ) {
					return;
				}

				this.drawer.$el.addClass( 'has-email_integration-part' );
			}

			FormBuild.prototype.onPartAdd.apply( this, arguments );
		},

		onPartModelRemove: function( model ) {
			FormBuild.prototype.onPartModelRemove.apply( this, arguments );

			if ( 'email_integration' === model.get( 'type' ) ) {
				this.drawer.$el.removeClass( 'has-email_integration-part' );
			}
		}
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
