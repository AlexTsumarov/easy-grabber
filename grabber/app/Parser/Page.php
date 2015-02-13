<?php

class Grabber_Parser_Page extends Grabber_Parser_Abstract {

	/**
	 * Object simple_html_dom
	 *
	 * @var obgect
	 */
	private $html = null;

	/**
	 * Start a grabbing of selected url.
	 *
	 * @param string $url The path what have to be grabbed.
	 *
	 * @return string Log messages what occur while grabbing. Might be inserted after into db with Grabber_Controller_Thread
	 */
	public function grab( $url ) {
		// flush log for each parsed url
		$this->conf->grablog = '';

		$this->parsed = array(
			'suburl'		 => $url,
			'title'			 => null,
			'fullurl'		 => null,
			'wpurl'			 => null,
			'linked-pages'	 => array(),
			'styles'		 => array(),
			'img'			 => array(),
			'files-css'		 => array(),
			'files-js'		 => array(),
			'selectors'		 => array(),
			'composed_css'	 => '',
		);

		$url = trim( $this->conf->cs_path . $url );

		$this->check_url_exc( $url );

		$content	 = file_get_contents( $url, false, stream_context_create( array( 'http' => array( 'follow_location' => true ) ) ) );
		$lib		 = new Grabber_Core_Simpledom();
		$this->html	 = $lib->str_get_html( $content );

		if ( !is_object( $this->html ) ) {
			throw new Exception( 'Page can`t be parsed: ' . $content );
		}

		$this->parsed[ 'title' ]	 = is_array( $this->html->find( 'title' ) ) ? trim( $this->html->find( 'title', 0 )->innertext ) : 'title?';
		$this->parsed[ 'fullurl' ]	 = $url;
		$this->parsed[ 'wpurl' ]	 = sanitize_title_with_dashes( basename( $url ) );

		// Get urls what have to be grabbed too
		if ( $this->conf->tabs_selector != '' ) {
			foreach ( $this->html->find( $this->conf->tabs_selector ) as $e ) {
				$this->parsed[ 'linked-pages' ][] = array( 'text' => $e->plaintext, 'link' => $e->href );
			}
		}

		$this
		->findLinkedResources()
		->getContentSelectors()
		->getContent()
		->storeFilesCss()
		->storeFilesJs()
		->attachResources();

		return $this->getData();
	}

	/**
	 * Gran for styles and set 2 arrays: founded files and inline styles.
	 *
	 * @return $this
	 */
	private function findLinkedResources() {
		if ( !is_object( $this->html ) ) {
			return $this;
		}

		foreach ( $this->html->find( 'script' ) as $e ) {
			if ( !empty( $e->src ) ) {
				$this->parsed[ 'files-js' ][] = $e->src;
			}
		}

		foreach ( $this->html->find( 'style' ) as $e ) {
			if ( strstr( $e->innertext, 'import' ) ) {
				// find imported files
				preg_match( "/(url\([\'\"]?)([^\"\'\)]+)([\"\']?\))/", $e->innertext, $matches );
				$file = $matches[ 2 ];
				if ( !empty( $file ) && !in_array( $file, (array) $this->parsed[ 'files-css' ] ) ) {
					$this->parsed[ 'files-css' ][] = $file;
				}
			};

			$style = trim( preg_replace( '/\s*@import.*;\s*/iU', '', $e->innertext ) );
			if ( $style != '' ) {
				$this->parsed[ 'styles' ][] = $style;
			}
		}
		foreach ( $this->html->find( 'link' ) as $e ) {
			if ( $e->src != '' ) {
				$this->parsed[ 'files-css' ][] = $e->src;
			}
		}

		return $this;
	}

	/**
	 * Store founded css resources locally
	 *
	 * @return $this
	 */
	private function storeFilesCss() {
		if ( !$this->parsed ) {
			return $this;
		}

		if ( $this->conf->css_path == '' ) {
			return $this;
		}

		foreach ( $this->parsed[ 'files-css' ] as &$link ) {
			
			if ( strpos( $link, '?' ) ) {
				$link = substr( $link, 0, strpos( $link, '?' ) );
			}

			$src = $this->conf->cs_path . $link;

			$dst = $this->conf->css_path . ltrim( $link, '/' );

			if ( !file_exists( $dst ) || $this->conf->rewrite_on_download == 'yes' ) {
				if ( $this->check_url( $src ) ) {
					
					$this->forceFilePutContents( $dst, file_get_contents( $src ) );
				}

				$this->conf->grablog .= $link . ' saved<br>';
			}
			
			$link = $this->conf->css_url . ltrim( $link, '/' );
		};

		return $this;
	}

	/**
	 * Store founded javascript resources locally
	 *
	 * @return $this
	 */
	private function storeFilesJs() {
		if ( !$this->parsed ) {
			return;
		}

		if ( $this->conf->js_path == '' ) {
			return;
		}

		foreach ( $this->parsed[ 'files-js' ] as &$link ) {
			
			if ( strpos( $link, '?' ) ) {
				$link = substr( $link, 0, strpos( $link, '?' ) );
			}

			$src = $this->conf->cs_path . $link;

			$dst = $this->conf->js_path . ltrim( $link, '/' );

			if ( (!file_exists( $dst ) || $this->conf->rewrite_on_download == 'yes') && file_exists( $src ) ) {
				if ( $this->check_url( $src ) ) {
					
					$this->forceFilePutContents( $dst, file_get_contents( $src ) );
				}

				$this->conf->grablog .= $link . ' saved<br>';
			}
			
			$link = $this->conf->js_url . ltrim( $link, '/' );
		};

		return $this;
	}

