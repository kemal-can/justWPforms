<script type="text/template" id="justwpforms-form-steps-template">
	<nav class="nav-tab-wrapper">
		<a href="#" class="nav-tab<%= ( 'build' === justwpforms.currentRoute ) ? ' nav-tab-active' : '' %>" data-step="build"><?php _e( 'Build', 'justwpforms' ); ?></a>
		<a href="#" class="nav-tab<%= ( 'setup' === justwpforms.currentRoute ) ? ' nav-tab-active' : '' %>" data-step="setup"><?php _e( 'Setup', 'justwpforms' ); ?></a>
		<a href="#" class="nav-tab<%= ( 'email' === justwpforms.currentRoute ) ? ' nav-tab-active' : '' %>" data-step="email"><?php _e( 'Emails', 'justwpforms' ); ?></a>
		<a href="#" class="nav-tab<%= ( 'messages' === justwpforms.currentRoute ) ? ' nav-tab-active' : '' %>" data-step="messages"><?php _e( 'Messages', 'justwpforms' ); ?></a>
		<a href="#" class="nav-tab<%= ( 'style' === justwpforms.currentRoute ) ? ' nav-tab-active' : '' %>" data-step="style"><?php _e( 'Styles', 'justwpforms' ); ?></a>
	</nav>
</script>
