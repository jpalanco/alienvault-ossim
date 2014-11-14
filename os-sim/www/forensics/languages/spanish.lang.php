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
DEFINE('_LOCALESTR1', 'eng_ENG.ISO8859-1'); //NEW
DEFINE('_LOCALESTR2', 'eng_ENG.utf-8'); //NEW
DEFINE('_LOCALESTR3', 'english'); //NEW
DEFINE('_STRFTIMEFORMAT', '%a %B %d, %Y %H:%M:%S'); //NEW - see strftime() sintax
//common phrases
DEFINE('_TITLE', 'Forensics Console ');
DEFINE('_FRMLOGIN', 'Usuario:');
DEFINE('_FRMPWD', 'Clave:');
DEFINE('_SOURCE', 'Origen');
DEFINE('_SOURCENAME', 'Nombre del origen');
DEFINE('_DEST', 'Destino');
DEFINE('_DESTNAME', 'Nombre del dest.');
DEFINE('_SORD', 'Origen o Dest');
DEFINE('_EDIT', 'Editar');
DEFINE('_DELETE', 'Borrar');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Nombre');
DEFINE('_INTERFACE', 'Interfaz');
DEFINE('_FILTER', 'Filtro');
DEFINE('_DESC', 'Descripci&oacute;n');
DEFINE('_LOGIN', 'Usuario');
DEFINE('_ROLEID', 'ID del papel');
DEFINE('_ENABLED', 'Activado');
DEFINE('_SUCCESS', 'Exitoso');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SIGNATURE', 'Firma');
DEFINE('_SENSORS', 'Sensores');
DEFINE('_TIMESTAMP', 'Marca de tiempo');
DEFINE('_NBSOURCEADDR', 'Direcci&oacute;n&nbsp;Origen');
DEFINE('_NBDESTADDR', 'Direcci&oacute;n&nbsp;Dest');
DEFINE('_NBLAYER4', 'Proto&nbsp;capa&nbsp;4');
DEFINE('_PRIORITY', 'Prioridad');
DEFINE('_EVENTTYPE', 'tipo de evento');
DEFINE('_JANUARY', 'Enero');
DEFINE('_FEBRUARY', 'Febrero');
DEFINE('_MARCH', 'Marzo');
DEFINE('_APRIL', 'Abril');
DEFINE('_MAY', 'Mayo');
DEFINE('_JUNE', 'Junio');
DEFINE('_JULY', 'Julio');
DEFINE('_AUGUST', 'Agosto');
DEFINE('_SEPTEMBER', 'Septiembre');
DEFINE('_OCTOBER', 'Octubre');
DEFINE('_NOVEMBER', 'Noviembre');
DEFINE('_DECEMBER', 'Diciembre');
DEFINE('_LAST', 'Ultimo');
DEFINE('_ALERT', 'Alertas');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ADDRESS', 'Direcci&oacute;n');
DEFINE('_UNKNOWN', 'desconocido');
DEFINE('_AND', 'AND'); //NEW
DEFINE('_OR', 'OR'); //NEW
DEFINE('_IS', 'is'); //NEW
DEFINE('_ON', 'on'); //NEW
DEFINE('_IN', 'in'); //NEW
DEFINE('_ANY', 'any'); //NEW
DEFINE('_NONE', 'none'); //NEW
DEFINE('_HOUR', 'Hora');
DEFINE('_DAY', 'D&iacute;a');
DEFINE('_MONTH', 'Mes');
DEFINE('_YEAR', 'a&ntilde;o');
DEFINE('_ALERTGROUP', 'Grupo de Alertas');
DEFINE('_ALERTTIME', 'Tiempo de Alerta');
DEFINE('_CONTAINS', 'contiene');
DEFINE('_DOESNTCONTAIN', 'no contiene');
DEFINE('_SOURCEPORT', 'puerto origen');
DEFINE('_DESTPORT', 'puerto destino');
DEFINE('_HAS', 'tiene');
DEFINE('_HASNOT', 'no tiene');
DEFINE('_PORT', 'Port'); //NEW
DEFINE('_FLAGS', 'Flags'); //NEW
DEFINE('_MISC', 'Misc'); //NEW
DEFINE('_BACK', 'Atr&aacute;s');
DEFINE('_DISPYEAR', '{ a&ntilde;o }');
DEFINE('_DISPMONTH', '{ mes }');
DEFINE('_DISPHOUR', '{ hora }');
DEFINE('_DISPDAY', '{ d&iacute;a }');
DEFINE('_DISPTIME', '{ tiempo }');
DEFINE('_ADDADDRESS', 'A&Ntilde;ADIR Direcci&oacute;n');
DEFINE('_ADDIPFIELD', 'A&Ntilde;ADIR Campo IP');
DEFINE('_ADDTIME', 'A&Ntilde;ADIR TIEMPO');
DEFINE('_ADDTCPPORT', 'A&Ntilde;ADIR Puerto TCP');
DEFINE('_ADDTCPFIELD', 'A&Ntilde;ADIR Campo TCP');
DEFINE('_ADDUDPPORT', 'A&Ntilde;ADIR Puerto UDP');
DEFINE('_ADDUDPFIELD', 'A&Ntilde;ADIR Campo UDP');
DEFINE('_ADDICMPFIELD', 'A&Ntilde;ADIR Campo ICMP');
DEFINE('_ADDPAYLOAD', 'ADD Payload'); //NEW
DEFINE('_MOSTFREQALERTS', 'Alertas M&aacute;s Frecuentes');
DEFINE('_MOSTFREQPORTS', 'Puertos M&aacute;s Frecuentes');
DEFINE('_MOSTFREQADDRS', 'Direcciones IP M&aacute;s Frecuentes');
DEFINE('_LASTALERTS', '&Uacute;ltimas Alertas');
DEFINE('_LASTPORTS', '&Uacute;ltimos Puertos');
DEFINE('_LASTTCP', '&Uacute;ltimas Alertas TCP');
DEFINE('_LASTUDP', '&Uacute;ltimas Alertas UDP');
DEFINE('_LASTICMP', '&Uacute;ltimas Alertas ICMP');
DEFINE('_QUERYDB', 'Query DB'); //NEW
DEFINE('_QUERYDBP', 'Query+DB'); //NEW - Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Selected'); //NEW
DEFINE('_ALLONSCREEN', 'ALL on Screen'); //NEW
DEFINE('_ENTIREQUERY', 'Entire Query'); //NEW
DEFINE('_OPTIONS', 'Options'); //NEW
DEFINE('_LENGTH', 'length'); //NEW
DEFINE('_CODE', 'code'); //NEW
DEFINE('_DATA', 'data'); //NEW
DEFINE('_TYPE', 'type'); //NEW
DEFINE('_NEXT', 'Siguiente');
DEFINE('_PREVIOUS', 'Anterior');
//Menu items
DEFINE('_HOME', 'Inicio');
DEFINE('_SEARCH', 'Buscar');
DEFINE('_AGMAINT', 'Mantenimiento de Grupos de Alertas');
DEFINE('_USERPREF', 'Preferencias');
DEFINE('_CACHE', 'Cache & Estado');
DEFINE('_ADMIN', 'Administraci&oacute;n');
DEFINE('_GALERTD', 'Hacer gr&aacute;fica de datos de alerta');
DEFINE('_GALERTDT', 'Hacer gr&aacute;fica del tiempo de detectar alertas');
DEFINE('_USERMAN', 'Manejar usuarios');
DEFINE('_LISTU', 'Listar usuarios');
DEFINE('_CREATEU', 'Crear usuario');
DEFINE('_ROLEMAN', 'Manejar papeles');
DEFINE('_LISTR', 'Listar papeles');
DEFINE('_LOGOUT', 'Logout');
DEFINE('_CREATER', 'Crear papel');
DEFINE('_LISTALL', 'Listar todo');
DEFINE('_CREATE', 'Crear');
DEFINE('_VIEW', 'Ver');
DEFINE('_CLEAR', 'Limpiar');
DEFINE('_LISTGROUPS', 'Listar Grupos');
DEFINE('_CREATEGROUPS', 'Crear Grupo');
DEFINE('_VIEWGROUPS', 'Ver Grupo');
DEFINE('_EDITGROUPS', 'Editar Grupo');
DEFINE('_DELETEGROUPS', 'Borrar Grupo');
DEFINE('_CLEARGROUPS', 'Aclarar Grupo');
DEFINE('_CHNGPWD', 'Cambiar clave');
DEFINE('_DISPLAYU', 'Ver usuario');
//base_footer.php
DEFINE('_FOOTER', '( por <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> y el equipo del <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">proyecto BASE </A><BR>Basado en ACID por Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Usuario no existe o su clave no fue reconocida!<br>Por favor intenta de nuevo');
// base_main.php
DEFINE('_MOSTRECENT', 'M&aacute;s reciente');
DEFINE('_MOSTFREQUENT', 'M&aacute;s frecuente ');
DEFINE('_ALERTS', ' Alertas:');
DEFINE('_ADDRESSES', ' Direcciones:');
DEFINE('_ANYPROTO', 'cualquier protocolo');
DEFINE('_UNI', '&Uacute;nico');
DEFINE('_LISTING', 'lista');
DEFINE('_TALERTS', 'Alertas de hoy: ');
DEFINE('_L24ALERTS', 'Alertas de los &uacute;ltimos 24 horas: ');
DEFINE('_SOURCEIP', 'IP Origen');
DEFINE('_DESTIP', 'IP Destino');
DEFINE('_L72ALERTS', 'Alertas de los &uacute;ltimos 72 horas: ');
DEFINE('_UNIALERTS', ' Alertas &uacute;nicas');
DEFINE('_LSOURCEPORTS', '&Uacute;ltimos puertos de origen: ');
DEFINE('_LDESTPORTS', '&Uacute;ltimos puertos de destino: ');
DEFINE('_FREGSOURCEP', 'Puertos de origen m&aacute;s frecuentes: ');
DEFINE('_FREGDESTP', 'Puertos de destino m&aacute;s frecuentes: ');
DEFINE('_QUERIED', 'Consultado en');
DEFINE('_DATABASE', 'Base de datos:');
DEFINE('_SCHEMAV', 'Versi&oacute;n de esquema:');
DEFINE('_TIMEWIN', 'Ventana de tiempo:');
DEFINE('_NOALERTSDETECT', 'ninguna alerta detectada');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Perfil de Tr&aacute;fico por Protocolo');
//base_auth.inc.php
DEFINE('_ADDEDSF', 'A&ntilde;adido con &eacute;xito');
DEFINE('_NOPWDCHANGE', 'No se pudo cambiar su clave: ');
DEFINE('_NOUSER', 'No existe ese usuario!');
DEFINE('_OLDPWD', 'Clave antigua no es correcta!');
DEFINE('_PWDCANT', 'No se pudo cambiar su clave: ');
DEFINE('_PWDDONE', 'Su clave ha sido cambiaa!');
DEFINE('_ROLEEXIST', 'Ya existe ese papel');
DEFINE('_ROLEIDEXIST', 'Ya existe ese ID de papel');
DEFINE('_ROLEADDED', 'Papel a&ntilde;adido con exito');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Manejo de Papeles');
DEFINE('_FRMROLEID', 'ID de Papel:');
DEFINE('_FRMROLENAME', 'Nombre de Papel:');
DEFINE('_FRMROLEDESC', 'Descripci&oacute;n:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE Manejo de Usuarios');
DEFINE('_FRMFULLNAME', 'Nombre Completo:');
DEFINE('_FRMROLE', 'Papel:');
DEFINE('_FRMUID', 'ID de Usuario:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Administraci&oacute;n');
DEFINE('_BASEADMINTEXT', 'Por favor, escoge una opci&oacute;n de la izquierda.');
//base_action.inc.php
DEFINE('_NOACTION', 'Ninguna acci&oacute;n fue escogida en las alertas');
DEFINE('_INVALIDACT', ' es una acci&oacute;n inv&aacute;lida');
DEFINE('_ERRNOAG', 'No se pudo a&ntilde;adir alertas porque no se escogi&oacute; ning&uacute;n AG');
DEFINE('_ERRNOEMAIL', 'No se pudieron a&ntilde;adir alertas de e-mail porque no se especific&oacute; ning&uacute;n direcci&oacute;n de e-mail');
DEFINE('_ACTION', 'ACCI&Oacute;N');
DEFINE('_CONTEXT', 'contexto');
DEFINE('_ADDAGID', 'A&Ntilde;ADIR a AG (por ID)');
DEFINE('_ADDAG', 'A&Ntilde;ADIR-Nuevo-AG');
DEFINE('_ADDAGNAME', 'A&Ntilde;ADIR a AG (por Nombre)');
DEFINE('_CREATEAG', 'Crear AG (por Nombre)');
DEFINE('_CLEARAG', 'Aclarar de AG');
DEFINE('_DELETEALERT', 'Borrar alerta(s)');
DEFINE('_EMAILALERTSFULL', 'Email alerta(s) (completo)');
DEFINE('_EMAILALERTSSUMM', 'Email alerta(s) (sumario)');
DEFINE('_EMAILALERTSCSV', 'Email alerta(s) (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Archivar alerta(s) (copiar)');
DEFINE('_ARCHIVEALERTSMOVE', 'Archivar alerta(s) (mover)');
DEFINE('_IGNORED', 'Ignorado ');
DEFINE('_DUPALERTS', ' alerta(s) duplicadas');
DEFINE('_ALERTSPARA', ' alerta(s)');
DEFINE('_NOALERTSSELECT', 'Ningunas alertas fueron seleccionadas o el');
DEFINE('_NOTSUCCESSFUL', 'no fue exitoso');
DEFINE('_ERRUNKAGID', 'Desconocido AG ID especificado (AG probablemente no existe)');
DEFINE('_ERRREMOVEFAIL', 'Fallo en borrar nuevo AG');
DEFINE('_GENBASE', 'Generado por BASE');
DEFINE('_ERRNOEMAILEXP', 'ERROR EN EXPORTACI&Oacute;N: No se pudo mandar alertas exportadas a');
DEFINE('_ERRNOEMAILPHP', 'Por favor revise la configuraci&oacute;n de correos electr&oacute;nicos en PHP.');
DEFINE('_ERRDELALERT', 'Error Borrando Alerta');
DEFINE('_ERRARCHIVE', 'Error en Archivar:');
DEFINE('_ERRMAILNORECP', 'ERROR EN EMAIL: Ning&uacute;n recipiente especificado');
//base_cache.inc.php
DEFINE('_ADDED', 'A&ntilde;adido ');
DEFINE('_HOSTNAMESDNS', ' nombres de maquinas al escondrijo de DNS');
DEFINE('_HOSTNAMESWHOIS', ' nombres de maquinas al escondrijo de Whois');
DEFINE('_ERRCACHENULL', 'ERROR EN ESCONDRIJO: linea de evento NULL encontrado?');
DEFINE('_ERRCACHEERROR', 'ERROR EN ESCONDRIJO:');
DEFINE('_ERRCACHEUPDATE', 'No se pudo actualizar el escondrijo de eventos');
DEFINE('_ALERTSCACHE', ' alerta(s) al escondrijo de alertas');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'No se pudo abrir archivo de rastro SQL');
DEFINE('_ERRSQLCONNECT', 'Error conectando al DB:');
DEFINE('_ERRSQLCONNECTINFO', '<P>Favor de checar los variables de conexion al DB en <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : Nombre de base de datos MySQL donde se guardan las alertas
               = $alert_host     : maquina donde esta el base de datos
               = $alert_port     : puerto del base de datos
               = $alert_user     : usuario para el base de datos
               = $alert_password : clave para el usuario
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Error (p)conectando al DB :');
DEFINE('_ERRSQLDB', 'ERROR de base de datos:');
DEFINE('_DBALCHECK', 'Buscando biblioteca de DB abstracci&oacute;n en ');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Error cargando biblioteca de DB abstracci&oacute;n: </B> de ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Por favor compruebe la variable de la biblioteca de DB abstracci&oacute;n <CODE>$DBlib_path</CODE> en <CODE>base_conf.php</CODE>
            <P>
            En este momento, se usa la biblioteca ADODB, que se puede encontrar en
            at <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Tipo de DB desconocido');
DEFINE('_ERRSQLDBTYPEINFO1', 'La variable <CODE>\$DBtype</CODE> en <CODE>base_conf.php</CODE> tiene el tipo desconocido de ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Solo los tipos siguientes son soportados: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'ERROR FATAL EN BASE:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Cargado en');
DEFINE('_SECONDS', 'segundos');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'No se pudo resolver la direcci&oacute;n');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Cabecera de Resultados de Consulta');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName desconocido');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority desconocido');
DEFINE('_UNCLASS', 'desclasificado');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'datos codificado como');
DEFINE('_NODENCODED', '(sin conversi&oacute;n de datos, asumiendo criterios en codificaci&oacute;n natural del DB)');
DEFINE('_SHORTJAN', 'Ene');
DEFINE('_SHORTFEB', 'Feb');
DEFINE('_SHORTMAR', 'Mar');
DEFINE('_SHORTAPR', 'Abr');
DEFINE('_SHORTMAY', 'May');
DEFINE('_SHORTJUN', 'Jun');
DEFINE('_SHORTJLY', 'Jul');
DEFINE('_SHORTAUG', 'Ago');
DEFINE('_SHORTSEP', 'Sep');
DEFINE('_SHORTOCT', 'Oct');
DEFINE('_SHORTNOV', 'Nov');
DEFINE('_SHORTDEC', 'Dic');
DEFINE('_DISPSIG', '{ firma }');
DEFINE('_DISPANYCLASS', '{ cualquier Clasificaci&oacute;n }');
DEFINE('_DISPANYPRIO', '{ cualquier Prioridad }');
DEFINE('_DISPANYSENSOR', '{ cualquier Sensor }');
DEFINE('_DISPADDRESS', '{ direcci&oacute;n }');
DEFINE('_DISPFIELD', '{ campo }');
DEFINE('_DISPPORT', '{ puerto }');
DEFINE('_DISPENCODING', '{ encoding }'); //NEW
DEFINE('_DISPCONVERT2', '{ Convert To }'); //NEW
DEFINE('_DISPANYAG', '{ cualquier Grupo de Alertas }');
DEFINE('_DISPPAYLOAD', '{ payload }'); //NEW
DEFINE('_DISPFLAGS', '{ flags }'); //NEW
DEFINE('_SIGEXACTLY', 'exactly'); //NEW
DEFINE('_SIGROUGHLY', 'roughly'); //NEW
DEFINE('_SIGCLASS', 'Signature Classification'); //NEW
DEFINE('_SIGPRIO', 'Signature Priority'); //NEW
DEFINE('_SHORTSOURCE', 'Source'); //NEW
DEFINE('_SHORTDEST', 'Dest'); //NEW
DEFINE('_SHORTSOURCEORDEST', 'Src or Dest'); //NEW
DEFINE('_NOLAYER4', 'no layer4'); //NEW
DEFINE('_INPUTCRTENC', 'Input Criteria Encoding Type'); //NEW
DEFINE('_CONVERT2WS', 'Convert To (when searching)'); //NEW
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'ERROR PHP: Se ha detectado una sesi&oacute;n de PHP usuario. Sin embargo, BASE no esta configurado para utilizar esta rutina hecho a la medida. Ponga <CODE>use_user_session=1</CODE> en <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'ERROR PHP: Una sesi&oacute;n usuario esta configurado, pero el codigo especificado en <CODE>user_session_path</CODE> es inv&aacute;lido.');
DEFINE('_PHPERRORCSESSIONVAR', 'ERROR PHP: Una sesi&oacute;n usuario esta configurado, pero el c&oacute;digo para utilizarla no ha sido especificado en BASE. Si desea una sesi&oacute;n hecho a la medida, usa la variable <CODE>user_session_path</CODE> en <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Sesi&oacute;n registrada');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Quitando');
DEFINE('_FROMCRIT', 'de los criterios');
DEFINE('_ERRCRITELEM', 'Criterio inv&aacute;lido');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Lista de consultas pre-hechas');
DEFINE('_DISPLAYING', 'Mostrando');
DEFINE('_DISPLAYINGTOTAL', 'Mostrando alertas %d-%d de %s en total');
DEFINE('_NOALERTS', 'No hay alertas.');
DEFINE('_QUERYRESULTS', 'Resultados de consulta');
DEFINE('_QUERYSTATE', 'Estado de consulta');
DEFINE('_DISPACTION', '{ acci&oacute;n }');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'La b&uacute;squeda por nombre AG especificada es inv&aacute;lia. Int&eacute;ntalo de nuevo.');
DEFINE('_ERRAGNAMEEXIST', 'El AG especificado no existe.');
DEFINE('_ERRAGIDSEARCH', 'La b&uacute;squeda por ID AG especificada es inv&aacute;lida. Intentalo de nuevo.');
DEFINE('_ERRAGLOOKUP', 'Error buscando un AG por ID');
DEFINE('_ERRAGINSERT', 'Error insertando nuevo AG');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Manejar Grupos de Alerta (AG)');
DEFINE('_ERRAGUPDATE', 'Error actualizando el AG');
DEFINE('_ERRAGPACKETLIST', 'Error borrando lista de paquetes por el AG:');
DEFINE('_ERRAGDELETE', 'Error borrando el AG');
DEFINE('_AGDELETE', 'BORRADO con exito');
DEFINE('_AGDELETEINFO', 'informaci&oacute;n borrada');
DEFINE('_ERRAGSEARCHINV', 'Los criterios de busqueda son inv&aacute;lidos.  Intenta de nuevo.');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Ning&uacute;n AG encontrado con esos criterios.');
DEFINE('_NOALERTGOUPS', 'No existen Grupos de Alerta');
DEFINE('_NUMALERTS', '# Alertas');
DEFINE('_ACTIONS', 'Acciones');
DEFINE('_NOTASSIGN', 'a&uacute;n no asignado');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Trafico de Exploraci&oacute;n de Puertos');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'No se pudo CREATE INDEX (crear indice) para');
DEFINE('_DBINDEXCREATE', 'Exitosamente crear INDEX para');
DEFINE('_ERRSNORTVER', 'Quiz&aacute;s es una versi&oacute;n antigua.  Solamente bases de datos creadas con Snort 1.7-beta0 o m&aacute;s reciente est&aacute;n soportados');
DEFINE('_ERRSNORTVER1', 'La base de datos del fondo');
DEFINE('_ERRSNORTVER2', 'parece estar incompleto o inv&aacute;lido');
DEFINE('_ERRDBSTRUCT1', 'La versi&oacute;n de la base de datos es v&aacute;lida, pero no la estructura  DB');
DEFINE('_ERRDBSTRUCT2', 'no est&aacute; presente. Usa la <A HREF="base_db_setup.php">p&aacute;gina de configuraci&oacute;n</A> para inicializar y mejorar la base de datos.');
DEFINE('_ERRPHPERROR', 'ERROR PHP');
DEFINE('_ERRPHPERROR1', 'Versi&oacute;n incompatible');
DEFINE('_ERRVERSION', 'Versi&oacute;n');
DEFINE('_ERRPHPERROR2', 'de PHP es demasiado antigua.  Por favor, instala la versi&oacute;n 4.0.4 o mayor');
DEFINE('_ERRPHPMYSQLSUP', '<B>Instalaci&oacute;n de PHP incompleta</B>: <FONT>a su instalaci&oacute;n de PHP le falta soporte
			   para MySQL para usar la base de datos de alertas.
               Es necesario recompilar PHP con la biblioteca necesaria (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>Instalaci&oacute;n de PHP incompleto</B>: <FONT>a su instalaci&oacute;n de PHP le falta soporte
			   para PostgreSQL para usar la base de datos de alertas.
               Es necesario recompilar PHP con la biblioteca necesaria (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>Instalaci&oacute;n de PHP incompleto</B>: <FONT>a su instalaci&oacute;n de PHP le falta soporte
			   para MSSQL para usar la base de datos de alertas.
               Es necesario recompilar PHP con la biblioteca necesaria (<CODE>--with-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'T&iacute;tulo de Gr&aacute;fica:');
DEFINE('_CHARTTYPE', 'Chart Type:'); //NEW
DEFINE('_CHARTTYPES', '{ chart type }'); //NEW
DEFINE('_CHARTPERIOD', 'Chart Period:'); //NEW
DEFINE('_PERIODNO', 'no period'); //NEW
DEFINE('_PERIODWEEK', '7 (a week)'); //NEW
DEFINE('_PERIODDAY', '24 (whole day)'); //NEW
DEFINE('_PERIOD168', '168 (24x7)'); //NEW
DEFINE('_CHARTSIZE', 'Size: (width x height)'); //NEW
DEFINE('_PLOTMARGINS', 'Plot Margins: (left x right x top x bottom)'); //NEW
DEFINE('_PLOTTYPE', 'Plot type:'); //NEW
DEFINE('_TYPEBAR', 'bar'); //NEW
DEFINE('_TYPELINE', 'line'); //NEW
DEFINE('_TYPEPIE', 'pie'); //NEW
DEFINE('_CHARTHOUR', '{hora}'); //NEW
DEFINE('_CHARTDAY', '{dia}'); //NEW
DEFINE('_CHARTMONTH', '{mês}'); //NEW
DEFINE('_GRAPHALERTS', 'Graph Alerts'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y AXIS CONTROLS'); //NEW
DEFINE('_CHRTTYPEHOUR', 'Tiempo (hora) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEDAY', 'Tiempo (d&iacute;a) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEWEEK', 'Tiempo (semana) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEMONTH', 'Tiempo (mes) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEYEAR', 'Tiempo (a&ntilde;o) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPESRCIP', 'Orig. direcci&oacute;n IP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEDSTIP', 'Dest. direcci&oacute;n IP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEDSTUDP', 'Dest. Puerto UDP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPESRCUDP', 'Orig. Puerto UDP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPEDSTPORT', 'Dest. Puerto TCP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPESRCPORT', 'Orig. Puerto TCP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPESIG', 'Classificaci&oacute;n de Firma vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. N&uacute;mero de Alertas');
DEFINE('_CHRTBEGIN', 'Origen de la gr&aacute;fica:');
DEFINE('_CHRTEND', 'Final de la gr&aacutefica:');
DEFINE('_CHRTDS', 'Origen de Datos:');
DEFINE('_CHRTX', 'Eje X');
DEFINE('_CHRTY', 'Eje Y');
DEFINE('_CHRTMINTRESH', 'Valor de Umbral M&iacute;nimo');
DEFINE('_CHRTROTAXISLABEL', 'Girar etiquetas de eje (90 grados)');
DEFINE('_CHRTSHOWX', 'Muestra lineas de rejilla del eje X');
DEFINE('_CHRTDISPLABELX', 'Mostrar etiqueta del eje X cada');
DEFINE('_CHRTDATAPOINTS', 'puntos de dato');
DEFINE('_CHRTYLOG', 'Eje Y logar&iacute;tmico');
DEFINE('_CHRTYGRID', 'Muestra lineas de rejilla del eje Y');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'Gr&aacute;fica de BASE');
DEFINE('_ERRCHRTNOTYPE', 'Ning&uacute;n tipo de gr&aacute;fica especificado');
DEFINE('_ERRNOAGSPEC', 'Ning&uacute;n AG especificado. Usando todas las alertas.');
DEFINE('_CHRTDATAIMPORT', 'Importando datos');
DEFINE('_CHRTTIMEVNUMBER', 'Tiempo vs. N&uacute;mero de Alertas');
DEFINE('_CHRTTIME', 'Tiempo');
DEFINE('_CHRTALERTOCCUR', 'Sucesos de Alerta');
DEFINE('_CHRTSIPNUMBER', 'Origen IP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTSIP', 'Direcci&oacute;n IP del Origen');
DEFINE('_CHRTDIPALERTS', 'Destino IP vs. N&uacute;mero de Alertas');
DEFINE('_CHRTDIP', 'Direcci&oacute;n IP del Destino');
DEFINE('_CHRTUDPPORTNUMBER', 'Puerto UDP (Destino) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTDUDPPORT', 'Dest. Puerto UDP');
DEFINE('_CHRTSUDPPORTNUMBER', 'Puerto UDP (Origen) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTSUDPPORT', 'Orig. Puerto UDP');
DEFINE('_CHRTPORTDESTNUMBER', 'Puerto TCP (Destino) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTPORTDEST', 'Dest. Puerto TCP');
DEFINE('_CHRTPORTSRCNUMBER', 'Puerto TCP (Origen) vs. N&uacute;mero de Alertas');
DEFINE('_CHRTPORTSRC', 'Orig. Puerto TCP');
DEFINE('_CHRTSIGNUMBER', 'Clasificaci&oacute;n de Firma vs. N&uacute;mero de Alertas');
DEFINE('_CHRTCLASS', 'Clasificaci&oacute;n');
DEFINE('_CHRTSENSORNUMBER', 'Sensor vs. N&uacute;mero de Alertas');
DEFINE('_CHRTHANDLEPERIOD', 'Encargando del punto si se necesita');
DEFINE('_CHRTDUMP', 'Descargando datos ... (escribiendo cada ');
DEFINE('_CHRTDRAW', 'Dibujando gr&aacute;fica');
DEFINE('_ERRCHRTNODATAPOINTS', 'No hay datos para dibujar');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Mantenimiento');
DEFINE('_MNTPHP', 'PHP Versi&oacute;n:');
DEFINE('_MNTCLIENT', 'CLIENTE:');
DEFINE('_MNTSERVER', 'SERVIDOR:');
DEFINE('_MNTSERVERHW', 'SERVIDOR HW:');
DEFINE('_MNTPHPVER', 'VERSI&Oacute;N PHP:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'Nivel de registro PHP:');
DEFINE('_MNTPHPMODS', 'Modulos Cargados:');
DEFINE('_MNTDBTYPE', 'Tipo de DB:');
DEFINE('_MNTDBALV', 'Versi&oacute;n de Abstracci&oacute;n DB:');
DEFINE('_MNTDBALERTNAME', 'Nombre de DB de Alertas:');
DEFINE('_MNTDBARCHNAME', 'Nombre de DB de Archivo:');
DEFINE('_MNTAIC', 'Escondrijo de informaci&oacute;n de Alertas:');
DEFINE('_MNTAICTE', 'Total de Eventos:');
DEFINE('_MNTAICCE', 'Eventos Escondrijidos:');
DEFINE('_MNTIPAC', 'Escondrijo de Direcciones IP');
DEFINE('_MNTIPACUSIP', 'IP Orig. &Uacute;nica:');
DEFINE('_MNTIPACDNSC', 'Escondrijo DNS:');
DEFINE('_MNTIPACWC', 'Escondrijo Whois:');
DEFINE('_MNTIPACUDIP', 'Dest.IP &Uacute;nicas:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Par (sid,cid) inv&aacute;lido');
DEFINE('_QAALERTDELET', 'Alerta BORRADA');
DEFINE('_QATRIGGERSIG', 'Firma Encontrada');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'firma');
DEFINE('_QCIPADDR', 'Direcciones IP');
DEFINE('_QCIPFIELDS', 'Opciones IP');
DEFINE('_QCTCPPORTS', 'Puertos TCP');
DEFINE('_QCTCPFLAGS', 'Banderas TCP');
DEFINE('_QCTCPFIELD', 'Opciones TCP');
DEFINE('_QCUDPPORTS', 'Puertos UDP');
DEFINE('_QCUDPFIELDS', 'Opciones UDP');
DEFINE('_QCICMPFIELDS', 'Opciones ICMP');
DEFINE('_QCDATA', 'Datos');
DEFINE('_QCERRCRITWARN', 'Aviso de Criterio:');
DEFINE('_QCERRVALUE', 'Un valor de');
DEFINE('_QCERRFIELD', 'Una opcion de');
DEFINE('_QCERROPER', 'Un operador de');
DEFINE('_QCERRDATETIME', 'Un valor de fecha/hora de');
DEFINE('_QCERRPAYLOAD', 'Un valor de la carga de');
DEFINE('_QCERRIP', 'Una direcci&oacute;n IP de');
DEFINE('_QCERRIPTYPE', 'Una direcci&oacute;n IP de tipo');
DEFINE('_QCERRSPECFIELD', ' fue entrada en un campo de protocolo, pero el campo en particular no fue especificado.');
DEFINE('_QCERRSPECVALUE', 'fue seleccionado, indicando que debe ser un criterio, pero ning&uacute;n valor para comparar fue especificado.');
DEFINE('_QCERRBOOLEAN', 'Varios criterios de campo de protocolo fueron entrados sin un operador Booleano (ej. AND, OR) entre ellos.');
DEFINE('_QCERRDATEVALUE', 'fue seleccionado, indicando que una fecha/hora debe ser comparada, pero ning&uacute;n valor fue especificado.');
DEFINE('_QCERRINVHOUR', '(Hora Inv&aacute;lida) Ning&uacute;n criterio de fecha fue entrado con la hora especificada.');
DEFINE('_QCERRDATECRIT', 'fue seleccionado, indicando que una fecha/hora debe ser comparada, pero ning&uacute;n valor fue especificado.');
DEFINE('_QCERROPERSELECT', 'fue entrada, pero ningn operador fue seleccionado.');
DEFINE('_QCERRDATEBOOL', 'Varios criterios de fecha/hora fueron entrados sin un operador Booleano (ej. AND, OR) entre ellos.');
DEFINE('_QCERRPAYCRITOPER', 'fue entrada en un campo de criterio de carga, pero un operador (ej. has, has not) no fue especificado.');
DEFINE('_QCERRPAYCRITVALUE', 'fue seleccionado, indicando que una carga debe ser un criterio, pero ning&uacute;n valor para comparar fue especificado.');
DEFINE('_QCERRPAYBOOL', 'Varios criterios de carga fueron entrados sin un operador Booleano (ej. AND, OR) entre ellos.');
DEFINE('_QCMETACRIT', 'Meta Criterio');
DEFINE('_QCIPCRIT', 'Criterio IP');
DEFINE('_QCPAYCRIT', 'Criterio Carga');
DEFINE('_QCTCPCRIT', 'Criterio TCP');
DEFINE('_QCUDPCRIT', 'Criterio UDP');
DEFINE('_QCICMPCRIT', 'Criterio ICMP');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Criterio direcci&oacute;n IP inv&aacute;lido');
DEFINE('_QCERRCRITADDRESSTYPE', 'fue entrada como un valor de criterio, pero el tipo de direcci&oacute;n (ej. origen, destino), no fue especificado.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicando que una direcci&oacute;n IP debe ser un criterio, pero ningna direcc&oacute;n para comparar fue especificada.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'fue seleccionado (en #');
DEFINE('_QCERRCRITIPIPBOOL', 'Varios criterios de direcci&oacute;n IP fueron entrados sin un operador Booleano (ej. AND, OR) entre ellos.');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Ordenar por');
DEFINE('_QFRMSORTNONE', 'nada');
DEFINE('_QFRMTIMEA', 'tiempo (ascendente)');
DEFINE('_QFRMTIMED', 'tiempo (descendente)');
DEFINE('_QFRMSIG', 'firma');
DEFINE('_QFRMSIP', 'IP de Origen');
DEFINE('_QFRMDIP', 'IP de Destino');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Sumario de Estad&iacute;sticas');
DEFINE('_QSCTIMEPROF', 'Perfil de tiempo');
DEFINE('_QSCOFALERTS', 'de alertas');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Lista de Alertas');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Categorias:');
DEFINE('_SCSENSORTOTAL', 'Sensores/Total:');
DEFINE('_SCTOTALNUMALERTS', 'N&uacute;mero de Alertas en Total:');
DEFINE('_SCSRCIP', 'Orig. direcciones IP:');
DEFINE('_SCDSTIP', 'Dest. direcciones IP:');
DEFINE('_SCUNILINKS', 'Enlaces IP &Uacute;nicas');
DEFINE('_SCSRCPORTS', 'Puertos de Origen: ');
DEFINE('_SCDSTPORTS', 'Puertos de Dest: ');
DEFINE('_SCSENSORS', 'Sensores');
DEFINE('_SCCLASS', 'clasificaciones');
DEFINE('_SCUNIADDRESS', 'Direcciones &Uacute;nicas: ');
DEFINE('_SCSOURCE', 'Origen');
DEFINE('_SCDEST', 'Destino');
DEFINE('_SCPORT', 'Puerto');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'ERROR EVENTO BUSQUEDA DE PUERTOS: ');
DEFINE('_PSEVENTERRNOFILE', 'Ning&uacute;n fichero especificado en la variable \$portscan_file.');
DEFINE('_PSEVENTERROPENFILE', 'No se pudo abrir el fichero de eventos de la b&uacute;squeda de puertos');
DEFINE('_PSDATETIME', 'Fecha/Hora');
DEFINE('_PSSRCIP', 'IP Origen');
DEFINE('_PSDSTIP', 'IP Destino');
DEFINE('_PSSRCPORT', 'Puerto Origen');
DEFINE('_PSDSTPORT', 'Puerto Destino');
DEFINE('_PSTCPFLAGS', 'Banderas TCP');
DEFINE('_PSTOTALOCC', 'Total<BR> de sucesos');
DEFINE('_PSNUMSENSORS', 'Num. de Sensores');
DEFINE('_PSFIRSTOCC', 'Primer<BR> Suceso');
DEFINE('_PSLASTOCC', 'Ultimo<BR> Suceso');
DEFINE('_PSUNIALERTS', 'Alertas &uacute;nicas');
DEFINE('_PSPORTSCANEVE', 'Eventos de busqueda de puertos');
DEFINE('_PSREGWHOIS', 'Buscar en el registro Whois en');
DEFINE('_PSNODNS', 'no se trat&oacute; de resolver por DNS');
DEFINE('_PSNUMSENSORSBR', 'Num. de <BR>Sensores');
DEFINE('_PSOCCASSRC', 'Sucesos <BR>como Orig.');
DEFINE('_PSOCCASDST', 'Sucesos <BR>como Dest.');
DEFINE('_PSWHOISINFO', 'Informaci&oacute;n Whois');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'Todas las alertas con %s/%s como');
DEFINE('_PSSHOW', 'Mostrar');
DEFINE('_PSEXTERNAL', 'Enlaces externos');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'Enlaces IP');
DEFINE('_SIPLSOURCEFGDN', 'Origen FQDN');
DEFINE('_SIPLDESTFGDN', 'Destino FQDN');
DEFINE('_SIPLDIRECTION', 'Sentido');
DEFINE('_SIPLPROTO', 'Protocolo');
DEFINE('_SIPLUNIDSTPORTS', 'Puertos Destino &uacute;nicos');
DEFINE('_SIPLUNIEVENTS', 'Eventos &uacute;nicos');
DEFINE('_SIPLTOTALEVENTS', 'Total de Eventos');
//base_stat_ports.php
DEFINE('_UNIQ', '&Uacute;nico');
DEFINE('_DSTPS', 'Puerto(s) de Destino');
DEFINE('_SRCPS', 'Puerto(s) de Origen');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Lista de Sensores');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Perfil de Tiempo de Alertas');
DEFINE('_BSTTIMECRIT', 'Criterio de Tiempo');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Ning&uacute;n criterio de perfilar fue especificado!</B>  Escoge "hora", "d&iacute;a", o "mes" para escoger la medida de las estat&iacute;sticas agregadas.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>El tipo de parametro de tiempo no est&aacute; especificado!</B>  Escoge entre "encendida", para seleccionar una s&oacute;la fecha, o "entre" para especificar un intervalo.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Ningn &ntilde;o especificado!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Ningn mes especificado!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Ningn &iacute;a especificado!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Direcciones de Origen &Uacute;nicas');
DEFINE('_SUASRCIP', 'Direcci&oacute;n IP de Origen');
DEFINE('_SUAERRCRITADDUNK', 'ERROR CRITERIO: tipo de direcci&oacute;n desconocido -- asumiendo direcci&oacute;n de destino');
DEFINE('_UNIDADD', 'Direcciones de Destino &uacute;nicas');
DEFINE('_SUADSTIP', 'Direcci&oacute;n IP de Destino');
DEFINE('_SUAUNIALERTS', 'Alertas&nbsp; &Uacute;nicas');
DEFINE('_SUASRCADD', 'Orig.&nbsp;Direc.');
DEFINE('_SUADSTADD', 'Dest.&nbsp;Direc.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE Preferencias del Usuario');
DEFINE('_BASEUSERERRPWD', 'Su clave no puede estar vac&iacute;a, o las dos claves no son iguales!');
DEFINE('_BASEUSEROLDPWD', 'Clave antigua:');
DEFINE('_BASEUSERNEWPWD', 'Clave nueva:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Clave nueva de nuevo:');
?>
