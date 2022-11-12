( function( $, _, Backbone, api, conditionSettings, settings ) {
	var FormSetup = justwpforms.classes.views.FormSetup;
	var FormEmail = justwpforms.classes.views.FormEmail;
	var FormMessages = justwpforms.classes.views.FormMessages;
	var FormBuild = justwpforms.classes.views.FormBuild;
	var FormModel = justwpforms.classes.models.Form;

	var ConditionModels = {
		Condition: Backbone.Model.extend( {
			defaults: {
				if: [],
				then: [],
			},

			initialize: function( attrs, options ) {
				this.attributes.if = new IfsCollection( this.get( 'if' ), options );
				this.attributes.then = new ConditionModels.Then( this.get( 'then' ), options );
			},

			toJSON: function() {
				var json = Backbone.Model.prototype.toJSON.call( this );
				json.if = json.if.toJSON();
				json.then = json.then.toJSON();

				return json;
			},
		} ),

		If: Backbone.Model.extend( {
			defaults: {
				op: conditionSettings.constants.AND,
				key: '',
				cmp: conditionSettings.constants.EQUAL,
				comparison: -1
			},
		} ),

		Then: Backbone.Model.extend( {
			defaults: {
				cb: '',
				key: '',
				args: []
			}
		} )
	};

	var IfsCollection = Backbone.Collection.extend( {
		model: ConditionModels.If
	} );

	var ConditionHelpers = {
		isChoicePart: function( partModel ) {
			if ( [ 'radio', 'checkbox', 'select', 'option' ].includes( partModel.get( 'type' ) ) ) {
				return true;
			}

			return false;
		},

		getAllChoiceParts: function( formItemId ) {
			var parts = justwpforms.form.get( 'parts' );
			var types = [ 'radio', 'checkbox', 'select' ];
			var choiceParts = parts.filter( function( model ) {
				return (
					types.includes( model.get( 'type' ) ) &&
					model.id !== formItemId &&
					! model.get( 'options' ).pluck( 'id' ).includes( formItemId )
				);
			} );

			return choiceParts;
		},
	};

	/**
	 * Loop through all applicable form parts and add logic views to them.
	 */
	_.each( justwpforms.classes.views.parts, function( part, partType ) {
		var Part = justwpforms.classes.views.parts[partType];

		justwpforms.classes.views.parts[partType] = Part.extend( {
			logicView: null,
			settingsLogicViews: [],

			ready: function() {
				Part.prototype.ready.apply( this, arguments );

				this.addLogicView();
				this.addPartSettingsLogicView();
			},

			addLogicView: function() {
				var self = this;

				this.logicView = new LogicView( {
					el: $( '.justwpforms-logic-view', self.$el ),
					options: {
						autoAdd: true,
						logicGroupSettings: {
							type: 'part'
						},
						formItem: self.model
					}
				} );

				this.logicView.trigger( 'ready' );
			},

			/**
			 * This is for adding logic view to part settings. A good example is Payment part and logic view added to
			 * `Price` control.
			 *
			 * It still uses the same logic view but adds a bunch of additional options for easier reference
			 * and changes form item's ID to the following format: `partID:partSettingID`. The `:` is a key here
			 * so backend can correctly parse this condition and knows which part it belongs to.
			 */
			addPartSettingsLogicView: function() {
				var self = this;

				$( '.justwpforms-part-settings-logic-view', this.$el ).each( function() {
					var logicView = new LogicView( {
						el: $( this ),
						options: {
							autoAdd: false,
							logicGroupSettings: {
								type: $( this ).attr( 'data-logic-type' ),
								thenText: $( this ).attr( 'data-logic-then-text' )
							},
							formItem: new Backbone.Model( {
								id: self.model.id + ':' + $( this ).attr( 'data-logic-id' ),
								partID: self.model.id,
								fieldID: $( this ).attr( 'data-logic-id' ),
								type: 'part-settings'
							} )
						}
					} );

					logicView.trigger( 'ready' );
					self.settingsLogicViews.push( logicView );
				} );
			}
		} );
	} );

	/**
	 * Extend choice-based fields to support conditional logic.
	 */
	[
		'radio',
		'checkbox',
		'select',
	].forEach( function( fieldView ) {
		var FieldView = justwpforms.classes.views.parts[fieldView];

		justwpforms.classes.views.parts[fieldView] = FieldView.extend( {
			
			addOptionItemView: function( optionModel, options ) {
				options = options || {};
				
				if ( options.duplicateOf ) {
					this.duplicateChoiceLogic( options.duplicateOf, optionModel );
				}

				FieldView.prototype.addOptionItemView.apply( this, arguments );
			},

			duplicateChoiceLogic: function( option, newOption ) {
				var optionConditions = justwpforms.form.get( 'conditions' ).filter( function( model ) {
					return model.get( 'then' ).get( 'key' ) === option.id;
				} );

				if ( 0 === optionConditions.length ) {
					return;
				}

				optionConditions.forEach( function( model ) {
					var newModel = new ConditionModels.Condition( model.toJSON() );

					newModel.get( 'then' ).set( 'key', newOption.id );
					justwpforms.form.get( 'conditions' ).add( newModel );
				} );
			},

		} );
	} );

	/**
	 * Extend choice views in all choice-based fields to support conditional logic.
	 */
	[
		'radioOptionItem',
		'radioOptionHeading',
		'checkboxOptionItem',
		'checkboxOptionHeading',
		'selectOptionItem',
		'selectOptionHeading',
	].forEach( function( optionView ) {
		var OptionView = justwpforms.classes.views[optionView];

		justwpforms.classes.views[optionView] = OptionView.extend( {
			events: _.extend( {}, OptionView.prototype.events, {
				'click .justwpforms-item-logic': 'onItemLogicButtonClick',
			} ),

			logicView: null,

			onReady: function() {
				OptionView.prototype.onReady.apply( this, arguments );

				this.addLogicView();
			},

			onItemLogicButtonClick: function( e ) {
				e.preventDefault();
				e.stopPropagation();

				$( '.justwpforms-part-choice-logic-wrap', this.$el ).slideToggle( 300, function() {
					$( e.target ).toggleClass( 'opened' );
				} );
			},

			addLogicView: function() {
				var self = this;
				
				this.logicView = new LogicView( {
					el: $( '.justwpforms-logic-view', self.$el ),
					options: {
						autoAdd: true,
						logicGroupSettings: {
							type: 'option',
							subtype: this.model.get( 'is_heading' ) ? 'heading' : 'item'
						},
						formItem: self.model
					}
				} );

				this.logicView.trigger( 'ready' );
			},

		} );
	} );

	var LogicGroupsModel = Backbone.Model.extend( {
		toJSON: function() {
			var json = Backbone.Model.prototype.toJSON.apply( this, arguments );

			if ( json.options && 'template' === json.options.type && json.options.template && ! _.isFunction( json.options.template ) ) {
				json.options.template = _.template( $( '#customize-justwpforms-logic-' + json.options.template ).text() );
			}

			return json;
		},
	} );

	var LogicGroupsCollection = Backbone.Collection.extend( {
		model: LogicGroupsModel,
	} );

	/**
	 * Main logic view. This is a wrapper for logic groups.
	 */
	var LogicView = Backbone.View.extend( {
		events: {
			'click .justwpforms-conditional__add-group': 'onAddGroupClick',
		},

		// Used to cache conditions dropdown options on `conditions-refresh` event
		optionsQueue: {},

		initialize: function( attrs ) {
			this.formItem = attrs.options.formItem;

			/**
			 * `autoAdd` is a flag telling us whether we automatically add an empty logic group to this logic view
			 * if there are no existing logic groups or not.
			 */
			this.autoAdd = attrs.options.autoAdd;
			this.logicGroupSettings = attrs.options.logicGroupSettings;

			this.logicGroups = new LogicGroupsCollection();
			this.logicGroupViews = new Backbone.Collection();

			this.listenTo( this.logicGroups, 'add', this.onLogicGroupAdd );
			this.listenTo( this.logicGroups, 'change', this.onLogicGroupChange );
			this.listenTo( this.logicGroups, 'remove', this.onLogicGroupRemove );

			this.listenTo( justwpforms.form.get( 'parts' ), 'add', this.onPartAdd );
			this.listenTo( justwpforms.form.get( 'parts' ), 'remove', this.onPartRemove );

			this.listenTo( this, 'ready', this.onReady );

			/**
			 * Hook to `conditions-refresh` event triggered by some email marketing integrations and add
			 * options to `optionsQueue`
			 */
			$( '#customize-control-' + this.formItem.get( 'id' ) ).on( 'conditions-refresh', this.onConditionsRefresh.bind( this ) );
		},

		render: function() {
			this.setElement( this.$el );

			return this;
		},

		onReady: function() {
			this.$placeholder = $( '.no-parts', this.$el );

			this.addLogicGroups();
		},

		onConditionsRefresh: function( e, data ) {
			var control = data.control;
			var data = data.data;

			this.optionsQueue[control] = data;
		},

		/**
		 * Goes through all conditions and adds them to the current view. That means all conditions for the form part
		 * that this Logic View is for, or for control in Setup / Email step.
		 */
		addLogicGroups: function() {
			var self = this;

			var itemConditions = justwpforms.form.get( 'conditions' ).filter( function( model ) {
				return model.get( 'then' ).get( 'key' ) === self.formItem.id;
			} );

			if ( itemConditions.length ) {
				_( itemConditions ).each( function( model, index ) {
					var itemModel = new LogicGroupsModel( {
						condition: model
					} );

					self.logicGroups.add( itemModel );
					self.addLogicGroup( itemModel );
				} );

				return;
			}

			if ( this.autoAdd ) {
				if ( ConditionHelpers.getAllChoiceParts( this.formItem.id ).length ) {
					this.addNewLogicGroup();
				} else {
					this.showPlaceholder();
				}
			} else {
				this.hidePlaceholder();
			}
		},

		createLogicGroupModel: function() {
			var model = new LogicGroupsModel();
			var conditionModel = new ConditionModels.Condition();

			conditionModel.get( 'then' ).set( 'key', this.formItem.id );
			model.set( 'condition', conditionModel );

			return model;
		},

		addNewLogicGroup: function() {
			var model = this.createLogicGroupModel();
			model.get( 'condition' ).get( 'then' ).set( 'args', [] );
			this.addLogicGroup( model );
		},

		addLogicGroup: function( model ) {
			if ( ! model ) {
				return;
			}

			if ( ! ConditionHelpers.getAllChoiceParts( this.formItem.id ).length ) {
				this.showPlaceholder();
				$( 'button', this.$el ).hide();

				return;
			} else {
				this.hidePlaceholder();
			}

			this.logicGroups.add( model );
			justwpforms.trigger( 'logic-group-added', this.formItem );
		},

		addLogicGroupView: function( model ) {
			var self = this;

			var logicGroupView = new LogicGroupView( {
				model: model,
				options: {
					formItem: self.formItem
				}
			} );

			var viewModel = new Backbone.Model( {
				view: logicGroupView
			} );

			this.logicGroupViews.add( viewModel );

			var view = viewModel.get( 'view' );
			$( 'button', this.$el ).before( view.render().$el );
			view.trigger( 'ready' );
		},

		/**
		 * Checks if condition model is complete or not.
		 */
		isFilled: function( model ) {
			var filled = false;
			var type = this.logicGroupSettings.type;
			var then = model.get( 'condition' ).get( 'then' );

			if ( 'select' === type && then.get( 'cb' ) ) {
				filled = true;
			}

			if ( 'set' === type && then.get( 'args' ).length ) {
				filled = true;
			}

			if ( 'template' === type && then.get( 'args' ).length ) {
				filled = true;
			}

			return filled;
		},

		/**
		 * When logic group is added, we add logic group view.
		 *
		 * If logic group is complete, we add it to form's `conditions` object.
		 */
		onLogicGroupAdd: function( model ) {
			/**
			 * Check for `optionsQueue` here to see if there are some options to add from when
			 * `conditions-refresh` event was fired. This is handled through `optionsQueue` because
			 * of the steps sequence.
			 *
			 * Since `conditions-refresh` is about dynamic changes to available condition options based
			 * on user change, those are not known at form builder render time. That means we'd end up with empty
			 * logic group here if we didn't capture `conditions-refresh` event before and stored available options
			 * to `optionsQueue`.
			 */
			if ( this.logicGroupSettings.hasOwnProperty( 'options' ) && this.optionsQueue[this.formItem.id] ) {
				this.logicGroupSettings.options = this.optionsQueue[this.formItem.id];
			}

			model.set( 'options', this.logicGroupSettings );

			this.addLogicGroupView( model );

			if ( this.isFilled( model ) ) {
				justwpforms.form.get( 'conditions' ).add( model.get( 'condition' ) );
			}
		},

		onLogicGroupChange: function( model ) {
			if ( this.isFilled( model ) ) {
				justwpforms.form.get( 'conditions' ).add( model.get( 'condition' ) );
			}
		},

		onAddGroupClick: function( e ) {
			e.preventDefault();

			this.addNewLogicGroup();
		},

		onLogicGroupRemove: function( model, index, options ) {
			justwpforms.form.get( 'conditions' ).remove( model.get( 'condition' ), { silent: true } );

			var viewModel = this.logicGroupViews.at( options.index );
			var view = viewModel.get( 'view' );

			this.logicGroupViews.remove( viewModel );

			view.remove();
			view.unbind();
			view.model.destroy();
		},

		/**
		 * When part is added, check if there are choice parts. Then decide whether we add a new logic group
		 * in case `autoAdd` is set to true. Otherwise, show placeholder text.
		 */
		onPartAdd: function() {
			var parts = ConditionHelpers.getAllChoiceParts( this.formItem.id );

			if ( parts.length ) {
				if ( this.autoAdd && ! this.logicGroups.length ) {
					this.addNewLogicGroup();
				} else if ( this.autoAdd ) {
					this.showPlaceholder();
				}

				this.hidePlaceholder();
			}
		},

		/**
		 * When part is removed, check if
		 */
		onPartRemove: function() {
			var parts = ConditionHelpers.getAllChoiceParts( this.formItem.id );

			if ( ! parts.length ) {
				if ( this.autoAdd ) {
					this.showPlaceholder();
					$( 'button', this.$el ).hide();
				} else {
					this.logicGroups.reset();
				}
			}
		},

		onConditionsChange: function( model ) {
			justwpforms.form.trigger( 'change' );
		},

		/**
		 * Handle placeholder showing. There are two types of placeholder text:
		 *
		 * - When there are no choice parts around
		 * - When this part is the only choice part in which case it can't set up conditions on itself
		 *
		 * Placeholder element is the same, it's just the text that changes.
		 */
		showPlaceholder: function() {
			var placeholderText = this.$placeholder.attr( 'data-default-text' );

			if ( ConditionHelpers.isChoicePart( this.formItem ) ) {
				placeholderText = this.$placeholder.attr( 'data-only-part-text' );
			}

			$( 'p', this.$placeholder ).text( placeholderText );
			this.$placeholder.show();

			if ( this.autoAdd ) {
				this.$placeholder.nextAll().hide();
			}
		},

		hidePlaceholder: function() {
			this.$placeholder.hide();
			this.$placeholder.nextAll().show();
		}
	} );

	/**
	 * Logic group view.
	 *
	 * Logic groups are views consisting of dropdowns to specify condition callback, comparison part and value.
	 */
	var LogicGroupView = Backbone.View.extend( {
		template: '#customize-justwpforms-logic-group',

		events: {
			'change .justwpforms-conditional__action': 'onActionChange',
			'mousedown .justwpforms-conditional__action': 'onActionMousedown',
			'blur .justwpforms-conditional__action': 'onActionChange',
			'click .justwpforms-conditional__add': 'onAddClick',
			'click .justwpforms-conditional__delete': 'onDeleteClick',
			'keyup [data-then-value]': 'onThenChange',
			'change [data-then-value]': 'onThenChange'
		},

		initialize: function( attrs ) {
			this.template = _.template( $( this.template ).text() );
			this.formItem = attrs.options.formItem;
			this.conditionModel = this.model.get( 'condition' );
			this.options = this.model.get( 'options' );

			this.conditions = this.conditionModel.get( 'if' );
			this.conditionsViews = new Backbone.Collection();

			this.listenTo( this, 'ready', this.onReady );

			this.listenTo( this.conditions, 'add', this.addConditionView );
			this.listenTo( this.conditions, 'change', this.onConditionChange );
			this.listenTo( this.conditions, 'remove', this.onConditionRemove );

			this.listenTo( justwpforms.form.get( 'conditions' ), 'remove', this.onConditionsRemove );

			this.listenTo( this.formItem, 'change:label', this.onItemLabelChange );

			this.listenTo( this.conditionModel.get( 'then' ), 'change:cb', this.onCallbackChange );

			$( '#customize-control-' + this.formItem.id ).on( 'conditions-refresh', this.onConditionsRefresh.bind( this ) );
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );

			return this;
		},

		onConditionsRefresh: function( e, data ) {
			if ( $( e.target ).attr( 'id' ) !== 'customize-control-' + this.formItem.get( 'id' ) ) {
				return;
			}

			this.refreshLogic( data );
		},

		/**
		 * Refreshes options in logic dropdowns with new set passed in parameter.
		 *
		 * @param {object} data Logic data
		 */
		refreshLogic: function( data ) {
			var modelOptions = this.model.get( 'options' );
			modelOptions.options = data.data;

			this.model.set( 'options', modelOptions );

			while ( this.conditions.last() ) {
				this.conditions.remove( this.conditions.last() );
			}

			this.addNewCondition();

			var $newEl = this.template( this.model.toJSON() );
			this.$el.replaceWith( $newEl );
			this.setElement( $newEl );
		},

		/**
		 * Returns logic type. E.g. `set` or `show`.
		 */
		getType: function() {
			return this.options.type;
		},

		onReady: function() {
			if ( 'part' === this.getType() || 'option' === this.getType() ) {
				this.$actionDropdown = $( '.justwpforms-conditional__action', this.$el );
			}

			var then = this.conditionModel.get( 'then' );

			if ( ( 'set' === this.getType() || 'select' === this.getType() || 'template' === this.getType() ) ) {
				this.$thenInput = $( '[data-then-value]', this.$el );
				this.$thenInput.val( '' );

				then.set( 'cb', 'set' );
			}

			this.updateAllValues();
			this.addConditions();

			/**
			 * Decide if model should be added to `conditions` collection in form settings or not.
			 */

			var addModel = false;

			if ( 'part' === this.getType() && then.get( 'cb' ) ) {
				addModel = true;
			}

			/**
			 * If type requires additional input, like entering a value to text input for the condition to be complete,
			 * do not add to collection right away unless that value is set.
			 */
			if ( ( 'set' === this.getType() || 'select' === this.getType() || 'template' === this.getType() ) && then.get( 'args' ).length ) {
				addModel = true;
			}

			if ( addModel ) {
				justwpforms.form.get( 'conditions' ).add( this.conditionModel );
			}
		},

		/**
		 * Populate inputs in the logic group with current values, or reset to empty if possible values are no longer
		 * available in the form. This can happen in case some form part was removed and it's not available
		 * to choose from the dropdown anymore.
		 */
		updateAllValues: function() {
			if ( this.conditionModel.get( 'then' ).get( 'cb' ) && ( 'part' === this.getType() || 'option' === this.getType() ) ) {
				this.$actionDropdown.val( this.conditionModel.get( 'then' ).get( 'cb' ) ).trigger( 'change' );
			}

			if ( 'set' === this.getType() || 'select' === this.getType() || 'template' === this.getType() ) {
				this.$thenInput.val( '' );

				var value = this.conditionModel.get( 'then' ).get( 'args' )[0];

				if ( this.$thenInput.is( 'select' ) ) {
					if ( $( 'option[value=' + value + ']', this.$thenInput ).length ) {
						this.$thenInput.val( value );
					} else {
						this.$thenInput.val( '' );
					}
				} else {
					this.$thenInput.val( value );
				}

			}
		},

		addConditions: function() {
			var self = this;

			if ( this.conditions.length ) {
				this.conditions.each( function( model, index ) {
					self.addConditionView( model );
				} );
			} else {
				this.addNewCondition();
			}
		},

		addNewCondition: function() {
			var newCondition = new ConditionModels.If();

			if ( ! this.conditions.length ) {
				newCondition.set( 'op', conditionSettings.constants.ANDOR );
			}

			this.conditions.add( newCondition );
			justwpforms.trigger( 'condition-added', this.formItem );
		},

		addConditionView: function( model ) {
			var self = this;

			var conditionView = new ConditionView( {
				model: model,
				options: {
					type: self.getType(),
					formItemId: self.formItem.id
				}
			} );

			var viewModel = new Backbone.Model( {
				view: conditionView
			} );

			this.conditionsViews.add( viewModel );

			var view = viewModel.get( 'view' );
			$( '.justwpforms-conditional__static', this.$el ).before( view.render().$el );
			view.trigger( 'ready' );
		},

		onConditionChange: function() {
			if ( ! this.conditionModel.get( 'then' ).get( 'cb' ) ) {
				return;
			}

			if ( ( 'set' === this.getType() || 'select' === this.getType() || 'template' === this.getType() ) && ! this.conditionModel.get( 'then' ).get( 'args' ) ) {
				return;
			}

			justwpforms.form.get( 'conditions' ).add( this.conditionModel );

			this.conditionModel.collection.trigger( 'change' );
		},

		onConditionRemove: function( model, index, options ) {
			var viewModel = this.conditionsViews.at( options.index );
			var view = viewModel.get( 'view' );

			this.conditionsViews.remove( viewModel );

			view.remove();
			view.unbind();
			view.model.destroy();

			justwpforms.form.get( 'conditions' ).trigger( 'change' );

			if ( ! this.conditions.length ) {
				// if is in current view and has collection
				if ( this.model.collection ) {
					this.model.collection.remove( this.model );
				} else {
					justwpforms.form.get( 'conditions' ).remove( this.model.get( 'condition' ) );
				}
			}
		},

		onConditionsRemove: function( model, collection, options ) {
			options = options || {};

			if ( 'then' === options.mode ) {
				var affectedConditions = this.conditions.filter( function( conditionModel ) {
					return conditionModel.get( 'key' ) === model.get( 'then' ).get( 'key' );
				} );

				this.conditions.remove( affectedConditions );
			}

			if ( 'if' === options.mode ) {
				affectedConditions = this.conditions.filter( function( conditionModel ) {
					return model.get( 'if' ).findWhere( { key: conditionModel.get( 'key' ) } );
				} );

				this.conditions.remove( affectedConditions );
			}
		},

		onActionChange: function( e ) {
			var $select = $( e.target );
			var value = $select.val();

			var then = this.conditionModel.get( 'then' );
			then.set( 'cb', value );

			if ( then.get( 'cb' ) ) {
				var $partDropdown = $( '.justwpforms-conditional__part:first', $select.next() );

				if ( 1 < $( 'option', $partDropdown ).length ) {
					$partDropdown.prop( 'disabled', false );
				}

				this.enhanceActionLabel();
			} else {
				this.reduceActionLabel();
			}
		},

		onCallbackChange: function() {
			this.conditionModel.trigger( 'change' );
		},

		onActionMousedown: function() {
			this.reduceActionLabel();
		},

		onAddClick: function( e ) {
			e.preventDefault();

			this.addNewCondition();
		},

		onDeleteClick: function( e ) {
			e.preventDefault();

			this.conditions.remove( this.conditions.last() );
		},

		onItemLabelChange: function( model, value ) {
			this.enhanceActionLabel();
		},

		/**
		 * Used to change chosen action's dropdown value label to include prefix.
		 *
		 * Example: if the value is "Show", we change it to 'Show "Untitled"' considering part's label is 'Untitled'.
		 */
		enhanceActionLabel: function() {
			var label = this.formItem.get( 'label' );

			if ( this.formItem.get( 'type' ) !== 'option' ) {
				label = '' !== label ? label : _justwpformsSettings.unlabeledFieldLabel;
			}

			var action = this.$actionDropdown.val();
			var prefix = this.$actionDropdown.attr( 'data-' + action + '-prefix' );
			var $selectedOption = $( 'option[value=' + this.$actionDropdown.val() + ']', this.$actionDropdown );

			var template = _.template( '<%= prefix %> "<%= label %>"' );
			var enhancedLabel = template( {
				prefix: prefix,
				label: label
			} );

			$selectedOption.text( enhancedLabel );
		},

		/**
		 * Does the opposite of `enhanceActionLabel`.
		 */
		reduceActionLabel: function( e ) {
			var action = this.$actionDropdown.val();
			var prefix = this.$actionDropdown.attr( 'data-' + action + '-prefix' );

			$( 'option[value=' + action + ']', this.$actionDropdown ).text( prefix );
		},

		onThenChange: function( e ) {
			e.stopPropagation();

			var $input = $( e.target );
			var value = $input.val();

			var then = this.conditionModel.get( 'then' );
			var args = then.get( 'args' );
			args[0] = value;

			then.set( 'args', args );

			justwpforms.form.trigger( 'change' );
		},
	} );

	var ConditionView = Backbone.View.extend( {
		templates: {
			main: '#customize-justwpforms-logic-item',
			partDropdown: '#customize-justwpforms-logic-part-dropdown-template',
			valueDropdown: '#customize-justwpforms-logic-value-dropdown-template'
		},

		events: {
			'change .justwpforms-conditional__operator': 'onOperatorChange',
			'change .justwpforms-conditional__part': 'onPartChange',
			'blur .justwpforms-conditional__part': 'onPartChange',
			'change .justwpforms-conditional__option': 'onValueChange',
			'blur .justwpforms-conditional__option': 'onValueChange',
			'change select': 'onSelectChange',
			'mousedown select': 'onSelectMousedown',
			'blur select': 'onSelectChange'
		},

		initialize: function( attrs ) {
			this.template  = _.template( $( this.templates.main ).text() );
			this.formItemId = attrs.options.formItemId;
			this.type = attrs.options.type;

			this.listenTo( justwpforms.form.get( 'parts' ), 'change add remove', _.debounce( function( model ) {
				this.onPartsChange( model );
			}, 1000 ) );

			this.listenTo( this, 'ready', this.onReady );
		},

		render: function() {
			this.setElement( this.template( this.model.toJSON() ) );

			return this;
		},

		onReady: function() {
			this.$operatorDropdown = $( '.justwpforms-conditional__operator', this.$el );
			this.$partDropdown = $( '.justwpforms-conditional__part', this.$el );
			this.$optionDropdown = $( '.justwpforms-conditional__option', this.$el );

			if ( this.model.collection.length > 1 &&
				0 < this.model.collection.indexOf( this.model ) ) {
				this.$operatorDropdown.show().prop( 'disabled', false );
			}

			this.updateParts();
			this.updateAllValues();

			if ( 'set' === this.type || 'select' === this.type || 'template' === this.type ) {
				this.$partDropdown.prop( 'disabled', false );
			}
		},

		updateAllValues: function() {
			if ( 0 < this.model.collection.indexOf( this.model ) && this.model.get( 'op' ) ) {
				this.$operatorDropdown
					.val( this.model.get( 'op' ) );
			}

			if ( this.model.get( 'key' ) ) {
				this.$partDropdown
					.val( this.model.get( 'key' ) )
					.prop( 'disabled', false )
					.trigger( 'change' );
			}

			if ( -1 < this.model.get( 'comparison' ) ) {
				this.$optionDropdown
					.val( this.model.get( 'comparison' ) )
					.prop( 'disabled', false )
					.trigger( 'change' );
			}
		},

		/**
		 * Updates parts available in the condition dropdown.
		 *
		 * – This is refreshed whenever the condition is rendered.
		 * - It sets part dropdown to the current value.
		 * - Disables part dropdown if there are no parts to choose from.
		 */
		updateParts: function() {
			var self = this;
			var parts = ConditionHelpers.getAllChoiceParts( this.formItemId );

			var currentPart = this.$partDropdown.val();

			if ( ! parts.length ) {
				return;
			}

			if ( this.model.collection.length > 1 ) {
				this.$partDropdown.prop( 'disabled', false );
			}

			// clear all except placeholder
			$( 'option:not(:first)', this.$partDropdown ).remove();

			_( parts ).each( function( partModel ) {
				if ( partModel.id !== self.formItemId ) {
					self.addPart( partModel );
				}
			} );

			if ( $( 'option[value=' + currentPart + ']', this.$partDropdown ).length ) {
				this.$partDropdown.val( currentPart );
			} else {
				this.$partDropdown.val( '' );

				if ( 1 === $( 'option', this.$partDropdown ).length ) {
					this.$partDropdown.prop( 'disabled', true );
				}
			}

			this.updateValues();
		},

		addPart: function( partModel ) {
			var template = _.template( $( this.templates.partDropdown ).text() );
			var html = template( {
				data: partModel.toJSON()
			} );

			this.$partDropdown.append( html );
		},

		/**
		 * Updates value dropdown in the condition.
		 *
		 * - It grabs choice part's ID from the `key` part of the condition.
		 * - Finds available choices of that choice part.
		 * - Resets value dropdown if the choice is no longer available in that part.
		 */
		updateValues: function() {
			var self = this;
			var partId = this.model.get( 'key' );
			var currentValue = this.$optionDropdown.val();
			var currentOption = $( 'option[value=' + currentValue + ']', this.$optionDropdown ).attr( 'data-option-id' );

			// clear all except placeholder
			$( 'option:not(:first)', this.$optionDropdown ).remove();

			if ( ! partId ) {
				this.$optionDropdown.val( '' ).prop( 'disabled', true );

				return;
			}

			var	part = justwpforms.form.get( 'parts' ).findWhere( { id: partId } );

			if ( ! part ) {
				return;
			}

			var partOptions = part.get( 'options' );

			if ( partOptions.length ) {
				partOptions.each( function( optionModel ) {
					if ( ! optionModel.get( 'is_heading' ) ) {
						self.addValue( optionModel );
					}
				} );

				this.$optionDropdown.prop( 'disabled', false );
			}

			var $previousOption = $( 'option[data-option-id='+ currentOption +']', this.$optionDropdown );

			if ( $previousOption.length ) {
				this.$optionDropdown.val( $previousOption.val() );
			} else {
				this.$optionDropdown.val( '' );
			}
		},

		/**
		 * Adds value to the value dropdown.
		 */
		addValue: function( optionModel ) {
			var template = _.template( $( this.templates.valueDropdown ).text() );
			var html = template( {
				data: {
					option: optionModel.toJSON(),
					index: optionModel.collection.indexOf( optionModel )
				}
			} );

			this.$optionDropdown.append( html );
		},

		onOperatorChange: function( e ) {
			var $select = $( e.target );

			this.model.set( 'op', $select.val() );
			this.$partDropdown.prop( 'disabled', false );
		},

		onPartChange: function( e ) {
			var $select = $( e.target );

			this.model.set( 'key', $select.val() );

			this.updateValues();
		},

		onValueChange: function( e ) {
			var $select = $( e.target );

			this.model.set( 'comparison', parseInt( $select.val(), 10 ) );
		},

		onSelectChange: function( e ) {
			var $select = $( e.target );

			if ( ! $select.attr( 'data-prefix' ) ) {
				return;
			}

			this.enhanceSelectLabel( $select );
		},

		onSelectMousedown: function( e ) {
			var $select = $( e.target );

			if ( ! $select.attr( 'data-prefix' ) ) {
				return;
			}

			this.reduceSelectLabel( $select );
		},

		enhanceSelectLabel: function( $select ) {
			if ( ! $select.val() ) {
				return;
			}

			var prefix = $select.attr( 'data-prefix' );
			var label = $( 'option[value=' + $select.val() + ']', $select ).attr( 'data-label' );
			var template = _.template( '<%= prefix %> "<%= label %>"' );
			var enhancedLabel = template( {
				prefix: prefix,
				label: label
			} );

			$( 'option[value=' + $select.val() + ']', $select ).text( enhancedLabel );
		},

		reduceSelectLabel: function( $select ) {
			if ( ! $select.val() ) {
				return;
			}

			var $selectedOption = $( 'option[value=' + $select.val() + ']', $select );
			$selectedOption.text( $selectedOption.attr( 'data-label' ) );
		},

		onPartsChange: function( model ) {
			if ( ! model || ! ConditionHelpers.isChoicePart( model ) ) {
				return;
			}

			this.updateParts();

			this.$partDropdown.trigger( 'change' );
			this.$optionDropdown.trigger( 'change' );
		}
	} );

	var ConditionalControlsMixin = {
		addLogicViews: function() {
			var self = this;

			$( '.customize-control', this.$el ).each( function() {
				self.addLogicView( $( this ) );
			} );
		},

		getLogicOptions: function( controlID ) {
			if ( ! controlID ) {
				return;
			}

			return _justwpformsConditionSettings.controls[controlID].options;
		},

		addLogicView: function( $control ) {
			var $logicWrap = $( '.justwpforms-setup-logic-wrap', $control );

			if ( ! $logicWrap.length ) {
				return;
			}

			var controlId = $logicWrap.attr( 'data-control' );

			var $control = $( '#customize-control-' + controlId );
			var options = this.getLogicOptions( controlId );

			var logicView = new LogicView( {
				el: $logicWrap,
				options: {
					autoAdd: false,
					logicGroupSettings: {
						type: conditionSettings.controls[controlId].type,
						id: controlId,
						options: options,
						template: conditionSettings.controls[controlId].template,
						thenText: $logicWrap.attr( 'data-conditional-then-text' )
					},
					formItem: new Backbone.Model( {
						id: controlId
					} )
				}
			} );

			logicView.trigger( 'ready' );
		}
	};

	justwpforms.classes.views.FormSetup = FormSetup.extend( _.extend( {}, {
		ready: function() {
			FormSetup.prototype.ready.apply( this, arguments );

			this.logicViews = new Backbone.Collection();
			this.addLogicViews();
		},
	}, ConditionalControlsMixin ) );

	justwpforms.classes.views.FormEmail = FormEmail.extend( _.extend( {}, {
		ready: function() {
			FormEmail.prototype.ready.apply( this, arguments );

			this.logicViews = new Backbone.Collection();
			this.addLogicViews();
		},
	}, ConditionalControlsMixin ) );

	justwpforms.classes.views.FormMessages = FormMessages.extend( _.extend( {}, {
		ready: function() {
			FormMessages.prototype.ready.apply( this, arguments );

			this.logicViews = new Backbone.Collection();
			this.addLogicViews();
		},
	}, ConditionalControlsMixin ) );

	justwpforms.classes.views.FormBuild = FormBuild.extend( {
		onPartModelRemove: function( partModel ) {
			FormBuild.prototype.onPartModelRemove.apply( this, arguments );

			var matchingThens = justwpforms.form.get( 'conditions' ).filter( function( model ) {
				var matchesField = model.get( 'then' ).get( 'key' ) === partModel.id;
				var matchesFieldSetting = model.get( 'then' ).get( 'key' ).startsWith( `${partModel.id}:` );
				
				return matchesField || matchesFieldSetting;
			} );

			justwpforms.form.get( 'conditions' ).remove( matchingThens, { mode: 'then' } );

			var matchingIfs = justwpforms.form.get( 'conditions' ).filter( function( model ) {
				var ifs = model.get( 'if' ).findWhere( { key: partModel.id } );

				return ifs;
			} );

			justwpforms.form.get( 'conditions' ).remove( matchingIfs, { mode: 'if' } );
		},

		addViewPart: function( partModel, options ) {
			options = options || {};

			if ( options.duplicateOf ) {
				this.duplicatePartLogic( options.duplicateOf, partModel );
			}

			FormBuild.prototype.addViewPart.apply( this, arguments );
		},

		duplicatePartLogic: function( part, newPart ) {
			// Part logic
			var itemConditions = justwpforms.form.get( 'conditions' ).filter( function( model ) {
				return model.get( 'then' ).get( 'key' ) === part.id;
			} );

			_( itemConditions ).each( function( model ) {
				var newModel = new ConditionModels.Condition( model.toJSON() );

				newModel.get( 'then' ).set( 'key', newPart.id );
				justwpforms.form.get( 'conditions' ).add( newModel );
			} );

			// Choice logic
			if ( [ 'radio', 'checkbox', 'select' ].includes( part.get( 'type' ) ) ) {
				part.get( 'options' ).each( function( option, o ) {
					var optionConditions = justwpforms.form.get( 'conditions' ).filter( function( model ) {
						return model.get( 'then' ).get( 'key' ) === option.id;
					} );

					if ( 0 === optionConditions.length ) {
						return;
					}

					var newOption = newPart.get( 'options' ).at( o );

					optionConditions.forEach( function( model ) {
						var newModel = new ConditionModels.Condition( model.toJSON() );

						newModel.get( 'then' ).set( 'key', newOption.id );
						justwpforms.form.get( 'conditions' ).add( newModel );
					} );
				} );
			}
		},
	} );

	justwpforms.classes.models.Form = FormModel.extend( {
		initialize: function( attrs, options ) {
			FormModel.prototype.initialize.apply( this, arguments );

			this.attributes.conditions = new justwpforms.classes.collections.conditions( this.get( 'conditions' ), options );
		},

		toJSON: function() {
			var json = Backbone.Model.prototype.toJSON.apply( this, arguments );
			json.parts = json.parts.toJSON();
			json.conditions = json.conditions.toJSON();

			return json;
		}
	} );

	justwpforms.classes.collections.conditions = Backbone.Collection.extend( {
		initialize: function() {
			this.listenTo( this, 'add', this.onConditionAdd );
			this.listenTo( this, 'change', this.onConditionChange );
			this.listenTo( this, 'remove', this.onConditionRemove );
		},

		model: function( attrs, options ) {
			var model = new ConditionModels.Condition( attrs, options );

			return model;
		},

		toJSON: function() {
			var json = Backbone.Collection.prototype.toJSON.call( this );

			return json;
		},

		onConditionAdd: function() {
			justwpforms.form.trigger( 'change' );
			this.updatePreview();
		},

		onConditionChange: function() {
			justwpforms.form.trigger( 'change' );
			this.updatePreview();
		},

		onConditionRemove: function() {
			justwpforms.form.trigger( 'change' );
			this.updatePreview();
		},

		updatePreview: function() {
			var data = {
				callback: 'onConditionalLogicUpdate',
			};

			justwpforms.previewSend( 'justwpforms-form-dom-update', data );
		}
	} );

	var onPartDuplicateCallback = justwpforms.previewer.onPartDuplicateCallback;
	var onOptionAddCallback = justwpforms.previewer.onOptionAddCallback;

	justwpforms.previewer = _.extend( justwpforms.previewer, {
		onPartDuplicateCallback: function( $form ) {
			onPartDuplicateCallback.apply( this, arguments );

			this.applyConditionalLogic( $form );
		},

		onOptionAddCallback: function( $form ) {
			onOptionAddCallback.apply( this, arguments );

			this.applyConditionalLogic( $form );
		},

		onConditionalLogicUpdate: function( $form ) {
			this.applyConditionalLogic( $form );
		},

		applyConditionalLogic: function( $form ) {
			var form = $form.get(0);
			var preview = form.ownerDocument.defaultView;

			if ( ! preview.justwpforms || ! preview.justwpforms.conditionals ) {
				return;
			}

			var conditions = {};
			conditions[justwpforms.form.id] = justwpforms.form.attributes.conditions.toJSON();

			preview.justwpforms.conditionals.reset();
			preview.justwpforms.conditionals.setConditions( conditions );
			preview.justwpforms.conditionals.applyConditions( justwpforms.form.id );

			var $document = $( form.ownerDocument );
			var $conditionalStyles = $( '#justwpforms-conditional-styles', $document );

			// Remove first-render conditional styles,
			// so that changes to conditional logic
			// structures is immediately reflected in preview
			// only through preview.justwpforms.conditionals[] calls.
			$conditionalStyles.remove();
		}, 
	} );
} ) ( jQuery, _, Backbone, wp.customize, _justwpformsConditionSettings, _justwpformsSettings );
