<?php
/**
 * All helper functions can be found here
 */

/**
 * Checks if the given $entity is registered for notifications by
 * register_notification_object()
 *
 * @param ElggEntity $entity  the entity to check
 * @param bool       $subject return the subject string (default=false)
 *
 * @return bool|string
 */
function advanced_notifications_is_registered_notification_entity(ElggEntity $entity, $subject = false) {
	$result = false;
	
	if (!empty($entity) && ($entity instanceof ElggEntity)) {
		$type = $entity->getType();
		if (empty($type)) {
			$type = "__BLANK__";
		}
		
		$subtype = $entity->getSubtype();
		if ((empty($subtype))) {
			$subtype = "__BLANK__";
		}
		
		// get the registered entity -> type/subtype
		$notifications = elgg_get_config("register_objects");
			
		if (!empty($notifications) && is_array($notifications)) {
			if (isset($notifications[$type]) && isset($notifications[$type][$subtype])) {
				if ($subject) {
					$result = $notifications[$type][$subtype];
				} else {
					$result = true;
				}
			}
		}
	}
	
	return $result;
}

/**
 * Checks if the given $annotaions is registered for notifications
 *
 * @param ElggAnnotation $annotation the annotation to check
 *
 * @return bool
 */
function advanced_notifications_is_registered_notification_annotation(ElggAnnotation $annotation) {
	$result = false;
	
	if (!empty($annotation) && ($annotation instanceof ElggAnnotation)) {
		$supported_annotations = array(
			"group_topic_post"
		);
		
		if (in_array($annotation->name, $supported_annotations)) {
			$result = true;
		}
	}
	
	return $result;
}

/**
 * Start a new commandline php process to sent out the notifications
 *
 * @param array $options the options to start to commandline
 *
 * @return void
 */
function advanced_notifications_start_commandline($options = array()) {
	// set some default options for the commandline
	$defaults = array(
		"secret" => advanced_notifications_generate_secret(),
		"host" => $_SERVER["HTTP_HOST"],
		"memory_limit" => ini_get("memory_limit"),
		"session_id" => session_id()
	);
	
	// is this a secure site
	if (isset($_SERVER["HTTPS"])) {
		$defaults["https"] = $_SERVER["HTTPS"];
	}
	
	// make sure $options is an array
	if (!empty($options) && !is_array($options)) {
		$options = array($options);
	}
	
	// merge defaults with supplied options
	$options = array_merge($defaults, $options);
	
	// prepare to call the commandline
	$script_location = dirname(dirname(__FILE__)) . "/procedures/cli.php";
	$query_string = http_build_query($options, "", " ");
	
	if (PHP_OS === "WINNT") {
		pclose(popen("start /B php " . $script_location . " " . $query_string, "r"));
	} else {
		exec("php " . $script_location . " " . $query_string . " > /dev/null &");
	}
}

/**
 * Generate a secret to be used in calling the commandline
 *
 * @return string
 */
function advanced_notifications_generate_secret() {
	static $result;
	
	if (!isset($result)) {
		$site_secret = get_site_secret();
		$plugin = elgg_get_plugin_from_id("advanced_notifications");
			
		$result = md5($plugin->getGUID() . $site_secret . $plugin->time_created);
	}
	
	return $result;
}

/**
 * Validate the secret provided to the commandline
 *
 * @param string $secret the string to validate
 *
 * @return bool
 */
function advanced_notifications_validate_secret($secret) {
	$result = false;
	
	if (!empty($secret)) {
		$correct_secret = advanced_notifications_generate_secret();
		if (!empty($correct_secret)) {
			if ($secret === $correct_secret) {
				$result = true;
			}
		}
	}
	
	return $result;
}

/**
 * Sent out the notifications for the provided entity_guid
 *
 * @param int    $guid  the guid of the user to notify
 * @param string $event notify about which event
 *
 * @return void
 */
