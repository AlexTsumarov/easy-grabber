<?php

class Grabber_Model_Thread extends Grabber_Core_Abstract
{
    public function deleteArchieved()
    {
        global $wpdb;

        $wpdb->query("delete FROM {$this->conf->thread_table}  ");
    }

    public function getThread()
    {
        global $wpdb;

        $table = array(
            'head' => array(
                'TID' => 'ID',
                'ts' => 'DATE',
                'duration' => 'DURATION',
                'status' => 'STATUS',
                'log' => 'LOG',
            ),
            'body' => array(),
        );

        //$r = $wpdb->prepare("SELECT * FROM {$this->conf->thread_table} where status <> 'finished' order by TID desc ", array());
        $r = $wpdb->prepare("SELECT * FROM {$this->conf->thread_table} order by TID desc ", array());

        $allposts = $wpdb->get_results($r);

        foreach ($allposts as $singlepost) {
            $table['body'][] = (array) $singlepost;
        }

        return $table;
    }

    public function isAllDead()
    {
        global $wpdb;

        $row = $wpdb->get_row(" select max(round( ( unix_timestamp(now()) - unix_timestamp(ts) - duration ) )) as sec from grabber_thread where status='work' ", ARRAY_A);

        if (! isset($row['sec'])) {
            return true;
        } elseif ($row['sec'] > $this->conf->thread_norespond_timeout) {
            return true;
        } else {
            return false;
        }
    }

    public function isDead()
    {
        global $wpdb;

        $row = $wpdb->get_row(" select max(round( ( unix_timestamp(now()) - unix_timestamp(ts) - duration ) )) as sec from grabber_thread where TID = '{$this->conf->TID}' ", ARRAY_A);

        if (! isset($row['sec'])) {
            return true;
        } elseif ($row['sec'] > $this->conf->thread_norespond_timeout) {
            return true;
        } else {
            return false;
        }
    }
}
