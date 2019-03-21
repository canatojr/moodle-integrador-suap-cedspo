<?php
require_once "header.php";

$id_diario = isset($_GET['id_diario']) ? $_GET['id_diario'] : die('Parâmetros incompletos (id_diario).');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $curso = isset($_POST['curso']) ? $_POST['curso'] : die('Parâmetros incompletos (curso).');
    $curso_partes = explode(" :: ", $curso);
    $id_curso = $curso_partes[0];
    $diario = new Diario($id_diario);
    $diario->id_moodle = $id_curso;
    $diario->associar();
    //Diario::associar($id_diario, $id_curso);
    redirect('listar_cursos.php', 'Diário associado.', 5);
    exit;
}
?>
    <h3>Associar diário '<?php echo $id_diario; ?>' a um curso</h3>
    <form method='POST'>
        <?php render_datalist('curso', ler_courses(), 'id', 'fullname', "turma:{$id_diario}", 'id_suap'); ?>
        <input type='submit' value='Aplicar'/>
    </form>
<?php
echo $OUTPUT->footer();
