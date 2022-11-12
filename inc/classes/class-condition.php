<?php

class justwpforms_Condition {

	const HF_AND = 100;
	const HF_ANDOR = 102;
	const HF_EQUAL = 0;

	private $clauses = array();
	private $ifs = array();
	private $callback = false;

	public $key = false;
	public $result = false;
	public $args = array();

	public function _if( $key, $cmp, $comparison ) {
		if ( 0 !== count( $this->clauses ) ) {
			return $this->error_already_started();
		}

		$this->add_clause( self::HF_ANDOR, $key, $cmp, $comparison );
	}

	public function _and( $key, $cmp, $comparison ) {
		if ( 0 === count( $this->clauses ) ) {
			return $this->error_not_started();
		}

		$this->add_clause( self::HF_AND, $key, $cmp, $comparison );
	}

	public function _and_or( $key, $cmp, $comparison ) {
		if ( 0 === count( $this->clauses ) ) {
			return $this->error_not_started();
		}

		$this->add_clause( self::HF_ANDOR, $key, $cmp, $comparison );
	}

	public function _then( $callback, $key, $args = array() ) {
		if ( 0 === count( $this->clauses ) ) {
			return $this->error_not_started();
		}

		$this->key = $key;
		$this->callback = $callback;
		$this->args = $args;

		$this->reduce();
	}

	private function add_clause( $op, $key, $cmp, $comparison ) {
		$clause = array( $op, $key, $cmp, $comparison );
		$this->clauses[] = $clause;
	}

	public function reduce() {
		$ifs = array_reduce( $this->clauses, function( $carry, $clause ) {
			list( $op, $key, $cmp, $comparison ) = $clause;

			if ( ! isset( $carry[$key] ) ) {
				$comparison = array( $comparison );
				$carry[$key] = array( $op, $key, $cmp, $comparison );
			} else {
				$carry[$key][3][] = $comparison;
			}

			return $carry;
		}, array() );

		$this->ifs = array();

		foreach( $ifs as $key => $if ) {
			list( $op, $key, $cmp, $comparison ) = $if;
			sort( $comparison );
			$comparison = implode( ', ', $comparison );
			$this->ifs[] = array( $op, $key, $cmp, $comparison );
		}
	}

	public function evaluate( $response, $form ) {
		foreach( $this->ifs as $clause ) {
			list( $op, $key, $cmp, $comparison ) = $clause;
			$part_name = justwpforms_get_part_name( array(
				'id' => $key,
			), $form );
			$value = isset( $response[$part_name] ) ? $response[$part_name] : false;
			$value = is_array( $value ) ? $value : array( $value );
			sort( $value );
			$value = implode( ', ', $value );
			$this->apply( $op, $value, $cmp, $comparison );
		}
	}

	public function apply( $op, $value, $cmp, $comparison ) {
		$result = false;

		switch( $cmp ) {
			case self::HF_EQUAL:
				$result = $comparison === $value;
				break;
			default:
				$result = false;
				break;
		}

		switch( $op ) {
			case self::HF_AND:
				$this->result = $this->result && $result;
				break;
			case self::HF_ANDOR:
				$this->result = $this->result || $result;
				break;
		}
	}

	public function to_array() {
		$clauses = array_map( function( $clause ) {
			list( $op, $key, $cmp, $comparison ) = $clause;

			$clause = array(
				'op' => $op,
				'key' => $key,
				'cmp' => $cmp,
				'comparison' => $comparison,
			);

			return $clause;
		}, $this->clauses );

		$array = array(
			'if' => $clauses,
			'then' => array(
				'key' => $this->key,
				'cb' => $this->callback,
				'args' => $this->args,
			),
		);

		return $array;
	}

	public static function from_array( $array ) {
		$condition = new justwpforms_Condition();

		foreach( $array['if'] as $clause ) {
			$condition->add_clause( $clause['op'], $clause['key'], $clause['cmp'], $clause['comparison'] );
		}

		$then = $array['then'];
		$condition->_then( $then['cb'], $then['key'], $then['args'] );

		return $condition;
	}

	public static function get_constants() {
		$constants = array(
			'AND' => self::HF_AND,
			'ANDOR' => self::HF_ANDOR,
			'EQUAL' => self::HF_EQUAL,
		);

		return $constants;
	}

	public function get_callback() {
		return $this->callback;
	}

	private function error_not_started() {
		throw new Exception( __( 'Condition not started.', 'justwpforms' ) );
	}

	private function error_already_started() {
		throw new Exception( __( 'Condition already started.', 'justwpforms' ) );
	}

}
