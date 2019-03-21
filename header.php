<?php
require_once dirname(__FILE__) . '/../../config.php';
//require_once("config.php");
@error_reporting(E_ALL | E_STRICT);
//@error_reporting(E_ERROR);
@ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
//$CFG->debug = (E_ERROR);
$CFG->debugdisplay = 1;

require_once "models.php";

$suap_min_year = $CFG->block_suap_min_year;
$current_year = date("Y");

if (!CLI_SCRIPT) :
    require_login();

    $context = context_system::instance();

    if (!is_siteadmin()) {
        print_error(get_string('notallowed', 'block_suap'));
    }

    $PAGE->set_context($context);
    // $PAGE->set_pagelayout('standard');
    $PAGE->set_title("Integração com SUAP");
    $PAGE->set_url(new moodle_url('/suap/index.php'));
    $PAGE->set_heading('Integração com SUAP');
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    // $PAGE->requires->js("/suap/js/json.js");
    // $PAGE->requires->js_init_call('M.block_suap.init');
    $PAGE->requires->css('/suap/style.css');
    echo $OUTPUT->header();
    ?>
<ul class="nav tab">
    <li><a href="listar_cursos.php" class="btn">Listar cursos</a></li>
    <li><a href="listar_campus.php" class="btn">Listar campus</a></li>
    <li><a href="listar_polos.php" class="btn">Listar polos</a></li>
</ul>
    <?php
endif;
