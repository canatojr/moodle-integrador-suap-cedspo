<?php
require_once('lib.php');
include('../lib/coursecatlib.php');
include('../course/lib.php');
include('../user/lib.php');


class Category {

    public static function has_suap_id($moodle_id) {
        global $DB;
        $result = $DB->record_exists_sql('SELECT * FROM {course_categories} WHERE id = ? AND id_suap IS NOT NULL', array($moodle_id));
        return $result;
    }

    public static function render_selectbox($id=null) {
        $options = coursecat::make_categories_list('moodle/category:manage');
        echo "<select name='categoria'>";
        foreach ($options as $key=>$label):
            echo "<option value='$key'";
            echo $id == $key ? " selected>" : ">";
            echo Category::has_suap_id($key) ? "(JÁ ASSOCIADO)" : "";
            echo "$label</option>";
        endforeach;
        echo "</select>";
    }
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
                foreach ($properties as $property) {
                    $instance->{$property} = $obj[$property];
                }
                $result[] = $instance;
            }
            return $result;
        } catch (Exception $e) {
            raise_error($e);
        }
    }

    protected static function criar_contexto($contextlevel, $instanceid, $base_path)
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
        echo "<li>Importando curso <b>{$curso->name} ($ano.$periodo)</b>...</li><ol>";
        foreach (Turma::ler_rest($id_curso, $ano, $periodo) as $turma) {
            echo "<li>";
            Turma::importar($id_curso, $turma->id, $turma->codigo);
            echo "</li>";
        };
        echo "</ol>";
    }
}


class Turma extends AbstractEntity
{
    public $id;
    public $codigo;
    public $id_curso;
    public $id_moodle;

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
        echo "Importando a turma (<b>$id_turma</b>) <b>$codigo</b> ...";

        // Se não existe uma category para esta turma criá-la como filha do curso
        $turma_moodle = Turma::ler_moodle($id_turma);
        if (!$turma_moodle) {
            $turma = new Turma($id_turma, $codigo);
            $turma->id_curso = $id_curso;
            $turma->criar();
            echo " A turma foi criada. <a href='../course/management.php?categoryid={$turma->id_moodle}' class='btn btn-small'>Acessar</a>";
        } else {
            echo " A turma já existe. <a href='../course/management.php?categoryid={$turma_moodle->id}' class='btn btn-small'>Acessar</a>";
        }
        echo "<ol>";
        foreach (Diario::ler_rest($id_turma) as $diario) {
            Diario::importar($id_turma, $diario->id, $diario);
        };
        echo "</ol>";
    }

    public function criar()
    {
        try {
            // Recupera o curso
            $parent = Curso::ler_moodle($this->id_curso);

            // Cria a categoria
            $record = coursecat::create(array(
                "name"=>"Turma: {$this->codigo}",
                "idnumber"=>$this->codigo,
                "description"=>'',
                "descriptionformat"=>1,
                "parent"=>$parent->id,
            ));

            // Associa ao SUAP
            Turma::associar($this->id, $record->id);
        } catch(Exception $e) {
            raise_error($e);
        }
    }
}


