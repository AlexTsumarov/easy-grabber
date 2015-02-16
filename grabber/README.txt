=== EASY GRABBER ===
Contributors: AlexTsumarov
Tags: grabber
Requires at least: 3.0
Tested up to: 3.5.2
Stable tag: 0.0.5
License: MIT
License URI: http://opensource.org/licenses/MIT
Donate link: email://po121@tut.by

A websites grabber for WordPress

== Description ==

Allows you to grab a World Wide Web site from the Internet to a WordPress posts and categories, 
recursively creating directories, getting HTML, images, and other files from the server and implements it into your post. 

Core features:
 - easy to use - can start grabbing after 2 fields be filled
 - easy to config - supported yml config format
 - multi-thread grabbing without any additional extensions for web/app server
 - all grabbed pages will be saved directly as WordPress post items, the same about categories
 - all grabbed css, js, images ( even the background images set by css ) be saved to grabbed folder
 - all image src will be updated be consistent with a new path where they saved
 - all inline css will be included to post and might be edited in individual window ( grabber search all css selectors, matches it with all css in all files content and paste into the post only your matched styles)

Note: multi thread grabbing available only for non FastCGI mode.

== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer.
2. It`s better to have Admin->Settings->Permalink->Common Settings->Custom Structure->` %category%/%postname%`
3. Basically the common css changes are configured for theme `Twenty Fifteen`. You can adopt with you theme at %plugin_dir%/resources/css/wp-fixes.css

== Frequently Asked Questions ==

= How to start? =
1. Activate a plugin.
2. Open config page and set website url, init suburl and other options if required.
3. Open queue page and click start button.

= Where I could find the results? =
1. At the page queue of Plugin in the grids queue done and history.
or
2. At the posts list page. 

== Screenshots ==

1. Plugin config page.
2. Plugin queue page.
3. Grabber wiki page in front view.

== Documentation ==

== Changelog ==

= 0.0.5 =
* First version.

== Upgrade Notice ==

== Arbitrary section ==

== Upgrade Notice ==

No data will be removed while upgrade.
