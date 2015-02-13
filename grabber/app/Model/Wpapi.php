<?php

/**
 * Adds a new post and category in wordpress by API.
 *
 * @package Grabber
 */
class Grabber_Model_Wpapi extends Grabber_Core_Abstract
{
    /**
     * Contains parsed array
     *
     * @var array
     */
    private $item = null;

    /**
     * Constructor for class Grabber_Model_Wpapi
     *
     * @return Grabber_Model_Wpapi
     */
    public function __construct()
    {
        wp_set_current_user(1);
    }

    /**
     * Add new post to WP or update an existent founded by path.
     *
     * @param array $item array of parsed values.
     *
     * @return int $post['ID'] an id of dreated post.
     */
    public function save_post($item = array())
    {
        $this->item = $item;

        if (!isset($this->item[ 'content' ])) {
            throw new Exception('Can`t create the post: no content defined');
        }

        $cID = $this->save_category();

        if (!$post = get_page_by_path($this->item[ 'wpurl' ], ARRAY_A, array( 'post', 'page' ))) {
            $post = array();
        }

        $post[ 'post_name' ]     = $this->item[ 'wpurl' ];
        $post[ 'post_title' ]     = isset($this->item[ 'title' ]) ? $this->item[ 'title' ] : '';
        $post[ 'post_status' ]     = 'publish';
        $post[ 'post_status' ]     = 'post';
        $post[ 'post_status' ]     = 'publish';
        $post[ 'post_category' ] = array( $cID );
        $post[ 'post_content' ]     = $this->item[ 'content' ];

        // insert post inine if post meta key are not set
        if ($this->conf->post_meta_styles == '' && $this->conf->add_css_inline == 'yes') {
            $post[ 'post_content' ] .= "<style id='post_css'>\n{$this->item[ 'composed_css' ]}</style>";
        }

        if (isset($post[ 'ID' ])) {
            $result = wp_update_post($post, true);
            if ($result instanceof WP_Error) {
                throw new Exception($result->get_error_message());
            }
        } else {
            $result = wp_insert_post($post, true);
            if ($result instanceof WP_Error) {
                throw new Exception($result->get_error_message());
            } else {
                $post[ 'ID' ] = $result;
            }
        }

        // insert post into the post meta
        if ($this->conf->post_meta_styles != '') {
            delete_post_meta($post[ 'ID' ], $this->conf->post_meta_styles);
            add_post_meta($post[ 'ID' ], $this->conf->post_meta_styles, $this->item[ 'composed_css' ], true);
        }

        return $post[ 'ID' ];
    }

    /**
     * Add new category to WP or return an exist category by `slug`.
     *
     * @return int Category ID
     */
    private function save_category()
    {
        $path = trim(trim(parse_url($this->item[ 'fullurl' ], PHP_URL_PATH), '/'));

        $dir = str_replace(array( '.', '\\' ), '', dirname($path));
        if (!empty($dir)) {
            $cID = get_category_by_slug(dirname($path));

            if (is_object($cID)) {
                $cID = $cID->term_id;
            }

            if ((int) $cID == 0) {
                $cID = wp_insert_category(array( 'cat_name' => $dir, 'taxonomy' => 'category', 'category_nicename' => $dir, 'category_parent' => $this->conf->insert_cat_into ));

                $this->conf->grablog .= "Category `$dir` created with #$cID<br>";

                if ($cID instanceof WP_Error) {
                    throw new Exception($cID->get_error_message());
                }

                if (is_object($cID)) {
                    $cID = $cID->term_id;
                }
            }
        }

        if (!isset($cID) || (int) $cID == 0) {
            $cID = $this->conf->insert_cat_into;
        }

        return $cID;
    }
}
