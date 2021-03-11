<?php
namespace block_suap\task;

class import extends \core\task\adhoc_task
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
        global $CFG;
        if (CLI_SCRIPT) {
            if ($CFG->block_suap_crontab) {
                mtrace("Importação SUAP>Moodle via cron iniciada");
                $this->execute_and_print("php ".$CFG->dirroot . '/blocks/suap/cron.php');
                mtrace("Importação SUAP>Moodle via cron terminada");
                mtrace("Agendando tarefa de limpeza");
                $task = new \block_suap\task\clean_cache();
                $task->set_next_run_time(time() + 1 * MINSECS);
                \core\task\manager::reschedule_or_queue_adhoc_task($task);
                mtrace("Tarefa agendada");
            } else {
                mtrace("Cron Desabilitado");
            }
        }
        
        
    }
}
