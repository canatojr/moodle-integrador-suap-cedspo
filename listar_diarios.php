<?php
require_once "header.php";
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
        foreach (Diario::ler_rest($turma) as $diario):
            echo "<tr><td>{$diario->id_on_suap}</td><td>{$diario->sigla}</td><td>{$codigo}.{$diario->sigla}</td><td>{$diario->situacao}</td><td>{$diario->descricao}</td>";
            echo "<td>";
            if ($diario->ja_associado()) {
                echo "<a href='importar_diario.php?id_diario={$diario->id_on_suap}&id_turma={$id_turma}&codigo={$codigo}' class='btn btn-mini btn-success'>Importar</a>";
            } else {
                echo "<a href='associar_diario.php?id_diario={$diario->id_on_suap}' class='btn btn-mini btn-success'>Associar</a>";
            }
            echo "</tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
