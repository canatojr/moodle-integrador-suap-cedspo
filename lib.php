<?php
require_once dirname(__FILE__) . '/../../config.php';
//require_once('config.php');

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
    global $CFG;

    $curl = curl_init("$CFG->block_suap_url_api/$service/");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $curl,
        CURLOPT_HTTPHEADER,
        array("Content-type: application/json",
        "Authorization: Token $CFG->block_suap_token")
    );
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
