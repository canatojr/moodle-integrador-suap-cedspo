<?php
require_once "header.php";
require_once $CFG->libdir . '/coursecatlib.php';
$options = coursecat::make_categories_list('moodle/category:manage');

$id_turma = isset($_GET['id_turma']) ? $_GET['id_turma'] : die('Parâmetros incompletos (id_turma).');
$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_turma).');
$ano_letivo = isset($_GET['ano_letivo']) ? $_GET['ano_letivo'] : die('Parâmetros incompletos (id_turma).');
$periodo_letivo = isset($_GET['periodo_letivo']) ? $_GET['periodo_letivo'] : die('Parâmetros incompletos (id_turma).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_categoria = isset($_POST['categoria']) ? $_POST['categoria'] : die('Parâmetros incompletos (categoria).');
    $turmas =  Turma::ler_rest($id_curso, $ano_letivo, $periodo_letivo);
    foreach ($turmas as $turma) {
        if ($turma->id_on_suap == $id_turma) {
            $turma->id_moodle = $id_categoria;
            $turma->associar();
            ob_clean();
            //header("Location: listar_cursos.php?ano={$ano_letivo}&periodo={$periodo_letivo}");
            redirect("listar_cursos.php?ano={$ano_letivo}&periodo={$periodo_letivo}", 'Turma associada.', 5);
            exit;
        }
    }
    echo "Ocorreu um erro.";
}

$categorias = ler_categories();
?>
    <h3>Associar turma '<?php echo $id_turma; ?>' à uma categoria</h3>
    <form method='POST'>
        <?php render_selectbox('categoria', $categorias, 'id', 'name', "turma:{$id_turma}", 'Escolha...', 'id_suap'); ?>
    <input type='hidden' name='id_curso' value='<?php echo $id_curso; ?>' />
    <input type='hidden' name='ano_letivo' value='<?php echo $ano_letivo; ?>' />
    <input type='hidden' name='periodo_letivo' value='<?php echo $periodo_letivo; ?>' />
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