class Diario extends AbstractEntity
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
        return Diario::ler_moodle($this->id);
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
        $diario->context = $DB->get_record('context', array('contextlevel' => $contexto_diario_moodle, 'instanceid' => $diario->id));
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
        echo "<li>Importando o diário (<b>$id_diario</b>)";

        // Se não existe uma category para esta turma criá-la como filha do curso
        $diario_moodle = Diario::ler_moodle($id_diario);
        if (!$diario_moodle) {
            $diario_moodle = new Diario();
            $diario_moodle->id_turma = $id_turma;
            $diario_moodle->criar($diario);
            echo " <b>{$diario_moodle->fullname}</b> ... foi criado com sucesso. <a class='btn btn-small' href='../course/management.php?categoryid={$diario_moodle->category}&courseid={$diario_moodle->id}'>Acessar</a></p>";
        } else {
            echo " <b>{$diario_moodle->fullname}</b> ... já existia. <a class='btn btn-small' href='../course/management.php?categoryid={$diario_moodle->category}&courseid={$diario_moodle->id}'>Acessar</a></p>";
        }
        echo "</li><ol>";
        Professor::importar($id_diario);
        Aluno::importar($id_diario);
        echo "</ol>";
    }

    public function criar($diario = null)
    {
        try {
            global $DB, $contexto_turma_moodle;

            // Recupera a turma
            $parent = Turma::ler_moodle($this->id_turma);

            // Cria o diário
            $record = create_course((object)array(
                'category'=>$parent->id,
                'fullname'=>"[{$parent->idnumber}.{$diario->sigla}] {$diario->descricao}",
                'shortname'=>"[{$parent->idnumber}.{$diario->sigla}]",
                'idnumber'=>"{$parent->idnumber}.{$diario->sigla}",
            ));

            // Associa ao SUAP
            Diario::associar($diario->id, $record->id);
            $this->id = $record->id;
            $this->fullname = $record->fullname;
            $this->category = $parent->id;
        } catch(Exception $e) {
            raise_error($e);
        }
    }
}


class Enrol extends AbstractEntity
{
    public $id;
    public $enroltype;
    public $courseid;
    public $roleid;

    public static function ler_ou_criar($enroltype,  $courseid,  $roleid)
    {
        global $DB;
        $enrol = $DB->get_record('enrol', array('enrol'=>$enroltype, 'courseid'=>$courseid, 'roleid'=>$roleid));
        if (!$enrol) {
            $enrol = new stdClass();
            $enrol->enrol = $enroltype;
            $enrol->courseid = $courseid;
            $enrol->roleid = $roleid;
            $enrol->status = 0;
            $enrol->expirythreshold = 86400;
            $enrol->timecreated = time();
            $enrol->timemodified = time();
            $enrol->id = $DB->insert_record('enrol', $enrol);
        }
        return $enrol;
    }
}

class Usuario extends AbstractEntity
{
    public $id;
    public $nome;
    public $login;
    public $matricula;
    public $tipo;
    public $email;
    public $email_secundario;
    public $status;
    public $situacao;
    public $id_moodle;

    public function __construct($id, $nome = null, $login = null, $tipo = null, $email = null, $email_secundario = null, $status = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->login = $login;
        $this->tipo = $tipo;
        $this->email = $email;
        $this->email_secundario = $email_secundario;
        $this->status = $status;
    }

    public function getUsername()
    {
        return $this->login ? $this->login : $this->matricula;
    }

    public function getEmail()
    {
        return $this->email ? $this->email : $this->email_secundario;
    }

    public function getSuspended()
    {
        return $this->getStatus() == 'ativo' ? 0 : 1;
    }

    public function getStatus()
    {
        return $this->status ? $this->status : $this->situacao;
    }

    public function getTipo()
    {
        return $this->tipo ? $this->tipo : 'Aluno';
    }

    public function getRoleId()
    {
        global $enrol_roleid;
        return $enrol_roleid[$this->getTipo()];
    }

    public function getEnrolType()
    {
        global $enrol_type;
        return $enrol_type[$this->getTipo()];
    }

    function sincronizar()
    {
        global $DB, $default_user_preferences;
        $usuario = $DB->get_record("user", array("username" => $this->getUsername()));
        if (!$usuario) {
//            dumpd($usuario);
            $nome_parts = explode(' ', $this->nome);
            $lastname = array_pop($nome_parts);
            $firstname = implode(' ', $nome_parts);
            $usuario = new stdClass();
            $usuario->username = $this->getUsername();
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

            AbstractEntity::criar_contexto('30', $usuario->id, '/1');

            foreach ($default_user_preferences as $key=>$value) {
                $this->criar_user_preferences($key, $value);
            }
            // Trigger event If required.
//            \core\event\user_created::create_from_userid($usuario->id)->trigger();
        } else {
            $usuario->suspended = $this->getSuspended();
            $DB->update_record('user', $usuario);
//            \core\event\user_updated::create_from_userid($usuario->id)->trigger();
        }
        echo "<li>Sincronizado <b>{$this->getUsername()} - {$this->nome} ({$this->getTipo()})</b> <a href='../user/profile.php?id={$usuario->id}' class='btn btn-small'>Acessar</a></li>";
        $this->id_moodle = $usuario->id;
        return $usuario;
    }

