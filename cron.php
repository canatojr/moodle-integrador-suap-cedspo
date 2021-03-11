<?php
define('CLI_SCRIPT', true);
require_once dirname(__FILE__) . "/header.php";


public function execute_and_print($command)
    {
        $handle = popen($command, "r");
        while (!feof($handle)) {
            echo fread($handle, 1024);
            flush();
        }
                fclose($handle);
    }

execute_and_print("php ".$CFG->dirroot . "/admin/cli/purge_caches.php --execute='\block_suap\task\cron'");


