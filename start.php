<?php

	require_once(dirname(__FILE__) . "/lib/events.php");
	require_once(dirname(__FILE__) . "/lib/functions.php");
	require_once(dirname(__FILE__) . "/lib/hooks.php");
	
	elgg_register_event_handler("init", "system", "advanced_notifications_init");
	
	function advanced_notifications_init(){
		// default object notifications
		elgg_register_event_handler("create", "object", "advanced_notifications_create_object_event_handler");
		elgg_register_event_handler("publish", "object", "advanced_notifications_create_object_event_handler");
		elgg_unregister_event_handler("create", "object", "object_notifications");
		elgg_unregister_event_handler("publish", "object", "object_notifications");
		
		// group forum topic notifications
		elgg_register_event_handler("create", "annotation", "advanced_notifications_create_annotation_event_handler");
		elgg_unregister_event_handler("create", "annotation", "discussion_reply_notifications"); // for Elgg versions >= 1.8.6
		elgg_unregister_event_handler("annotate", "all", "group_object_notifications"); // older versions of Elgg < 1.8.6
		
		elgg_register_plugin_hook_handler("notify:annotation:subject", "group_topic_post", "advanced_notifications_discussion_reply_subject_hook");
		
		// unregister some stuff from messages
		elgg_unregister_plugin_hook_handler("notify:entity:message", "object", "messages_notification_msg");
		advanced_notifications_unregister_notification_object("object", "messages");
		
		if (elgg_get_plugin_setting("replace_site_notifications", "advanced_notifications") == "yes") {
			elgg_register_plugin_hook_handler("register", "menu:filter", "advanced_notifications_filter_menu_hook");
	
			elgg_register_plugin_hook_handler("route", "activity", "advanced_notifications_route_hook");
		}
	}
	