	/**
	 * Gran a post content by a selector in config
	 *
	 * @return $this
	 */
	private function getContent() {
		if ( !is_object( $this->html ) ) {
			return $this;
		}

		$content = $this->html->find( $this->conf->main_wrapper, 0 );
		if ( !is_object( $content ) ) {
			throw new Exception( 'Content not found by selector ' . $this->conf->main_wrapper );
		}

		// images
		if ( $this->conf->img_path != '' && $this->conf->img_url != '' ) {
			foreach ( $content->find( 'img' ) as $e ) {
				if ( !$this->check_url( $this->conf->cs_path . $e->src ) ) {
					continue;
				}

				$this->parsed[ 'img' ][] = $e->src;

				$img_binary = file_get_contents( $this->conf->cs_path . $e->src );

				$dst = $this->conf->img_path . $e->src;

				if ( !file_exists( $dst ) || $this->conf->rewrite_on_download == 'yes' ) {
					$this->forceFilePutContents( $dst, $img_binary );
					$this->conf->grablog .= $e->src . ' saved<br>';
				}

				if ( !strstr( $e->src, 'wp-content' ) ) {
					$e->src = $this->conf->img_url . $e->src;
				}
			}
		}

		//links
		if ( $this->conf->update_links == 'yes' ) {
			foreach ( $content->find( 'a' ) as $a ) {
				// skip for external links
				if ( strstr( $a->href, '://' ) && !strstr( $a->href, $this->conf->cs_path ) ) {
					continue;
				}

				$a->href = $this->getNewLinkHref( $a->href );
			}
		}

		// save object of content to html
		$this->parsed[ 'content' ] = $content->innertext;

		// find all background images
		$pattern = '/background[-image]*:\s*url\(\s*([\'"]*)(?P<file>[^\1]+)\1\s*\)/i';
		preg_match_all( $pattern, $this->parsed[ 'content' ], $founded, PREG_SET_ORDER );
		foreach ( $founded as $found ) {
			$file = trim( $found[ 'file' ], "\"'" );

			$this->parsed[ 'img' ][] = $file;

			$src = $this->conf->cs_path . trim( $file, "\"'" );

			$dst = $this->conf->img_path . $file;

			if ( !file_exists( $dst ) || $this->conf->rewrite_on_download == 'yes' ) {
				$this->conf->grablog .= $file . ' saved<br>';
				$img_binary = file_get_contents( $src );
				$this->forceFilePutContents( $dst, $img_binary );
			}

			$this->parsed[ 'content' ] = str_replace( $src, $this->conf->img_url . $file, $this->parsed[ 'content' ] );
		}

		return $this;
	}

	/**
	 * Find all selectors into the post content. Will used after to find intersect with css files. It will be inserted into the post.
	 *
	 * @return $this
	 */
	private function getContentSelectors() {
		if ( !is_object( $this->html ) ) {
			return $this;
		}

		if ( $this->conf->add_css_inline == 'yes' && $this->html->find( $this->conf->main_wrapper, 0 ) ) {
			$this->parsed[ 'selectors' ] = array();
			foreach ( $this->html->find( '*[class=*]' ) as $e ) {
				if ( !empty( $e->class ) ) {
					$this->parsed[ 'selectors' ][] = "." . $e->class;
				}
			}
			foreach ( $this->html->find( '*[id=*]' ) as $e ) {
				if ( !empty( $e->id ) ) {
					$this->parsed[ 'selectors' ][] = "#" . $e->id;
				}
			}
			$this->parsed[ 'selectors' ] = array_unique( $this->parsed[ 'selectors' ] );
		}

		return $this;
	}

	/**
	 * Return a new href for link with WP suburls.
	 * Means you have wp settings like `Admin->Settings->Permalink->Common Settings->Custom Structure->%category%/%postname%.
	 *
	 * @param string $href An original href of grabbed website
	 *
	 * @return string New href
	 */
	private function getNewLinkHref( $href = '' ) {
		if ( !isset( $this->conf->insert_cat_into_slug ) ) {
			$category = get_category( $this->conf->insert_cat_into, OBJECT );
			if ( !is_object( $category ) ) {
				return $href;
			} elseif ( $category->slug != '' ) {
				$this->conf->insert_cat_into_slug = trim( $category->slug, '/' );
			}
		}

		// skip if no category set
		if ( $this->conf->insert_cat_into_slug == '' ) {
			return $href;
		}

		// skip for external links
		if ( strstr( $href, '://' ) && !strstr( $href, $this->conf->cs_path ) ) {
			return $href;
		}

		$dir = ltrim( parse_url( $href, PHP_URL_PATH ), '/' );

		$href = '/' . $this->conf->insert_cat_into_slug . '/' . ltrim( $href, '/' );

		return $href;
	}

	/**
	 * Insert <script> and <link> into the post body.
	 *
	 * @return string New href
	 */
	private function attachResources() {

		if ( $this->conf->attachResources == '' )
			return $this;
		
		$attached = '';
		
		foreach ( $this->parsed[ 'files-css' ] as $src ) {
			
			$attached .= "";
		}

		foreach ( $this->parsed[ 'files-css' ] as $src ) {
			
			$attached .= "<link rel='stylesheet' type='text/css' href='$src' />\n";
		}
		
		$this->parsed[ 'content' ] = $attached . $this->parsed[ 'content' ];

		return $this;
	}

}
