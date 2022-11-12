<?php

class justwpforms_API_Request {

	public $url;
	public $arguments;

	public function __construct( $url, $arguments ) {
		$this->url = $url;
		$this->arguments = $arguments;
	}

}