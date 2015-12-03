<?php
require_once($CFG->dirroot . '/suap/lib.php');

$role_mapping = ['Principal'=>4, 'Moderador'=>3];

function criar_contexto($contextlevel, $instanceid, $base_path)
{
    global $DB;
    $context = new stdClass();
    $context->contextlevel = $contextlevel;
    $context->instanceid = $instanceid;
    $context->depth = count(explode('/', $base_path));
    $context->id = $DB->insert_record('context', $context);
    $context->path = "{$base_path}/{$context->id}";
    $DB->update_record('context', $context);
}


class AbstractEntity
{
    protected static function ler_rest_generico($service, $id, $class, $properties)
    {
        try {
            $response = json_request($service, array('id_diario' => $id));
            $result = array();
            foreach ($response as $id => $obj) {
                $instance = new $class($id);
                dump($obj, $instance);
                foreach ($properties as $property) {
                    $instance->{$property} = $obj[$property];
                }
                $result[] = $instance;
            }
            return $result;
        } catch (Exception $e) {
            dump_error($e);
        }
    }

}


class Polo
{
    public $id;
    public $nome;

    public function __construct($id, $nome)
    {
        $this->id = $id;
        $this->nome = $nome;
    }

    public static function ler_rest()
    {
        $response = json_request("listar_polos_ead");
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new Polo($id, $obj['descricao']);
        }
        return $result;
    }
}


class Campus
{
    public $id;
    public $nome;
    public $sigla;

    public function __construct($id, $nome, $sigla)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->sigla = $sigla;
    }

    public static function ler_rest()
    {
        $response = json_request("listar_campus_ead");
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new Campus($id, $obj['descricao'], $obj['sigla']);
        }
        return $result;
    }
}


class ComponenteCurricular
{
    public $id;
    public $tipo;
    public $periodo;
    public $qtd_avaliacoes;
    public $descricao_historico;
    public $optativo;
    public $descricao;
    public $sigla;

    public function __construct($id, $tipo, $periodo, $qtd_avaliacoes, $descricao_historico, $optativo, $descricao, $sigla)
    {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->periodo = $periodo;
        $this->qtd_avaliacoes = $qtd_avaliacoes;
        $this->descricao_historico = $descricao_historico;
        $this->optativo = $optativo;
        $this->descricao = $descricao;
        $this->sigla = $sigla;
    }

    public static function ler_rest($id_curso)
    {
        $response = json_request("listar_componentes_curriculares_ead", array(
            'id_curso' => $id_curso));
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new ComponenteCurricular($id, $obj['tipo'], $obj['periodo'], $obj['qtd_avaliacoes'], $obj['descricao_historico'], $obj['optativo'], $obj['descricao'], $obj['sigla']);
        }
        return $result;
    }

}


class Curso
{
    public $id;
    public $nome;
    public $descricao;
    public $codigo;

    public function __construct($id, $nome, $descricao, $codigo)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->codigo = $codigo;
    }

    public function ja_associado()
    {
        global $DB;
        $result = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE id_suap = ?', array(Curso::format_id_suap($this->id)));
        return $result;
    }

    public static function ler_rest($ano_letivo, $periodo_letivo)
    {
        global $suap_id_campus_ead;

        $response = json_request("listar_cursos_ead", array(
            'id_campus' => $suap_id_campus_ead,
            'ano_letivo' => $ano_letivo,
            'periodo_letivo' => $periodo_letivo));
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new Curso($id, $obj['nome'], $obj['descricao'], $obj['codigo']);
        }
        return $result;
    }

    public static function format_id_suap($id_suap)
    {
        return "{'curso':'{$id_suap}'}";
    }

    public static function ler_moodle($id_suap)
    {
        global $DB, $contexto_turma_moodle;
        $curso = $DB->get_record_sql("SELECT c.* FROM {course_categories} c WHERE id_suap = ?", array(Curso::format_id_suap($id_suap)));
        if (!$curso) {
            return null;
        }
        $curso->context = $DB->get_record('context', array('contextlevel' => $contexto_turma_moodle, 'instanceid' => $curso->id));
        return $curso;
    }

    public static function ler_categorias()
    {
        global $DB;
        $req = "SELECT c.id, c.name, c.idnumber, c.id_suap FROM {course_categories} c";
        $courses = array();
        $result = $DB->get_records_sql($req);
        foreach ($result as $course) {
            $courses[] = $course;
        }
        return $courses;
    }

    public static function associar($id_curso_suap, $id_categoria)
    {
        global $DB;
        $sql = 'UPDATE {course_categories} SET id_suap = NULL WHERE id_suap = ?';
        $DB->execute($sql, array(Curso::format_id_suap($id_curso_suap)));

        $sql = 'UPDATE {course_categories} SET id_suap = ? WHERE id = ?';
        $DB->execute($sql, array(Curso::format_id_suap($id_curso_suap), $id_categoria));
    }

    public static function importar($id_curso, $ano, $periodo)
    {
        $curso = Curso::ler_moodle($id_curso);
        echo "<p>Importando curso <b>{$curso->name} ($ano.$periodo)</b> ...</p>";
        foreach (Turma::ler_rest($id_curso, $ano, $periodo) as $turma) {
            Turma::importar($id_curso, $turma->id, $turma->codigo);
        };
    }
}


