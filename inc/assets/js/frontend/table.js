( function( $ ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.table = {
		isFilled: function() {
			var $rows = $( '.justwpforms-table__row.justwpforms-table__row--body', this.$el );

			var $filledRows = $rows.filter( function() {
				var $row = $( this );
				var $input = $( 'input:checked', $row );

				return $input.length > 0;
			} );

			return $rows.length === $filledRows.length;
		},
	};

} )( jQuery );
