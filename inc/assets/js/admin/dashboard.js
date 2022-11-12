( function( $, settings ) {

	var onDialogButton = justwpforms.dashboard.onDialogButton;

	justwpforms.dashboard.onDialogButton = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var formId = $( '#justwpforms-dialog-select' ).val();

		if ( ! formId ) {
			return false;
		}

		if ( 1 == settings.forms[formId].modal ) {
			var link = settings.modalLink.replace( 'ID', formId );

			window.parent.send_to_editor( link );
			$( '#justwpforms-modal' ).dialog( 'close' );
			$( '#justwpforms-dialog-select' ).val( '' );

			if ( editor = this.getCurrentEditor() ) {
				editor.trigger( 'focus' );
			}

			return;
		}

		return onDialogButton.apply( this, arguments );
	}

	justwpforms.dashboard.removeNotices = function() {
		$( '.justwpforms-notice' ).remove();
	}

	justwpforms.dashboard.addNotice = function( status, message, dismissible ) {
		this.removeNotices();

		var classes = 'notice justwpforms-notice notice-' + status;

		if ( dismissible ) {
			classes += ' is-dismissible';
		}


		var html = '<div class="' + classes + '"><p>' + message + '</p>';

		if ( dismissible ) {
			html += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">';
			html += settings.textNoticeDismiss;
			html += '</span></button>';
		}

		html += '</div>';

		var $notice = $( html );

		$( '#post' ).before( $notice );

		var $button = $( '.notice-dismiss', $notice );

		$button.on( 'click', function( e ) {
			e.preventDefault();

			$notice.fadeTo( 100, 0, function() {
				$notice.slideUp( 100, function() {
					$notice.remove();
				} );
			} );
		} );
	}

	var SendUserEmail = function() {
		this.$field = null;
		this.$button = null;
		this.$spinner = null;
	}

	SendUserEmail.prototype.bind = function() {
		this.$field = $( '#justwpforms-message-send-email-field' );
		this.$button = $( '#justwpforms-message-send-email-submit' );
		this.$spinner = this.$button.prev();
		this.$button.on( 'click', this.onClick.bind( this ) );
		this.$field.on( 'keydown', this.onKeyDown.bind( this ) );
	}

	SendUserEmail.prototype.onClick = function( e ) {
		e.preventDefault();

		this.$button.prop( 'disabled', true );
		this.$spinner.css( 'visibility', 'visible' );

		var url = this.$button.attr( 'data-url' );
		var email = this.$field.val();

		$.post( url, { email: email }, this.onSubmit.bind( this ) );
	}

	SendUserEmail.prototype.onKeyDown = function( e ) {
		if ( event.which == 13 || event.keyCode == 13 ) {
			e.preventDefault();

			this.$button.trigger( 'click' );
		}
	}

	SendUserEmail.prototype.onSubmit = function( response ) {
		this.$field.val( '' );
		this.$spinner.css( 'visibility', 'hidden' );
		this.$button.prop( 'disabled', false );

		var status = response.success ? 'success' : 'error';

		justwpforms.dashboard.addNotice( status, response.data.message, true );
		window.scrollTo( 0, 0 );
	}

	justwpforms.dashboard.focusFirstResponseInput = function() {
		$( '.justwpforms-edit-message-table input[type="text"],' +
		   ' .justwpforms-edit-message-table input[type="number"],' +
		   ' .justwpforms-edit-message-table textarea' ).first().trigger( 'focus' );
	}

	justwpforms.dashboard.animateTableRow = function( $tr, colorFrom, colorTo ) {
		$tr.animate( { 'backgroundColor': colorFrom }, 'fast' )
			.animate( { backgroundColor: colorTo }, {
				complete: function() {
					$tr.css( 'backgroundColor', '' );
				}
			} );
	}

	var ActivityRowActions = function() {
		this.removedRows = {};
	}

	ActivityRowActions.prototype.bind = function() {
		$( document ).on( 'click', '.justwpforms-mark_spam a', this.onMarkSpamClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-mark_not_spam a', this.onMarkNotSpamClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-undo a', this.onUndoClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-mark_read a', this.onMarkReadClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-mark_unread a', this.onMarkUnreadClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-trash a', this.onTrashClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-restore a', this.onRestoreClick.bind( this ) );
		$( document ).on( 'click', '.justwpforms-delete a', this.onDeleteClick.bind( this ) );
	}

	ActivityRowActions.prototype.getRowTemplate = function( message, postId, undoUrl ) {
		var id = postId ? `id="${postId}"` : ``;
		var undoLink = (
			undoUrl ?
			`<a href="#" data-href="${undoUrl}" title="">${settings.messageAdminNotices.undo}</a>.` :
			''
		);

		var template = `
		<tr class="justwpforms-undo" ${id}>
			<td colspan="4">
				<div class="undo">
					<div class="spam-undo-inside">
						${message}. ${undoLink}
					</div>
				</div>
			</td>
		</tr>
		`;

		return template;
	}

	ActivityRowActions.prototype.onMarkSpamClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var postId = $tr.attr( 'id' );
		var url = new URL( $target.attr( 'data-href' ) );
		var action = url.searchParams.get( 'action' );
		var notice = settings.messageAdminNotices[action];
		var undoUrl = $target.attr( 'data-undo' );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', '#faafaa' );

		$tr.fadeOut( 350, function() {
			var $html = $( self.getRowTemplate( notice, postId, undoUrl ) );

			$html.hide();
			$tr.before( $html );
			self.removedRows[postId] = $tr.remove();
			$html.fadeIn( 350 );
		} );
	}

	ActivityRowActions.prototype.onMarkNotSpamClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var url = new URL( $target.attr( 'data-href' ) );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', 'rgb(102, 204, 102)' );

		$tr.fadeOut( 350, function() {
			var $tbody = $tr.parents( 'tbody' );
			
			$tr.remove();

			if ( 0 === $tbody.children().length ) {
				var $html = $( self.getRowTemplate( settings.messageAdminNotices.noActivity ) );

				$tbody.append( $html );
			}
		} );
	}

	ActivityRowActions.prototype.onMarkReadClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var url = $target.attr( 'data-href' );
		var currentUrl = new URL( window.location.href );
		var statusFilter = currentUrl.searchParams.get( 'activity_status' );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		if ( 'unread' === statusFilter ) {
			$tr.fadeOut( 350, function() {
				var $tbody = $tr.parents( 'tbody' );
				
				$tr.remove();

				if ( 0 === $tbody.children().length ) {
					var $html = $( self.getRowTemplate( settings.messageAdminNotices.noActivity ) );

					$tbody.append( $html );
				}
			} );
		} else {
			$( 'th, td', $tr ).animate( { 'backgroundColor': 'transparent' }, 350, function() {
				$tr.removeClass( 'justwpforms-message-unread' );
			} );
		}
	}

	ActivityRowActions.prototype.onMarkUnreadClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var url = $target.attr( 'data-href' );
		var currentUrl = new URL( window.location.href );
		var statusFilter = currentUrl.searchParams.get( 'activity_status' );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		if ( 'read' === statusFilter ) {
			$tr.fadeOut( 350, function() {
				var $tbody = $tr.parents( 'tbody' );
				
				$tr.remove();

				if ( 0 === $tbody.children().length ) {
					var $html = $( self.getRowTemplate( settings.messageAdminNotices.noActivity ) );

					$tbody.append( $html );
				}
			} );
		} else {
			$( 'th, td', $tr ).animate( { 'backgroundColor': '#fcf9e8' }, 350, function() {
				$tr.addClass( 'justwpforms-message-unread' );
			} );
		}
	}

	ActivityRowActions.prototype.onTrashClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var postId = $tr.attr( 'id' );
		var url = new URL( $target.attr( 'data-href' ) );
		var action = url.searchParams.get( 'action' );
		var notice = settings.messageAdminNotices[action];
		var undoUrl = $target.attr( 'data-undo' );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', '#faafaa' );

		$tr.fadeOut( 350, function() {
			var $html = $( self.getRowTemplate( notice, postId, undoUrl ) );

			$html.hide();
			$tr.before( $html );
			self.removedRows[postId] = $tr.remove();
			$html.fadeIn( 350 );
		} );
	}

	ActivityRowActions.prototype.onDeleteClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var url = new URL( $target.attr( 'data-href' ) );
		var currentUrl = new URL( window.location.href );
		var statusFilter = currentUrl.searchParams.get( 'post_status' );
		var notice = ( 
			'trash' === statusFilter ?
			settings.messageAdminNotices.noActivityTrash :
			settings.messageAdminNotices.noActivity
		);

		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', '#faafaa' );

		$tr.fadeOut( 350, function() {
			var $tbody = $tr.parents( 'tbody' );
				
			$tr.remove();

			if ( 0 === $tbody.children().length ) {
				var $html = $( self.getRowTemplate( notice ) );

				$tbody.append( $html );
			}
		} );
	}

	ActivityRowActions.prototype.onRestoreClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var url = new URL( $target.attr( 'data-href' ) );
		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', 'rgb(102, 204, 102)' );

		$tr.fadeOut( 350, function() {
			var $tbody = $tr.parents( 'tbody' );
			
			$tr.remove();

			if ( 0 === $tbody.children().length ) {
				var $html = $( self.getRowTemplate( settings.messageAdminNotices.noActivityTrash ) );

				$tbody.append( $html );
			}
		} );
	}

	ActivityRowActions.prototype.onUndoClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		var $target = $( e.target );
		var $tr = $target.parents( 'tr' );
		var postId = $tr.attr( 'id' );
		var url = new URL( $target.attr( 'data-href' ) );

		var self = this;

		$.post( url, function( response ) {
			self.updateCounters( response.data );
		} );

		$( 'th, td', $tr ).css( 'backgroundColor', '#cceebb' );
		
		$tr.fadeOut( 350, function() {
			var $originalRow = self.removedRows[postId];
			
			$tr.after( $originalRow );
			$( 'th, td', $originalRow ).css( 'backgroundColor', '' );
			$originalRow.fadeIn( 350 );
			$tr.remove();
		} );
	}

	ActivityRowActions.prototype.updateCounters = function( data ) {
		$( 'li.all span.count' ).text( `(${ data.counters.total })` );
		$( 'li.unread span.count' ).text( `(${ data.counters.unread })` );
		$( 'li.read span.count' ).text( `(${ data.counters.read })` );
		$( 'li.spam span.count' ).text( `(${ data.counters.spam })` );
		$( 'li.trash span.count' ).text( `(${ data.counters.trash })` );

		var $unreadBadge = $( '.justwpforms-pending-count' );
		
		$unreadBadge.hide();

		if ( data.counters.unread > 0 ) {
			$( '.pending-count', $unreadBadge ).text( data.counters.unread );
			$unreadBadge.show();
		}

		document.title = data.pageTitle;

		$( `.justwpforms-responses-count-wrapper[data-form-id="${ data.formID }"]` ).replaceWith( data.badge );
	}

	var FormTable = function() {
		this.$link = null;
	}

	FormTable.prototype.bind = function() {
		this.settings = ( 'undefined' !== typeof _justwpformsFormStatusSettings ) ? _justwpformsFormStatusSettings : false;
		this.$table = $( 'body.post-type-justwpform .wp-list-table' );
		this.$restoreLink = $( 'body.post-type-justwpform .restore a' );

		if ( this.$restoreLink.length ) {
			this.$restoreLink.on( 'click', this.onRestoreClick.bind( this ) );
		}

		this.rewordNoFormsInArchive();
		this.removeLinksFromArchivedForms();
	}

	FormTable.prototype.onRestoreClick = function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();

		window.location = $( e.target ).attr( 'href' );

		var $tr = $( e.target ).parents( 'tr' );
		justwpforms.dashboard.animateTableRow( $tr, '#e7e7d3', 'rgba(249, 249, 249)' );
	}

	FormTable.prototype.removeLinksFromArchivedForms = function() {
		$( 'tr.type-justwpform.status-archive' ).each( function() {
			var $this = $( this );
			var $linkWrap = $( '.column-title > strong', $this );
			var linkText = $( 'a.row-title', $this ).text();

			$( 'a.row-title', $this ).remove();
			$linkWrap.text( linkText );
		} );
	}

	FormTable.prototype.rewordNoFormsInArchive = function() {
		if ( ! this.settings ) {
			return;
		}

		$( 'tr.no-items td', this.$table ).text( this.settings.labels.not_found_in_archive );
	}

	var dashboardInit = justwpforms.dashboard.init;

	justwpforms.dashboard.sendUserEmail = new SendUserEmail();
	justwpforms.dashboard.activityRowActions = new ActivityRowActions();
	justwpforms.dashboard.formTable = new FormTable();

	justwpforms.dashboard.init = function() {
		dashboardInit.apply( this, arguments );

		this.sendUserEmail.bind();
		this.focusFirstResponseInput();
		this.activityRowActions.bind();
		this.formTable.bind();
	}

} )( jQuery, _justwpformsAdmin );
