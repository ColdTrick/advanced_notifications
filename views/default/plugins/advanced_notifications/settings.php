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

$owner = elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('advanced_notifications:settings:notify_owner_subscribers'),
	'#help' => elgg_echo('advanced_notifications:settings:notify_owner_subscribers:help'),
	'name' => 'params[notify_owner_subscribers]',
	'value' => 1,
	'checked' => (bool) $plugin->notify_owner_subscribers,
]);

$notification_events = advanced_notifications_get_notification_events();
if (!empty($notification_events)) {
	$owner_subscriptions = advanced_notifications_get_owner_subscription_settings();
	$allowed_types = elgg_get_config('entity_types');
	
	$header = [
		elgg_format_element('th', [], elgg_echo('table_columns:fromProperty:type')),
	];
	
	$header = elgg_format_element('tr', [], implode(PHP_EOL, $header));
	$header = elgg_format_element('thead', [], $header);
	
	$rows = [];
	foreach ($notification_events as $type => $subtypes) {
		if (!is_array($subtypes) || !in_array($type, $allowed_types)) {
			continue;
		}
		
		foreach ($subtypes as $subtype => $actions) {
			$row = [];
			
			$lan_key = rtrim(implode(':', ['item', $type, $subtype]), ':');
			if (elgg_language_key_exists($lan_key)) {
				$label = elgg_echo($lan_key);
			} else {
				$label = rtrim(implode(':', [$type, $subtype]), ':');
			}
			
			$row[] = elgg_format_element('td', [], elgg_view_field([
				'#type' => 'checkbox',
				'#label' => $label,
				'#class' => 'mbn',
				'name' => !empty($subtype) ? "params[owner_subscription][{$type}][{$subtype}]" : "params[owner_subscription][{$type}]",
				'default' => 0,
				'value' => 1,
				'checked' => isset($owner_subscriptions[$type][$subtype]),
			]));
			
			$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
		}
	}
	
	$body = elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
	
	$owner .= elgg_view('output/longtext', [
		'value' => elgg_echo('advanced_notifications:settings:owner_subscriptions:description'),
	]);
	
	$owner .= elgg_format_element('table', ['class' => 'elgg-table-alt'], $header . $body);
}

echo elgg_view_module('inline', elgg_echo('advanced_notifications:settings:owner'), $owner);
