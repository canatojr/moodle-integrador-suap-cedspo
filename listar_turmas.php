<?php
require_once("header.php");

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_curso).');
$ano = isset($_GET['ano']) ? $_GET['ano'] :  die('Parâmetros incompletos (ano).');
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : die('Parâmetros incompletos (periodo).');
?>
<h3>Listar turmas do turma '<?php echo Curso::ler_moodle($id_curso)->name ?>'</h3>
<table class="table">
  <thead>
    <tr><th>ID</th><th>Código</th><th>Ações</th></tr>
  </thead>
  <tbody>
    <?php
    foreach (Turma::ler_rest($id_curso, $ano, $periodo) as $row):
      echo "<tr><td>{$row->id}</td><td>{$row->codigo}</td>";
      echo "<td>";
      if ($row->ja_associado()) {
        echo "<a href='importar_diario.php?id_turma={$row->id}&id_curso=$id_curso&codigo={$row->codigo}' class='btn btn-mini btn-success'>Importar</a>";
        echo "<a href='listar_diarios.php?id_turma={$row->id}' class='btn btn-mini'>Diários</a>";
      } else {
        echo "<a href='associar_turma.php?id_turma={$row->id}' class='btn btn-mini btn-success'>Associar</a>";
      }
      echo "</td></tr>";
    endforeach
    ?>
  </tbody>
</table>
<?php
echo $OUTPUT->footer();
