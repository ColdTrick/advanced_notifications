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
		
		// unregister some stuff from messages
		elgg_unregister_plugin_hook_handler("notify:entity:message", "object", "messages_notification_msg");
		advanced_notifications_unregister_notification_object("object", "messages");
		
		if (elgg_get_plugin_setting("replace_site_notifications", "advanced_notifications") == "yes") {
			elgg_register_plugin_hook_handler("register", "menu:filter", "advanced_notifications_filter_menu_hook");
	
			elgg_register_plugin_hook_handler("route", "activity", "advanced_notifications_route_hook");
		}		
	}
	
	function advanced_notifications_route_hook($hooks, $type, $return_value, $params){
		$page = $return_value["segments"];
		
		if(!empty($page)){
			
			switch($page[0]) {
				case "notifications":
				case "groups":
					if(elgg_is_logged_in()){
						elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());
						set_input('page_type', $page[0]);
						
						require_once(elgg_get_plugins_path() . "advanced_notifications/pages/river.php");
						return false;
					} else {
						forward("activity");
					}
					
			}
		}		
	}
	
	function advanced_notifications_filter_menu_hook($hooks, $type, $return_value, $params){
		$result = $return_value;
		
		if(elgg_is_logged_in() && elgg_in_context("activity")){
			// add new items
			if(elgg_is_active_plugin("groups")){
				$result[] = ElggMenuItem::factory(array(
						'text' => elgg_echo('advanced_notifications:activity:groups'),
						'href' => 'activity/groups',
						'name' => 'groups',
						'contexts' => array("activity"),
						'priority' => 900,
						'title' => elgg_echo('advanced_notifications:activity:groups:info')
				));
			}
			
			if(elgg_is_active_plugin("messages")){
				$result[] = ElggMenuItem::factory(array(
						'text' => elgg_echo('advanced_notifications:activity:notifications'),
						'href' => 'activity/notifications',
						'name' => 'notifications',
						'contexts' => array("activity"),
						'priority' => 910,
						'title' => elgg_echo('advanced_notifications:activity:notifications:info')
				));
			}
		}
		
		return $result;
	}