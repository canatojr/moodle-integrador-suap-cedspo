<?php
require_once "header.php";

require_once "models.php";

$id_curso =  get_or_die('id_curso');
$codigo = get_or_die('codigo');
$ano = get_or_die('ano_letivo');
$periodo = get_or_die('periodo_letivo');
$id_turma = get_or_die('id_turma');
$codigo_turma = get_or_die('codigo_turma');

$curso = new Curso($id_curso);
$curso->ler_moodle();

$turma = new Turma($id_curso, $codigo_turma, $curso);
$turma->criar();


/*

    $turmas =  Turma::ler_rest($id_curso, $ano_letivo, $periodo_letivo);
    foreach ($turmas as $turma) {
        if ($turma->id_on_suap == $id_turma) {
            $turma->id_moodle = $id_categoria;
            $turma->associar();
            ob_clean();
            header("Location: listar_cursos.php?ano={$ano_letivo}&periodo={$periodo_letivo}");
            exit;
        }
    }
    echo "Ocorreu um erro.";
}
*/
echo "Criado com sucesso. <a href='listar_turmas.php?id_curso=$id_curso&ano=$ano&periodo=$periodo'>Voltar</a>";
?>

<?php
echo $OUTPUT->footer();
