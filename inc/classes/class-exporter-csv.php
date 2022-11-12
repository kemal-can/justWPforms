<?php

class justwpforms_Exporter_CSV {

	public $form_id;
	public $filename;

	public function __construct( $form_id, $filename ) {
		$this->form_id = $form_id;
		$this->filename = $filename;
	}

	public function export( $activity_ids = array() ) {
		global $wpdb;

		$form = justwpforms_get_form_controller()->get( $this->form_id );

		if ( ! $form ) {
			$error = __( 'Form not found', 'justwpforms' );
			return new WP_Error( $error );
		}

		$controller = justwpforms_get_message_controller();
		$messages = (
			empty( $activity_ids ) ? 
			$controller->get_by_form( $this->form_id ) :
			$controller->get( $activity_ids )
		);
		$parts = array_filter( $form['parts'], 'justwpforms_csv_is_part_visible' );
		$part_ids = wp_list_pluck( $parts, 'id' );
		$parts = array_combine( $part_ids, $parts );
		$headers = array();
		$rows = array();

		// CSV part value formatting
		add_filter( 'justwpforms_get_csv_value', array( $this, 'get_csv_value' ), 10, 4 );
		// Tracking id
		add_filter( 'justwpforms_csv_headers', array( $this, 'append_tracking_id_header' ), 10, 2 );
		add_filter( 'justwpforms_csv_row', array( $this, 'append_tracking_id_value' ), 10, 3 );
		// Submission date and time
		add_filter( 'justwpforms_csv_headers', array( $this, 'append_submission_date_header' ) );
		add_filter( 'justwpforms_csv_row', array( $this, 'append_submission_date_value' ), 10, 2 );

		foreach ( $parts as $part_id => $part ) {
			$headers[$part_id] = justwpforms_get_csv_header( $part );
		}

		$headers = apply_filters( 'justwpforms_csv_headers', $headers, $form );

		foreach( $messages as $message ) {
			global $justwpforms_submission;

			$justwpforms_submission = $message;
			$row = array();

			foreach( $headers as $header_id => $header ) {
				$value = '';

				if ( isset( $message['parts'][$header_id] ) ) {
					$value = $message['parts'][$header_id];
					$part = $parts[$header_id];
					$value = justwpforms_get_csv_value( $value, $message, $part, $form );
				}

				$row[$header_id] = $value;
			}

			$row = apply_filters( 'justwpforms_csv_row', $row, $message, $form );

			$rows[] = $row;
		}

		$output = fopen( 'php://output', 'w' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->filename );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

		do_action( 'justwpforms_csv_export_before', $output );

		fputcsv( $output, array_values( $headers ) );

		foreach( $rows as $row ) {
			fputcsv( $output, array_values( $row ) );
		}

		exit();
	}

	public function get_csv_value( $value, $message, $part, $form ) {
		switch( $part[ 'type' ] ) {
			case 'table':
				$value = str_replace( '<br>', "\n", $value );
				$value = strip_tags( $value );
				break;
			default:
				break;
		}

		return $value;
	}

	public function append_tracking_id_header( $headers, $form ) {
		if ( intval( $form['unique_id'] ) ) {
			$headers['tracking_id'] = __( 'Identifier', 'justwpforms' );
		}

		return $headers;
	}

	public function append_tracking_id_value( $row, $message, $form ) {
		if ( intval( $form['unique_id'] ) ) {
			$row['tracking_id'] = $message['tracking_id'];
		}
		
		return $row;
	}

	public function append_submission_date_header( $headers ) {
		$headers['submission_date'] = __( 'Submission date and time', 'justwpforms' );

		return $headers;
	}

	public function append_submission_date_value( $row, $message ) {
		$date_format = get_option( 'date_format', 'm/d/Y' );
		$date = get_the_date( $date_format, $message['ID'] );
		$time = get_the_time( get_option( 'time_format' ), $message['ID'] );
		$date = $date . ' ' . $time;
		$row['submission_date'] = $date;
		
		return $row;
	}

}
