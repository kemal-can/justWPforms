( function( $, _, Backbone, api, settings, partSettings ) {

	justwpforms.classes.models.parts.narrative = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.narrative.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.narrative = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-narrative-template',

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'click .insert-media': 'onAddMediaClick',
			'sort-stop': 'onSortStop',
		} ),

		editorId: null,

		ready: function() {
			justwpforms.classes.views.Part.prototype.ready.apply( this, arguments );

			this.initEditor();
		},

		initEditor: function() {
			var $textarea = $( 'textarea.wp-editor-area', this.$el );
			this.editorId = $textarea.attr( 'id' );
			this.editorSettings = {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link,justwpforms_narrative_input,hfmedia',
					plugins : 'charmap compat3x paste directionality hr image lists media wordpress wpautoresize wpeditimage wpemoji wplink wptextpattern wpview',
					setup: this.onEditorInit.bind( this ),
				},
				quicktags: {
					buttons: 'strong,em,del,link,blank,close'
				},
				mediaButtons: true,
				hfmedia: {
					minimal: false,
					supports: {
						'image' : 'Image',
						'audio' : 'Audio',
						'video' : 'Video'
					},
					filters: {
						'image' : 'Image',
						'audio' : 'Audio',
						'video' : 'Video'
					},
				}
			};

			wp.editor.initialize( this.editorId, this.editorSettings );
		},

		removeEditor: function() {
			wp.editor.remove( this.editorId );
		},

		onSortStop: function() {
			this.removeEditor();
			this.initEditor();
		},

		onEditorInit: function( editor ) {
			var self = this;
			var refreshPreview = _.debounce( this.refreshPreview.bind( this ), 500 );

			if ( 'undefined' !== typeof QTags ) {
				QTags.addButton( 'hf_blank', 'blank', '[]', '', 'blank', 'Blank', 50, this.editorId );
			}

			editor.on( 'keyup change', function() {
				self.model.set( 'format', editor.getContent() );
				refreshPreview();
			} );

			editor.addButton( 'justwpforms_narrative_input', {
				title: partSettings.blankTooltip,

				onClick: function() {
					editor.insertContent( '[]' );
				},
			} );
		},

		refreshPreview: function() {
			var model = this.model;

			model.fetchHtml(function (response) {
				var data = {
					id: model.get('id'),
					html: response
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		onAddMediaClick: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var editor              = tinymce.get( this.editorId );
			var editorMediaSettings = this.editorSettings.hfmedia;

			justwpforms.utils.setMediaFilters( editorMediaSettings.filters );

			justwpforms.iodia = new wp.media.view.MediaFrame.Select();
			justwpforms.iodia.open();
			justwpforms.iodia.on( 'close', justwpforms.utils.onAttachmentSelected.bind( this, editor, editorMediaSettings.supports ) );
		},

		remove: function() {
			var editorId = this.model.id + '_format';
			wp.editor.remove( editorId );

			justwpforms.classes.views.Part.prototype.remove.apply( this, arguments );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings, _justwpformsNarrativeSettings );
