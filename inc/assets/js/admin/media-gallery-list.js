( function( $, settings ) {

	$( function() {
		var $option = $( '<option value="justwpforms">' + settings.label + '</option>' );

		var $attachment_fiter = $( '#attachment-filter' );

		$attachment_fiter.children().last().before( $option );

		if ( settings.selected ) {
			$option.prop( 'selected', true );
		}

		if ( 'justwpforms' == $attachment_fiter.val() ) {
				$( '#forms-filter' ).show();
		}

		$( document ).on( 'change', '#attachment-filter', function( e ) {
			var $filter = $( e.target );
			var value   = $filter.val();

			if ( 'justwpforms' == value ) {
				$( '#forms-filter' ).show();
			} else {
				$( '#forms-filter' ).hide();
			}
		} );
	} );

} )( jQuery, _justwpformsAdminMediaSettings );