<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/suap/lib.php');

class block_suap extends block_base
{

    function init()
    {
        $this->title = get_string('pluginname', 'block_suap');
    }

    public function applicable_formats()
    {
        return array('all' => true);
    }

    function get_content()
    {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        // shortcut -  only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = "<ul>";
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/configurar_cursos.php\">Configurar cursos</a></li>";
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/sincronizar_diarios.php\">Sincronizar diáris</a></li>";
        $this->content->text .= "</ul>";

        return $this->content;
    }

}
