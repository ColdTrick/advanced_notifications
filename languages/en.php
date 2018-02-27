<?php

return [
	'advanced_notifications:settings:queue_delay' => 'Notification queue delay (seconds)',
	'advanced_notifications:settings:queue_delay:help' => 'Items will be picked up out of the queue after the given delay',
	
	'advanced_notifications:settings:notify_owner_subscribers' => 'Notify the subscribers of an owner about new content',
	'advanced_notifications:settings:notify_owner_subscribers:help' => "By default Elgg notifies the subscribers of a container (eg. group or user)
of new content. This means that if you whish to be notified about new content by a user and they created the new content in a group, you won't get
notified (unless you whish to receive the group notifications). With this setting enabled the subscribers will receive a notification.",
];
