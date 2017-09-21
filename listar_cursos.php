<?php
require_once("header.php");

$ano = isset($_GET['ano']) ? $_GET['ano'] : $current_year;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : null;
?>
    <h3>Listar cursos</h3>
    <form>
        <dl class='oneline'>
            <dt>Ingresso no ano:</dt>
            <dd><input name='ano' type='number' min='<?php echo $suap_min_year; ?>' max='<?php echo $current_year; ?>'
                       step='1' value="<?php echo $ano; ?>"/></dd>
            <dt>Período:</dt>
            <dd><input name='periodo' type='number' min='1' max='2' step='1' value="<?php echo $periodo; ?>"/></dd>
            <dt>&nbsp;</dt>
            <dd><input type='submit' value="Filtrar"/></dd>
        </dl>
    </form>
<?php
if ($periodo) { 
    echo "<h3>Cursos presentes no SUAP de $ano.$periodo</h3>";
    echo "<table class='table'><thead><tr><th>Código</th><th>Nome</th><th>Ação</th></tr></thead><tbody>";
    foreach (Curso::ler_rest($ano, $periodo) as $row) {
            echo "<tr><td>{$row->codigo}<br>(S: {$row->id_on_suap})</td><td><b>{$row->nome}</b><br />{$row->descricao}</td>";
            echo "<td>";
            if ($row->ja_associado()) {
                echo "<a href='importar_diario.php?id_curso={$row->id_on_suap}&ano={$ano}&periodo={$periodo}' class='btn btn-mini btn-success'>Importar</a>";
                echo "<a href='auto_associar.php?id_curso={$row->id_on_suap}' class='btn btn-mini btn-info'>Auto associar</a><br/>";
                echo "<a href='listar_turmas.php?id_curso={$row->id_on_suap}&codigo={$row->codigo}&ano={$ano}&periodo={$periodo}' class='btn btn-mini'>Turmas</a>";
            } else {
                echo "<a href='associar_curso.php?id_curso={$row->id_on_suap}' class='btn btn-mini btn-success'>Associar</a><br/>";
            }
            echo "<a href='listar_componentes.php?id_curso={$row->id_on_suap}&ano={$ano}&periodo={$periodo}' class='btn btn-mini'>Componentes</a>";
            echo "</td></tr>";

    }
    echo "</tbody></table>";
} else {
  echo "<h3>Escolha um ano e um período.</h3>";
}
echo $OUTPUT->footer();
