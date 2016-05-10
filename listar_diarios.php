<?php
require_once("header.php");
$id_turma = get_or_die('id_turma');
$codigo = get_or_die('codigo');
$turma = new Turma($id_turma, $codigo);
$turma->ler_moodle();
?>
    <h3>Listar diário da turma '<?php echo $turma->name; ?>'</h3>
    <table class="table">
        <thead><tr><th>ID SUAP</th><th>Sigla</th><th>Código</th><th>Situacao</th><th>Descrição</th><th>Ação</th></tr></thead>
        <tbody>
        <?php
        foreach (Diario::ler_rest($turma) as $row):
            echo "<tr><td>{$row->id_on_suap}</td><td>{$row->sigla}</td><td>{$codigo}.{$row->sigla}</td><td>{$row->situacao}</td><td>{$row->descricao}</td>";
            echo "<td>";
            if ($row->ja_associado()) {
                echo "<a href='importar_diario.php?id_diario={$row->id_on_suap}&id_turma={$id_turma}' class='btn btn-mini btn-success'>Importar</a>";
//                echo "<a href='listar_professores.php?id_diario={$row->id_on_suap}' class='btn btn-mini'>Professores</a>";
//                echo "<a href='listar_alunos.php?id_diario={$row->id_on_suap}' class='btn btn-mini'>Alunos</a></td>";
            } else {
                echo "<a href='associar_diario.php?id_diario={$row->id_on_suap}' class='btn btn-mini btn-success'>Associar</a>";
            }
            echo "</tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
