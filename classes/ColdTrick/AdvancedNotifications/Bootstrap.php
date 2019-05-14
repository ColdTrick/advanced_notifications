<?php

namespace ColdTrick\AdvancedNotifications;

use Elgg\DefaultPluginBootstrap;
use Elgg\Di\ServiceProvider;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function boot() {
		
		$plugin = $this->plugin();
		if (!$plugin->getSetting('queue_delay')) {
			return;
		}
		
		$sp = _elgg_services();
		$sp->setFactory('notifications', function(ServiceProvider $sp) {
			$queue_name = \Elgg\Notifications\NotificationsService::QUEUE_NAME;
			$queue = new \ColdTrick\AdvancedNotifications\NotificationQueue($queue_name, $sp->db);
			$sub = new \Elgg\Notifications\SubscriptionsService($sp->db);
			return new \Elgg\Notifications\NotificationsService($sub, $queue, $sp->hooks, $sp->session, $sp->translator, $sp->entityTable, $sp->logger);
		});
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		
		// register plugin hooks
		$hooks = $this->elgg()->hooks;
		$hooks->registerHandler('enqueue', 'notification', __NAMESPACE__ . '\Enqueue::preventPrivateNotifications', 9000);
		$hooks->registerHandler('enqueue', 'notification', __NAMESPACE__ . '\Enqueue::delayPrivateContentNotification', 9900);
		
		$hooks->registerHandler('get', 'subscriptions', __NAMESPACE__ . '\Subscriptions::addOwnerSubscribers');
		$hooks->registerHandler('get', 'subscriptions', __NAMESPACE__ . '\Subscriptions::checkAccessCollectionMembership', 9000);
		
		$hooks->registerHandler('setting', 'plugin', __NAMESPACE__ . '\PluginSettings::setPluginSetting');
		
		// register event handlers
		$events = $this->elgg()->events;
		$events->registerHandler('update:after', 'object', __NAMESPACE__ . '\Enqueue::checkForDelayedNotification');
	}
}
