( function( $, _, Backbone, api, settings, formIntegrations ) {

	var justwpforms;
	var classes = {};
	classes.models = {};
	classes.models.parts = {};
	classes.collections = {};
	classes.views = {};
	classes.views.parts = {};
	classes.routers = {};

	classes.models.Form = Backbone.Model.extend( {
		idAttribute: 'ID',
		defaults: settings.form,

		getPreviewUrl: function() {
			var previewUrl =
				settings.baseUrl +
				'?post_type=' + this.get( 'post_type' ) +
				'&p=' + this.id +
				'&preview=true';

			return previewUrl;
		},

		isNew: function() {
			return ( 0 == this.id );
		},

		initialize: function( attrs, options ) {
			Backbone.Model.prototype.initialize.apply( this, arguments );

			this.attributes.parts = new classes.collections.Parts( this.get( 'parts' ), options );

			this.changeDocumentTitle();
		},

		toJSON: function() {
			var json = Backbone.Model.prototype.toJSON.apply( this, arguments );
			json.parts = json.parts.toJSON();

			return json;
		},

		save: function( options ) {
			var self = this;
			options = options || {};

			var request = wp.ajax.post( 'justwpforms-update-form', _.extend( {
				'justwpforms-nonce': api.settings.nonce.justwpforms,
				justwpforms: 1,
				form_id: this.id,
				wp_customize: 'on',
			}, {
				form: JSON.stringify( this.toJSON() )
			} ) );

			request.done( function( response ) {
				if ( self.isNew() ) {
					justwpforms.updateFormID( response.ID );
				}

				if ( justwpforms.previewLoaded ) {
					api.previewer.refresh();
				}

				self.trigger( 'save', response );

				if ( options.success ) {
					options.success( response );
				}
			} );

			request.fail( function( response ) {
				// noop
			} );
		},

		changeDocumentTitle: function() {
			var title = $( 'title' ).text();
			var newTitle = '';

			newTitle = title.replace( title.substring( 0, title.indexOf( ':' ) ), 'Form' );
			$( 'title' ).text( newTitle );

			var formTitle = this.get( 'post_title' );
			var titleTemplate = 'Form';

			if ( formTitle ) {
				titleTemplate = titleTemplate + ': ' + formTitle;
			}

			_wpCustomizeSettings.documentTitleTmpl = titleTemplate;
		},

		fetchCustomCSS: function( success ) {
			var data = {
				action: 'justwpforms-get-custom-css',
				'justwpforms-nonce': api.settings.nonce.justwpforms,
				justwpforms: 1,
				wp_customize: 'on',
				form_id: justwpforms.form.id,
				form: JSON.stringify( this.toJSON() ),
			};

			var request = $.ajax( ajaxurl, {
				type: 'post',
				dataType: 'text',
				data: data,
			} );

			request.done( success );
		},
	} );

	classes.models.Part = Backbone.Model.extend( {
		initialize: function( attributes ) {
			Backbone.Model.prototype.initialize.apply( this, arguments );

			if ( ! this.id ) {
				var id = justwpforms.utils.uniqueId( this.get( 'type' ) + '_', this.collection );
				this.set( 'id', id );
			}
		},

		fetchHtml: function( success ) {
			var data = {
				action: 'justwpforms-form-part-add',
				'justwpforms-nonce': api.settings.nonce.justwpforms,
				justwpforms: 1,
				wp_customize: 'on',
				form_id: justwpforms.form.id,
				form: JSON.stringify( justwpforms.form.toJSON() ),
				part: this.toJSON(),
			};

			var request = $.ajax( ajaxurl, {
				type: 'post',
				dataType: 'html',
				data: data
			} );

			justwpforms.previewSend( 'justwpforms-form-part-disable', {
				id: this.get( 'id' ),
			} );

			request.done( success );
		}
	} );

	classes.collections.Parts = Backbone.Collection.extend( {
		model: function( attrs, options ) {
			var model = PartFactory.model( attrs, options );
			return model;
		},

		fetchHtml: function( success ) {
			var data = {
				action: 'justwpforms-form-parts-add',
				'justwpforms-nonce': api.settings.nonce.justwpforms,
				justwpforms: 1,
				wp_customize: 'on',
				form_id: justwpforms.form.id,
				form: JSON.stringify( justwpforms.form.toJSON() ),
				parts: this.toJSON(),
			};

			var request = $.ajax( ajaxurl, {
				type: 'post',
				dataType: 'json',
				data: data
			} );

			justwpforms.previewSend( 'justwpforms-form-parts-disable', {
				ids: this.pluck( 'id' ),
			} );

			request.done( success );
		},
	} );

	var PartFactory = {
		model: function( attrs, options, BaseClass ) {
			BaseClass = BaseClass || classes.models.Part;
			return new BaseClass( attrs, options );
		},

		view: function( attrs, BaseClass ) {
			BaseClass = BaseClass || classes.views.Part;
			return new BaseClass( attrs );
		},
	};

	justwpforms = Backbone.Router.extend( {
		routes: {
			'build': 'build',
			'setup': 'setup',
			'email': 'email',
			'style': 'style',
			'messages': 'messages',
		},

		steps: [ 'build', 'setup', 'email', 'style', 'messages' ],
		previousRoute: '',
		currentRoute: 'build',
		savedStates: {
			'build': {
				'scrollTop': 0,
				'activePartIndex': -1
			},
			'setup': {
				'scrollTop': 0,
			},
			'email': {
				'scrollTop': 0,
			},
			'messages': {
				'scrollTop': 0,
			},
			'style': {
				'scrollTop': 0,
				'activeSection': ''
			},
			'messages': {
				'scrollTop': 0,
				'activeSection': ''
			}
		},
		form: false,
		previewLoaded: false,
		buffer: [],

		initialize: function( options ) {
			Backbone.Router.prototype.initialize( this, arguments );

			this.listenTo( this, 'route', this.onRoute );
		},

		start: function( options ) {
			this.parts = new Backbone.Collection();
			this.parts.reset( _( settings.formParts ).values() );
			this.form = new classes.models.Form( settings.form, { silent: true } );
			this.actions = new classes.views.Actions( { model: this.form } ).render();
			this.sidebar = new classes.views.Sidebar( { model: this.form } ).render();

			Backbone.history.start();
			api.previewer.previewUrl( this.form.getPreviewUrl() );

			this.initilizeGlobalControls();
		},

		initilizeGlobalControls: function() {
			if ( this.form.attributes.ID  == 0 ) {
				this.form.attributes.part_title_label_placement = 'above';
			}
		},

		flushBuffer: function() {
			if ( this.buffer.length > 0 ) {
				_.each( this.buffer, function( entry ) {
					api.previewer.send( entry.event, entry.data );
				} );

				this.buffer = [];
			}
		},

		previewSend: function( event, data, options ) {
			if ( justwpforms.previewer.ready ) {
				api.previewer.send( event, data );
			} else {
				justwpforms.buffer.push( {
					event: event,
					data: data,
				} );
			}
		},

		onRoute: function( segment ) {
			this.sidebar.steps.disable();

			var previousStepIndex = this.steps.indexOf( this.currentRoute ) + 1;
			var stepIndex = this.steps.indexOf( segment ) + 1;
			var direction = previousStepIndex < stepIndex ? 1: -1;
			var stepProgress = Math.round( stepIndex / ( this.steps.length ) * 100 );
			var childView;

			switch( segment ) {
				case 'setup':
					childView = new classes.views.FormSetup( { model: this.form } );
					break;
				case 'build':
					childView = new classes.views.FormBuild( { model: this.form } );
					break;
				case 'email':
					childView = new classes.views.FormEmail( { model: this.form } );
					break;
				case 'style':
					childView = new classes.views.FormStyle( { model: this.form } );
					break;
				case 'messages':
					childView = new classes.views.FormMessages( { model: this.form } );
					break;
			}

			this.previousRoute = this.currentRoute;
			this.currentRoute = segment;

			if ( 'style' !== this.previousRoute ) {
				this.savedStates[this.previousRoute]['scrollTop'] = this.sidebar.$el.scrollTop();
			}

			this.sidebar.doStep( {
				step: {
					slug: segment,
					index: stepIndex,
					progress: stepProgress,
					count: this.steps.length,
				},
				direction: direction,
				child: childView,
			} );

			this.sidebar.steps.enable();
		},

		forward: function() {
			var nextStepIndex = this.steps.indexOf( this.currentRoute ) + 1;
			nextStepIndex = Math.min( nextStepIndex, this.steps.length - 1 );
			var nextStep = this.steps[nextStepIndex];

			this.navigate( nextStep, { trigger: true } );
		},

		back: function() {
			var previousStepIndex = this.steps.indexOf( this.currentRoute ) - 1;
			previousStepIndex = Math.max( previousStepIndex, 0 );

			var previousStep = this.steps[previousStepIndex];
			this.navigate( previousStep, { trigger: true } );
		},

		updateFormID: function( id ) {
			var url = window.location.href.replace( /form_id=[\d+]/, 'form_id=' + id );
			window.location.href = url;
		},

		setup: function() {
			// noop
		},

		build: function() {
			// noop
		},

		style: function() {
			// noop
		},

	} );

	classes.views.Base = Backbone.View.extend( {
		events: {
			'mouseover [data-pointer]': 'onHelpMouseOver',
			'mouseout [data-pointer]': 'onHelpMouseOut',
		},

		pointers: {},

		initialize: function() {
			if ( this.template ) {
				this.template = _.template( $( this.template ).text() );
			}

			this.listenTo( this, 'ready', this.ready );

			// Capture and mute link clicks to avoid
			// hijacking Backbone router and breaking
			// Customizer navigation.
			this.delegate( 'click', '.justwpforms-stack-view a:not(.external)', this.muteLink );
		},

		ready: function() {
			// Noop
		},

		muteLink: function( e ) {
			e.preventDefault();
		},

		setupHelpPointers: function() {
			var $helpTriggers = $( '[data-pointer]', this.$el );
			var self = this;

			$helpTriggers.each( function() {
				var $trigger = $( this );
				var $control = $trigger.parents( '.customize-control' );

				var pointerId = $control.attr( 'id' );
				var $target = $control.find( '[data-pointer-target]' );

				var $pointer = $target.pointer( {
					pointerClass: 'wp-pointer justwpforms-help-pointer',
					content: $( 'span', $trigger ).html(),
					position: {
						edge: 'left',
						align: 'center',
					},
					open: function( e, ui ) {
						ui.pointer.css( 'margin-left', '-1px' );
					},
					close: function( e, ui ) {
						ui.pointer.css( 'margin-left', '0' );
					},
					buttons: function() {},
				} );

				self.pointers[pointerId] = $pointer;
			} );
		},

		onHelpMouseOver: function( e ) {
			var $target = $( e.target );
			var $control = $target.parents( '.customize-control' );
			var pointerId = $control.attr( 'id' );
			var $pointer = this.pointers[pointerId];

			if ( $pointer ) {
				$pointer.pointer( 'open' );
			}
		},

		onHelpMouseOut: function( e ) {
			var $target = $( e.target );
			var $control = $target.parents( '.customize-control' );
			var pointerId = $control.attr( 'id' );
			var $pointer = this.pointers[pointerId];

			if ( $pointer ) {
				$pointer.pointer( 'close' );
			}
		},

		unbindEvents: function() {
			// Unbind any listenTo handlers
			this.stopListening();
			// Unbind any delegated DOM handlers
			this.undelegateEvents()
			// Unbind any direct view handlers
			this.off();
		},

		remove: function() {
			this.unbindEvents();
			Backbone.View.prototype.remove.apply( this, arguments );
		},
	} );

	classes.views.Actions = classes.views.Base.extend( {
		el: '#customize-header-actions',
		template: '#justwpforms-customize-header-actions',

		events: {
			'click #justwpforms-save-button': 'onSaveClick',
			'click #justwpforms-close-link': 'onCloseClick',
			'onbeforeunload': 'onWindowClose',
		},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change', this.onFormChange );
			$( window ).bind( 'beforeunload', this.onWindowClose.bind( this ) );
		},

		render: function() {
			this.$el.html( this.template( {
				isNewForm: this.model.isNew()
			} ) );

			return this;
		},

		enableSave: function() {
			var $saveButton = $( '#justwpforms-save-button', this.$el );

			$saveButton.prop( 'disabled', false ).text( $saveButton.data( 'text-default' ) );
		},

		disableSave: function() {
			$( '#justwpforms-save-button', this.$el ).prop( 'disabled', true );
		},

		isDirty: function() {
			return $( '#justwpforms-save-button', this.$el ).is( ':enabled' );
		},

		onFormChange: function() {
			this.enableSave();
		},

		onCloseClick: function( e ) {
			if ( this.isDirty() ) {
				var message = $( e.currentTarget ).data( 'message' );

				if ( ! confirm( message ) ) {
					e.preventDefault();
					e.stopPropagation();

					return false;
				} else {
					$( window ).unbind( 'beforeunload' );
				}
			}
		},

		onWindowClose: function() {
			if ( this.isDirty() ) {
				return '';
			}
		},

		onSaveClick: function( e ) {
			e.preventDefault();

			var self = this;

			this.disableSave();

			this.model.save({
				success: function() {
					var $saveButton = $( '#justwpforms-save-button', this.$el );

					$saveButton.text( $saveButton.data('text-saved') );
				}
			});
		},
	} );

	classes.views.Sidebar = classes.views.Base.extend( {
		el: '.wp-full-overlay-sidebar-content',

		steps: null,
		current: null,
		previous: null,

		render: function( options ) {
			this.$el.empty();
			this.steps = new classes.views.Steps( { model: this.model } );

			return this;
		},

		doStep: function( options ) {
			var child = options.child.render();
			this.$el.append( child.$el );
			child.trigger( 'ready' );

			if ( this.current ) {
				this.previous = this.current;
				this.current = child;

				this.current.$el.show();
				this.previous.$el.hide();
				this.onStepComplete();
			} else {
				this.current = child;
				this.current.$el.show();
				this.steps.render( options );
			}
		},

		onStepComplete: function( options ) {
			this.previous.remove();
			this.steps.render( options );
			this.$el.scrollTop( justwpforms.savedStates[justwpforms.currentRoute]['scrollTop'] );
		}
	} );

	classes.views.Steps = classes.views.Base.extend( {
		el: '#justwpforms-steps-nav',
		template: '#justwpforms-form-steps-template',

		events: {
			'click .nav-tab': 'onStepClick',
		},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.disabled = false;
		},

		render: function( options ) {
			var data = _.extend( {}, options, { form: this.model.toJSON() } );
			this.$el.html( this.template( data ) );
			this.$el.show();
		},

		onStepClick: function( e ) {
			e.preventDefault();

			if ( this.disabled ) {
				return;
			}

			var $link = $( e.target );
			var stepID = $link.attr( 'data-step' );

			$( '.nav-tab', this.$el ).removeClass( 'nav-tab-active' );
			$link.addClass( 'nav-tab-active' );

			justwpforms.navigate( stepID, { trigger: true } );
		},

		disable: function() {
			this.disabled = true;
		},

		enable: function() {
			this.disabled = false;
		}

	} );

	classes.views.FormBuild = classes.views.Base.extend( {
		template: '#justwpforms-form-build-template',

		events: {
			'keyup #justwpforms-form-name': 'onNameChange',
			'click #justwpforms-form-name': 'onNameInputClick',
			'click .justwpforms-add-new-part': 'onPartAddButtonClick',
			'change #justwpforms-form-name': 'onNameChange',
			'global-attribute-set': 'onSetGlobalAttribute',
			'global-attribute-unset': 'onUnsetGlobalAttribute',
		},

		drawer: null,

		globalAttributes: {},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.partViews = new Backbone.Collection();

			this.listenTo( justwpforms, 'part-add', this.onPartAdd );
			this.listenTo( justwpforms, 'part-duplicate', this.onPartDuplicate );
			this.listenTo( this.model.get( 'parts' ), 'add', this.onPartModelAdd );
			this.listenTo( this.model.get( 'parts' ), 'remove', this.onPartModelRemove );
			this.listenTo( this.model.get( 'parts' ), 'change', this.onPartModelChange );
			this.listenTo( this.model.get( 'parts' ), 'reset', this.onPartModelsSorted );
			this.listenTo( this.partViews, 'add', this.onPartViewAdd );
			this.listenTo( this.partViews, 'remove', this.onPartViewRemove );
			this.listenTo( this.partViews, 'reset', this.onPartViewsSorted );
			this.listenTo( this.partViews, 'add remove reset', this.onPartViewsChanged );
			this.listenTo( this, 'sort-stop', this.onPartSortStop );
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );
			return this;
		},

		ready: function() {
			this.model.get( 'parts' ).each( function( partModel ) {
				this.addViewPart( partModel );
			}, this );

			$( '.justwpforms-form-widgets', this.$el ).sortable( {
				items: '> .justwpforms-widget:not(.no-sortable)',
				handle: '.justwpforms-part-widget-top',
				axis: 'y',
				tolerance: 'pointer',
				scroll: true,
				scrollSpeed: 5,

				stop: function( e, ui ) {
					this.trigger( 'sort-stop', e, ui );
				}.bind( this ),
			} );

			this.drawer = new classes.views.PartsDrawer();
			$( '.wp-full-overlay' ).append( this.drawer.render().$el );

			if ( -1 === justwpforms.savedStates.build.activePartIndex ) {
				$( '#justwpforms-form-name', this.$el ).trigger( 'focus' ).trigger( 'select' );
			}
		},

		onNameInputClick: function( e ) {
			var $input = $(e.target);

			$input.trigger( 'select' );
		},

		onNameChange: function( e ) {
			e.preventDefault();

			var value = $( e.target ).val();
			this.model.set( 'post_title', value );
			justwpforms.previewSend( 'justwpforms-form-title-update', value );
		},

		onPartAddButtonClick: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.drawer.toggle();
		},

		onPartAdd: function( type, options ) {
			var partModel = PartFactory.model(
				{ type: type },
				{ collection: this.model.get( 'parts' ) },
			);

			this.drawer.close();

			this.model.get( 'parts' ).add( partModel, options );
			this.model.trigger( 'change', this.model );

			partModel.fetchHtml( function( response ) {
				var data = {
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-add', data );
			} );
		},

		onPartDuplicate: function( part, options ) {
			var attrs = part.toJSON();
			delete attrs.id;

			if ( [ 'radio', 'checkbox', 'select' ].includes( part.get( 'type' ) ) ) {
				var duplicatePartId = justwpforms.utils.uniqueId( part.get( 'type' ) + '_', part.collection );

				for ( var o = 0; o < attrs.options.length; o ++ ) {
					attrs.options[o].id = attrs.options[o].id.replace( `${part.id}_`, `${duplicatePartId}_` );
				}
			}

			var duplicate = PartFactory.model(
				attrs,
				{ collection: this.model.get( 'parts' ) },
			);

			var index = this.model.get( 'parts' ).indexOf( part );
			var after = part.get( 'id' );
			options = options || {};
			options.at = index + 1;
			options.duplicateOf = part;

			this.model.get( 'parts' ).add( duplicate, options );
			this.model.trigger( 'change', this.model );

			duplicate.fetchHtml( function( response ) {
				var data = {
					html: response,
					after: after,
					callback: 'onPartDuplicateCallback',
				};

				justwpforms.previewSend( 'justwpforms-form-part-add', data );
			} );
		},

		onPartModelAdd: function( partModel, partsCollection, options ) {
			this.addViewPart( partModel, options );

			for ( var attribute in this.globalAttributes ) {
				if ( partModel.has( attribute ) ) {
					partModel.set( attribute, this.globalAttributes[attribute] );
				}
			}
		},

		onPartModelRemove: function( partModel ) {
			this.model.trigger( 'change', this.model );

			var partViewModel = this.partViews.find( function( viewModel ) {
				return viewModel.get( 'view' ).model.id === partModel.id;
			}, this );

			this.partViews.remove( partViewModel );

			justwpforms.previewSend( 'justwpforms-form-part-remove', partModel.id );
		},

		onPartModelChange: function( partModel ) {
			this.model.trigger( 'change' );
		},

		onPartModelsSorted: function() {
			this.partViews.reset( _.map( this.model.get( 'parts' ).pluck( 'id' ), function( id ) {
				return this.partViews.get( id );
			}, this ) );
			this.model.trigger( 'change' );

			var ids = this.model.get( 'parts' ).pluck( 'id' );
			justwpforms.previewSend( 'justwpforms-form-parts-sort', ids );
		},

		addViewPart: function( partModel, options ) {
			var settings = justwpforms.parts.findWhere( { type: partModel.get( 'type' ) } );

			if ( settings ) {
				var partView = PartFactory.view( _.extend( {
					type: settings.get( 'type' ),
					model: partModel,
					settings: settings,
				}, options ) );

				var partViewModel = new Backbone.Model( {
					id: partModel.id,
					view: partView,
				} );

				this.partViews.add( partViewModel, options );
			}
		},

		onPartViewAdd: function( viewModel, collection, options ) {
			var partView = viewModel.get( 'view' );

			if ( 'undefined' === typeof( options.index ) ) {
				$( '.justwpforms-form-widgets', this.$el ).append( partView.render().$el );
			} else if ( 0 === options.index ) {
				$( '.justwpforms-form-widgets', this.$el ).prepend( partView.render().$el );
			} else {
				$( '.justwpforms-widget:nth-child(' + options.index + ')', this.$el ).after( partView.render().$el );
			}

			this.assignLastOfTypeClasses();
			partView.trigger( 'ready' );

			if ( options.scrollto ) {
				this.$el.parent().animate( {
					scrollTop: partView.$el.position().top
				}, 400 );
			}
		},

		onPartViewRemove: function( viewModel ) {
			var partView = viewModel.get( 'view' );
			partView.remove();

			this.assignLastOfTypeClasses();
		},

		onPartSortStop: function( e, ui ) {
			var $sortable = $( '.justwpforms-form-widgets', this.$el );
			var ids = [];

			$( '.justwpforms-widget', $sortable ).each( function() {
				ids.push( $(this).attr( 'data-part-id' ) );
			} );

			this.model.get( 'parts' ).reset( _.map( ids, function( id ) {
				return this.model.get( 'parts' ).get( id );
			}, this ) );

			ui.item.trigger( 'sort-stop' );
		},

		onPartViewsSorted: function( partViews ) {
			var $stage = $( '.justwpforms-form-widgets', this.$el );

			partViews.forEach( function( partViewModel ) {
				var partView = partViewModel.get( 'view' );
				var $partViewEl = partView.$el;
				$partViewEl.detach();
				$stage.append( $partViewEl );
				partView.trigger( 'refresh' );
			}, this );

			this.assignLastOfTypeClasses();
		},

		onPartViewsChanged: function( partViews ) {
			if ( this.partViews.length > 0 ) {
				this.$el.addClass( 'has-parts' );
			} else {
				this.$el.removeClass( 'has-parts' );
			}
		},

		assignLastOfTypeClasses: function() {
			var partTypeGroups = this.partViews.groupBy( function( partViewModel ) {
				return partViewModel.get( 'view' ).model.get( 'type' );
			} );

			for ( var type in partTypeGroups ) {
				var viewModels = partTypeGroups[type];

				viewModels.forEach( function( viewModel ) {
					viewModel.get( 'view' ).$el.removeClass( 'last-of-type' );
				} );

				viewModels[viewModels.length - 1].get( 'view' ).$el.addClass( 'last-of-type' );
			}
		},

		onSetGlobalAttribute: function( e, data ) {
			this.partViews
				.filter( function( viewModel ) {
					return viewModel.id !== data.id
				} )
				.forEach( function( viewModel ) {
					var view = viewModel.get( 'view' );
					$( '[data-apply-to="' + data.attribute + '"]', view.$el ).prop( 'checked', false );
					$( '[data-bind="' + data.attribute + '"]', view.$el ).val( data.value );
					view.model.set( data.attribute, data.value );
				} );

			this.globalAttributes[data.attribute] = data.value;
		},

		onUnsetGlobalAttribute: function( e, data ) {
			this.partViews
				.filter( function( viewModel ) {
					return viewModel.id !== data.id
				} )
				.forEach( function( viewModel ) {
					var view = viewModel.get( 'view' );
					var previous = view.model.previous( data.attribute );
					$( '[data-bind="' + data.attribute + '"]', view.$el ).val( previous );
					view.model.set( data.attribute, previous );
				} );

			delete this.globalAttributes[data.attribute];
		},

		remove: function() {
			while ( partView = this.partViews.first() ) {
				this.partViews.remove( partView );
			};

			this.drawer.close();
			this.drawer.remove();

			classes.views.Base.prototype.remove.apply( this, arguments );
		},
	} );

	classes.views.PartsDrawer = classes.views.Base.extend( {
		template: '#justwpforms-form-parts-drawer-template',

		events: {
			'click .justwpforms-parts-list-item': 'onListItemClick',
			'keyup #part-search': 'onPartSearch',
			'change #part-search': 'onPartSearch',
			'click .justwpforms-clear-search': 'onClearSearchClick'
		},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			$( '.wp-full-overlay-sidebar' ).on( 'click', this.close.bind( this ) );
		},

		render: function() {
			this.setElement( this.template( { parts: justwpforms.parts.toJSON() } ) );
			this.applyConditionClasses();
			return this;
		},

		applyConditionClasses: function() {
			var self = this;

			_( formIntegrations ).each( function( value, index ) {
				if ( '1' === value ) {
					self.$el.addClass( 'has-' + index + '-integration' );
				}
			} );

			var partTypes = justwpforms.form.get( 'parts' ).map( function( model ) {
				return model.get( 'type' );
			} );

			partTypes = _.union( partTypes );

			for ( var i = 0; i < partTypes.length; i++ ) {
				self.$el.addClass( 'has-' + partTypes[i] );
			}
		},

		onListItemClick: function( e ) {
			e.stopPropagation();

			var $li = $( e.currentTarget );

			if ( $li.hasClass( 'justwpforms-parts-list-item--dummy' ) || $li.hasClass( 'justwpforms-parts-list-item--group' ) ) {
				return;
			}

			var type = $li.data( 'part-type' );
			justwpforms.trigger( 'part-add', type, { expand: true } );
		},

		onPartSearch: function( e ) {
			var search = $( e.target ).val().toLowerCase();
			var $clearButton = $( e.target ).nextAll( 'button' );
			var $partEls = $( '.justwpforms-parts-list-item', this.$el );

			if ( '' === search ) {
				$partEls.removeClass( 'hidden' );
				$clearButton.removeClass( 'active' );

			} else {
				$clearButton.addClass( 'active' );
				$partEls.addClass( 'hidden' );

				var results = justwpforms.parts.filter( function( part ) {
					if( 'layout_drawer_group' == part.get( 'type' ) ) {
						return false;
					}

					var label = part.get( 'label' ).toLowerCase();
					var description = part.get( 'description' ).toLowerCase();

					return label.indexOf( search ) >= 0 || description.indexOf( search ) >= 0;
				} );

				var has_design_parts = false;
				var design_parts = [ 'layout_title', 'placeholder', 'media', 'divider', 'page_break' ];

				results.forEach( function( part ) {
					var type = part.get( 'type' );

					if ( design_parts.indexOf( type ) !== -1 ) {
						has_design_parts = true;
					}

					$( '.justwpforms-parts-list-item[data-part-type="' + type + '"]', this.$el ).removeClass( 'hidden' );
				} );

				if ( has_design_parts ) {
					$( '.justwpforms-parts-list-item.justwpforms-parts-list-item--group', this.$el ).removeClass( 'hidden' );
				}
			}
		},

		onClearSearchClick: function( e ) {
			$( '#part-search', this.$el ).val( '' ).trigger( 'change' );
		},

		toggle: function() {
			this.$el.toggleClass( 'expanded' );
			$( 'body' ).toggleClass( 'adding-justwpforms-parts' );

			if ( this.$el.hasClass( 'expanded') ) {
				$( '#part-search' ).trigger( 'focus' );
			}
		},

		close: function() {
			this.$el.removeClass( 'expanded' );
			$( 'body' ).removeClass( 'adding-justwpforms-parts' );
		}
	} );

	classes.views.Part = classes.views.Base.extend( {
		$: $,

		events: {
			'click .justwpforms-widget-action': 'onWidgetToggle',
			'click .justwpforms-form-part-close': 'onWidgetToggle',
			'click .justwpforms-form-part-remove': 'onPartRemoveClick',
			'click .justwpforms-form-part-duplicate': 'onPartDuplicateClick',
			'keyup [data-bind]': 'onInputChange',
			'change [data-bind]': 'onInputChange',
			'change input[type=number]': 'onNumberChange',
			'mouseover': 'onMouseOver',
			'mouseout': 'onMouseOut',
			'click .apply-all-check': 'applyOptionGlobally',
			'click .justwpforms-form-part-advanced-settings': 'onAdvancedSettingsClick',
			'click .justwpforms-form-part-logic': 'onLogicButtonClick',
		},

		initialize: function( options ) {
			classes.views.Base.prototype.initialize.apply( this, arguments );
			this.settings = options.settings;

			// listen to changes in common settings
			this.listenTo( this.model, 'change:label', this.onPartLabelChange );
			this.listenTo( this.model, 'change:width', this.onPartWidthChange );
			this.listenTo( this.model, 'change:required', this.onRequiredCheckboxChange );
			this.listenTo( this.model, 'change:placeholder', this.onPlaceholderChange );
			this.listenTo( this.model, 'change:description', _.debounce( function( model, value ) {
				this.onDescriptionChange( model, value );
				}, 800 ) );
			this.listenTo( this.model, 'change:default_value', this.onDefaultValueChange );
			this.listenTo( this.model, 'change:label_placement', this.onLabelPlacementChange );
			this.listenTo( this.model, 'change:css_class', this.onCSSClassChange );
			this.listenTo( this.model, 'change:prefix', this.onPartPrefixChange );
			this.listenTo( this.model, 'change:suffix', this.onPartSuffixChange );
			this.listenTo( this.model, 'change:limit_answer', this.onPartLimitAnswerChange );

			this.listenTo( this, 'ready', this.updateDynamicDropdownSettings );

			if ( options.expand ) {
				this.listenTo( this, 'ready', this.expandToggle );
			}
		},

		render: function() {
			this.setElement( this.template( {
				settings: this.settings.toJSON(),
				instance: this.model.toJSON(),
			} ) );

			return this;
		},

		/**
		 * Trigger a previewer event on mouse over.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onMouseOver: function() {
			var data = {
				id: this.model.id,
				callback: 'onPartMouseOverCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Trigger a previewer event on mouse out.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onMouseOut: function() {
			var data = {
				id: this.model.id,
				callback: 'onPartMouseOutCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Send changed label value to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onPartLabelChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onPartLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Send data about changed part width to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onPartWidthChange: function( model, value, options ) {
			var data = {
				id: this.model.id,
				callback: 'onPartWidthChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Trigger a previewer event on change of the "This is a required field" checkbox.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onRequiredCheckboxChange: function() {
			var model = this.model;

			var data = {
				id: this.model.id,
				callback: 'onRequiredCheckboxChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Slide toggle part view in the customize pane.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		expandToggle: function() {
			var $el = this.$el;

			this.closeOpenWidgets( $el );

			$( '.justwpforms-widget-content', this.$el ).slideToggle( 200, function() {
				$el.toggleClass( 'justwpforms-widget-expanded' );

				if ( $el.hasClass( 'justwpforms-widget-expanded' ) ) {
					$( 'input[data-bind=label]', $el ).trigger( 'focus' );
				}
			} );

			justwpforms.savedStates.build.activePartIndex = this.$el.index();
		},

		closeOpenWidgets: function( $currentElement ) {
			var $openWidgets = $( '.justwpforms-widget-expanded' ).not( $currentElement );

			$( '.justwpforms-widget-content', $openWidgets ).slideUp( 200, function() {
				$openWidgets.removeClass( 'justwpforms-widget-expanded' );
			} );
		},

		/**
		 * Call expandToggle method on toggle indicator click or 'Close' button click of the part view in Customize pane.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onWidgetToggle: function( e ) {
			e.preventDefault();
			this.expandToggle();
		},

		/**
		 * Remove part model from collection on "Delete" button click.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onPartRemoveClick: function( e ) {
			e.preventDefault();

			var self = this;

			$( '.justwpforms-widget-content', this.$el ).slideUp( 'fast', function() {
				$( this ).removeClass( 'justwpforms-widget-expanded' );

				self.model.collection.remove( self.model );
			} );
		},

		onPartDuplicateClick: function( e ) {
			e.preventDefault();

			justwpforms.trigger( 'part-duplicate', this.model, {
				expand: true,
				scrollto: true,
			} );
		},

		/**
		 * Update model with the changed data. Triggered on change event of inputs in the part view.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onInputChange: function( e ) {
			var $el = $( e.target );
			var value = $el.val();
			var attribute = $el.data( 'bind' );

			if ( 'label' === attribute ) {
				var $inWidgetTitle = this.$el.find('.in-widget-title');
				$inWidgetTitle.find('span').text(value);

				if ( value ) {
					$inWidgetTitle.show();
				} else {
					$inWidgetTitle.hide();
				}
			}

			if ( $el.is(':checkbox') ) {
				if ( $el.is(':checked') ) {
					value = 1;
				} else {
					value = 0;
				}
			}

			this.model.set( attribute, value );
		},

		/**
		 * Send changed placeholder value to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onPlaceholderChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onPlaceholderChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Send changed default value to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onDefaultValueChange: function() {
			var data = {
				id: this.model.id,
				callback: 'onDefaultValueChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		/**
		 * Send changed description value to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onDescriptionChange: function( model, value ) {
			var data = {
				id: this.model.id,
			};

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		/**
		 * Send data about changed label placement value to previewer.
		 *
		 * @since 1.0.0.
		 *
		 * @return void
		 */
		onLabelPlacementChange: function( model, value, options ) {
			if ( 'hidden' === value ) {
				$( '[data-bind=description_mode] option[value=tooltip]', this.$el ).hide();

				this.resetDescriptionMode();
			} else {
				$( '[data-bind=description_mode] option[value=tooltip]', this.$el ).show();
			}

			if ( 'as_placeholder' === value ) {
				$( '.justwpforms-placeholder-option', this.$el ).hide();
			} else {
				$( '.justwpforms-placeholder-option', this.$el ).show();
			}

			model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		resetDescriptionMode: function() {
			var $descriptionModeDropdown = $( '[data-bind=description_mode]', this.$el );

			if ( $descriptionModeDropdown.length ) {
				this.model.set( 'description_mode', '' );
				$descriptionModeDropdown.val( '' );
			}
		},

		applyOptionGlobally: function( e ) {
			var $input = $( e.target );
			var attribute = $input.attr( 'data-apply-to' );

			if ( $input.is( ':checked' ) ) {
				this.$el.trigger( 'global-attribute-set', {
					id: this.model.id,
					attribute: attribute,
					value: this.model.get( attribute ),
				} );
			} else {
				this.$el.trigger( 'global-attribute-unset', {
					id: this.model.id,
					attribute: attribute,
				} );
			}
		},

		onCSSClassChange: function( model, value, options ) {
			var data = {
				id: this.model.id,
				callback: 'onCSSClassChangeCallback',
				options: options,
			};

			justwpforms.previewSend( 'justwpforms-part-dom-update', data );
		},

		showDescriptionOptions: function() {
			this.$el.find('.justwpforms-description-options').fadeIn();
		},

		hideDescriptionOptions: function() {
			var $descriptionOptionsWrap = this.$el.find('.justwpforms-description-options');

			$descriptionOptionsWrap.fadeOut(200, function() {
				$descriptionOptionsWrap.find('input').prop('checked', false);
			});
		},

		onAdvancedSettingsClick: function( e ) {
			$( '.justwpforms-part-advanced-settings-wrap', this.$el ).slideToggle( 300, function() {
				$( e.target ).toggleClass( 'opened' );
			} );
		},

		onLogicButtonClick: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			$( '.justwpforms-part-logic-wrap', this.$el ).slideToggle( 300, function() {
				$( e.target ).toggleClass( 'opened' );
			} );
		},

		onNumberChange: function( e ) {
			var $input = $( e.target );
			var value = parseInt( $input.val(), 10 );
			var min = $input.attr( 'min' );
			var max = $input.attr( 'max' );
			var attribute = $input.attr( 'data-bind' );

			if ( value < parseInt( min, 10 ) ) {
				$input.val( min );
				this.model.set( attribute, min );
			}

			if ( value > parseInt( max, 10 ) ) {
				$input.val( max );
				this.model.set( attribute, max );
			}
		},

		refreshPart: function() {
			var model = this.model;

			this.model.fetchHtml( function( response ) {
				var data = {
					id: model.get( 'id' ),
					html: response,
				};

				justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
			} );
		},

		updateDynamicDropdownSettings: function() {
			var self = this;
			var $controls = $( '.justwpforms-client-updated', this.$el );

			$controls.each( function( index, control ) {
				var $control = $( control );
				var source   = $control.data( 'source' );
				var value    = justwpforms.form.get( source );

				var variable = $control.data( 'var' );
				var property = $control.data( 'var-prop' );
				var options  = window[variable][property];

				if ( value ) {
					options = window[variable][property][value];
				}

				if ( ! options ) {
					return;
				}

				$( 'option:not(:first)', $control ).remove();

				_( options ).each( function( option, index ) {
					if ( ! option.items ) {
						var $option = $( '<option />' );
						$option.attr( 'value', option.id );
						$option.text( option.name );
						$option.appendTo( $control );

						if ( self.model.get( $control.data( 'bind' ) ) == option.id ) {
							$option.prop( 'selected', true );
						}
					} else {
						var $optgroup = $( '<optgroup />' );
						$optgroup.attr( 'label', option.name );

						_( option.items ).each( function( option, index ) {
							var $option = $( '<option />' );
							$option.attr( 'value', option.id );
							$option.text( option.name );
							$option.appendTo( $optgroup );

							if ( self.model.get( $control.data( 'bind' ) ) == option.id ) {
								$option.prop( 'selected', true );
							}
						} );

						$optgroup.appendTo( $control );
					}
				} );
			} );
		},

		onPartPrefixChange: function( model, value ) {
			var data;

			/**
			 * If prefix is empty or had no value before, trigger part refresh so it hides / shows itself.
			 */
			if ( ! value || ! model.previous( 'prefix' ) ) {
				this.model.fetchHtml( function( response ) {
					data = {
						id: model.get( 'id' ),
						html: response,
					};

					justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
				} );
			/**
			 * Otherwise, update prefix by part dom update in preview.
			 */
			} else {
				data = {
					id: this.model.get( 'id' ),
					callback: 'onPartPrefixChangeCallback',
				};

				justwpforms.previewSend( 'justwpforms-part-dom-update', data );
			}
		},

		onPartSuffixChange: function( model, value ) {
			var data;

			/**
			 * If suffix is empty or had no value before, trigger part refresh so it hides / shows itself.
			 */
			if ( ! value || ! model.previous( 'suffix' ) ) {
				this.model.fetchHtml( function( response ) {
					data = {
						id: model.get( 'id' ),
						html: response,
					};

					justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
				} );
			/**
			 * Otherwise, update suffix by part dom update in preview.
			 */
			} else {
				data = {
					id: this.model.get( 'id' ),
					callback: 'onPartSuffixChangeCallback',
				};

				justwpforms.previewSend( 'justwpforms-part-dom-update', data );
			}
		},

		onPartLimitAnswerChange: function( model, value ) {
			var $limitSettings = $( '.justwpforms-answer-limit-settings', this.$el );

			if ( value ) {
				$limitSettings.show();
			} else {
				$limitSettings.hide();
			}
		},

	} );

	classes.views.FormSetup = classes.views.Base.extend( {
		template: '#justwpforms-form-setup-template',

		events: _.extend( {}, classes.views.Base.prototype.events, {
			'keyup [data-attribute]': 'onInputChange',
			'change [data-attribute]': 'onInputChange',
			'change input[type=number]': 'onNumberChange',
			'click .insert-media': 'onAddMediaClick',
		} ),

		pointers: {},

		editors: {
			'max_entries_message' : {},
			'abandoned_resume_return_message' : {},
			'review_step_message' : {},
			'password_error_message': {},
		},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:abandoned_resume_response_expire', this.onAbandonedResumeToggle );
			this.listenTo( justwpforms, 'logic-group-added', this.onLogicGroupAdd );
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );
			this.applyConditionClasses();

			return this;
		},

		onLogicGroupAdd: function( formItem ) {
			if ( 'redirect_url' === formItem.id ) {
				this.addURLAutocomplete( $( '#customize-control-redirect_url input[data-url-suggest]:last', this.$el ) );
			}
		},

		applyConditionClasses: function() {
			var self = this;

			_( formIntegrations ).each( function( value, index ) {
				if ( '0' !== value ) {
					self.$el.addClass( 'has-' + index + '-integration' );

					if ( value !== '1' ) {
						self.$el.addClass( 'has-' + value + '-integration' );
					}
				}
			} );

			var partTypes = justwpforms.form.get( 'parts' ).map( function( model ) {
				return model.get( 'type' );
			} );

			partTypes = _.union( partTypes );

			for ( var i = 0; i < partTypes.length; i++ ) {
				var className = 'has-' + partTypes[i];

				if ( self.$el.hasClass( className ) ) {
					className = className + '-part';
				}

				self.$el.addClass( className );
			}

			self.$el.addClass( 'has-' + justwpforms.form.get( 'parts' ).length + '-parts' );

			var partsWithLimitedOptions = justwpforms.form.get( 'parts' ).filter( function( part ) {
				var hasOptions = [ 'radio', 'checkbox', 'select' ].includes( part.get( 'type' ) );

				if ( ! hasOptions ) {
					return false;
				}

				var hasLimitedOptions = part.get( 'options' ).find( function( option ) {
					var limitSubmissionAmount = option.get( 'limit_submissions_amount' );

					return typeof limitSubmissionAmount !== 'undefined' && limitSubmissionAmount != '';
				} );

				return hasLimitedOptions;
			} );

			if ( partsWithLimitedOptions.length ) {
				self.$el.addClass( 'has-show-submissions-left' );
			}

			var handDrawnSignature = this.model.get( 'parts' ).findWhere( {
				type: 'signature',
				signature_type: 'draw',
			} );

			if ( handDrawnSignature ) {
				self.$el.addClass( 'has-drawn-signature' );
			}
		},

		ready: function() {
			var self = this;

			this.setupHelpPointers();
			this.addURLAutocomplete( $( 'input[data-url-suggest]', this.$el ) );

			var defaultEditorSettings = {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link',
				},
				quicktags: {
					buttons: 'strong,em,del,link,close',
				},
				hfmedia: {
					minimal: false,
					supports: {
						'image' : 'Image',
						'audio' : 'Audio',
						'video' : 'Video'
					},
					filters: {
						'image' : 'Image',
						'audio' : 'Audio',
						'video' : 'Video',
					}
				}
			};

			_.each( this.editors, function( editorSettings, editorId ) {
				var settings = _.extend( defaultEditorSettings, editorSettings );
				settings.tinymce.setup = self.onEditorInit.bind( self );

				self.initEditor( editorId, settings );
			} );

			$( '.customize-control[data-fetch-bind]', this.$el ).each( function( index, control ) {
				var $control = $( control );

				self.fetchNewControlOptions( $control.data( 'fetch-on' ), $control );
			} );

			wp.media.events.on( 'editor:image-edit', this.onImageEdit.bind( this ) );
		},

		addURLAutocomplete: function( $input ) {
			var last;

			if ( ! $input.length ) {
				return;
			}

			$input.autocomplete( {
				source: function( request, response ) {
					if ( last === request.term ) {
						response( cache );
						return;
					}

					if ( /^https?:/.test( request.term ) || request.term.indexOf( '.' ) !== -1 ) {
						return response();
					}

					$.post( window.ajaxurl, {
						action: 'wp-link-ajax',
						page: 1,
						search: request.term,
						_ajax_linking_nonce: $( '#_ajax_linking_nonce' ).val()
					}, function( data ) {
						cache = data;
						response( data );
					}, 'json' );

					last = request.term;
				},
				focus: function( event, ui ) {
					$input.attr( 'aria-activedescendant', 'mce-wp-autocomplete-' + ui.item.ID );
					/*
					 * Don't empty the URL input field, when using the arrow keys to
					 * highlight items. See api.jqueryui.com/autocomplete/#event-focus
					 */
					event.preventDefault();
				},
				select: function( event, ui ) {
					$input.val( ui.item.permalink );
					$input.trigger( 'change' );

					if ( 9 === event.keyCode && typeof window.wpLinkL10n !== 'undefined' ) {
						// Audible confirmation message when a link has been selected.
						speak( window.wpLinkL10n.linkSelected );
					}

					return false;
				},
				open: function() {
					$input.attr( 'aria-expanded', 'true' );
				},
				close: function() {
					$input.attr( 'aria-expanded', 'false' );
				},
				minLength: 2,
				position: {
					my: 'left top+2'
				},
				messages: {
					noResults: ( typeof window.uiAutocompleteL10n !== 'undefined' ) ? window.uiAutocompleteL10n.noResults : '',
					results: function( number ) {
						if ( typeof window.uiAutocompleteL10n !== 'undefined' ) {
							if ( number > 1 ) {
								return window.uiAutocompleteL10n.manyResults.replace( '%d', number );
							}

							return window.uiAutocompleteL10n.oneResult;
						}
					}
				}
			} ).autocomplete( 'instance' )._renderItem = function( ul, item ) {
				var fallbackTitle = ( typeof window.wpLinkL10n !== 'undefined' ) ? window.wpLinkL10n.noTitle : '',
					title = item.title ? item.title : fallbackTitle;

				return $( '<li role="option" id="mce-wp-autocomplete-' + item.ID + '">' )
				.append( '<span>' + title + '</span>&nbsp;<span class="wp-editor-float-right">' + item.info + '</span>' )
				.appendTo( ul );
			};
		},

		initEditor: function( editorId, editorSettings ) {
			wp.editor.initialize( editorId, editorSettings );
		},

		onAddMediaClick: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var editorId, editor;

			editorId = $( e.target ).parents( '.customize-control' ).attr( 'id' );
			editorId = editorId.replace( 'customize-control-', '' );

			editor = tinymce.get( editorId );

			var editorMediaSettings = this.editors[editorId].hfmedia;

			justwpforms.utils.setMediaFilters( editorMediaSettings.filters );

			justwpforms.media = new wp.media.view.MediaFrame.Select();
			justwpforms.media.views.view.$el.addClass( 'justwpforms-media__' + editorId );
			justwpforms.media.views.view.render();
			justwpforms.media.open();

			justwpforms.media.on( 'close', justwpforms.utils.onAttachmentSelected.bind( this, editor, editorMediaSettings.supports ) );
		},

		onImageEdit: function( data ) {
			if ( ! data.editor.id ) {
				return;
			}

			if ( 'undefined' === typeof this.editors[data.editor.id] ) {
				return;
			}

			var options = {
				data: data,
				minimalMode: this.editors[data.editor.id].hfmedia.minimal
			};

			justwpforms.utils.onImageEdit( options );
		},

		onEditorInit: function( editor ) {
			var $textarea = $( '#' + editor.id, this.$el );
			var attribute = $textarea.data( 'attribute' );
			var self = this;

			editor.on( 'keyup change', function() {
				self.model.set( attribute, editor.getContent() );
			} );

			if ( this.editors[editor.id].hasOwnProperty( 'hfmedia' ) && this.editors[editor.id].hfmedia.minimal ) {
				editor.on( 'click', function( e ) {
					$( 'body' ).addClass( 'justwpforms-editor-media-minimal' );
				} );

				$( document ).on( 'click', function() {
					$( 'body' ).removeClass( 'justwpforms-editor-media-minimal' );
				} );
			}
		},

		onInputChange: function( e ) {
			e.preventDefault();

			var self = this;
			var $el = $( e.target );
			var attribute = $el.data( 'attribute' );
			var value = $el.val();

			if ( $el.is( ':checkbox' ) ) {
				value = $el.is( ':checked' ) ? value: 0;

				if ( $el.is( ':checked' ) ) {
					$el.parents( '.customize-control' ).addClass( 'checked' );
				} else {
					$el.parents( '.customize-control' ).removeClass( 'checked' );
				}
			}

			$( '#customize-control-' + attribute ).attr( 'data-value', value );

			this.model.set( attribute, value );
		},

		fetchNewControlOptions: function( attribute, $control ) {
			if ( attribute !== $control.attr( 'data-fetch-bind' ) ) {
				return;
			}

			var self = this;
			var attributeValue = this.model.get( attribute );
			var targetControlAttribute = $control.attr( 'data-field' );
			var fetchVar = $control.attr( 'data-fetch-var' );
			var fetchProp = $control.attr( 'data-fetch-prop' );

			var data = window[fetchVar];

			if ( fetchProp ) {
				data = data[fetchProp];
			}

			if ( attributeValue ) {
				data = data[attributeValue];
				data.field = targetControlAttribute;
			}

			var template = _.template( $( '#justwpforms-' + $control.attr( 'data-type' ) + '-js-template' ).text() );
			var html = template( { data: data } );

			$( '.customize-control-options', $control ).html( html );

			if ( data.length ) {
				$( '.no-groups', $control ).hide();
			} else {
				$( '.no-groups', $control ).show();
			}

			// reset value
			this.model.set( targetControlAttribute, '' );
			// map newly generated input to 'onInputChange' method
			$( '.customize-control-options input', $control ).on( 'change', self.onInputChange.bind( this ) );
			// trigger fetch-complete event to hook on later and handle conditions etc.
			$control.trigger( 'fetch-complete' );
		},

		onAbandonedResumeToggle: function( model, value ) {
			var data = {
				callback: 'onAbandonedResumeToggleCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onNumberChange: function( e ) {
			var $input = $( e.target );
			var value = parseInt( $input.val(), 10 );
			var min = $input.attr( 'min' );
			var max = $input.attr( 'max' );
			var attribute = $input.attr( 'data-bind' );

			if ( value < parseInt( min, 10 ) ) {
				$input.val( min );
				this.model.set( attribute, min );
			}

			if ( value > parseInt( max, 10 ) ) {
				$input.val( max );
				this.model.set( attribute, max );
			}
		},

		remove: function() {
			_.each( this.editors, function( editorSettings, editorId ) {
				wp.editor.remove( editorId );
			} );

			classes.views.Base.prototype.remove.apply( this, arguments );
		},

	} );

	classes.views.FormEmail = classes.views.FormSetup.extend( {
		template: '#justwpforms-form-email-template',

		editors: {
			'confirmation_email_content' : {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link,hfmedia',
					plugins: 'charmap compat3x paste directionality hr image lists media wordpress wpautoresize wpeditimage wpemoji wplink wptextpattern wpview',
				},
				quicktags: {
					buttons: 'strong,em,del,link,close'
				},
				mediaButtons: true,
				hfmedia: {
					minimal: true,
					supports: {
						'image' : 'Image',
						'audio_link' : 'Audio',
						'video_link' : 'Video'
					},
					filters: {
						'image' : 'Image'
					},
				}
			},
			'abandoned_resume_email_content' : {
				tinymce: {
					toolbar1: 'bold,italic,strikethrough,link,hfmedia',
					plugins: 'charmap compat3x paste directionality hr image lists media wordpress wpautoresize wpeditimage wpemoji wplink wptextpattern wpview',
				},
				mediaButtons: true,
				hfmedia: {
					minimal: true,
					supports: {
						'image' : 'Image',
						'audio_link' : 'Audio',
						'video_link' : 'Video'
					},
					filters: {
						'image' : 'Image'
					},
				}
			},
		},

		render: function() {
			classes.views.FormSetup.prototype.render.apply( this, arguments );

			if ( '' !== this.model.get( 'abandoned_resume_response_expire' ) ) {
				this.$el.addClass( 'allow-resume' );
			}

			return this;
		},
	} );

	classes.views.FormMessages = classes.views.FormSetup.extend( {
		template: '#justwpforms-form-messages-template',

		events: _.extend( {}, classes.views.Base.prototype.events, {
			'keyup [data-attribute]': 'onInputChange',
			'keyup [data-attribute="optional_part_label"]': 'onOptionalPartLabelChange',
			'keyup [data-attribute="required_field_label"]': 'onRequiredPartLabelChange',
			'keyup [data-attribute="submit_button_label"]': 'onSubmitButtonLabelChange',
			'keyup input[data-attribute="abandoned_resume_save_button_label"]': 'onAbandonedResumeSaveButtonLabelChange',
			'keyup [data-attribute="words_label_min"]': 'onCharLimitMinWordsChange',
			'keyup [data-attribute="words_label_max"]': 'onCharLimitMaxWordsChange',
			'keyup [data-attribute="characters_label_min"]': 'onCharLimitMinCharsChange',
			'keyup [data-attribute="characters_label_max"]': 'onCharLimitMaxCharsChange',
			'keyup [data-attribute="no_results_label"]': 'onNoResultsLabelChange',
			'keyup [data-attribute="show_results_label"]': 'onShowResultsLabelChange',
			'keyup [data-attribute="back_to_poll_label"]': 'onBackToPollLabelChange',
			'keyup [data-attribute="max_files_uploaded_label"]': 'onMaxFilesUploadedLabelChange',
			'keyup [data-attribute="file_upload_browse_label"]': 'onFilesBrowseLabelChange',
			'keyup [data-attribute="file_upload_delete_label"]': 'onFilesDeleteLabelChange',
			'keyup [data-attribute="field_signature_start_drawing_button_label"]': 'onSignatureStartDrawingButtonLabelChange',
			'keyup [data-attribute="field_signature_start_over_button_label"]': 'onSignatureStartOverButtonLabelChange',
			'keyup [data-attribute="field_signature_clear_button_label"]': 'onSignatureClearButtonLabelChange',
			'keyup [data-attribute="field_signature_done_button_label"]': 'onSignatureDoneButtonLabelChange',
			'keyup [data-attribute="phone_label_country_code"]': 'onPhoneLabelCountryChange',
			'keyup [data-attribute="phone_label_number"]': 'onPhoneLabelNumberChange',
			'change [data-attribute]': 'triggerKeyUp',
			'click [data-reset]': 'resetDefaultMessage',
			'keyup input[data-attribute]': 'onMessageValueChange',
		} ),

		editors: {
		},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:submissions_left_label', _.debounce( function( model, value ) {
				this.onSubmissionsLeftLabelChange( model, value );
			}, 800 ) );
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );
			classes.views.FormSetup.prototype.render.apply( this, arguments );
			this.applyMsgConditionClasses();

			if ( this.model.get( 'per_form_validation_msg' ) == 0 ) {
 				this.model.set( 'per_form_validation_msg', 1 );
 			}

 			this.initializeResetButtons();

			return this;
		},

		initializeResetButtons: function() {
			var self = this;
			var $el = this.$el;

			$( '[data-reset]', $el ).each( function() {
				var attribute = $( this ).data( 'reset');

				self.setResetDisabled( attribute );
			} );
		},

		onMessageValueChange: function( e ) {
			var attribute = $( e.target ).data( 'attribute' );

			this.setResetDisabled( attribute );
		},

		setResetDisabled: function( attribute ) {
			var $el = this.$el;
			var $reset = $( '[data-reset="' + attribute + '"]', $el );

			var val_current = $( '[data-attribute="' + attribute + '"]', $el ).val();
			var val_default = $reset.data( 'default' );

			if ( val_current !== val_default ) {
				$reset.prop('disabled', false);
			} else {
				$reset.prop('disabled', true);
			}
		},

		resetDefaultMessage: function( e ) {
			var $reset = $( e.target );
			var attribute = $reset.data( 'reset' );
			var $input = $( '[data-attribute="' + attribute + '"]' );

			$input.val( $reset.data( 'default' ) );
			$reset.prop( 'disabled', true );

			$input.trigger( 'keyup' );
		},

		applyMsgConditionClasses: function() {
			var self = this;

			if ( justwpforms.form.get( 'parts' ).length > 0 ) {
				self.$el.addClass( 'has-field' );
			}
			var hasRequiredField = this.model.get( 'parts' ).find( function( part ) {
				var types = [ 'single_line_text', 'multi_line_text', 'email', 'website_url', 'number',
						'phone', 'date', 'address', 'signature', 'payments', 'narrative' ];
				var required = part.get( 'required' );

				if ( required == 1 ) {
					var type = part.get( 'type' );
					return ( $.inArray( type, types ) > -1 );
				}

				return false;
			} );

			if ( hasRequiredField ) {
				self.$el.addClass( 'has-required-text-fields' );
			}

			var hasRequiredSelectionField = this.model.get( 'parts' ).find( function( part ) {
				var types = [ 'table', 'radio', 'checkbox', 'select', 'poll', 'scale',
						'rank_order', 'likert_scale', 'legal', 'rating', 'email_integration' ];
				var required = part.get( 'required' );

				if ( required == 1 ) {
					var type = part.get( 'type' );
					return ( $.inArray( type, types ) > -1 );
				}

				return false;
			} );

			if ( hasRequiredSelectionField ) {
				self.$el.addClass( 'has-required-selection-fields' );
			}

			var hasLimitChoice = justwpforms.form.get( 'parts' ).findWhere( {
				limit_choices: 1
			} );

			if ( hasLimitChoice ) {
				self.$el.addClass( 'has-limit-choices' );
			}

			if ( justwpforms.form.get( 'schedule_visibility' ) == 1 ) {
				self.$el.addClass( 'is-scheduled' );
			}

			if ( '' !== this.model.get( 'abandoned_resume_response_expire' ) ) {
				self.$el.addClass( 'allow-resume' );
			}

			if ( justwpforms.form.get( 'preview_before_submit' ) == 1 ) {
				self.$el.addClass( 'allow-preview' );
			}

			var hasEmail = justwpforms.form.get( 'parts' ).findWhere( {
				type: 'email'
			} );

			if ( hasEmail && justwpforms.form.get( 'restrict_user_entries' ) == 1 ) {
				self.$el.addClass( 'is-restrict-users' );
			}

			var confirmSubmission = justwpforms.form.get( 'confirm_submission' );

			if ( confirmSubmission == 'success_message_hide_form' || confirmSubmission == 'success_message' ) {
				self.$el.addClass( 'is-show-success-msg' );
			}

			var hasShowResultVoting = justwpforms.form.get( 'parts' ).findWhere( {
				show_results_before_voting: 1
			} );

			if ( hasShowResultVoting ) {
				self.$el.addClass( 'has-show-voting' );
			}

			var limitedAnswerField = justwpforms.form.get( 'parts' ).find( function( part ) {
				return part.get( 'max_limit_answer' ) >= 0;
			} );

			if ( limitedAnswerField ) {
				self.$el.addClass( 'has-limit-answer' );
			}

			var rankOrder = this.model.get( 'parts' ).findWhere( {
				type: 'rank_order',
			} );

			var googleAutocomplete = this.model.get( 'parts' ).findWhere( {
				has_autocomplete: 1,
			} );

			if ( rankOrder || googleAutocomplete ) {
				self.$el.addClass( 'has-search-not-found' );
			}

		},

		triggerKeyUp: function( e ) {
			$( e.target ).trigger( 'keyup' );
		},

		onSubmitButtonLabelChange: function( e ) {
			var data = {
				callback: 'onSubmitButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onOptionalPartLabelChange: function( e ) {
			var data = {
				callback: 'onOptionalPartLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onRequiredPartLabelChange: function( e ) {
			var data = {
				callback: 'onRequiredPartLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onSubmissionsLeftLabelChange: function( model, value ) {
			var data = {
				callback: 'onSubmissionsLeftLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );

			var dropdowns = justwpforms.form.get( 'parts' ).filter( function( part ) {
				if ( 'select' != part.get( 'type' ) ) {
					return false;
				}

				var hasLimitedOptions = part.get( 'options' ).find( function( option ) {
					var limitSubmissionAmount = option.get( 'limit_submissions_amount' );
					return typeof limitSubmissionAmount !== 'undefined' && limitSubmissionAmount != '';
				} );

				return hasLimitedOptions;
			} );

			dropdowns.forEach( function( dropdown ) {
				dropdown.fetchHtml( function( response ) {
					var data = {
						id: dropdown.get( 'id' ),
						html: response,
					};

					justwpforms.previewSend( 'justwpforms-form-part-refresh', data );
				} );
			} );
		},

		onCharLimitMinWordsChange: function ( e ) {
			var data = {
				callback: 'onCharLimitMinWordsChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onCharLimitMaxWordsChange: function ( e ) {
			var data = {
				callback: 'onCharLimitMaxWordsChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onCharLimitMinCharsChange: function ( e ) {
			var data = {
				callback: 'onCharLimitMinCharsChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onCharLimitMaxCharsChange: function ( e ) {
			var data = {
				callback: 'onCharLimitMaxCharsChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},
		onNoResultsLabelChange: function( e ) {
			var data = {
				callback: 'onNoResultsLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onShowResultsLabelChange: function( e ) {
			var data = {
				callback: 'onPollShowResultsLabelChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onBackToPollLabelChange: function( e ) {
			var data = {
				callback: 'onBackToPollLabelChangeCallback'
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onMaxFilesUploadedLabelChange: function( e ) {
			var data = {
				callback: 'onAttachmentMaxFilesUploadedLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onFilesBrowseLabelChange: function( e ) {
			var data = {
				callback: 'onFilesBrowseLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onFilesDeleteLabelChange: function( e ) {
			var data = {
				callback: 'onFilesDeleteLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onSignatureStartDrawingButtonLabelChange: function( e ) {
			var data = {
				callback: 'onSignatureStartDrawingButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onSignatureStartOverButtonLabelChange: function( e ) {
			var data = {
				callback: 'onSignatureStartOverButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onSignatureClearButtonLabelChange: function( e ) {
			var data = {
				callback: 'onSignatureClearButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onSignatureDoneButtonLabelChange: function( e ) {
			var data = {
				callback: 'onSignatureDoneButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

		onPhoneLabelCountryChange: function( e ) {
			var data = {
				callback: 'onPhoneLabelCountryChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data )
		},

		onPhoneLabelNumberChange: function( e ) {
			var data = {
				callback: 'onPhoneLabelNumberChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data )
		},

		onAbandonedResumeSaveButtonLabelChange: function( e ) {
			var data = {
				callback: 'onAbandonedResumeSaveButtonLabelChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		},

	} );

	classes.views.FormStyle = classes.views.Base.extend( {
		template: '#justwpforms-form-style-template',

		events: _.extend( {}, classes.views.Base.prototype.events, {
			'change [data-attribute]': 'onAttributeChange',
			'click h3.accordion-section-title': 'onGroupClick',
			'click .customize-panel-back': 'onGroupBackClick',
			'change [data-target="form_class"]': 'onFormClassChange',
			'change [data-target="css_var"] input[type=radio]': 'onRadioChange',
			'navigate-to-group': 'navigateToGroup',
		} ),

		pointers: {},

		initialize: function() {
			classes.views.Base.prototype.initialize.apply( this, arguments );

			this.listenTo( this.model, 'change:additional_css', _.debounce( function( model, value ) {
				this.onAdditionalCSSChange( model, value );
			}, 500 ) );
			this.listenTo( this.model, 'change:form_title', this.onChangeTitleDisplay );

			this.styles = new Backbone.Collection();
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );
			this.applyConditionClasses();
			return this;
		},

		onAdditionalCSSChange: function( model, value ) {
			justwpforms.form.fetchCustomCSS( function( response ) {
				api.previewer.send( 'justwpforms-custom-css-updated', response );
			} );
		},

		applyConditionClasses: function() {
			var self = this;

			var partTypes = justwpforms.form.get( 'parts' ).map( function( model ) {
				return model.get( 'type' );
			} );

			partTypes = _.union( partTypes );

			for ( var i = 0; i < partTypes.length; i++ ) {
				var className = 'has-' + partTypes[i];

				if ( self.$el.hasClass( className ) ) {
					className = className + '-part';
				}

				self.$el.addClass( className );
			}

			var hasSubmitInline = ( justwpforms.form.get( 'captcha' ) != '1' )
				&& ( justwpforms.form.get( 'parts' ).findLastIndex( { width: 'auto' } ) !== -1 );

			if ( hasSubmitInline ) {
				this.$el.addClass( 'has-submit-inline' );
			}

			_( formIntegrations ).each( function( value, index ) {
				if ( '0' !== value ) {
					self.$el.addClass( 'has-' + index + '-integration' );

					if ( value !== '1' ) {
						self.$el.addClass( 'has-' + value + '-integration' );
					}
				}
			} );
		},

		ready: function() {
			this.initColorPickers();
			this.initUISliders();
			this.setupHelpPointers();
			this.initCodeEditors();

			if ( justwpforms.savedStates.style.activeSection ) {
				this.navigateToGroup( justwpforms.savedStates.style.activeSection );
			}

			$( '.justwpforms-style-controls-group' ).on( 'scroll', function() {
				justwpforms.savedStates.style.scrollTop = $( this ).scrollTop();
			} );
		},

		setScrollPosition: function() {
			$( '.justwpforms-style-controls-group.open', this.$el ).scrollTop( justwpforms.savedStates.style.scrollTop );
		},

		onFormClassChange: function( e ) {
			e.preventDefault();

			var data = {
				attribute: $( e.target ).data( 'attribute' ),
				callback: 'onFormClassChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-class-update', data );
		},

		refreshFormParts: function( partAttribute, value ) {
			justwpforms.form.get( 'parts' ).forEach( function( $partModel ) {
				$partModel.set( partAttribute, value );
			} );

			justwpforms.form.get( 'parts' ).fetchHtml( function( response ) {
				justwpforms.previewSend( 'justwpforms-form-parts-refresh', response );
			} );
		},

		onChangeTitleDisplay: function() {
			var data = {
				attribute: 'form_title',
				callback: 'onFormClassChangeCallback',
			};

			justwpforms.previewSend( 'justwpforms-form-class-update', data );
		},

		onRadioChange: function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var attribute = $target.data( 'attribute' );
			var variable = $target.parents( '.justwpforms-buttonset-control' ).data( 'variable' );

			var value = $target.val();

			var data = {
				variable: variable,
				value: value,
			};

			justwpforms.previewSend( 'justwpforms-css-variable-update', data );
		},

		onAttributeChange: function( e ) {
			e.preventDefault();

			var $target = $( e.target );
			var attribute = $target.data( 'attribute' );
			var value = $target.val();

			if ( $target.is( ':checkbox' ) ) {
				value = $target.is( ':checked' ) ? value : '';
			}

			justwpforms.form.set( attribute, value );
		},

		onGroupClick: function( e ) {
			e.preventDefault();

			var self = this;

			$( '.justwpforms-style-controls-group', this.$el ).removeClass( 'open' ).addClass( 'animate' );

			setTimeout( function() {
				$( '.justwpforms-divider-control', this.$el )
					.removeClass( 'active' )
					.addClass( 'inactive' );

				var $linkTab = $( e.target ).parent();
				var $group = $linkTab.next();

				$group.addClass( 'open' );
				self.setScrollPosition();

				justwpforms.savedStates.style.activeSection = $linkTab.attr( 'id' );
			}, 200 );
		},

		onGroupBackClick: function( e ) {
			e.preventDefault();

			$( '.justwpforms-divider-control', this.$el )
				.removeClass( 'inactive' )
				.addClass( 'active' );

			var $section = $( e.target ).closest( '.justwpforms-style-controls-group' );

			$section.addClass( 'closing' );

			setTimeout( function() {
				$section.removeClass('closing open');
			}, 200);

			justwpforms.savedStates.style.activeSection = '';
			justwpforms.savedStates.style.scrollTop = 0;
		},

		navigateToGroup: function( groupID ) {
			if ( ! groupID ) {
				return;
			}

			var $group = $( '#' + groupID, this.$el );

			if ( ! $group.length ) {
				return;
			}

			$( '.justwpforms-style-controls-group', this.$el ).removeClass( 'open' );

			$( '.justwpforms-divider-control', this.$el )
				.removeClass( 'active' )
				.addClass( 'inactive' );

			$group.next().removeClass( 'animate' ).addClass( 'open' );

			this.setScrollPosition();
		},

		initColorPickers: function() {
			var self = this;
			var $colorInputs = $( '.justwpforms-color-input', this.$el );

			$colorInputs.each( function( index, el ) {
				var $control = $( el ).parents( '.customize-control' );
				var variable = $control.data( 'variable' );

				$( el ).wpColorPicker( {
					defaultColor: $( el ).attr( 'data-default' ),
					change: function( e, ui ) {
						var value = ui.color.toString();

						self.model.set( $( el ).attr( 'data-attribute' ), value );

						var data = {
							variable: variable,
							value: value,
						};

						justwpforms.previewSend( 'justwpforms-css-variable-update', data );
					}
				} );
			} );
		},

		initUISliders: function() {
			var self = this;
			var $container = this.$el.find( '.justwpforms-range-control, .justwpforms-form-width-range-control, .justwpforms-form_title-range-control' );

			$container.each( function( index, el ) {
				var $this = $(this);
				var variable = $this.data('variable');
				var $sliderInput = $( 'input[type=range]', $this );

				$sliderInput.on( 'change', function() {
					var $this = $(this);

					self.model.set( $sliderInput.attr( 'data-attribute' ), $this.val() );

					var data = {
						variable: variable,
						value: $this.val() + $( el ).attr( 'data-unit' ),
					};

					justwpforms.previewSend( 'justwpforms-css-variable-update', data );
				});
			} );
		},

		initCodeEditors: function() {
			if ( ! $( '.justwpforms-code-control', this.$el ).length ) {
				return;
			}

			var self = this;

			$( '.justwpforms-code-control', this.$el ).each( function() {
				var $this = $( this );
				var $el = $( 'textarea', $this );

				if ( 'rich' === $this.attr( 'data-mode' ) ) {
					self.initSyntaxHighlightingEditor( $el );
				} else {
					self.initPlainTextEditor( $el );
				}
			} );
		},

		initSyntaxHighlightingEditor: function( $el ) {
			var self = this;
			var attribute = $el.attr( 'data-attribute' );

			var editor = wp.codeEditor.initialize(
				$el.attr( 'id' ),
				{
					codemirror: {
						"mode": $el.attr( 'data-mode' ),
						"lint": true,
						"lineNumbers": true,
						"styleActiveLine": true,
						"indentUnit": 2,
						"indentWithTabs": true,
						"tabSize": 2,
						"lineWrapping": true,
						"autoCloseBrackets": true,
						"matchBrackets": true,
						"continueComments": true,
						"extraKeys": {
							"Ctrl-Space": "autocomplete",
							"Ctrl-\/": "toggleComment",
							"Cmd-\/": "toggleComment",
							"Alt-F": "findPersistent",
							"Ctrl-F": "findPersistent",
							"Cmd-F": "findPersistent"
						},
						"direction": "ltr",
						"gutters": [ "CodeMirror-lint-markers" ],
					}
				}
			);

			editor.codemirror.on( 'change', function() {
				var value = editor.codemirror.getValue();

				self.model.set( attribute, value );
			} );
		},

		initPlainTextEditor: function( $el ) {
			var self = this;
			var attribute = $el.attr( 'data-attribute' );

			$el.on( 'blur', function onBlur() {
				$el.data( 'next-tab-blurs', false );
			} );

			$el.on( 'keydown', function onKeydown( event ) {
				var selectionStart, selectionEnd, value, tabKeyCode = 9, escKeyCode = 27;

				if ( escKeyCode === event.keyCode ) {
					if ( ! $el.data( 'next-tab-blurs' ) ) {
						$el.data( 'next-tab-blurs', true );
						event.stopPropagation(); // Prevent collapsing the section.
					}
					return;
				}

				// Short-circuit if tab key is not being pressed or if a modifier key *is* being pressed.
				if ( tabKeyCode !== event.keyCode || event.ctrlKey || event.altKey || event.shiftKey ) {
					return;
				}

				// Prevent capturing Tab characters if Esc was pressed.
				if ( $el.data( 'next-tab-blurs' ) ) {
					return;
				}

				selectionStart = $el[0].selectionStart;
				selectionEnd = $el[0].selectionEnd;
				value = $el[0].value;

				if ( selectionStart >= 0 ) {
					$el[0].value = value.substring( 0, selectionStart ).concat( '\t', value.substring( selectionEnd ) );
					$el.selectionStart = $el[0].selectionEnd = selectionStart + 1;
				}

				event.stopPropagation();
				event.preventDefault();
			});

			$el.on( 'keyup', function( e ) {
				self.model.set( attribute, $( e.target ).val() );
			} );
		}
	} );

	Previewer = {
		$: $,
		ready: false,

		getPartModel: function( id ) {
			return justwpforms.form.get( 'parts' ).get( id );
		},

		getPartElement: function( html ) {
			return this.$( html );
		},

		bind: function() {
			this.ready = true;

			// Form title pencil
			api.previewer.bind(
				'justwpforms-title-pencil-click',
				this.onPreviewPencilClickTitle.bind( this )
			);

			// Part pencils
			api.previewer.bind(
				'justwpforms-pencil-click-part',
				this.onPreviewPencilClickPart.bind( this )
			);
		},

		/**
		 *
		 * Previewer callbacks for pencils
		 *
		 */
		onPreviewPencilClickPart: function( id ) {
			justwpforms.navigate( 'build', { trigger: true } );

			var $partWidget = $( '[data-part-id="' + id + '"]' );

			if ( ! $partWidget.hasClass( 'justwpforms-widget-expanded' ) ) {
				$partWidget.find( '.toggle-indicator' ).trigger( 'click' );
			}

			$( 'input', $partWidget ).first().trigger( 'focus' );
		},

		onPreviewPencilClickTitle: function( id ) {
			justwpforms.navigate( 'build', { trigger: true } );

			$( 'input[name="post_title"]' ).trigger( 'focus' );
		},

		onOptionalPartLabelChangeCallback: function( $form ) {
			var optionalLabel = justwpforms.form.get( 'optional_part_label' );
			$( '.justwpforms-optional', $form ).text( optionalLabel );
		},

		onRequiredPartLabelChangeCallback: function( $form ) {
			var requiredLabel = justwpforms.form.get( 'required_field_label' );
			$( '.justwpforms-required', $form ).text( requiredLabel );
		},

		onSubmissionsLeftLabelChangeCallback: function( $form ) {
			var submissionsLeftLabel = justwpforms.form.get( 'submissions_left_label' );
			$( '.justwpforms-submissions-left', $form ).text( submissionsLeftLabel );
		},

		onSubmitButtonLabelChangeCallback: function ( $form ) {
			var submitLabel = justwpforms.form.get( 'submit_button_label' );
			$( '.justwpforms-button--submit', $form ).html( submitLabel );
		},

		onCharLimitMinWordsChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'words_label_min' );
			$( '.justwpforms-part__char-counter.word_min span.counter-label', $form ).text( label );
		},

		onCharLimitMaxWordsChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'words_label_max' );
			$( '.justwpforms-part__char-counter.word_max span.counter-label', $form ).text( label );
		},

		onCharLimitMinCharsChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'characters_label_min' );
			$( '.justwpforms-part__char-counter.character_min span.counter-label', $form ).text( label );
		},

		onCharLimitMaxCharsChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'characters_label_max' );
			$( '.justwpforms-part__char-counter.character_max span.counter-label', $form ).text( label );
		},

		onNoResultsLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'no_results_label' );

			$( 'li.justwpforms-custom-select-dropdown__not-found', $form ).text( label );
		},

		onAbandonedResumeSaveButtonLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'abandoned_resume_save_button_label' );
			$( '.justwpforms-save-session', $form ).html( label );
		},

		onPollShowResultsLabelChangeCallback:  function( $form ) {
			var label = justwpforms.form.get( 'show_results_label' );
			$( '.justwpforms-poll__show-results span', $form ).text( label );
		},

		onBackToPollLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'back_to_poll_label' );
			$( '.justwpforms-poll__back-to-poll span', $form ).text( label );
		},

		onAttachmentMaxFilesUploadedLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'max_files_uploaded_label' );
			$( '.justwpforms-attachment__counter .counter-label-1', $form ).text( label );
		},

		onFilesBrowseLabelChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'file_upload_browse_label' );
			$( '.justwpforms-attachment .justwpforms-input-group__suffix--button button.justwpforms-plain-button', $form ).html( label );
		},

		onFilesDeleteLabelChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'file_upload_delete_label' );
			$( '.justwpforms-attachment-link.justwpforms-delete-attachment', $form ).html( label );
		},

		onSignatureStartDrawingButtonLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'field_signature_start_drawing_button_label' );
			$( '.justwpforms--signature-area--start-drawing', $form ).text( label );
		},

		onSignatureStartOverButtonLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'field_signature_start_over_button_label' );
			$( '.justwpforms--signature-area--edit-drawing', $form ).text( label );
		},

		onSignatureClearButtonLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'field_signature_clear_button_label' );
			$( '.justwpforms--signature-area--clear-drawing', $form ).text( label );
		},

		onSignatureDoneButtonLabelChangeCallback: function( $form ) {
			var label = justwpforms.form.get( 'field_signature_done_button_label' );
			$( '.justwpforms--signature-area--done-drawing', $form ).text( label );
		},

		onAbandonedResumeToggleCallback: function( $form ) {
			var value = justwpforms.form.get( 'abandoned_resume_response_expire' );

			if ( '' !== value ) {
				$form.attr( 'data-justwpforms-resumable', '' );
			} else {
				$form.removeAttr( 'data-justwpforms-resumable' );
			}
		},

		onPhoneLabelCountryChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'phone_label_country_code' );
			$( '.justwpforms-phone-international .justwpforms-input-country-code .justwpforms-phone-international-labels', $form ).text( label );
		},

		onPhoneLabelNumberChangeCallback: function ( $form ) {
			var label = justwpforms.form.get( 'phone_label_number' );
			$( '.justwpforms-phone-international .justwpforms-input .justwpforms-phone-international-labels', $form ).text( label );
		},

		/**
		 *
		 * Previewer callbacks for live part DOM updates
		 *
		 */
		onPartDuplicateCallback: function( $form ) {
			// Noop
		},

		onPartMouseOverCallback: function( id, html ) {
			var $part = this.$( html );
			$part.addClass( 'highlighted' );
		},

		onPartMouseOutCallback: function( id, html ) {
			var $part = this.$( html );
			$part.removeClass( 'highlighted' );
		},

		onPartLabelChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var $label = this.$( '.justwpforms-part-wrap > .justwpforms-part__label-container span.label', $part ).first();

			$label.text( part.get( 'label' ) );
		},

		onRequiredCheckboxChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var required = part.get( 'required' );
			var optionalLabel = justwpforms.form.get( 'optional_part_label' );
			var requiredLabel = justwpforms.form.get( 'required_field_label' );

			if ( 0 === parseInt( required, 10 ) ) {
				$part.removeAttr( 'data-justwpforms-required' );
				$( '.justwpforms-optional', $part ).text( optionalLabel );
			} else {
				$( '.justwpforms-required', $part ).text( requiredLabel );
				$part.attr( 'data-justwpforms-required', '' );
			}
		},

		onPartWidthChangeCallback: function( id, html, options ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var width = part.get( 'width' );

			$part.removeClass( 'justwpforms-part--width-half' );
			$part.removeClass( 'justwpforms-part--width-full' );
			$part.removeClass( 'justwpforms-part--width-third' );
			$part.removeClass( 'justwpforms-part--width-quarter' );
			$part.removeClass( 'justwpforms-part--width-auto' );
			$part.addClass( 'justwpforms-part--width-' + width );
		},

		onPlaceholderChangeCallback: function( id, html ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );

			// this.$( 'input:not([type="hidden"])', $part ).first().attr( 'placeholder', part.get( 'placeholder' ) );
			this.$( 'input:not([type="hidden"])', $part ).attr( 'placeholder', part.get( 'placeholder' ) );
		},

		onDefaultValueChangeCallback: function( id, $part ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var default_value = part.get( 'default_value' );

			$part.find( ':input' ).val( default_value );

			if ( part.get( 'rich_text' ) ) {
				var instance = $part.data( 'justwpformPart' );

				instance.editor.setContent( default_value );
			}
		},

		onLabelPlacementChangeCallback: function( id, html, options ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );

			$part.removeClass( 'justwpforms-part--label-above' );
			$part.removeClass( 'justwpforms-part--label-below' );
			$part.removeClass( 'justwpforms-part--label-left' );
			$part.removeClass( 'justwpforms-part--label-right' );
			$part.removeClass( 'justwpforms-part--label-inside' );
			$part.addClass( 'justwpforms-part--label-' + part.get( 'label_placement' ) );
		},

		onCSSClassChangeCallback: function( id, html, options ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var previousClass = part.previous( 'css_class' );
			var currentClass = part.get( 'css_class' );

			$part.removeClass( previousClass );
			$part.addClass( currentClass );
		},

		onSubPartAdded: function( id, partHTML, optionHTML ) {
			var partView = justwpforms.sidebar.current.partViews.get( id ).get( 'view' );
			partView.onSubPartAdded( id, partHTML, optionHTML );
		},

		onFormClassChangeCallback: function( attribute, html, options ) {
			var $formContainer = this.$( html );
			var previousClass = justwpforms.form.previous( attribute );
			var currentClass = justwpforms.form.get( attribute );

			$formContainer.removeClass( previousClass );
			$formContainer.addClass( currentClass );

			api.previewer.send( 'justwpforms-form-class-updated' );
		},

		onFocusRevealDescriptionCallback: function( id, html, options ) {
			var part = justwpforms.form.get( 'parts' ).get( id );
			var $part = this.$( html );
			var focusRevealDescription = part.get('focus_reveal_description');

			if ( 1 == focusRevealDescription ) {
				$part.addClass( 'justwpforms-part--focus-reveal-description' );
			} else {
				$part.removeClass( 'justwpforms-part--focus-reveal-description' );
			}
		},

		onPartPrefixChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $prefix = this.$( '.justwpforms-input-group__prefix span', $part );

			$prefix.text( part.get( 'prefix' ) );
		},

		onPartSuffixChangeCallback: function( id, html, options, $ ) {
			var part = this.getPartModel( id );
			var $part = this.getPartElement( html );
			var $suffix = this.$( '.justwpforms-input-group__suffix span', $part );

			$suffix.text( part.get( 'suffix' ) );
		},
	};

	justwpforms = window.justwpforms = new justwpforms();
	justwpforms.classes = classes;
	justwpforms.factory = PartFactory;
	justwpforms.previewer = Previewer;

	justwpforms.utils = {
		uniqueId: function( prefix, collection ) {
			if ( collection ) {
				var increments = collection
					.pluck( 'id' )
					.map( function( id ) {
						var numberId = id.match( /_(\d+)$/ );
						numberId = numberId !== null ? parseInt( numberId[1] ): 0;
						return numberId;
					} )
					.sort( function( a, b ) {
						return b - a;
					} );

				var increment = increments.length ? increments[0] + 1 : 1;

				return prefix + increment;
			}

			return _.uniqueId( prefix );
		},

		fetchPartialHtml: function( partialName, success ) {
			var data = {
				action: 'justwpforms-form-fetch-partial-html',
				'justwpforms-nonce': api.settings.nonce.justwpforms,
				justwpforms: 1,
				wp_customize: 'on',
				form_id: justwpforms.form.id,
				form: JSON.stringify( justwpforms.form.toJSON() ),
				partial_name: partialName
			};

			var request = $.ajax( ajaxurl, {
				type: 'post',
				dataType: 'html',
				data: data
			} );

			justwpforms.previewSend( 'justwpforms-form-partial-disable', {
				partial: partialName
			} );

			request.done( success );
		},

		unprefixOptionId: function( optionId ) {
			var split = optionId.split( '_' );
			var numericPart = _(split).last();

			return numericPart;
		},

		setMediaFilters: function( types ) {
			var AttachmentFilters = wp.media.view.AttachmentFilters;
			var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;

			var MediaLibraryUploadedFilter = AttachmentFilters.extend( {
				id: 'media-attachment-uploaded-filter',

				createFilters: function() {
					var filters = {};
					var priority = 10;

					filters.all = {
						text: 'All types',
						props: {
							type: null
						},
						priority: priority
					};

					_( types ).each( function( label, filterType ) {
						priority = priority + 10;

						filters[filterType] = {
							text: label,
							props: {
								type: filterType
							},
							priority: priority
						}
					} );

					this.filters = filters;
				},
			} );

			wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend( {
				createToolbar: function() {
					AttachmentsBrowser.prototype.createToolbar.call( this );

					this.toolbar.set(
						'MediaLibraryUploadedFilter',
						new MediaLibraryUploadedFilter( {
							controller: this.controller,
							model: this.collection.props,
							priority: -100
						} ).render()
					);
				},
			} );
		},

		onAttachmentSelected: function( editor, types ) {
			var selection = justwpforms.media.state().get( 'selection' ).first();

			if ( ! selection ) {
				return;
			}

			var attachment = selection.toJSON();
			var content = '';

			var props = wp.media.string.props( {
				link: 'embed',
			}, attachment );

			switch ( attachment.type ) {
				case 'image' :
					if ( types.hasOwnProperty( 'image' ) ) {
						content = wp.media.string.image( {
							url: attachment.url,
							alt: attachment.title || '',
							align: 'none',
							link: 'none',
						} );
					}
					break;
				case 'video' :
					if ( types.hasOwnProperty( 'video' ) ) {
						content = wp.media.string.video( props, attachment );
					}

					if ( types.hasOwnProperty( 'video_link' ) ) {
						content = wp.media.string.link( {
							linkUrl: attachment.url,
							title: attachment.title || 'Link',
						} );
					}
					break;
				case 'audio' :
					if ( types.hasOwnProperty( 'audio' ) ) {
						content = wp.media.string.audio( props, attachment );
					}

					if ( types.hasOwnProperty( 'audio_link' ) ) {
						content = wp.media.string.link( {
							linkUrl: attachment.url,
							title: attachment.title || 'Link',
						} );
					}
					break;
			}

			wp.media.editor.wpActiveEditor = editor;
			wp.media.editor.insert( content );
			wp.media.editor.wpActiveEditor = null;
			justwpforms.media.remove();
			justwpforms.media = null;
		},

		onImageEdit: function( options ) {
			var minimalMode = options.minimalMode;

			wp.media.events.once( 'editor:frame-create', function( frameData ) {
				if ( minimalMode ) {
					frameData.frame.views.view.$el.addClass( 'justwpforms-media-edit-minimal' );
				}
			} );
		}
	};

	api.bind( 'ready', function() {
		justwpforms.start();

		api.previewer.bind( 'ready', function() {
			justwpforms.flushBuffer();
			justwpforms.previewer.bind();
		} );
	} );

	justwpforms.factory.model = _.wrap( justwpforms.factory.model, function( func, attrs, options, BaseClass ) {
		BaseClass = justwpforms.classes.models.parts[attrs.type];

		return func( attrs, options, BaseClass );
	} );

	justwpforms.factory.view = _.wrap( justwpforms.factory.view, function( func, options, BaseClass ) {
		BaseClass = justwpforms.classes.views.parts[options.type];

		return func( options, BaseClass );
	} );

} ) ( jQuery, _, Backbone, wp.customize, _justwpformsSettings, _justwpformsIntegrations );
