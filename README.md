
>Estas instruções descrevem como fazer a instalação correta do Plugin "SUAP > Moodle".
Esse plugin foi desenvolvido pela equipe de desenvolvimento da COTIC IFRN EaD.
Plugin desenvolvido baseado no módulo Enrollment block, by Symetrix.
A versão usada no IFSP é mantida pela DSI em colaboração com as CTIs dos câmpus.

# Pré-requisitos
Você irá precisar no mínimo de:

1. Um servidor rodando Moodle 3.1, ou superior;
2. Um navegador com suporte ao Javascript;
3. Um token do SUAP para sincronizar os dados (edu.pode_sincronizar_dados).

# Instalação

Estas instruções assumem que o Moodle está instalado no seu servidor (/var/www/html/moodle).

> Descompacte a versão mais recente do plugin no repositório do gitlab: https://gitlab.ifsp.edu.br/ti/moodle-integrador-suap ;

> Extrair o conteúdo do arquivo para o diretório: */var/www/html/moodle/blocks* e renomear o diretório do plugin para *suap*;

> Faça login no seu ambiente Moodle como usuário administrador;

### O próprio Moodle irá identificar a nova instalação e apresentará a tela para configurar o plugin.

* Configure a:
	* URL do SUAP
	* Token de Autenticação

Clique no botão para finalizar a instalação;

A partir deste ponto você já poderá adicionar o bloco do "SUAP".
> Entre na administração do site > habilitar edição de bloco > Adicionar um bloco > Escolher "SUAP > Moodle"

# Banco de dados

> TODO: Criar a tabela de relacionamento.

O banco de dados deve ser modificado, com a adição das seguintes colunas:

1. Na tabela "mdl_course_categories" adicionar coluna "id_suap"
	> ALTER TABLE 'mdl_course_categories' ADD 'id_suap' VARCHAR(100) NULL ;
2. Na tabela "mdl_course" adicionar coluna "id_suap"
	> ALTER TABLE 'mdl_course' ADD 'id_suap' VARCHAR(100) NULL ;
3. Na tabela "mdl_course_categories" adicionar coluna "custom_css"
	> ALTER TABLE 'mdl_course_categories' ADD 'custom_css' TEXT NULL ;

# SUAP
  
## Incluir a permissão *Sincronizador Moodle* para o usuário que fará a sincronização.
### Permissão é concedida normalmente pelo diretor acadêmico do Câmpus.

Para isso, faça login no SUAP e no menu lateral vá em *ENSINO* > *Cadastros Gerais* > *Diretorias Acadêmicas* > Aba *Outras Atividades* > Sincronizador Moodle

# CRONTAB

## TESTE ESSA FUNÇÃO ANTES DE COLOCA-LA EM PRODUÇÃO, PARA SABER SE ELA ATENDE SUA NECESSIDADE

### FAÇA ISSO EM UM AMBIENTE DE TESTE

Incluir no crontab o seguinte Comando, ele irá importar todos os cursos do ano/semestre corrente
> php /var/www/html/moodle/blocks/suap/cron.php > /dev/null

EX. Para executar todos os dias as 4 horas da manhã
> 0 4 * * * php /var/www/html/moodle/blocks/suap/cron.php > /dev/null