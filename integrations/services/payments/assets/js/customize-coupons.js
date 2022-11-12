( function( $, _, Backbone, api, settings ) {

	var FormMessages = justwpforms.classes.views.FormMessages;

	justwpforms.classes.views.FormMessages = FormMessages.extend ( {

		events: _.extend( {}, FormMessages.prototype.events, {
			'keyup [data-attribute="coupon_label"]' : 'onCouponLabelChange',
			'keyup [data-attribute="coupon_apply_label"]' : 'onCouponApplyLabelChange',
		} ),

		applyMsgConditionClasses: function() {
			var self = this;

			var acceptCoupons = justwpforms.form.get( 'parts' ).findWhere( {
				accept_coupons: 1
			} );

			if ( acceptCoupons ) {
				self.$el.addClass( 'accept-coupons' );
			}
			FormMessages.prototype.applyMsgConditionClasses.apply( this, arguments );
		},

		onCouponLabelChange: function() {
			var data = {
				callback: 'onCouponLabelChangeCallback',
			}

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onCouponApplyLabelChange: function() {
			var data = {
				callback: 'onCouponApplyLabelChangeCallback',

			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},
	} );

	var PaymentsView = justwpforms.classes.views.parts.payments;

	justwpforms.classes.views.parts.payments = PaymentsView.extend( {
		events: _.extend( {}, PaymentsView.prototype.events, {} ),

		initialize: function() {
			PaymentsView.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:accept_coupons', this.onAcceptCouponChange );
		},

		onAcceptCouponChange: function( e ) {
			var model = this.model;

			this.model.set( 'accept_coupons', this.model.get( 'accept_coupons' ) );

			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		}
	} );

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onCouponLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'coupon_label' );

			$( '.justwpforms-payments__coupon label.justwpforms-part__label span.label', $form ).text( label );
		},
		onCouponApplyLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'coupon_apply_label' );

			$( '.justwpforms-payments__coupon #justwpforms_coupon_apply', $form ).text( label );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );