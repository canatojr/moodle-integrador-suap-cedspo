<?php
namespace block_suap\task;

class build_css extends \core\task\adhoc_task
{
    public function execute_and_print($command)
    {
        $handle = popen($command, "r");
        while (!feof($handle)) {
            echo fread($handle, 1024);
            flush();
        }
                fclose($handle);
    }

    public function execute()
    { 
        mtrace("Compilando CSS");
        $this->execute_and_print("php ".$CFG->dirroot . '/admin/cli/build_theme_css.php', "r");
        mtrace("CSS compilado");
    }
}