class Turma
{
    public $id;
    public $codigo;
    public $id_curso;

    public function __construct($id, $codigo)
    {
        $this->id = $id;
        $this->codigo = $codigo;
    }

    public static function format_id_suap($id_suap)
    {
        return "{'turma':'{$id_suap}'}";
    }

    public function ja_associado()
    {
        global $DB;
        $result = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE id_suap = ?', array(Turma::format_id_suap($this->id)));
        return $result;
    }

    public static function ler_rest($id_curso, $ano_letivo, $periodo_letivo)
    {
        $response = json_request("listar_turmas_ead", array(
            'id_curso' => $id_curso,
            'ano_letivo' => $ano_letivo,
            'periodo_letivo' => $periodo_letivo));
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new Turma($id, $obj['codigo']);
        }
        return $result;
    }

    public static function ler_moodle($id_turma)
    {
        global $DB, $contexto_turma_moodle;
        $turma = $DB->get_record_sql("SELECT c.* FROM {course_categories} c WHERE id_suap = ?", array(Turma::format_id_suap($id_turma)));
        if (!$turma) {
            return null;
        }
        $turma->context = $DB->get_record('context', array('contextlevel' => $contexto_turma_moodle, 'instanceid' => $turma->id));
        return $turma;
    }

    public static function associar($id_suap, $id_categoria)
    {
        global $DB;
        $sql = 'UPDATE {course_categories} SET id_suap = NULL WHERE id_suap = ?';
        $DB->execute($sql, array(Turma::format_id_suap($id_suap)));

        $sql = 'UPDATE {course_categories} SET id_suap = ? WHERE id = ?';
        $DB->execute($sql, array(Turma::format_id_suap($id_suap), $id_categoria));
    }

    public static function importar($id_curso, $id_turma, $codigo)
    {
        $curso = Curso::ler_moodle($id_curso);
        echo "<p>Importando turma <b>$id_turma</b> do curso <b>{$curso->name}</b> ...</p>";

        // Se não existe uma category para esta turma criá-la como filha do curso
        $turma_moodle = Turma::ler_moodle($id_turma);
        if (!$turma_moodle) {
            $turma = new Turma($id_turma, $codigo);
            $turma->id_curso = $id_curso;
            $turma->criar();
        }

        foreach (Diario::ler_rest($id_turma) as $diario) {
            Diario::importar($id_turma, $diario->id, $diario);
        };
    }

    public function criar()
    {
        global $DB, $contexto_turma_moodle;

        // Recupera o curso
        $parent = Curso::ler_moodle($this->id_curso);

        // Cria a turma
        $record = new stdClass();
        $record->name = 'Turma: ' . $this->codigo;
        $record->idnumber = $this->codigo;
        //$record->description = $turma_suap['descricao'];
        $record->description = '';
        $record->timemodified = time();
        $record->parent = $parent->id;
        $record->id_suap = Turma::format_id_suap($this->id);
        $record->depth = $parent->depth + 1;
        $record->descriptionformat = 1;
        $record->theme = $parent->theme;
        $record->sortorder = '0';
        $record->id = $DB->insert_record('course_categories', $record);
        $record->path = "{$parent->path}/{$record->id}";
        $DB->update_record('course_categories', $record);

        criar_contexto($contexto_turma_moodle, $record->id, $parent->context->path);
        fix_course_sortorder();
    }
}


class Diario
{
    public $id;
    public $sigla;
    public $situacao;
    public $descricao;
    public $id_turma;

    public function __construct($id = null, $sigla = null, $situacao = null, $descricao = null)
    {
        $this->id = $id;
        $this->sigla = $sigla;
        $this->situacao = $situacao;
        $this->descricao = $descricao;
    }

