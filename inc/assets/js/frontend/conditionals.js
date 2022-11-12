( function( $, settings ) {

	var Conditionals = function( conditions ) {
		this.visitedAttribute = 'data-justwpforms-condition-visited';

		this.setConditions( conditions );
	};

	Conditionals.prototype.setConditions = function( conditions ) {
		this.conditions = {};

		for ( var formID in conditions ) {
			this.conditions[formID] = this.reduceClauses( conditions[formID] );
			this.applyConditions( formID );
		}
	};

	Conditionals.prototype.reset = function() {
		if ( ! this.conditions ) {
			return;
		}

		for ( var formID in this.conditions ) {
			this.resetConditions( formID );
		}
	};

	Conditionals.prototype.reduceClauses = function( clauses ) {
		clauses = clauses.map( this.reduceClause.bind( this ) );

		return clauses;
	}

	Conditionals.prototype.reduceClause = function( clause ) {
		var clause_groups = clause.if.reduce( function( group, _if ) {
			group[_if.key] = group[_if.key] || [];
			group[_if.key].push( _if );

			return group;
		}, {} );

		for ( var key in clause_groups ) {
			clause_groups[key] = clause_groups[key].reduce( function( group, _if, i ) {
				if ( i === 0 ) {
					_if.comparison = [_if.comparison];
					group = _if;
				} else {
					group.comparison.push( _if.comparison );
				}

				return group;
			}, {} );
		}

		var _if = [];

		for ( var key in clause_groups ) {
			var group = clause_groups[key];
			group.comparison = group.comparison.sort().join( ', ' );
			_if.push( group );
		}

		clause.if = _if;

		return clause;
	}

	Conditionals.prototype.callbacks = {};

	Conditionals.prototype.bind = function() {
		$( document ).on( 'justwpforms-change', '[data-justwpforms-conditionable]', this.onChange.bind( this ) );
		$( document ).on( 'justwpforms-init', this.onInit.bind( this ) );
	};

	Conditionals.prototype.onInit = function( e ) {
		var $target = $( e.target );
		var $form = $( 'form', $target );
		var formID = parseInt( $( '[name=justwpforms_form_id]', $form ).val(), 10 );

		if ( ! $form.is( '[data-justwpforms-conditionable]' ) ) {
			return;
		}

		this.applyConditions( formID );
	};

	Conditionals.prototype.onChange = function( e ) {
		var $form = $( e.currentTarget ).parents( '.justwpforms-form' );
		var form = $.data( $form.get( 0 ), 'justwpform' );
		var formID = parseInt( $( '[name=justwpforms_form_id]', $form ).val(), 10 );
		var $part = $( e.target );
		var partID = $part.attr( 'data-justwpforms-id' );

		this.applyConditions( formID );
	};

	Conditionals.prototype.applyConditions = function( formID ) {
		var $form = $( '[name="justwpforms_form_id"][value="' + formID + '"]' ).parents( '.justwpforms-form' );

		if ( ! this.conditions[formID] ) {
			return;
		}

		var conditions = this.conditions[formID];

		$( '[data-justwpforms-type="select"]', $form ).each( function() {
			var $select = $( 'select', $( this ) );

			if ( ! $( this ).data( 'originalHTML' ) ) {
				$( this ).data( 'originalHTML', $select.html() );
			}

			$( this ).data( 'originalValue', $select.val() );
			$select.html( $( this ).data( 'originalHTML' ) );
		} );

		$( '[data-justwpforms-type="select"]', $form ).each( function() {
			$( 'select', $( this ) ).val( $( this ).data( 'originalValue' ) );
		} );

		// Run the whole condition stack 
		// multiple times. Could be optimized,
		// but ensures that any decently-sized chain of logic is correctly resolved.
		for ( var l = 0; l < settings.chainLength; l ++ ) {
			$( '[' + this.visitedAttribute + ']', $form ).removeAttr( this.visitedAttribute );

			var results = conditions.map( function( condition ) {
				var result = {
					condition: condition,
					result: this.evaluateIf( condition, formID ),
				};

				return result;
			}.bind( this ) );

			results.sort( function( a, b ) {
				if ( b.result ) {
					return 1;
				} else if ( a.result ) {
					return -1;
				}

				return 0;
			} );

			results.forEach( function( result ) {
				this.applyThen( result.result, result.condition.then, formID );

				$( '[data-justwpforms-type="select"]', $form ).each( function() {
					$( 'select', $( this ) ).val( $( this ).data( 'originalValue' ) );
				} );
			}.bind( this ) );
		}

		$( '[data-justwpforms-type="select"]', $form ).each( function() {
			var $select = $( 'select', $( this ) );

			$( '[data-hidden]', $select ).remove();
			$select.val( $( this ).data( 'originalValue' ) );
		} );
	};

	Conditionals.prototype.resetConditions = function( formID ) {
		var $form = $( '[name="justwpforms_form_id"][value="' + formID + '"]' ).parents( '.justwpforms-form' );

		if ( ! this.conditions[formID] ) {
			return;
		}

		var conditions = this.conditions[formID];

		$( '[' + this.visitedAttribute + ']', $form ).removeAttr( this.visitedAttribute );

		conditions.forEach( function( condition ) {
			this.resetThen( condition.then, formID );
		}.bind( this ) );
	};

	Conditionals.prototype.evaluateIf = function( condition, formID ) {
		var ifs = condition.if.map( function( clause ) {
			return this.evaluateClause( clause, formID );
		}.bind( this ) );

		var result = ifs.reduce( function( result, clause ) {
			switch( clause.op ) {
				case settings.constants.AND:
					result = result && clause.result;
					break;
				case settings.constants.ANDOR:
					result = result || clause.result;
					break;
			}

			return result;
		}, false );

		return result;
	};

	Conditionals.prototype.getPartByID = function( partID, formID ) {
		partID = partID.split( ':' );

		var idParts = [ 'justwpforms-', formID, '_', partID[0] ];

		if ( partID.length > 1 ) {
			idParts.push( '_' + partID[1] );
		} else {
			idParts.push( '-part' );
		}

		var id = idParts.join( '' );
		var $part = $( '#' + id );

		return $part;
	}

	Conditionals.prototype.getOptionByID = function( optionID, formID ) {
		var $form = $( `#justwpforms-${formID}` );
		var $option = $( `#${optionID}`, $form );

		return $option;
	}

	Conditionals.prototype.applyThen = function( result, then, formID ) {
		var callback = this.callbacks[then.cb];
		
		if ( ! callback ) {
			return;
		}

		if ( 'show' === then.cb || 'hide' === then.cb || 'set' === then.cb ) {
			var $part = this.getPartByID( then.key, formID );
			
			callback.call( this, $part, result, then );
			$part.attr( this.visitedAttribute, true );
		} else if ( 'show_option' === then.cb || 'hide_option' === then.cb ) {
			var $option = this.getOptionByID( then.key, formID );
			
			callback.call( this, $option, result, then );
			$option.attr( this.visitedAttribute, true );
		}
	};

	Conditionals.prototype.resetThen = function( then, formID ) {
		var callback = this.callbacks[then.cb];
		
		if ( ! callback ) {
			return;
		}

		if ( 'show' === then.cb || 'hide' === then.cb ) {
			var $part = this.getPartByID( then.key, formID );
			$part.show();
		} else if ( 'show_option' === then.cb || 'hide_option' === then.cb ) {
			var $option = this.getOptionByID( then.key, formID );
			
			if ( ! $option.is( 'option' ) && ! $option.is( 'optgroup' ) ) {
				$option.show();	
			} else {
				$option.removeAttr( 'data-hidden' );
			}
		}
	};

	Conditionals.prototype.getPartValue = function( part ) {
		var type = part.getType();
		var $inputs = $( [] );

		switch( type ) {
			case 'radio':
			case 'checkbox':
				$inputs = $( 'input:checked', part.$el );
				break;
			default:
				$inputs = part.$input;
				break;
		}

		var value = $inputs.map( function() {
			return $( this ).val();
		} ).toArray().sort().join( ', ' );

		return value;
	}

	Conditionals.prototype.comparePartValue = function( value, comparison, comparator ) {
		var result = false;

		switch( comparator ) {
			case settings.constants.EQUAL:
				result = result || ( value == comparison );
				break;
		}

		return result;
	}

	Conditionals.prototype.evaluateClause = function( clause, formID ) {
		var $part = this.getPartByID( clause.key, formID );
		var part = $part.data( 'justwpformPart' );
		var result = false;

		if ( part ) {
			var value = this.getPartValue( part );
			result = this.comparePartValue( value, clause.comparison, clause.cmp );
		}

		var clause = {
			op: clause.op,
			result: result,
		};

		return clause;
	};

	Conditionals.prototype.callbacks.show = function( $part, result, then ) {
		if ( ! $part.is( '[data-justwpforms-type="select"]' ) ) {
			var $previouslyChecked = $( 'input[type="radio"]:checked, input[type="checkbox"]:checked', $part );

			if ( ! $part.attr( this.visitedAttribute ) ) {
				$previouslyChecked.prop( 'checked', false );
				$part.hide();
			}

			if ( result ) {
				$previouslyChecked.prop( 'checked', true );
				$part.show();
			}
		} else {
			var previousValue = $( ':selected', $part ).val();

			if ( ! $part.attr( this.visitedAttribute ) ) {
				$part.data( 'originalValue', '' );
				$part.hide();
			}

			if ( result ) {
				$part.data( 'originalValue', previousValue );
				$part.show();
			}
		}
	};

	Conditionals.prototype.callbacks.hide = function( $part, result, then ) {
		if ( ! $part.is( '[data-justwpforms-type="select"]' ) ) {
			if ( ! $part.attr( this.visitedAttribute ) ) {
				$part.show();
			}

			if ( result ) {
				$( 'input[type="radio"]:checked, input[type="checkbox"]:checked', $part ).prop( 'checked', false );
				$part.hide();
			}
		} else {
			if ( ! $part.attr( this.visitedAttribute ) ) {
				$part.show();
			}

			if ( result ) {
				$part.data( 'originalValue', '' );
				$part.hide();
			}
		}
	};

	Conditionals.prototype.callbacks.set = function( $part, result, then ) {
		var data = {};

		if ( result ) {
			data.value = then.args[0];
		}

		if ( ! $part.attr( this.visitedAttribute ) || result ) {
			$part.trigger( 'condition-update', data );
		}
	};

	Conditionals.prototype.callbacks.show_option = function( $option, result, then ) {
		if ( ! $option.is( 'option' ) && ! $option.is( 'optgroup' ) ) {
			var $previouslyChecked = $( 'input[type="radio"]:checked, input[type="checkbox"]:checked', $option );

			if ( ! $option.attr( this.visitedAttribute ) ) {
				$previouslyChecked.prop( 'checked', false );
				$option.hide();
			}

			if ( result ) {
				$previouslyChecked.prop( 'checked', true );
				$option.show();
			}
		} else {
			var previouslySelected = $option.is( ':selected' );

			if ( ! $option.attr( this.visitedAttribute ) ) {
				if ( previouslySelected ) {
					$option.parents( '[data-justwpforms-type="select"]' ).data( 'originalValue', '' );
				}

				$option.attr( 'data-hidden', true );
			}

			if ( result ) {
				if ( previouslySelected ) {
					$option.parents( '[data-justwpforms-type="select"]' ).data( 'originalValue', $option.val() );
				}

				$option.removeAttr( 'data-hidden' );
			}
		}
	};

	Conditionals.prototype.callbacks.hide_option = function( $option, result, then ) {
		if ( ! $option.is( 'option' ) && ! $option.is( 'optgroup' ) ) {
			if ( ! $option.attr( this.visitedAttribute ) ) {
				$option.show();
			}

			if ( result ) {
				$( 'input[type="radio"], input[type="checkbox"]', $option ).prop( 'checked', false );
				
				$option.hide();
			}
		} else {
			var previouslySelected = $option.is( ':selected' );

			if ( ! $option.attr( this.visitedAttribute ) ) {
				$option.removeAttr( 'data-hidden' );
			}

			if ( result ) {
				if ( previouslySelected ) {
					$option.parents( '[data-justwpforms-type="select"]' ).data( 'originalValue', '' );
				}
				
				$option.attr( 'data-hidden', true );
			}
		}
	};

	justwpforms.conditionals = null;

	$( function() {
		justwpforms.conditionals = new Conditionals( settings.conditions );
		justwpforms.conditionals.bind();
	} );

} )( jQuery, _justwpformsSettings.conditionals );
