( function( $, Cookies ) {
	$( function() {
		$( document ).on( 'justwpforms.submitted', function( e, response ) {
			if ( ! response.data.paypal || ! response.data.paypal.paymentId ) {
				return;
			}

			var $form = $( e.target );
			var form_id = $( '[name=justwpforms_form_id]', $form ).val();

			Cookies.set( 'justwpforms_checkout', {
				id: response.data.paypal.paymentId,
				status: window.location.href,
				form_id: form_id,
			} );
		} );
	} );
} )( jQuery, Cookies );