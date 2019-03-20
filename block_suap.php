<?php

require_once dirname(__FILE__) . '/../../config.php';
require_once $CFG->dirroot . '/blocks/suap/lib.php';

class block_suap extends block_base
{
    public function init()
    {
        $this->title = get_string('suap', 'block_suap');
        $this->version = 2017072600;
    }

    public function applicable_formats()
    {
        return array('all' => true);
    }
    
    public function has_config()
    {
        return true;
    }

    public function get_content()
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
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/listar_cursos.php\">Listar Cursos do SUAP</a></li>";
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/listar_campus.php\">Listar Câmpus</a></li>";
        $this->content->text .= "<li><a href=\"{$CFG->wwwroot}/blocks/suap/listar_polos.php\">Listar Polos</a></li>";
        $this->content->text .= "</ul>";
        
        $this->content->text .= "<p>Consulte seu ID de Câmpus e configure na <a href=\"{$CFG->wwwroot}/admin/settings.php?section=blocksettingsuap\">Administração do Bloco</a>.</p>";
        

        return $this->content;
    }
}
