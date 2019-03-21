<?php
require_once "header.php";

function ano_periodo_render_selectbox($name)
{
    global $suap_min_year, $current_year;
    $anos_periodos = [];
    for ($ano = (int)$current_year; $ano>=(int)$suap_min_year; $ano--) {
        $anos_periodos[]="$ano.2";
        $anos_periodos[]="$ano.1";
    }
    echo "<select name='{$name}' required>";
    foreach ($anos_periodos as $ano_periodo) :
        echo "<option value='{$ano_periodo}'>{$ano_periodo}</option>";
    endforeach;
    echo "</select>";
}

$id_curso = isset($_GET['id_curso']) ? $_GET['id_curso'] : die("Informe um curso.");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ano_periodo_inicial = isset($_POST['ano_periodo_inicial']) ? $_POST['ano_periodo_inicial'] : die("Informe o ano + período inicial");
    $ano_periodo_final = isset($_POST['ano_periodo_final']) ? $_POST['ano_periodo_final'] : die("Informe o ano + período final");

    if ((float)$ano_periodo_inicial > (float)$ano_periodo_final) {
        die("Ano/Período inicial deve ser inferior ao Ano/Período final");
    }

    $ano_inicial = explode('.', $ano_periodo_inicial)[0];
    $periodo_inicial = explode('.', $ano_periodo_inicial)[1];
    $ano_final = explode('.', $ano_periodo_final)[0];
    $periodo_final = explode('.', $ano_periodo_final)[1];
    echo "<div class='log'><ol><li>Inicio.</li>";
    (new Curso($id_curso))->auto_associar($ano_inicial, $periodo_inicial, $ano_final, $periodo_final);
    echo "<li>Fim.</li>";
    // echo "<li class='btn'><a href='../admin/purgecaches.php' target='_blank'>Deseja limpar o cache agora?</a></li>";
    echo "</ol></div>";
} else {
    echo "<h3>Associar automaticamente os diários </h3><form method='POST'><dl>";
    echo "<p>Caso existam <b>categorias com idnumber no Moodle</b> iguais aos <b>códigos da turma no SUAP</b> esta categoria será associada a uma turma.
    Caso existam <b>courses com idnumber no Moodle</b> iguais aos <b>códigos completo do diários no SUAP</b> estes courses serão associados a um diários.
    Fora isso, nada mais será alterado, criado ou excluído.<p>";
    echo "<dt>Curso: </dt><dd>" . (new Curso($id_curso))->ler_moodle()->name . "</dd>";
    echo "<dt>Ano/Período inicial: </dt><dd>";
    ano_periodo_render_selectbox('ano_periodo_inicial');
    echo "</dd>";
    echo "<dt>Ano/Período final: </dt><dd>";
    ano_periodo_render_selectbox('ano_periodo_final');
    echo "</dd>";
    echo "<input type='submit' value='Associar'/>";
    echo "</dl></form>";
}
echo $OUTPUT->footer();
