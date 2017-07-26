<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/suap/lib.php');

class block_suap extends block_base
{

    function init()
    {
        $this->title = get_string('suap', 'block_suap');
        $this->version = 2017072600;
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
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/listar_cursos.php\">Listar cursos SUAP</a></li>";
        $this->content->text .= "</ul>";

        return $this->content;
    }

}
