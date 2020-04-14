<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_suap_upgrade($oldversion)
{
    global $DB, $CFG;

    if ($oldversion < 2020041002) {
        $dbman = $DB->get_manager();
        $table_category = new xmldb_table('course_categories');
        $table_course = new xmldb_table('course');
        $idsuap = new xmldb_field('id_suap', XMLDB_TYPE_CHAR, '100', null, null, null, null, null);
        $custom_css = new xmldb_field('custom_css', XMLDB_TYPE_CHAR, '100', null, null, null, '', null);

        if (!$dbman->field_exists($table_course, $idsuap)) {
              $dbman->add_field($table_course, $idsuap);
        }
        if (!$dbman->field_exists($table_category, $idsuap)) {
            $dbman->add_field($table_category, $idsuap);
        }
        if (!$dbman->field_exists($table_category, $custom_css)) {
            $dbman->add_field($table_category, $custom_css);
        }
        upgrade_plugin_savepoint(true, 2020041002, 'block', 'suap');
	}
	
	
	//NOVAS VERSÕES VEM AQUI PARA NÂO QUEBRAR A ATUALIZAÇÂO

    return true;
}
