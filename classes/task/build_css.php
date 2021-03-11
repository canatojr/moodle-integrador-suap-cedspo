<?php
namespace block_suap\task;

class build_css extends \core\task\adhoc_task
{
    public function execute()
    { 
        mtrace("Compilando CSS");
        $this->popen("php ".$CFG->dirroot . '/admin/cli/build_theme_css.php', "r");
        mtrace("CSS compilado");
    }
}
