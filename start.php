<?php
/**
 * The main plugin file
 */

// register default Elgg events
elgg_register_event_handler('init', 'system', 'advanced_notifications_init');

/**
 * Gets called during system initialization
 *
 * @return void
 */
function advanced_notifications_init() {
	
	// register plugin hooks
	elgg_register_plugin_hook_handler('enqueue', 'notification', '\ColdTrick\AdvancedNotifications\Enqueue::preventPrivateNotifications', 9000);
}
