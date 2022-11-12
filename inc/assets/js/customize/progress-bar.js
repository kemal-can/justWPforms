( function( $, _, Backbone, api, settings, progressBarSettings ) {

	var FormBuild = justwpforms.classes.views.FormBuild;
	var FormStyle = justwpforms.classes.views.FormStyle;
	var Previewer = justwpforms.previewer;

	var justwpformsProgressBar = function() {
		this.totalSteps = 0;
	}

	justwpformsProgressBar.prototype.init = function() {
		var pageBreaks = justwpforms.form.get( 'parts' ).where( { type: 'page_break' } ).length;
		this.totalSteps = pageBreaks;
	}

	justwpformsProgressBar.prototype.refresh = function() {
		var self = this;
		var pageBreaks = justwpforms.form.get( 'parts' ).where( { type: 'page_break' } ).length;

		if ( 1 < this.totalSteps && this.totalSteps === pageBreaks ) {
			justwpforms.utils.fetchPartialHtml( 'form-steps-progress', function( response ) {
				var data = {
					selector: '[data-partial-id=form-steps-progress]',
					options: {
						after: '[data-partial-id="title"]'
					},
					html: response
				};

				justwpforms.previewSend( 'justwpforms-form-partial-html-fetch', data );
			} );
		}
	}

	justwpformsProgressBar.prototype.addStep = function() {
		this.totalSteps++;

		this.refresh();
	}

	justwpformsProgressBar.prototype.removeStep = function() {
		this.totalSteps--;

		this.refresh();
	}

	justwpformsProgressBar.prototype.unload = function() {
		var data = {
			partial: 'form-steps-progress'
		};

		justwpforms.previewSend( 'justwpforms-form-partial-remove', data );
	}

	justwpformsProgressBar.prototype.getTotalSteps = function() {
		return this.totalSteps;
	}

	justwpforms.classes.views.FormBuild = FormBuild.extend( {
		ready: function() {
			FormBuild.prototype.ready.apply( this, arguments );
		},

		onPartAdd: function( type, options ) {
			FormBuild.prototype.onPartAdd.apply( this, arguments );

			if ( 'page_break' !== type ) {
				return;
			}

			var form = justwpforms.form;
			var parts = justwpforms.form.get( 'parts' );
			var $firstPart = $( '.justwpforms-form-widgets .justwpforms-widget:first-child', this.$el );
			var firstPartModel = parts.findWhere({ id: $firstPart.attr( 'data-part-id' ) });

			if ( 'page_break' !== firstPartModel.get( 'type' ) ) {
				var partModel = justwpforms.factory.model(
					{ type: 'page_break' },
					{ collection: form.get( 'parts' ) },
				);

				var options = {
					index: 0,
					at: 0,
					expand: true
				};

				partModel.set( 'is_first', true );
				partModel.set( 'label', progressBarSettings.i18n.first_label );

				form.get( 'parts' ).add( partModel, options );
				form.trigger( 'change', partModel );

				partModel.fetchHtml( function( response ) {
					var data = {
						html: response,
						after: -1
					};

					justwpforms.previewSend( 'justwpforms-form-part-add', data );
				} );
			}
		},

		onPartSortStop: function() {
			FormBuild.prototype.onPartSortStop.apply( this, arguments );

			justwpforms.progressBar.refresh();
		}
	} );

	justwpforms.classes.views.FormStyle = FormStyle.extend( {
		applyConditionClasses: function() {
			FormStyle.prototype.applyConditionClasses.apply( this, arguments );

			var hasProgressBar = false;

			if ( justwpforms.form.get( 'parts' ).where( { type: 'page_break' } ).length >= 2 ) {
				hasProgressBar = true;
			}

			if ( hasProgressBar ) {
				this.$el.addClass( 'has-progress-bar' );
			}
		}
	} );

	api.bind( 'ready', function() {
		justwpforms.progressBar = new justwpformsProgressBar();
		justwpforms.progressBar.init();
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings, _justwpformsProgressBarSettings );
