<?php
$integration = justwpforms_get_integration_google_analytics();
?>
<div data-justwpforms-type="google_analytics">
	<input type="hidden" name="justwpforms_ga_referer" value="<?php echo $integration->get_referer( $form ); ?>" />
	<input type="hidden" name="justwpforms_ga_user_agent" />
	<input type="hidden" name="justwpforms_ga_page_url" />
</div>