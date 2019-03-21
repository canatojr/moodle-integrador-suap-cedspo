<?php
require_once "header.php";

$ano = isset($_GET['ano']) ? $_GET['ano'] : $current_year;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '1';
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
    <table class="table">
        <thead><tr><th>ID</th><th>Código</th><th>Nome</th><th>Descrição</th><th>Ação</th></tr></thead>
        <tbody>
        <?php
        foreach (Curso::ler_rest($ano, $periodo) as $row):
            echo "<tr><td>{$row->id_on_suap}</td><td>{$row->codigo}</td><td>{$row->nome}</td><td>{$row->descricao}</td>";
            echo "<td>";
            if ($row->ja_associado()) {
                echo "<a href='importar_diario.php?id_curso={$row->id_on_suap}&ano={$ano}&periodo={$periodo}' class='btn btn-mini btn-success'>Importar</a>";
                echo "<a href='auto_associar.php?id_curso={$row->id_on_suap}' class='btn btn-mini btn-info'>Auto associar</a>";
                echo "<a href='listar_turmas.php?id_curso={$row->id_on_suap}&codigo={$row->codigo}&ano={$ano}&periodo={$periodo}' class='btn btn-mini'>Turmas</a>";
            } else {
                echo "<a href='associar_curso.php?id_curso={$row->id_on_suap}' class='btn btn-mini btn-success'>Associar</a>";
            }
            echo "<a href='listar_componentes.php?id_curso={$row->id_on_suap}&ano={$ano}&periodo={$periodo}' class='btn btn-mini'>Componentes</a>";
            echo "</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
