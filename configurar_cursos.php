<?php
require_once "page_config.php";
require_once "models/integracao.php";
$PAGE->set_url(new moodle_url('/blocks/suap/configurar_cursos.php'));
echo $OUTPUT->header();
$cursos = rest_ler_cursos();
?>
    <h3>Configurar cursos</h3>
<?php foreach ($cursos as $key => $value): ?>
    <?php dump($value); ?>
<?php endforeach ?>
    <form name="form" id="form_importar" action="sincronizar.php" method="post">
        <div>
            <label for="dates">Ano/Periodo:</label>
            <select name="dates" id="dates"><?php echo receber_ano_options(); ?></select>
        </div>

        <div>
            <label for="cursos"><?php echo get_string('curso', 'block_enrollment') ?> : </label>
            <select name="cursos" id="cursos" class="cursos"><?php echo receber_cursos_options(); ?></select>
        </div>

        <div id='row_turmas'>
            <label>Turmas:</label>
            <ul id="turmas"></ul>
        </div>

        <div id='row_buttons'>
            <label></label>
            <input id="imp_diarios" name="imp_diarios" type="submit" value="Sincronizar diários"/>
        </div>

    </form>
<?php
echo $OUTPUT->footer();
