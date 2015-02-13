<?php

/**
 * Controller to attach custom css and js to front and admin layout.
 *
 * @package Grabber
 */
class Grabber_Controller_Front extends Grabber_Core_Abstract
{
    /**
     * Add custom css and js
     *
     * @wp-hook wp_enqueue_scripts
     *
     * @return void
     */
    public function add_wp_css_fixes()
    {
        wp_register_style('wp-frabber-fixes', plugins_url('/grabber/resources/css/wp-fixes.css', 'grabber'));
        wp_enqueue_style('wp-frabber-fixes');
    }

    /**
     * Add post css
     *
     * @wp-hook the_content
     *
     * @return void
     */
    public function add_post_css($content)
    {
        if (is_single()) {
            $css = get_post_meta($GLOBALS[ 'post' ]->ID, $this->conf->post_meta_styles, true);

            $content .= "<style>$css</style>";
        }

        return $content;
    }

}
