
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

# SUAP
  
## Incluir a permissão *Sincronizador Moodle* para o usuário que fará a sincronização.
### Permissão é concedida normalmente pelo diretor acadêmico do Câmpus.

Para isso, faça login no SUAP e no menu lateral vá em *ENSINO* > *Cadastros Gerais* > *Diretorias Acadêmicas* > Aba *Outras Atividades* > Sincronizador Moodle

# CRONTAB

## TESTE ESSA FUNÇÃO ANTES DE COLOCA-LA EM PRODUÇÃO, PARA SABER SE ELA ATENDE SUA NECESSIDADE

### FAÇA ISSO EM UM AMBIENTE DE TESTE

Para utilizar a importação automática vá até as configurações do bloco e ative a atualização atraves do Crontab. Lembramos que para o correto funcionamento do crontab é necessário ativar o crontab do moodle seguindo a documentação encontrada em https://docs.moodle.org/