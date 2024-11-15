<?php

namespace ColdTrick\AdvancedNotifications\Plugins;

/**
 * Changes for the Reported Content plugin
 */
class ReportedContent {
	
	/**
	 * Allow Reported Content items to be enqueued and not delayed
	 *
	 * @param \Elgg\Event $event 'delayed_actions', 'advanced_notifications'
	 *
	 * @return null|array
	 */
	public static function allowEnqueue(\Elgg\Event $event): ?array {
		$entity = $event->getObject();
		
		return $entity instanceof \ElggReportedContent ? [] : null;
	}
}
