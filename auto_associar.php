<?php
require_once("header.php");

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : null;
$id_turma = isset($_GET['id_turma']) ? $_GET['id_turma'] : null;
$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;
$id_diario = isset($_GET['id_diario']) ? $_GET['id_diario'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<div class='log'>";
    echo "<ol>";
    echo "<li>Inicio.</li>";
    if ($id_turma && $id_diario) {
        Diario::importar($id_turma, $id_diario);
    } else if ($id_curso && $id_turma && $codigo) {
        Turma::importar($id_curso, $id_turma, $codigo);
    } else if ($id_curso) {
        Curso::importar($id_curso, $ano, $periodo);
    } else {
        die('Informe um curso (+ ano e periodo), turma (+ curso e codigo) ou diário (+ turma).');
    }
    echo "<li>Fim.</li>";
    echo "<li class='btn'><a href='../admin/purgecaches.php' target='_blank'>Deseja limpar o cache agora?</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    if (!$id_curso && !$id_turma && !$id_diario) {
        die('Informe um curso (+ ano e periodo), turma (+ curso e codigo) ou diário (+ turma).');
    }
    echo "<h3>Confirmar a importação </h3><dl>";
    echo $id_curso ? "<dt>Curso: </dt><dd>" . Curso::ler_moodle($id_curso)->name . " ($ano.$periodo)</dd>" : "";
    echo $id_turma ? "<dt>Turma: </dt><dd>$id_turma</dd>" : "";
    echo $codigo ? "<dt>Código da turma: </dt><dd>$codigo</dd>" : "";
    echo $id_diario ? "<dt>Diário: </dt><dd>$id_diario</dd>" : "";
    echo "</dl>";
    ?>
    <form method='POST'>
        <input type='submit' value='Importar'/>
    </form>
    <?php
}
echo $OUTPUT->footer();
