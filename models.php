<?php
require_once 'lib.php';
require_once $CFG->libdir . '/coursecatlib.php';
require_once '../../course/lib.php';
require_once '../../user/lib.php';
require_once '../../group/lib.php';
require_once "../../enrol/locallib.php";
require_once "../../enrol/externallib.php";

define("SUAP_ID_CAMPUS_EAD", $CFG->block_suap_id_campus);
define("NIVEL_CURSO", $CFG->block_suap_nivel_curso);
define("NIVEL_TURMA", $CFG->block_suap_nivel_turma);
define("NIVEL_PERIODO", $CFG->block_suap_nivel_periodo);


function get_or_die($param)
{
    return isset($_GET[$param]) ? $_GET[$param] : die("Parâmetros incompletos ($param).");
}


function merge_objects($source, $destin)
{
    foreach (get_object_vars($source) as $attr => $value) {
        if ($attr == 'id') {
            $attr = 'id_moodle';
        }
        $destin->$attr = $value;
    }
}


function cmp_by_label($a, $b)
{
    return strcmp($a->getLabel(), $b->getLabel());
}


function ler_courses()
{
    global $DB;
    return $DB->get_records_sql("SELECT id, fullname, idnumber, id_suap FROM {course} ORDER BY fullname");
}


function ler_categories()
{
    global $DB;
    return $DB->get_records('course_categories');
}


class AbstractEntity
{
    public $id_on_suap;
    public $id_moodle;

    public function __construct($id_on_suap)
    {
        $this->id_on_suap = $id_on_suap;
    }

    public function ja_associado()
    {
        $instance = $this->ler_moodle();
        return $instance && $instance->id_moodle;
    }

    public static function ler_rest_generico($service, $id, $class, $properties)
    {
        $response = json_request($service, ['id_diario' => $id]);
        $result = [];
        foreach ($response as $id => $obj) {
            $instance = new $class($id);
            foreach ($properties as $property) {
                $instance->{$property} = $obj[$property];
            }
            $result[] = $instance;
        }
        return $result;
    }

    public function execute($sql, $params)
    {
        global $DB;
        return $DB->execute($sql, $params);
    }

    public function get_records_sql($sql, $params)
    {
        global $DB;
        return $DB->get_records_sql($sql, $params);
    }

    public function get_records($tablename, $filters)
    {
        $params = [];
        $where = '';
        foreach ($filters as $fieldname => $value) {
            $where .= $where == '' ? "WHERE $fieldname = ?" : " AND $fieldname = ?";
            $params[] = $value;
        }
        return $this->get_records_sql(
            "SELECT * FROM {{$tablename}} $where",
            $params
        );
    }

    public function get_record($tablename, $filters)
    {
        $array = $this->get_records($tablename, $filters);
        return array_shift($array);
    }

    public function getIdSUAP()
    {
        $clasname = strtolower(get_class($this));
        return "{'{$clasname}':'{$this->id_on_suap}'}";
    }

    public function associar()
    {
        $tablename = $this->getTablename();
        $this->execute(
            "UPDATE {{$tablename}} SET id_suap=NULL WHERE id_suap=?",
            [$this->getIdSUAP()]
        );
        $this->execute(
            "UPDATE {{$tablename}}  SET id_suap=? WHERE id=?",
            [$this->getIdSUAP(), $this->id_moodle]
        );
    }

    public function ler_moodle()
    {
        $table = $this->getTablename();
        $filter = ['id_suap' => $this->getIdSUAP()];
        $instance = $this->get_record($table, $filter);
        if (!$instance) {
            $rows = $this->get_records($table, ['idnumber' => $this->getCodigo()]);
            if (count($rows) == 1) {
                $this->execute(
                    "UPDATE {{$table}} SET id_suap=? WHERE idnumber=?",
                    [$this->getIdSUAP(), $this->getCodigo()]
                );
                $instance = $this->get_record($table, $filter);
            }
            if (!$instance) {
                return $this;
            }
        }
        merge_objects($instance, $this);
        $this->context = $this->get_record(
            'context',
            ['contextlevel' => $this->getContextLevel(),
            'instanceid' => $this->id_moodle]
        );
        return $this;
    }
}


class Polo extends AbstractEntity
{
    public $nome;

