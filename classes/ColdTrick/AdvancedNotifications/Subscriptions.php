<?php

namespace ColdTrick\AdvancedNotifications;

use Elgg\Notifications\NotificationEvent;
use Elgg\Notifications\InstantNotificationEvent;

class Subscriptions {
	
	/**
	 * Add content owner subscribers to the list
	 *
	 * @param string $hook         the name of the hook
	 * @param string $type         the type of the hook
	 * @param array  $return_value current return value
	 * @param array  $params       supplied params
	 *
	 * @return void|array
	 */
	public static function addOwnerSubscribers($hook, $type, $return_value, $params) {
		
		if (!elgg_get_plugin_setting('notify_owner_subscribers', 'advanced_notifications')) {
			return;
		}
		
		$event = elgg_extract('event', $params);
		if (!$event instanceof NotificationEvent) {
			return;
		}
		
		$object = $event->getObject();
		if (!$object instanceof \ElggEntity) {
			return;
		}
		
		if ($object->owner_guid === $object->container_guid) {
			// container subscribers are already added
			// since owner is the same, don't duplicate
			return;
		}
		
		if ($event instanceof InstantNotificationEvent) {
			if (!$object instanceof \ElggComment) {
				// only extend the enqueued notifications
				return;
			}
			
			if (self::isRegisteredNotificationEvent($object->getType(), $object->getSubtype(), $event->getAction())) {
				// event will also be enqueued, extend subscribers then
				return;
			}
		}
		
		$subscribers = elgg_get_subscriptions_for_container($object->owner_guid);
		if (empty($subscribers)) {
			return;
		}
		
		foreach ($subscribers as $user_guid => $methods) {
			if (isset($return_value[$user_guid])) {
				// already in the list, don't add/overrule settings
				continue;
			}
			
			$return_value[$user_guid] = $methods;
		}
		
		return $return_value;
	}
	
	/**
	 * Check if a notification event is registered
	 *
	 * @param string $type    object type
	 * @param string $subtype object subtype
	 * @param string $action  action (create, update, etc)
	 *
	 * @return bool
	 */
	protected static function isRegisteredNotificationEvent($type, $subtype, $action) {
		
		$events = _elgg_services()->notifications->getEvents();
		if (empty($events) || !is_array($events)) {
			return false;
		}
		
		if (!isset($events[$type]) || !isset($events[$type][$subtype])) {
			return false;
		}
		
		if (!in_array($action, $events[$type][$subtype])) {
			return false;
		}
		
		return true;
	}
}
