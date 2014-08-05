<?php
/**
 * All plugin hooks are bundled here
 */

/**
 * listen to the /activity page handler
 *
 * @param string $hooks        the name of the hook
 * @param string $type         the type of the hook
 * @param array  $return_value the current return value
 * @param array  $params       supplied params
 *
 * @return bool|void
 */
function advanced_notifications_route_hook($hooks, $type, $return_value, $params) {
	
	if (advanced_notifications_replace_site_notifications()) {
		$page = elgg_extract("segments", $return_value);
	
		if (!empty($page)) {
				
			switch ($page[0]) {
				case "notifications":
				case "groups":
					if (elgg_is_logged_in()) {
						elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());
						set_input("page_type", $page[0]);
	
						require_once(dirname(dirname(__FILE__)) . "/pages/river.php");
						return false;
					} else {
						forward("activity");
					}
					break;
			}
		}
	}
}

/**
 * extend the filter tabs on activity pages
 *
 * @param string         $hooks        the name of the hook
 * @param string         $type         the type of the hook
 * @param ElggMenuItem[] $return_value the current return value
 * @param array          $params       supplied params
 *
 * @return ElggMenuItem[]
 */
function advanced_notifications_filter_menu_hook($hooks, $type, $return_value, $params) {
	$result = $return_value;

	if (advanced_notifications_replace_site_notifications()) {
		if (elgg_is_logged_in() && elgg_in_context("activity")) {
			// add new items
			if (elgg_is_active_plugin("groups")) {
				$result[] = ElggMenuItem::factory(array(
					"text" => elgg_echo("advanced_notifications:activity:groups"),
					"href" => "activity/groups",
					"name" => "groups",
					"contexts" => array("activity"),
					"priority" => 900,
					"title" => elgg_echo("advanced_notifications:activity:groups:info")
				));
			}
				
			if (elgg_is_active_plugin("messages")) {
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
	}

	return $result;
}

/**
 * Return a new subject for group discussion creation
 *
 * @param string $hooks        the name of the hook
 * @param string $type         the type of the hook
 * @param string $return_value the current return value
 * @param array  $params       supplied params
 *
 * @return string
 */
function advanced_notifications_discussion_subject_hook($hooks, $type, $return_value, $params) {
	
	if (empty($params) || !is_array($params)) {
		return $return_value;
	}
	
	$entity = elgg_extract("entity", $params);
	if (empty($entity) || !elgg_instanceof($entity, "object", "groupforumtopic")) {
		return $return_value;
	}
	
	return elgg_echo("advanced_notifications:discussion:create:subject", array($entity->title));
}

/**
 * Return a new subject for group discussion replies
 *
 * @param string $hooks        the name of the hook
 * @param string $type         the type of the hook
 * @param string $return_value the current return value
 * @param array  $params       supplied params
 *
 * @return string
 */
function advanced_notifications_discussion_reply_subject_hook($hooks, $type, $return_value, $params) {
	
	if (empty($params) || !is_array($params)) {
		return $return_value;
	}
	
	$annotation = elgg_extract("annotation", $params);
	if (empty($annotation) || !($annotation instanceof ElggAnnotation)) {
		return $return_value;
	}
	
	$entity = $annotation->getEntity();
	if (empty($entity) || !elgg_instanceof($entity, "object", "groupforumtopic")) {
		return $return_value;
	}
	
	return elgg_echo("advanced_notifications:discussion:reply:subject", array($entity->title));
}

/**
 * Replace the message body of the notification with an url to the content (default behaviour)
 *
 * @param string $hooks        the name of the hook
 * @param string $type         the type of the hook
 * @param string $return_value the current return value
 * @param array  $params       supplied params
 *
 * @return string
 */
function advanced_notifications_email_body_hook($hooks, $type, $return_value, $params) {
	$result = $return_value;
	
	if (empty($params) || !is_array($params)) {
		return $result;
	}
	
	// thewire part
	$entity = elgg_extract("entity", $params);
	
	if (elgg_instanceof($entity, "object", "thewire")) {
		$descr = $entity->description;
		$owner = $entity->getOwnerEntity();
		
		if ($entity->reply) {
			$parents = $entity->getEntitiesFromRelationship("parent", false, 1);
			
			if ($parents) {
				$parent_owner = $parents[0]->getOwnerEntity();
			}
			$result = elgg_echo("thewire:notify:reply", array($owner->name, $parent_owner->name));
		} else {
			$result = elgg_echo("thewire:notify:post", array($owner->name));
		}
		
		$result .= "\n\n" . $descr . "\n\n";
		$result .= elgg_echo("thewire") . ": " . elgg_get_site_url() . "thewire";
	}
	// end: thewire
	
	// replace body by a link to the entity?
	// check if we need to replace the message
	if (!advanced_notifications_no_mail_content()) {
		return $result;
	}
	
	// only replace email body
	$method = elgg_extract("method", $params);
	if (empty($method) || ($method != "email")) {
		return $result;
	}
	
	// get the entity or annotation for the body
	$annotation = elgg_extract("annotation", $params);
		
	if (empty($entity) && empty($annotation)) {
		return $result;
	}
			
	if (empty($entity) && !empty($annotation)) {
		// we have a comment on a discussion
		$entity = $annotation->getEntity();
	}
		
	// return the url to the entity
	$result = elgg_echo("advanced_notifications:notification:email:body", array($entity->getURL()));
	
	return $result;
}

/**
 * In case we choose to replace the email body content, overrule the default action
 *
 * @param string $hooks        the name of the hook
 * @param string $type         the type of the hook
 * @param bool   $return_value the current return value
 * @param array  $params       supplied params
 *
 * @return boolean
 */
function advanced_notifications_comment_action_hook($hooks, $type, $return_value, $params) {
	$result = $return_value;
	
	if (advanced_notifications_no_mail_content()) {
		// we'll take over because no mail content may leave the site
		$result = false;
		
		include(dirname(dirname(__FILE__)) . "/actions/comments/add.php");
	}
	
	return $result;
}
