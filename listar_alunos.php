<?php
require_once "header.php";

$id_diario = isset($_GET['id_diario']) ? $_GET['id_diario'] : die('Parâmetros incompletos (id_diario).');
?>
    <h3>Listar alunos</h3>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach (Aluno::ler_rest($id_diario) as $row):
            echo "<tr><td>{$row->id}</td><td>{$row->nome}</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
