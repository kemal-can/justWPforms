( function( $, settings, window ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.attachment = {
		init: function() {
			this.type = this.$el.data( 'justwpforms-type' );
			this.$input = $( 'input', this.$el );
			this.$form = this.$el.parents( 'form' );
			this.$uploadBox = $( '.justwpforms-upload-area', this.$el );
			this.uploadBox = this.$uploadBox.get( 0 );
			this.$fileList = $( '.justwpforms-attachment__list', this.$el );
			this.$fileItemTemplate = $( '#justwpforms-tmpl-attachment-file' );
			this.$progress = $( '.justwpforms-attachment-progress[data-type=uploading] span', this.$el );
			this.$fileCounter = $( '.justwpforms-attachment__counter span.current', this.$el );
			this.$invalidNotice = $( '.justwpforms-file-notices .justwpforms-part-error-notice[data-error-type=invalid]', this.$el );
			this.$sizeNotice = $( '.justwpforms-file-notices .justwpforms-part-error-notice[data-error-type=size]', this.$el );
			this.$duplicateNotice = $( '.justwpforms-file-notices .justwpforms-part-error-notice[data-error-type=duplicate]', this.$el );
			this.dragCounter = 0;

			this.formId = $( '[name="justwpforms_form_id"]', this.$form ).val();
			this.partId = this.$el.attr( 'data-justwpforms-part-id' );
			this.maxFileSize = this.$el.attr( 'data-justwpforms-max-file-size' ) + 'mb';
			this.maxFileCount = parseInt( this.$el.attr( 'data-justwpforms-max-file-count' ), 10 );
			this.allowedFileExtensions = this.$el.attr( 'data-justwpforms-allowed-file-extensions' );
			this.$itemTemplate = $( '.item-template', this.$el );

			this.total = 0;
			this.loaded = 0;
			this.uploader = null;
			this.removeList = [];

			this.$el.on( 'focusin', this.onFocusIn.bind( this ) );
			this.$el.on( 'focusout', this.onFocusOut.bind( this ) );
			this.$uploadBox.on( 'click', this.onClick.bind( this ) );
			this.$uploadBox.on( 'dragenter', this.onDragEnter.bind(this) );
			this.$uploadBox.on( 'dragleave', this.onDragLeave.bind(this) );
			this.$fileList.on( 'click', 'button.justwpforms-delete-attachment', this.onDeleteAttachment.bind( this ) );

			this.initUploader();
		},

		onFocusIn: function() {
			this.$uploadBox.addClass( 'focus' );
		},

		onFocusOut: function() {
			this.$uploadBox.removeClass( 'focus' );
		},

		onClick: function() {
			$( 'input[type="file"]', this.$el ).trigger( 'focus' );
		},

		initUploader: function() {
			var filters = {
				max_file_size: this.maxFileSize,
				prevent_duplicates: true,
			};

			var extensions = this.allowedFileExtensions.replace(/\s/g, '');

			filters.mime_types = [ {
				title: 'mime types',
				extensions: extensions,
			} ];

			this.uploader = new plupload.Uploader( {
				runtimes: 'html5',
				drop_element: this.uploadBox,
				browse_button: this.uploadBox,
				url : settings.ajaxUrl,
				multipart_params: {
					action: settings.fileUploadAction,
					justwpforms_form_id: this.formId,
					justwpforms_part_id: this.partId,
				},
				filters: filters,
			} );

			this.uploader.bind( 'FilesAdded', this.onFilesAdded.bind( this ) );
			this.uploader.bind( 'FileUploaded', this.onFileUploaded.bind( this ) );
			this.uploader.bind( 'UploadProgress', this.onUploadProgress.bind( this ) );
			this.uploader.bind( 'UploadComplete', this.onUploadComplete.bind( this ) );
			this.uploader.bind( 'Error', this.onError.bind( this ) );
			this.uploader.init();

			this.syncFileList();
		},

		syncFileList: function() {
			var uploader = this.uploader;

			$( '.justwpforms-attachment-item', this.$fileList ).each( function() {
				var $this = $( this );
				var id = $this.attr( 'data-attachment-id' );

				if ( '' !== id ) {
					var name = $( '.justwpforms-attachment-item__name', $this ).text();
					var size = parseInt( $( '.justwpforms-attachment-input__size', $this ).val() );
					var source = new mOxie.File();
					var file = new plupload.File( source );

					file.status = plupload.DONE;
					file.hash_id = id;
					file.name = name;
					file.size = size;

					uploader.files.push( file );
				}
			} );
		},

		onFilesAdded: function( uploader, files ) {
			var fileCount = uploader.files.length + files.length;

			if ( this.maxFileCount > 0 && fileCount > this.maxFileCount ) {
				uploader.splice( this.maxFileCount );
				files.splice( fileCount - this.maxFileCount );
			}

			for ( var i = 0; i < files.length; i++ ) {
				this.total = this.total + files[i].size;
			}

			this.startUpload();
		},

		onFileUploaded: function( uploader, file, result ) {
			if ( 200 === result.status ) {
				var response = JSON.parse( result.response );

				if ( response.success && response.data ) {
					this.loaded = this.loaded + file.size;
					uploader.getFile( file.id ).hash_id = response.data;
					this.refreshList();
				}

				this.$el.trigger( 'justwpforms-change' );
			}
		},

		onUploadProgress: function( uploader, file ) {
			var loaded = this.loaded + file.loaded;

			this.updateProgress( loaded );
		},

		onUploadComplete: function( uploader, files ) {
			this.total = 0;
			this.loaded = 0;

			this.$uploadBox.removeClass( 'uploading' );
			this.resetProgress();
			this.cleanUpQueue();
		},

		refreshList: function() {
			var fileCount = 0;

			this.$fileList.empty();

			this.uploader.files.forEach( function( file, f ) {
				if ( 'undefined' === typeof file || file.status !== 5 ) {
					return;
				}

				var itemTemplate = this.$itemTemplate.html();
				itemTemplate = itemTemplate.replace( /\[#\]/g, '[' + f + ']' );

				var $item = $( itemTemplate );
				var size = plupload.formatSize( file.size );

				$item.attr( 'data-attachment-id', file.hash_id );
				$( '.justwpforms-attachment-item__name', $item ).text( file.name );
				$( '.justwpforms-attachment-item__size', $item ).text( size );
				$( '.justwpforms-attachment-input__id', $item ).val( file.hash_id );
				$( '.justwpforms-attachment-input__name', $item ).val( file.name );
				$( '.justwpforms-attachment-input__size', $item ).val( file.size );

				this.$fileList.append( $item );
				fileCount++;
			}.bind( this ) );

			this.$fileCounter.text( fileCount );
			this.$input = $( 'input', this.$el );

			if ( this.uploader.files.length > 0 ) {
				this.$fileList.addClass( 'has-items' );
			} else {
				this.$fileList.removeClass( 'has-items' );
			}
		},

		cleanUpQueue: function () {
			var uploader = this.uploader;

			this.removeList.forEach( function( file ) {
				uploader.removeFile( file );
			} )

			this.removeList = [];
		},

		onError: function( uploader, error ) {
			if ( error.file ) {
				this.removeList.push( error.file );
			}

			if ( -600 === error.code ) {
				this.$sizeNotice.show();
			} else if ( -602 === error.code ) {
				this.$duplicateNotice.show();
			} else {
				this.$invalidNotice.show();
			}
		},

		startUpload: function() {
			this.$uploadBox.removeClass( 'entered' );
			this.$uploadBox.addClass( 'uploading' );
			this.hideNotices();
			this.uploader.start();
		},

		updateProgress: function( loaded ) {
			var percent = 0;

			if ( loaded === this.total ) {
				percent = 100;
			} else {
				percent = Math.ceil( ( loaded / this.total ) * 100 );
			}

			this.$progress.text( percent );
		},

		resetProgress: function() {
			this.$progress.text( 0 );
		},

		removeFile: function( hashId ) {
			if ( ! hashId.length ) {
				return;
			}

			for ( var i = 0; i < this.uploader.files.length; i++ ) {
				if ( hashId === this.uploader.files[i].hash_id ) {
					this.uploader.removeFile( this.uploader.files[i].id );
				}
			}

			this.refreshList();

			$.ajax( {
				url : settings.ajaxUrl,
				type: 'post',
				data: { action: settings.fileDeleteAction,
						hash_id: hashId,
						form_id: this.formId },
			} );
		},

		onDeleteAttachment: function( e ) {
			e.preventDefault();

			var $item = $( e.target ).parents( '.justwpforms-attachment-item' );
			var fileHashId = $item.attr( 'data-attachment-id' );

			this.removeFile( fileHashId );

			this.$el.trigger( 'justwpforms-change' );
		},

		onDragEnter: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.dragCounter++;
			this.$uploadBox.addClass( 'entered' );
		},

		onDragLeave: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.dragCounter--;

			if ( 0 === this.dragCounter ) {
				this.$uploadBox.removeClass( 'entered' );
			}
		},

		hideNotices: function() {
			this.$invalidNotice.hide();
			this.$sizeNotice.hide();
			this.$duplicateNotice.hide();
		}
	};

} )( jQuery, _justwpformsSettings, window );
