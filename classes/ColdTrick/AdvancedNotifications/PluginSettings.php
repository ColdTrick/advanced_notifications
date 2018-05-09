<?php

namespace ColdTrick\AdvancedNotifications;

class PluginSettings {
	
	/**
	 * Change the value of a plugin setting
	 *
	 * @param \Elgg\Hook $hook 'setting', 'plugin'
	 *
	 * @return void|string
	 */
	public static function setPluginSetting(\Elgg\Hook $hook) {
		
		$return_value = $hook->getValue();
		if (!is_array($return_value)) {
			return;
		}
		
		if ($hook->getParam('plugin_id') !== 'advanced_notifications') {
			return;
		}
		
		return json_encode($return_value);
	}
}
