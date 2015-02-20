<?php

class Grabber_Model_Log extends Grabber_Core_Abstract
{
    public function truncateLog()
    {
        global $wpdb;

        $logs = $wpdb->get_results("SELECT * FROM {$this->conf->log_table} ");
        foreach ($logs as $log) {
            if ($log->wp_id > 0) {
                $wpdb->insert($this->conf->hist_table, array( 'req_url' => $log->req_url, 'wp_id' => $log->wp_id, 'notes' => $log->notes ), array( '%s', '%d', '%s' ));
            }
        }

        $wpdb->query(" truncate {$this->conf->log_table} ");

        return $this;
    }

    public function getLog()
    {
        global $wpdb;

        $table = array(
            'head'     => array(
                'req_url'     => 'REQ URL',
                'wp_url'     => 'WP URL',
                'notes'         => 'NOTES',
            ),
            'body'     => array(),
        );

        $sql = "
			SELECT {$this->conf->log_table}.* from {$this->conf->log_table}";

        $r = $wpdb->prepare($sql, array());

        $allposts = $wpdb->get_results($r);

        foreach ($allposts as $row) {
            if (! get_post($row->wp_id)) {
                $wpdb->query("delete from {$this->conf->log_table} where wp_id = '{$row->wp_id}' ");
                continue;
            }

            $row->wp_url = "<a target='_blank' href='".get_permalink($row->wp_id)."'>".parse_url(get_permalink($row->wp_id), PHP_URL_PATH)."</a>";

            $row->req_url = "<a href='{$this->conf->cs_path}{$row->req_url}' target=_Blank>{$row->req_url}</a>";

            $table[ 'body' ][] = (array) $row;
        }

        return $table;
    }

    public function getHistory()
    {
        global $wpdb;

        $table = array(
            'head'     => array(
                'req_url'     => 'REQ URL',
                'wp_url'     => 'WP URL',
                'notes'         => 'NOTES',
            ),
            'body'     => array(),
        );

        $sql = "
			SELECT {$this->conf->hist_table}.* from {$this->conf->hist_table}";

        $r = $wpdb->prepare($sql, array());

        $allposts = $wpdb->get_results($r);

        foreach ($allposts as $row) {
            if (! get_post($row->wp_id)) {
                $wpdb->query("delete from {$this->conf->hist_table} where wp_id = '{$row->wp_id}' ");
                continue;
            }

            $row->wp_url = "<a target='_blank' href='".get_permalink($row->wp_id)."'>".parse_url(get_permalink($row->wp_id), PHP_URL_PATH)."</a>";

            $row->req_url = "<a href='{$this->conf->cs_path}{$row->req_url}' target=_Blank>{$row->req_url}</a>";

            $table[ 'body' ][] = (array) $row;
        }

        return $table;
    }
}
