<?php

class Grabber_Parser_Abstract extends Grabber_Core_Abstract
{
    protected $parsed     = null;

    public function getData()
    {
        return $this->parsed;
    }

    public function setData(&$link)
    {
        $this->parsed = &$link;

        return $this;
    }

    public function __toArray()
    {
        return getData();
    }

    protected function check_url_exc($url)
    {
        $headers = @get_headers($url);

        if (strpos($headers[ 0 ], '200') === false) {
            throw new Exception("Wrong headers: ".$headers[ 0 ]);
        }
    }

    public static function check_url($url)
    {
        $headers = @get_headers($url);

        if (strpos($headers[ 0 ], '200') === false) {
            return false;
        } else {
            return true;
        }
    }

    protected function forceFilePutContents($filepath, $content)
    {
        if (strstr($filepath, 'http://')) {
            return;
        }

        if (strstr($filepath, 'https://')) {
            return;
        }

        try {
            $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $filepath, $filepathMatches);
            if ($isInFolder) {
                $folderName     = $filepathMatches[ 1 ];
                $fileName     = $filepathMatches[ 2 ];
                if (!is_dir($folderName)) {
                    @mkdir($folderName, 0777, true);
                }
            }
            @file_put_contents($filepath, $content);
        } catch (Exception $e) {
        }
    }
}