function advanced_notifications_entity_notification($guid, $event) {
	global $NOTIFICATION_HANDLERS;
	
	// get the entity to notify
	$entity = get_entity($guid);
	if (!empty($entity)) {
		// check if the entity isn't private, this shouldn't happen as the commandline should have prevented this
		if ($entity->access_id != ACCESS_PRIVATE) {
			
			// this is new as of Elgg 1.8.14
			if ($event == "publish") {
				// make sure some objects are registered as notification object
				advanced_notifications_fix_notification_entities();
			}
			
			// check if this is a notifiable entity type/subtype, this also shouldn't happen see above
			$default_subject = advanced_notifications_is_registered_notification_entity($entity, true);
			if (!empty($default_subject)) {
				// let's prepare for sending
				$default_message = $default_subject . ": " . $entity->getURL();
				
				// check if we need to disable site notifications
				if (elgg_get_plugin_setting("replace_site_notifications", "advanced_notifications") == "yes") {
					unregister_notification_handler("site");
				}
				
				if (!empty($NOTIFICATION_HANDLERS) && is_array($NOTIFICATION_HANDLERS)) {
					// this could take a long time, especialy with large groups
					set_time_limit(0);
					
					// prepare the options to get the interested users
					$options = array(
						"type" => "user",
						"site_guids" => ELGG_ENTITIES_ANY_VALUE,
						"limit" => false,
						"joins" => array("JOIN " . elgg_get_config("dbprefix") . "users_entity ue ON e.guid = ue.guid"),
						"wheres" => array("(ue.banned = 'no')"), // banned users don't need to be notified
						"relationship_guid" => $entity->getContainerGUID(),
						"inverse_relationship" => true,
						"callback" => "advanced_notifications_row_to_guid"
					);
					
					// if we have a logged in user, don't notify him/her
					$logged_in_user_guid = elgg_get_logged_in_user_guid();
					if (!emtpy($logged_in_user_guid)) {
						$options["wheres"][] = "(e.guid <> " . $logged_in_user_guid . ")";
					}
					
					// process the different notification handlers
					foreach ($NOTIFICATION_HANDLERS as $method => $dummy) {
						// get the interested users for the entity
						$options["relationship"] = "notify" . $method;
						
						// allow the interested user options to be ajusted
						$params = array(
							"entity" => $entity,
							"options" => $options,
							"method" => $method
						);
						
						$options = elgg_trigger_plugin_hook("interested_users:options", "notify:" . $method, $params, $options);
						if (!empty($options)) {
							// we got through the hook, so get the users
							$user_guids = elgg_get_entities_from_relationship($options);
							if (!empty($user_guids)) {
								// process each user
								foreach ($user_guids as $user_guid) {
									// fetch the user entity to process
									$user = get_user($user_guid);
									if (!empty($user)) {
										// check if the user has access to the entity
										if (has_access_to_entity($entity, $user)) {
											// trigger a hook to make a custom message
											$message = elgg_trigger_plugin_hook("notify:entity:message", $entity->getType(), array(
												"entity" => $entity,
												"to_entity" => $user,
												"method" => $method), $default_message);
											// check if the hook made a correct message
											if (empty($message) && $message !== false) {
												// the hook did it incorrect, so reset the message
												$message = $default_message;
											}
											
											// this is new, trigger a hook to make a custom subject
											$subject = elgg_trigger_plugin_hook("notify:entity:subject", $entity->getType(), array(
												"entity" => $entity,
												"to_entity" => $user,
												"method" => $method), $default_subject);
											// check if the hook made a correct subject
											if (empty($subject)) {
												// the hook did it incorrect, so reset the subject
												$subject = $default_subject;
											}
											
											// if the hook returnd false, don't sent a notification
											if ($message !== false) {
												notify_user($user->getGUID(), $entity->getContainerGUID(), $subject, $message, null, $method);
											}
										}
									}
									
									// cleanup some of the caches
									_elgg_invalidate_query_cache();
									_elgg_invalidate_cache_for_entity($user_guid);
									
									unset($user);
								}
							}
							
							// some small cleanup
							unset($user_guids);
						}
					}
				}
			}
		}
	}
}

/**
 * Sent out the notifications for the provided annotation_id
 *
 * @param int    $id    the id of the annotation
 * @param string $event the type of event
 *
 * @return void
 */
