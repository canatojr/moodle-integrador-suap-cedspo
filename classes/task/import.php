<?php
namespace block_suap\task;

class import extends \core\task\adhoc_task
{
    public function execute()
    { 
        global $CFG;
        global $DB;
        $data = $this->get_custom_data();
        
        if (CLI_SCRIPT) {
            if ($CFG->block_suap_crontab) {
                mtrace("Importação SUAP>Moodle via cron iniciada");

                require_once(__DIR__ . "/../../header.php");
                $url_suap=$CFG->wwwroot."/blocks/suap/listar_cursos.php";
                foreach (\Curso::ler_rest($data->{'ano'}, $data->{'periodo'}) as $row) {
                    if ($row->ja_associado()) {
                        (new \Curso($row->id_on_suap))->importar($data->{'ano'}, $data->{'periodo'});
                    } else {
                        echo "\nVocê deve associar o curso " . $row->nome . " em " . $url_suap;
                    }
                }


                mtrace("Importação SUAP>Moodle via cron terminada");
                if($data->{'clean'}){
                    mtrace("Agendando tarefa de limpeza");
                    $task = new \block_suap\task\clean_cache();
                    $task->set_next_run_time(time() + 1 * MINSECS);
                    \core\task\manager::reschedule_or_queue_adhoc_task($task);
                    mtrace("Tarefa agendada");
                }
                
            } else {
                mtrace("Cron Desabilitado");
            }
        }
        
        
    }
}
