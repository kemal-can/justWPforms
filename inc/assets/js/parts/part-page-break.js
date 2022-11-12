( function( $, _, Backbone, api, settings ) {

	justwpforms.classes.models.parts.page_break = justwpforms.classes.models.Part.extend( {
		defaults: function() {
			return _.extend(
				{},
				settings.formParts.page_break.defaults,
				_.result( justwpforms.classes.models.Part.prototype, 'defaults' ),
			);
		},
	} );

	var Previewer = justwpforms.previewer;

	justwpforms.classes.views.parts.page_break = justwpforms.classes.views.Part.extend( {
		template: '#customize-justwpforms-page-break-template',

		initialize: function() {
			justwpforms.classes.views.Part.prototype.initialize.apply(this, arguments);

			this.listenTo( this, 'ready', this.onReady );
			this.listenTo( this.model, 'change:label', this.onPageBreakLabelChange );
			this.listenTo( this.model, 'change:css_class', this.onCSSClassChange );
			this.listenTo( this.model.collection, 'remove', this.onPartModelRemove );
		},

		onReady: function() {
			this.addNoSortableClass();

			if ( justwpforms.hasOwnProperty( 'progressBar' ) ) {
				justwpforms.progressBar.addStep();
			}
		},

		addNoSortableClass: function() {
			if ( 0 === this.$el.index() ) {
				this.$el.addClass( 'no-sortable' );
			}
		},

		onPageBreakLabelChange: function( model, value ) {
			var data = {
				id: this.model.id,
				callback: 'onPageBreakLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );

			var data = {
				id: model.id,
				partialSelector: '[data-partial-id=form-steps-progress]',
				callback: 'updatePageBreakProgressBarCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-partial-dom-update', data );
		},

		onCSSClassChange: function( model, value, options ) {
			var data = {
				id: model.id,
				partialSelector: '[data-partial-id=form-steps-progress]',
				callback: 'onPageBreakCSSClassChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-partial-dom-update', data );
		},

		onPartModelRemove: function( model, collection ) {
			var type = model.get( 'type' );

			if ( 'page_break' === type ) {
				justwpforms.progressBar.removeStep();
			}

			var pageBreakModels = collection.where( { type: 'page_break' } );

			if ( 1 === pageBreakModels.length ) {
				collection.remove( pageBreakModels );
			}

			if ( ! pageBreakModels.length ) {
				justwpforms.progressBar.unload();
			}
		}
	} );

	justwpforms.previewer = _.extend( {}, Previewer, {
		onPageBreakLabelChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var $label = this.$( '.justwpforms-page-break .label', $part );

			$label.text( part.get( 'label' ) );
		},

		updatePageBreakProgressBarCallback: function( partialSelector, stepID, $partial ) {
			var part = justwpforms.form.get( 'parts' ).get( stepID );
			var $step = this.$( '[data-justwpforms-step-id=' + stepID + ']', $partial );
			var $label = this.$( '.justwpforms-form-progress__step-title', $step );

			$label.text( part.get( 'label' ) );
		},

		onPageBreakCSSClassChangeCallback: function( partialSelector, stepID, $partial ) {
			var part = justwpforms.form.get( 'parts' ).get( stepID );
			var $step = this.$( '[data-justwpforms-step-id=' + stepID + ']', $partial );

			var previousClass = part.previous( 'css_class' );
			var currentClass = part.get( 'css_class' );

			$step.removeClass( previousClass );
			$step.addClass( currentClass );
		},
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings );
