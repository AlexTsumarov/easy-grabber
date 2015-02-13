<?php

/**
 * Controller to build admin view and handle actions
 *
 * @package Grabber
 */
class Grabber_Controller_Admin extends Grabber_Core_Abstract {

	/**
	 * Constructor for class Grabber_Controller_Admin.
	 *
	 * @return Grabber_Controller_Admin
	 */
	public function __construct() {
		$this->conf->config_slug = 'grabber_config';
		$this->conf->run_slug	 = 'grabber_run';
		$this->conf->thread_slug = 'grabber_threads';
	}

	/**
	 * Show plugin in administrator panel.
	 *
	 * @wp-hook admin_menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		$this->conf->config_prefix = add_menu_page( 'Config', 'Grabber', 'administrator', $this->conf->config_slug, array( $this, 'render_config_page' ) );
		add_submenu_page( $this->conf->config_slug, 'Queue', 'Queue', 'manage_options', $this->conf->run_slug, array( $this, 'render_run_page' ) );
		add_submenu_page( $this->conf->thread_slug, 'Threads', 'Threads', 'manage_options', $this->conf->thread_slug, array( $this, 'render_runner_page' ) );
	}

	/**
	 * Show plugin in administrator panel.
	 *
	 * @wp-hook admin_init
	 *
	 * @return void
	 */
	public function admin_init() {
		register_setting( Grabber_Core_Config::config_path, Grabber_Core_Config::config_path );
		register_setting( Grabber_Core_Config::run_path, Grabber_Core_Config::run_path );
		add_settings_section( 'default', null, null, $this->conf->config_slug );

		foreach ( Grabber_Core_Config::$labels as $setting_name => $label ) {
			$setting_value = $this->conf->$setting_name;

			if ( in_array( $setting_name, array( 'add_css_inline', 'rewrite_on_download', 'update_links', 'skip_exist_in_hist', 'attachResources' ) ) ) {
				//checkboxes
				add_settings_field( $setting_name, '', array( $this, 'render_checkbox' ), $this->conf->config_slug, 'default', array( 'id' => $setting_name, 'value' => 'yes', 'text' => $label, 's_value' => $setting_value ) );
			} elseif ( in_array( $setting_name, array( 'grab_list' ) ) ) {
				//checkboxes
				add_settings_field( $setting_name, $label, array( $this, 'render_textarea' ), $this->conf->config_slug, 'default', array( 'id' => $setting_name, 'value' => $setting_value ) );
			} elseif ( in_array( $setting_name, array( 'insert_cat_into' ) ) ) {
				//selectbox
				add_settings_field( $setting_name, $label, array( $this, 'render_categories' ), $this->conf->config_slug, 'default', array( $setting_name, $setting_value ) );
			} else {
				//inputs
				add_settings_field( $setting_name, $label, array( $this, 'render_input' ), $this->conf->config_slug, 'default', array( 'id' => $setting_name, 'value' => $setting_value ) );
			}
		}

		if ( isset( $_GET[ 'settings-updated' ] ) ) {
			wp_redirect( '?page=' . Grabber_Core_Config::run_path );
		}
	}

	/**
	 * Add styles editor to add / edit post page.
	 *
	 * @wp-hook add_meta_boxes
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		if ( $this->conf->post_meta_styles != '' ) {
			foreach ( array( 'post', 'page' ) as $post_type ) {
				add_meta_box(
				$this->conf->post_meta_styles, __( Grabber_Core_Config::$labels[ 'post_meta_styles' ], 'grabber' ), array( $this, 'render_css_box' ), $post_type, 'normal', 'default'
				);
			}
		}
	}

	/**
	 * Show plugin in administrator panel.
	 *
	 * @return void
	 */
	public function render_config_page() {
		?>
		<div class="wrap">
			<form method="post" action="options.php">

				<div class="metabox-holder">
					<div class="postbox-container" style="width: 99%;">
		<?php
		add_meta_box( Grabber_Core_Config::config_path, __( 'Config', Grabber_Core_Config::config_path ), array( $this, 'do_settings_box' ), $this->conf->config_slug, 'main' );
		settings_fields( Grabber_Core_Config::config_path );
		do_meta_boxes( $this->conf->config_slug, 'main', null );
		?>
					</div>
				</div>

				<p>
					<input type="submit" class="button button-primary" name="save_options" value="<?php esc_attr_e( 'Save' );
		?>" />
				</p>
			</form>
		</div>

						   <?php
					   }

