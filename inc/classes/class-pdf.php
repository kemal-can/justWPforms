<?php

class justwpforms_PDF {

	public $path = '';
	public $response;
	public $form;
	public $document;

	public function __construct( $form, $response ) {
		$this->form = $form;
		$this->response = $response;
	}

	public function get_path( $filename ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['path'];
		$path = "{$upload_dir}/{$filename}";

		return $path;
	}

	public function generate( $settings ) {
		require_once( justwpforms_get_include_folder() . '/lib/hfpdf/hfpdf.php' );

		$defaults = array(
			'title' => '',
			'header' => '',
			'content' => '',
			'footer' => '',
			'logo' => '',
			'filename' => '',
		);

		$settings = wp_parse_args( $settings, $defaults );
		extract( $settings );

		$this->path =  $this->get_path( $filename );
		$this->document = new hFPDF();

		if ( ! empty( $logo ) ) {
			$this->document->OutputLogo( $logo, 50 );
		}

		$this->document->OutputTitle( $title );
		$this->document->OutputContent( $header );
		$this->document->OutputSubmissionData( $content );
		$this->document->OutputContentFooter( $footer );
	}

	public function save() {
		if ( ! $this->document ) {
			return false;
		}

		$this->document->Output( $this->path, 'F' );
	}

}
