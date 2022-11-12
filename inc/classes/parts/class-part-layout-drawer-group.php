<?php

class justwpforms_Part_LayoutDrawerGroup extends justwpforms_Form_Part {

	public $type  = 'layout_drawer_group';
	public $group = 'drawer_group';

	public function __construct() {
		$this->label = __( 'Design', 'justwpforms' );
		$this->description = '';
	}

}
