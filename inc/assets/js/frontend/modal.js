( function( $, settings ) {

	var modalTemplate = '#justwpforms-modal-template';

	var justwpformsModal = function( el, formId ) {
		this.formId = formId;
		this.el = el;
		this.$el = $( el );
		this.$inner = $( '.justwpforms-modal__inner', this.$el );
		this.$container = $( '.justwpforms-modal__form-container', this.$el );
		this.$closeButton = $( '.justwpforms-modal__close-button', this.$el );
		this.$form = null;

		this.open();
		this.bindEvents();

		$.get( settings.ajaxUrl, {
			action: settings.action,
			form_id: this.formId,
		}, function( html ) {
			this.$container.html( html );
			this.$closeButton.css( 'display', 'block' );
			this.focus();
		}.bind( this ) );
	}

	justwpformsModal.prototype.open = function() {
		$( 'body' ).append( this.$el );
		$( 'body' ).addClass( 'justwpforms-modal-open' );
	}

	justwpformsModal.prototype.bindEvents = function() {
		this.$closeButton.on( 'click', this.onCloseClick.bind( this ) );
		// this.$el.on( 'click', '.justwpforms-form', this.onInsideClick.bind( this ) );
		this.$el.on( 'click', this.onOutsideClick.bind( this ) );
		$( document ).on( 'keydown.justwpforms', this.onKeyDown.bind(this) );
		$( this.$el ).on( 'mousewheel touchmove', this.onScroll.bind(this) );
	}

	justwpformsModal.prototype.onScroll = function() {
		var $form = $( 'form', this.$el );
		var formScrollTop = Math.abs( $form.offset().top - $form.parent().offset().top );

		if ( formScrollTop > 0 ) {
			this.$closeButton.addClass('scrolled');
		} else {
			this.$closeButton.removeClass('scrolled');
		}
	}

	justwpformsModal.prototype.focus = function() {
		var $firstPart = $( '.justwpforms-part', this.$form ).first();

		switch ( $firstPart.attr( 'data-justwpforms-type' ) ) {
			case 'single_line_text':
			case 'multi_line_text':
			case 'number':
			case 'email':
				$( 'input:visible, textarea:visible', $firstPart ).trigger( 'focus' );
				break;
			default:
				break;
		}
	}

	justwpformsModal.prototype.unbindEvents = function() {
		$( document ).off( 'keydown.justwpforms' );
	}

	justwpformsModal.prototype.close = function() {
		$( '.justwpforms-form [data-justwpforms-type]', this.$el ).trigger( 'justwpforms.detach' );
		this.$el.remove();
		$( 'body' ).removeClass( 'justwpforms-modal-open' );
	}

	justwpformsModal.prototype.onInsideClick = function( e ) {
		e.stopPropagation();
	}

	justwpformsModal.prototype.onCloseClick = function( e ) {
		e.preventDefault();

		this.close();
		this.unbindEvents();
	}

	justwpformsModal.prototype.onOutsideClick = function( e ) {
		var container = this.$container.get( 0 );
		var eventPath = e.originalEvent.composedPath();

		if ( -1 !== eventPath.indexOf( container ) ) {
			return;
		}

		e.preventDefault();

		this.close();
		this.unbindEvents();
	}

	justwpformsModal.prototype.onKeyDown = function( e ) {
		if ( 27 === e.keyCode ) {
			e.preventDefault();
			this.close();
			this.unbindEvents();
		}
	}

	$.fn.justwpformsModal = function( formId ) {
		this.each(function() {
			$.data( this, 'justwpformsModal', new justwpformsModal( this, formId ) );
		} );
	}

	$( function() {
		$( document ).on( 'click', 'a[href^="#justwpforms-"]', function( e ) {
			e.stopPropagation();
			e.preventDefault();

			var formId = $( e.target ).attr( 'data-form-id' );

			if ( ! formId ) {
				formId = $( e.target ).attr( 'href' ).replace( '#justwpforms-', '' );
			}

			var html = $( '#justwpforms-modal-template-' + formId ).html();
			var $modal = $( html ).justwpformsModal( formId );
		} );
	} );

} )( jQuery, _justwpformsModalSettings );
