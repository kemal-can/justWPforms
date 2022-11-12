<?php

class justwpforms_Task_Email_Owner extends justwpforms_Task {

	public static $event = 'email_owner';

	public function run() {
		$response = justwpforms_get_message_controller()->get( $this->response_id, true );

		if ( ! $response ) {
			error_log( 'Response not found.' );
			return;
		}

		$form_id = $response['form_id'];
		$form = justwpforms_get_form_controller()->get( $form_id );
		$conditional_controller = justwpforms_get_conditional_controller();

		if ( $conditional_controller->has_conditions( $form ) ) {
			$form = justwpforms_get_conditional_controller()->get( $form, $response['request'] );
		}

		if ( ! $form ) {
			error_log( 'Form not found.' );
			return;
		}

		$subject = $this->get_email_owner_confirmation_subject( $form, $response );

		if ( ! empty( $form['email_recipient'] ) && ! empty( $subject ) ) {
			// Compose an email message
			$email_message = new justwpforms_Email_Message( $response );
			$name = $form['alert_email_from_name'];
			$to = explode( ',', $form['email_recipient'] );

			$from_address = justwpforms_get_form_property( $form, 'alert_email_from_address' );
			$from_address = explode( ',', $from_address );

			$email_message->set_from( $from_address[0] );
			$email_message->set_from_name( $name );
			$email_message->set_to( $to[0] );

			if ( count( $to ) > 1 ) {
				$email_message->set_ccs( array_slice( $to, 1 ) );
			}

			$bccs = explode( ',', $form['email_bccs'] );

			if ( count( $bccs ) > 0 ) {
				$email_message->set_bccs( $bccs );
			}

			$email_message->set_subject( $subject );

			// Add a Reply To header and a reply-and-mark-as-read link
			// if the form includes an email part
			$email_reply_to_id = justwpforms_get_form_property( $form, 'alert_email_reply_to' );
			$email_reply_parts = array();

			if ( 'all' === $email_reply_to_id ) {
				$email_reply_parts = justwpforms_get_form_controller()->get_parts_by_type( $form, 'email' );
			} else {
				$email_part = justwpforms_get_form_controller()->get_part_by_id( $form, $email_reply_to_id );

				if ( ! $email_part ) {
					$email_part = justwpforms_get_form_controller()->get_first_part_by_type( $form, 'email' );
				}

				if ( $email_part ) {
					$email_reply_parts[] = $email_part;
				}
			}

			if ( ! empty( $email_reply_parts ) ) {
				$reply_to = array_map( function( $email_part ) use( $response ) {
					$part_id = $email_part['id'];
					$part_value = justwpforms_get_message_part_value( $response['parts'][$part_id], $email_part );

					return $part_value;
				}, $email_reply_parts );

				$reply_to = array_values( array_filter( array_map( 'trim', $reply_to ) ) );

				$email_message->set_reply_to( $reply_to );
			}

			ob_start();
			require_once( justwpforms_owner_email_template_path() );
			$content = ob_get_clean();

			$email_message->set_content( $content );
			$email_message = apply_filters( 'justwpforms_email_alert', $email_message );
			$email_message->send();

			do_action( 'justwpforms_email_alert_sent', $email_message );
		}
	}

	public function get_email_owner_confirmation_subject( $form, $response ) {
		$subject = $form['alert_email_subject'];
 		$subject_parts = array_filter( $form['parts'], function( $part ) {
			$use_as_subject = (
				isset( $part['use_as_subject'] )
				&& intval( $part['use_as_subject'] )
			);
 			return $use_as_subject;
		} );
		$subject_parts = array_values( $subject_parts );

 		if ( count( $subject_parts ) > 0 ) {
			$part = $subject_parts[count( $subject_parts ) - 1];
			$subject = justwpforms_get_email_part_value( $response, $part, $form );
		}

 		return $subject;
	}

}
