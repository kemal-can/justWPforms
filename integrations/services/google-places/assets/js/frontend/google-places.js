( function( $, settings ) {
	$( function() {
		var justwpformsGooglePlaces = function( el ) {
			this.el = el;
			this.$el = $( el );

			this.init();
		}

		justwpformsGooglePlaces.prototype.init = function() {
			this.$input = $( 'input', this.$el );
			this.mode = this.$el.attr('data-mode');
			this.autocomplete = this.$el.data('google-autocomplete');


			var $input = $( '[data-serialize]', this.$el );
			var $visualInput = $( '.justwpforms-part--address__autocomplete', this.$el );
			var $select = $( '.justwpforms-custom-select-dropdown', this.$el );

			var autocompleteOptions = {
				delay: 500,
				source: settings.actionAutocomplete,
				url: settings.url,
			};

			$visualInput.justwpformsSelect( {
				$input: $input,
				$select: $select,
				searchable: 'autocomplete',
				autocompleteOptions: autocompleteOptions
			});
		}

		$.fn.justwpformsGooglePlaces = function( method ) {
			if ( 'string' === typeof method ) {
				var instance = $( this ).data( 'justwpformsGooglePlaces' );

				if ( instance && instance[method] ) {
					return instance[method].apply( instance, Array.prototype.slice.call( arguments, 1 ) );
				}
			} else {
				this.each( function() {
					$.data( this, 'justwpformsGooglePlaces', new justwpformsGooglePlaces( this ) );
				} );
			}
		}

		$( document ).on( 'justwpforms-part-address-init', '.justwpforms-part--address-googleplaces', function() {
			$( this ).justwpformsGooglePlaces();
		} );
	} );
} ) ( jQuery, _justwpformsSettings.googlePlaces );
