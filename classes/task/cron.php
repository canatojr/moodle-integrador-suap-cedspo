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
        if ($CFG->block_suap_crontab) {
            mtrace("Agendando tarefas de importação");
        
            $datets=time() + 1 * MINSECS;
            $datets2=time() + 16 * MINSECS;

            $date= new \DateTime;
            $date->setTimestamp($datets);
            $date2= new \DateTime;
            $date2->setTimestamp($datets2);


            $task = new import();
            $task->set_next_run_time($datets);
            $task->set_blocking(true);
            $task->set_fail_delay(3600);
            
            $task2 = new import();
            
            $task2->set_next_run_time($datets2);
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
            mtrace("Agendando importação de ".$ano."/".$periodo." para iniciar em ".$date->format('d/m/Y H:i:s'));
            \core\task\manager::reschedule_or_queue_adhoc_task($task);

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

            mtrace("Agendando importação de ".$ano."/".$periodo." para iniciar em ".$date2->format('d/m/Y H:i:s'));
            \core\task\manager::reschedule_or_queue_adhoc_task($task2);
            mtrace("Tarefas agendadas");
            mtrace("Você pode ver a execução das tarefas a partir do horário agendado acima em '".$CFG->wwwroot."/admin/tool/task/runningtasks.php'". " e após a conclusão da tarefa os logs estarão disponíveis em '".$CFG->wwwroot."/admin/tasklogs.php?filter=block_suap%5Ctask%5Cimport'");
        }else {
            mtrace("Cron desabilitado, acesse '".$CFG->wwwroot."/admin/settings.php?section=blocksettingsuap' e ative a atualização pelo crontab");
        }
    }
}
