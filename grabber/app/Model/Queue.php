<?php

class Grabber_Model_Queue extends Grabber_Core_Abstract
{
    public function addUrls($urls = null, $queueUrl = '')
    {
        if (!is_array($urls)) {
            return;
        }

        global $wpdb;

        $nested = $wpdb->get_row("SELECT * FROM {$this->conf->queue_table} where url = '$queueUrl' and parent is not null limit 1 ");
        if (is_object($nested) && $nested->ID > 0) {
            return;
        }

        $exist_urls     = array();
        if ($this->conf->skip_exist_in_hist == 'yes') {
            $sql         = "(select req_url as url from {$this->conf->log_table}) union (select url as url from {$this->conf->queue_table}) union (select req_url as url from {$this->conf->hist_table}) ";
        } else {
            $sql         = "(select req_url as url from {$this->conf->log_table}) union (select url as url from {$this->conf->queue_table}) ";
        }
        $r             = $wpdb->prepare($sql, array());
        $rows         = $wpdb->get_results($r);
        foreach ($rows as $row) {
            $exist_urls[] = ltrim($row->url, '/');
        }

        foreach ($urls as $url) {
            if (!in_array(ltrim($url, '/'), $exist_urls)) {
                if (strstr($url, '?')) {
                    $url = trim(substr($url, 0, strpos($url, '?')));
                }

                if (strstr($url, '#')) {
                    $url = trim(substr($url, 0, strpos($url, '#')));
                }

                $url = ltrim($url, '/');

                if (!empty($url)) {
                    $wpdb->insert($this->conf->queue_table, array( 'url' => $url, 'parent' => $queueUrl ), array( '%s', '%s' ));
                }
            }
        }
    }

    public function getUrlFromQueue($tid = null)
    {
        if (!$tid) {
            return;
        }

        global $wpdb;

        if ($this->conf->one_url_parse_timeout > 0) {
            $wpdb->query(" update {$this->conf->queue_table} set tid = 0 where tid > 0 and UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(ts) > {$this->conf->one_url_parse_timeout} ");
        }

        $row = $wpdb->get_row("SELECT * FROM {$this->conf->queue_table} where tid = 0 limit 1 ");

        if (is_object($row) && $row->ID > 0) {
            $wpdb->update($this->conf->queue_table, array( 'tid' => $tid ), array( 'ID' => $row->ID ), array( '%d' ), array( '%d' ));

            return $row->url;
        }

        return;
    }

    public function releaseUrl($url = null)
    {
        if (!$url) {
            return;
        }

        global $wpdb;

        $wpdb->update($this->conf->queue_table, array( 'tid' => 0 ), array( 'url' => $url ), array( '%d' ), array( '%s' ));

        return;
    }

    public function urlDone($data = null)
    {
        if (!$data) {
            return;
        }

        global $wpdb;

        $queueItem = $wpdb->get_row("SELECT * FROM {$this->conf->queue_table} where url = '{$data[ 'req_url' ]}' ");

        $wpdb->query(" delete from {$this->conf->queue_table} where url = '{$data[ 'req_url' ]}' ");

        if (is_object($queueItem) && $queueItem->parent && $queueItem->parent != $data[ 'req_url' ]) {
            $data[ 'notes' ] .= "<br>parent: ".$queueItem->parent;
        }

        $wpdb->insert($this->conf->log_table, $data, array( '%s', '%s', '%s', '%s' ));

        return;
    }

    public function fillQueue()
    {
        global $wpdb;

        $arr = explode("\n", $this->conf->grab_list);

        foreach ($arr as $url) {
            $wpdb->insert($this->conf->queue_table, array( 'url' => $url ), array( '%s' ));
        }

        return $this;
    }

    public function truncateQueue()
    {
        global $wpdb;

        $wpdb->query(" truncate {$this->conf->queue_table} ");

        return $this;
    }

    public function getQueue()
    {
        global $wpdb;

        $table = array(
            'head'     => array(
                'url'     => 'URL',
                'ts'     => 'DATE',
                //'status' => 'STATUS',
                'parent' => 'PARENT',
                'tid'     => 'THREAD ID',
            ),
            'body'     => array(),
        );

        $sql = "
			SELECT {$this->conf->queue_table}.*,
				if({$this->conf->thread_table}.status='finished','parser fail',if({$this->conf->thread_table}.tid=0,'done','wait')) as status
			FROM {$this->conf->queue_table}
			left join {$this->conf->thread_table} on {$this->conf->thread_table}.TID = {$this->conf->queue_table}.tid
			";

        $rows = $wpdb->get_results($sql);

        foreach ($rows as $row) {
            $row->url = "<a href='{$this->conf->cs_path}{$row->url}' target=_Blank>{$row->url}</a>";

            $table[ 'body' ][] = (array) $row;
        }

        return $table;
    }

    public function count()
    {
        global $wpdb;

        $row = $wpdb->get_row(" select count(*) as cn from {$this->conf->queue_table} ", ARRAY_A);

        return $row[ 'cn' ];
    }
}
