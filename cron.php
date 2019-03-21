<?php
define('CLI_SCRIPT', true);
require_once "header.php";
$ano = date("Y");
$periodo = (date("m") > 6 ? "2" : "1");
foreach (Curso::ler_rest($ano, $periodo) as $row):
    (new Curso($row->id_on_suap))->importar($ano, $periodo);
endforeach;
