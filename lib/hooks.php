<?php

	function advanced_notifications_route_hook($hooks, $type, $return_value, $params){
		$page = $return_value["segments"];
	
		if(!empty($page)){
				
			switch($page[0]) {
				case "notifications":
				case "groups":
					if(elgg_is_logged_in()){
						elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());
						set_input("page_type", $page[0]);
	
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
					"text" => elgg_echo("advanced_notifications:activity:groups"),
					"href" => "activity/groups",
					"name" => "groups",
					"contexts" => array("activity"),
					"priority" => 900,
					"title" => elgg_echo("advanced_notifications:activity:groups:info")
				));
			}
				
			if(elgg_is_active_plugin("messages")){
				$result[] = ElggMenuItem::factory(array(
					"text" => elgg_echo("advanced_notifications:activity:notifications"),
					"href" => "activity/notifications",
					"name" => "notifications",
					"contexts" => array("activity"),
					"priority" => 910,
					"title" => elgg_echo("advanced_notifications:activity:notifications:info")
				));
			}
		}
	
		return $result;
	}
	
	/**
	 * Return a new subject for group discussion replies
	 *
	 * @param string $hooks
	 * @param string $type
	 * @param string $return_value
	 * @param array $params
	 * @return string
	 */
	function advanced_notifications_discussion_reply_subject_hook($hooks, $type, $return_value, $params) {
		
		return elgg_echo("advanced_notifications:discussion:reply:subject");
	}