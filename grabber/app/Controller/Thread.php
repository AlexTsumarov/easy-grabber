<?php

class Grabber_Controller_Thread extends Grabber_Core_Abstract {

	/**
	 * Contains log of current thread.
	 *
	 * @var array
	 */
	private $log = array();

	/**
	 * Contains url of current thread.
	 *
	 * @var String
	 */
	private $url = null;

	/**
	 * Constructor for class Grabber_Controller_Thread.
	 *
	 * @return Grabber_Controller_Thread
	 */
	public function __construct() {

		//ini_set( 'max_execution_time', 25 );

		$this->conf->pid				 = getmypid();
		$this->conf->max_execution_time	 = ini_get( 'max_execution_time' ) / 3;
		$this->conf->memory_limit		 = ini_get( 'memory_limit' );
		$this->conf->start_time			 = $this->time();
		$this->conf->local_start_time	 = $this->time();
		$this->conf->time_spent			 = $this->time();
		register_shutdown_function( array( $this, 'shutdown' ) );
		$this->log[]					 = "Start: memory_limit {$this->conf->memory_limit}, max_execution_time {$this->conf->max_execution_time}<br>";
	}

	/**
	 * Run one thread and execute queue one-by-one.
	 *
	 * @wp-hook wp_ajax_grabber_parser, wp_ajax_nopriv_grabber_parser
	 *
	 * @return Void
	 */
	public function run() {

		if ( ! is_admin() )
			throw new Exception ('No admin sessoin founded.');
		
		$this->register();

		$this->deatach();

		$page = new Grabber_Parser_Page();

		$css = new Grabber_Parser_Css();

		$queue = new Grabber_Model_Queue();

		$wpapi = new Grabber_Model_Wpapi();

		while ( $this->url = $queue->getUrlFromQueue( $this->conf->TID ) ) {

			$this->updateThread( $this->url );

			$url_log = array(
				'req_url'	 => $this->url,
				'wp_id'		 => 0,
				'notes'		 => ''
			);

			try {

				$grabbedData = $page->grab( $this->url );
				
				$css->setData( $grabbedData )->generate();

				$url_log[ 'wp_id' ] = $wpapi->save_post( $grabbedData );

				$url_log[ 'notes' ] = $this->conf->grablog;
				
				$this->addLinkedUrlsToQueue( $grabbedData[ 'linked-pages' ] );
				
			} catch ( Exception $e ) {

				$url_log[ 'notes' ] .= $e->getMessage().'<br>'.$this->conf->grablog;
				
				$this->updateThread( $e->getMessage() );
			};
			
			$queue->urlDone( $url_log );

			$this->restart();
		}

		$this->shutdown();
		
		exit();
	}

	/**
	 * Fill a queue with founded links.
	 * 
	 * @param array $links array of founded links.
	 *
	 * @return void
	 */
	private function addLinkedUrlsToQueue( $links = null ) {

		if ( !is_array( $links ) )
			return;

		$urls = array();
		
		foreach ( $links as $row ) {

			if ( isset( $row[ 'link' ] ) && !empty( $row[ 'link' ] ) ) {

				$urls[] = $row[ 'link' ];
			}
		}
		if ( sizeof( $urls ) == 0 )
			return;

		$queue = new Grabber_Model_Queue( $this->conf );

		$queue->addUrls( $urls, $this->url );
	}

	/**
	 * Register thread in table `$this->conf->thread_table`
	 *
	 * @return $this
	 */
	private function register() {

		global $wpdb;

		$data = array(
			'pid'	 => $this->conf->pid,
			'log'	 => '',
		);

		$format = array(
			'%d'
		);

		if ( isset( $_GET[ 'TID' ] ) ) {

			$this->conf->TID = $_GET[ 'TID' ];
			$row			 = $wpdb->get_row( "SELECT * FROM {$this->conf->thread_table} where TID = '{$this->conf->TID}' " );
			if ( is_object( $row ) ) {

				$this->log				 = explode( '<br>', $row->log );
				$this->conf->start_time	 = $this->conf->start_time - $row->duration * 1000;
			}
		} else {

			$wpdb->insert( $this->conf->thread_table, $data, $format );
			$this->conf->TID = $wpdb->insert_id;
		}

		return $this;
	}

	/**
	 * Deatach thread from request.
	 *
	 * @return $this
	 */
	private function deatach() {

		ignore_user_abort();
		ob_end_clean();
		ob_start();
		echo $this->conf->TID;
		header( "Connection: close" );
		header( "Content-Length: " . ob_get_length() );
		ob_end_flush();
		flush();

		if ( isset( $_GET[ 'delay' ] ) && $_GET[ 'delay' ] > 0 ) {

			$this->updateThread( 'Wait for starting delay ' . $_GET[ 'delay' ] . ' sec' );
			sleep( $_GET[ 'delay' ] );
		}

		return $this;
	}

