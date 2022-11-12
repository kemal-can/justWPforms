( function( $, settings ) {

	var justwpforms = window.justwpforms || {};
	window.justwpforms = justwpforms;

	justwpforms.dashboard = {
		$deactivationLink: null,
		copyToClipboardTimeout: null,

		init: function() {
			$( document ).on( 'click', '.justwpforms-editor-button', this.onEditorButton.bind( this ) );
			$( '.justwpforms-dialog__button' ).on( 'click', this.onDialogButton.bind( this ) );
			$( '.justwpforms-notice:not(.one-time)' ).on( 'click', '.notice-dismiss', this.onNoticeDismiss.bind( this ) );
			$( document ).on( 'click', 'button.justwpforms-clipboard__button', this.copyToClipboard );
		},

		onEditorButton: function( e ) {
			var title = $( e.currentTarget ).attr( 'data-title' );

			$('#justwpforms-modal').dialog( {
				title: title,
				dialogClass: 'justwpforms-dialog wp-dialog',
				draggable: false,
				width: 'auto',
				modal: true,
				resizable: false,
				closeOnEscape: true,
				position: {
					my: 'center',
					at: 'center',
					of: $(window)
				}
			} );
		},

		onDialogButton: function( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var formId = $( '#justwpforms-dialog-select' ).val();
			if ( ! formId ) {
				return false;
			}

			var shortcode = settings.shortcode.replace( 'ID', formId );
			window.parent.send_to_editor( shortcode );
			$( '#justwpforms-modal' ).dialog( 'close' );
			$( '#justwpforms-dialog-select' ).val( '' );

			if ( editor = this.getCurrentEditor() ) {
				editor.focus();
			}
		},

		getCurrentEditor: function() {
			var editor,
				hasTinymce = typeof tinymce !== 'undefined',
				hasQuicktags = typeof QTags !== 'undefined';

			if ( ! wpActiveEditor ) {
				if ( hasTinymce && tinymce.activeEditor ) {
					editor = tinymce.activeEditor;
					wpActiveEditor = editor.id;
				} else if ( ! hasQuicktags ) {
					return false;
				}
			} else if ( hasTinymce ) {
				editor = tinymce.get( wpActiveEditor );
			}

			return editor;
		},

		onNoticeDismiss: function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var $parent = $target.parents( '.notice' ).first();
			var id = $parent.attr( 'id' ).replace( 'justwpforms-notice-', '' );
			var nonce = $parent.data( 'nonce' );

			$.post( ajaxurl, {
					action: 'justwpforms_hide_notice',
					nid: id,
					nonce: nonce
				}
			);
		},

		copyToClipboard: function( e ) {
			var $target = $( e.target );
			var $success = $target.next();
			var $input = $( '<input>' );

			$input.val( $target.attr( 'data-value' ) );
			$target.after( $input );
			$input.focus().select();

			try {
				document.execCommand( 'copy' );
				clearTimeout( this.copyToClipboardTimeout );
				$success.removeClass( 'hidden' );
				copyToClipboardTimeout = setTimeout( function() {
					$success.addClass( 'hidden' );
				}, 3000 );
			} catch( e ) {}

			$target.trigger( 'focus' );
			$input.remove();
		},
	};

	$( function() {
		justwpforms.dashboard.init();
	} );

} )( jQuery, _justwpformsAdmin );