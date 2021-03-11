<?php
namespace block_suap\task;

class clean_cache extends \core\task\adhoc_task
{
    public function execute()
    { 
        mtrace("Limpando cache");
        $this->popen("php ".$CFG->dirroot . '/admin/cli/purge_caches.php', "r");
        mtrace("Cache limpo");
        mtrace("Agendando tarefa");
        $task = new \block_suap\task\build_css();
        $task->set_next_run_time(time() + 1 * MINSECS);
        \core\task\manager::reschedule_or_queue_adhoc_task($task);
        mtrace("Tarefa agendada");
    }
}

class build_css extends \core\task\adhoc_task
{
    public function execute()
    { 
        mtrace("Compilando CSS");
        $this->popen("php ".$CFG->dirroot . '/admin/cli/build_theme_css.php', "r");
        mtrace("CSS compilado");
    }
}
