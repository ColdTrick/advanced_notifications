<?php

namespace ColdTrick\AdvancedNotifications;

class PluginSettings {
	
	/**
	 * Change the value of a plugin setting
	 *
	 * @param string $hook         the name of the hook
	 * @param string $type         the type of the hook
	 * @param mixed  $return_value current return value
	 * @param array  $params       supplied params
	 *
	 * @return void|string
	 */
	public static function setPluginSetting($hook, $type, $return_value, $params) {
		
		if (!is_array($return_value)) {
			return;
		}
		
		if (elgg_extract('plugin_id', $params) !== 'advanced_notifications') {
			return;
		}
		
		return json_encode($return_value);
	}
}
