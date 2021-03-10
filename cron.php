<?php
define('CLI_SCRIPT', true);
require_once dirname(__FILE__) . "/header.php";

$ano = date("Y");
$periodo = (date("m") > 6 ? "2" : "1");

if($CFG->block_suap_auto_semestre_enabled){
    importar_diarios_suap($ano, $periodo);
}else{
    importar_diarios_suap($CFG->block_suap_auto_semestre_ano, $CFG->block_suap_auto_semestre_semestre);
}

function importar_diarios_suap($ano, $periodo, $continue=true){
    if($periodo > 1 && $continue==true){
        importar_diarios_suap($ano, $periodo-1, false);
    }elseif($continue==true){
        importar_diarios_suap($ano-1, $periodo+1, false);
    }
    $url_suap=$CFG->wwwroot."/blocks/suap/listar_cursos.php";
    foreach (Curso::ler_rest($ano, $periodo) as $row) {
        if ($row->ja_associado()) {
            (new Curso($row->id_on_suap))->importar($ano, $periodo);
        } else {
            echo "\nVocê deve associar o curso " . $row->nome . " em " . $url_suap;
        }
    }
    echo "\n\nImportação $ano.$periodo Conluída\n";
}

$ch = curl_init($CFG->wwwroot);
curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$page = curl_exec($ch);
curl_close($ch);

