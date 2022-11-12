<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?php wp_title(); ?></title>
		<?php wp_head(); ?>

		<link rel="stylesheet" type="text/css" href="<?php echo justwpforms_get_plugin_url() . '/core/assets/css/notice.css'; ?>">
	</head>
	<body class="justwpforms-preview">
		<?php global $post; $form = justwpforms_get_form_controller()->get( $post->ID ); ?>
		<?php justwpforms_get_form_assets()->output( $form, justwpforms_Form_Assets::MODE_CUSTOMIZER ); ?>
		<?php include( justwpforms_get_core_folder() . '/templates/single-form.php' ); ?>
		<?php wp_footer(); ?>
	</body>
</html>
