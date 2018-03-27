<?php
/**
 * Helper functions are bundled here
 */

function advanced_notifications_get_owner_subscription_settings() {
	static $settings;
	
	if (isset($settings)) {
		return $settings;
	}
	
	$settings = advanced_notifications_get_notification_events();
	
	$plugin_settings = elgg_get_plugin_setting('owner_subscription', 'advanced_notifications');
	if (empty($plugin_settings)) {
		return $settings;
	}
	
	$plugin_settings = json_decode($plugin_settings, true);
	foreach ($plugin_settings as $type => $subtypes) {
		if (!is_array($subtypes)) {
			// eg. group
			$enabled = (bool) $subtypes;
			if (!$enabled) {
				unset($settings[$type]);
			}
			
			continue;
		}
		
		foreach ($subtypes as $subtype => $enabled) {
			$enabled = (bool) $enabled;
			if ($enabled) {
				continue;
			}
			
			unset($settings[$type][$subtype]);
		}
	}
	
	return $settings;
}

/**
 * Get all the registered type/subtype notification events
 *
 * @see elgg_register_notification_event()
 *
 * @return array
 */
function advanced_notifications_get_notification_events() {
	return _elgg_services()->notifications->getEvents();
}
