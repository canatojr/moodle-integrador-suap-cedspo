<?php
require_once("header.php");

set_time_limit(300);

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : null;
$id_turma = isset($_GET['id_turma']) ? $_GET['id_turma'] : null;
$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;
$id_diario = isset($_GET['id_diario']) ? $_GET['id_diario'] : null;
$sigla = isset($_GET['sigla']) ? $_GET['sigla'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : null;
$situacao = null;
$descricao = null;

$curso = null;
$turma = null;
$diario = null;
$objeto = null;

if ($id_turma && $id_diario && $sigla) {
    $turma = (new Turma($id_turma, $codigo))->ler_moodle();
    foreach (Diario::ler_rest($turma) as $diario) {
      if ($diario->id_on_suap == $id_diario) {
         $diario = $diario->ler_moodle();
         break;
      }
    }
    if (!$diario) {die("Diário não encontrado.");}  
    $objeto = $diario;
} else if ($id_curso && $id_turma && $codigo) {
    $turma = (new Turma($id_turma, $codigo))->ler_moodle();
    $objeto = $turma;
} else if ($id_curso && $ano && $periodo) {
    $curso = (new Curso($id_curso))->ler_moodle();
    $objeto = $curso;
} 

if (!$objeto) {
    die('Informe um curso (+ ano e periodo), turma (+ curso e codigo) ou diário (+ turma e sigla).');
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>Processando a importação</h3>";
    echo "<ol>";
    $objeto->importar($ano, $periodo);
    echo "</ol>";
    echo "<a class='btn' href='../admin/purgecaches.php' target='_blank'>Deseja limpar o cache agora?</a>";
    echo "</div>";
} else {
    echo "<h3>Confirmar a importação</h3>";
    echo "<ol>";
    $objeto->preview($ano, $periodo);
    echo "</ol>";
    echo "<form method='POST'><input type='submit' value='Importar'/></form>";
}

echo $OUTPUT->footer();
