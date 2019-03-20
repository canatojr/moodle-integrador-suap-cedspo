<?php
require_once "header.php";
?>
    <h3>Listar campi</h3>
    <table class="table">
        <thead><tr><th>ID SUAP</th><th>Nome do campus</th><th>Sigla do campus</th></tr></thead>
        <tbody>
        <?php
        foreach (Campus::ler_rest() as $row):
            echo "<tr><td>{$row->id_on_suap}</td><td>{$row->nome}</td><td>{$row->sigla}</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
