<?php

namespace ColdTrick\AdvancedNotifications;

use Elgg\Notifications\NotificationEvent;

/**
 * Notification subscription event listener
 */
class Subscriptions {
	
	/**
	 * Validate that subscribers are member of an access collection.
	 *
	 * Not really needed for 'normal' users but for admins this is required, as access isn't validated for them
	 *
	 * @param \Elgg\Event $event 'get', 'subscriptions'
	 *
	 * @return null|array
	 */
	public static function checkAccessCollectionMembership(\Elgg\Event $event): ?array {
		$result = $event->getValue();
		if (empty($result)) {
			// no subscribers to validate
			return null;
		}
		
		$notification_event = $event->getParam('event');
		if (!$notification_event instanceof NotificationEvent) {
			return null;
		}
		
		// allow other plugins to skip validating acl access
		$allow_acl_validation = (bool) elgg_trigger_event_results('validate:acl_membership', 'advanced_notifications', $event->getParams(), true);
		if (!$allow_acl_validation) {
			// a plugin prevented acl membership validation
			return null;
		}
		
		$object = $notification_event->getObject();
		$ignored_access_ids = [
			ACCESS_PRIVATE,
			ACCESS_FRIENDS,
			ACCESS_LOGGED_IN,
			ACCESS_PUBLIC,
		];
		if (!$object instanceof \ElggEntity || in_array($object->access_id, $ignored_access_ids)) {
			return null;
		}
		
		$action = $notification_event->getAction();
		if ($action === 'invite' && $object instanceof \ElggGroup) {
			// do not clean up private(invisible) group invitation
			return null;
		}
		
		$acl = elgg_get_access_collection($object->access_id);
		if (!$acl instanceof \ElggAccessCollection) {
			// not an ACL
			return null;
		}
		
		$acl_members = $acl->getMembers([
			'callback' => function($row) {
				return (int) $row->guid;
			},
			'limit' => false,
		]);
		if (empty($acl_members)) {
			// acl has no members, so remove everybody
			return [];
		}
		
		$guids_to_remove = array_diff(array_keys($result), $acl_members);
		if (empty($guids_to_remove)) {
			// nothing to clean up
			return null;
		}
		
		foreach ($guids_to_remove as $guid) {
			unset($result[$guid]);
		}
		
		return $result;
	}
}
