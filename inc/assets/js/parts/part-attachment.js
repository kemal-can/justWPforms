( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.attachment = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.attachment.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	justwpforms.classes.views.parts.attachment = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-attachment-template',

		events: _.extend( {}, justwpforms.classes.views.Part.prototype.events, {
			'click .justwpforms-show-file-extensions a': 'onShowFileExtensionsClick',
			'click .justwpforms-hide-file-extensions a': 'onHideFileExtensionsClick',
			'change [data-bind="min_file_count"]': 'updateMinMaxFileCount',
			'change [data-bind="max_file_count"]': 'updateMinMaxFileCount',
			'change .justwpforms-file-types-wrap .justwpforms-file-type-checkbox': 'onFileTypeCheckboxClicked',
		} ),

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo( this.model, 'change:max_file_count', this.onMaxFileCountChange );
			this.listenTo( this.model, 'change:placeholder', this.onAttachmentPlaceholderChange );
			this.listenTo( this.model, 'change:file_limit_of_label', this.onFileLimitOfLabelChange );
			this.listenTo( this.model, 'change:max_files_uploaded_label', this.onMaxFilesUploadedLabelChange );
		},

		onFileTypeCheckboxClicked: function( e ) {
			var checkbox = e.target;
			var clickedType = $( checkbox ).val();
			var selectedExtensions = this.model.get( 'allowed_file_extensions' ).replace(/\s/g, '').split( ',' );
			var indexOf = selectedExtensions.indexOf( clickedType );

			if ( checkbox.checked ) {
				if ( -1 == indexOf ) {
					selectedExtensions.push( clickedType );
				}
			} else {
				if ( indexOf >= 0 ) {
					selectedExtensions.splice( indexOf, 1 );
				}
			}

			this.model.set( 'allowed_file_extensions', selectedExtensions.join() );
		},

		updateMinMaxFileCount: function() {
			var minFileCount = this.model.get( 'min_file_count' );
			var maxFileCount = this.model.get( 'max_file_count' );

			minFileCount = '' == minFileCount ? 0 : minFileCount;
			maxFileCount = '' == maxFileCount ? 0 : maxFileCount;
			minFileCount = parseInt( minFileCount );
			maxFileCount = parseInt( maxFileCount );

			if ( maxFileCount != 0 && minFileCount > maxFileCount ) {
				maxFileCount = minFileCount;
			}

			this.model.set( 'min_file_count', minFileCount );
			this.model.set( 'max_file_count', maxFileCount );

			$( '[data-bind="min_file_count"]', this.$el ).val( minFileCount == 0 ? '' : minFileCount );
			$( '[data-bind="max_file_count"]', this.$el ).val( maxFileCount  == 0 ? '' : maxFileCount );
		},

		onMaxFileCountChange: function( model, value ) {
			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		onAttachmentPlaceholderChange: function( model, value ) {
			var data = {
				id: model.id,
				callback: 'onAttachmentPlaceholderChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		onFileLimitOfLabelChange: function( model, value ) {
			var data = {
				id: model.id,
				callback: 'onAttachmentFileLimitOfLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},


	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onAttachmentPlaceholderChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $placeholder = this.$( '.justwpforms-attachment-progress[data-type=default]', $part );

			$placeholder.text( part.get( 'placeholder' ) );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
