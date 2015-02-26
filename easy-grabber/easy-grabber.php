<?php

/*
  Plugin Name: Import by URL
  Description: Grab static pages and insert as WP post. Supports: multi thread, grab css/JavaScript/image files, creation of new posts and categories.
  Version: 0.0.14
  Author: Alex Ts
  Author URI: https://github.com/AlexTsumarov/
 */

define('GRABBER_VERSION', '0.0.14');
define('GRABBER_DIR', plugin_dir_path(__FILE__));
define('GRABBER_URL', plugin_dir_url(__FILE__));
add_action('plugins_loaded', 'grabber_init', 0);
register_activation_hook(__FILE__, 'grabber_activate');

/**
 * Callback for activating the plugin.
 *
 * @wp-hook register_activation_hook
 *
 * @return void
 */
function grabber_activate()
{
    grabber_loader();

    $grabber = new Grabber_Core_Bootstrap();

    $grabber->activate();
}

/**
 * Callback for starting the plugin.
 *
 * @wp-hook plugins_loaded
 *
 * @return void
 */
function grabber_init()
{
    grabber_loader();

    $grabber = new Grabber_Core_Bootstrap();

    $grabber->run();
}

/**
 * Loader
 *
 * @return void
 */
function grabber_loader()
{
    $dir = dirname(__FILE__).DIRECTORY_SEPARATOR;

    require $dir.'app/Core/Autoloader.php';

    Grabber_AutoLoader::spl_register($dir);
}
