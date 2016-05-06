<?php
require_once('lib.php');
require_once('../lib/coursecatlib.php');
require_once('../course/lib.php');
require_once('../user/lib.php');
require_once("../enrol/locallib.php");
require_once("../enrol/externallib.php");

class AbstractEntity
{
  public $id_suap;
  public $id_moodle;

  function __construct($id_suap)
  {
    $this->id_suap = $id_suap;
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
    $rows = $this->get_records($tablename, $filters);
    dumpd($rows);
    return $rows[0];
  }

  function jid_suap()
  {
    $clasname = strtolower(get_class($this));
    return "{'{$clasname}':'{$this->id_suap}'}";
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

  public function __construct($id_suap, $codigo)
  {
    parent::__construct($id_suap, $codigo);
  }

  public function ja_associado()
  {
    return $this->get_records('course_categories',
                              ['id_suap' => $this->jid_suap()]);
  }

  public static function render_selectbox($level=0)
  {
    global $DB;
    $has_suap_ids = array_keys($DB->get_records_sql('SELECT id FROM {course_categories} WHERE id_suap IS NOT NULL'));
    // echo "<select name='categoria' rows='10'>";
    foreach (coursecat::make_categories_list('moodle/category:manage') as $key=>$label):
      if ( ($level>0) && (count(split(' / ', $label)) != $level) ) {continue;}
      $jah_associado = in_array($key, $has_suap_ids) ?  "disabled" : "";
      echo "<label class='as_row $jah_associado' ><input type='radio' value='$key' name='categoria' $jah_associado />$label</label>";
      // $jah_associado = in_array($key, $has_suap_ids) ? "jah_associado" : "";
      // echo "<option value='$key' class='$jah_associado'>$label</option>";
    endforeach;
    // echo "</select>";
  }
/*
  protected function ler_parent($id_parent)
  {
    return null;
  }

  public function criar()
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
  function associar()
  {
    $this->execute('UPDATE {course_categories} SET id_suap=NULL WHERE id_suap=?',
                   [$this->jid_suap()]);
    $this->execute('UPDATE {course_categories} SET id_suap=? WHERE id=?',
                   [$this->jid_suap(), $this->id_moodle]);
  }

  public function ler_moodle()
  {
    $instance = $this->get_record("course_categories",
                                  ['id_suap' => $this->jid_suap()]);
    if (!$instance) {
      return null;
    }
    $instance->context = $this->get_record('context',
                                           ['contextlevel' => CONTEXT_CATEGORY,
                                            'instanceid' => $instance->id]);
    return $instance;
  }
}


class Curso extends Category
{
  public $nome;
  public $descricao;

  public function __construct($id_suap, $codigo, $nome, $descricao)
  {
    parent::__construct($id_suap, $codigo);
    $this->nome = $nome;
    $this->descricao = $descricao;
  }

  public static function ler_rest($ano_letivo, $periodo_letivo)
  {
    $response = json_request("listar_cursos_ead",
                             ['id_campus' => SUAP_ID_CAMPUS_EAD,
                              'ano_letivo' => $ano_letivo,
                              'periodo_letivo' => $periodo_letivo]);
    $result = [];
    foreach ($response as $id_suap => $o) {
      $result[] = new Curso($id_suap, $o['nome'], $o['descricao'], $o['codigo']);
    }
    return $result;
  }

  public static function ler_categorias()
  {/*
    global $DB;
    $courses = [];
    $result = $DB->get_records('course_categories');
    foreach ($result as $course) {
      $courses[] = $course;
    }
    return $courses;*/
  }
/*
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

  public static function auto_associar($id_curso, $ano_inicial, $periodo_inicial, $ano_final, $periodo_final) {
      global $DB;
      try {
          $ano_inicial = (int)$ano_inicial;
          $periodo_inicial = (int)$periodo_inicial;
          $ano_final = (int)$ano_final;
          $periodo_final = (int)$periodo_final;

          for ($ano=$ano_inicial; $ano<=$ano_final; $ano++) {
              for ($periodo=1; $periodo<=2; $periodo++) {
                  if ( ($ano==$ano_inicial && $periodo<$periodo_inicial) || ($ano==$ano_final && $periodo>$periodo_final) ) {
                      continue;
                  }
                  foreach (Turma::ler_rest($id_curso, $ano, $periodo) as $turma_suap) {
                      $sql = "SELECT c.* FROM {course_categories} c WHERE idnumber = ?";
                      $data = array($turma_suap->codigo);
                      echo "<li>Turma <b>{$turma_suap->codigo}</b>";
                      $turmas_moodle = $DB->get_records_sql($sql, $data);
                      if (count($turmas_moodle) == 1) {
                          $turma_moodle = array_shift($turmas_moodle);
                          echo " (<b>{$turma_moodle->name}</b>)";
                          $turma_moodle2 = Turma::ler_moodle($turma_suap->id, $turma_suap->codigo);
                          if ($turma_moodle2 && !$turma_moodle2->auto_associado && $turma_moodle2->id == $turma_moodle->id) {
                            echo " - NADA A FAZER: JÁ ASSOCIADO.";
                          } elseif ($turma_moodle2 && $turma_moodle2->id != $turma_moodle->id) {
                            echo " - PROBLEMA: JÁ ASSOCIADO A OUTRA 'CATEGORIA'.";
                          } elseif ($turma_moodle2 && $turma_moodle2->auto_associado) {
                            echo " - ASSOCIADO.";
                          } else {
                            $turma_suap->associar($turma_moodle->id);
                            echo " - ASSOCIADO.";
                          }
                      } elseif (count($turmas_moodle) != 1) {
                          echo " - PROBLEMA: MAIS DE UMA TURMA COM ESTE idnumber.";
                      } else {
                          echo " - NADA A FAZER: NÃO ENCONTRADO.";
                      }
                      echo "<ol>";
                      $diarios = Diario::ler_rest($turma_suap->id);
                      if (count($diarios) > 0) {
                          foreach ($diarios as $diario_suap):
                              $sql = "SELECT * FROM {course} WHERE idnumber = ?";
                              $idnumber_diario = "{$turma_suap->codigo}.{$diario_suap->sigla}";
                              $data = array($idnumber_diario);

                              echo "<li>Diário <b>{$idnumber_diario} ({$diario_suap->id})</b>";
                              $diarios_moodle = $DB->get_records_sql($sql, $data);
                              if (count($diarios_moodle) == 1) {
                                $diario_moodle = array_shift($diarios_moodle);
                                $diario_moodle2 = Diario::ler_moodle($diario_suap->id);
                                if ($diario_moodle2 && !$diario_moodle2->auto_associado && $diario_moodle2->id == $diario_moodle->id) {
                                  echo " - NADA A FAZER: JÁ ASSOCIADO.";
                                } elseif ($diario_moodle2 && $diario_moodle2->id != $diario_moodle->id) {
                                  echo " - PROBLEMA: JÁ ASSOCIADO A OUTRO 'COURSE'.";
                                } elseif ($diario_moodle2 && $diario_moodle2->auto_associado) {
                                  echo " - ASSOCIADO.";
                                } else {
                                  $diario_suap->associar($diario_moodle->id);
                                  echo " - ASSOCIADO.";
                                }
                              } else {
                                  echo " - NADA A FAZER: NÃO ENCONTRADO.";
                              }
                              echo "</li>";
                          endforeach;
                      } else {
                          echo "<li>Não existem diários para esta turma.</li>";
                      }
                      echo "</ol>";
                      echo "</li>";
                  };
              }
          }
      } catch(Exception $e) {
          raise_error($e);
      }
  }
*/
}

