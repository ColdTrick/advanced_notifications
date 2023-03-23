<?php

namespace ColdTrick\AdvancedNotifications;

/**
 * Notification queue event listener
 */
class Enqueue {
	
	/**
	 * Delay the notification of content if it's private
	 *
	 * Mainly the 'create' notifications
	 *
	 * @param \Elgg\Event $event 'enqueue', 'notification'
	 *
	 * @return null|bool
	 */
	public static function delayPrivateContentNotification(\Elgg\Event $event): ?bool {
		$object = $event->getParam('object');
		if (!$object instanceof \ElggObject) {
			return null;
		}
		
		$access_id = (int) $object->access_id;
		if ($access_id !== ACCESS_PRIVATE) {
			return null;
		}
		
		$action = $event->getParam('action');
		if (!self::isSupportedDelayAction($action, $object)) {
			return null;
		}
		
		$object->advanced_notifications_delayed_action = $action;
		return false;
	}
	
	/**
	 * Check if delayed notification for this object is needed
	 *
	 * @param \Elgg\Event $event 'update:after', 'object'
	 *
	 * @return void
	 */
	public static function checkForDelayedNotification(\Elgg\Event $event): void {
		$object = $event->getObject();
		if (!$object instanceof \ElggObject) {
			return;
		}
		
		$access_id = (int) $object->access_id;
		if ($access_id === ACCESS_PRIVATE) {
			return;
		}
		
		if (!isset($object->advanced_notifications_delayed_action)) {
			return;
		}
		
		$action = $object->advanced_notifications_delayed_action;
		if (empty($action)) {
			// ??????
			return;
		}
		
		// enqueue the original notification
		$notification_service = _elgg_services()->notifications;
		$notification_service->enqueueEvent($action, $object);
		
		unset($object->advanced_notifications_delayed_action);
	}
	
	/**
	 * Check if the action is supported for delayed notification
	 *
	 * @param string      $action the action to check (eg. 'create')
	 * @param \ElggObject $object the object to check for (eg. \ElggBlog, \ElggObject)
	 *
	 * @return bool
	 */
	protected static function isSupportedDelayAction(string $action, \ElggObject $object): bool {
		if (empty($action)) {
			return false;
		}
		
		$supported_actions = [
			'create',
		];
		$params = [
			'action' => $action,
			'object' => $object,
		];
		$supported_actions = elgg_trigger_event_results('delayed_actions', 'advanced_notifications', $params, $supported_actions);
		if (!is_array($supported_actions)) {
			return false;
		}
		
		return in_array($action, $supported_actions);
	}
}
