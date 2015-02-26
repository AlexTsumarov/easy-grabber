<?php

/**
 * Common helper.
 *
 * @package Grabber
 */
class Grabber_Helper_Common {

	/**
	 * Count body rows of the table.
	 *
	 * @param link $table Link to table array
	 *
	 * @return int
	 */
	public function count( &$table ) {
		$count = isset( $table[ 'body' ] ) ? ' (' . sizeof( $table[ 'body' ] ) . ')' : '';

		return $count;
	}

	/**
	 * Page reload after $sec seconds.
	 *
	 * @param int $sec Number of seconds.
	 *
	 * @return Grabber_Helper_Common
	 */
	public function refreshEvery( $sec = 1 ) {
		if ( $sec < 1 ) {
			return $this;
		}

		echo "<script>
			setInterval( function () { window.location.href=window.location.href; }, " . $sec * 1000 . ");
				</script>";

		return $this;
	}

	/**
	 * Determines if a command exists on the current environment
	 *
	 * @param string $command The command to check
	 * @return bool True if the command has been found ; otherwise, false.
	 */
	public static function commandExists( $command ) {

		$whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

		$process = proc_open(
		"$whereIsCommand $command", array(
			0	 => array( "pipe", "r" ), //STDIN
			1	 => array( "pipe", "w" ), //STDOUT
			2	 => array( "pipe", "w" ), //STDERR
		), $pipes
		);
		if ( $process !== false ) {
			$stdout	 = stream_get_contents( $pipes[ 1 ] );
			$stderr	 = stream_get_contents( $pipes[ 2 ] );
			fclose( $pipes[ 1 ] );
			fclose( $pipes[ 2 ] );
			proc_close( $process );

			return $stdout != '';
		}

		return false;
	}

	/**
	 * Create headers with context
	 *
	 * @param string $url The target url
	 * @param object $url New stream_context_create
	 * @param object $assoc Flag
	 * @return string $headers
	 */
	function get_headers_with_stream_context( $url, $context, $assoc = 0 ) {
		$fp			 = fopen( $url, 'r', null, $context );
		$metaData	 = stream_get_meta_data( $fp );
		fclose( $fp );

		$headerLines = $metaData[ 'wrapper_data' ];

		if ( !$assoc )
			return $headerLines;

		$headers = array();
		foreach ( $headerLines as $line ) {
			if ( strpos( $line, 'HTTP' ) === 0 ) {
				$headers[ 0 ] = $line;
				continue;
			}

			list($key, $value) = explode( ': ', $line );
			$headers[ $key ] = $value;
		}

		return $headers;
	}

	public static function get_web_page( $url ) {
		
		$res	 = array();
		$options = array(
			CURLOPT_RETURNTRANSFER	 => true, // return web page 
			CURLOPT_HEADER			 => false, // do not return headers 
			CURLOPT_FOLLOWLOCATION	 => true, // follow redirects 
			CURLOPT_USERAGENT		 => "WordPress Easy Grabber", // who am i 
			CURLOPT_AUTOREFERER		 => true, // set referer on redirect 
			CURLOPT_CONNECTTIMEOUT	 => 120, // timeout on connect 
			CURLOPT_TIMEOUT			 => 120, // timeout on response 
			CURLOPT_MAXREDIRS		 => 10, // stop after 10 redirects 
		);
		$ch		 = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );
		$err	 = curl_errno( $ch );
		$errmsg	 = curl_error( $ch );
		$header	 = curl_getinfo( $ch );
		curl_close( $ch );

		$res[ 'content' ]	 = $content;
		$res[ 'url' ]		 = $header[ 'url' ];
		
		return $content;
	}

}