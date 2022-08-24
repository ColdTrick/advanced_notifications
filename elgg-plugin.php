<?php

return [
	'plugin' => [
		'version' => '6.0',
	],
	'events' => [
		'update:after' => [
			'object' => [
				'\ColdTrick\AdvancedNotifications\Enqueue::checkForDelayedNotification' => [],
			],
		],
	],
	'hooks' => [
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
	],
];