    public function __construct($id_on_suap, $nome)
    {
        parent::__construct($id_on_suap);
        $this->nome = $nome;
    }

    public static function ler_rest()
    {
        global $polos;
        if (!$polos) {
            $response = json_request("listar_polos_ead", array());
            $result = [];
            foreach ($response as $id_on_suap => $obj) {
                $result[] = new Polo($id_on_suap, $obj['descricao']);
            }
            $polos = $result;
        }
        return $polos;
    }
}


class Campus extends AbstractEntity
{
    public $nome;
    public $sigla;

    public function __construct($id_on_suap, $nome, $sigla)
    {
        parent::__construct($id_on_suap);
        $this->nome = $nome;
        $this->sigla = $sigla;
    }

    public static function ler_rest()
    {
        $response = json_request("listar_campus_ead", array());
        $result = [];
        foreach ($response as $id_on_suap => $obj) {
            $result[] = new Campus($id_on_suap, $obj['descricao'], $obj['sigla']);
        }
        return $result;
    }
}


class ComponenteCurricular extends AbstractEntity
{
    public $tipo;
    public $periodo;
    public $qtd_avaliacoes;
    public $descricao_historico;
    public $optativo;
    public $descricao;
    public $sigla;

    public function __construct($id_on_suap=null, $tipo=null, $periodo=null, $qtd_avaliacoes=null, $descricao_historico=null, $optativo=null, $descricao=null, $sigla=null)
    {
        parent::__construct($id_on_suap);
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
        $response = json_request(
            "listar_componentes_curriculares_ead",
            array('id_curso' => $id_curso)
        );
        $result = [];
        foreach ($response as $id_on_suap => $o) {
            $result[] = new ComponenteCurricular($id_on_suap, $o['tipo'], $o['periodo'], $o['qtd_avaliacoes'], $o['descricao_historico'], $o['optativo'], $o['descricao'], $o['sigla']);
        }
        return $result;
    }
}


class Category extends AbstractEntity
{
    public $codigo;

    public function __construct($id_on_suap=null, $codigo=null)
    {
        parent::__construct($id_on_suap);
        $this->codigo = $codigo;
    }

    public function getTablename()
    {
        return "course_categories";
    }

    public function getContextLevel()
    {
        return '40';
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public static function render_selectbox($level = 0)
    {
        global $DB;
        $has_suap_ids = array_keys($DB->get_records_sql('SELECT id FROM {course_categories} WHERE id_suap IS NOT NULL'));
        foreach (coursecat::make_categories_list('moodle/category:manage') as $key => $label):
            if (($level > 0) && (count(explode(' / ', $label)) != $level)) {
                continue;
            }
        $jah_associado = in_array($key, $has_suap_ids) ? "disabled" : "";
        echo "<label class='as_row $jah_associado' ><input type='radio' value='$key' name='categoria' $jah_associado />$label</label>";
        endforeach;
    }
}


class Curso extends Category
{
    public $nome;
    public $descricao;

    public function __construct($id_on_suap=null, $codigo=null, $nome=null, $descricao=null)
    {
        parent::__construct($id_on_suap, $codigo);
        $this->nome = $nome;
        $this->descricao = $descricao;
    }

    public function getLabel()
    {
        return $this->descricao;
    }

    public static function ler_rest($ano_letivo, $periodo_letivo)
    {
        $response = json_request(
            "listar_cursos_ead",
            ['id_campus' => SUAP_ID_CAMPUS_EAD,
                'ano_letivo' => $ano_letivo,
            'periodo_letivo' => $periodo_letivo]
        );
        $result = [];
        foreach ($response as $id_on_suap => $o) {
            $result[] = new Curso($id_on_suap, $o['codigo'], $o['nome'], $o['descricao']);
        }
        usort($result, 'cmp_by_label');
        return $result;
    }

    public function importar($ano, $periodo)
    {
        $this->ler_moodle();
        if (CLI_SCRIPT) {
            echo "\nImportando do curso {$this->name} diários do período $ano.$periodo...\n";
        } else {
            echo "<li>Importando do curso <b>{$this->name}</b> diários do período <b>$ano.$periodo</b>...</li><ol>";
        }
        
        foreach (Turma::ler_rest($this->id_on_suap, $ano, $periodo, $this) as $turma) {
            if (!CLI_SCRIPT) {
                echo "<li>";
            }
            $turma->importar();
            if (!CLI_SCRIPT) {
                echo "</li>";
            }
        };
        if (!CLI_SCRIPT) {
            echo "</ol>";
        }
    }

