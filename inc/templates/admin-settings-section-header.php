<?php
$section_id = "justwpforms-{$section['id']}-section";
$extra_classes = array( $section_id );

if ( isset( $section['extra_classes'] ) ) {
	$extra_classes = array_merge( $extra_classes, $section['extra_classes'] );
}

$section_state = $section['default_state'];

if ( isset( $user_sections_states[$section_id] ) ) {
	$section_state = $user_sections_states[$section_id];
}

if ( 'closed' === $section_state ) {
	$extra_classes[] = 'closed';
}
?>
<div id="<?php echo $section_id; ?>" class="postbox justwpforms-settings-section <?php echo join( ' ', $extra_classes ); ?>">
	<div class="postbox-header">
		<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
			<span class="screen-reader-text"><?php echo $section['title']; ?></span>
			<span class="toggle-indicator" aria-hidden="true"></span>
		</button>
		<h2 class="hndle"><?php echo $section['title']; ?> <span class="spinner"></span></h2>
	</div>
	<div class="inside">
		<?php if ( isset( $section['description'] ) ) : ?>
			<p><?php echo $section['description']; ?></p>
		<?php endif; ?>