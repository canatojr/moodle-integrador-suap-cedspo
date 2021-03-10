<?php
namespace block_suap\task;

class cron_task extends \core\task\scheduled_task
{
    public function get_name()
    {
        return "Importa alunos e professores do SUAP";
    }

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
        mtrace("Importação SUAP>Moodle via cron iniciada");
        global $CFG;
        if (CLI_SCRIPT) {
            if ($CFG->block_suap_crontab) {
                $this->execute_and_print("php ".$CFG->dirroot . '/blocks/suap/cron.php');
                \core\task\manager::clear_static_caches();
                $this->execute_and_print("php ".$CFG->dirroot . '/admin/cli/purge_caches.php');
                $this->execute_and_print("php ".$CFG->dirroot . '/admin/cli/build_theme_css.php');
            } else {
                echo "\n\nCron Desabilitado\n";
            }
        }
        mtrace("Importação SUAP>Moodle via cron terminada");
    }
}
