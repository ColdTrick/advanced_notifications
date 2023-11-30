<?php

return [
	'plugin' => [
		'version' => '7.0.1',
	],
	'events' => [
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