    public static function format_id_suap($id_suap)
    {
        return "{'diario':'{$id_suap}'}";
    }

    public function ja_associado()
    {
        global $DB;
        $result = $DB->get_records_sql('SELECT * FROM {course} WHERE id_suap = ?', array(Diario::format_id_suap($this->id)));
        return $result;
    }

    public static function ler_rest($id_turma)
    {
        $response = json_request("listar_diarios_ead", array('id_turma' => $id_turma));
        $result = array();
        foreach ($response as $id => $obj) {
            $result[] = new Diario($id, $obj['sigla'], $obj['situacao'], $obj['descricao']);
        }
        return $result;
    }

    public static function ler_moodle($id_diario)
    {
        global $DB, $contexto_diario_moodle;
        $diario = $DB->get_record_sql("SELECT c.* FROM {course} c WHERE id_suap = ?", array(Diario::format_id_suap($id_diario)));
        if (!$diario) {
            return null;
        }
        $diario->context = $DB->get_record('context', array('contextlevel' => $contexto_diario_moodle, 'instanceid' => $id_diario));
        return $diario;
    }

    public static function ler_cursos()
    {
        global $DB;
        $req = "SELECT id, fullname, idnumber, id_suap FROM {course} ORDER BY fullname";
        $courses = array();
        $result = $DB->get_records_sql($req);
        foreach ($result as $course) {
            $courses[] = $course;
        }
        return $courses;
    }

    public static function associar($id_suap, $id_curso)
    {
        global $DB;
        $sql = 'UPDATE {course} SET id_suap = NULL WHERE id_suap = ?';
        $DB->execute($sql, array(Diario::format_id_suap($id_suap)));

        $sql = 'UPDATE {course} SET id_suap = ? WHERE id = ?';
        $DB->execute($sql, array(Diario::format_id_suap($id_suap), $id_curso));
    }

    public static function importar($id_turma, $id_diario, $diario = null)
    {
        $turma = Turma::ler_moodle($id_turma);
        echo "<p>Importando diário <b>$id_diario</b> da turma <b>{$turma->name}</b> ...</p>";

        // Se não existe uma category para esta turma criá-la como filha do curso
        $diario_moodle = Diario::ler_moodle($id_diario);
        if (!$diario_moodle) {
            echo "<p>Criando o diário</p>";
            $diario_moodle = new Diario();
            $diario_moodle->id_turma = $id_turma;
            $diario_moodle->criar($diario);
        }

        Professor::importar($id_diario);
        Aluno::importar($id_diario);
    }

    public function criar($diario = null)
    {
        global $DB, $contexto_diario_moodle;

        // Recuperar a turma
        $parent = Turma::ler_moodle($this->id_turma);

        // Criar o diário
        $record = new stdClass();
        $record->category = $parent->id;
        $record->fullname = "[{$parent->idnumber}.{$diario->sigla}] {$diario->descricao}";
        $record->shortname = "[{$parent->idnumber}.{$diario->sigla}]";
        $record->idnumber = "{$parent->idnumber}.{$diario->sigla}";
        $record->summary = '';
        $record->summaryformat = 1;
        $record->newsitems = 5;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->cacherev = time();
        $record->id_suap = Diario::format_id_suap($diario->id);
        $record->id = $DB->insert_record('course', $record);
        $DB->update_record('course', $record);

        criar_contexto($contexto_diario_moodle, $record->id, $parent->context->path);
        fix_course_sortorder();
    }
}


class Enrol extends AbstractEntity
{

}

class Usuario extends AbstractEntity
{
    public $id;
    public $nome;
    public $login;
    public $tipo;
    public $email;
    public $email_secundario;
    public $status;
    public $situacao;

    public function __construct($id, $nome=null, $login=null, $tipo=null, $email=null, $email_secundario=null, $status=null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->login = $login;
        $this->tipo = $tipo;
        $this->email = $email;
        $this->email_secundario = $email_secundario;
        $this->status = $status;
    }

    public function getRoleId() {
        global $role_mapping;
        return $role_mapping[$this->tipo];
    }

    public function getUsername() {
        return $role_mapping[$this->tipo];
    }

    public function getEmail() {
        return $this->email ? $this->email : $this->email_secundario;
    }

    public function getSuspended() {
        return $this->status == 'ativo' ? 0 : 1;
    }

