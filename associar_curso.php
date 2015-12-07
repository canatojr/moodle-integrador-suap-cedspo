<?php
require_once("../config.php");
require_once("models.php");

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_curso).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_categoria = isset($_POST['categoria']) ? $_POST['categoria'] : die('Parâmetros incompletos (categoria).');
    Curso::associar($id_curso, $id_categoria);
    header("Location: listar_cursos.php");
    exit;
}

require_once("header.php");
$categorias = Curso::ler_categorias();
?>
    <h3>Associar curso '<?php echo $id_curso; ?>' à uma categoria</h3>
    <form method='POST'>
        <?php Category::render_selectbox() ?>
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
