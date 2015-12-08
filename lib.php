<?php

$suap_prefix_url = "https://suap.ifrn.edu.br/edu/api";
$suap_token = "676367c4877e17034690cac73d386921f49b78a9";
$suap_id_campus_ead = '14';
$suap_min_year = '2013';
$current_year = date("Y");
$contexto_turma_moodle = '40';
$contexto_diario_moodle = '50';

$enrol_roleid = ['Moderador' => 20, 'Principal' => 3, 'Aluno' => 5];
$enrol_type = ['Moderador' => 'manual', 'Principal' => 'manual', 'Aluno' => 'manual'];
$default_user_preferences = ['auth_forcepasswordchange'=>'0', 'htmleditor'=>'0', 'email_bounce_count'=>'1', 'email_send_count'=>'1'];


function dump()
{
    echo '<pre>';
    foreach (func_get_args() as $key => $value) {
        var_dump($value);
    }
    echo '</pre>';
}

function dumpd()
{
    echo '<pre>';
    foreach (func_get_args() as $key => $value) {
        var_dump($value);
    }
    echo '</pre>';
    die();
}

function raise_error()
{
    echo '<pre>';
    foreach (func_get_args() as $key => $value) {
        var_dump($value);
    }
    echo '</pre>';
    die();
}

function json_request($service, $params)
{
    global $suap_prefix_url, $suap_token;

    $curl = curl_init("$suap_prefix_url/$service/");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json",
        "Authorization: Token $suap_token"));
    curl_setopt($curl, CURLOPT_POST, true);

    if (isset($params)) {
        $content = json_encode($params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    }
    $json_response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($status != 200) {
        die("REST ERRO: $status");
    }
    if (substr($json_response, 0, 8) == '{"erro":') {
        $erro = json_decode($json_response, true);
        die("REST ERRO: " . $erro['erro']);
    }

    $result = json_decode($json_response, true);

    return count($result) == 1 && array_key_exists(0, $result) ? [] : $result;
}

function render_selectbox($name, $list, $prop_id = 'id', $prop_name = 'name', $selected_value = null, $vazio = 'Escolha...', $another_key = null)
{
    if (in_array($name, $_REQUEST) && !$selected_value) {
        $selected_value = $_REQUEST[$name];
    }
    echo "<select name='$name'>";
    if ($vazio) {
        echo "<option>$vazio</option>";
    }
    echo $selected_value;
    foreach ($list as $elem) {
        $value = $elem->{$prop_id};
        $label = $elem->{$prop_name};
        $key_value = $another_key ? $elem->{$another_key} : $value;
        $selected = $key_value == $selected_value ? " selected='selected' " : "";
        echo "<option value='$value' $selected >$label</option>";
    }
    echo "</select>";
}

function render_datalist($name, $list, $prop_id = 'id', $prop_name = 'name', $selected_value = null, $another_key = null)
{
    if (in_array($name, $_REQUEST) && !$selected_value) {
        $selected_value = $_REQUEST[$name];
    }
    echo "<input list='datalist_$name' name='$name' size='150'>";
    echo "<datalist id='datalist_$name'>";
    echo $selected_value;
    foreach ($list as $elem) {
        $value = $elem->{$prop_id};
        $label = $elem->{$prop_name};
        $key_value = $another_key ? $elem->{$another_key} : $value;
        echo "<option value='$value :: $label' />";
    }
    echo "</datalist>";
}
