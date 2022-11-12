<?php

class justwpforms_Email_Message {

	/**
	 * The sender address.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $from = array();

	/**
	 * The sender name.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $from_name;

	/**
	 * The reply-to address.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $reply_to = array();

	/**
	 * The list of recipients.
	 *
	 * @since 1.4.3
	 *
	 * @var array
	 */
	private $to = array();

	/**
	 * The list of cc addresses.
	 *
	 * @since 1.4.3
	 *
	 * @var array
	 */
	private $ccs = array();

	/**
	 * The list of bcc addresses.
	 *
	 * @since 1.4.3
	 *
	 * @var array
	 */
	private $bccs = array();

	/**
	 * The email subject.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $subject;

	/**
	 * The email content type.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $content_type = 'text/html';

	/**
	 * The email message content.
	 *
	 * @since 1.4.3
	 *
	 * @var string
	 */
	private $content;

	private $attachments = array();

	/**
	 * The submission message this email is linked to.
	 *
	 * @since 1.4.3
	 *
	 * @var array
	 */
	public $message;

	public function __construct( $message = array() ) {
		$this->from = array();
		$this->to = array();
		$this->reply_to = array();
		$this->ccs = array();
		$this->bccs = array();
		$this->subject = '';
		$this->content = '';
		$this->message = $message;
	}

	public function set_from( $email, $name = '' ) {
		$from = apply_filters( 'justwpforms_email_from', $email, $this->message );
		$from = is_array( $from ) ? $from : array( $from );
		$from = array_values( array_filter( array_map( 'trim', $from ) ) );

		$this->from = $from;
	}

	public function get_from() {
		$from = array_map( array( justwpforms_get_email_encoder(), 'encode_email' ), $this->from );
		$from = implode( ', ', $from );

		return $from;
	}

	public function set_from_name( $name ) {
		$from_name = apply_filters( 'justwpforms_email_from_name', $name, $this->message );

		$this->from_name = $from_name;
	}

	public function get_from_name() {
		return $this->from_name;
	}

	public function set_to( $to ) {
		/**
		 * Filter the list of recipients for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param array $recipients Current list of recipients.
		 * @param array $message    The submission this email was triggered from.
		 *
		 * @return array
		 */
		$to = apply_filters( 'justwpforms_email_to', $to, $this->message );
		$to = is_array( $to ) ? $to : array( $to );
		$to = array_values( array_filter( array_map( 'trim', $to ) ) );

		$this->to = $to;
	}

	public function get_to() {
		$to = array_map( array( justwpforms_get_email_encoder(), 'encode_email' ), $this->to );
		$to = implode( ', ', $to );

		return $to;
	}

	public function set_ccs( $ccs = array() ) {
		/**
		 * Filter the list of recipients for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param array $recipients Current list of recipients.
		 * @param array $message    The submission this email was triggered from.
		 *
		 * @return array
		 */
		$ccs = apply_filters( 'justwpforms_email_ccs', $ccs, $this->message );
		$ccs = is_array( $ccs ) ? $ccs : array( $ccs );
		$ccs = array_values( array_filter( array_map( 'trim', $ccs ) ) );

		$this->ccs = $ccs;
	}

	public function get_ccs() {
		$ccs = array_map( array( justwpforms_get_email_encoder(), 'encode_email' ), $this->ccs );
		$ccs = implode( ', ', $ccs );

		return $ccs;
	}

	public function set_bccs( $bccs = array() ) {
		/**
		 * Filter the list of recipients for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param array $recipients Current list of recipients.
		 * @param array $message    The submission this email was triggered from.
		 *
		 * @return array
		 */
		$bccs = apply_filters( 'justwpforms_email_bccs', $bccs, $this->message );
		$bccs = is_array( $bccs ) ? $bccs : array( $bccs );
		$bccs = array_values( array_filter( array_map( 'trim', $bccs ) ) );

		$this->bccs = $bccs;
	}

	public function get_bccs() {
		$bccs = array_map( array( justwpforms_get_email_encoder(), 'encode_email' ), $this->bccs );
		$bccs = implode( ', ', $bccs );

		return $bccs;
	}

	public function set_reply_to( $reply_to = array() ) {
		/**
		 * Filter the list of recipients for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param array $recipients Current list of recipients.
		 * @param array $message    The submission this email was triggered from.
		 *
		 * @return array
		 */
		$reply_to = apply_filters( 'justwpforms_email_reply_to', $reply_to, $this->message );
		$reply_to = is_array( $reply_to ) ? $reply_to : array( $reply_to );
		$reply_to = array_values( array_filter( array_map( 'trim', $reply_to ) ) );

		$this->reply_to = $reply_to;
	}

	public function get_reply_to() {
		$reply_to = array_map( array( justwpforms_get_email_encoder(), 'encode_email' ), $this->reply_to );
		$reply_to = implode( ', ', $reply_to );

		return $reply_to;
	}

	public function set_subject( $subject = '' ) {
		$subject = trim( $subject );

		/**
		 * Filter the subject for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param string $subject Current subject.
		 * @param array  $message    The submission this email was triggered from.
		 * @param array  $recipients The address this email is being sent to.
		 *
		 * @return string
		 */
		$subject = apply_filters( 'justwpforms_email_subject', $subject, $this->message, $this->to );

		$this->subject = $subject;
	}

	public function get_subject() {
		return $this->subject;
	}

	public function set_content( $content = '' ) {
		$content = trim( $content );

		/**
		 * Filter the content for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param string $content    Current content.
		 * @param array  $message    The submission this email was triggered from.
		 * @param array  $recipients The address this email is being sent to.
		 *
		 * @return string
		 */
		$content = apply_filters( 'justwpforms_email_content', $content, $this->message, $this->to );

		$this->content = $content;
	}

	public function get_content() {
		return $this->content;
	}

	public function get_response() {
		return $this->message;
	}

	public function get_headers() {
		$headers = array();

		$reply_to = $this->get_reply_to();

		if ( ! empty( $reply_to ) ) {
			array_push( $headers, 'Reply-To: ' . $reply_to  );
		}

		$ccs = $this->get_ccs();

		if ( ! empty( $ccs ) ) {
			array_push( $headers, 'Cc: ' . $ccs );
		}

		$bccs = $this->get_bccs();

		if ( ! empty( $bccs ) ) {
			array_push( $headers, 'Bcc: ' . $bccs );
		}

		return $headers;
	}

	public function add_attachment( $path ) {
		$this->attachments[] = $path;
	}

	public function get_attachments() {
		return $this->attachments;
	}

	public function get_content_type() {
		/**
		 * Filter the content type for this email message.
		 *
		 * @since 1.4.3
		 *
		 * @param string $content_type Current content type.
		 * @param array  $message      The submission this email was triggered from.
		 *
		 * @return string
		 */
		$content_type = apply_filters( 'justwpforms_email_content_type', $this->content_type, $this->message );

		return $content_type;
	}

	public function send() {
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$from = $this->get_from();

		if ( $from ) {
			add_filter( 'wp_mail_from', array( $this, 'get_from' ) );
		}

		if ( $this->from_name ) {
			add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		}

		$result = wp_mail( $this->get_to(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		if ( $from ) {
			remove_filter( 'wp_mail_from', array( $this, 'get_from' ) );
		}

		if ( $this->from_name ) {
			remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		}

		return $result;
	}

}
