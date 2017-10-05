<?php

$plugin = elgg_extract('entity', $vars);

echo elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('advanced_notifications:settings:queue_delay'),
	'#help' => elgg_echo('advanced_notifications:settings:queue_delay:help'),
	'name' => 'params[queue_delay]',
	'value' => $plugin->queue_delay,
	'min' => 0,
]);
