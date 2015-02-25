=== Grabber plugin for WordPress ===

[![License](https://poser.pugx.org/leaphly/cart-bundle/license.svg)](https://packagist.org/packages/leaphly/cart-bundle)

<img src='https://travis-ci.org/AlexTsumarov/easy-grabber.svg'>

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