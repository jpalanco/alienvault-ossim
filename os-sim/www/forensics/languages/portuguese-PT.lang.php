<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/


//locale
DEFINE('_LOCALESTR1', 'pt_PT.ISO8859-1');
DEFINE('_LOCALESTR2', 'pt_PT.utf-8');
DEFINE('_LOCALESTR3', 'portuguese');
DEFINE('_STRFTIMEFORMAT', '%a %d de %b de %Y %H:%M:%S');
//common phrases
DEFINE('_CHARSET', 'UTF-8');
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Nome:');
DEFINE('_FRMPWD', 'Senha:');
DEFINE('_SOURCE', 'Origem');
DEFINE('_SOURCENAME', 'Nome da Origem');
DEFINE('_DEST', 'Destino');
DEFINE('_DESTNAME', 'Nome do Destino');
DEFINE('_SORD', 'Origem ou Destino');
DEFINE('_EDIT', 'Editar');
DEFINE('_DELETE', 'Apagar');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Nome');
DEFINE('_INTERFACE', 'Interface');
DEFINE('_FILTER', 'Filtro');
DEFINE('_DESC', 'Descrição');
DEFINE('_LOGIN', 'Nome');
DEFINE('_ROLEID', 'ID do perfil');
DEFINE('_ENABLED', 'Habilitado');
DEFINE('_SUCCESS', 'Com Sucesso');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensores');
DEFINE('_SIGNATURE', 'Assinatura');
DEFINE('_TIMESTAMP', 'Data');
DEFINE('_NBSOURCEADDR', 'End.&nbsp;de&nbsp;Origem');
DEFINE('_NBDESTADDR', 'End.&nbsp;de&nbsp;Destino');
DEFINE('_NBLAYER4', 'Proto.&nbsp;Camada&nbsp;4');
DEFINE('_PRIORITY', 'Prioridade');
DEFINE('_EVENTTYPE', 'tipo de evento');
DEFINE('_JANUARY', 'Janeiro');
DEFINE('_FEBRUARY', 'Fevereiro');
DEFINE('_MARCH', 'Março');
DEFINE('_APRIL', 'Abril');
DEFINE('_MAY', 'Maio');
DEFINE('_JUNE', 'Junho');
DEFINE('_JULY', 'Julho');
DEFINE('_AUGUST', 'Agosto');
DEFINE('_SEPTEMBER', 'Setembro');
DEFINE('_OCTOBER', 'Outubro');
DEFINE('_NOVEMBER', 'Novembro');
DEFINE('_DECEMBER', 'Dezembro');
DEFINE('_LAST', 'Último');
DEFINE('_FIRST', 'Primeiro');
DEFINE('_TOTAL', 'Total');
DEFINE('_ALERT', 'Alerta');
DEFINE('_ADDRESS', 'Endereço');
DEFINE('_UNKNOWN', 'Desconhecido');
DEFINE('_AND', 'E');
DEFINE('_OR', 'OU');
DEFINE('_IS', 'é');
DEFINE('_ON', 'em');
DEFINE('_IN', 'em');
DEFINE('_ANY', 'qualquer');
DEFINE('_NONE', 'nenhum');
DEFINE('_HOUR', 'Hora');
DEFINE('_DAY', 'Dia');
DEFINE('_MONTH', 'Mês');
DEFINE('_YEAR', 'Ano');
DEFINE('_ALERTGROUP', 'Grupo de Alertas');
DEFINE('_ALERTTIME', 'Hora dos alertas');
DEFINE('_CONTAINS', 'contém');
DEFINE('_DOESNTCONTAIN', 'não contem');
DEFINE('_SOURCEPORT', 'porta origem');
DEFINE('_DESTPORT', 'porta destino');
DEFINE('_HAS', 'tem');
DEFINE('_HASNOT', 'não tem');
DEFINE('_PORT', 'Porta');
DEFINE('_FLAGS', 'Flags');
DEFINE('_MISC', 'Misc');
DEFINE('_BACK', 'Voltar');
DEFINE('_DISPYEAR', '{ ano }');
DEFINE('_DISPMONTH', '{ mês }');
DEFINE('_DISPHOUR', '{ hora }');
DEFINE('_DISPDAY', '{ dia }');
DEFINE('_DISPTIME', '{ hora }');
DEFINE('_ADDADDRESS', 'Adicionar Endereço');
DEFINE('_ADDIPFIELD', 'Adicionar Campo IP');
DEFINE('_ADDTIME', 'Adicionar Hora');
DEFINE('_ADDTCPPORT', 'Adicionar Porta TCP');
DEFINE('_ADDTCPFIELD', 'Adicionar Campo TCP');
DEFINE('_ADDUDPPORT', 'Adicionar Porta UDP');
DEFINE('_ADDUDPFIELD', 'Adicionar Campo UDP');
DEFINE('_ADDICMPFIELD', 'Adicionar Campo ICMP');
DEFINE('_ADDPAYLOAD', 'Adicionar Payload');
DEFINE('_MOSTFREQALERTS', 'alertas mais frequentes');
DEFINE('_MOSTFREQPORTS', 'portas mais frequentes');
DEFINE('_MOSTFREQADDRS', 'endereços IP mais frequentes');
DEFINE('_LASTALERTS', 'alertas mais recentes');
DEFINE('_LASTPORTS', 'portas mais recentes');
DEFINE('_LASTTCP', 'alertas TCP mais recentes');
DEFINE('_LASTUDP', 'alertas UDP mais recentes');
DEFINE('_LASTICMP', 'alertas ICMP mais recentes');
DEFINE('_QUERYDB', 'Pesquisar na BD');
DEFINE('_QUERYDBP', 'Pesquisar+na+BD'); //Igual a _QUERYDB onde os espaços são '+'s.
//Deveria ser algo como: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Seleccionado(s)');
DEFINE('_ALLONSCREEN', 'Todos no ecrã');
DEFINE('_ENTIREQUERY', 'Toda a pesquisa');
DEFINE('_OPTIONS', 'Opções');
DEFINE('_LENGTH', 'comprimento');
DEFINE('_CODE', 'código');
DEFINE('_DATA', 'dados');
DEFINE('_TYPE', 'tipo');
DEFINE('_NEXT', 'Próximo');
DEFINE('_PREVIOUS', 'Anterior');
//Menu items
DEFINE('_HOME', 'Início');
DEFINE('_SEARCH', 'Pesquisa');
DEFINE('_AGMAINT', 'Manutenção do Grupo de Alertas');
DEFINE('_USERPREF', 'Preferências do Utilizador');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administração');
DEFINE('_GALERTD', 'Gráfico de Alertas');
DEFINE('_GALERTDT', 'Gráfico de Alertas por Tempo');
DEFINE('_USERMAN', 'Controlo de Utilizadores');
DEFINE('_LISTU', 'Lista de utilizadores');
DEFINE('_CREATEU', 'Criar um utilizador');
DEFINE('_ROLEMAN', 'Gestão de Perfis');
DEFINE('_LISTR', 'Lista de Perfis');
DEFINE('_CREATER', 'Criar um Perfil');
DEFINE('_LISTALL', 'Listar Tudo');
DEFINE('_CREATE', 'Criar');
DEFINE('_VIEW', 'Ver');
DEFINE('_CLEAR', 'Limpar');
DEFINE('_LISTGROUPS', 'Listar Grupos');
DEFINE('_CREATEGROUPS', 'Criar Grupo');
DEFINE('_VIEWGROUPS', 'Ver Grupo');
DEFINE('_EDITGROUPS', 'Editar Grupo');
DEFINE('_DELETEGROUPS', 'Apagar Grupo');
DEFINE('_CLEARGROUPS', 'Limpar Grupo');
DEFINE('_CHNGPWD', 'Mudar Senha');
DEFINE('_DISPLAYU', 'Mostrar Utilizador');
//base_footer.php
DEFINE('_FOOTER', '( Por <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> e grupo <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">do projecto BASE</A><BR>Baseado no ACID de Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Utilizador inexistente ou senha incorreta!<br>Por favor tente novamente');
// base_main.php
DEFINE('_MOSTRECENT', 'Alertas mais recentes - ');
DEFINE('_MOSTFREQUENT', 'Alertas mais frequentes - ');
DEFINE('_ALERTS', ' alertas:');
DEFINE('_ADDRESSES', ' endereços');
DEFINE('_ANYPROTO', 'qualquer protocolo');
DEFINE('_UNI', 'únicos');
DEFINE('_LISTING', 'lista');
DEFINE('_TALERTS', 'Alertas de Hoje: ');
DEFINE('_SOURCEIP', 'IP Origem');
DEFINE('_DESTIP', 'IP Destino');
DEFINE('_L24ALERTS', 'Alertas das últimas 24 Horas: ');
DEFINE('_L72ALERTS', 'Alertas das últimas 72 Horas: ');
DEFINE('_UNIALERTS', ' Alertas Únicos');
DEFINE('_LSOURCEPORTS', 'Portas de origem mais recentes: ');
DEFINE('_LDESTPORTS', 'Portas de destino mais recentes: ');
DEFINE('_FREGSOURCEP', 'Portas de origem mais frequentes: ');
DEFINE('_FREGDESTP', 'Portas de destino mais frequentes: ');
DEFINE('_QUERIED', 'Consultado em');
DEFINE('_DATABASE', 'BD:');
DEFINE('_SCHEMAV', 'Versão do Esquema:');
DEFINE('_TIMEWIN', 'Janela de tempo:');
DEFINE('_NOALERTSDETECT', 'nenhum alerta detectado');
DEFINE('_USEALERTDB', 'Usar Base de Dados de Alertas');
DEFINE('_USEARCHIDB', 'Usar Base de Dados de Arquivo');
DEFINE('_TRAFFICPROBPRO', 'Carácter do Tráfego por Protocolo');
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Adicionado(s) com Sucesso');
DEFINE('_NOPWDCHANGE', 'Não foi possível trocar a sua senha: ');
DEFINE('_NOUSER', 'Utilizador não existe!');
DEFINE('_OLDPWD', 'Senha antiga incorrecta!');
DEFINE('_PWDCANT', 'Não foi possível mudar a sua senha: ');
DEFINE('_PWDDONE', 'A sua senha foi mudada!');
DEFINE('_ROLEEXIST', 'Perfil já existe');
DEFINE('_ROLEIDEXIST', 'ID do perfil já existe');
DEFINE('_ROLEADDED', 'Perfil adicionada com sucesso');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'Administração de Perfis do BASE');
DEFINE('_FRMROLEID', 'ID do Perfil:');
DEFINE('_FRMROLENAME', 'Nome do Perfil:');
DEFINE('_FRMROLEDESC', 'Descrição:');
DEFINE('_UPDATEROLE', 'Actualizar Perfil');
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE: Administração de Utilizadores');
DEFINE('_FRMFULLNAME', 'Nome Completo:');
DEFINE('_FRMROLE', 'Perfil:');
DEFINE('_FRMUID', 'ID do Usuário:');
DEFINE('_SUBMITQUERY', 'Inserir');
DEFINE('_UPDATEUSER', 'Actualizar Utilizador');
//admin/index.php
DEFINE('_BASEADMIN', 'BASE: Administração');
DEFINE('_BASEADMINTEXT', 'Por favor selecione uma opção à esquerda.');
//base_action.inc.php
DEFINE('_NOACTION', 'Não foi especificada nenhuma acção nos alertas');
DEFINE('_INVALIDACT', ' é uma acção inválida');
DEFINE('_ERRNOAG', 'Não foi possível adicionar alertas porque o GA não foi especificado');
DEFINE('_ERRNOEMAIL', 'Não foi possível enviar e-mail de alertas porque nenhum foi especificado');
DEFINE('_ACTION', 'ACÇÃO');
DEFINE('_CONTEXT', 'contexto');
DEFINE('_ADDAGID', 'ADICIONAR ao GA (pelo ID)');
DEFINE('_ADDAG', 'ADICIONAR-Novo-AG');
DEFINE('_ADDAGNAME', 'ADICIONAR ao GA (por Nome)');
DEFINE('_CREATEAG', 'Criar GA (por Nome)');
DEFINE('_CLEARAG', 'Apagar do GA');
DEFINE('_DELETEALERT', 'Apagar alerta(s)');
DEFINE('_EMAILALERTSFULL', 'Alerta(s) de E-mail (completo)');
DEFINE('_EMAILALERTSSUMM', 'Alerta(s) de E-mail (sumário)');
DEFINE('_EMAILALERTSCSV', 'Alerta(s) de E-mail (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Arquivo de alerta(s) (cópia)');
DEFINE('_ARCHIVEALERTSMOVE', 'Arquivo de alerta(s) (mover)');
DEFINE('_IGNORED', 'Ignorado ');
DEFINE('_DUPALERTS', ' alerta(s) duplicado(s)');
DEFINE('_ALERTSPARA', ' alerta(s)');
DEFINE('_NOALERTSSELECT', 'Não foi selecionado nenhum alerta ou o');
DEFINE('_NOTSUCCESSFUL', 'não teve sucesso');
DEFINE('_ERRUNKAGID', 'O GA ID especificado não existe (o GA provávelmente não existe)');
DEFINE('_ERRREMOVEFAIL', 'Falhou ao remover o novo GA');
DEFINE('_GENBASE', 'Gerado por BASE');
DEFINE('_ERRNOEMAILEXP', 'ERRO DE EXPORTAÇÃO: Não foi possível enviar alertas para');
DEFINE('_ERRNOEMAILPHP', 'Verificar a configuração de mensagens em PHP.');
DEFINE('_ERRDELALERT', 'Erro ao apagar alerta');
DEFINE('_ERRARCHIVE', 'Erro de arquivo:');
DEFINE('_ERRMAILNORECP', 'ERRO DE MENSAGEM: Nenhum destino Especificado');
//base_cache.inc.php
DEFINE('_ADDED', 'Adicionado(s) ');
DEFINE('_HOSTNAMESDNS', ' hostnames para a cache IP DNS');
DEFINE('_HOSTNAMESWHOIS', ' hostnames para a cache Whois');
DEFINE('_ERRCACHENULL', 'ERRO de Cache: Coluna NULA de evento encontrada?');
DEFINE('_ERRCACHEERROR', 'ERRO DE CACHE DE EVENTO:');
DEFINE('_ERRCACHEUPDATE', 'Não foi possível actualizar a cache de eventos');
DEFINE('_ALERTSCACHE', ' alerta(s) para a cache de Alertas');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Não foi possível abrir arquivo de trace do SQL');
DEFINE('_ERRSQLCONNECT', 'Erro ao conectar à BD: ');
DEFINE('_ERRSQLCONNECTINFO', '<P>Verfique as variáveis de conexão à BD em <I>base_conf.php</I>
              <PRE>
               = $alert_dbname   : Base de dados MySQL onde os alertas estão gravados 
               = $alert_host     : host onde a base de dados está gravada
               = $alert_port     : porta onde a base de dados está gravada
               = $alert_user     : nome do utilizador da base de dados
               = $alert_password : senha para o utilizador
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Erro ao conectar à BD: ');
DEFINE('_ERRSQLDB', 'ERRO da base de dados:');
DEFINE('_DBALCHECK', 'Verificando lib de abstração da BD em');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Erro ao carregar lib de abstração da BD: </B> de ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Verifique a variável da lib de abstração de BD <CODE>$DBlib_path</CODE> no <CODE>base_conf.php</CODE>
            <P>
            A biblioteca de Base de Dados actual utilizada é o ADODB, que pode ser descarregada
            em <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Tipo de Base de Dados Inválido Especificado');
DEFINE('_ERRSQLDBTYPEINFO1', 'A variável <CODE>\$DBtype</CODE> em <CODE>base_conf.php</CODE> foi selecionada para o tipo não reconhecido de ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Somente as seguintes base de dados são suportadas: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE: ERRO FATAL:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Carregado em');
DEFINE('_SECONDS', 'segundo(s)');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Não foi possível resolver o endereço');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Query Results Output Header');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName desconhecido');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority desconhecido');
DEFINE('_UNCLASS', 'não-classificado');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'Dados codificados como');
DEFINE('_NODENCODED', '(nenhuma conversão de dados, assumindo critério de codificação nativa da BD)');
DEFINE('_SHORTJAN', 'Jan');
DEFINE('_SHORTFEB', 'Fev');
DEFINE('_SHORTMAR', 'Mar');
DEFINE('_SHORTAPR', 'Abr');
DEFINE('_SHORTMAY', 'Mai');
DEFINE('_SHORTJUN', 'Jun');
DEFINE('_SHORTJLY', 'Jul');
DEFINE('_SHORTAUG', 'Ago');
DEFINE('_SHORTSEP', 'Set');
DEFINE('_SHORTOCT', 'Out');
DEFINE('_SHORTNOV', 'Nov');
DEFINE('_SHORTDEC', 'Dez');
DEFINE('_DISPSIG', '{ assinatura }');
DEFINE('_DISPANYCLASS', '{ qualquer Classificação }');
DEFINE('_DISPANYPRIO', '{ qualquer Prioridade }');
DEFINE('_DISPANYSENSOR', '{ qualquer Sensor }');
DEFINE('_DISPADDRESS', '{ endereço }');
DEFINE('_DISPFIELD', '{ campo }');
DEFINE('_DISPPORT', '{ porta }');
DEFINE('_DISPENCODING', '{ codificação }');
DEFINE('_DISPCONVERT2', '{ Converter Em }');
DEFINE('_DISPANYAG', '{ qualquer Grupo de Alertas }');
DEFINE('_DISPPAYLOAD', '{ payload }');
DEFINE('_DISPFLAGS', '{ flags }');
DEFINE('_SIGEXACTLY', 'exactamente');
DEFINE('_SIGROUGHLY', 'parecido');
DEFINE('_SIGCLASS', 'Classificação da Assinatura');
DEFINE('_SIGPRIO', 'Prioridade da Assinatura');
DEFINE('_SHORTSOURCE', 'Orig');
DEFINE('_SHORTDEST', 'Dest');
DEFINE('_SHORTSOURCEORDEST', 'Orig ou Dest');
DEFINE('_NOLAYER4', 'sem camada 4');
DEFINE('_INPUTCRTENC', 'Tipo de codificação para o critério');
DEFINE('_CONVERT2WS', 'Converter em (na procura)');
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'ERRO PHP: Uma sessão (usuário) PHP customizada foi detectada. Porém, o BASE não foi configurado para explicitamente usar esse handler customizado.  Configure <CODE>use_user_session=1</CODE> em <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'ERRO PHP: Um handler de sessão (usuário) PHP customizado foi configurado, mas o código handler definido no <CODE>user_session_path</CODE> é inválido.');
DEFINE('_PHPERRORCSESSIONVAR', 'ERRO PHP: Um handler de sessão (usuário) PHP customizado foi configurado, mas a implementação deste handler não foi especificada no BASE.  Se deseja um handler customizado, configure a variável <CODE>user_session_path</CODE> em <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Sessão Registada');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Removendo');
DEFINE('_FROMCRIT', 'do critério');
DEFINE('_ERRCRITELEM', 'Elemento do critério inválido');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List');
DEFINE('_DISPLAYING', 'Exibindo');
DEFINE('_DISPLAYINGTOTAL', 'Exibindo alertas %d-%d de %s total');
DEFINE('_NOALERTS', 'Não foi encontrado nenhum alerta.');
DEFINE('_QUERYRESULTS', 'Resultados da Consulta');
DEFINE('_QUERYSTATE', 'Estado da Consulta');
DEFINE('_DISPACTION', '{ acção }');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'O nome do GA especificado é inválido. Tente novamente!');
DEFINE('_ERRAGNAMEEXIST', 'O GA especificado não existe.');
DEFINE('_ERRAGIDSEARCH', 'A pesquisa GA ID especificada é inválida. Tente novamente!');
DEFINE('_ERRAGLOOKUP', 'Erro ao procurar pelo GA ID');
DEFINE('_ERRAGINSERT', 'Erro ao inserir novo GA');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Manutenção de Grupos de Alertas (GA)');
DEFINE('_ERRAGUPDATE', 'Erro ao atualizar o GA');
DEFINE('_ERRAGPACKETLIST', 'Erro removendo lista de pacotes para o GA:');
DEFINE('_ERRAGDELETE', 'Erro removendo o GA');
DEFINE('_AGDELETE', 'REMOVIDO com sucesso');
DEFINE('_AGDELETEINFO', 'informação removida');
DEFINE('_ERRAGSEARCHINV', 'O critério de pesquisa inserido é inválido. Tente novamente!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Não foi encontrado nenhum GA com esse critério.');
DEFINE('_NOALERTGOUPS', 'Não existem Grupos de Alertas');
DEFINE('_NUMALERTS', '# Alertas');
DEFINE('_ACTIONS', 'Ações');
DEFINE('_NOTASSIGN', 'ainda não definido');
DEFINE('_SAVECHANGES', 'Guardar alterações');
DEFINE('_CONFIRMDELETE', 'Confirmar Eiminação');
DEFINE('_CONFIRMCLEAR', 'Confirmar Limpeza');
//base_common.php
DEFINE('_PORTSCAN', 'Alertas de Portscan');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Não foi possível criar INDEX para');
DEFINE('_DBINDEXCREATE', 'INDEX criado com sucesso para');
DEFINE('_ERRSNORTVER', 'Pode ser uma versão antiga.  Somente bases de dados de alerta criadas pelo Snort 1.7-beta0 ou superior são suportadas');
DEFINE('_ERRSNORTVER1', 'A seguinte base de dados');
DEFINE('_ERRSNORTVER2', 'parece estar incompleta/inválida');
DEFINE('_ERRDBSTRUCT1', 'A versão da base de dados é válida, mas a estrutura de BD do BASE');
DEFINE('_ERRDBSTRUCT2', 'não está presente. Use a <A HREF="base_db_setup.php">página de Setup</A> para configurar e optimizar a BD.');
DEFINE('_ERRPHPERROR', 'ERRO DO PHP');
DEFINE('_ERRPHPERROR1', 'Versão incompatível');
DEFINE('_ERRVERSION', 'Versão');
DEFINE('_ERRPHPERROR2', 'do PHP é muito antiga. Por favor, atualize para a versão 4.0.4 ou superior');
DEFINE('_ERRPHPMYSQLSUP', '<B>Compilação do PHP incompleta</B>: <FONT>o suporte ao MySQL necessário para
               ler a base de dados de alertas não foi compilado no PHP.  
               Por favor, recompile o PHP com as bibliotecas necessárias (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>Compilação do PHP incompleta</B>: <FONT>o suporte ao PostgreSQL necessário para
               ler a base de dados de alertas não foi compilado no PHP.  
               Por favor, recompile o PHP com as bibliotecas necessárias (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>Compilação do PHP incompleta</B>: <FONT>o suporte ao MS SQL Server necessário para
                   ler a base de dados de alertas não foi compilado no PHP.  
                   Por favor, recompile o PHP com as bibliotecas necessárias (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>Compilação do PHP incompleta</B>: <FONT>o suporte ao Oracle necessário para
                   ler a base de dados de alertas não foi compilado no PHP.  
                   Por favor, recompile o PHP com as bibliotecas necessárias (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Título do gráfico:');
DEFINE('_CHARTTYPE', 'Tipo de gráfico:');
DEFINE('_CHARTTYPES', '{ tipo de gráfico }');
DEFINE('_CHARTPERIOD', 'Periodo do gráfico:');
DEFINE('_PERIODNO', 'sem periodo');
DEFINE('_PERIODWEEK', '7 (uma semana)');
DEFINE('_PERIODDAY', '24 (dia inteiro)');
DEFINE('_PERIOD168', '168 (24x7)');
DEFINE('_CHARTSIZE', 'Tamanho: (largura x altura)');
DEFINE('_PLOTMARGINS', 'Margens de desenho: (esq x dir x cima x baixo)');
DEFINE('_PLOTTYPE', 'Tipo de desenho:');
DEFINE('_TYPEBAR', 'barras');
DEFINE('_TYPELINE', 'linhas');
DEFINE('_TYPEPIE', 'tarte');
DEFINE('_CHARTHOUR', '{hora}');
DEFINE('_CHARTDAY', '{dia}');
DEFINE('_CHARTMONTH', '{mês}');
DEFINE('_GRAPHALERTS', 'Desenhar gráfico');
DEFINE('_AXISCONTROLS', 'Controlo dos eixos X / Y');
DEFINE('_CHRTTYPEHOUR', 'Tempo (hora) vs. Número de Alertas');
DEFINE('_CHRTTYPEDAY', 'Tempo (dia) vs. Número de Alertas');
DEFINE('_CHRTTYPEWEEK', 'Tempo (semana) vs. Número de Alertas');
DEFINE('_CHRTTYPEMONTH', 'Tempo (mês) vs. Número de Alertas');
DEFINE('_CHRTTYPEYEAR', 'Tempo (ano) vs. Número de Alertas');
DEFINE('_CHRTTYPESRCIP', 'Endereço IP (Src.) vs. Número de Alertas');
DEFINE('_CHRTTYPEDSTIP', 'Endereço IP (Dst.) vs. Número de Alertas');
DEFINE('_CHRTTYPEDSTUDP', 'Porta UDP (Dst.) vs. Número de Alertas');
DEFINE('_CHRTTYPESRCUDP', 'Porta UDP (Src.) vs. Número de Alertas');
DEFINE('_CHRTTYPEDSTPORT', 'Porta TCP (Dst.) vs. Número de Alertas');
DEFINE('_CHRTTYPESRCPORT', 'Porta TCP (Src.) vs. Número de Alertas');
DEFINE('_CHRTTYPESIG', 'Classificação (Sig.) vs. Número de Alertas');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. Número de Alertas');
DEFINE('_CHRTBEGIN', 'Início do gráfico:');
DEFINE('_CHRTEND', 'Fim do gráfico:');
DEFINE('_CHRTDS', 'Origem dos dados:');
DEFINE('_CHRTX', 'Eixo X');
DEFINE('_CHRTY', 'Eixo Y');
DEFINE('_CHRTMINTRESH', 'Valor de limite mínimo');
DEFINE('_CHRTROTAXISLABEL', 'Rodar etiquetas dos eixos (90 degrees)');
DEFINE('_CHRTSHOWX', 'Mostrar linhas de grelha do eixo X');
DEFINE('_CHRTDISPLABELX', 'Mostrar etiqueta do eixo X a cada');
DEFINE('_CHRTDATAPOINTS', 'pontos de dados');
DEFINE('_CHRTYLOG', 'Eixo Y logarítmico');
DEFINE('_CHRTYGRID', 'Mostrar linhas de grelha do eixo Y');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'Gráfico BASE');
DEFINE('_ERRCHRTNOTYPE', 'Não foi especificado nenhum tipo de gráfico');
DEFINE('_ERRNOAGSPEC', 'Não foi especificado nenhum GA. Usando todos alertas.');
DEFINE('_CHRTDATAIMPORT', 'Iniciando importação de dados');
DEFINE('_CHRTTIMEVNUMBER', 'Tempo vs. Número de Alertas');
DEFINE('_CHRTTIME', 'Tempo');
DEFINE('_CHRTALERTOCCUR', 'Ocorrência de Alertas');
DEFINE('_CHRTSIPNUMBER', 'IP de Origem vs. Número de Alertas');
DEFINE('_CHRTSIP', 'Endereço IP de Origem');
DEFINE('_CHRTDIPALERTS', 'IP de Destino vs. Número de Alertas');
DEFINE('_CHRTDIP', 'Endereço IP de Destino');
DEFINE('_CHRTUDPPORTNUMBER', 'Porta UDP (Destino) vs. Número de Alertas');
DEFINE('_CHRTDUDPPORT', 'Porta UDP Dest.');
DEFINE('_CHRTSUDPPORTNUMBER', 'Porta UDP (Origem) vs. Número de Alertas');
DEFINE('_CHRTSUDPPORT', 'Porta UDP Orig.');
DEFINE('_CHRTPORTDESTNUMBER', 'Porta TCP (Destino) vs. Número de Alertas');
DEFINE('_CHRTPORTDEST', 'Porta TCP Port');
DEFINE('_CHRTPORTSRCNUMBER', 'Porta TCP (Origem) vs. Número de Alertas');
DEFINE('_CHRTPORTSRC', 'Porta TCP Orig.');
DEFINE('_CHRTSIGNUMBER', 'Classificação da Assinatura vs. Número de Alertas');
DEFINE('_CHRTCLASS', 'Classificação');
DEFINE('_CHRTSENSORNUMBER', 'Sensor vs. Número de Alertas');
DEFINE('_CHRTHANDLEPERIOD', 'Manipulando Período se necessário');
DEFINE('_CHRTDUMP', 'Mostrando dados ... (escrevendo somente cada');
DEFINE('_CHRTDRAW', 'Desenhando gráfico');
DEFINE('_ERRCHRTNODATAPOINTS', 'Nenhum ponto de dados para desenhar');
DEFINE('_GRAPHALERTDATA', 'Gráficos de Alertas');
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Manutenção');
DEFINE('_MNTPHP', 'Compilação do PHP:');
DEFINE('_MNTCLIENT', 'CLIENTE:');
DEFINE('_MNTSERVER', 'SERVIDOR:');
DEFINE('_MNTSERVERHW', 'HW DO SERVIDOR:');
DEFINE('_MNTPHPVER', 'VERSÃO DO PHP:');
DEFINE('_MNTPHPAPI', 'API DO PHP:');
DEFINE('_MNTPHPLOGLVL', 'Nível do log do PHP:');
DEFINE('_MNTPHPMODS', 'Módulos Carregados:');
DEFINE('_MNTDBTYPE', 'Tipo de BD:');
DEFINE('_MNTDBALV', 'Versão da abstração da BD:');
DEFINE('_MNTDBALERTNAME', 'Nome da BD de ALERTA:');
DEFINE('_MNTDBARCHNAME', 'Nome da BD de ARQUIVO:');
DEFINE('_MNTAIC', 'Informações da cache de Alertas:');
DEFINE('_MNTAICTE', 'Total de Eventos:');
DEFINE('_MNTAICCE', 'Eventos na Cache:');
DEFINE('_MNTIPAC', 'Cache de Endereços IP');
DEFINE('_MNTIPACUSIP', 'IP de origem únicos:');
DEFINE('_MNTIPACDNSC', 'DNS na Cache:');
DEFINE('_MNTIPACWC', 'Whois na Cache:');
DEFINE('_MNTIPACUDIP', 'IP de destino únicos:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Par (sid,cid) inválido');
DEFINE('_QAALERTDELET', 'Alerta REMOVIDO');
DEFINE('_QATRIGGERSIG', 'Assinatura que despoletou');
DEFINE('_QANORMALD', 'Vista normal');
DEFINE('_QAPLAIND', 'Vista plana');
DEFINE('_QANOPAYLOAD', 'Foi usado log rápido portanto o payload foi descartado');
//base_qry_common.php
DEFINE('_QCSIG', 'assinatura');
DEFINE('_QCIPADDR', 'Endereços IP');
DEFINE('_QCIPFIELDS', 'Campos IP');
DEFINE('_QCTCPPORTS', 'Portas TCP');
DEFINE('_QCTCPFLAGS', 'Flags TCP');
DEFINE('_QCTCPFIELD', 'Campos TCP');
DEFINE('_QCUDPPORTS', 'Portas UDP');
DEFINE('_QCUDPFIELDS', 'Campos UDP');
DEFINE('_QCICMPFIELDS', 'Campos ICMP');
DEFINE('_QCDATA', 'Dados');
DEFINE('_QCERRCRITWARN', 'Aviso de critério:');
DEFINE('_QCERRVALUE', 'Um valor de');
DEFINE('_QCERRFIELD', 'Um campo de');
DEFINE('_QCERROPER', 'Um operador de');
DEFINE('_QCERRDATETIME', 'Um valor data/hora de');
DEFINE('_QCERRPAYLOAD', 'Um valor payload de');
DEFINE('_QCERRIP', 'Um endereço IP de');
DEFINE('_QCERRIPTYPE', 'Um endereço IP do tipo');
DEFINE('_QCERRSPECFIELD', ' foi inserido para um campo de protocolo, mas o campo em particular não foi especificado.');
DEFINE('_QCERRSPECVALUE', 'foi selecionado indicando que deve ser um critério, mas nenhum valor foi especificado para a comparação.');
DEFINE('_QCERRBOOLEAN', 'Multiplos campos de protocolo inseridos sem um operador boleano (ex.: E, OU) entre eles.');
DEFINE('_QCERRDATEVALUE', 'foi selecionado indicando que um critério de data/hora deve ser comparado, mas nenhum valor foi especificado.');
DEFINE('_QCERRINVHOUR', '(Hora Inválida) Nenhum critério de data foi inserido com a hora especificada.');
DEFINE('_QCERRDATECRIT', 'foi selecionado indicando que um critério de data/hora deve ser comparado, mas nenhum valor foi especificado.');
DEFINE('_QCERROPERSELECT', 'foi inserido mas nenhum operador foi selecionado.');
DEFINE('_QCERRDATEBOOL', 'Múltiplos critério de Data/Hora foram inseridos sem um operador boleano (ex.: E, OU) entre eles.');
DEFINE('_QCERRPAYCRITOPER', 'foi inserido para um critério de campo de payload, mas um operator (e.g. has, has not) não foi especificado.');
DEFINE('_QCERRPAYCRITVALUE', 'foi selecionado indicando que o payload deve ser um critério, mas nenhum valor para comparação foi especificado.');
DEFINE('_QCERRPAYBOOL', 'Múltiplos critérios de dados de payload inseridos sem um operador boleano (ex.: E, OU) entre eles.');
DEFINE('_QCMETACRIT', 'Meta Critério');
DEFINE('_QCIPCRIT', 'Critério de IP');
DEFINE('_QCPAYCRIT', 'Critério de Payload');
DEFINE('_QCTCPCRIT', 'Critério de TCP');
DEFINE('_QCUDPCRIT', 'Critério de UDP');
DEFINE('_QCICMPCRIT', 'Critério de ICMP');
DEFINE('_QCLAYER4CRIT', 'Critério de Camada 4');
DEFINE('_QCERRINVIPCRIT', 'Critério de endereço IP inválido');
DEFINE('_QCERRCRITADDRESSTYPE', 'foi inserido como um valor de critério, mas o tipo de endereço (ex.: origem, destino) não foi especificado.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicando que um endereço IP deve ser um critério, mas nenhum endereço para comparação foi especificado.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'foi selecionado (em #');
DEFINE('_QCERRCRITIPIPBOOL', 'Foi inserido um critério de Multiplos endereços IP sem um operador boleano (ex.: E, OU) entre eles.');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Classificação');
DEFINE('_QFRMSORTNONE', 'Nenhuma');
DEFINE('_QFRMTIMEA', 'Data (ascendente)');
DEFINE('_QFRMTIMED', 'Data (descendente)');
DEFINE('_QFRMSIG', 'Assinatura');
DEFINE('_QFRMSIP', 'IP de origem');
DEFINE('_QFRMDIP', 'IP de destino');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Resumo Estatístico');
DEFINE('_QSCTIMEPROF', ' Perfil de Tempo');
DEFINE('_QSCOFALERTS', 'dos alertas');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Lista de Alertas');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Categorias:');
DEFINE('_SCSENSORTOTAL', 'Sensores/Total:');
DEFINE('_SCTOTALNUMALERTS', 'Número Total de Alertas:');
DEFINE('_SCSRCIP', 'Ends. IP de Origem:');
DEFINE('_SCDSTIP', 'Ends. IP de Destino:');
DEFINE('_SCUNILINKS', 'Links IP Únicos');
DEFINE('_SCSRCPORTS', 'Portas de Origem: ');
DEFINE('_SCDSTPORTS', 'Portas de Destino: ');
DEFINE('_SCSENSORS', ' Sensores');
DEFINE('_SCCLASS', 'classificações');
DEFINE('_SCUNIADDRESS', ' Endereços Únicos: ');
DEFINE('_SCSOURCE', 'Origem');
DEFINE('_SCDEST', 'Destino');
DEFINE('_SCPORT', 'Porta');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'ERRO DE EVENTO PORTSCAN: ');
DEFINE('_PSEVENTERRNOFILE', 'Não foi especificado nenhum arquivo na variável \$portscan_file.');
DEFINE('_PSEVENTERROPENFILE', 'Não foi possível abrir o arquivo de eventos Portscan');
DEFINE('_PSDATETIME', 'Data/Hora');
DEFINE('_PSSRCIP', 'IP de Origem');
DEFINE('_PSDSTIP', 'IP de Destino');
DEFINE('_PSSRCPORT', 'Porta de Origem');
DEFINE('_PSDSTPORT', 'Porta de Destino');
DEFINE('_PSTCPFLAGS', 'Flags TCP');
DEFINE('_PSTOTALOCC', 'Total de<BR> Ocorrências');
DEFINE('_PSNUMSENSORS', 'Núm de Sensores');
DEFINE('_PSFIRSTOCC', 'Primeira<BR> Ocorrência');
DEFINE('_PSLASTOCC', 'Última<BR> Ocorrência');
DEFINE('_PSUNIALERTS', 'Alertas Únicos');
DEFINE('_PSPORTSCANEVE', 'Eventos Portscan');
DEFINE('_PSREGWHOIS', 'Consulta registo (whois) em');
DEFINE('_PSNODNS', 'nenhuma consulta DNS realizada');
DEFINE('_PSNUMSENSORSBR', 'Núm de <BR>Sensores');
DEFINE('_PSOCCASSRC', 'Ocorrências <BR>como Orig.');
DEFINE('_PSOCCASDST', 'Ocorrências <BR>como Dest.');
DEFINE('_PSWHOISINFO', 'Informações Whois');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'Links IP');
DEFINE('_SIPLSOURCEFGDN', 'FQDN de Origem');
DEFINE('_SIPLDESTFGDN', 'FQDN de Destino');
DEFINE('_SIPLDIRECTION', 'Direção');
DEFINE('_SIPLPROTO', 'Protocolo');
DEFINE('_SIPLUNIDSTPORTS', 'Portas de Destino Únicas');
DEFINE('_SIPLUNIEVENTS', 'Eventos Únicos');
DEFINE('_SIPLTOTALEVENTS', 'Total de Eventos');
DEFINE('_PSTOTALHOSTS', 'Total de endereços pesquisados');
DEFINE('_PSDETECTAMONG', 'Foram detectados %d alertas únicos entre %d no endereço %s');
DEFINE('_PSALLALERTSAS', 'Todos os alertas com %s/%s como');
DEFINE('_PSSHOW', 'Mostra');
DEFINE('_PSEXTERNAL', 'Externo');
//base_stat_ports.php
DEFINE('_UNIQ', 'Únicas');
DEFINE('_DSTPS', 'Porta(s) de Destino');
DEFINE('_SRCPS', 'Porta(s) de Origem');
DEFINE('_OCCURRENCES', 'Ocorrências');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Lista de Sensores');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Perfil de tempo dos Alertas');
DEFINE('_BSTTIMECRIT', 'Critério de Tempo');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Nenhum critério de perfil foi especificado!</B>  Clique em "hora", "dia", ou "mês" para escolher a granularidade do agrupamento estatístico.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>O parâmetro de intervalo de tempo a ser usado não foi especificado!</B>  Escolha "em" para especificar uma única data, ou "entre" para especificar um intervalo.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Nenhum parâmetro de ano foi especificado!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Nenhum parâmetro de mês foi especificado!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Nenhum parâmetro de dia foi especificado!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Caracterizar por');
DEFINE('_TIMEON', 'em');
DEFINE('_TIMEBETWEEN', 'entre');
DEFINE('_PROFILEALERT', 'Caracterizar Alerta');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Ends. de Origem Únicos');
DEFINE('_SUASRCIP', 'Ends. IP de Origem');
DEFINE('_SUAERRCRITADDUNK', 'ERRO DE CRITÉRIO: tipo de end. desconhecido -- assumindo end. de destino');
DEFINE('_UNIDADD', 'Endereços Únicos');
DEFINE('_SUADSTIP', 'Ends. IP de Destino');
DEFINE('_SUAUNIALERTS', 'Alertas&nbsp;Únicos');
DEFINE('_SUASRCADD', 'Ends.&nbsp;de&nbsp;Origem');
DEFINE('_SUADSTADD', 'Ends.&nbsp;de&nbsp;Destino');
//base_user.php
DEFINE('_BASEUSERTITLE', 'Preferências de usuário do BASE');
DEFINE('_BASEUSERERRPWD', 'A senha não pode ser nula ou as duas senhas não coincidem!');
DEFINE('_BASEUSEROLDPWD', 'Senha antiga:');
DEFINE('_BASEUSERNEWPWD', 'Nova senha:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Repita a nova senha:');
DEFINE('_LOGOUT', 'Logout');
?>
