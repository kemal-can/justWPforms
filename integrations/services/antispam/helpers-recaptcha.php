<?php

if ( ! function_exists( 'justwpforms_recaptcha' ) ) :

function justwpforms_recaptcha( $form ) {
	$has_captcha = apply_filters( 'justwpforms_form_has_captcha', false, $form );
	if ( $has_captcha ): ?>
	<div class="justwpforms-form__part justwpforms-part justwpforms-part--recaptcha justwpforms-recaptcha-v<?php echo justwpforms_get_recaptcha_version(); ?>" data-sitekey="<?php echo $form['captcha_site_key']; ?>" data-justwpforms-type="recaptcha_v<?php echo justwpforms_get_recaptcha_version(); ?>" data-theme="<?php echo isset( $form['captcha_theme'] ) ? $form['captcha_theme'] : ''; ?>">
		<?php if ( 2 === justwpforms_get_recaptcha_version() ) : ?>
			<label for="g-recaptcha-response" class="justwpforms-part__label">
				<span class="label"><?php echo $form['captcha_label']; ?></span>
			</label>
		<?php endif; ?>
		<div class="justwpforms-part-wrap" id="justwpforms-<?php echo $form['ID']; ?>-recaptcha"></div>

		<?php
		if ( 2 === justwpforms_get_recaptcha_version() ) {
			justwpforms_part_error_message( justwpforms_get_recaptcha_part_name( $form ) );
		}
		?>
	</div>
	<?php endif;
}

endif;

if ( ! function_exists( 'justwpforms_get_recaptcha_locales' ) ):

function justwpforms_get_recaptcha_locales() {
	$locales = array(
		'ar', 'af', 'am', 'hy', 'az', 'eu',
		'bn', 'bg', 'ca', 'zh-HK', 'zh-CN',
		'zh-TW', 'hr', 'cs', 'da', 'nl', 'en-GB',
		'en', 'et', 'fil', 'fi', 'fr', 'fr-CA',
		'gl', 'ka', 'de', 'de-AT', 'de-CH', 'el',
		'gu', 'iw', 'hi', 'hu', 'is', 'id', 'it',
		'ja', 'kn', 'pl', 'pt', 'pt-BR', 'pt-PT',
		'ro', 'ru', 'sr', 'si', 'sk', 'sl', 'es',
		'es-419', 'sw', 'sv', 'ta', 'te', 'th',
		'tr', 'uk', 'ur', 'vi', 'zu'
	);

	return $locales;
}

endif;

if ( ! function_exists( 'justwpforms_get_recaptcha_locale' ) ):

function justwpforms_get_recaptcha_locale() {
	$wp_locale = get_locale();
	$locale = preg_replace( '/[-_]+.+/m', '', $wp_locale );
	$locales = justwpforms_get_recaptcha_locales();
	$locale = in_array( $locale, $locales ) ? $locale : '';
	$locale = apply_filters( 'justwpforms_recaptcha_locale', $locale, $wp_locale );

	return $locale;
}

endif;

if ( ! function_exists( 'justwpforms_get_recaptcha_part_name' ) ) :

	function justwpforms_get_recaptcha_part_name( $form ) {
		return "justwpforms-{$form['ID']}-recaptcha";
	}

endif;

if ( ! function_exists( 'justwpforms_get_recaptcha_version' ) ) :

	function justwpforms_get_recaptcha_version() {
		$version        = null;
		$active_service = justwpforms_get_antispam_integration()->get_active_service();

		if ( ! $active_service ) {
			return $version;
		}

		if ( 'recaptcha' === $active_service->id ) {
			$version = 2;
		} else if ( 'recaptchav3' === $active_service->id ) {
			$version = 3;
		}

		return $version;
	}

endif;
