<?php
require_once('lib.php');
require_once('../lib/coursecatlib.php');
require_once('../course/lib.php');
require_once('../user/lib.php');
require_once("../enrol/locallib.php");
require_once("../enrol/externallib.php");

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

class AbstractEntity
{
  public $id_on_suap;
  public $id_moodle;

  function __construct($id_on_suap)
  {
    $this->id_on_suap = $id_on_suap;
  }

  function ja_associado()
  {
    $instance = $this->ler_moodle();
    return $instance && $instance->id_moodle;
  }

  static function ler_rest_generico($service, $id, $class, $properties)
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

  function execute($sql, $params)
  {
    global $DB;
    return $DB->execute($sql, $params);
  }

  function get_records_sql($sql, $params)
  {
    global $DB;
    return $DB->get_records_sql($sql, $params);
  }

  function get_records($tablename, $filters)
  {
    $params = [];
    $where = '';
    foreach ($filters as $fieldname => $value) {
      $where .= $where == '' ? "WHERE $fieldname = ?" : " AND $fieldname = ?";
      $params[] = $value;
    }
    return $this->get_records_sql("SELECT * FROM {{$tablename}} $where",
                                  $params);
  }

  function get_record($tablename, $filters)
  {
    return array_shift($this->get_records($tablename, $filters));
  }

  function getIdSUAP()
  {
    $clasname = strtolower(get_class($this));
    return "{'{$clasname}':'{$this->id_on_suap}'}";
  }

  function associar()
  {
    $tablename = $this->getTablename();
    $this->execute("UPDATE {{$tablename}} SET id_suap=NULL WHERE id_suap=?",
                   [$this->getIdSUAP()]);
    $this->execute("UPDATE {{$tablename}}  SET id_suap=? WHERE id=?",
                   [$this->getIdSUAP(), $this->id_moodle]);
  }

  function ler_moodle()
  {
    $table = $this->getTablename();
    $filter = ['id_suap' => $this->getIdSUAP()];
    $instance = $this->get_record($table, $filter);
    if (!$instance) {
      $rows = $this->get_records($table, ['idnumber' => $this->getCodigo()]);
      if (count($rows) == 1) {
        $this->execute("UPDATE {{$table}} SET id_suap=? WHERE idnumber=?",
                       [$this->getIdSUAP(), $this->getCodigo()]);
        $instance = $this->get_record($table, $filter);
      }
      if (!$instance) {
        return $this;
      }
    }
    merge_objects($instance, $this);
    $this->context = $this->get_record('context',
                                       ['contextlevel' => $this->getContextLevel(),
                                       'instanceid' => $this->id_moodle]);
    return $this;

  }

//  static function criar_contexto($contextlevel, $instanceid, $base_path)
//  {
//    global $DB;
//    $context = new stdClass();
//    $context->contextlevel = $contextlevel;
//    $context->instanceid = $instanceid;
//    $context->depth = count(explode('/', $base_path));
//    $context->id = $DB->insert_record('context', $context);
//    $context->path = "{$base_path}/{$context->id}";
//    $DB->update_record('context', $context);
//  }
}


class Category extends AbstractEntity
{
  public $codigo;

  function __construct($id_on_suap, $codigo)
  {
    parent::__construct($id_on_suap);
    $this->codigo = $codigo;
  }

  function getTablename()
  {
    return "course_categories";
  }

  function getContextLevel()
  {
    return CONTEXT_CATEGORY;
  }

  function getCodigo()
  {
    return $this->codigo;
  }

