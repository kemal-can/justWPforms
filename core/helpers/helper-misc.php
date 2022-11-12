<?php

if ( ! function_exists( 'justwpforms_get_part_label' ) ):
/**
 * Get a non-empty label for the part.
 *
 * @since 1.0
 *
 * @param array $part The part data to retrieve the label for.
 *
 * @return string
 */
function justwpforms_get_part_label( $part ) {
	$label = isset( $part['label'] ) ? $part['label'] : '';
	$label = apply_filters( 'the_title', $label, null );

	if ( '' === $label ) {
		$label = __( '(no title)', 'justwpforms' );
	}

	$label = apply_filters( 'justwpforms_get_part_label', $label, $part );

	return $label;
}

endif;

if ( ! function_exists( 'justwpforms_csv_is_part_visible' ) ):

function justwpforms_csv_is_part_visible( $part ) {
	$visible = apply_filters( 'justwpforms_csv_part_visible', true, $part );

	return $visible;
}

endif;

if ( ! function_exists( 'justwpforms_get_csv_header' ) ):
/**
 * Get a non-empty CSV header for the part.
 *
 * @param array $part The part data to retrieve the label for.
 *
 * @return string
 */
function justwpforms_get_csv_header( $part ) {
	$part_label = justwpforms_get_part_label( $part );
	$part_id = $part['id'];
	$header = ! empty( $part['label'] ) ? $part_label : "Blank [{$part_id}]";

	return $header;
}

endif;

if ( ! function_exists( 'justwpforms_get_csv_value' ) ):
/**
 * Get a CSV response value.
 *
 * @param array $message The message data to retrieve the value for.
 * @param array $part    The part data relative to the current value.
 *
 * @return string
 */
