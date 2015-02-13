<?php

/**
 * Parse grabbed styles into separate variable
 *
 * @package Grabber
 */
class Grabber_Parser_Css extends Grabber_Parser_Abstract
{
    //url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)
    const bgimage_pattren = '/url\(([\s])?([\"|\'])?(?P<file>.*?)([\"|\'])?([\s])?\)/i';

    /**
     * Generate a css string with founded css selectors and find intersect with a styles into attached css files.
     *
     * @return string Log messages
     */
    public function generate()
    {
        if ($this->conf->add_css_inline != 'yes') {
            return;
        }

        if (!isset($this->parsed[ 'content' ])) {
            return;
        }

        $global_css             = '';
        $composed_css         = '';
        $reached_css         = array();
        $reached_selectors     = array();

        foreach ($this->parsed[ 'files-css' ] as $file) {
            if (file_exists($this->conf->css_path.$file)) {
                $global_css .= file_get_contents($this->conf->css_path.$file);
            }
        }
        $global_css_array     = $this->BreakCSS($global_css);
        $global_selectors     = array_keys($global_css_array);

        // find if parsed selector are occur into global selector list
        foreach ($global_selectors as $global_selector) {
            foreach ($this->parsed[ 'selectors' ] as $parsed_selector) {
                if (strstr($parsed_selector, $global_selector)) {
                    if (!isset($reached_selectors[ $global_selector ])) {
                        $reached_selectors[ $global_selector ] = $global_css_array[ $global_selector ];
                    } else {
                        $reached_selectors[ $global_selector ] = array_merge($global_css_array[ $global_selector ], (array) $reached_selectors[ $global_selector ]);
                    };
                }
            }
        }

        // collect exist inline styles in string
        foreach ($this->parsed[ 'styles' ] as $css_string) {
            $css_string = $css_string;
            if (!empty($css_string)) {
                $composed_css .= $css_string;
            }
        };

        // collect founded global styles in string
        foreach ($reached_selectors as $selector => $rules) {
            $composed_css .= "$selector{ ";
            foreach ($rules as $key => $val) {
                $composed_css .= "$key: $val;\n ";
            }
            $composed_css .= "}";
        };

        $composed_css = str_replace(array("  ", "\n", "\r", "\t"), "", $composed_css);
        $composed_css = str_replace("{", "{\n  ", $composed_css);
        $composed_css = str_replace(";", ";\n  ", $composed_css);
        $composed_css = str_replace("}", "\n}\n", $composed_css);

        $this->parsed[ 'composed_css' ] = $composed_css;

        $this->saveBackgroundImages();

        return $this;
    }

    /**
     * Save background images and replace url to downloaded
     *
     * @return void
     */
    private function saveBackgroundImages()
    {
        // find all background images into the style
        preg_match_all(Grabber_Parser_Css::bgimage_pattren, $this->parsed[ 'composed_css' ], $founded, PREG_SET_ORDER);
        if (is_array($founded)) {
            foreach ($founded as $found) {
                $file = trim($found[ 'file' ], "\"'");

                $this->parsed[ 'img' ][] = $file;

                $src = trim($file, "\"'");

                $dst = $this->conf->img_path.$file;

                if (!file_exists($dst) || $this->conf->rewrite_on_download == 'yes') {
                    $this->conf->grablog .= $file.' saved<br>';

                    if ($this->check_url($this->conf->cs_path.$src)) {
                        $img_binary = file_get_contents($this->conf->cs_path.$src);
                        $this->forceFilePutContents($dst, $img_binary);
                    }
                }

                $this->parsed[ 'composed_css' ] = str_replace($src, $this->conf->img_url.$file, $this->parsed[ 'composed_css' ]);
            }
        }

        // find all background images into the content
        preg_match_all(Grabber_Parser_Css::bgimage_pattren, $this->parsed[ 'content' ], $founded, PREG_SET_ORDER);
        if (is_array($founded)) {
            foreach ($founded as $found) {
                $file = trim($found[ 'file' ], "\"'");

                $this->parsed[ 'img' ][] = $file;

                $src = trim($file, "\"'");

                $dst = $this->conf->img_path.$file;

                if (!file_exists($dst) || $this->conf->rewrite_on_download == 'yes') {
                    $this->conf->grablog .= $file.' saved<br>';

                    if ($this->check_url($this->conf->cs_path.$src)) {
                        $img_binary = file_get_contents($this->conf->cs_path.$src);
                        $this->forceFilePutContents($dst, $img_binary);
                    }
                }

                $this->parsed[ 'content' ] = str_replace($src, $this->conf->img_url.$file, $this->parsed[ 'content' ]);
            }
        }
    }

    /**
     * Generate associative array selector:rule from css string
     *
     * @return array Associative array selector:rule
     */
    private function BreakCSS($css)
    {
        $results = array();

        preg_match_all('/(.+?)\s?\{\s?(.+?)\s?\}/', $css, $matches);
        foreach ($matches[ 0 ] as $i => $original) {
            foreach (explode(';', $matches[ 2 ][ $i ]) as $attr) {
                if (strlen(trim($attr)) > 0) { // for missing semicolon on last element, which is legal
                    list($name, $value) = explode(':', $attr);
                    $results[ $matches[ 1 ][ $i ] ][ trim($name) ] = trim($value);
                }
            }
        }

        return $results;
    }
}
