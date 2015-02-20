<?php

class Grabber_Controller_Install extends Grabber_Core_Abstract
{
    /**
     * Constructor for class Grabber_Controller_Install.
     *
     * @return Grabber_Controller_Install
     */
    public function __construct()
    {
        global $wpdb;
        $this->conf->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * Create custom tables and set the required options.
     *
     * @return void
     */
    public function install()
    {
        global $wpdb;

        require_once ABSPATH.'wp-admin/includes/upgrade.php';

        $sql = "
CREATE TABLE if not exists {$this->conf->log_table} (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`req_url` VARCHAR(2048) NULL DEFAULT NULL,
	`wp_id` INT(11) NULL DEFAULT NULL,
	`notes` VARCHAR(2048) NULL DEFAULT NULL,
	PRIMARY KEY (`ID`)
)
{$this->conf->charset_collate}
;";
        dbDelta($sql);

        $sql = "
CREATE TABLE if not exists {$this->conf->hist_table} (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`req_url` VARCHAR(2048) NULL DEFAULT NULL,
	`wp_id` INT(11) NULL DEFAULT NULL,
	`notes` VARCHAR(2048) NULL DEFAULT NULL,
	PRIMARY KEY (`ID`)
)
{$this->conf->charset_collate}
;";
        dbDelta($sql);

        $sql = "
CREATE TABLE if not exists {$this->conf->queue_table} (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`url` VARCHAR(255) NULL DEFAULT NULL,
	`tid` INT(11) NULL DEFAULT '0',
	`ts` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`parent` VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE INDEX `url` (`url`)
)
{$this->conf->charset_collate}
;";
        dbDelta($sql);

        $sql = "
CREATE TABLE if not exists {$this->conf->thread_table} (
	`TID` INT(11) NOT NULL AUTO_INCREMENT,
	`pid` INT(11) NOT NULL DEFAULT '0',
	`ts` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`duration` FLOAT NULL DEFAULT '0',
	`status` VARCHAR(50) NULL DEFAULT 'work',
	`log` VARCHAR(4096) NULL DEFAULT NULL,
	PRIMARY KEY (`TID`),
	INDEX `pid` (`pid`),
	INDEX `status` (`status`)
)
{$this->conf->charset_collate}
";
        dbDelta($sql);
    }
}
