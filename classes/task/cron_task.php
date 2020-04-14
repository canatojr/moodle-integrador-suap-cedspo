<?php
namespace block_suap\task;

class cron_task extends \core\task\scheduled_task
{

    public function get_name()
    {
        return "Importa alunos e professores do SUAP";
    }

    public function execute()
    {   
        global $CFG;
        if(CLI_SCRIPT) {
            if($CFG->block_suap_crontab) {
                $handle = popen("php ".$CFG->dirroot . '/blocks/suap/cron.php', "r");
                while( !feof($handle) ){
                    echo fread($handle, 1024);
                    flush();
                }
                fclose($fp);
            }else{
                echo "\n\nCron Desabilitado\n";    
            }
        }
        
    }
}

