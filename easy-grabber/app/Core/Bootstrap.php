<?php

/**
 *  Grabber bootstrap class
 *
 */
class Grabber_Core_Bootstrap extends Grabber_Core_Abstract
{
    /**
     * Start the plugin on plugins_loaded hook.
     *
     * @return void
     */
    public function run()
    {
        $front = new Grabber_Controller_Front();

        add_action('wp_enqueue_scripts', array( $front, 'add_wp_css_fixes' ));

        add_filter('the_content', array( $front, 'add_post_css' ));

        if (is_admin()) {
            $admin = new Grabber_Controller_Admin();

            $thread = new Grabber_Controller_Thread();

            add_action('admin_menu', array( $admin, 'admin_menu' ), 20);

            add_action('admin_init', array( $admin, 'admin_init' ), 20);

            add_action('add_meta_boxes', array( $admin, 'add_meta_boxes' ));

            add_action('admin_print_styles-post.php', array( $front, 'add_wp_css_fixes' ));

            add_action('wp_ajax_grabber_parser', array( $thread, 'run' ));

            add_action('wp_ajax_nopriv_grabber_parser', array( $thread, 'run' ));

            add_action('wp_ajax_grabber_single', array( $thread, 'single' ));

            add_action('wp_ajax_nopriv_grabber_single', array( $thread, 'single' ));
        }
    }

    /**
     * Install callback to create custom database tables.
     *
     * @wp-hook register_activation_hook
     *
     * @return void
     */
    public function activate()
    {
        $install = new Grabber_Controller_Install();
        $install->install();
    }
}
