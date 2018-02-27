<?php
/**
 * The main plugin file
 */

// register default Elgg events
elgg_register_event_handler('init', 'system', 'advanced_notifications_init');
elgg_register_event_handler('plugins_boot', 'system', 'advanced_notifications_plugins_boot');

/**
 * Called during plugins boot
 *
 * @return void
 */
function advanced_notifications_plugins_boot() {
	
	if (!elgg_get_plugin_setting('queue_delay', 'advanced_notifications')) {
		return;
	}
	
	$sp = _elgg_services();
	$sp->setFactory('notifications', function($sp) {
		$queue_name = \Elgg\Notifications\NotificationsService::QUEUE_NAME;
		$queue = new \ColdTrick\AdvancedNotifications\NotificationQueue($queue_name, $sp->db);
		$sub = new \Elgg\Notifications\SubscriptionsService($sp->db);
		return new \Elgg\Notifications\NotificationsService($sub, $queue, $sp->hooks, $sp->session, $sp->translator, $sp->entityTable, $sp->logger);
	});
}

/**
 * Gets called during system initialization
 *
 * @return void
 */
function advanced_notifications_init() {
	
	// register plugin hooks
	elgg_register_plugin_hook_handler('enqueue', 'notification', '\ColdTrick\AdvancedNotifications\Enqueue::preventPrivateNotifications', 9000);
	elgg_register_plugin_hook_handler('enqueue', 'notification', '\ColdTrick\AdvancedNotifications\Enqueue::delayPrivateContentNotification', 9001);
	
	elgg_register_plugin_hook_handler('get', 'subscriptions', '\ColdTrick\AdvancedNotifications\Subscriptions::addOwnerSubscribers');
	
	// register event handlers
	elgg_register_event_handler('update:after', 'object', '\ColdTrick\AdvancedNotifications\Enqueue::checkForDelayedNotification');
}