  public static function render_selectbox($level=0)
  {
    global $DB;
    $has_suap_ids = array_keys($DB->get_records_sql('SELECT id FROM {course_categories} WHERE id_suap IS NOT NULL'));
    foreach (coursecat::make_categories_list('moodle/category:manage') as $key=>$label):
      if ( ($level>0) && (count(split(' / ', $label)) != $level) ) {continue;}
      $jah_associado = in_array($key, $has_suap_ids) ?  "disabled" : "";
      echo "<label class='as_row $jah_associado' ><input type='radio' value='$key' name='categoria' $jah_associado />$label</label>";
    endforeach;
  }
/*
  protected function ler_parent($id_parent)
  {
    return null;
  }

  function criar()
  {
    // Recupera o parent
    $parent = $this->ler_parent($this->id_curso);

    // Cria a categoria
    $record = coursecat::create(array(
      "name"=>"Turma: {$this->codigo}",
      "idnumber"=>$this->codigo,
      "description"=>'',
      "descriptionformat"=>1,
      "parent"=>$parent->id,
    ));

    // Associa ao SUAP
    $this->associar($record->id);
    // $this->id_moodle = $record->id;
  }
*/
/*
  public static function ler_categorias()
  {
    global $DB;
    $courses = [];
    $result = $DB->get_records('course_categories');
    foreach ($result as $course) {
      $courses[] = $course;
    }
    return $courses;
  }
*/
}


class Curso extends Category
{
  public $nome;
  public $descricao;

  function __construct($id_on_suap, $codigo, $nome, $descricao)
  {
    parent::__construct($id_on_suap, $codigo);
    $this->nome = $nome;
    $this->descricao = $descricao;
  }

  function getLabel()
  {
    return $this->descricao;
  }

  static function ler_rest($ano_letivo, $periodo_letivo)
  {
    $response = json_request("listar_cursos_ead",
                             ['id_campus' => SUAP_ID_CAMPUS_EAD,
                              'ano_letivo' => $ano_letivo,
                              'periodo_letivo' => $periodo_letivo]);
    $result = [];
    foreach ($response as $id_on_suap => $o) {
      $result[] = new Curso($id_on_suap, $o['codigo'], $o['nome'], $o['descricao']);
    }
    usort($result, 'cmp_by_label');
    return $result;
  }

  function importar($ano, $periodo)
  {
    $this->ler_moodle();
    echo "<li>Importando do curso <b>{$this->name}</b> diários do período <b>$ano.$periodo</b>...</li><ol>";
    /*
    foreach (Turma::ler_rest($id_curso, $ano, $periodo) as ) {
      echo "<li>";
      $turma->importar($id_curso, ;
      echo "</li>";
    };
    */
    echo "</ol>";
  }

