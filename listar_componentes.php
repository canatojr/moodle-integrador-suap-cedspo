<?php
require_once "header.php";

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die('Parâmetros incompletos (id_curso).');
?>
    <h3>Listar componentes curriculares do curso <?php echo $id_curso ?> </h3>
    <table class="table">
        <thead><tr><th>ID SUAP</th><th>Período</th><th>Sigla</th><th>Descrição</th><th>Qtd. avaliações</th></tr></thead>
        <tbody>
        <?php
        foreach (ComponenteCurricular::ler_rest($id_curso) as $row):
            $periodo = $row->optativo ? 'Optativo' : "$row->periodo&ordm;";
            echo "<tr><td>{$row->id_on_suap}</td><td>{$periodo}</td><td>{$row->sigla}</td><td>{$row->descricao}</td><td>{$row->qtd_avaliacoes}</td></tr>";
        endforeach
        ?>
        </tbody>
    </table>
<?php
echo $OUTPUT->footer();
