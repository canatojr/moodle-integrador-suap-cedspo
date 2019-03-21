<?php
require_once "header.php";
$id_curso = get_or_die('id_curso');
$codigo = get_or_die('codigo');
$ano = get_or_die('ano');
$periodo = get_or_die('periodo');
$curso = new Curso($id_curso, $codigo, $ano, $periodo);
$curso->ler_moodle();
?>
    <h3>Listar turmas do turma '<?php echo $curso->name ?>' para ofertas em '<?php echo "{$ano}.{$periodo}"; ?>'.</h3>
    <table class="table">
        <thead><tr><th>ID SUAP</th><th>Código</th><th>Ações</th></tr></thead>
        <tbody>
        <?php
        foreach (Turma::ler_rest($id_curso, $ano, $periodo, $curso) as $row):
            echo "<tr><td>{$row->id_on_suap}</td><td>{$row->codigo}</td><td>";
            if ($row->ja_associado()) {
                echo "<a href='importar_diario.php?id_turma={$row->id_on_suap}&id_curso={$id_curso}&codigo={$row->codigo}' class='btn btn-mini btn-success'>Importar</a>";
                echo "<a href='listar_diarios.php?id_turma={$row->id_on_suap}&codigo={$row->codigo}' class='btn btn-mini'>Diários</a>";
            } else {
                echo "<a href='criar_turma.php?id_curso={$id_curso}&ano_letivo={$ano}&periodo_letivo={$periodo}&id_turma={$row->id_on_suap}&codigo=$codigo&codigo_turma={$row->codigo}' class='btn btn-mini btn-success'>Criar</a>";
                echo "<a href='associar_turma.php?id_curso={$id_curso}&ano_letivo={$ano}&periodo_letivo={$periodo}&id_turma={$row->id_on_suap}&codigo={$row->codigo}' class='btn btn-mini btn-success'>Associar</a>";
                echo "<a href='listar_diarios.php?id_turma={$row->id_on_suap}&codigo={$row->codigo}&apenasver=1' class='btn btn-mini'>Diários</a>";
            }
            echo "</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