  function auto_associar($ano_inicial, $periodo_inicial, $ano_final, $periodo_final) {
    global $DB;
    $ano_inicial = (int)$ano_inicial;
    $periodo_inicial = (int)$periodo_inicial;
    $ano_final = (int)$ano_final;
    $periodo_final = (int)$periodo_final;

    for ($ano=$ano_inicial; $ano<=$ano_final; $ano++)
    {
      for ($periodo=1; $periodo<=2; $periodo++) {
        if ( ($ano==$ano_inicial && $periodo<$periodo_inicial) || ($ano==$ano_final && $periodo>$periodo_final) ) {
            continue;
        }
        foreach (Turma::ler_rest($this->id_on_suap, $ano, $periodo) as $turma_suap) {
          if ($turma_suap->ja_associado()) {
            echo "<li class='notifysuccess'>A turma SUAP <strong>{$turma_suap->codigo}</strong> JÁ está associada à categoria <strong>{$turma_suap->name}</strong> no Moodle.<ol>";
          } else {
            echo "<li class='notifyproblem'>A turma SUAP <strong>{$turma_suap->codigo}</strong> NÃO está associada a uma categoria no Moodle.<ol>";
          }
          $diarios = Diario::ler_rest($turma_suap);
          if (count($diarios) == 0) {
            echo "<li class='notifymessage'>Não existem diários para esta turma.</li>";
          }
          foreach ($diarios as $diario_suap):
            if ($diario_suap->ja_associado()) {
              echo "<li class='notifysuccess'>O diário SUAP <b>{$diario_suap->getCodigo()}</b> <strong>JÁ</strong> está associado ao course {$diario_suap->name} no Moodle.";
            } else {
              echo "<li>O <b>diário SUAP {$diario_suap->getLabel()}</b> NÃO está associado a um <b>course no Moodle</b>.";
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
  public $id_curso;

  function getLabel()
  {
    return $this->codigo;
  }

  static function ler_rest($id_curso, $ano_letivo, $periodo_letivo)
  {
    $response = json_request("listar_turmas_ead",
                             ['id_curso' => $id_curso,
                              'ano_letivo' => $ano_letivo,
                              'periodo_letivo' => $periodo_letivo]);
    $result = [];
    foreach ($response as $id_on_suap => $obj) {
      $result[] = new Turma($id_on_suap, $obj['codigo']);
    }
    usort($result, 'cmp_by_label');
    return $result;
  }

  function importar()
  {
    $curso = (new Curso($id_curso))->ler_moodle();
    echo "Importando a turma <b>{$this->codigo}</b> (<b>{$this->id_moodle}</b>)...";
    /*
    // Se não existe uma category para esta turma criá-la como filha do curso
    $turma_moodle = Turma::ler_moodle($id_turma, $codigo);
    if (!$turma_moodle) {
        $turma = new Turma($id_turma, $codigo);
        $turma->id_curso = $id_curso;
        $turma->criar();
        echo " A turma foi criada.";
        $categoryid = $turma->id_moodle;
    } else {
        echo " A turma já existe.";
        $categoryid = $turma->id;
    }
    */
    /*
    echo " A turma já existe. <a href='../course/management.php?categoryid={$categoryid}' class='btn btn-mini'>Acessar</a>";
    echo "<ol>";
    foreach (Diario::ler_rest($id_turma) as $diario) {
        Diario::importar($id_turma, $diario->id, $diario);
    };
    echo "</ol>";
    */
  }

  function criar()
  {
    /*
    // Recupera o curso
    $parent = Curso::ler_moodle($this->id_curso);
// $DB->record_exists('course_categories', array('idnumber' => $data->idnumber))
    // Cria a categoria
    $record = coursecat::create(array(
        "name"=>"Turma: {$this->codigo}",
        "idnumber"=>$this->codigo,
        "description"=>'',
        "descriptionformat"=>1,
        "parent"=>$parent->id,
    ));

    // Associa ao SUAP
    $this->associar($record->id);
    // $this->id_moodle = $record->id;
    */
  }
}


class Diario extends AbstractEntity
{
  public $sigla;
  public $situacao;
  public $descricao;
  public $turma;
  function __construct($id_on_suap, $sigla=null, $situacao=null, $descricao=null, $turma = null)
  {
    parent::__construct($id_on_suap);
    $this->sigla = $sigla;
    $this->situacao = $situacao;
    $this->descricao = $descricao;
    $this->id_turma = $id_turma;
    $this->turma = $turma;
  }

  function getTablename()
  {
    return "course";
  }

  function getLabel()
  {
    return $this->sigla;
  }

  function getCodigo()
  {
    return $this->turma ? "{$this->turma->codigo}.{$this->sigla}" : NULL;
  }

  function getContextLevel()
  {
    return CONTEXT_COURSE;
  }

  static function ler_rest($turma)
  {
    $response = json_request("listar_diarios_ead", ['id_turma' => $turma->id_on_suap]);
    $result = [];
    foreach ($response as $id_on_suap => $obj) {
        $result[] = new Diario($id_on_suap, $obj['sigla'], $obj['situacao'], $obj['descricao'], $turma);
    }
    usort($result, 'cmp_by_label');
    return $result;
  }

  function importar($id_turma, $id_diario, $diario = null)
  {
    /*
    $turma = Turma::ler_moodle($id_turma);
    echo "<li>Importando o diário (<b>$id_diario</b>)";
    // Se não existe um course para este curso criá-la como filho do período
    $diario_moodle = Diario::ler_moodle($id_diario);
    if (!$diario_moodle) {
      $diario_moodle = new Diario();
      $diario_moodle->id_turma = $id_turma;
      $diario_moodle->criar($diario);
      echo " <b>{$diario_moodle->fullname}</b> ... foi criado com sucesso. ";
    } else {
      echo " <b>{$diario_moodle->fullname}</b> ... já existia. ";
    }
    echo "<a class='btn btn-mini' href='../course/management.php?categoryid={$diario_moodle->category}&courseid={$diario_moodle->id}'>Configuração</a>";
    echo "<a class='btn btn-mini' href='../course/view.php?id={$diario_moodle->id}'>Acessar</a>";

    echo "</li><ol>";
    // Professor::importar($id_diario);
    // Aluno::importar($id_diario);
    echo "</ol>";
    */
  }

  function criar($diario = null)
  {
    /*
    global $DB;

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
    $diario->associar($record->id);
    $this->id = $record->id;
    $this->fullname = $record->fullname;
    $this->category = $parent->id;
    */
  }

  /*
  public static function ler_cursos()
  {
    global $DB;
    $req = "SELECT id, fullname, idnumber, id_suap FROM {course} ORDER BY fullname";
    $courses = [];
    $result = $DB->get_records_sql($req);
    foreach ($result as $course) {
      $courses[] = $course;
    }
    return $courses;
  }
  */
}

/*
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

    function __construct($id, $nome = null, $login = null, $tipo = null, $email = null, $email_secundario = null, $status = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->login = $login;
        $this->tipo = $tipo;
        $this->email = $email;
        $this->email_secundario = $email_secundario;
        $this->status = $status;
    }

    function getUsername()
    {
        return $this->login ? $this->login : $this->matricula;
    }

    function getEmail()
    {
        return $this->email ? $this->email : $this->email_secundario;
    }

    function getSuspended()
    {
        return $this->getStatus() == 'ativo' ? 0 : 1;
    }

    function getStatus()
    {
        return $this->status ? $this->status : $this->situacao;
    }

    function getTipo()
    {
        return $this->tipo ? $this->tipo : 'Aluno';
    }

    function getRoleId()
    {
        global $enrol_roleid;
        return $enrol_roleid[$this->getTipo()];
    }

    function getEnrolType()
    {
        global $enrol_type;
        return $enrol_type[$this->getTipo()];
    }

    protected static function importar($id_diario, $oque, $list)
    {
        try {
            $diario = Diario::ler_moodle($id_diario);
            echo "<li>Sincronizando <b>" . count($list) .  " $oque</b> do diário <b>{$diario->fullname}</b> ...<ol>";

            foreach ($list as $instance) {
                $instance->sincronizar();
                $instance->arrolar($diario);
            }
            echo "</ol></li>";
        } catch (Exception $e) {
            raise_error($e);
        }
    }

    function sincronizar()
    {
        global $DB, $default_user_preferences;
        $usuario = $DB->get_record("user", array("username" => $this->getUsername()));
        $nome_parts = explode(' ', $this->nome);
        $lastname = array_pop($nome_parts);
        $firstname = implode(' ', $nome_parts);
        if (!$usuario) {
            $this->id_moodle = user_create_user(array(
                'lastname'=>$lastname,
                'firstname'=>$firstname,
                'username'=>$this->getUsername(),
                'auth'=>'ldap',
                'password'=>'not cached',
                'email'=>$this->getEmail(),
                'suspended'=>$this->getSuspended(),
                'timezone'=>'99',
                'lang'=>'pt_br',
                'confirmed'=>1,
            ), false);

            foreach ($default_user_preferences as $key=>$value) {
                $this->criar_user_preferences($key, $value);
            }
            $usuario->id = $this->id_moodle;
            $oper = 'Criado';
        } else {
            user_update_user(array(
                'id'=>$usuario->id,
                'suspended'=>$this->getSuspended(),
                'lastname'=>$lastname,
                'firstname'=>$firstname,
            ), false);
            $oper = 'Atualizado';
        }

        echo "<li>$oper <b>{$this->getUsername()} - {$this->nome} ({$this->getTipo()})</b> <a href='../user/profile.php?id={$usuario->id}' class='btn btn-mini'>Acessar</a></li>";
        $this->id_moodle = $usuario->id;
    }

    function criar_user_preferences($name, $value)
    {
        global $DB;
        $DB->insert_record('user_preferences',
                           (object)array( 'userid'=>$this->id_moodle, 'name'=>$name, 'value'=>$value, ));
    }

    function arrolar($diario_moodle) {
        global $DB, $USER;
        $enrol = Enrol::ler_ou_criar($this->getEnrolType(), $diario_moodle->id, $this->getRoleId());

        echo "<li>";
        $enrolment = $DB->get_record('user_enrolments', array('enrolid'=>$enrol->id,'userid'=>$this->id_moodle));
        if (!$enrolment) {
            $id = $DB->insert_record('user_enrolments', (object)array(
                'enrolid'=>$enrol->id,
                'userid'=>$this->id_moodle,
                'status'=>0,
                'timecreated'=>time(),
                'timemodified'=>time(),
                'timestart'=>time(),
                'modifierid'=>$USER->id,
                'timeend'=>0,
            ));
            echo " Arrolado, ";
        } else {
            echo " Já arrolado. ";
        }

        $assignment = $DB->get_record('role_assignments', array('roleid'=>$this->getRoleId(), 'contextid'=>$diario_moodle->context->id, 'userid'=>$this->id_moodle, 'itemid'=>0));
        if (!$assignment) {
            $id2 = $DB->insert_record('role_assignments', (object)array(
                'roleid'=>$this->getRoleId(),
                'contextid'=>$diario_moodle->context->id,
                'userid'=>$this->id_moodle,
                'itemid'=>0,
            ));
            echo " atribuído ";
        } else {
            echo " já atribuído ";
        }
        echo " <b>{$this->getUsername()} - {$this->nome} ({$this->getTipo()} )</b></li>";
    }
}


class Professor extends Usuario
{
    public static function ler_rest($id_diario)
    {
        return AbstractEntity::ler_rest_generico("listar_professores_ead", $id_diario, 'Professor', ['nome', 'login', 'tipo', 'email', 'email_secundario', 'status']);
    }

    public static function importar($id_diario, $oque=null, $list=null)
    {
        try {
            Usuario::importar($id_diario, 'docentes', Professor::ler_rest($id_diario));
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

    public static function importar($id_diario, $oque=null, $list=null)
    {
        try {
            Usuario::importar($id_diario, 'docentes', Aluno::ler_rest($id_diario));
        } catch (Exception $e) {
            raise_error($e);
        }
    }
}
*/

/*
class Polo extends AbstractEntity
{
  public $nome;

  function __construct($id_on_suap, $nome)
  {
    parent::__construct($id_on_suap);
    $this->nome = $nome;
  }

  public static function ler_rest()
  {
    $response = json_request("listar_polos_ead");
    $result = [];
    foreach ($response as $id_on_suap => $obj) {
      $result[] = new Polo($id_on_suap, $obj['descricao']);
    }
    return $result;
  }
}


class Campus extends AbstractEntity
{
  public $nome;
  public $sigla;

  function __construct($id_on_suap, $nome, $sigla)
  {
    parent::__construct($id_on_suap);
    $this->nome = $nome;
    $this->sigla = $sigla;
  }

  public static function ler_rest()
  {
    $response = json_request("listar_campus_ead");
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

  function __construct($id_on_suap, $tipo, $periodo, $qtd_avaliacoes, $descricao_historico, $optativo, $descricao, $sigla)
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
    $response = json_request("listar_componentes_curriculares_ead",
                             array('id_curso' => $id_curso));
    $result = [];
    foreach ($response as $id_on_suap => $o) {
      $result[] = new ComponenteCurricular($id_on_suap, $o['tipo'], $o['periodo'], $o['qtd_avaliacoes'], $o['descricao_historico'], $o['optativo'], $o['descricao'], $o['sigla']);
    }
    return $result;
  }
}
*/
