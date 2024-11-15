<?php

return [
	'plugin' => [
		'version' => '7.0.2',
	],
	'events' => [
		'delayed_actions' => [
			'advanced_notifications' => [
				'\ColdTrick\AdvancedNotifications\Plugins\ReportedContent::allowEnqueue' => [],
			],
		],
		'enqueue' => [
			'notification' => [
				'\ColdTrick\AdvancedNotifications\Enqueue::delayPrivateContentNotification' => ['priority' => 9900],
			],
		],
		'get' => [
			'subscriptions' => [
				'\ColdTrick\AdvancedNotifications\Subscriptions::checkAccessCollectionMembership' => ['priority' => 9900],
			],
		],
		'update:after' => [
			'object' => [
				'\ColdTrick\AdvancedNotifications\Enqueue::checkForDelayedNotification' => [],
			],
		],
	],
];
