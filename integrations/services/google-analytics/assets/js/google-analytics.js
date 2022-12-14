( function( $, settings ) {

	justwpforms.parts = justwpforms.parts || {};

	justwpforms.parts.google_analytics = {
		init: function() {
			this.$userAgent = $( '[name$="user_agent"]', this.$el );
			this.$pageURL = $( '[name$="page_url"]', this.$el );
			this.$pageTitle = $( '[name$="page_title"]', this.$el );
			this.$input = $( 'input', this.$el );

			this.$userAgent.val( navigator.userAgent );
			this.$pageURL.val( window.location.href );
			this.$pageTitle.val( document.title );
		},
	};

	var GoogleAnalytics = function() {
		this.forms = {};
	};

	GoogleAnalytics.prototype.bind = function() {
		$( window ).on( 'unload', this.onUnload.bind( this ) );
	};

	GoogleAnalytics.prototype.onUnload = function( e ) {
		for ( var formId in this.forms ) {
			var form = this.forms[formId];
			var data = new FormData();

			var formId = $( '[name="justwpforms_form_id"]', form.$el ).val();
			var $fields = $( '[name^="justwpforms_ga_"]', form.$el );

			data.append( 'action', settings.action );
			data.append( 'form_id', formId );

			$( '[name^="justwpforms_ga_"]', form.$el ).each( function() {
				data.append( this.name, this.value );
			} );

			navigator.sendBeacon( settings.ajaxUrl, data );
		};
	};

	justwpforms.googleAnalytics = null;

	$( function() {
		justwpforms.googleAnalytics = new GoogleAnalytics();
		justwpforms.googleAnalytics.bind();
	} );

} )( jQuery, _justwpformsSettings.googleAnalytics );
