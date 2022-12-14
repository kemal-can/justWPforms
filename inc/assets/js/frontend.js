( function( $, settings ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.base = {
		init: function() {
			this.type = this.$el.data( 'justwpforms-type' );
			this.$input = $( 'input, textarea, select', this.$el );

			this.$input.on( 'keyup change', this.triggerChange.bind( this ) );
			this.$input.on( 'blur', this.onBlur.bind( this ) );
			this.$input.on( 'focus', this.onInputFocus.bind( this ) );

			this.onBlur();
		},

		getType: function() {
			return this.type;
		},

		onInputFocus: function() {
			this.$el.addClass( 'focus' );
		},

		onBlur: function() {
			if ( this.$el.is( '.justwpforms-part--label-as_placeholder' ) ) {
				if ( this.isFilled() ) {
					this.$el.addClass( 'justwpforms-part--filled' );
				} else {
					this.$el.removeClass( 'justwpforms-part--filled' );
				}
			}

			this.$el.removeClass( 'focus' );
		},

		triggerChange: function( data ) {
			this.$el.trigger( 'justwpforms-change', data );
		},

		isRequired: function() {
			var isRequired = (
				this.$el.is( ':visible' )
				&& this.$el.is( '[data-justwpforms-required]' )
			);

			return isRequired;
		},

		isFilled: function() {
			var filledInputs = this.$input.filter( function() {
				var $input = $( this );
				var hasValue = false;

				if ( $input.is( '[type=checkbox]' ) || $input.is( '[type=radio]' ) ) {
					hasValue = $input.is( ':checked' );
				} else {
					hasValue = '' !== $input.val();
				}

				return hasValue;
			} );

			return filledInputs.length > 0;
		},

		serialize: function() {
			var serialized = this.$input.map( function( i, input ) {
				var $input = $( input );
				var keyValue = {
					name: $input.attr( 'name' ),
					value: $input.val(),
				};

				if ( $input.is( '[type=checkbox]' ) || $input.is( '[type=radio]' ) ) {
					if ( ! $input.is( ':checked' ) ) {
						return;
					}
				}

				return keyValue;
			} ).toArray();

			return serialized;
		},

		isValid: function() {
			var valid = true;

			var type = this.$el.data( 'justwpforms-type' );

			if ( ! this.$input ) {
				return valid;
			}

			if ( this.isRequired() ) {
				valid = valid && this.isFilled();
			}

			return valid;
		},

		destroy: function() {
			var $parts = $( '[data-justwpforms-type]', this.$form );

			$parts.each( function() {
				$( this ).trigger( 'justwpforms.detach' );
			} );

			this.$el.data( 'justwpformPart', false );
		}
	}

	justwpforms.wrapPart = function( $part, $form ) {
		var type = $part.data( 'justwpforms-type' );
		var partMethods = justwpforms.parts.base;

		if ( justwpforms.parts[type] ) {
			partMethods = $.extend( {}, justwpforms.parts.base, justwpforms.parts[type] );
		}

		$part.justwpformPart( partMethods, {
			form: $form,
		} );
	}

	justwpforms.Form = function( el ) {
		this.el = el;
		this.$el = $( this.el );
		this.$form = $( 'form', this.$el );
		this.$parts = $( '[data-justwpforms-type]', this.$form );
		this.$submits = $( '[type="submit"]', this.$form );
		this.$submit = $( '[type="submit"]', this.$form );
		this.$submitLinks = $( 'button.submit', this.$form );
		this.$step = $( '[name="justwpforms_step"]', this.$form );

		this.init();
	}

	justwpforms.Form.prototype = {
		init: function() {
			var $form = this.$form;
			var $parts = $( '[data-justwpforms-type]', this.$form );

			$parts.each( function() {
				var $part = $( this );
				var type = $part.data( 'justwpforms-type' );

				justwpforms.wrapPart( $part, $form );
			} );

			$( '[name="client_referer"]', this.$form ).val( window.location.href );

			this.$el.trigger( 'justwpforms-change' );
			this.$el.trigger( 'justwpforms-init' );

			// Reset in case of previous initialization
			this.$form.off( 'submit' );
			this.$submit.off( 'click' );
			this.$submitLinks.off( 'click' );

			this.$form.on( 'submit', this.submit.bind( this ) );
			this.$submit.on( 'click', this.buttonSubmit.bind( this ) );
			this.$submitLinks.on( 'click', this.linkSubmit.bind( this ) );
			this.$el.on( 'justwpforms-scrolltop', this.onScrollTop.bind( this ) );
			this.$el.on( 'click', '.justwpforms-print-submission', this.printSubmission.bind( this ) );
			this.$el.on( 'click', '.justwpforms-redirect-to-page', this.redirectNow.bind( this ) );
		},

		detach: function() {
			this.$el.off( 'justwpforms-change' );
			this.$el.off( 'justwpforms-scrolltop' );
			var $parts = $( '[data-justwpforms-type]', this.$form );
			$parts.remove();
		},

		serialize: function( submitEl ) {
			var action = $( '[name=action]', this.$form ).val();
			var clientReferer = $( '[name="client_referer"]', this.$form ).val();
			var form_id = $( '[name=justwpforms_form_id]', this.$form ).val();
			var step = this.$step.val();
			var randomSeed = $( '[name=justwpforms_random_seed]', this.$form ).val();

			var formData = [
				{ name: 'action', value: action },
				{ name: 'justwpforms_client_referer', value: clientReferer },
				{ name: 'justwpforms_form_id', value: form_id },
				{ name: 'justwpforms_step', value: step },
				{ name: 'justwpforms_random_seed', value: randomSeed },
			];

			var honeypotNames = [ 'single_line_text', 'multi_line_text', 'number' ];

			for (var h = 0; h < honeypotNames.length; h ++) {
				var inputName = form_id + '-' + honeypotNames[h];
				var $input = $( '[name=' + inputName + ']' );

				if ( $input.length ) {
					formData.push( {
						name: inputName,
						value: $input.val(),
					} );

					break;
				}
			}

			var $parts = $( '[data-justwpforms-type]', this.$form );
			var partData = $parts.map( function( i, part ) {
				return $( part ).justwpformPart( 'serialize' );
			} )
			.toArray()
			.filter( function( entry ) {
				return null !== entry.name && undefined !== entry.name;
			} );

			var data = formData.concat( partData );
			var params = new URLSearchParams();
			
			data.forEach( function( entry ) {
				params.append( entry.name, entry.value );
			} );

			var hash = justwpforms.Antispam.getHash( data );

			params.append( 'hash', hash );
			
			var platformInfo = justwpforms.Antispam.getPlatformInfo();

			for ( const [key, value] of Object.entries( platformInfo ) ) {
				params.append( key , value );
			}
			
			params = params.toString();

			return params;
		},

		buttonSubmit: function( e ) {
			if ( e.target.hasAttribute( 'data-step' ) ) {
				this.$step.val( e.target.getAttribute( 'data-step' ) );
			}
		},

		linkSubmit: function( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();

			if ( e.target.hasAttribute( 'data-step' ) ) {
				this.$step.val( e.target.getAttribute( 'data-step' ) );
			}

			this.$form.trigger( 'submit' );
		},

		submit: function( e ) {
			e.preventDefault();

			this.$form.addClass( 'justwpforms-form--submitting' );
			this.$submits.prop( 'disabled', true );

			var async = ( 'undefined' === typeof this.$form.attr( 'data-justwpforms-redirect-blank' ) );

			$.ajax( {
				type: 'post',
				async: async,
				data: this.serialize( e.target ),
			} ).done( this.onSubmitComplete.bind( this ) );
		},

		onSubmitComplete: function( response ) {
			this.$form.trigger( 'justwpforms.submitted', response );

			if ( ! response.data ) {
				return false;
			}

			if ( response.data.html ) {
				var $el = $( response.data.html );
				var $parts = $( '[data-justwpforms-type]', this.$form );

				$parts.each( function() {
					$( this ).trigger( 'justwpforms.detach' );
				} );

				this.detach();

				this.$el.replaceWith( $el );
				this.$el = $el;
				this.$el.justwpform();

				var $form = $( 'form', this.$el );

				// User filterable
				if ( $form.attr( 'data-justwpforms-scroll-disabled' ) ) {
					return;
				}

				var elemCoordinates = this.$el.get( 0 ).getBoundingClientRect();

				if( elemCoordinates.top < 0 ) {
					var elTopOffset = this.$el.offset().top;
					var $notices = $( '.justwpforms-message-notices', this.$el );

					// User filterable
					var increment = $form.attr( 'data-justwpforms-scroll-offset' );

					if ( increment ) {
						increment = parseInt( increment, 10 );
						elTopOffset += increment;
					}

					this.$el.trigger( 'justwpforms-scrolltop', elTopOffset );
				}

				if ( response.data.printable_data && $( '.justwpforms-print-submission', this.$el ).length > 0 ) {

					var $submissionLinks = $( '.justwpforms-form-links', this.$el );
					$('<iframe>', {
						srcdoc: response.data.printable_data,
					    class: 'justwpforms-printable-submission-frame',
					    css: {
					        display: 'none',
					    }
					} ).appendTo( $submissionLinks );
				}
			}

			if ( true === response.success && response.data.redirect ) {
				setTimeout(function(){
		            window.location.href = response.data.redirect;
					return false;
	         	}, ( response.data.redirect_after * 1000 ) );
			}
		},

		onScrollTop: function( e, offset ) {
			if ( e.isDefaultPrevented() ) {
				return;
			}

			$( 'html, body' ).animate( {
				scrollTop: offset + 'px'
			}, 500 );
		},

		printSubmission: function( e ) {
			e.preventDefault();

			$('.justwpforms-printable-submission-frame', this.$el ).get(0).contentWindow.print();
		},

		redirectNow: function( e ) {
			e.preventDefault();

			window.location.href = $( e.target ).data( 'url' );
		},
	}

	justwpforms.Part = function( el ) {
		this.el = el;
		this.$el = $( this.el );
	}

	$.fn.justwpformPart = function( method ) {
		var args = arguments;

		if ( 'object' === typeof method ) {
			var part = new justwpforms.Part( this );
			$.extend( part, method );
			$( this ).data( 'justwpformPart', part );
			part.init.apply( part, Array.prototype.slice.call( arguments, 1 ) );
		} else {
			var instance = $( this ).data( 'justwpformPart' );

			if ( instance && instance[method] ) {
				return instance[method].apply( instance, Array.prototype.slice.call( arguments, 1 ) );
			}
		}
	}

	$.fn.justwpform = function ( method ) {
		this.each(function () {
			if ( ! method ) {
				$.data( this, 'justwpform', new justwpforms.Form( this, arguments ) );
			} else {
				var instance = $.data( this, 'justwpform' );

				if ( instance && instance[method] ) {
					return instance[ method ].apply( instance, Array.prototype.slice.call( arguments, 1 ) );
				}
			}
		} );
	}

	var Sessions = function() {
	};

	Sessions.prototype.bind = function() {
		$( window ).on( 'visibilitychange', this.onUnload.bind( this ) );
	};

	Sessions.prototype.onUnload = function( e ) {
		var data = new FormData();

		data.append( 'action', settings.actionAbandon );

		if( typeof justwpforms.formSessionWatcher !== 'undefined' &&
			 ! $.isEmptyObject( justwpforms.formSessionWatcher.abandonAlerts ) ) {
			data.append( 'sessions', JSON.stringify( justwpforms.formSessionWatcher.abandonAlerts ) );
		}

		navigator.sendBeacon( settings.ajaxUrl, data );
	};

	justwpforms.sessionWatcher = null;

	justwpforms.scripts = {
		fetch: function( slug, url, callback ) {
			if ( justwpforms.scripts.cache[slug] ) {
				return callback();
			}

			$.getScript( url, function() {
				justwpforms.scripts.cache[slug] = true;

				return callback();
			} );
		},
		cache: {},
	};

	$( function() {
		$( '.justwpforms-form' ).justwpform();

		justwpforms.sessionWatcher = new Sessions();
		justwpforms.sessionWatcher.bind();
	} );

} )( jQuery, _justwpformsSettings );
