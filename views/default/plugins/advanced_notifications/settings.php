<?php

$plugin = $vars["entity"];

$noyes_options = array(
		"no" => elgg_echo("option:no"),
		"yes" => elgg_echo("option:yes")
);

echo elgg_echo("advanced_notifications:settings:replace_site_notifications");
echo "&nbsp;" . elgg_view("input/dropdown", array("name" => "params[replace_site_notifications]", "options_values" => $noyes_options, "value" => $plugin->replace_site_notifications));


