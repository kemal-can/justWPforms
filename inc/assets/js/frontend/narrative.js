( function( $ ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.narrative = {
		isFilled: function() {
			var emptyInputs = this.$input.filter( function() {
				return '' === $( this ).val();
			} );

			return 0 === emptyInputs.length;
		},
	};

} )( jQuery );