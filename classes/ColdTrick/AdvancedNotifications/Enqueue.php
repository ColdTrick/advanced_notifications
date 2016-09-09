<?php

namespace ColdTrick\AdvancedNotifications;

class Enqueue {
	
	/**
	 * Prevent the enqueing of a notification event for private content
	 *
	 * @param string $hook         the name of the hook
	 * @param string $type         the type of the hook
	 * @param bool   $return_value current return value
	 * @param array  $params       supplied params
	 *
	 * @return void|false
	 */
	public static function preventPrivateNotifications($hook, $type, $return_value, $params) {
		
		if ($return_value !== true) {
			// already prevented
			return;
		}
		
		if (!is_array($params)) {
			return;
		}
		
		$object = elgg_extract('object', $params);
		if (!($object instanceof \ElggEntity) && !($object instanceof \ElggExtender)) {
			return;
		}
		
		$access_id = (int) $object->access_id;
		if ($access_id !== ACCESS_PRIVATE) {
			return;
		}
		
		return false;
	}
}
