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
        if($CFG->block_suap_crontab == 1) {
	    echo shell_exec("php ".$CFG->dirroot . '/blocks/suap/cron.php');
        }else{
            echo "\n\nCron Desabilitado\n";    
        }
        
    }
}

