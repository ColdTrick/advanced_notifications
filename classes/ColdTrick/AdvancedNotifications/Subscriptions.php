<?php

namespace ColdTrick\AdvancedNotifications;

use Elgg\Notifications\SubscriptionNotificationEvent;
use Elgg\Notifications\NotificationEvent;

class Subscriptions {
	
	/**
	 * Add content owner subscribers to the list
	 *
	 * @param \Elgg\Hook $hook 'get', 'subscriptions'
	 *
	 * @return void|array
	 */
	public static function addOwnerSubscribers(\Elgg\Hook $hook) {
		
		if (!elgg_get_plugin_setting('notify_owner_subscribers', 'advanced_notifications')) {
			return;
		}
		
		$event = $hook->getParam('event');
		if (!$event instanceof SubscriptionNotificationEvent) {
			return;
		}
		
		$object = $event->getObject();
		if (!$object instanceof \ElggEntity || $object->access_id === ACCESS_PRIVATE) {
			return;
		}
		
		if ($object->owner_guid === $object->container_guid) {
			// container subscribers are already added
			// since owner is the same, don't duplicate
			return;
		}
		
		if (!self::isAllowedNotificationEvent($object->getType(), $object->getSubtype(), $event->getAction())) {
			return;
		}
		
		$subscribers = elgg_get_subscriptions_for_container($object->owner_guid);
		if (empty($subscribers)) {
			return;
		}
		
		$return_value = $hook->getValue();
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
		
		$events = advanced_notifications_get_notification_events();
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
	
	/**
	 * Check if a notification event is allowed to be extended
	 *
	 * @param string $type    object type
	 * @param string $subtype object subtype
	 * @param string $action  action (create, update, etc)
	 *
	 * @return bool
	 */
	protected static function isAllowedNotificationEvent($type, $subtype, $action) {
		
		$settings = advanced_notifications_get_owner_subscription_settings();
		if (!isset($settings[$type])) {
			return false;
		}
		
		if (empty($subtype)) {
			return true;
		}
		
		return isset($settings[$type][$subtype]);
	}
	
	/**
	 * Validate that subscribers are member of an access collection.
	 *
	 * Not realy needed for 'normal' users but for admins this is required, as access isn't vaidated to them
	 *
	 * @param \Elgg\Hook $hook 'get', 'subscriptions'
	 *
	 * @return void|array
	 */
	public static function checkAccessCollectionMembership(\Elgg\Hook $hook) {
		
		$result = $hook->getValue();
		if (empty($result)) {
			// no subscribers to validate
			return;
		}
		
		$event = $hook->getParam('event');
		if (!$event instanceof NotificationEvent) {
			return;
		}
		
		$object = $event->getObject();
		$ignored_access_ids = [
			ACCESS_PRIVATE,
			ACCESS_FRIENDS,
			ACCESS_LOGGED_IN,
			ACCESS_PUBLIC,
		];
		if (!$object instanceof \ElggEntity || in_array($object->access_id, $ignored_access_ids)) {
			return;
		}
		
		$acl = get_access_collection($object->access_id);
		if ($acl === false) {
			// not an ACL
			return;
		}
		
		$acl_members = get_members_of_access_collection($object->access_id, true);
		if (empty($acl_members)) {
			// acl has no members, so remove everybody
			return [];
		}
		
		$guids_to_remove = array_diff(array_keys($result), $acl_members);
		if (empty($guids_to_remove)) {
			// nothing to cleanup
			return;
		}
		
		foreach ($guids_to_remove as $guid) {
			unset($result[$guid]);
		}
		
		return $result;
	}
}
