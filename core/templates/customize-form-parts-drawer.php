<script type="text/template" id="justwpforms-form-parts-drawer-template">
	<div id="justwpforms-parts-drawer">
		<div class="justwpforms-parts-drawer-header">
			<div class="justwpforms-parts-drawer-header-search">
				<input type="text" placeholder="<?php _e( 'Search fields', 'justwpforms' ); ?>&hellip;" id="part-search">
				<div class="justwpforms-parts-drawer-header-search-icon"></div>
				<button type="button" class="justwpforms-clear-search"><span class="screen-reader-text"><?php _e( 'Clear Results', 'justwpforms' ); ?></span></button>
			</div>
		</div>
		<ul class="justwpforms-parts-list">
			<% for (var p = 0; p < parts.length; p ++) { var part = parts[p]; %>
			<%
				var customClass = '';
				var isDummy = false;
				var isGroup = false;

				if ( -1 !== part.type.indexOf( 'dummy' ) ) {
					isDummy = true;
				}

				if ( 'drawer_group' === part.group ) {
					isGroup = true;
				}

				if ( isDummy ) {
					customClass = ' justwpforms-parts-list-item--dummy';
				}

				if ( isGroup ) {
					customClass = ' justwpforms-parts-list-item--group';
				}
			%>
			<li class="justwpforms-parts-list-item<%= customClass %>" data-part-type="<%= part.type %>">
				<div class="justwpforms-parts-list-item-content">
					<div class="justwpforms-parts-list-item-title">
						<h3><%= part.label %></h3>
						<% if ( isDummy ) { %>&nbsp;<span class="members-only"><?php _e( 'Members Only', 'justwpforms') ?></span><% } %>
					</div>
					<div class="justwpforms-parts-list-item-description"><%= part.description %></div>
				</div>
			</li>
			<% } %>
		</ul>
		<div class="justwpforms-parts-drawer-not-found">
			<p><?php _e( 'No fields found.', 'justwpforms' ); ?></p>
		</div>
	</div>
</script>
