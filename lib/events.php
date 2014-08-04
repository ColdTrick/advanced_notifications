<?php
/**
 * All event handlers are bundled here
 */

/**
 * Handles the sending of notifications when a new (registered) object is created
 *
 * @param string     $event  the name of the event
 * @param string     $type   the type of the event
 * @param ElggObject $object supplied ElggObject
 *
 * @return void
 */
function advanced_notifications_create_object_event_handler($event, $type, $object) {
	
	if (!empty($object) && elgg_instanceof($object, "object")) {
		// trigger a hook so other plugins can takeover notification handling
		$hookresult = elgg_trigger_plugin_hook("object:notifications", $type, array(
					"event" => $event,
					"object_type" => $type,
					"object" => $object
		), false);
		// it the hook returns true it's assumed the notifications are sent
		if ($hookresult === true) {
			return true;
		}
		
		// if the object is PRIVATE no notification will be sent
		if ($object->access_id != ACCESS_PRIVATE) {
			// are notifications allowed for this object -> type/subtype
			if (advanced_notifications_is_registered_notification_entity($object)) {
				$commandline_options = array(
					"event" => $event,
					"type" => $type,
					"guid" => $object->getGUID()
				);
				
				advanced_notifications_start_commandline($commandline_options);
			}
		}
	}
}

/**
 * Handles the sending of notifications when replied on group forum topic
 *
 * @param string         $event      the name of the event
 * @param string         $type       the type of the event
 * @param ElggAnnotation $annotation the supplied ElggAnnotation
 *
 * @return void
 */
function advanced_notifications_create_annotation_event_handler($event, $type, $annotation) {
	
	if (!empty($annotation) && ($annotation instanceof ElggAnnotation)) {
		// is this an annotation on which notifications should be sent
		if (advanced_notifications_is_registered_notification_annotation($annotation)) {
			// check if the entity isn't PRIVATE
			$entity = $annotation->getEntity();
			if (!empty($entity) && ($entity->access_id != ACCESS_PRIVATE)) {
				// is the entity a registered notification entity
				if (advanced_notifications_is_registered_notification_entity($entity)) {
					// prepare to call the commandline
					$commandline_options = array(
						"event" => $event,
						"type" => $type,
						"id" => $annotation->id
					);
					
					// in older versions of elgg do this differently
					if (version_compare(get_version(true), "1.8.6", "<")) {
						$entity = $annotation->getEntity();
						
						unset($commandline_options["id"]);
						$commandline_options["type"] = $entity->getType();
						$commandline_options["guid"] = $entity->getGUID();
						
						$commandline_options["input"] = "group_topic_post|" . base64_encode($annotation->value);
					}
					
					advanced_notifications_start_commandline($commandline_options);
				}
			}
		}
	}
}
