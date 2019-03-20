<?php
require_once "header.php";
?>
    <h3>Listar polos</h3>
    <table class="table">
        <thead><tr><th>ID SUAP</th><th>Nome do polo</th></tr></thead>
        <tbody>
        <?php
        foreach (Polo::ler_rest() as $row):
            echo "<tr><td>{$row->id_on_suap}</td><td>{$row->nome}</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
