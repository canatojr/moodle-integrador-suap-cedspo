<?php
require_once("header.php");

$id_turma = isset($_GET['id_turma']) ? $_GET['id_turma'] : die('Parâmetros incompletos (id_turma).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_categoria = isset($_POST['categoria']) ? $_POST['categoria'] : die('Parâmetros incompletos (categoria).');
    Turma::associar($id_turma, $id_categoria);
}

$categorias = Curso::ler_categorias();
?>
    <h3>Associar curso '<?php echo $id_turma; ?>' à uma categoria</h3>
    <form method='POST'>
        <?php render_selectbox('categoria', $categorias, 'id', 'name', "turma:{$id_turma}", 'Escolha...', 'id_suap'); ?>
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
