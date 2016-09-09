Advanced Notifications
======================

[![Build Status](https://scrutinizer-ci.com/g/ColdTrick/advanced_notifications/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/advanced_notifications/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ColdTrick/advanced_notifications/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/advanced_notifications/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/coldtrick/advanced_notifications/v/stable.svg)](https://packagist.org/packages/coldtrick/advanced_notifications)
[![License](https://poser.pugx.org/coldtrick/advanced_notifications/license.svg)](https://packagist.org/packages/coldtrick/advanced_notifications)

This plugin changes the way Elgg handles some notifications, it doesn't change the content of the notification or who gets it.

Features
-----------

- Prevent the enqueing of a notifcation if the content is private.  
This will prevent notifications to admins about private content. See [Elgg issue #9789](https://github.com/Elgg/Elgg/issues/9789)
- Delay content notifications until the content is no longer private.  
Mainly this was developed for the 'create' notifications of content (eg. Discussion)

Developers
----------

If you wish to extend the allowed delay notification actions register a plugin hook on
`delayed_actions` `advanced_notifications` and add your action to the result array.
