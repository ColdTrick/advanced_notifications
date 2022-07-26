<?php

namespace ColdTrick\AdvancedNotifications;

use Elgg\Notifications\NotificationEvent;

class Subscriptions {
	
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
		
		// allow other plugins to skip validating acl access
		$allow_acl_validation = (bool) elgg_trigger_plugin_hook('validate:acl_membership', 'advanced_notifications', $hook->getParams(), true);
		if (!$allow_acl_validation) {
			// a plugin prevented acl membership validation
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
		
		$action = $event->getAction();
		if ($action === 'invite' && $object instanceof \ElggGroup) {
			// do not cleanup private(invisible) group invitation
			return;
		}
		
		$acl = elgg_get_access_collection($object->access_id);
		if (!$acl instanceof \ElggAccessCollection) {
			// not an ACL
			return;
		}
		
		$acl_members = $acl->getMembers([
			'callback' => function($row) {
				return (int) $row->guid;
			},
		]);
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
