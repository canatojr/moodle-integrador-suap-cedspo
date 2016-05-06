<?php
$suap_prefix_url = "https://suap.ifrn.edu.br/edu/api";
$suap_token = "********************************";
$suap_min_year = '2013';
$current_year = date("Y");
// Valid constant names
define("SUAP_ID_CAMPUS_EAD", '14');
define("NIVEL_CURSO", 2);
define("CONTEXT_CATEGORY", '40');
define("CONTEXT_COURSE", '50');

$enrol_roleid = ['Moderador' => 20, 'Principal' => 3, 'Aluno' => 5];
$enrol_type = ['Moderador' => 'manual', 'Principal' => 'manual', 'Aluno' => 'manual'];
$default_user_preferences = ['auth_forcepasswordchange'=>'0', 'htmleditor'=>'0', 'email_bounce_count'=>'1', 'email_send_count'=>'1'];
