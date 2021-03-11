<?php
namespace block_suap\task;

class cron extends \core\task\scheduled_task
{
    public function get_name()
    {
        return "Importa alunos e professores do SUAP";
    }

    public function execute()
    { 
        mtrace("Agendando tarefa de importação");
        $task = new import();
        $task->set_next_run_time(time() + 1 * MINSECS);
        $task->set_blocking(true);
        $task->set_fail_delay(3600);
        \core\task\manager::reschedule_or_queue_adhoc_task($task);
        mtrace("Tarefa agendada");
    }
}


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