    public function auto_associar($ano_inicial, $periodo_inicial, $ano_final, $periodo_final)
    {
        global $DB;
        $ano_inicial = (int)$ano_inicial;
        $periodo_inicial = (int)$periodo_inicial;
        $ano_final = (int)$ano_final;
        $periodo_final = (int)$periodo_final;

        for ($ano = $ano_inicial; $ano <= $ano_final; $ano++) {
            for ($periodo = 1; $periodo <= 2; $periodo++) {
                if (($ano == $ano_inicial && $periodo < $periodo_inicial) || ($ano == $ano_final && $periodo > $periodo_final)) {
                    continue;
                }
                foreach (Turma::ler_rest($this->id_on_suap, $ano, $periodo) as $turma_suap) {
                    $turma_suap->ler_moodle();
                    if ($turma_suap->ja_associado()) {
                        echo "<li class='notifysuccess'>A turma SUAP <strong>{$turma_suap->codigo}</strong> JÁ está associada à categoria <strong>{$turma_suap->name}</strong> no Moodle.<ol>";
                    } else {
                        echo "<li class='notifyproblem'>A turma SUAP <strong>{$turma_suap->codigo}</strong> NÃO FOI associada a uma categoria no Moodle.<ol>";
                    }
                    $diarios = Diario::ler_rest($turma_suap);
                    if (count($diarios) == 0) {
                        echo "<li class='notifymessage'>Não existem diários para esta turma.</li>";
                    }
                    foreach ($diarios as $diario_suap):
                        $diario_suap->ler_moodle();
                    if ($diario_suap->ja_associado()) {
                        echo "<li class='notifysuccess'>O diário SUAP <b>{$diario_suap->getCodigo()}</b> JÁ está associado ao course <b>{$diario_suap->fullname}</b> no Moodle.";
                    } else {
                        echo "<li class='notifyproblem'>O <b>diário SUAP {$diario_suap->getCodigo()}</b> NÃO está associado a um <b>course no Moodle</b>.";
                    }
                    echo "</li>";
                    endforeach;
                    echo "</ol></li>";
                };
            }
        }
    }
}

class Turma extends Category
{
    public $curso;

    public function __construct($id_on_suap, $codigo, $curso=null)
    {
        parent::__construct($id_on_suap, $codigo);
        $this->curso = $curso;
    }

    public function getLabel()
    {
        return $this->codigo;
    }

    public static function ler_rest($id_curso, $ano_letivo, $periodo_letivo, $curso = null)
    {
        $response = json_request(
            "listar_turmas_ead",
            ['id_curso' => $id_curso, 'ano_letivo' => $ano_letivo, 'periodo_letivo' => $periodo_letivo]
        );
        $result = [];
        foreach ($response as $id_on_suap => $obj) {
            $result[] = new Turma($id_on_suap, $obj['codigo'], $curso);
        }
        usort($result, 'cmp_by_label');
        return $result;
    }

    public function importar()
    {
        if (!CLI_SCRIPT) {
            echo "Importando a turma <b>{$this->codigo}</b>...";
        } else {
            echo "\nImportando a turma {$this->codigo}...";
        }
        // Se não existe uma category para esta turma criá-la como filha do curso
        $this->ler_moodle();
        if (!$this->id_moodle) {
            $this->criar();
            echo " A turma foi criada.";
        } else {
            echo " A turma já existe.";
        }
        if (!CLI_SCRIPT) {
            echo " <a href='../../course/management.php?categoryid={$this->id_moodle}' class='btn btn-mini'>Acessar</a><ol>";
        }
        foreach (Diario::ler_rest($this) as $diario) {
            $diario->importar();
        };
        if (!CLI_SCRIPT) {
            echo "</ol>";
        }
    }

