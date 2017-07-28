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
    
    function has_config() {
        return true;
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
        
        // apenas administradores
        if (!is_siteadmin()) {
            return false;
        }
        
        $this->content = new stdClass();
        $this->content->footer = '';
        
        $this->content->text = "<ul>";
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/listar_cursos.php\">Listar cursos SUAP</a></li>";
        $this->content->text .= "</ul>";
        
        if (isset($CFG->block_suap_id_campus)) {
            $this->content->text .= "<p>Config ID Campus: {$CFG->block_suap_id_campus}</p>";
        } else {
            $this->content->text .= "<p>Configure o ID do Campus.</p>";
        }

        return $this->content;
    }

}
