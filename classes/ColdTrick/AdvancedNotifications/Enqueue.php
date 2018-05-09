<?php

namespace ColdTrick\AdvancedNotifications;

class Enqueue {
	
	/**
	 * Prevent the enqueing of a notification event for private content
	 *
	 * @param \Elgg\Hook $hook 'enqueue', 'notification'
	 *
	 * @return void|false
	 */
	public static function preventPrivateNotifications(\Elgg\Hook $hook) {
		
		if ($hook->getValue() !== true) {
			// already prevented
			return;
		}
		
		$object = $hook->getParam('object');
		if (!$object instanceof \ElggEntity && !$object instanceof \ElggExtender) {
			return;
		}
		
		$access_id = (int) $object->access_id;
		if ($access_id !== ACCESS_PRIVATE) {
			return;
		}
		
		return false;
	}
	
	/**
	 * Delay the notification on content if it's private
	 *
	 * Mainly the create notifications
	 *
	 * @param \Elgg\Hook $hook 'enqueue', 'notification'
	 *
	 * @return void|false
	 */
	public static function delayPrivateContentNotification(\Elgg\Hook $hook) {
		
		$object = $hook->getParam('object');
		if (!$object instanceof \ElggObject) {
			return;
		}
		
		$access_id = (int) $object->access_id;
		if ($access_id !== ACCESS_PRIVATE) {
			return;
		}
		
		$action = $hook->getParam('action');
		if (!self::isSupportedDelayAction($action, $object)) {
			return;
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
	public static function checkForDelayedNotification(\Elgg\Event $event) {
		
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
		$notification_service->enqueueEvent($action, $object->getType(), $object);
		
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
	protected static function isSupportedDelayAction($action, \ElggObject $object) {
		
		if (empty($action) || !$object instanceof \ElggObject) {
			return false;
		}
		
		$supported_actions = [
			'create',
		];
		$params = [
			'action' => $action,
			'object' => $object,
		];
		$supported_actions = elgg_trigger_plugin_hook('delayed_actions', 'advanced_notifications', $params, $supported_actions);
		if (!is_array($supported_actions)) {
			return false;
		}
		
		return in_array($action, $supported_actions);
	}
}
