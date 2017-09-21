<?php
require_once("header.php");
$id_turma = get_or_die('id_turma');
$codigo = get_or_die('codigo');
$turma = new Turma($id_turma, $codigo);
$turma->ler_moodle();

echo "<h3>Listar diário da turma '{$turma->codigo}'</h3>";
echo "<table class='table'><thead><tr><th>ID SUAP</th><th>Sigla</th><th>Código</th><th>Situacao</th><th>Descrição</th><th>Ação</th></tr></thead><tbody>";
foreach (Diario::ler_rest($turma) as $diario) {
    echo "<tr><td>{$diario->id_on_suap}</td><td>{$diario->sigla}</td><td>{$codigo}.{$diario->sigla}</td><td>{$diario->situacao}</td><td>{$diario->descricao}</td>";
    echo "<td>";
    echo "<a href='importar_diario.php?id_diario={$diario->id_on_suap}&sigla={$diario->sigla}&id_turma={$id_turma}&codigo={$codigo}' class='btn btn-mini btn-success'>Importar</a>";
    if (!$diario->ja_associado()) {
        echo "<a href='associar_diario.php?id_diario={$diario->id_on_suap}' class='btn btn-mini btn-default'>Associar</a>";
    }
    echo "</td></tr>";
}
echo "</tbody></table>";
echo $OUTPUT->footer();