function justwpforms_get_csv_value( $value, $message, $part, $form ) {
	$value = justwpforms_get_message_part_value( $value, $part, 'csv' );
	$value = htmlspecialchars_decode( $value );
	$value = apply_filters( 'justwpforms_get_csv_value', $value, $message, $part, $form );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_get_message_part_value' ) ):
/**
 * Get the part submission value in a readable format.
 *
 * @since 1.0
 *
 * @param mixed  $value       The original submission value.
 * @param array  $part        Current part data.
 * @param string $destination An optional destination slug.
 *
 * @return string
 */
function justwpforms_get_message_part_value( $value, $part = array(), $destination = '' ) {
	$original_value = $value;

	if ( is_string( $value ) ) {
		$value = maybe_unserialize( $value );
	}

	if ( is_array( $value ) ) {
		$value = array_filter( array_values( $value ) );
		$value = implode( ', ', $value );
	}

	$value = wp_unslash( $value );

	$value = htmlspecialchars( $value );

	$value = apply_filters( 'justwpforms_message_part_value', $value, $original_value, $part, $destination );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_the_message_part_value' ) ):

function justwpforms_the_message_part_value( $value, $part = array(), $destination = '' ) {
	$value = justwpforms_get_message_part_value( $value, $part, $destination );

	echo $value;
}

endif;

if ( ! function_exists( 'justwpforms_stringify_part_value' ) ):
/**
 * Transforms a part value into a string.
 *
 * @since 1.0
 *
 * @param mixed $value The original submission value.
 * @param array $part  Current part data.
 * @param array $form  Current form data.
 *
 * @return string
 */
function justwpforms_stringify_part_value( $value, $part, $form ) {
	$value = apply_filters( 'justwpforms_stringify_part_value', $value, $part, $form );
	$value = maybe_serialize( $value );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_customizer_url' ) ):
/**
 * Get a formatted url for the Customize screen,
 * complete with a return url.
 *
 * @since 1.0
 *
 * @param string $return_url The url to return to after
 *                           the Customize screen is closed.
 *
 * @return string
 */
function justwpforms_customizer_url( $return_url = '' ) {
	if ( '' === $return_url ) {
		$return_url = urlencode( add_query_arg( null, null ) );
	}

	$customize_url = add_query_arg( array(
		'return' => $return_url,
		'justwpforms' => 1,
	), 'customize.php' );

	return $customize_url;
}

endif;

if ( ! function_exists( 'justwpforms_get_form_edit_link' ) ):
/**
 * Get the admin edit url for a justwpform post.
 *
 * @since 1.0
 *
 * @param string|int $id         The form ID.
 * @param string     $return_url The url to return to after
 *                               the Customize screen is closed.
 *
 * @return string
 */
function justwpforms_get_form_edit_link( $id, $return_url = '', $step = '' ) {
	$return_url = empty( $return_url ) ? justwpforms_get_all_form_link() : $return_url;
	$base_url = add_query_arg( array(
		'form_id' => $id,
	), justwpforms_customizer_url( $return_url ) );
	$step = in_array( $step, array( 'build', 'setup', 'style' ) ) ? $step : 'build';
	$url = "{$base_url}#{$step}";

	return $url;
}

endif;

if ( ! function_exists( 'justwpforms_get_all_form_link' ) ):
/**
 * Get the url of the All Forms admin screen.
 *
 * @since 1.0
 *
 * @return string
 */
function justwpforms_get_all_form_link() {
	return admin_url( 'edit.php?post_type=' . justwpforms_get_form_controller()->post_type );
}

endif;

if ( ! function_exists( 'justwpforms_admin_footer' ) ):
/**
 * Output the justwpforms rating admin footer.
 *
 * @since 1.0
 *
 * @return string
 */
function justwpforms_admin_footer() {
	?>
	<span id="footer-thankyou">
		<?php _e( 'Thank you for creating with', 'justwpforms' ); ?> <a href="https://wordpress.org/" target="_blank">WordPress</a>.
	</span>
	<?php
}

endif;

if ( ! function_exists( 'justwpforms_get_countries' ) ):
/**
 * Outputs an array of country names.
 *
 * @since 1.1
 *
 * @return void
 */
function justwpforms_get_countries() {
	return array(
		__( 'Afghanistan', 'justwpforms' ),
		__( 'Albania', 'justwpforms' ),
		__( 'Algeria', 'justwpforms' ),
		__( 'American Samoa', 'justwpforms' ),
		__( 'Andorra', 'justwpforms' ),
		__( 'Angola', 'justwpforms' ),
		__( 'Anguilla', 'justwpforms' ),
		__( 'Antarctica', 'justwpforms' ),
		__( 'Antigua and Barbuda', 'justwpforms' ),
		__( 'Argentina', 'justwpforms' ),
		__( 'Armenia', 'justwpforms' ),
		__( 'Aruba', 'justwpforms' ),
		__( 'Australia', 'justwpforms' ),
		__( 'Austria', 'justwpforms' ),
		__( 'Azerbaijan', 'justwpforms' ),
		__( 'Bahamas', 'justwpforms' ),
		__( 'Bahrain', 'justwpforms' ),
		__( 'Bangladesh', 'justwpforms' ),
		__( 'Barbados', 'justwpforms' ),
		__( 'Belarus', 'justwpforms' ),
		__( 'Belgium', 'justwpforms' ),
		__( 'Belize', 'justwpforms' ),
		__( 'Benin', 'justwpforms' ),
		__( 'Bermuda', 'justwpforms' ),
		__( 'Bhutan', 'justwpforms' ),
		__( 'Bolivia', 'justwpforms' ),
		__( 'Bosnia and Herzegowina', 'justwpforms' ),
		__( 'Botswana', 'justwpforms' ),
		__( 'Bouvet Island', 'justwpforms' ),
		__( 'Brazil', 'justwpforms' ),
		__( 'British Indian Ocean Territory', 'justwpforms' ),
		__( 'Brunei Darussalam', 'justwpforms' ),
		__( 'Bulgaria', 'justwpforms' ),
		__( 'Burkina Faso', 'justwpforms' ),
		__( 'Burundi', 'justwpforms' ),
		__( 'Cambodia', 'justwpforms' ),
		__( 'Cameroon', 'justwpforms' ),
		__( 'Canada', 'justwpforms' ),
		__( 'Cape Verde', 'justwpforms' ),
		__( 'Cayman Islands', 'justwpforms' ),
		__( 'Central African Republic', 'justwpforms' ),
		__( 'Chad', 'justwpforms' ),
		__( 'Chile', 'justwpforms' ),
		__( 'China', 'justwpforms' ),
		__( 'Christmas Island', 'justwpforms' ),
		__( 'Cocos (Keeling) Islands', 'justwpforms' ),
		__( 'Colombia', 'justwpforms' ),
		__( 'Comoros', 'justwpforms' ),
		__( 'Congo', 'justwpforms' ),
		__( 'Congo, the Democratic Republic of the', 'justwpforms' ),
		__( 'Cook Islands', 'justwpforms' ),
		__( 'Costa Rica', 'justwpforms' ),
		__( 'Ivory Coast', 'justwpforms' ),
		__( 'Croatia (Hrvatska)', 'justwpforms' ),
		__( 'Cuba', 'justwpforms' ),
		__( 'Cyprus', 'justwpforms' ),
		__( 'Czech Republic', 'justwpforms' ),
		__( 'Denmark', 'justwpforms' ),
		__( 'Djibouti', 'justwpforms' ),
		__( 'Dominica', 'justwpforms' ),
		__( 'Dominican Republic', 'justwpforms' ),
		__( 'East Timor', 'justwpforms' ),
		__( 'Ecuador', 'justwpforms' ),
		__( 'Egypt', 'justwpforms' ),
		__( 'El Salvador', 'justwpforms' ),
		__( 'Equatorial Guinea', 'justwpforms' ),
		__( 'Eritrea', 'justwpforms' ),
		__( 'Estonia', 'justwpforms' ),
		__( 'Ethiopia', 'justwpforms' ),
		__( 'Falkland Islands (Malvinas)', 'justwpforms' ),
		__( 'Faroe Islands', 'justwpforms' ),
		__( 'Fiji', 'justwpforms' ),
		__( 'Finland', 'justwpforms' ),
		__( 'France', 'justwpforms' ),
		__( 'France Metropolitan', 'justwpforms' ),
		__( 'French Guiana', 'justwpforms' ),
		__( 'French Polynesia', 'justwpforms' ),
		__( 'French Southern Territories', 'justwpforms' ),
		__( 'Gabon', 'justwpforms' ),
		__( 'Gambia', 'justwpforms' ),
		__( 'Georgia', 'justwpforms' ),
		__( 'Germany', 'justwpforms' ),
		__( 'Ghana', 'justwpforms' ),
		__( 'Gibraltar', 'justwpforms' ),
		__( 'Greece', 'justwpforms' ),
		__( 'Greenland', 'justwpforms' ),
		__( 'Grenada', 'justwpforms' ),
		__( 'Guadeloupe', 'justwpforms' ),
		__( 'Guam', 'justwpforms' ),
		__( 'Guatemala', 'justwpforms' ),
		__( 'Guinea', 'justwpforms' ),
		__( 'Guinea-Bissau', 'justwpforms' ),
		__( 'Guyana', 'justwpforms' ),
		__( 'Haiti', 'justwpforms' ),
		__( 'Heard and Mc Donald Islands', 'justwpforms' ),
		__( 'Holy See (Vatican City State)', 'justwpforms' ),
		__( 'Honduras', 'justwpforms' ),
		__( 'Hong Kong', 'justwpforms' ),
		__( 'Hungary', 'justwpforms' ),
		__( 'Iceland', 'justwpforms' ),
		__( 'India', 'justwpforms' ),
		__( 'Indonesia', 'justwpforms' ),
		__( 'Iran (Islamic Republic of)', 'justwpforms' ),
		__( 'Iraq', 'justwpforms' ),
		__( 'Ireland', 'justwpforms' ),
		__( 'Israel', 'justwpforms' ),
		__( 'Italy', 'justwpforms' ),
		__( 'Jamaica', 'justwpforms' ),
		__( 'Japan', 'justwpforms' ),
		__( 'Jordan', 'justwpforms' ),
		__( 'Kazakhstan', 'justwpforms' ),
		__( 'Kenya', 'justwpforms' ),
		__( 'Kiribati', 'justwpforms' ),
		__( 'Korea, Democratic People\'s Republic of', 'justwpforms' ),
		__( 'Korea, Republic of', 'justwpforms' ),
		__( 'Kuwait', 'justwpforms' ),
		__( 'Kyrgyzstan', 'justwpforms' ),
		__( 'Lao, People\'s Democratic Republic', 'justwpforms' ),
		__( 'Latvia', 'justwpforms' ),
		__( 'Lebanon', 'justwpforms' ),
		__( 'Lesotho', 'justwpforms' ),
		__( 'Liberia', 'justwpforms' ),
		__( 'Libyan Arab Jamahiriya', 'justwpforms' ),
		__( 'Liechtenstein', 'justwpforms' ),
		__( 'Lithuania', 'justwpforms' ),
		__( 'Luxembourg', 'justwpforms' ),
		__( 'Macau', 'justwpforms' ),
		__( 'Macedonia, The Former Yugoslav Republic of', 'justwpforms' ),
		__( 'Madagascar', 'justwpforms' ),
		__( 'Malawi', 'justwpforms' ),
		__( 'Malaysia', 'justwpforms' ),
		__( 'Maldives', 'justwpforms' ),
		__( 'Mali', 'justwpforms' ),
		__( 'Malta', 'justwpforms' ),
		__( 'Marshall Islands', 'justwpforms' ),
		__( 'Martinique', 'justwpforms' ),
		__( 'Mauritania', 'justwpforms' ),
		__( 'Mauritius', 'justwpforms' ),
		__( 'Mayotte', 'justwpforms' ),
		__( 'Mexico', 'justwpforms' ),
		__( 'Micronesia, Federated States of', 'justwpforms' ),
		__( 'Moldova, Republic of', 'justwpforms' ),
		__( 'Monaco', 'justwpforms' ),
		__( 'Mongolia', 'justwpforms' ),
		__( 'Montserrat', 'justwpforms' ),
		__( 'Morocco', 'justwpforms' ),
		__( 'Mozambique', 'justwpforms' ),
		__( 'Myanmar', 'justwpforms' ),
		__( 'Namibia', 'justwpforms' ),
		__( 'Nauru', 'justwpforms' ),
		__( 'Nepal', 'justwpforms' ),
		__( 'Netherlands', 'justwpforms' ),
		__( 'Netherlands Antilles', 'justwpforms' ),
		__( 'New Caledonia', 'justwpforms' ),
		__( 'New Zealand', 'justwpforms' ),
		__( 'Nicaragua', 'justwpforms' ),
		__( 'Niger', 'justwpforms' ),
		__( 'Nigeria', 'justwpforms' ),
		__( 'Niue', 'justwpforms' ),
		__( 'Norfolk Island', 'justwpforms' ),
		__( 'Northern Mariana Islands', 'justwpforms' ),
		__( 'Norway', 'justwpforms' ),
		__( 'Oman', 'justwpforms' ),
		__( 'Pakistan', 'justwpforms' ),
		__( 'Palau', 'justwpforms' ),
		__( 'Panama', 'justwpforms' ),
		__( 'Papua New Guinea', 'justwpforms' ),
		__( 'Paraguay', 'justwpforms' ),
		__( 'Peru', 'justwpforms' ),
		__( 'Philippines', 'justwpforms' ),
		__( 'Pitcairn', 'justwpforms' ),
		__( 'Poland', 'justwpforms' ),
		__( 'Portugal', 'justwpforms' ),
		__( 'Puerto Rico', 'justwpforms' ),
		__( 'Qatar', 'justwpforms' ),
		__( 'Reunion', 'justwpforms' ),
		__( 'Romania', 'justwpforms' ),
		__( 'Russian Federation', 'justwpforms' ),
		__( 'Rwanda', 'justwpforms' ),
		__( 'Saint Kitts and Nevis', 'justwpforms' ),
		__( 'Saint Lucia', 'justwpforms' ),
		__( 'Saint Vincent and the Grenadines', 'justwpforms' ),
		__( 'Samoa', 'justwpforms' ),
		__( 'San Marino', 'justwpforms' ),
		__( 'Sao Tome and Principe', 'justwpforms' ),
		__( 'Saudi Arabia', 'justwpforms' ),
		__( 'Senegal', 'justwpforms' ),
		__( 'Seychelles', 'justwpforms' ),
		__( 'Sierra Leone', 'justwpforms' ),
		__( 'Singapore', 'justwpforms' ),
		__( 'Slovakia (Slovak Republic)', 'justwpforms' ),
		__( 'Slovenia', 'justwpforms' ),
		__( 'Solomon Islands', 'justwpforms' ),
		__( 'Somalia', 'justwpforms' ),
		__( 'South Africa', 'justwpforms' ),
		__( 'South Georgia and the South Sandwich Islands', 'justwpforms' ),
		__( 'Spain', 'justwpforms' ),
		__( 'Sri Lanka', 'justwpforms' ),
		__( 'St. Helena', 'justwpforms' ),
		__( 'St. Pierre and Miquelon', 'justwpforms' ),
		__( 'Sudan', 'justwpforms' ),
		__( 'Suriname', 'justwpforms' ),
		__( 'Svalbard and Jan Mayen Islands', 'justwpforms' ),
		__( 'Swaziland', 'justwpforms' ),
		__( 'Sweden', 'justwpforms' ),
		__( 'Switzerland', 'justwpforms' ),
		__( 'Syrian Arab Republic', 'justwpforms' ),
		__( 'Taiwan, Province of China', 'justwpforms' ),
		__( 'Tajikistan', 'justwpforms' ),
		__( 'Tanzania, United Republic of', 'justwpforms' ),
		__( 'Thailand', 'justwpforms' ),
		__( 'Togo', 'justwpforms' ),
		__( 'Tokelau', 'justwpforms' ),
		__( 'Tonga', 'justwpforms' ),
		__( 'Trinidad and Tobago', 'justwpforms' ),
		__( 'Tunisia', 'justwpforms' ),
		__( 'Turkey', 'justwpforms' ),
		__( 'Turkmenistan', 'justwpforms' ),
		__( 'Turks and Caicos Islands', 'justwpforms' ),
		__( 'Tuvalu', 'justwpforms' ),
		__( 'Uganda', 'justwpforms' ),
		__( 'Ukraine', 'justwpforms' ),
		__( 'United Arab Emirates', 'justwpforms' ),
		__( 'United Kingdom', 'justwpforms' ),
		__( 'United States', 'justwpforms' ),
		__( 'United States Minor Outlying Islands', 'justwpforms' ),
		__( 'Uruguay', 'justwpforms' ),
		__( 'Uzbekistan', 'justwpforms' ),
		__( 'Vanuatu', 'justwpforms' ),
		__( 'Venezuela', 'justwpforms' ),
		__( 'Vietnam', 'justwpforms' ),
		__( 'Virgin Islands (British)', 'justwpforms' ),
		__( 'Virgin Islands (U.S.)', 'justwpforms' ),
		__( 'Wallis and Futuna Islands', 'justwpforms' ),
		__( 'Western Sahara', 'justwpforms' ),
		__( 'Yemen', 'justwpforms' ),
		__( 'Yugoslavia', 'justwpforms' ),
		__( 'Zambia', 'justwpforms' ),
		__( 'Zimbabwe', 'justwpforms' ),
	);
}

endif;

if ( ! function_exists( 'justwpforms_is_preview' ) ):
/**
 * Returns whether or not we're previewing a justwpform.
 *
 * @since 1.3
 *
 * @return void
 */
function justwpforms_is_preview() {
	$post_type = justwpforms_get_form_controller()->post_type;
	$is_justwpform = get_post_type() === $post_type;
	$justwpform_parameter = isset( $_POST['justwpforms'] );

	// Preview frame
	if ( $is_justwpform && is_customize_preview() ) {
		return true;
	}

	// Ajax calls
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $justwpform_parameter ) {
		return true;
	}

	return false;
}

endif;

if ( ! function_exists( 'justwpforms_get_email_part_label' ) ):

function justwpforms_get_email_part_label( $message, $part = array(), $form = array() ) {
	$label = justwpforms_get_part_label( $part );
	$label = apply_filters( 'justwpforms_email_part_label', $label, $message, $part, $form );

	return $label;
}

endif;

if ( ! function_exists( 'justwpforms_get_email_part_value' ) ):

function justwpforms_get_email_part_value( $message, $part = array(), $form = array(), $context = '' ) {
	$parts = $message['parts'];
	$part_id = $part['id'];
	$value = justwpforms_get_message_part_value( $parts[$part_id], $part, 'email' );
	$value = apply_filters( 'justwpforms_email_part_value', $value, $message, $part, $form, $context );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_email_is_part_visible' ) ):

function justwpforms_email_is_part_visible( $part = array(), $form = array(), $response = array() ) {
	$visible = apply_filters( 'justwpforms_email_part_visible', true, $part, $form, $response );

	return $visible;
}

endif;

if ( ! function_exists( 'justwpforms_owner_email_template_path' ) ):

function justwpforms_owner_email_template_path() {
	$path = justwpforms_get_include_folder() . '/templates/email-owner.php';
	$path = apply_filters( 'justwpforms_owner_email_template_path', $path );

	return $path;
}

endif;

if ( ! function_exists( 'justwpforms_user_email_template_path' ) ):

function justwpforms_user_email_template_path() {
	$path = justwpforms_get_include_folder() . '/templates/email-user.php';
	$path = apply_filters( 'justwpforms_user_email_template_path', $path );

	return $path;
}

endif;

if ( ! function_exists( 'justwpforms_is_preview_context' ) ) :

function justwpforms_is_preview_context() {
	$preview = is_customize_preview();
	$block = justwpforms_is_block_context();

	return $preview || $block;
}

endif;

if ( ! function_exists( 'justwpforms_is_block_context' ) ) :

function justwpforms_is_block_context() {
	$is_block = defined( 'REST_REQUEST' ) && REST_REQUEST;

	return $is_block;
}

endif;

if ( ! function_exists( 'justwpforms_is_gutenberg' ) ):

function justwpforms_is_gutenberg() {
	global $wp_version;

	$is_50 = version_compare( $wp_version, '5.0-alpha', '>=' );
	$is_plugin = is_plugin_active( 'gutenberg/gutenberg.php' );
	$is_gutenberg = $is_50 || $is_plugin;

	return $is_gutenberg;
}

endif;

if ( ! function_exists( 'justwpforms_update_meta' ) ):

function justwpforms_update_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
	$meta_key = "_justwpforms_{$meta_key}";

	return update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
}

endif;

if ( ! function_exists( 'justwpforms_get_meta' ) ):

function justwpforms_get_meta( $post_id, $key = '', $single = false ) {
	$key = "_justwpforms_{$key}";

	return get_post_meta( $post_id, $key, $single );
}

endif;

if ( ! function_exists( 'justwpforms_delete_meta' ) ):

function justwpforms_delete_meta( $post_id, $key = '', $value = '' ) {
	$key = "_justwpforms_{$key}";

	return delete_post_meta( $post_id, $key, $value );
}

endif;

if ( ! function_exists( 'justwpforms_meta_exists' ) ):

function justwpforms_meta_exists( $post_id, $key = '' ) {
	$key = "_justwpforms_{$key}";

	return metadata_exists( 'post', $post_id, $key );
}

endif;

if ( ! function_exists( 'justwpforms_unprefix_meta' ) ):

function justwpforms_unprefix_meta( $meta ) {
	$meta = $meta ? $meta : array();
	$meta = array_map( function( $entry ) {
		return reset( $entry );
	}, $meta );
	$meta = array_map( 'maybe_unserialize', $meta );
	$prefixed_meta = array();
	$unprefixed_meta = array();

	foreach( $meta as $key => $value ) {
		if ( false !== strpos( $key, '_justwpforms_' ) ) {
			$unprefixed_key = str_replace( '_justwpforms_', '', $key );
			$prefixed_meta[$unprefixed_key] = $value;
		} else {
			$unprefixed_meta[$key] = $value;
		}
	}

	foreach( $unprefixed_meta as $key => $value ) {
		if ( ! isset( $prefixed_meta[$key] ) ) {
			$prefixed_meta[$key] = $value;
		}
	}

	return $prefixed_meta;
}

endif;

if ( ! function_exists( 'justwpforms_prefix_meta' ) ):

function justwpforms_prefix_meta( $meta ) {
	foreach( $meta as $key => $value ) {
		$prefixed_key = "_justwpforms_{$key}";
		$meta[$prefixed_key] = $value;
		unset( $meta[$key] );
	}

	return $meta;
}

endif;

if ( ! function_exists( 'justwpforms_get_message_title' ) ):

function justwpforms_get_message_title( $message_id ) {
	$title = sprintf( __( 'Submission #%s', 'justwpforms' ), $message_id );

	return $title;
}

endif;

if ( ! function_exists( 'justwpforms_explode_value' ) ):

function justwpforms_explode_value( $value, $separator = '' ) {
	$value = explode( ',', $value );
	$value = array_map( 'trim', $value );
	$value = array_filter( $value );

	return $value;
}

endif;

if ( ! function_exists( 'justwpforms_customize_get_current_form' ) ):

function justwpforms_customize_get_current_form() {
	$form = justwpforms()->customize->get_current_form();

	if ( is_array( $form ) ) {
		return $form;
	}
}

endif;

if ( ! function_exists( 'justwpforms_get_part_customize_fields' ) ):

function justwpforms_get_part_customize_fields( $fields, $type ) {
	return apply_filters( "justwpforms_part_customize_fields_{$type}", $fields );
}

endif;

if ( ! function_exists( 'justwpforms_get_part_customize_template_path' ) ):

function justwpforms_get_part_customize_template_path( $template, $type ) {
	return apply_filters( "justwpforms_part_customize_template_path_{$type}", $template );
}

endif;

if ( ! function_exists( 'justwpforms_get_part_frontend_template_path' ) ):

function justwpforms_get_part_frontend_template_path( $template, $type ) {
	return apply_filters( "justwpforms_part_frontend_template_path_{$type}", $template );
}

endif;

if ( ! function_exists( 'justwpforms_get_php_locales' ) ):

function justwpforms_get_php_locales( $code = '' ) {
	$locales = array(
		'af' => __( 'Afrikaans', 'justwpforms' ),
		'ak' => __( 'Akan', 'justwpforms' ),
		'sq' => __( 'Albanian', 'justwpforms' ),
		'arq' => __( 'Algerian Arabic', 'justwpforms' ),
		'am' => __( 'Amharic', 'justwpforms' ),
		'ar' => __( 'Arabic', 'justwpforms' ),
		'hy' => __( 'Armenian', 'justwpforms' ),
		'rup' => __( 'Aromanian', 'justwpforms' ),
		'frp' => __( 'Arpitan', 'justwpforms' ),
		'as' => __( 'Assamese', 'justwpforms' ),
		'az' => __( 'Azerbaijani', 'justwpforms' ),
		'bcc' => __( 'Balochi Southern', 'justwpforms' ),
		'ba' => __( 'Bashkir', 'justwpforms' ),
		'eu' => __( 'Basque', 'justwpforms' ),
		'bel' => __( 'Belarusian', 'justwpforms' ),
		'bn' => __( 'Bengali', 'justwpforms' ),
		'bs' => __( 'Bosnian', 'justwpforms' ),
		'br' => __( 'Breton', 'justwpforms' ),
		'bg' => __( 'Bulgarian', 'justwpforms' ),
		'ca' => __( 'Catalan', 'justwpforms' ),
		'ceb' => __( 'Cebuano', 'justwpforms' ),
		'zh' => __( 'Chinese', 'justwpforms' ),
		'co' => __( 'Corsican', 'justwpforms' ),
		'hr' => __( 'Croatian', 'justwpforms' ),
		'cs' => __( 'Czech', 'justwpforms' ),
		'da' => __( 'Danish', 'justwpforms' ),
		'dv' => __( 'Dhivehi', 'justwpforms' ),
		'nl' => __( 'Dutch', 'justwpforms' ),
		'dzo' => __( 'Dzongkha', 'justwpforms' ),
		'en' => __( 'English', 'justwpforms' ),
		'eo' => __( 'Esperanto', 'justwpforms' ),
		'et' => __( 'Estonian', 'justwpforms' ),
		'fo' => __( 'Faroese', 'justwpforms' ),
		'fi' => __( 'Finnish', 'justwpforms' ),
		'fr' => __( 'French', 'justwpforms' ),
		'fy' => __( 'Frisian', 'justwpforms' ),
		'fur' => __( 'Friulian', 'justwpforms' ),
		'fuc' => __( 'Fulah', 'justwpforms' ),
		'gl' => __( 'Galician', 'justwpforms' ),
		'ka' => __( 'Georgian', 'justwpforms' ),
		'de' => __( 'German', 'justwpforms' ),
		'el' => __( 'Greek', 'justwpforms' ),
		'kal' => __( 'Greenlandic', 'justwpforms' ),
		'gn' => __( 'Guaraní', 'justwpforms' ),
		'gu' => __( 'Gujarati', 'justwpforms' ),
		'haw' => __( 'Hawaiian', 'justwpforms' ),
		'haz' => __( 'Hazaragi', 'justwpforms' ),
		'he' => __( 'Hebrew', 'justwpforms' ),
		'hi' => __( 'Hindi', 'justwpforms' ),
		'hu' => __( 'Hungarian', 'justwpforms' ),
		'is' => __( 'Icelandic', 'justwpforms' ),
		'ido' => __( 'Ido', 'justwpforms' ),
		'id' => __( 'Indonesian', 'justwpforms' ),
		'ga' => __( 'Irish', 'justwpforms' ),
		'it' => __( 'Italian', 'justwpforms' ),
		'ja' => __( 'Japanese', 'justwpforms' ),
		'jv' => __( 'Javanese', 'justwpforms' ),
		'kab' => __( 'Kabyle', 'justwpforms' ),
		'kn' => __( 'Kannada', 'justwpforms' ),
		'kk' => __( 'Kazakh', 'justwpforms' ),
		'km' => __( 'Khmer', 'justwpforms' ),
		'kin' => __( 'Kinyarwanda', 'justwpforms' ),
		'ky' => __( 'Kirghiz', 'justwpforms' ),
		'ko' => __( 'Korean', 'justwpforms' ),
		'ckb' => __( 'Kurdish', 'justwpforms' ),
		'lo' => __( 'Lao', 'justwpforms' ),
		'lv' => __( 'Latvian', 'justwpforms' ),
		'li' => __( 'Limburgish', 'justwpforms' ),
		'lin' => __( 'Lingala', 'justwpforms' ),
		'lt' => __( 'Lithuanian', 'justwpforms' ),
		'lb' => __( 'Luxembourgish', 'justwpforms' ),
		'mk' => __( 'Macedonian', 'justwpforms' ),
		'mg' => __( 'Malagasy', 'justwpforms' ),
		'ms' => __( 'Malay', 'justwpforms' ),
		'ml' => __( 'Malayalam', 'justwpforms' ),
		'mri' => __( 'Maori', 'justwpforms' ),
		'mr' => __( 'Marathi', 'justwpforms' ),
		'xmf' => __( 'Mingrelian', 'justwpforms' ),
		'mn' => __( 'Mongolian', 'justwpforms' ),
		'me' => __( 'Montenegrin', 'justwpforms' ),
		'ary' => __( 'Moroccan Arabic', 'justwpforms' ),
		'mya' => __( 'Myanmar (Burmese)', 'justwpforms' ),
		'ne' => __( 'Nepali', 'justwpforms' ),
		'nb' => __( 'Norwegian (Bokmål)', 'justwpforms' ),
		'nn' => __( 'Norwegian (Nynorsk)', 'justwpforms' ),
		'oci' => __( 'Occitan', 'justwpforms' ),
		'ory' => __( 'Oriya', 'justwpforms' ),
		'os' => __( 'Ossetic', 'justwpforms' ),
		'ps' => __( 'Pashto', 'justwpforms' ),
		'fa' => __( 'Persian', 'justwpforms' ),
		'pl' => __( 'Polish', 'justwpforms' ),
		'pt' => __( 'Portuguese', 'justwpforms' ),
		'pa' => __( 'Punjabi', 'justwpforms' ),
		'rhg' => __( 'Rohingya', 'justwpforms' ),
		'ro' => __( 'Romanian', 'justwpforms' ),
		'roh' => __( 'Romansh Vallader', 'justwpforms' ),
		'ru' => __( 'Russian', 'justwpforms' ),
		'rue' => __( 'Rusyn', 'justwpforms' ),
		'sah' => __( 'Sakha', 'justwpforms' ),
		'sa' => __( 'Sanskrit', 'justwpforms' ),
		'srd' => __( 'Sardinian', 'justwpforms' ),
		'gd' => __( 'Scottish Gaelic', 'justwpforms' ),
		'sr' => __( 'Serbian', 'justwpforms' ),
		'szl' => __( 'Silesian', 'justwpforms' ),
		'snd' => __( 'Sindhi', 'justwpforms' ),
		'si' => __( 'Sinhala', 'justwpforms' ),
		'sk' => __( 'Slovak', 'justwpforms' ),
		'sl' => __( 'Slovenian', 'justwpforms' ),
		'so' => __( 'Somali', 'justwpforms' ),
		'azb' => __( 'South Azerbaijani', 'justwpforms' ),
		'es' => __( 'Spanish', 'justwpforms' ),
		'su' => __( 'Sundanese', 'justwpforms' ),
		'sw' => __( 'Swahili', 'justwpforms' ),
		'sv' => __( 'Swedish', 'justwpforms' ),
		'gsw' => __( 'Swiss German', 'justwpforms' ),
		'tl' => __( 'Tagalog', 'justwpforms' ),
		'tah' => __( 'Tahitian', 'justwpforms' ),
		'tg' => __( 'Tajik', 'justwpforms' ),
		'tzm' => __( 'Tamazight', 'justwpforms' ),
		'ta' => __( 'Tamil', 'justwpforms' ),
		'tt' => __( 'Tatar', 'justwpforms' ),
		'te' => __( 'Telugu', 'justwpforms' ),
		'th' => __( 'Thai', 'justwpforms' ),
		'bo' => __( 'Tibetan', 'justwpforms' ),
		'tir' => __( 'Tigrinya', 'justwpforms' ),
		'tr' => __( 'Turkish', 'justwpforms' ),
		'tuk' => __( 'Turkmen', 'justwpforms' ),
		'twd' => __( 'Tweants', 'justwpforms' ),
		'ug' => __( 'Uighur', 'justwpforms' ),
		'uk' => __( 'Ukrainian', 'justwpforms' ),
		'ur' => __( 'Urdu', 'justwpforms' ),
		'uz' => __( 'Uzbek', 'justwpforms' ),
		'vi' => __( 'Vietnamese', 'justwpforms' ),
		'wa' => __( 'Walloon', 'justwpforms' ),
		'cy' => __( 'Welsh', 'justwpforms' ),
		'yor' => __( 'Yoruba', 'justwpforms' ),
	);

	if ( empty( $code ) ) {
		return $locales;
	}

	$code = strtolower( $code );

	if ( isset( $locales[$code] ) ) {
		return $locales[$code];
	}

	$code = explode( '-', $code );
	$code = reset( $code );

	if ( isset( $locales[$code] ) ) {
		return $locales[$code];
	}

	return '';
}

endif;

if ( ! function_exists( 'justwpforms_customize_part_footer' ) ):

function justwpforms_customize_part_footer() {
	$template = justwpforms_get_include_folder() . '/templates/customize-form-part-footer.php';
	$template = apply_filters( 'justwpforms_part_customize_footer_template_path', $template );

	$html = '';

	ob_start();
		require( $template );
	$html = ob_get_clean();

	echo $html;
}

endif;

if ( ! function_exists( 'justwpforms_customize_part_logic' ) ) :

function justwpforms_customize_part_logic() {
	$template_path = '';
	$template_html = '';

	$template_path = apply_filters( 'justwpforms_customize_part_logic_template_path', $template_path );

	if ( '' !== $template_path ) {
		ob_start();
			require( $template_path );
		$template_html = ob_get_clean();
	}

	echo $template_html;
}

endif;

if ( ! function_exists( 'justwpforms_customize_part_choice_logic' ) ) :

function justwpforms_customize_part_choice_logic() {
	$template_path = '';
	$template_html = '';

	$template_path = apply_filters( 'justwpforms_customize_part_choice_logic_template_path', $template_path );

	if ( '' !== $template_path ) {
		ob_start();
			require( $template_path );
		$template_html = ob_get_clean();
	}

	echo $template_html;
}

endif;

if ( ! function_exists( 'justwpforms_customize_part_choice_footer' ) ):

function justwpforms_customize_part_choice_footer() {
	$template = justwpforms_get_include_folder() . '/templates/customize-form-part-choice-footer.php';
	$template = apply_filters( 'justwpforms_customize_part_choice_footer_template_path', $template );

	$html = '';

	ob_start();
		require( $template );
	$html = ob_get_clean();

	echo $html;
}

endif;

if ( ! function_exists( 'justwpforms_customize_part_width_control' ) ) :

function justwpforms_customize_part_width_control() {
	require( justwpforms_get_core_folder() . '/templates/partials/customize-field-width.php' );
}

endif;

if ( ! function_exists( 'justwpforms_get_validation_message' ) ) :

function justwpforms_get_validation_message( $message_key ) {
	$validation_messages = justwpforms_validation_messages();

	return $validation_messages->get_message( $message_key );
}

endif;

if ( ! function_exists( 'justwpforms_debug_log_enabled' ) ):

function justwpforms_debug_log_enabled() {
	$enabled = (
		( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) &&
		( defined( 'WP_DEBUG_LOG' ) && true === WP_DEBUG_LOG )
	);

	return $enabled;
}

endif;

if ( ! function_exists( 'justwpforms_log_error' ) ):

function justwpforms_log_error( $error ) {
	error_log( "[justwpforms:{$error->get_error_code()}] {$error->get_error_message()}" );
}

endif;

if ( ! function_exists( 'justwpforms_cache_get' ) ):

function justwpforms_cache_get( $key, &$found = null ) {
	return justwpforms_get_cache()->get( $key, $found );
}

endif;

if ( ! function_exists( 'justwpforms_cache_set' ) ):

function justwpforms_cache_set( $key, $value ) {
	return justwpforms_get_cache()->set( $key, $value );
}

endif;

if ( ! function_exists( 'justwpforms_is_email' ) ):

function justwpforms_is_email( $email ) {
	$email = justwpforms_get_email_encoder()->encode_email( $email );
	$is_email = is_email( $email );
	$is_email = apply_filters( 'justwpforms_is_email', $is_email, $email );

	return $is_email;
}

endif;

if ( ! function_exists( 'justwpforms_is_admin_screen' ) ):

function justwpforms_is_admin_screen( $id = '' ) {
	$current_screen = get_current_screen();

	if ( ! $current_screen ) {
		return false;
	}

	if ( empty( $id ) ) {
		$is_admin_screen = (
			in_array( $current_screen->id, array(
				'edit-justwpform',
				'edit-justwpforms-message',
				'justwpforms-message',
			) ) ||
			justwpforms_is_admin_screen( 'justwpforms-welcome' ) ||
			justwpforms_is_admin_screen( 'justwpforms-settings' ) ||
			justwpforms_is_admin_screen( 'justwpforms-integrations' )
		);

		return $is_admin_screen;
	}

	$prefix = sanitize_title( __( 'Forms', 'justwpforms' ) );
	$is_admin_screen = "{$prefix}_page_{$id}" === $current_screen->id;

	return $is_admin_screen;
}

endif;


if ( ! function_exists( 'justwpforms_safe_array_merge' ) ):

function justwpforms_safe_array_merge( $a, $b ) {
	foreach( $b as $key => $value ) {
		if ( isset( $a[$key] ) ) {
			throw new Exception( __( "Duplicate key {$key}", 'justwpforms' ) );
		}

		$a[$key] = $value;
	}

	return $a;
}

endif;

if ( ! function_exists( 'justwpforms_concatenate_scripts' ) ) :

function justwpforms_concatenate_scripts() {
	$is_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
	$is_debug = $is_debug || justwpforms_is_preview();
	$concatenate = apply_filters( 'justwpforms_concatenate_scripts', ! $is_debug );

	return $concatenate;
}

endif;

if ( ! function_exists( 'justwpforms_concatenate_styles' ) ) :

function justwpforms_concatenate_styles() {
	$is_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
	$is_debug = $is_debug || justwpforms_is_preview();
	$concatenate = apply_filters( 'justwpforms_concatenate_styles', ! $is_debug );

	return $concatenate;
}

endif;

if ( ! function_exists( 'justwpforms_random_number' ) ) :

function justwpforms_random_number( $seed = '' ) {
	$seed = '' !== $seed ? $seed : microtime();
	$seed = sha1( $seed );
	$seed = substr( $seed, 0, 8 );
	$seed = abs( hexdec( $seed ) );

	return $seed;
}

endif;

if ( ! function_exists( 'justwpforms_random_range' ) ) :

function justwpforms_random_range( $length, $seed = '' ) {
	$range = array();

	for ( $v = 0; $v < $length; $v ++ ) {
		$seed = justwpforms_random_number( $seed );
		$range[] = $seed;
	}

	$range = array_flip( $range );
	ksort( $range, SORT_NUMERIC );
	$range = array_values( $range );

	return $range;
}

endif;

if ( ! function_exists( 'justwpforms_shuffle_array' ) ) :

function justwpforms_shuffle_array( $array, $seed = '' ) {
	$indices = justwpforms_random_range( count( $array ), $seed );
	$shuffled = array();

	foreach( $indices as $index ) {
		$shuffled[] = $array[$index];
	}

	return $shuffled;
}

endif;

if ( ! function_exists( 'justwpforms_the_external_link_icon' ) ) :

function justwpforms_the_external_link_icon( $echo = true ) {
	if ( ! $echo ) {
		ob_start();
	}
?><svg xmlns="http://www.w3.org/2000/svg" viewBox="11 -4 1 24" width="20" height="18" class="components-external-link__icon css-bqq7t3 etxm6pv0" role="img" aria-hidden="true" focusable="false" style="margin-bottom: -2px;" fill="currentColor"><path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path></svg><?php
	if ( ! $echo ) {
		return ob_get_clean();
	}
}

endif;
