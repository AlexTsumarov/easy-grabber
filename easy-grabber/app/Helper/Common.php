<?php
/**
 * Common helper.
 *
 * @package Grabber
 */
class Grabber_Helper_Common
{
    /**
     * Count body rows of the table.
     *
     * @param link $table Link to table array
     *
     * @return int
     */
    public function count(&$table)
    {
        $count             = isset($table[ 'body' ]) ? ' ('.sizeof($table[ 'body' ]).')' : '';

        return $count;
    }

    /**
     * Page reload after $sec seconds.
     *
     * @param int $sec Number of seconds.
     *
     * @return Grabber_Helper_Common
     */
    public function refreshEvery($sec = 1)
    {
        if ($sec < 1) {
            return $this;
        }

        echo "<script>
			setInterval( function () { window.location.href=window.location.href; }, ".$sec * 1000 .");
				</script>";

        return $this;
    }
}
