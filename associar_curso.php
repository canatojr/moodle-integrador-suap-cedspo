<?php
//require_once("config.php");
require_once "models.php";

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_curso).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_categoria = isset($_POST['categoria']) ? $_POST['categoria'] : die('Parâmetros incompletos (categoria).');
    $curso = new Curso($id_curso);
    $curso->id_moodle = $id_categoria;
    $curso->associar();
    //header("Location: listar_cursos.php");
    redirect('listar_cursos.php', 'Curso associado.', 5);
    exit;
}

require_once "header.php";
?>
    <h3>Associar curso '<?php echo $id_curso; ?>' a uma categoria</h3>
    <form method='POST'>
        <?php Category::render_selectbox(NIVEL_CURSO) ?>
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
