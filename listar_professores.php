<?php
require_once "header.php";

$id_diario = isset($_GET['id_diario']) ? $_GET['id_diario'] : die('Parâmetros incompletos (id_diario).');
?>
    <h3>Listar professores</h3>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Login</th>
            <th>Tipo</th>
            <th>Email</th>
            <th>Email Secundário</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach (Professor::ler_rest($id_diario) as $row):
            echo "<tr><td>{$row->id}</td><td>{$row->nome}</td><td>{$row->login}</td><td>{$row->tipo}</td>";
            echo "<td>{$row->email}</td><td>{$row->email_secundario}</td><td>{$row->status}</td>";
            echo "<td><a>Sincronizar</a></td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
