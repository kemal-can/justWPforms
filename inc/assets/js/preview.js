( function( $, _, Backbone, api, settings ) {

	justwpforms.parts = justwpforms.parts || {};

	var handlers = {};
	var $pencil, $form, $submit, $recaptcha;

	handlers.getPart = function( id ) {
		var $part = $( '[data-justwpforms-id="' + id + '"]' );

		return $part;
	}

	handlers.formTitleUpdate = function( title ) {
		$( '.justwpforms-form__title:first' ).text( title );
	}

	handlers.formDomUpdate = function( e ) {
		var context = parent.justwpforms.previewer;
		var callback = context[e.callback];
		var options = e.options || {}

		if ( callback ) {
			callback.call( context, $form, options, $ );
		}
	}

	handlers.formPartAdd = function( e ) {
		$part = $( e.html );

		if ( 'undefined' === typeof e.after ) {
			if ( $recaptcha.length ) {
				$recaptcha.before( $part );
			} else {
				$submit.before( $part );
			}
		} else if ( -1 === e.after ) {
			$( '.justwpforms-part', $form ).first().before( $part );
		} else {
			var $previous = handlers.getPart( e.after );
			$previous.after( $part );
		}

		$part.prepend( $( $pencil ) );
		justwpforms.wrapPart( $part, $form );

		if ( e.callback ) {
			var context = parent.justwpforms.previewer;
			var callback = context[e.callback];
			var options = e.options || {}

			if ( callback ) {
				callback.call( context, $form, options, $ );
			}
		}
	}

	handlers.formPartRefresh = function( e ) {
		var $part = handlers.getPart( e.id );
		var $next = $part.next();
		var $refreshedPart = $( e.html );

		$part.trigger( 'justwpforms.detach' );
		$part.remove();
		$next.before( $refreshedPart );
		$refreshedPart.prepend( $( $pencil ) );
		justwpforms.wrapPart( $refreshedPart, $form );
		$refreshedPart.trigger( 'justwpforms.attach' );

		if ( e.callback ) {
			var context = parent.justwpforms.previewer;
			var callback = context[e.callback];
			var options = e.options || {}

			if ( callback ) {
				callback.call( context, $form, options, $ );
			}
		}
	}

	handlers.formPartsRefresh = function( e ) {
		for( var id in e.data ) {
			var html = e.data[id];
			var $part = handlers.getPart( id );
			var $next = $part.next();
			var $refreshedPart = $( html );

			$part.trigger( 'justwpforms.detach' );
			$part.remove();
			$next.before( $refreshedPart );
			$refreshedPart.prepend( $( $pencil ) );
			justwpforms.wrapPart( $refreshedPart, $form );
			$refreshedPart.trigger( 'justwpforms.attach' );
		}
	}

	handlers.partialHtmlFetch = function( e ) {
		var $partial = $( e.selector );
		var context = parent.justwpforms.previewer;
		var $refreshedPartial = $( e.html );
		var options = e.options || {};

		if ( options.pencil ) {
			$refreshedPartial.prepend( $( $pencil ) );
		}

		if ( $partial.length ) {
			$partial.replaceWith( $refreshedPartial );
		} else {
			$( options.after ).after( $refreshedPartial );
		}
	}

	handlers.partialDomUpdate = function( e ) {
		var $partial = $( e.partialSelector );
		var context = parent.justwpforms.previewer;
		var callback = context[e.callback];
		var options = e.options || {};

		if ( callback ) {
			callback.call( context, e.partialSelector, e.id, $partial, options, $ );
		}
	}

	handlers.formPartDisable = function( e ) {
		var $part = handlers.getPart( e.id );

		$part.addClass( 'unloading' );
	}

	handlers.formPartsDisable = function( e ) {
		e.ids.forEach( function( id ) {
			var $part = handlers.getPart( id );
			$part.addClass( 'unloading' );
		} );
	}

	handlers.formPartRemove = function( id ) {
		var $part = handlers.getPart( id );
		$part.trigger( 'justwpforms.detach' );
		$part.remove();
	}

	handlers.partDomUpdate = function( e ) {
		var $part = handlers.getPart( e.id );
		var context = parent.justwpforms.previewer;
		var callback = context[e.callback];
		var options = e.options || {}

		if ( callback ) {
			callback.call( context, e.id, $part, options, $ );
		}
	}

	handlers.partialDisable = function( e ) {
		var $partial = $( '[data-partial-id='+ e.partial + ']' );

		$partial.addClass( 'unloading' );
	}

	handlers.partialRemove = function( e ) {
		var $partial = $( '[data-partial-id='+ e.partial + ']' );

		$partial.remove();
	}

	handlers.formPartsSort = function( ids ) {
		var $parts = $.map( ids, function( id ) {
			var $part = handlers.getPart( id );

			$part.trigger( 'justwpforms.detach' );
			$part.detach();

			return $part;
		} );

		$.each( $parts, function( i, $part ) {
			if ( $recaptcha.length ) {
				$recaptcha.before( $part );
			} else {
				$submit.before( $part );
			}

			$part.trigger( 'justwpforms.attach' );
		} );
	}

	handlers.subPartAdded = function( e ) {
		var $part = handlers.getPart( e.id );
		var callback = parent.justwpforms.onSubPartAdded;
		var options = {};

		callback.call( parent.justwpforms, e.id, $part, e.html, options );
	}

	handlers.cssVariableUpdate = function( e ) {
		var formID = parent.justwpforms.form.get( 'ID' );
		var $parts = $( '[data-justwpforms-type]', $form );
		var variable = {
			name: e.variable,
			value: e.value,
		};

		document.querySelector( '.justwpforms-form' ).style.setProperty( e.variable, e.value );

		$.each( $parts, function( i, part ) {
			$( part ).trigger( 'justwpforms.cssvar', variable );
		} );
	}

	handlers.formClassUpdate = function( e ) {
		var context = parent.justwpforms.previewer;
		var callback = context[e.callback];
		var options = e.options || {}
		var $parts = $( '[data-justwpforms-type]', $form );

		if ( callback ) {
			callback.call( context, e.attribute, $formContainer, options );
		}
	}

	handlers.formClassUpdated = function( e ) {
		var $parts = $( '[data-justwpforms-type]', $form );

		$.each( $parts, function( i, part ) {
			$( part ).trigger( 'justwpforms.formclass', $form.attr( 'class' ) );
		} );
	}

	handlers.pencilPartClick = function( e ) {
		e.preventDefault();

		var id = $( e.target ).parents( '.justwpforms-part' ).data( 'justwpforms-id' );
		api.preview.send( 'justwpforms-pencil-click-part', id );
	}

	handlers.pencilPartialClick = function( e ) {
		e.preventDefault();

		var $partial = $( e.target ).closest( '.justwpforms-partial-edit-shortcut' ).parent();

		api.preview.send( 'justwpforms-' + $partial.attr( 'data-partial-id' ) + '-pencil-click' );
	}

	handlers.customCSSUpdated = function( css ) {
		$( '[data-justwpforms-additional-css]' ).html( css );
	}

	handlers.recaptchaUpdate = function( e ) {
		var context = parent.justwpforms.previewer;
		var callback = context[e.callback];
		var options = e.options || {}

		if ( callback ) {
			callback.call( context, $recaptcha, $ );
		}
	}

	handlers.silenceEvent = function( e ) {
		e.preventDefault();
	}

	handlers.init = function() {
		// Populate pointers
		$pencil = $( '#justwpforms-pencil-template' ).html();
		$formContainer = $( '.justwpforms-form' );
		$form = $( '.justwpforms-form form' );
		$submit = $( '.justwpforms-part.justwpforms-part--submit', $form );
		$recaptcha = $( '.justwpforms-part.justwpforms-part--recaptcha', $form );

		// Append pencils to existing elements
		$( '.justwpforms-block-editable:not(.no-pencil)' ).prepend( $( $pencil ) );

		// Remove unpreviewable
		$( '.justwpforms-form form' ).removeClass( 'customize-unpreviewable' );
		$( '.notice a' ).removeClass( 'customize-unpreviewable' );
	}

	handlers.bind = function() {
		// Bind preview handlers
		api.preview.bind( 'justwpforms-form-title-update', handlers.formTitleUpdate );
		api.preview.bind( 'justwpforms-form-dom-update', handlers.formDomUpdate );
		api.preview.bind( 'justwpforms-form-part-add', handlers.formPartAdd );
		api.preview.bind( 'justwpforms-form-part-remove', handlers.formPartRemove );
		api.preview.bind( 'justwpforms-form-parts-sort', handlers.formPartsSort );
		api.preview.bind( 'justwpforms-form-part-refresh', handlers.formPartRefresh );
		api.preview.bind( 'justwpforms-form-parts-refresh', handlers.formPartsRefresh );
		api.preview.bind( 'justwpforms-form-part-disable', handlers.formPartDisable );
		api.preview.bind( 'justwpforms-form-parts-disable', handlers.formPartsDisable );
		api.preview.bind( 'justwpforms-form-partial-html-fetch', handlers.partialHtmlFetch );
		api.preview.bind( 'justwpforms-form-partial-dom-update', handlers.partialDomUpdate );
		api.preview.bind( 'justwpforms-form-partial-disable', handlers.partialDisable );
		api.preview.bind( 'justwpforms-form-partial-remove', handlers.partialRemove );
		api.preview.bind( 'justwpforms-part-dom-update', handlers.partDomUpdate );
		api.preview.bind( 'justwpforms-css-variable-update', handlers.cssVariableUpdate );
		api.preview.bind( 'justwpforms-form-class-update', handlers.formClassUpdate );
		api.preview.bind( 'justwpforms-form-class-updated', handlers.formClassUpdated );
		api.preview.bind( 'justwpforms-custom-css-updated', handlers.customCSSUpdated );
		api.preview.bind( 'justwpforms-form-recaptcha-update', handlers.recaptchaUpdate );

		// Bind DOM handlers
		$( document.body ).on(
			'click',
			'.justwpforms-block-editable--part .customize-partial-edit-shortcut',
			handlers.pencilPartClick
		);

		$( document.body ).on(
			'click',
			'.justwpforms-block-editable--partial .customize-partial-edit-shortcut',
			handlers.pencilPartialClick
		);

		$( '.justwpforms-ask-link, .justwpforms-notice p a' ).on( 'click', function() {
			window.open( $( this ).attr( 'href' ) );
		} );

		$( '.justwpforms-notice a.justwpforms-dismiss-notice' ).on( 'click', function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var $parent = $target.parents( '.justwpforms-notice' ).first();
			var id = $parent.attr( 'id' ).replace( 'justwpforms-notice-', '' );
			var nonce = $parent.data( 'nonce' );

			$.post( settings.ajaxurl, {
					action: 'justwpforms_hide_notice',
					nid: id,
					nonce: nonce
				}
			);

			$parent.fadeOut();
		}),

		// Silence unwanted events
		$( document.body ).on( 'click', 'button', handlers.silenceEvent );
		$( document.body ).on( 'click', 'button[type=submit]', handlers.silenceEvent );
		$( '.justwpforms-form' ).on( 'submit', handlers.silenceEvent );
	}

	$( function() {
		handlers.init();
		handlers.bind();
		api.preview.send( 'justwpforms-preview-ready' );
	} );

} )( jQuery, _, Backbone, wp.customize, _justwpformsPreviewSettings );
