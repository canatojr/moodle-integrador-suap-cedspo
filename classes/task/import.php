<?php
namespace block_suap\task;

class import extends \core\task\adhoc_task
{
    public function get_name()
    {
        return "Importa alunos e professores do SUAP";
    }
    public function execute()
    { 
        global $CFG;
        global $DB;
        $data = $this->get_custom_data();

        $notification="";
        
        if (CLI_SCRIPT) {
            mtrace("Importação SUAP>Moodle via cron iniciada");

            require_once(__DIR__ . "/../../header.php");
            $url_suap=$CFG->wwwroot."/blocks/suap/listar_cursos.php?ano=".$data->{'ano'}."&periodo=".$data->{'periodo'};
            $notification_header="Você tem cursos com a importação do SUAP desativada por não ter categorias associadas.\n\n Para associar acesse $url_suap e faça a associação";
            foreach (\Curso::ler_rest($data->{'ano'}, $data->{'periodo'}) as $row) {
                if ($row->ja_associado()) {
                    (new \Curso($row->id_on_suap))->importar($data->{'ano'}, $data->{'periodo'});
                } else {
                    echo "\nVocê deve associar o curso " . $row->nome . " em " . $url_suap;
                    $notification+="\n" . $row->nome;
                }
            }
            if(!empty($notification)){
                $message = new \core\message\message();
                $message->component = 'block_suap'; // Your plugin's name
                $message->name = 'courseimport'; // Your notification name from message.php
                $message->userfrom = core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here
                $message->userto = get_admins();
                $message->subject = 'Cursos do SUAP não associados';
                $message->fullmessage = $notification;
                $message->fullmessageformat = FORMAT_PLAIN;
                $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
                //$content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
                //$message->set_additional_content('email', $content);
            }

            mtrace("\nImportação SUAP>Moodle via cron terminada");
            if($data->{'clean'}){
                mtrace("Agendando tarefa de limpeza");
                $task = new \block_suap\task\clean_cache();
                $task->set_next_run_time(time() + 1 * MINSECS);
                \core\task\manager::reschedule_or_queue_adhoc_task($task);
                mtrace("Tarefa agendada");
            }
        }
        
        
    }
}