    static function criar_user_preferences($usuarioid, $name, $value) {
        global $DB;
        $user_preferences = new stdClass();
        $user_preferences->userid = $usuarioid;
        $user_preferences->name = $name;
        $user_preferences->value = $value;
        $DB->insert_record('user_preferences', $user_preferences);
    }

    function sincronizar() {
        global $DB;
        $usuario = $DB->get_record("user", array("username"=>$this->login));
        if (!$usuario) {
//            if ($DB->get_record("user", array("email"=>$email))) {
//                throw new Exception("JÃ¡ existe um usuÃ¡rio com o email '$email'.");
//            }
            $nome_parts = explode(' ', $this->nome);
            $lastname = array_pop($nome_parts);
            $firstname = implode(' ', $nome_parts);
            $usuario = new stdClass();
            $usuario->username = $this->login;
            $usuario->auth = 'ldap';
            $usuario->firstname = $firstname;
            $usuario->lastname = $lastname;
            $usuario->email = $this->getEmail();
            $usuario->timecreated = time();
            $usuario->timemodified = time();
            $usuario->timezone = '99';
            $usuario->lang = 'pt_br';
            $usuario->password = 'not cached';
            $usuario->suspended = $this->getSuspended();
            $usuario->confirmed = 1;
            $usuario->id = $DB->insert_record('user', $usuario);

            criar_contexto('30', $usuario->id, '/1');

            Usuario::criar_user_preferences($usuario->id, 'auth_forcepasswordchange', '0');
            Usuario::criar_user_preferences($usuario->id, 'htmleditor', '0');
            Usuario::criar_user_preferences($usuario->id, 'email_bounce_count', '1');
            Usuario::criar_user_preferences($usuario->id, 'email_send_count', '1');
        } else {
            $usuario->suspended = $this->getSuspended();
            $DB->update_record('user', $usuario);
        }
        return $usuario;
    }

//    function arrolar($id_diario, $enrolid, $userid, $roleid, $contextid, $sortorder =0) {
//        global $DB;
//        $diario = Diario::ler_moodle($id_diario);
//
//        if (!$DB->get_record('user_enrolments', array('enrolid'=>$enrolid,'userid'=>$userid))) {
//            $user_enrolments = new stdClass();
//            $user_enrolments->enrolid = $enrolid;
//            $user_enrolments->userid = $userid;
//
//            $user_enrolments->status = 0;
//            $user_enrolments->timeend = 0;
//            $user_enrolments->modifierid = 4;
//            $user_enrolments->timestart = time();
//            $user_enrolments->timecreated = time();
//            $user_enrolments->timemodified = time();
//            $user_enrolments->id = $DB->insert_record('user_enrolments', $user_enrolments);
//        }
//
//        if (!$DB->get_record('role_assignments', array('roleid'=>$roleid, 'contextid'=>$contextid, 'userid'=>$userid, 'itemid'=>0))) {
//            $role_assigments = new stdClass();
//            $role_assigments->roleid = $roleid;
//            $role_assigments->contextid = $contextid;
//            $role_assigments->userid = $userid;
//            $role_assigments->component = '';
//            $role_assigments->itemid = 0;
//
//            $role_assigments->sortorder = $sortorder;
//            $role_assigments->timemodified = time();
//            $role_assigments->modifierid = time();
//            $role_assigments->id = $DB->insert_record('role_assignments', $role_assigments);
//        }
//    }
}


class Professor extends Usuario
{

    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico("listar_professores_ead", $id_diario, 'Professor', ['nome', 'login', 'tipo', 'email', 'email_secundario', 'status']);
    }

    public static function importar($id_diario)
    {
        try {
            $diario = Diario::ler_moodle($id_diario);
            echo "<p>Importando professores e tutores do diário <b>{$diario->fullname}</b> ...</p>";

            foreach (Professor::ler_rest($id_diario) as $professor) {
                $professor->sincronizar();
            }
        } catch (Exception $e) {
            dump_error($e);
        }
    }
}


class Aluno extends Usuario
{
    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico("listar_alunos_ead", $id_diario, 'Aluno', ['nome', 'matricula', 'tipo', 'email', 'email_secundario', 'situacao']);
    }

    public static function importar($id_diario)
    {
        try {
            echo "<p>Importando alunos do diário <b>" . Diario::ler_moodle($id_diario)->fullname . "</b> ...</p>";

            foreach (Aluno::ler_rest($id_diario) as $aluno) {
                $aluno->sincronizar();
            }
        } catch (Exception $e) {
            dump_error($e);
        }
    }
}
