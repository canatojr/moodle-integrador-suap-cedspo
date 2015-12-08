<?php
require_once("header.php");

function ano_periodo_render_selectbox($name) {
    global $suap_min_year, $current_year;
    $anos_periodos = [];
    for ($ano = (int)$suap_min_year; $ano<=(int)$current_year; $ano++) {
        $anos_periodos[]="$ano.1";
        $anos_periodos[]="$ano.2";
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
    Curso::auto_associar($id_curso, $ano_inicial, $periodo_inicial, $ano_final, $periodo_final);
    echo "<li>Fim.</li><li class='btn'><a href='../admin/purgecaches.php' target='_blank'>Deseja limpar o cache agora?</a></li></ol></div>";
} else {
    echo "<h3>Associar automaticamente as turmas e diários </h3><form method='POST'><dl>";
    echo "<dt>Curso: </dt><dd>" . Curso::ler_moodle($id_curso)->name . "</dd>";
    echo "<dt>Ano/Período inicial: </dt><dd>"; ano_periodo_render_selectbox('ano_periodo_inicial'); echo "</dd>";
    echo "<dt>Ano/Período final: </dt><dd>"; ano_periodo_render_selectbox('ano_periodo_final'); echo "</dd>";
    echo "<input type='submit' value='Associar'/>";
    echo "</dl></form>";
}
echo $OUTPUT->footer();
