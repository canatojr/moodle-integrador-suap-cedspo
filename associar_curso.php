<?php
require_once("header.php");

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_curso).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_categoria = isset($_POST['categoria']) ? $_POST['categoria'] : die('Parâmetros incompletos (categoria).');
    Curso::associar($id_curso, $id_categoria);
}

$categorias = Curso::ler_categorias();
?>
    <h3>Associar curso '<?php echo $id_curso; ?>' à uma categoria</h3>
    <form method='POST'>
        <?php render_selectbox('categoria', $categorias, 'id', 'name', "curso:{$id_curso}", 'Escolha...', 'id_suap'); ?>
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
