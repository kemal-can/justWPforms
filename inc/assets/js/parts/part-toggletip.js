( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.toggletip = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.toggletip.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.toggletip = justwpforms.classes.views.Part.extend( {
		template: '#justwpforms-customize-toggletip-template',
		editorId: null,

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'sort-stop': 'onSortStop',
		} ),

		ready: function() {
			justwpforms.classes.views.Part.prototype.ready.apply( this, arguments );
			this.initEditor();
		},

		onPartLabelChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onHeadingChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		initEditor: function() {
			var $textarea = $( 'textarea.wp-editor-area', this.$el );

			this.editorId = $textarea.attr( 'id' );
			this.editorSettings = {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link',
					plugins : 'charmap compat3x paste directionality hr image lists wordpress wpautoresize wpemoji wplink wptextpattern wpview',
					setup: this.onEditorInit.bind( this )
				},
				quicktags: {
					buttons: {}
				},
				mediaButtons: false
			};

			wp.editor.initialize( this.editorId, this.editorSettings );
		},

		removeEditor: function() {
			wp.editor.remove( this.editorId );
			this.editorId = null;
		},

		onSortStop: function() {
			this.removeEditor();
			this.initEditor();
		},

		onEditorInit: function( editor ) {
			editor.on( 'keyup change', function() {
				this.model.set( 'details', editor.getContent() );

				var data = {
					id: this.model.get('id'),
					callback: 'onDescriptionChangeCallBack',
				};

				justwpforms.previewSend('justwpforms-part-dom-update', data);
			}.bind( this ) );
		},

		remove: function() {
			wp.editor.remove( this.editorId );

			justwpforms.classes.views.Part.prototype.remove.apply( this, arguments );
		}
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onDescriptionChangeCallBack: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			$( '.justwpforms-part__el details div.justwpforms-toggletip-text', $part ).html( part.get( 'details' ) );
		},

		onHeadingChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var label = part.get( 'label' );
			var $label = this.$( '.justwpforms-part-wrap > .justwpforms-part__label-container span.label', $part ).first();

			$label.text( label );
			$( '.justwpforms-part__el details summary u', $part ).html( label );


		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
