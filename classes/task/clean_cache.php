<?php
namespace block_suap\task;

class clean_cache extends \core\task\adhoc_task
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
        mtrace("Limpando cache");
        $this->execute_and_print("php ".$CFG->dirroot . '/admin/cli/purge_caches.php', "r");
        \core\task\manager::clear_static_caches();
        mtrace("Cache limpo");

        mtrace("Agendando tarefa para compilar CSS");
        $task = new \block_suap\task\build_css();
        $task->set_next_run_time(time() + 1 * MINSECS);
        \core\task\manager::reschedule_or_queue_adhoc_task($task);
        mtrace("Tarefa agendada"); 
    }
}