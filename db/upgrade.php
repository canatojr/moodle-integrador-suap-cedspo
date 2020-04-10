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
              echo "Criando id_suap na tabela course\n";
              $dbman->add_field($table_course, $idsuap);
        }else{
            echo "id_suap na tabela course já existe\n";
        }
        if (!$dbman->field_exists($table_category, $idsuap)) {
            echo "Criando id_suap na tabela course_category\n";
            $dbman->add_field($table_category, $idsuap);
        }else{
            echo "id_suap na tabela course_category já existe\n";
        }
        if (!$dbman->field_exists($table_category, $custom_css)) {
            echo "Criando custom_css na tabela course_category\n";
            $dbman->add_field($table_category, $custom_css);
        }else{
            echo "custom_css na tabela course já existe\n";
        }
        upgrade_plugin_savepoint(true, 2020041002, 'block', 'suap');
    }
    return true;
}