	/**
	 * Restart thread because by timeout or other reason.
	 *
	 * @param boolean|null $force If true - retart with no condition.
	 * 
	 * @return void
	 */
	private function restart( $force = false ) {

		// force
		if ( $force ) {

			$this->updateThread( 'Restart while error' );
			get_headers( admin_url( 'admin-ajax.php' ) . '?action=grabber_parser&TID=' . $this->conf->TID );
			$this->shutdown();
			exit;
		}

		// by timeout
		$this->conf->time_spent = round( ( $this->time() - $this->conf->local_start_time ) / 1000 );
		if ( $this->conf->time_spent >= $this->conf->max_execution_time ) {

			$this->updateThread( 'Restart by timeout: ' . $this->conf->max_execution_time . ' sec' );
			$this->conf->status = 'restarted';
			get_headers( admin_url( 'admin-ajax.php' ) . '?action=grabber_parser&TID=' . $this->conf->TID );
			$this->shutdown();
			exit;
		}
	}

	/**
	 * Get time stamp in milliseconds.
	 *
	 * @return Integer
	 */
	private function time() {
		list($usec, $sec) = explode( ' ', microtime() );
		return (int) ((int) $sec * 1000 + ((float) $usec * 1000));
	}

	/**
	 * Called while a script ends.
	 * 
	 * @hook register_shutdown_function
	 *
	 * @return void
	 */
	public function shutdown() {

		global $wpdb;

		$duration = ( $this->time() - $this->conf->start_time ) / 1000;

		if ( !isset( $this->conf->status ) )
			$this->conf->status = 'finished';

		$e = error_get_last();
		if ( isset( $e[ 'message' ] ) ) {

			$this->log[]		 = $e[ 'file' ] . " #{$e[ 'line' ]}: {$e[ 'message' ]}";
			$this->conf->status	 = 'failed';
			$queue				 = new Grabber_Model_Queue( $this->conf );
			$queue->releaseUrl( $this->url );
		}

		$log = (string) implode( "<br>", $this->log );

		$wpdb->update(
		$this->conf->thread_table, array( 'status' => $this->conf->status, 'duration' => $duration, 'log' => $log ), array( 'TID' => $this->conf->TID ), array( '%s', '%s', '%s' ), array( '%d' )
		);

		if ( $this->conf->status == 'failed' ) {

			$this->restart();
		}
	}

	/**
	 * Update a thread duration, status and log data in db.
	 *
	 * @return void
	 */
	private function updateThread( $withMessage = null ) {

		$this->isThreadDead();

		$duration = ( $this->time() - $this->conf->start_time ) / 1000;

		if ( !isset( $withMessage ) )
			return $this;

		if ( !is_string( $withMessage ) )
			$withMessage = print_r( $withMessage, true );

		$this->log[] = $duration . ': ' . $withMessage;

		$log = substr( implode( "<br>", $this->log ), 0, 4096 );

		$this->conf->status = 'work';

		global $wpdb;
		$wpdb->update(
		$this->conf->thread_table, array( 'status' => $this->conf->status, 'duration' => $duration, 'log' => $log ), array( 'TID' => $this->conf->TID ), array( '%s', '%s', '%s' ), array( '%d' )
		);
	}

	/**
	 * Check if curent thread record doesn`t exist in db or thread execution time is out.
	 *
	 * @return void
	 */
	public function isThreadDead() {

		$thread = new Grabber_Model_Thread( $this->conf );
		if ( $thread->isDead() ) {

			exit;
		}
	}

	/**
	 * Debug method to sibgle url parser run.
	 *
	 * @wp-hook wp_ajax_grabber_single, wp_ajax_nopriv_grabber_single
	 *
	 * @return String
	 */
	public function single() {

		if ( ! is_admin() )
			throw new Exception ('Access are stricted.');
		
		$url = 'summits/';
		if ( isset( $_GET[ 'path' ] ) )
			$url = $_GET[ 'path' ];

		$log = '1122';

		$c	 = new Grabber_Parser_Css( $this->conf );
		$p	 = new Grabber_Parser_Page( $this->conf );

		$p->page( $url );
		$d	 = $p->getData();
		$c->setData( $d );
		$out = $c->insertCss();

		$wpapi	 = new Grabber_Model_Wpapi( $this->conf );
		$p		 = $wpapi->save_post( $d );

		echo $log;
	}

}
