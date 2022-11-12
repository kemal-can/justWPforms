( function( $ ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.rating = {
		init: function() {
			this.type = this.$el.data( 'justwpforms-type' );
			this.$input = $( 'input', this.$el );

			this.$input.on( 'change', this.triggerChange.bind( this ) );
			this.$input.on( 'blur', this.onBlur.bind( this ) );
		},

		isFilled: function() {
			return '0' !== $( ':checked', this.$el ).val();
		},
	};

} )( jQuery );
