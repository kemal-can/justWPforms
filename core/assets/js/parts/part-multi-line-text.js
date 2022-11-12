( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.multi_line_text = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.multi_line_text.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.multi_line_text = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-multi-line-text-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo( this.model, 'change:placeholder', this.onPlaceholderChange );
			this.listenTo( this.model, 'change:limit_input', this.onLimitInputChange );
			this.listenTo( this.model, 'change:character_limit', this.onCharacterLimitChange );
			this.listenTo( this.model, 'change:character_limit_mode', this.onCharacterLimitModeChange );
			this.listenTo( this.model, 'change:characters_label', this.onLimitWordsCharactersLabelChange );
			this.listenTo( this.model, 'change:words_label', this.onLimitWordsCharactersLabelChange );
		},

		/**
		 * Send updated placeholder value to previewer. Added as a special method
		 * because of 'textarea' selector used instead of 'input'.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onPlaceholderChange: function() {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onMultiLineTextPlaceholderChange',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onLimitInputChange: function( model, value ) {
			var $limitSettings = $( '.character-limit-settings', this.$el );

			if ( value ) {
				$limitSettings.show();
			} else {
				$limitSettings.hide();
			}

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		toggleMinMaxLabel: function( labelAttribute, $labelControl, limitValue ) {
			var label = this.model.get( labelAttribute ).toLowerCase();

			if ( -1 !== label.indexOf( 'min ' ) || -1 !== label.indexOf( 'max ' ) ) {
				if ( 'word_max' === limitValue || 'character_max' === limitValue ) {
					label = label.replace( 'min ', 'max ' );
				} else {
					label = label.replace( 'max ', 'min ' );
				}

				label = label.charAt(0).toUpperCase() + label.slice( 1 );

				$( 'input', $labelControl ).val( label );
				this.model.set( labelAttribute, label );
			}
		},

		onCharacterLimitModeChange: function( model, value ) {
			switch ( value ) {
				case 'word_min':
				case 'word_max':
					var $wordsLabel = $( '.character-limit__words-label', this.$el );
					this.toggleMinMaxLabel( 'words_label', $wordsLabel, value );

					$wordsLabel.show();
					$( '.character-limit__characters-label', this.$el ).hide();

					break;
				case 'character_min':
				case 'character_max':
					var $charactersLabel = $( '.character-limit__characters-label', this.$el );
					this.toggleMinMaxLabel( 'characters_label', $charactersLabel, value );

					$charactersLabel.show();
					$( '.character-limit__words-label', this.$el ).hide();
					break;
				default:
					break;
			}

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		onCharacterLimitChange: function( model, value ) {
				var data = {
					id: this.model.get('id'),
					callback: 'onCharacterLimitChange',
				};

				justwpforms.previewSend('justwpforms-part-dom-update', data);

		},

		onLimitWordsCharactersLabelChange: function( model, value ) {
			var data = {
				id: this.model.get( 'id' ),
				callback: 'onLimitWordsCharactersLabelChangeCallback',
				options: {
					label: value
				}
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		}

	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onMultiLineTextPlaceholderChange: function( id, html, options ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );

			this.$( 'textarea', $part ).attr( 'placeholder', part.get( 'placeholder' ) );
		},

		onLimitWordsCharactersLabelChangeCallback: function( id, html, options ) {
			var $part = this.getPartElement( html );

			this.$( 'span.counter-label', $part ).text( options.label );
		},

		onMultiLineTextRowsChange: function( id, html, options ) {
			var $part = this.getPartElement( html );

			this.$( 'textarea', $part ).attr( 'rows', options.value );
		},
		
		onCharacterLimitChange: function (id, html, options) {
			var part = this.getPartModel(id);
			var $part = this.getPartElement(html);

			this.$( '.justwpforms-part__char-counter span.counter-limit', $part ).html( part.get( 'character_limit' ) );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
