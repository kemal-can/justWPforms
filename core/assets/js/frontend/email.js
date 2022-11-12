( function( $ ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.email = {
		init: function() {
			this.type = this.$el.data( 'justwpforms-type' );
			this.$input = $( 'input', this.$el );
			this.$visualInput = $( 'input[type=email]', this.$el );

			this.$input.on( 'keyup', this.triggerChange.bind( this ) );
			this.$input.on( 'change', this.triggerChange.bind( this ) );
			this.$input.on( 'focus', this.onInputFocus.bind( this ) );
			this.$visualInput.on( 'blur', this.onBlur.bind( this ) );
		}
	};

} )( jQuery );
