<?php

/**
 * Abstract class of Grabber to work with config.
 *
 * @package Grabber
 */
class Grabber_Core_Abstract
{
    /**
     * Contains config data of current request.
     *
     * @var array
     */
    private $conf = null;

    /**
     * Represents a config data
     *
     * @return Grabber_Core_Abstract
     */
    public function __get($k)
    {
        if ($k == 'conf') {
            if (!isset($this->conf)) {
                $this->conf = Grabber_Core_Config::singletoneInstance();
            }

            return $this->conf;
        }
    }
}