    function criar_user_preferences($name, $value)
    {
        global $DB;
        $user_preferences = new stdClass();
        $user_preferences->userid = $this->id_moodle;
        $user_preferences->name = $name;
        $user_preferences->value = $value;
        $DB->insert_record('user_preferences', $user_preferences);
    }

    function arrolar($diario_moodle) {
        global $DB, $contexto_diario_moodle;
        $enrol = Enrol::ler_ou_criar($this->getEnrolType(), $diario_moodle->id, $this->getRoleId());

        if (!$DB->get_record('user_enrolments', array('enrolid'=>$enrol->id,'userid'=>$diario_moodle->id))) {
            $user_enrolments = new stdClass();
            $user_enrolments->enrolid = $enrol->id;
            $user_enrolments->userid = $diario_moodle->id;

            $user_enrolments->status = 0;
            $user_enrolments->timeend = 0;
            $user_enrolments->modifierid = 4;
            $user_enrolments->timestart = time();
            $user_enrolments->timecreated = time();
            $user_enrolments->timemodified = time();
            $user_enrolments->id = $DB->insert_record('user_enrolments', $user_enrolments);
        }

        if (!$DB->get_record('role_assignments', array('roleid'=>$this->getRoleId(), 'contextid'=>$diario_moodle->context->id, 'userid'=>$this->id_moodle, 'itemid'=>0))) {
            $role_assigments = new stdClass();
            $role_assigments->roleid = $this->getRoleId();
            $role_assigments->contextid = $diario_moodle->context->id;
            $role_assigments->userid = $this->id_moodle;

            $role_assigments->itemid = 0;
            $role_assigments->component = '';
            $role_assigments->sortorder = 0;
            $role_assigments->timemodified = time();
            $role_assigments->modifierid = time();
            $role_assigments->id = $DB->insert_record('role_assignments', $role_assigments);
        }
        echo "<li>Arrolado <b>{$instance->getUsername()} - {$instance->nome} ({$instance->getTipo()} )</b></li>";
    }

    protected static function sincronizar_usuarios($id_diario, $oque, $list)
    {
        try {
            $diario = Diario::ler_moodle($id_diario);
            echo "<li>Sincronizando <b>" . count($list) .  " $oque</b> do diário <b>{$diario->fullname}</b> ...<ol>";

            foreach ($list as $instance) {
                $instance->sincronizar();
            }
            echo "</ol></li>";
        } catch (Exception $e) {
            raise_error($e);
        }
    }

    protected static function arrolar_usuarios($id_diario, $oque, $list)
    {
        try {
            $diario = Diario::ler_moodle($id_diario);
            echo "<li>Arrolando <b>" . count($list) .  " $oque</b> do diário <b>{$diario->fullname}</b> ...</li><ol>";

            foreach ($list as $instance) {
//                $instance->arrolar($diario);
            }
            echo "</ol>";
        } catch (Exception $e) {
            raise_error($e);
        }
    }
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
            $list = Professor::ler_rest($id_diario);
            Usuario::sincronizar_usuarios($id_diario, 'docentes', $list);
            Usuario::arrolar_usuarios($id_diario, 'docentes', $list);
        } catch (Exception $e) {
            raise_error($e);
        }
    }
}


class Aluno extends Usuario
{
    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico("listar_alunos_ead", $id_diario, 'Aluno', ['nome', 'matricula', 'email', 'email_secundario', 'situacao']);
    }

    public static function importar($id_diario)
    {
        try {
            $list = Aluno::ler_rest($id_diario);
            Usuario::sincronizar_usuarios($id_diario, 'aluno(s)', $list);
            Usuario::arrolar_usuarios($id_diario, 'aluno(s)', $list);
        } catch (Exception $e) {
            raise_error($e);
        }
    }
}