    public function criar()
    {
        try {
            // Cria a categoria
            $record = coursecat::create(
                array(
                "name" => "Turma: {$this->codigo}",
                "idnumber" => $this->codigo,
                "description" => '',
                "descriptionformat" => 1,
                "parent" => $this->curso->id_moodle,
                )
            );
            $this->id_moodle = $record->id;

            // Associa ao SUAP
            $this->associar();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}


class Diario extends AbstractEntity
{
    public $sigla;
    public $situacao;
    public $descricao;
    public $turma;
    public $id_suap;

    public function __construct($id_on_suap, $sigla = null, $situacao = null, $descricao = null, $turma = null)
    {
        parent::__construct($id_on_suap);
        $this->sigla = $sigla;
        $this->situacao = $situacao;
        $this->descricao = $descricao;
        $this->turma = $turma;
        $this->id_suap = $id_on_suap;
    }

    public function getTablename()
    {
        return "course";
    }

    public function getLabel()
    {
        return $this->sigla;
    }

    public function getCodigo()
    {
        return $this->turma ? "{$this->turma->codigo}.{$this->sigla} {$this->id_suap}" : null;
    }

    public function getContextLevel()
    {
        return '50';
    }

    public static function ler_rest($turma)
    {
        $response = json_request("listar_diarios_ead", ['id_turma' => $turma->id_on_suap]);
        $result = [];
        foreach ($response as $id_on_suap => $obj) {
            $result[] = new Diario($id_on_suap, $obj['sigla'], $obj['situacao'], $obj['descricao'], $turma);
        }
        usort($result, 'cmp_by_label');
        return $result;
    }

    public function importar()
    {
        if (!CLI_SCRIPT) {
            echo "<li>Importando o diário (<b>{$this->getCodigo()}</b>)... ";
        } else {
            echo "\nImportando o diário ({$this->getCodigo()})...";
        }
        
        $this->ler_moodle();
        if ($this->ja_associado()) {
            echo "já existia. ";
        } else {
            $this->criar();
            echo "foi criado com sucesso. ";
        }
        if (!CLI_SCRIPT) {
            echo "<a class='btn btn-mini' href='../../course/management.php?categoryid={$this->category}&courseid={$this->id_moodle}'>Configurações do curso</a>";
            echo "<a class='btn btn-mini' href='../../course/view.php?id={$this->id_moodle}'>Acessar o curso</a>";

            echo "</li><ol>";
        }
        Professor::sincronizar($this);
        Aluno::sincronizar($this);
        if (!CLI_SCRIPT) {
            echo "</ol>";
        }
    }

    public function criar()
    {
        // Criar período
        $periodo_numero = explode('.', $this->turma->getCodigo())[1];
        $periodo_nome = "{$periodo_numero}º período";
        $turma_id = $this->turma->id_moodle;
        $periodo_params = ["parent" => $turma_id, 'name' => $periodo_nome];

        $periodo = $this->get_record('course_categories', $periodo_params);
        if (!$periodo) {
            $periodo = coursecat::create($periodo_params);
        }

        // Criar o diário
        $dados = (object)array(
            'category'=>$periodo->id,
            'fullname'=>"[{$this->getCodigo()}] {$this->descricao}",
            'shortname'=>"[{$this->getCodigo()}]",
            'idnumber'=>"{$this->getCodigo()}",
        );

        $record = create_course($dados);

        // Associa ao SUAP
        $this->id_moodle = $record->id;
        $this->associar($record->id);
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
        return strtolower($this->login ? $this->login : $this->matricula);
    }
    
    public function getMatricula()
    {
        return strtolower($this->matricula ? $this->matricula : null);
    }

    public function getEmail()
    {
        return $this->email ? $this->email : $this->email_secundario;
    }

    public function getEmailPrimario()
    {
        return $this->email ? $this->email : null;
    }

    public function getEmailSecundario()
    {
        return $this->email_secundario ? $this->email_secundario : null;
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
        //$editingteacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $enrol_roleid = ['Moderador' => 4, 'Principal' => 3, 'Aluno' => 5, 'Tutor' => 4, 'Formador' => 3];
        return $enrol_roleid[$this->getTipo()];
    }

    public function getEnrolType()
    {
        global $enrol_type;
        $enrol_type = ['Moderador' => 'manual', 'Principal' => 'manual', 'Aluno' => 'manual', 'Tutor' => 'manual', 'Formador' => 'manual'];
        return $enrol_type[$this->getTipo()];
    }

    protected static function sincronizar($diario, $oque, $list)
    {
        try {
            if (!CLI_SCRIPT) {
                echo "<li>Sincronizando <b>" . count($list) .  " $oque</b> do diário <b>{$diario->fullname}</b> ...<ol>";
            } else {
                echo "\nSincronizando " . count($list) .  " $oque do diário {$diario->fullname}...";
            }
            
            foreach ($list as $instance) {
                if (!CLI_SCRIPT) {
                    echo "<li>";
                }
                
                $instance->importar();
                $instance->arrolar($diario);
                $instance->engrupar($diario);
                if (!CLI_SCRIPT) {
                    echo "</li>";
                }
            }
            if (!CLI_SCRIPT) {
                echo "</ol></li>";
            }
        } catch (Exception $e) {
            raise_error($e);
        }
    }

    public function importar()
    {
        global $DB, $default_user_preferences;
        $default_user_preferences = ['auth_forcepasswordchange'=>'0', 'htmleditor'=>'0', 'email_bounce_count'=>'1', 'email_send_count'=>'1'];
        $usuario = $DB->get_record("user", array("username" => $this->getUsername()));
        $nome_parts = explode(' ', $this->nome);
        $lastname = array_pop($nome_parts);
        $firstname = implode(' ', $nome_parts);
        $issuerdata = $DB->get_record_sql('SELECT * FROM {oauth2_issuer} WHERE name LIKE ? ', ['%SUAP%']);
        if (!$usuario) {
            $this->id_moodle = user_create_user(
                [
                'lastname'=>$lastname,
                'firstname'=>$firstname,
                'username'=>$this->getUsername(),
                'idnumber'=>$this->getUsername(),
                'auth'=>'manual',
                'password'=>$this->generate_password(),
                'email'=>$this->getEmail(),
                'suspended'=>$this->getSuspended(),
                'timezone'=>'99',
                'lang'=>'pt_br',
                'confirmed'=>1,
                'mnethostid'=>1,
                ],
                false
            );

            foreach ($default_user_preferences as $key=>$value) {
                $this->criar_user_preferences($key, $value);
            }
            $usuario->id = $this->id_moodle;
            $oper = 'Criado';
        } else {
            $userinfo = [
                'id'=>$usuario->id,
                'idnumber'=>$this->getUsername(),
                'auth'=>'manual',
                'suspended'=>$this->getSuspended(),
                'email'=>$this->getEmail(),
                'lastname'=>$lastname,
                'firstname'=>$firstname,
                'mnethostid'=>1,
            ];
            user_update_user($userinfo, false);
            $oper = 'Atualizado';
        }
            //Cria linked_login
            $record = new stdClass();
            $record->issuerid = $issuerdata->id;
            $record->username = $this->getUsername();
            $record->userid = $usuario->id;
            $record->email = $this->getEmail();
            $record->confirmtoken = '';
            $record->confirmtokenexpires = 0;
            try{
                $linkedlogin = new \auth_oauth2\linked_login(0, $record);
                $linkedlogin->create();
            }catch (Exception $e) {
                echo "";
            }

            //Atualiza linked_login
            $DB->get_record_sql('UPDATE {auth_oauth2_linked_login} SET email = ? WHERE issuerid = ? AND userid = ? AND username = ? ', [$this->getEmail(),$issuerdata->id,$usuario->id,$this->getUsername()]);
            //$linked = \auth_oauth2\linked_login::get_record(['issuerid' => $issuerdata->id, 'userid' => $usuario->id,'username'=>$this->getUsername()]);
            //$linked->email = $this->getEmail();
            //$DB->update_record('auth_oauth2_linked_login', $linked);


        if (!CLI_SCRIPT) {
            echo "$oper <b><a href='../../user/profile.php?id={$usuario->id}'>{$this->getUsername()} - {$this->nome}</a> ({$this->getTipo()})</b>";
        } else {
            echo "\n$oper {$this->getUsername()} - {$this->nome} ({$this->getTipo()})";
        }
        
        $this->id_moodle = $usuario->id;
    }

    public function criar_user_preferences($name, $value)
    {
        global $DB;
        $DB->insert_record(
            'user_preferences',
            (object)array( 'userid'=>$this->id_moodle, 'name'=>$name, 'value'=>$value, )
        );
    }

    public function arrolar($diario)
    {
        global $DB, $USER;
        $enrol = Enrol::ler_ou_criar($this->getEnrolType(), $diario->id_moodle, $this->getRoleId());

        $enrolment = $DB->get_record('user_enrolments', array('enrolid'=>$enrol->id,'userid'=>$this->id_moodle));
        if (!$enrolment) {
            $id = $DB->insert_record(
                'user_enrolments',
                (object)['enrolid'=>$enrol->id,
                                              'userid'=>$this->id_moodle,
                                              'status'=>0,
                                              'timecreated'=>time(),
                                              'timemodified'=>time(),
                                              'timestart'=>time(),
                                              'modifierid'=>$USER->id,
                'timeend'=>0,]
            );
            echo " Foi arrolado. ";
        } else {
            echo " Já arrolado. ";
        }

        $assignment = $DB->get_record(
            'role_assignments',
            array('roleid'=>$this->getRoleId(), 'contextid'=>$diario->context->id, 'userid'=>$this->id_moodle, 'itemid'=>0)
        );
        $diario->ja_associado();
        if (!$assignment) {
            $id2 = $DB->insert_record(
                'role_assignments',
                (object)['roleid'=>$this->getRoleId(),
                                               'contextid'=>$diario->context->id,
                                               'userid'=>$this->id_moodle,
                'itemid'=>0,]
            );
            echo " Foi atribuído. ";
        } else {
            echo " Já atribuído. ";
        }
    }

    public function engrupar($diario)
    {
        global $DB, $USER;
        $polo = $this->getPolo();
        if ($polo) {
            $data = (object)['courseid' => $diario->id_moodle, 'name' => $polo->nome];
            $group = $this->get_record('groups', $data);
            if (!$group) {
                groups_create_group($data);
                $group = $this->get_record('groups', $data);
            }
            if ($this->get_record('groups_members', ['groupid' => $group->id, 'userid' => $this->id_moodle, ])) {
                if (!CLI_SCRIPT) {
                    echo "Já estava no grupo <b>{$polo->nome}</b>.";
                } else {
                    echo "Já estava no grupo {$polo->nome}.";
                }
            } else {
                if (!CLI_SCRIPT) {
                    echo "Adicionado ao grupo <b>{$polo->nome}</b>.";
                } else {
                    echo "Adicionado ao grupo {$polo->nome}.";
                }
                groups_add_member($group->id, $this->id_moodle);
            }
        }
    }
    
    private function generate_password($length = 20){
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`-=~!@#$%^&*()_+,./<>?;:[]{}\|';

        $str = '';
        $max = strlen($chars) - 1;

        for ($i=0; $i < $length; $i++)
            $str .= $chars[random_int(0, $max)];
        
        return $str;
    }
}


class Professor extends Usuario
{
    public function getPolo()
    {
        return null;
    }

    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico("listar_professores_ead", $id_diario, 'Professor', ['nome', 'login', 'tipo', 'email', 'email_secundario', 'status']);
    }

    public static function sincronizar($diario, $oque=null, $list=null)
    {
        Usuario::sincronizar($diario, 'docentes', Professor::ler_rest($diario->id_on_suap));
    }
}


class Aluno extends Usuario
{
    public $polo;

    public function getPolo()
    {
        $polos = Polo::ler_rest();
        foreach ($polos as $attr => $polo) {
            if (intval($polo->id_on_suap) == intval($this->polo)) {
                return $polo;
            }
        }
        return null;
    }

    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico(
            "listar_alunos_ead",
            $id_diario,
            'Aluno',
            ['nome', 'matricula', 'email', 'email_secundario', 'situacao', 'polo']
        );
    }

    public static function sincronizar($diario, $oque=null, $list=null)
    {
        Usuario::sincronizar($diario, 'alunos', Aluno::ler_rest($diario->id_on_suap));
    }
}

class Enrol extends AbstractEntity
{
    public $id;
    public $enroltype;
    public $courseid;
    public $roleid;

    public static function ler_ou_criar($enroltype, $courseid, $roleid)
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
