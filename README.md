Advanced Notifications
======================

![Elgg 5.0](https://img.shields.io/badge/Elgg-5.0-green.svg)
![Elgg 6.0](https://img.shields.io/badge/Elgg-6.0-green.svg)
![Lint Checks](https://github.com/ColdTrick/advanced_notifications/actions/workflows/lint.yml/badge.svg?event=push)
[![Latest Stable Version](https://poser.pugx.org/coldtrick/advanced_notifications/v/stable.svg)](https://packagist.org/packages/coldtrick/advanced_notifications)
[![License](https://poser.pugx.org/coldtrick/advanced_notifications/license.svg)](https://packagist.org/packages/coldtrick/advanced_notifications)

This plugin changes the way Elgg handles some notifications, it doesn't change the content of the notification or who gets it.

Features
-----------

- Delay content notifications until the content is no longer private.  
Mainly this was developed for the 'create' notifications of content (eg. Discussion)

Developers
----------

If you wish to extend the allowed delay notification actions register an event handler on
`delayed_actions`, `advanced_notifications` and add your action to the result array.
