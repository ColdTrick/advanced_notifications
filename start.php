<?php
/**
 * The main plugin file
 */

// register default Elgg events
elgg_register_event_handler('init', 'system', 'advanced_notifications_init');

/**
 * Gets called during system initialization
 *
 * @return void
 */
function advanced_notifications_init() {

}
