( function( $, settings ) {

	var justwpformsCoupon = function( el ) {
		this.el = el;
		this.$el = $( el );
		this.$form = $( 'form', this.$el );
		this.$input = $( '#justwpforms_payment_coupon', this.$form );
		this.$apply = $( '#justwpforms_coupon_apply', this.$form );
		this.$price = $( '.justwpforms-payments__price input[type="hidden"]', this.$form );
		this.$coupon = $( '.justwpforms-payments__coupon input[type="hidden"]', this.$form );

		this.init();
	}

	justwpformsCoupon.prototype.init = function() {
		this.$apply.on( 'click', this.applyCoupon.bind( this ) );
	},

	justwpformsCoupon.prototype.applyCoupon = function( e ) {
		e.preventDefault();

		var $input = this.$input;
		var c = $input.val();

		if ( '' === c ) {
			return false;
		}

		var $noticeWrap = $( '.justwpforms-coupon-notice', this.$form );
		var $noticeText = $( '.justwpforms-coupon-notice span', this.$form );
		var $priceText = $( '.justwpforms-payments__price .price', this.$form );
		var $price = this.$price;
		var $coupon = this.$coupon;

		var data = {
			action: settings.action,
			coupon: c,
			formid: $( '[name="justwpforms_form_id"]', this.$form ).val(),
			nonce: settings.nonce,
		}

		$noticeWrap.hide();
		$noticeWrap.removeClass( 'error' )
		$noticeWrap.html( '' );

		$.post( settings.ajaxurl, data, function( r ) {
			if ( r.success ) {
				$price.val( r.data.amount );
				$coupon.val( r.data.coupon );
				$input.val( '' );
				$priceText.addClass( 'strikethrough' );

				var $discountedText = $( '.discounted-price.coupons', this.$form );
				$discountedText.text( r.data.amount_display );
			} else {
				$noticeWrap.html( r.data.message );
				$noticeWrap.addClass( 'error' );
				$noticeWrap.show();
			}
		} );
	},


	$.fn.justwpformsCoupon = function( method ) {
		if ( 'string' === typeof method ) {
			var instance = $( this ).data( 'justwpformsCoupon' );

			if ( instance && instance[method] ) {
				return instance[method].apply( instance, Array.prototype.slice.call( arguments, 1 ) );
			}
		} else {
			this.each( function() {
				$.data( this, 'justwpformsCoupon', new justwpformsCoupon( this ) );
			} );
		}
	}

	$( document ).on( 'justwpforms-init', '.justwpforms-form', function( e ) {
		$( this ).justwpformsCoupon();
	} );

} )( jQuery, _justwpformsSettings.coupons );
