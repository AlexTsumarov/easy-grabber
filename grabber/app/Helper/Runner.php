<?php
/**
 * Runs number of new threads.
 *
 * @package Grabber
 */
class Grabber_Helper_Runner extends Grabber_Core_Abstract
{
    /**
     * Run `$this->conf->grab_threads` threads.
     *
     * @return Grabber_Helper_Runner
     */
    public function runParserTreads()
    {
        for ($i = 0; $i < $this->conf->grab_threads; $i++) {
            get_headers(admin_url('admin-ajax.php').'?action=grabber_parser&delay='.$this->conf->runner_grad_pause * $i);
        }

        echo "Added ".$this->conf->grab_threads." html threads <br>";

        return $this;
    }

    /**
     * Kiil all runned threads.
     *
     * @return Grabber_Helper_Runner
     */
    public function terminateParserThreads()
    {
        echo "Drop archieved threads <br>";

        $thread = new Grabber_Model_Thread($this->conf);

        $thread->deleteArchieved();

        return $this;
    }

    /**
     * Redirect after 1 second to queue admin page.
     *
     * @return Grabber_Helper_Runner
     */
    public function redirect()
    {
        echo "<script>
			setInterval( function () { window.location.href='?page={$this->conf->run_slug}'; }, 1000);
				</script>";

        return $this;
    }
}