					   /**
						* Render a page with queue, threads, log and history grids.
						*
						* @return string
						*/
					   public function render_run_page() {
						   $thread_model	 = new Grabber_Model_Thread( $this->conf );
						   $queue_model	 = new Grabber_Model_Queue( $this->conf );
						   $log_model		 = new Grabber_Model_Log( $this->conf );
						   $threads		 = $thread_model->getThread();
						   $queue			 = $queue_model->getQueue();
						   $logs			 = $log_model->getLog();
						   $hist			 = $log_model->getHistory();
						   ?>
		<div class="wrap">

			<form method="post" action="?page=grabber_threads">

				<p>
					<input type="submit" class="button button-primary" name="start" value="<?php esc_attr_e( 'Start' );
						   ?>" />
		<?php if ( $queue_model->count() > 0 && $thread_model->isAllDead() ) {
			?>
						<input type="submit" class="button button-primary" name="continue" value="<?php esc_attr_e( 'Continue' );
			?>" />
			<?php
		}
		?>
				</p>

				<div class="metabox-holder">
					<div class="postbox-container" style="width: 99%;">
						   <?php
						   do_meta_boxes( $this->conf->run_slug, 'main', null );
						   settings_fields( Grabber_Core_Config::run_path );

						   echo "<h3 style='margin-bottom: -20px;'>Threads" . Grabber_Helper_Common::count( $threads ) . "</h3>";
						   $testListTable = new Grabber_Helper_Table( $threads, $this->conf );
						   $testListTable->prepare_items();
						   $testListTable->display();

						   echo "<h3 style='margin-bottom: -20px;'>Queue wait" . Grabber_Helper_Common::count( $queue ) . "</h3>";
						   $testListTable = new Grabber_Helper_Table( $queue, $this->conf );
						   $testListTable->prepare_items();
						   $testListTable->display();

						   echo "<h3 style='margin-bottom: -20px;'>Queue done" . Grabber_Helper_Common::count( $logs ) . "</h3>";
						   $testListTable = new Grabber_Helper_Table( $logs, $this->conf );
						   $testListTable->prepare_items();
						   $testListTable->display();

						   echo "<h3 style='margin-bottom: -20px;'>History" . Grabber_Helper_Common::count( $hist ) . "</h3>";
						   $testListTable = new Grabber_Helper_Table( $hist, $this->conf );
						   $testListTable->prepare_items();
						   $testListTable->display();

						   if ( Grabber_Helper_Common::count( $threads ) ) {
							   Grabber_Helper_Common::refreshEvery( 20 );
						   }
						   ?>
					</div>
				</div>

			</form>
		</div>

						<?php
					}

					/**
					 * Render a page which will run threads and redirect after to queue page.
					 *
					 * @return string
					 */
					public function render_runner_page() {
						?>
		<div class="wrap">
			<h2><?php echo esc_html( 'Wait while a grabbing threads will be generated' );
						?></h2>

			<form method="post" action="?page=grabber_threads">

				<div class="metabox-holder">
					<div class="postbox-container" style="width: 99%;">
		<?php
		if ( isset( $_POST[ 'start' ] ) ) {
			$log	 = new Grabber_Model_Log( $this->conf );
			$queue	 = new Grabber_Model_Queue( $this->conf );
			$log->truncateLog();
			$queue->truncateQueue()->fillQueue();
		}

		$runner = new Grabber_Helper_Runner( $this->conf );
		$runner->terminateParserThreads()->runParserTreads()->redirect();
		?>
					</div>
				</div>
			</form>
		</div>

						<?php
					}

					/**
					 * Prints out settings sections added to a settings page
					 *
					 * @return string
					 */
					public function do_settings_box() {
						do_settings_sections( $this->conf->config_slug );
					}

					/**
					 * Render the input
					 *
					 * @param array $args array with name and value
					 *
					 * @return string
					 */
					public function render_input( $args ) {
						$id = Grabber_Core_Config::config_path . '[' . $args[ 'id' ] . ']';
						?>
		<input id="<?php echo $id;
						?>" style="width:50%;"  type="text" name="<?php echo $id;
						?>" value="<?php echo $args[ 'value' ];
						?>" />
		<?php
	}

	/**
	 * Render the checkbox
	 *
	 * @param array $args array with name and value
	 *
	 * @return string
	 */
	public function render_checkbox( $args ) {
		$id		 = Grabber_Core_Config::config_path . '[' . $args[ 'id' ] . ']';
		$checked = ($args[ 's_value' ] == 'yes') ? 'checked' : '';
		?>
		<input name="<?php echo $id;
		?>" type="checkbox" value="<?php echo $args[ 'value' ];
		?>" <?php echo $checked;
		?> /> <?php echo " {$args[ 'text' ]}";
		?> <br/>
			   <?php
		   }

		   /**
			* Render the textarea
			*
			* @param array $args array with name and value
			*
			* @return string
			*/
		   public function render_textarea( $args ) {
			   $id = Grabber_Core_Config::config_path . '[' . $args[ 'id' ] . ']';
			   ?>
		<textarea style="width:50%; height: 100px;" name="<?php echo $id;
			   ?>"><?php echo $args[ 'value' ];
			   ?></textarea><br/>
		<?php
	}

	/**
	 * Render the inputs the list of categories
	 *
	 * @param array $parameters array with selectbox name and selected value
	 *
	 * @return string
	 */
	public function render_categories( $parameters = array() ) {
		$args = array(
			'hide_empty'	 => 0,
			'show_count'	 => 1,
			'hierarchical'	 => 1,
			'taxonomy'		 => 'category',
		);

		if ( isset( $parameters[ 0 ] ) ) {
			$args[ 'name' ] = Grabber_Core_Config::config_path . '[' . $parameters[ 0 ] . ']';
		}

		if ( isset( $parameters[ 1 ] ) ) {
			$args[ 'selected' ] = $parameters[ 1 ];
		}

		wp_dropdown_categories( $args );
	}

	/**
	 * Callback function of add_meta_box to render the editor for styles.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function render_css_box( $post ) {
		$content = get_post_meta( $post->ID, $this->conf->post_meta_styles, true );

		echo "<textarea class='css' style='width:100%; height: 200px' name='{$this->conf->post_meta_styles}' id='{$this->conf->post_meta_styles}'>$content</textarea>";

		//echo "<script> hljs.initHighlightingOnLoad(); </script>";
		//<pre><code class='css'>".str_replace( "}", "}\n", $content )."</code></pre>
	}

}
