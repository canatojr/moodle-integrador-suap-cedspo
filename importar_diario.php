<?php
require_once "header.php";

set_time_limit(600);

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
    if ($id_curso && $id_turma && $codigo) {
        $turma = new Turma($id_turma, $codigo, $id_curso);
        $turma->importar();
    } elseif ($id_turma && $id_diario) {
        $turma = new Turma($id_turma, $codigo);
        $diario = new Diario($id_diario, null, null, null, $turma->ler_moodle());
        $diario->ler_moodle()->importar();
    } elseif ($id_curso) {
        (new Curso($id_curso))->importar($ano, $periodo);
    } else {
        die('Informe um curso (+ ano e periodo), turma (+ curso e codigo) ou diário (+ turma).');
    }
    echo "<li>Fim.</li>";
    echo "<li class='btn'><a href='../../admin/purgecaches.php' target='_blank'>Deseja limpar o cache agora?</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    if (!$id_curso && !$id_turma && !$id_diario) {
        die('Informe um curso (+ ano e periodo), turma (+ curso e codigo) ou diário (+ turma).');
    }
    echo "<h3>Confirmar a importação </h3><dl>";
    echo $id_curso ? "<dt>Curso: </dt><dd>" . (new Curso($id_curso))->ler_moodle()->name . "</dd>" : "";
    echo $id_turma ? "<dt>ID da turma no SUAP: </dt><dd>$id_turma</dd>" : "";
    echo $codigo ? "<dt>Código da turma no SUAP: </dt><dd>$codigo</dd>" : "";
    echo $id_diario ? "<dt>ID do diário no SUAP: </dt><dd>$id_diario</dd>" : "";
    echo "</dl>"; ?>
    <form method='POST'>
        <input type='submit' value='Importar'/>
    </form>
    <?php
}
echo $OUTPUT->footer();
