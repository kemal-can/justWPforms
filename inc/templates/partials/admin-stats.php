<div class="justwpforms-stats-panel">
	<?php foreach( $widgets as $widget ) : ?>
	<?php $trend_class = $widget['trend'] > 0 ? 'positive' : ( $widget['trend'] < 0 ? 'negative' : 'equal' ); ?>
	<div class="justwpforms-stats-widget justwpforms-stats-widget-<?php echo $widget['id']; ?> <?php echo $trend_class; ?>">
		<h2>
			<strong><?php echo array_sum( $widget['values'] ); ?></strong>
			<span class="justwpforms-stats-widget__trend"><?php echo abs( $widget['trend'] ); ?>%</span><br>
			<?php echo $widget['title']; ?>
		</h2>
		<ul class="justwpforms-stats-widget__bars">
		<?php foreach( $widget['bars'] as $bar ) : ?>
			<li style="height: <?php echo $bar; ?>;"></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php endforeach; ?>
</div>