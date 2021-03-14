<?php
namespace block_suap\task;

class notify extends \core\task\scheduled_task
{
    public function get_name()
    {
        return "Verifica e alerta cursos do SUAP não associados";
    }
    public function execute()
    { 
        global $CFG;
        global $DB;

        $notification="";
        
        if ($CFG->block_suap_crontab) {
            mtrace("Verificando cursos associados");

            require_once(__DIR__ . "/../../header.php");
            if($CFG->block_suap_auto_semestre_enabled){
                $ano = date("Y");
                $periodo = (date("m") > 6 ? "2" : "1");
            }else{
                $ano = $CFG->block_suap_auto_semestre_ano;
                $periodo = $CFG->block_suap_auto_semestre_semestre;
            }
            $url_suap=$CFG->wwwroot."/blocks/suap/listar_cursos.php?ano=".$ano ."&periodo=".$periodo;
            $notification_header="Você tem cursos com a importação do SUAP desativada por não ter categorias associadas.\n\n Para associar acesse $url_suap e faça a associação\n";
            foreach (\Curso::ler_rest($ano, $periodo) as $row) {
                if (!$row->ja_associado()) {
                    echo "\nVocê deve associar o curso " . $row->nome . " em " . $url_suap;
                    $notification.="\n" . $row->nome;
                }
            }
            if(!empty($notification)){
                echo "Preparando notificação";
                $admins = get_admins();
                foreach($admins as $admin){
                    $message = new \core\message\message();
                    $message->component = 'block_suap'; // Your plugin's name
                    $message->name = 'courseimport'; // Your notification name from message.php
                    $message->userfrom = \core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here
                    $message->userto = $admin;
                    $message->subject = 'Cursos do SUAP não associados';
                    $message->fullmessage = $notification_header.$notification;
                    $message->fullmessageformat = FORMAT_PLAIN;
                    $message->fullmessagehtml   = '';
                    $message->smallmessage      = '';
                    $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
                    //$content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
                    //$message->set_additional_content('email', $content);
                    message_send($message);
                }
                
            }

        }else{
            mtrace("Cron desabilitado, acesse '".$CFG->wwwroot."/admin/settings.php?section=blocksettingsuap' e ative a atualização pelo crontab");
        }
    }
}