/*
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
        return Turma::ler_moodle($this->id, $this->codigo);
    }

    public static function ler_rest($id_curso, $ano_letivo, $periodo_letivo)
    {
        $response = json_request("listar_turmas_ead", array(
            'id_curso' => $id_curso,
            'ano_letivo' => $ano_letivo,
            'periodo_letivo' => $periodo_letivo));
        $result = [];
        foreach ($response as $id => $obj) {
            $result[] = new Turma($id, $obj['codigo']);
        }
        return $result;
    }

    public static function ler_moodle($id_turma, $idnumber=NULL)
    {
        global $DB;
        $turma = $DB->get_record_sql("SELECT c.* FROM {course_categories} c WHERE id_suap = ?", array(Turma::format_id_suap($id_turma)));
        if (!$turma) {
            if ($idnumber==NULL) {
              return null;
            }
            $turmas_moodle = $DB->get_records('course_categories', array('idnumber' => $idnumber));
            if (count($turmas_moodle) != 1) {
              return null;
            } else {
              $sql = 'UPDATE {course_categories} SET id_suap = ? WHERE idnumber = ?';
              $DB->execute($sql, array(Turma::format_id_suap($id_turma), $idnumber));
              $turma = array_shift($turmas_moodle);
              $turma->auto_associado = true;
            }
        }
        $turma->context = $DB->get_record('context', array('contextlevel' => CONTEXT_CATEGORY, 'instanceid' => $turma->id));
        return $turma;
    }

    public function associar($id_suap, $id_categoria)
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
        echo " A turma já existe. <a href='../course/management.php?categoryid={$categoryid}' class='btn btn-mini'>Acessar</a>";
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
// $DB->record_exists('course_categories', array('idnumber' => $data->idnumber))
            // Cria a categoria
            $record = coursecat::create(array(
                "name"=>"Turma: {$this->codigo}",
                "idnumber"=>$this->codigo,
                "description"=>'',
                "descriptionformat"=>1,
                "parent"=>$parent->id,
            ));
            dumpd($record);

            // Associa ao SUAP
            $this->associar($record->id);
            // $this->id_moodle = $record->id;
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
        $result = [];
        foreach ($response as $id => $obj) {
            $result[] = new Diario($id, $obj['sigla'], $obj['situacao'], $obj['descricao']);
        }
        return $result;
    }

    public static function ler_moodle($id_diario)
    {
        global $DB;
        $diario = $DB->get_record_sql("SELECT c.* FROM {course} c WHERE id_suap = ?", array(Diario::format_id_suap($id_diario)));
        if (!$diario) {
            return null;
        }
        $diario->context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $diario->id));
        return $diario;
    }

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

    public function associar($id_suap, $id_curso)
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
    }

    public function criar($diario = null)
    {
        try {
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

  public function __construct($id_suap, $nome)
  {
    parent::__construct($id_suap);
    $this->nome = $nome;
  }

  public static function ler_rest()
  {
    $response = json_request("listar_polos_ead");
    $result = [];
    foreach ($response as $id_suap => $obj) {
      $result[] = new Polo($id_suap, $obj['descricao']);
    }
    return $result;
  }
}


class Campus extends AbstractEntity
{
  public $nome;
  public $sigla;

  public function __construct($id_suap, $nome, $sigla)
  {
    parent::__construct($id_suap);
    $this->nome = $nome;
    $this->sigla = $sigla;
  }

  public static function ler_rest()
  {
    $response = json_request("listar_campus_ead");
    $result = [];
    foreach ($response as $id_suap => $obj) {
      $result[] = new Campus($id_suap, $obj['descricao'], $obj['sigla']);
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

  public function __construct($id_suap, $tipo, $periodo, $qtd_avaliacoes, $descricao_historico, $optativo, $descricao, $sigla)
  {
    parent::__construct($id_suap);
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
    foreach ($response as $id_suap => $o) {
      $result[] = new ComponenteCurricular($id_suap, $o['tipo'], $o['periodo'], $o['qtd_avaliacoes'], $o['descricao_historico'], $o['optativo'], $o['descricao'], $o['sigla']);
    }
    return $result;
  }
}
*/
