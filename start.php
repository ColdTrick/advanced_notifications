<?php

	require_once(dirname(__FILE__) . "/lib/functions.php");
	require_once(dirname(__FILE__) . "/lib/events.php");
	
	elgg_register_event_handler("init", "system", "advanced_notifications_init");
	
	function advanced_notifications_init(){
		// default object notifications
		elgg_register_event_handler("create", "object", "advanced_notifications_create_object_event_handler");
		elgg_unregister_event_handler("create", "object", "object_notifications");
		
		// group forum topic notifications
		elgg_register_event_handler("create", "annotation", "advanced_notifications_create_annotation_event_handler");
		elgg_unregister_event_handler("create", "annotation", "discussion_reply_notifications"); // for Elgg versions >= 1.8.6
		elgg_unregister_event_handler("annotate", "all", "group_object_notifications"); // older versions of Elgg < 1.8.6
	}