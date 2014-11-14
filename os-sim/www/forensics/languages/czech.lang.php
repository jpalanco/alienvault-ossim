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
DEFINE('_CHARSET', 'iso-8859-2');
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Login:');
DEFINE('_FRMPWD', 'Heslo:');
DEFINE('_SOURCE', 'Zdroj');
DEFINE('_SOURCENAME', 'Jméno zdoje');
DEFINE('_DEST', 'Cíl');
DEFINE('_DESTNAME', 'Jméno cíle');
DEFINE('_SORD', 'Zdroj n. cíl');
DEFINE('_EDIT', 'Upravit');
DEFINE('_DELETE', 'Smazat');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Jméno');
DEFINE('_INTERFACE', 'Rozhraní');
DEFINE('_FILTER', 'Filtr');
DEFINE('_DESC', 'Popis');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'ID role');
DEFINE('_ENABLED', 'Enabled');
DEFINE('_SUCCESS', 'Successful');
DEFINE('_SENSOR', 'Senzor');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Podpis');
DEFINE('_TIMESTAMP', 'Èasová znaèka');
DEFINE('_NBSOURCEADDR', 'Zdrojová&nbsp;adresa');
DEFINE('_NBDESTADDR', 'Cílová&nbsp;adresa');
DEFINE('_NBLAYER4', 'Protokol 4. vrstvy');
DEFINE('_PRIORITY', 'Priorita');
DEFINE('_EVENTTYPE', 'typ události');
DEFINE('_JANUARY', 'Leden');
DEFINE('_FEBRUARY', 'Únor');
DEFINE('_MARCH', 'Bøezen');
DEFINE('_APRIL', 'Duben');
DEFINE('_MAY', 'Kvìten');
DEFINE('_JUNE', 'Èerven');
DEFINE('_JULY', 'Èervenec');
DEFINE('_AUGUST', 'Srpen');
DEFINE('_SEPTEMBER', 'Záøí');
DEFINE('_OCTOBER', 'Øíjen');
DEFINE('_NOVEMBER', 'Listopad');
DEFINE('_DECEMBER', 'Prosinec');
DEFINE('_LAST', 'Poslední');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Alarm');
DEFINE('_ADDRESS', 'Adresa');
DEFINE('_UNKNOWN', 'neznámý');
DEFINE('_AND', 'AND'); //NEW
DEFINE('_OR', 'OR'); //NEW
DEFINE('_IS', 'is'); //NEW
DEFINE('_ON', 'on'); //NEW
DEFINE('_IN', 'in'); //NEW
DEFINE('_ANY', 'any'); //NEW
DEFINE('_NONE', 'none'); //NEW
DEFINE('_HOUR', 'Hour'); //NEW
DEFINE('_DAY', 'Day'); //NEW
DEFINE('_MONTH', 'Month'); //NEW
DEFINE('_YEAR', 'Year'); //NEW
DEFINE('_ALERTGROUP', 'Alert Group'); //NEW
DEFINE('_ALERTTIME', 'Alert Time'); //NEW
DEFINE('_CONTAINS', 'contains'); //NEW
DEFINE('_DOESNTCONTAIN', 'does not contain'); //NEW
DEFINE('_SOURCEPORT', 'source port'); //NEW
DEFINE('_DESTPORT', 'dest port'); //NEW
DEFINE('_HAS', 'has'); //NEW
DEFINE('_HASNOT', 'has not'); //NEW
DEFINE('_PORT', 'Port'); //NEW
DEFINE('_FLAGS', 'Flags'); //NEW
DEFINE('_MISC', 'Misc'); //NEW
DEFINE('_BACK', 'Back'); //NEW
DEFINE('_DISPYEAR', '{ year }'); //NEW
DEFINE('_DISPMONTH', '{ month }'); //NEW
DEFINE('_DISPHOUR', '{ hour }'); //NEW
DEFINE('_DISPDAY', '{ day }'); //NEW
DEFINE('_DISPTIME', '{ time }'); //NEW
DEFINE('_ADDADDRESS', 'ADD Addr'); //NEW
DEFINE('_ADDIPFIELD', 'ADD IP Field'); //NEW
DEFINE('_ADDTIME', 'ADD TIME'); //NEW
DEFINE('_ADDTCPPORT', 'ADD TCP Port'); //NEW
DEFINE('_ADDTCPFIELD', 'ADD TCP Field'); //NEW
DEFINE('_ADDUDPPORT', 'ADD UDP Port'); //NEW
DEFINE('_ADDUDPFIELD', 'ADD UDP Field'); //NEW
DEFINE('_ADDICMPFIELD', 'ADD ICMP Field'); //NEW
DEFINE('_ADDPAYLOAD', 'ADD Payload'); //NEW
DEFINE('_MOSTFREQALERTS', 'Most Frequent Alerts'); //NEW
DEFINE('_MOSTFREQPORTS', 'Most Frequent Ports'); //NEW
DEFINE('_MOSTFREQADDRS', 'Most Frequent IP addresses'); //NEW
DEFINE('_LASTALERTS', 'Last Alerts'); //NEW
DEFINE('_LASTPORTS', 'Last Ports'); //NEW
DEFINE('_LASTTCP', 'Last TCP Alerts'); //NEW
DEFINE('_LASTUDP', 'Last UDP Alerts'); //NEW
DEFINE('_LASTICMP', 'Last ICMP Alerts'); //NEW
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
DEFINE('_NEXT', 'Next'); //NEW
DEFINE('_PREVIOUS', 'Previous'); //NEW
//Menu items
DEFINE('_HOME', 'Domù');
DEFINE('_SEARCH', 'Hledat');
DEFINE('_AGMAINT', 'Správa skupin alarmù');
DEFINE('_USERPREF', 'U¾ivatelské volby');
DEFINE('_CACHE', 'Ke¹ a stav');
DEFINE('_ADMIN', 'Administrace');
DEFINE('_GALERTD', 'Vytvoøit graf alarmù');
DEFINE('_GALERTDT', 'Vytvoøit graf èasu detekce alarmù');
DEFINE('_USERMAN', 'Správa u¾ivatelù');
DEFINE('_LISTU', 'Seznam u¾ivatelù');
DEFINE('_CREATEU', 'Vytvoøit u¾ivatele');
DEFINE('_ROLEMAN', 'Správa rolí');
DEFINE('_LISTR', 'Seznam rolí');
DEFINE('_CREATER', 'Vytvoøit roli');
DEFINE('_LISTALL', 'Vypsat v¹e');
DEFINE('_CREATE', 'Vytvoø');
DEFINE('_VIEW', 'Zobraz');
DEFINE('_CLEAR', 'Vyèisti');
DEFINE('_LISTGROUPS', 'Seznam skupin');
DEFINE('_CREATEGROUPS', 'Vytvoø skupinu');
DEFINE('_VIEWGROUPS', 'Zobraz skupinu');
DEFINE('_EDITGROUPS', 'Edituj skupinu');
DEFINE('_DELETEGROUPS', 'Sma¾ skupinu');
DEFINE('_CLEARGROUPS', 'Vyèisti skupinu');
DEFINE('_CHNGPWD', 'Zmìnit heslo');
DEFINE('_DISPLAYU', 'Zobraz u¾ivatele');
//base_footer.php
DEFINE('_FOOTER', ' (by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> and the <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'U¾ivatel neexistuje nebo jste zadali ¹patné heslo!<br>Zkuste prosím znovu.');
// base_main.php
DEFINE('_MOSTRECENT', 'Posledních ');
DEFINE('_MOSTFREQUENT', 'Nejèastìj¹ích ');
DEFINE('_ALERTS', ' alarmù:');
DEFINE('_ADDRESSES', ' adres:');
DEFINE('_ANYPROTO', 'jakýkoliv<br>protokol');
DEFINE('_UNI', 'unikátní');
DEFINE('_LISTING', 'výpis');
DEFINE('_TALERTS', 'Dne¹ní alarmy: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'Alarmy za posledních 24 hodin: ');
DEFINE('_L72ALERTS', 'Alarmy za posledních 72 hodin: ');
DEFINE('_UNIALERTS', 'unikátních alarmù');
DEFINE('_LSOURCEPORTS', 'Poslední zdrojové porty: ');
DEFINE('_LDESTPORTS', 'Poslední cílové porty: ');
DEFINE('_FREGSOURCEP', 'Nejèastìj¹í zdrojové porty: ');
DEFINE('_FREGDESTP', 'Nejèastìj¹í cílové porty: ');
DEFINE('_QUERIED', 'Dotázáno ');
DEFINE('_DATABASE', 'Databáze:');
DEFINE('_SCHEMAV', 'Verze schématu:');
DEFINE('_TIMEWIN', 'Èasové rozmezí:');
DEFINE('_NOALERTSDETECT', '®ádné alarmy dezji¹tìny');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Úspì¹nì pøidán');
DEFINE('_NOPWDCHANGE', 'Nelze zmìnit heslo: ');
DEFINE('_NOUSER', 'U¾ivatel neexistuje!');
DEFINE('_OLDPWD', 'Aktuální heslo není správné!');
DEFINE('_PWDCANT', 'Nelze zmìnit heslo: ');
DEFINE('_PWDDONE', 'Heslo bylo zmìnìno.');
DEFINE('_ROLEEXIST', 'Role existuje');
DEFINE('_ROLEIDEXIST', 'ID role existuje');
DEFINE('_ROLEADDED', 'Role pøidána úspì¹nì');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'Správa rolí BASE');
DEFINE('_FRMROLEID', 'ID role:');
DEFINE('_FRMROLENAME', 'Jméno role:');
DEFINE('_FRMROLEDESC', 'Popis:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'Správa u¾ivatelù BASE');
DEFINE('_FRMFULLNAME', 'Celé jméno:');
DEFINE('_FRMROLE', 'Role:');
DEFINE('_FRMUID', 'ID u¾ivatele:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'Administrace BASE');
DEFINE('_BASEADMINTEXT', 'Zvolte prosím operaci nalevo.');
//base_action.inc.php
DEFINE('_NOACTION', 'Nebyla specifikována operace');
DEFINE('_INVALIDACT', ' je neplatná operace');
DEFINE('_ERRNOAG', 'Nemohu pøidat alarmy; nebyla specifikována skupina');
DEFINE('_ERRNOEMAIL', 'Nemohu zaslat alarmy po¹tou; nebyla specifikována emailová adresa');
DEFINE('_ACTION', 'Operace');
DEFINE('_CONTEXT', 'kontext');
DEFINE('_ADDAGID', 'Pøidat do skupiny (podle ID)');
DEFINE('_ADDAG', 'Pøidat do novì vytvoøené skupiny'); // not used
DEFINE('_ADDAGNAME', 'Pøidat do skupiny (podle jména)');
DEFINE('_CREATEAG', 'Pøidat do novì vytvoøené skupiny');
DEFINE('_CLEARAG', 'Vymazat se skupiny');
DEFINE('_DELETEALERT', 'Smazat');
DEFINE('_EMAILALERTSFULL', 'Zaslat emailem (detailní)');
DEFINE('_EMAILALERTSSUMM', 'Zaslat emailem (shrnutí)');
DEFINE('_EMAILALERTSCSV', 'Zaslat emailem (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Archivovat (vytvoøit kopii)');
DEFINE('_ARCHIVEALERTSMOVE', 'Archivovat (pøesunout)');
DEFINE('_IGNORED', 'Ignorováno');
DEFINE('_DUPALERTS', ' duplicitní alarm(y)');
DEFINE('_ALERTSPARA', ' alarm(y)');
DEFINE('_NOALERTSSELECT', '®ádné alarmy nebyly vybrány nebo');
DEFINE('_NOTSUCCESSFUL', 'nebyla úspì¹ná');
DEFINE('_ERRUNKAGID', 'Zadáno neznámé ID skupiny (skupina pravdìpodobnì neexistuje)');
DEFINE('_ERRREMOVEFAIL', 'Selhalo odstranìní nové skupiny');
DEFINE('_GENBASE', 'Vytvoøeno BASE');
DEFINE('_ERRNOEMAILEXP', 'Chyba pøi exportování: Nemohu poslat alarmy');
DEFINE('_ERRNOEMAILPHP', 'Zkontrolujte konfiguraci emailu PHP.');
DEFINE('_ERRDELALERT', 'Chyba pøi mazání alarmu');
DEFINE('_ERRARCHIVE', 'Chyba pøi archivaci:');
DEFINE('_ERRMAILNORECP', 'Chyba pøi zasílání emailem: Nebyl zadán pøíjemce');
//base_cache.inc.php
DEFINE('_ADDED', 'Pøidáno ');
DEFINE('_HOSTNAMESDNS', ' jmen do IP DNS vyrovnávací pamìti');
DEFINE('_HOSTNAMESWHOIS', ' jmen do Whois vyrovnávací pamìti');
DEFINE('_ERRCACHENULL', 'Chyba pøi aktualizaci vyrovnávací pamìti: nalezena NULL øádka event?');
DEFINE('_ERRCACHEERROR', 'Chyba pøi aktualizaci vyrovnávací pamìti:');
DEFINE('_ERRCACHEUPDATE', 'Nemohu aktualizovat vyrovnávací pamì»');
DEFINE('_ALERTSCACHE', ' alarmù do vyrovnávací pamìti');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Nemohu otevøít soubor pro trasování SQL');
DEFINE('_ERRSQLCONNECT', 'Chyba pøi pøipojování databáze:');
DEFINE('_ERRSQLCONNECTINFO', '<P>Zkontrolujte promìnné pro pøipojování se do databáze v souboru <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : jméno databáze 
               = $alert_host     : hostitel
               = $alert_port     : port
               = $alert_user     : u¾ivatelské jméno
               = $alert_password : heslo
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Chyba pøi pøipojování databáze:');
DEFINE('_ERRSQLDB', 'Databázová chyba:');
DEFINE('_DBALCHECK', 'Kontraoluje knihovnu pro práci s databází v');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Chyba pøi naèítání knihovny pro práci s databází: </B> od ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Zkontrolujte promìnnou pro urèení cesty ke knihovnì pro práci s databází <CODE>$DBlib_path</CODE> v souboru <CODE>base_conf.php</CODE>
            <P>Knihovnu pro práci s databází ADODB stáhnìte z
            <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Specifikován neplatný typ databáze');
DEFINE('_ERRSQLDBTYPEINFO1', 'Promìnná <CODE>\$DBtype</CODE> v souboru <CODE>base_conf.php</CODE> byla ¹patnì nastavena na ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Podporovány jsou pouze následující databázové systémy: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'Kritická chyba BASE:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Naèteno za');
DEFINE('_SECONDS', 'vteøin');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Nemohu pøelo¾it adresu');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Záhlaví výsledkù dotazu'); //not used
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'neznámé SigName');
DEFINE('_ERRSIGPROIRITYUNK', 'neznámé SigPriority');
DEFINE('_UNCLASS', 'nezaøazeno');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'data zakódóvána jako');
DEFINE('_NODENCODED', '(¾ádná konverze dat, pøedpokládám po¾adavek ve výchozím formátu databáze)');
DEFINE('_SHORTJAN', 'Jan'); //NEW
DEFINE('_SHORTFEB', 'Feb'); //NEW
DEFINE('_SHORTMAR', 'Mar'); //NEW
DEFINE('_SHORTAPR', 'Apr'); //NEW
DEFINE('_SHORTMAY', 'May'); //NEW
DEFINE('_SHORTJUN', 'Jun'); //NEW
DEFINE('_SHORTJLY', 'Jly'); //NEW
DEFINE('_SHORTAUG', 'Aug'); //NEW
DEFINE('_SHORTSEP', 'Sep'); //NEW
DEFINE('_SHORTOCT', 'Oct'); //NEW
DEFINE('_SHORTNOV', 'Nov'); //NEW
DEFINE('_SHORTDEC', 'Dec'); //NEW
DEFINE('_DISPSIG', '{ signature }'); //NEW
DEFINE('_DISPANYCLASS', '{ any Classification }'); //NEW
DEFINE('_DISPANYPRIO', '{ any Priority }'); //NEW
DEFINE('_DISPANYSENSOR', '{ any Sensor }'); //NEW
DEFINE('_DISPADDRESS', '{ adress }'); //NEW
DEFINE('_DISPFIELD', '{ field }'); //NEW
DEFINE('_DISPPORT', '{ port }'); //NEW
DEFINE('_DISPENCODING', '{ encoding }'); //NEW
DEFINE('_DISPCONVERT2', '{ Convert To }'); //NEW
DEFINE('_DISPANYAG', '{ any Alert Group }'); //NEW
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
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler. Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Sezení zaregistrováno');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Odstranit');
DEFINE('_FROMCRIT', 'z kritérií');
DEFINE('_ERRCRITELEM', 'Neplatný elemt kritéria');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Platný základní dotaz');
DEFINE('_DISPLAYING', 'Zobrazuji');
DEFINE('_DISPLAYINGTOTAL', 'Zobrazuji alarmy %d-%d z %s celkem');
DEFINE('_NOALERTS', '®ádné alarmy nenalezeny.');
DEFINE('_QUERYRESULTS', 'Výsledky dotazu');
DEFINE('_QUERYSTATE', 'Stav dotazu');
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Jmenovanou skupinu nelze nalézt. Zkuste to znovu.');
DEFINE('_ERRAGNAMEEXIST', 'Zadaná skupina neexistuje.');
DEFINE('_ERRAGIDSEARCH', 'Skupinu urèenou ID nelze nalézt. Zkuste to znovu.');
DEFINE('_ERRAGLOOKUP', 'Chyba pøi vyhledávání skupiny dle ID');
DEFINE('_ERRAGINSERT', 'Chyba pøi vkládání nové skupiny');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Správa skupin');
DEFINE('_ERRAGUPDATE', 'Chyba pøi aktualizaci skupiny');
DEFINE('_ERRAGPACKETLIST', 'Chyba pøi mazání obsahu skupiny:');
DEFINE('_ERRAGDELETE', 'Chyba pøi mazání skupiny');
DEFINE('_AGDELETE', 'smazána úspì¹nì');
DEFINE('_AGDELETEINFO', 'informace smazána');
DEFINE('_ERRAGSEARCHINV', 'Zadané vyhledávací kritérium je neplatné. Zkuste to znovu.');
DEFINE('_ERRAGSEARCHNOTFOUND', '®ádná skupiny s tímto kritériem nenalezena.');
DEFINE('_NOALERTGOUPS', 'Nejsou definovány ¾ádné skupiny');
DEFINE('_NUMALERTS', 'poèet alarmù');
DEFINE('_ACTIONS', 'Akce');
DEFINE('_NOTASSIGN', 'je¹tì nepøiøazeno');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Provoz skenování portù');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Nemohu vytvoøit index pro');
DEFINE('_DBINDEXCREATE', 'Úspì¹nì vytvoøen index pro');
DEFINE('_ERRSNORTVER', 'Mù¾e se jednat o star¹í verzi. Podporovány jsou pouze databáze vytvoøené Snort 1.7-beta0 nebo novìj¹ím');
DEFINE('_ERRSNORTVER1', 'Základní databáze');
DEFINE('_ERRSNORTVER2', 'se zdá nekompletní nebo neplatná');
DEFINE('_ERRDBSTRUCT1', 'Verze databáze je správná, ale neobsahuje');
DEFINE('_ERRDBSTRUCT2', 'BASE tabulky. Pou¾ijte <A HREF="base_db_setup.php">Inicializaèní stránku</A> pro nastavení a optimalizaci databáze.');
DEFINE('_ERRPHPERROR', 'Chyba PHP');
DEFINE('_ERRPHPERROR1', 'Nekompatibilní verze');
DEFINE('_ERRVERSION', 'Verze');
DEFINE('_ERRPHPERROR2', 'PHP je pøíli¹ stará. Proveïte prosím aktualizaci na verzi 4.0.4 nebo pozdìj¹í');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP podpora není kompletní</B>: <FONT>podpora pro práci s MySQL 
               databází není souèástí instalace.
               Prosím pøeinstalujte PHP s potøebnou knihovnou (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP podpora není kompletní</B>: <FONT>podpora pro práci s PostgreSQL
               databází není souèástí instalace.
               Prosím pøeinstalujte PHP s potøebnou knihovnou (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP podpora není kompletní</B>: <FONT>podpora pro práci s MS SQL
               databází není souèástí instalace.
               Prosím pøeinstalujte PHP s potøebnou knihovnou (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP podpora není kompletní</B>: <FONT>podpora pro práci s Oracle
               databází není souèástí instalace.
               Prosím pøeinstalujte PHP s potøebnou knihovnou (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Nadpis grafu:');
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
DEFINE('_CHARTMONTH', '{mÃªs}'); //NEW
DEFINE('_GRAPHALERTS', 'Graph Alerts'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y AXIS CONTROLS'); //NEW
DEFINE('_CHRTTYPEHOUR', 'Èas (hodiny) proti poètu alarmù');
DEFINE('_CHRTTYPEDAY', 'Èas (dny) proti poètu alarmù');
DEFINE('_CHRTTYPEWEEK', 'Èas (týdny) proti poètu alarmù');
DEFINE('_CHRTTYPEMONTH', 'Èas (mìsíce) proti poètu alarmù');
DEFINE('_CHRTTYPEYEAR', 'Èas (roky) proti poètu alarmù');
DEFINE('_CHRTTYPESRCIP', 'Zdrojová IP adresa proti poètu alarmù');
DEFINE('_CHRTTYPEDSTIP', 'Cílová IP adresa proti poètu alarmù');
DEFINE('_CHRTTYPEDSTUDP', 'Cílový UDP port proti poètu alarmù');
DEFINE('_CHRTTYPESRCUDP', 'Zdrojový UDP port proti poètu alarmù');
DEFINE('_CHRTTYPEDSTPORT', 'Cílový TCP port proti poètu alarmù');
DEFINE('_CHRTTYPESRCPORT', 'Zdrojový TCP port proti poètu alarmù');
DEFINE('_CHRTTYPESIG', 'Klasifikace podpisù proti poètu alarmù');
DEFINE('_CHRTTYPESENSOR', 'Senzor proti poètu alarmù');
DEFINE('_CHRTBEGIN', 'Zaèátek grafu:');
DEFINE('_CHRTEND', 'Konec grafu:');
DEFINE('_CHRTDS', 'Zdroj dat:');
DEFINE('_CHRTX', 'Osa X');
DEFINE('_CHRTY', 'Osa Y');
DEFINE('_CHRTMINTRESH', 'Minimální hodnota');
DEFINE('_CHRTROTAXISLABEL', 'Otoèit popisky os o 90 stupòù');
DEFINE('_CHRTSHOWX', 'Zobraz rastr pro osu X');
DEFINE('_CHRTDISPLABELX', 'Zobraz popis osy X ka¾dých');
DEFINE('_CHRTDATAPOINTS', 'vzorkù dat');
DEFINE('_CHRTYLOG', 'Osa Y logaritmická');
DEFINE('_CHRTYGRID', 'Zobraz rastr pro osu Y');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'Graf BASE');
DEFINE('_ERRCHRTNOTYPE', 'Nebyl urèen typ grafu');
DEFINE('_ERRNOAGSPEC', 'Nebyla urèena skupiny. Pou¾ívám v¹echny alarmy.');
DEFINE('_CHRTDATAIMPORT', 'Zaèínám naèítat data');
DEFINE('_CHRTTIMEVNUMBER', 'Èas port proti poètu alarmù');
DEFINE('_CHRTTIME', 'Èas');
DEFINE('_CHRTALERTOCCUR', 'Výskyty alarmù');
DEFINE('_CHRTSIPNUMBER', 'Zdrojová IP adresa proti poètu alarmù');
DEFINE('_CHRTSIP', 'Zdrojová IP adresa');
DEFINE('_CHRTDIPALERTS', 'Cílová IP adresa proti poètu alarmù');
DEFINE('_CHRTDIP', 'Cílová IP adresa');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP port (cíl) port proti poètu alarmù');
DEFINE('_CHRTDUDPPORT', 'Cílový UDP port');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP port (zdroj) port proti poètu alarmù');
DEFINE('_CHRTSUDPPORT', 'Zdrojový UDP port');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP port (cíl) port proti poètu alarmù');
DEFINE('_CHRTPORTDEST', 'Cílový TCP port');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP port (zdroj) port proti poètu alarmù');
DEFINE('_CHRTPORTSRC', 'Zdrojový TCP port');
DEFINE('_CHRTSIGNUMBER', 'Klasifikace podpisù proti poètu alarmù');
DEFINE('_CHRTCLASS', 'Klasifikace');
DEFINE('_CHRTSENSORNUMBER', 'Senzor port proti poètu alarmù');
DEFINE('_CHRTHANDLEPERIOD', 'Rozhodné období (pokud je tøeba)');
DEFINE('_CHRTDUMP', 'Vypisuji data ... (zobrazuji jen ka¾dé');
DEFINE('_CHRTDRAW', 'Kreslím graf');
DEFINE('_ERRCHRTNODATAPOINTS', 'Pro vykreslení nejsou k dispozici ¾ádná data');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Údr¾ba');
DEFINE('_MNTPHP', 'PHP popis:');
DEFINE('_MNTCLIENT', 'CLIENT:');
DEFINE('_MNTSERVER', 'SERVER:');
DEFINE('_MNTSERVERHW', 'SERVER HW:');
DEFINE('_MNTPHPVER', 'PHP VERZE:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'Úroveò hlá¹ení PHP:');
DEFINE('_MNTPHPMODS', 'Nahrané moduly:');
DEFINE('_MNTDBTYPE', 'Typ databáze:');
DEFINE('_MNTDBALV', 'Verze podpùrné databázové knihovny:');
DEFINE('_MNTDBALERTNAME', 'Jméno ALERT databáze:');
DEFINE('_MNTDBARCHNAME', 'Jméno ARCHIVE databáze:');
DEFINE('_MNTAIC', 'Vyrovnávací pamì» alarmù:');
DEFINE('_MNTAICTE', 'Celkový poèet událostí:');
DEFINE('_MNTAICCE', 'Události ve vyrovnávací pamìti:');
DEFINE('_MNTIPAC', 'Vyrovnávací pamì» IP address');
DEFINE('_MNTIPACUSIP', 'Unikátní zdrojové IP:');
DEFINE('_MNTIPACDNSC', 'DNS ve vyrovnávací pamìti:');
DEFINE('_MNTIPACWC', 'Whois ve vyrovnávací pamìti:');
DEFINE('_MNTIPACUDIP', 'Unikátní cílové IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Neplatný pár (sid,cid)');
DEFINE('_QAALERTDELET', 'Alarm smazán');
DEFINE('_QATRIGGERSIG', 'Detekovaný podpis alarmu');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'podpis');
DEFINE('_QCIPADDR', 'IP adresy');
DEFINE('_QCIPFIELDS', 'IP pole');
DEFINE('_QCTCPPORTS', 'TCP porty');
DEFINE('_QCTCPFLAGS', 'TCP vlajky');
DEFINE('_QCTCPFIELD', 'TCP pole');
DEFINE('_QCUDPPORTS', 'UDP porty');
DEFINE('_QCUDPFIELDS', 'UDP pole');
DEFINE('_QCICMPFIELDS', 'ICMP poel');
DEFINE('_QCDATA', 'Data');
DEFINE('_QCERRCRITWARN', 'Varování vyhledávacích kritérií:');
DEFINE('_QCERRVALUE', 'Hodnota');
DEFINE('_QCERRFIELD', 'Pole');
DEFINE('_QCERROPER', 'Operátor');
DEFINE('_QCERRDATETIME', 'Hodnota datum/èas');
DEFINE('_QCERRPAYLOAD', 'Hodnota obsahu');
DEFINE('_QCERRIP', 'IP adresa');
DEFINE('_QCERRIPTYPE', 'IP adresa typu');
DEFINE('_QCERRSPECFIELD', 'bylo zadáno pole protokolu, ale nebyla urèena hodnota.');
DEFINE('_QCERRSPECVALUE', 'bylo vybráno, ale nebyla urèena hodnota.');
DEFINE('_QCERRBOOLEAN', 'Více polí pro urèení protokolu bylo zadáno, ale nebyl mezi nimi zadán logický operátor (AND, OR).');
DEFINE('_QCERRDATEVALUE', 'bylo zvoleno, ¾e se má vyhledávat podle data/èasu, ale nebyla urèena hodnota.');
DEFINE('_QCERRINVHOUR', '(Neplatná hodina) ®ádné kritérium pro urèení data/èasu neodpovídá urèenému èasu.');
DEFINE('_QCERRDATECRIT', 'bylo zvoleno, ¾e se má vyhledávat podle data/èasu, ale nebyla urèena hodnota.');
DEFINE('_QCERROPERSELECT', 'bylo vlo¾eno, ale nebyl zvolen ¾ádný operátor.');
DEFINE('_QCERRDATEBOOL', 'Více kritérií datum/èas bylo zadáno bez urèení logického operátoru (AND, OR) mezi nimi.');
DEFINE('_QCERRPAYCRITOPER', 'byl urèen obsah, který se má vyhledávat, ale nebylo zvoleno, zda má být obsa¾en nebo ne.');
DEFINE('_QCERRPAYCRITVALUE', 'bylo urèeno, ¾e se má vyhledávat podle obsahu, ale nebyla urèena hodnota.');
DEFINE('_QCERRPAYBOOL', 'Více kritérií obsahu bylo zadáno bez urèenít logického operátoru (AND, OR) mezi nimi.');
DEFINE('_QCMETACRIT', 'Meta kritária');
DEFINE('_QCIPCRIT', 'IP kritéria');
DEFINE('_QCPAYCRIT', 'Obsahová kritéria');
DEFINE('_QCTCPCRIT', 'TCP kritéria');
DEFINE('_QCUDPCRIT', 'UDP kritéria');
DEFINE('_QCICMPCRIT', 'ICMP kritéria');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Neplatné kritérium IP adresy');
DEFINE('_QCERRCRITADDRESSTYPE', 'byla zvolena jako kritérium, ale nebylo urèeno, zda se jedná o zdrojovou nebo cílovou adresu.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'ukazujíc, ¾e IP adresa má být kritériem, ale nebyla urèena hodnota.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'bylo vybráno (v #');
DEFINE('_QCERRCRITIPIPBOOL', 'Více kritérií pro IP adresy bylo zadáno bez urèení logického operátoru (AND, OR) mezi nimi.');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Smìr tøídìní');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMTIMEA', 'èas (vzestupnì)');
DEFINE('_QFRMTIMED', 'èas (sestupnì)');
DEFINE('_QFRMSIG', 'podpis');
DEFINE('_QFRMSIP', 'zdrojová IP adresa');
DEFINE('_QFRMDIP', 'cílová IP adresa');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Souhrné statistiky');
DEFINE('_QSCTIMEPROF', 'Profil v èase');
DEFINE('_QSCOFALERTS', 'z alarmù');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Výpis alarmù');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategorie:');
DEFINE('_SCSENSORTOTAL', 'Senzory/Celkem:');
DEFINE('_SCTOTALNUMALERTS', 'Celkový poèet alarmù:');
DEFINE('_SCSRCIP', 'Zdrojových IP adres:');
DEFINE('_SCDSTIP', 'Cílových IP adres:');
DEFINE('_SCUNILINKS', 'Unikátních IP spojù');
DEFINE('_SCSRCPORTS', 'Zdrojových portù: ');
DEFINE('_SCDSTPORTS', 'Cílových portù: ');
DEFINE('_SCSENSORS', 'Senzorù');
DEFINE('_SCCLASS', 'Klasifikace');
DEFINE('_SCUNIADDRESS', 'Unikátních adres: ');
DEFINE('_SCSOURCE', 'Zdroj');
DEFINE('_SCDEST', 'Cíl');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN EVENT ERROR: ');
DEFINE('_PSEVENTERRNOFILE', 'No file was specified in the \$portscan_file variable.');
DEFINE('_PSEVENTERROPENFILE', 'Unable to open Portscan event file');
DEFINE('_PSDATETIME', 'Date/Time');
DEFINE('_PSSRCIP', 'Source IP');
DEFINE('_PSDSTIP', 'Destination IP');
DEFINE('_PSSRCPORT', 'Source Port');
DEFINE('_PSDSTPORT', 'Destination Port');
DEFINE('_PSTCPFLAGS', 'TCP Flags');
DEFINE('_PSTOTALOCC', 'Total<BR> Occurrences');
DEFINE('_PSNUMSENSORS', 'Num of Sensors');
DEFINE('_PSFIRSTOCC', 'First<BR> Occurrence');
DEFINE('_PSLASTOCC', 'Last<BR> Occurrence');
DEFINE('_PSUNIALERTS', 'Unique Alerts');
DEFINE('_PSPORTSCANEVE', 'Portscan Events');
DEFINE('_PSREGWHOIS', 'Registry lookup (whois) in');
DEFINE('_PSNODNS', 'no DNS resolution attempted');
DEFINE('_PSNUMSENSORSBR', 'Num of <BR>Sensors');
DEFINE('_PSOCCASSRC', 'Occurances <BR>as Src.');
DEFINE('_PSOCCASDST', 'Occurances <BR>as Dest.');
DEFINE('_PSWHOISINFO', 'Whois Information');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP spoje');
DEFINE('_SIPLSOURCEFGDN', 'Zdrojové FQDN');
DEFINE('_SIPLDESTFGDN', 'Cílové FQDN');
DEFINE('_SIPLDIRECTION', 'Smìr');
DEFINE('_SIPLPROTO', 'Protokol');
DEFINE('_SIPLUNIDSTPORTS', 'Unikátních cílových portù');
DEFINE('_SIPLUNIEVENTS', 'Unikátních alarmù');
DEFINE('_SIPLTOTALEVENTS', 'Celkem alarmù');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unikátní');
DEFINE('_DSTPS', 'cílové porty');
DEFINE('_SRCPS', 'zdrojové porty');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Výpis senzorù');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Time Profile of Alerts');
DEFINE('_BSTTIMECRIT', 'Time Criteria');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>No profiling criteria was specified!</B>  Click on "hour", "day", or "month" to choose the granularity of the aggregate statistics.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>The type of time parameter which will be passed was not specified!</B>  Choose either "on", to specify a single date, or "between" to specify an interval.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>No Year parameter was specified!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>No Month parameter was specified!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>No Day parameter was specified!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Unikátní zdrojové IP adresy');
DEFINE('_SUASRCIP', 'Zdrojová IP adresa');
DEFINE('_SUAERRCRITADDUNK', 'chyba v kritériu: neznámý typ adresy -- pøedpokládám cílovou');
DEFINE('_UNIDADD', 'Unikátní cílové IP adresy');
DEFINE('_SUADSTIP', 'Cílová IP adresa');
DEFINE('_SUAUNIALERTS', 'Unikátních alarmù');
DEFINE('_SUASRCADD', 'Zdrojových adres');
DEFINE('_SUADSTADD', 'Cílových adres');
//base_user.php
DEFINE('_BASEUSERTITLE', 'U¾ivatelské pøedvolby BASE');
DEFINE('_BASEUSERERRPWD', 'Heslo nesmí být prázné nebo heslo nesouhlasí!');
DEFINE('_BASEUSEROLDPWD', 'Staré heslo:');
DEFINE('_BASEUSERNEWPWD', 'Nové heslo:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Nové heslo znovu:');
DEFINE('_LOGOUT', 'Odhlásit');
?>
