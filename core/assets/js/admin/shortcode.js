( function( window, views, $, settings ) {

	var justwpforms = {
		template: wp.media.template( 'justwpforms-shortcode' ),

		getTitle: function( id ) {
			var title = 'Form';
			
			if ( settings.forms[id] ) {
				title += ': ' + settings.forms[id].post_title;
			}

			return title;
		},

		getId: function() {
			return this.shortcode.attrs.named.id;
		},

		initialize: function() {
			var id = this.getId();
			var title = this.getTitle( id );

			this.render( this.template( {
				id: id,
				title: title,
			} ) );
		},

		edit: function() {
			var id = this.getId();
			var returnUrl = encodeURIComponent( document.location.href );
			var link = settings.editLink.replace( 'ID', id ).replace( 'URL', returnUrl );

			document.location.href = link;
		},
	}

	views.register( 'justwpforms', _.extend( {}, justwpforms ) );
	views.register( 'form', _.extend( {}, justwpforms ) );

} )( window, window.wp.mce.views, window.jQuery, _justwpformsAdmin );
