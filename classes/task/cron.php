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
