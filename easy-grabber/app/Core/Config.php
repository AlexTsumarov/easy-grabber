<?php

/**
 * Config helper to get and set config and request data.
 *
 * @package Grabber
 */
class Grabber_Core_Config {

	/**
	 * Contains default plugin config
	 *
	 * @var string
	 */
	const config_default = 'defaults.yml';

	/**
	 * Contains id in WP get_settings storage.
	 *
	 * @var string
	 */
	const config_path = 'grabber_settings';

	/**
	 * Contains id of WP register_setting.
	 *
	 * @var string
	 */
	const run_path = 'grabber_run';

	/**
	 * Contains config data of current request.
	 *
	 * @var array
	 */
	private $config = null;

	/**
	 * Contains config instance
	 *
	 * @var array
	 */
	private static $_instance = null;

	/**
	 * Contains defaults data of plugin.
	 *
	 * @var array
	 */
	private $defaults = array(
		'grab_threads'				 => 1,
		'log_table'					 => 'grabber_log',
		'hist_table'				 => 'grabber_hist',
		'queue_table'				 => 'grabber_queue',
		'thread_table'				 => 'grabber_thread',
		'css_path'					 => 'resources/css/',
		'css_url'					 => 'resources/css/',
		'js_path'					 => 'resources/js/',
		'img_path'					 => 'resources/img/',
		'js_url'					 => 'resources/js/',
		'img_url'					 => 'resources/img/',
		'runner_grad_pause'			 => 1,
		'one_url_parse_timeout'		 => 120,
		'thread_norespond_timeout'	 => 300,
		'table_tows'				 => 5,
		'post_meta_styles'			 => 'post_css',
		'attachResources'			 => '',
		'drop_with_selectors'		 => '',
	);

	/**
	 * Contains labels for settings page.
	 *
	 * All label from this array will be rendered by Grabber_Controller_Admin->admin_init().
	 * In depends of id it will be input, textaream checkbox or select box.
	 *
	 * @var array
	 */
	public static $labels = array(
		'cs_path'				 => 'Url of grabbed resource',
		'grab_list'				 => 'Suburls to grab,<br>separated by newline',
		'tabs_selector'			 => 'Grab linked pages by selector',
		'grab_threads'			 => 'Numbers of grabbing threads, 1..10',
		'main_wrapper'			 => 'Content selector',
		'drop_with_selectors'	 => 'Drop elements with selector, <br>separated by newline',
		'insert_cat_into'		 => 'Category to insert posts',
		'update_links'			 => 'Update links with WP category',
		'add_css_inline'		 => 'Insert grabbed css from `Content selector` to post inline',
		'rewrite_on_download'	 => 'Force rewrite resources grabbed before',
		'skip_exist_in_hist'	 => 'Skip nested urls what exist in history table',
		'attachResources'		 => 'Link all founded css and javascript files to post',
	);

	public static function singletoneInstance() {
		if ( !self::$_instance ) {
			self::$_instance = new Grabber_Core_Config();
		}

		return self::$_instance;
	}

	/**
	 * Getter for config.
	 *
	 * @param string $k key of config.
	 *
	 * @return mixed
	 */
	public function __get( $k ) {

		if ( !isset( $this->config[ $k ] ) && isset( $this->defaults[ $k ] ) ) {
			$this->config[ $k ] = $this->defaults[ $k ];
		}

		$this->sanitize( $k );

		if ( !isset( $this->config[ $k ] ) ) {
			return;
		}

		return $this->config[ $k ];
	}

	/**
	 * Setter for config.
	 *
	 * @param string $k key of config.
	 * @param string $v value of config.
	 *
	 * @return string
	 */
	public function __set( $k, $v ) {
		
		$this->config[ $k ] = $v;

		return $this->config[ $k ];
	}

	/**
	 * Constructor for class Grabber_Core_Config.
	 *
	 * Fill config from WP get_settings or if not from defaults.
	 *
	 * @return Grabber_Core_Config
	 */
	private function __construct() {
		// load settings from file
		if ( file_exists( GRABBER_DIR . self::config_default ) && file_exists( GRABBER_DIR . 'vendor/Spyc.php' ) ) {
			require_once GRABBER_DIR . 'vendor/Spyc.php';

			$file_data = spyc_load_file( GRABBER_DIR . self::config_default );

			if ( isset( $file_data[ 'labels' ] ) && is_array( $file_data[ 'labels' ] ) ) {
				foreach( $file_data[ 'labels' ] as $k => $v )
					Grabber_Core_Config::$labels[$k] = $v;
			}

			if ( isset( $file_data[ 'defaults' ] ) && is_array( $file_data[ 'defaults' ] ) ) {
				foreach ( $file_data[ 'defaults' ] as $k => $v )
					$this->defaults[ $k ] = $v;
			}
		}

		// update specific defaults
		foreach ( $this->defaults as $k => &$v ) {
			if ( in_array( $k, array( 'css_path', 'js_path', 'img_path' ) ) ) {
				$v = GRABBER_DIR . $v;
			} elseif ( in_array( $k, array( 'css_url', 'js_url', 'img_url' ) ) ) {
				$v = parse_url( GRABBER_URL, PHP_URL_PATH ) . $v;
			}
		}

		// override settings from WP storage
		$this->config = get_settings( self::config_path );
	}

	/**
	 * Sanitize for some of config values.
	 *
	 * @param string $k key of config.
	 *
	 * @return void
	 */
	private function sanitize( $k ) {
		switch ( $k ) {

			case 'thread_norespond_timeout': case 'one_url_parse_timeout':
				$max_execution_time = ini_get( 'max_execution_time' );
				if ( $max_execution_time > 0 ) {
					$this->config[ $k ] = min( $this->config[ $k ], $max_execution_time );
				}
				break;

			case 'grab_list':
				$array	 = array();
				$arg	 = $this->config[ $k ];
				$arg	 = str_replace( array( "\r\n", ',', ';' ), "\n", $arg );
				$arg	 = explode( "\n", $arg );
				foreach ( $arg as $i => &$url ) {
					$url = trim( $url );
					$url = ltrim( parse_url( $url, PHP_URL_PATH ), '/' );
					if ( !empty( $url ) ) {
						$array[] = $url;
					}
				}
				$arg				 = array_unique( $array );
				$this->config[ $k ]	 = implode( "\n", $arg );
				break;
		}
	}

}
