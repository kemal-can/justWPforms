<?php
/**
 *
 * Part loop
 *
 */
?>
<?php foreach( $form['parts'] as $part ) : ?>

	<?php if ( justwpforms_email_is_part_visible( $part, $form, $response ) ) : ?>

	<b><?php echo justwpforms_get_email_part_label( $response, $part, $form ); ?></b><br>

	<?php echo justwpforms_get_email_part_value( $response, $part, $form, 'admin-email' ); ?>
	<br><br>

	<?php endif; ?>

<?php endforeach; ?>

<?php
/**
 *
 * Tracking number
 *
 */
?>
<?php if ( justwpforms_get_form_property( $form, 'unique_id' ) ) : ?>

#<?php echo $response['tracking_id']; ?>

<?php endif; ?>

<?php
/**
 *
 * User data
 *
 */

if ( justwpforms_capture_client_ip() && ( justwpforms_get_form_property( $form, 'include_submitters_ip' ) ) ) : ?>

	<b><?php _e( 'IPv4/IPv6', 'justwpforms' ); ?></b><br>
	<?php echo justwpforms_get_meta( $response['ID'], 'client_ip', true ); ?>
	<br><br>

<?php endif; ?>

<?php if ( justwpforms_get_form_property( $form, 'include_referral_link' ) ): ?>

	<?php $page_referrer = justwpforms_get_meta( $response['ID'], 'client_referer', true ); ?>
	<b><?php _e( 'Referral', 'justwpforms' ); ?></b><br><a href="<?php echo $page_referrer; ?>"><?php echo $page_referrer; ?></a>
	<br><br>

<?php endif; ?>

<?php if ( justwpforms_get_form_property( $form, 'include_submission_date_time' ) ):

		$submitted = sprintf(
			__( '%1$s UTC%2$s', 'justwpforms' ),
			date_i18n( __( 'M j, Y g:i a' ), strtotime( $response['post_date'] ) ),
			date_i18n( __( 'P' ), strtotime( $response['post_date'] ) )
		);
		?>

		<b><?php _e( 'Date and time', 'justwpforms' ); ?></b><br><?php echo $submitted; ?><br>
		<br><br>
<?php endif; ?>
