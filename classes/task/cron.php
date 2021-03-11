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
        global $CFG;
        mtrace("Agendando tarefas de importação");

        $task = new import();
        $task->set_next_run_time(time() + 1 * MINSECS);
        $task->set_blocking(true);
        $task->set_fail_delay(3600);
        
        $task2 = new import();
        $task2->set_next_run_time(time() + 16 * MINSECS);
        $task2->set_blocking(true);
        $task2->set_fail_delay(3600);
        

        if($CFG->block_suap_auto_semestre_enabled){
            $ano = date("Y");
            $periodo = (date("m") > 6 ? "2" : "1");
        }else{
            $ano = $CFG->block_suap_auto_semestre_ano;
            $periodo = $CFG->block_suap_auto_semestre_semestre;
        }

        $task->set_custom_data(array(
                'ano' => $ano,
                'periodo' => $periodo,
                'clean' => false
        ));

        if($periodo > 1){
            $periodo = $periodo-1;
        }else{
            $ano = $ano-1;
            $periodo = $periodo+1;
        }

        $task2->set_custom_data(array(
                'ano' => $ano,
                'periodo' => $periodo,
                'clean' => true
        ));

        \core\task\manager::reschedule_or_queue_adhoc_task($task);
        \core\task\manager::reschedule_or_queue_adhoc_task($task2);
        mtrace("Tarefas agendadas");
    }
}
