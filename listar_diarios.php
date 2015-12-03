<?php
require_once("header.php");

$id_turma = isset($_GET['id_turma']) ? $_GET['id_turma'] : die('Parâmetros incompletos (id_turma).');
?>
<h3>Listar diário da turma '<?php echo Turma::ler_moodle($id_turma)->name; ?>'</h3>
<table class="table">
  <thead>
    <tr><th>ID</th><th>Sigla</th><th>Situacao</th><th>Descrição</th><th>Ação</th></tr>
  </thead>
  <tbody>
    <?php
    foreach (Diario::ler_rest($id_turma) as $row):
      echo "<tr><td>{$row->id}</td><td>{$row->sigla}</td><td>{$row->situacao}</td><td>{$row->descricao}</td>";
      echo "<td>";
      if ($row->ja_associado()) {
        echo "<a href='importar_diario.php?id_diario={$row->id}&id_turma={$id_turma}' class='btn btn-mini btn-success'>Importar</a>";
        echo "<a href='listar_professores.php?id_diario={$row->id}' class='btn btn-mini'>Professores</a>";
        echo "<a href='listar_alunos.php?id_diario={$row->id}' class='btn btn-mini'>Alunos</a></td>";
      } else {
        echo "<a href='associar_diario.php?id_diario={$row->id}' class='btn btn-mini btn-success'>Associar</a>";
      }
      echo "</tr>";
    endforeach
    ?>
  </tbody>
</table>
<?php
echo $OUTPUT->footer();