function advanced_notifications_annotation_notification($id, $event) {
	global $NOTIFICATION_HANDLERS;
	
	// get the annotation
	$annotation = elgg_get_annotation_from_id($id);
	if (!empty($annotation)) {
		// are notifications on this annotation allowed
		if (advanced_notifications_is_registered_notification_annotation($annotation)) {
			// get the entity the annotation was made on
			$entity = $annotation->getEntity();
			
			// get the owner of the annotation
			$owner = $annotation->getOwnerEntity();
			
			if (!empty($entity) && !empty($owner)) {
				// make sure the entity isn't a PRIVATE entity, this shouldn't happed as the commandline shouldn't be called
				if ($entity->access_id != ACCESS_PRIVATE) {
					// is the entity a registered entity type/subtype, this shouldn't happen see above
					$default_subject = advanced_notifications_is_registered_notification_entity($entity, true);
					if (!empty($default_subject)) {
						// prepare the message to sent
						$default_message = $default_subject . ": " . $entity->getURL();
						
						// check if we need to disable site notifications
						if (elgg_get_plugin_setting("replace_site_notifications", "advanced_notifications") == "yes") {
							unregister_notification_handler("site");
						}
						
						if (!empty($NOTIFICATION_HANDLERS) && is_array($NOTIFICATION_HANDLERS)) {
							// this could take a long time, especialy with large groups
							set_time_limit(0);
							
							// prepare options to get the interested users
							$options = array(
								"type" => "user",
								"site_guids" => ELGG_ENTITIES_ANY_VALUE,
								"limit" => false,
								"joins" => array("JOIN " . elgg_get_config("dbprefix") . "users_entity ue ON e.guid = ue.guid"),
								"wheres" => array(
									"(ue.banned = 'no')", // banned users don't need to be notified
									"(e.guid <> " . $owner->getGUID() . ")"
								),
								"relationship_guid" => $entity->getContainerGUID(),
								"inverse_relationship" => true,
								"callback" => "advanced_notifications_row_to_guid"
							);
							
							foreach ($NOTIFICATION_HANDLERS as $method => $dummy) {
								// get the interested users for the entity
								$options["relationship"] = "notify" . $method;
								
								// allow the interested user options to be ajusted
								$params = array(
									"annotation" => $annotation,
									"entity" => $entity,
									"options" => $options,
									"method" => $method
								);
								
								$options = elgg_trigger_plugin_hook("interested_users:options", "notify:" . $method, $params, $options);
								if (!empty($options)) {
									// we got through the hook, so get the users
									$user_guids = elgg_get_entities_from_relationship($options);
									if (!empty($user_guids)) {
										// process each user
										foreach ($user_guids as $user_guid) {
											// fetch the user entity to process
											$user = get_user($user_guid);
											if (!empty($user)) {
												// check if the user has access to the entity
												if (has_access_to_entity($entity, $user)) {
													// trigger a hook to make a custom message
													$message = elgg_trigger_plugin_hook("notify:annotation:message", $annotation->getSubtype(), array(
														"annotation" => $annotation,
														"to_entity" => $user,
														"method" => $method), $default_message);
													// check if the hook made a correct message
													if (empty($message) && $message !== false) {
														// the hook did it incorrect, so reset the message
														$message = $default_message;
													}
														
													// this is new, trigger a hook to make a custom subject
													$subject = elgg_trigger_plugin_hook("notify:annotation:subject", $annotation->getSubtype(), array(
														"annotation" => $annotation,
														"to_entity" => $user,
														"method" => $method), $default_subject);
													// check if the hook made a correct subject
													if (empty($subject)) {
														// the hook did it incorrect, so reset the subject
														$subject = $default_subject;
													}
														
													// if the hook returnd false, don't sent a notification
													if ($message !== false) {
														notify_user($user->getGUID(), $entity->getContainerGUID(), $subject, $message, null, $method);
													}
												}
											}
												
											// cleanup some of the caches
											_elgg_invalidate_query_cache();
											_elgg_invalidate_cache_for_entity($user_guid);
												
											unset($user);
										}
									}
										
									// some small cleanup
									unset($user_guids);
								}
							}
						}
					}
				}
			}
		}
	}
}

/**
 * Returns a Elgg datarow as GUID
 *
 * @param stdClass $row the database row
 *
 * @return int
 */
function advanced_notifications_row_to_guid($row) {
	return (int) $row->guid;
}

/**
 * Unregister an entity type/subtype from notifications handling
 *
 * @param string $type    the type to unregister
 * @param string $subtype the subtype to unregister
 *
 * @return void
 */
function advanced_notifications_unregister_notification_object($type = "", $subtype = "") {
	global $CONFIG;
	
	if (empty($type)) {
		$type = "__BLANK__";
	}
	
	if (empty($subtype)) {
		$subtype = "__BLANK__";
	}
	
	// are there registered notification objects
	if (isset($CONFIG->register_objects)) {
		if (isset($CONFIG->register_objects[$type])) {
			if (isset($CONFIG->register_objects[$type][$subtype])) {
				// remove the registered entity type/subtype
				unset($CONFIG->register_objects[$type][$subtype]);
			}
		}
	}
}

/**
 * register some entity types for notification
 *
 * @return void
 */
function advanced_notifications_fix_notification_entities() {
	// fix blog
	register_notification_object("object", "blog", elgg_echo("blog:newpost"));
}

/**
 * Check a plugin setting to disable content in the message body
 *
 * @return bool
 */
function advanced_notifications_no_mail_content() {
	static $result;
	
	if (!isset($result)) {
		$result = false;
		
		if (elgg_get_plugin_setting("no_mail_content", "advanced_notifications") == "yes") {
			$result = true;
		}
	}
	
	return $result;
}

/**
 * Check a plugin setting if the site notifications should be taken over
 *
 * @return bool
 */
function advanced_notifications_replace_site_notifications() {
	static $result;
	
	if (!isset($result)) {
		$result = false;
			
		if (elgg_get_plugin_setting("replace_site_notifications", "advanced_notifications") == "yes") {
			$result = true;
		}
	}
	
	return $result;
}
