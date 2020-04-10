<?php
define('CLI_SCRIPT', true);
require_once "header.php";
if($CFG->block_suap_crontab == 1) {
    $ano = date("Y");
    $periodo = (date("m") > 6 ? "2" : "1");
    $url_suap=$CFG->wwwroot."/blocks/suap/listar_cursos.php";
    foreach (Curso::ler_rest($ano, $periodo) as $row) {
        if ($row->ja_associado()) {
            (new Curso($row->id_on_suap))->importar($ano, $periodo);
        }else{
            echo "\nVocê deve associar o curso " . $row->nome . " em " . $url_suap;
        }
    }

    $ch = curl_init($CFG->wwwroot);
    curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $page = curl_exec($ch);
    curl_close($ch);
    echo "\n\nCron Conluído\n";
}else{
    echo "\n\nCron Desabilitado\n";    
}