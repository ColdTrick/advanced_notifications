<?php

/* @var $plugin ElggPlugin */
$plugin = elgg_extract('entity', $vars);

echo elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('advanced_notifications:settings:queue_delay'),
	'#help' => elgg_echo('advanced_notifications:settings:queue_delay:help'),
	'name' => 'params[queue_delay]',
	'value' => $plugin->queue_delay,
	'min' => 0,
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('advanced_notifications:settings:notify_owner_subscribers'),
	'#help' => elgg_echo('advanced_notifications:settings:notify_owner_subscribers:help'),
	'name' => 'params[notify_owner_subscribers]',
	'value' => 1,
	'checked' => (bool) $plugin->notify_owner_subscribers,
]);
