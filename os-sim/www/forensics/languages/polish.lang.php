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
DEFINE('_FRMPWD', 'Haslo:');
DEFINE('_SOURCE', 'Zrodlowy');
DEFINE('_SOURCENAME', 'Nazwa Zrodlowa');
DEFINE('_DEST', 'Docelowy');
DEFINE('_DESTNAME', 'Nazwa Docelowa');
DEFINE('_SORD', 'Zrodlowy lub Docelowy');
DEFINE('_EDIT', 'Edycja');
DEFINE('_DELETE', 'Kasuj');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Nazwa');
DEFINE('_INTERFACE', 'Interfejs');
DEFINE('_FILTER', 'Filtr');
DEFINE('_DESC', 'Opis');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'ID Roli');
DEFINE('_ENABLED', 'Wlaczony');
DEFINE('_SUCCESS', 'Pomyslny');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensory'); //NEW
DEFINE('_SIGNATURE', 'Sygnatura');
DEFINE('_TIMESTAMP', 'Data/Czas');
DEFINE('_NBSOURCEADDR', 'Adres&nbsp;Zrodlowy');
DEFINE('_NBDESTADDR', 'Adres&nbsp;Docelowy');
DEFINE('_NBLAYER4', 'Protokol&nbsp;Warstwy&nbsp;4');
DEFINE('_PRIORITY', 'Priorytet');
DEFINE('_EVENTTYPE', 'typ zdarzenia');
DEFINE('_JANUARY', 'Styczen');
DEFINE('_FEBRUARY', 'Luty');
DEFINE('_MARCH', 'Marzec');
DEFINE('_APRIL', 'Kwiecien');
DEFINE('_MAY', 'Maj');
DEFINE('_JUNE', 'Czerwiec');
DEFINE('_JULY', 'Lipiec');
DEFINE('_AUGUST', 'Sierpien');
DEFINE('_SEPTEMBER', 'Wrzesien');
DEFINE('_OCTOBER', 'Pazdziernik');
DEFINE('_NOVEMBER', 'Listopad');
DEFINE('_DECEMBER', 'Grudzien');
DEFINE('_LAST', 'Ostatni');
DEFINE('_FIRST', 'Pierwszy'); //NEW
DEFINE('_TOTAL', 'Wszystkich'); //NEW
DEFINE('_ALERT', 'Alarmy');
DEFINE('_ADDRESS', 'Adres');
DEFINE('_UNKNOWN', 'nieznany');
DEFINE('_AND', 'I'); //NEW
DEFINE('_OR', 'LUB'); //NEW
DEFINE('_IS', 'jest'); //NEW
DEFINE('_ON', 'na'); //NEW
DEFINE('_IN', 'w'); //NEW
DEFINE('_ANY', 'kazdy'); //NEW
DEFINE('_NONE', 'none'); //NEW
DEFINE('_HOUR', 'Godzina'); //NEW
DEFINE('_DAY', 'Dzien'); //NEW
DEFINE('_MONTH', 'Miesiac'); //NEW
DEFINE('_YEAR', 'Rok'); //NEW
DEFINE('_ALERTGROUP', 'Grupa Alarmow'); //NEW
DEFINE('_ALERTTIME', 'Czas Alarmu'); //NEW
DEFINE('_CONTAINS', 'zawiera'); //NEW
DEFINE('_DOESNTCONTAIN', 'nie zawiera'); //NEW
DEFINE('_SOURCEPORT', 'port zrodlowy'); //NEW
DEFINE('_DESTPORT', 'port docelowy'); //NEW
DEFINE('_HAS', 'ma'); //NEW
DEFINE('_HASNOT', 'nie ma'); //NEW
DEFINE('_PORT', 'Port'); //NEW
DEFINE('_FLAGS', 'Flagi'); //NEW
DEFINE('_MISC', 'Misc'); //NEW
DEFINE('_BACK', 'Wstecz'); //NEW
DEFINE('_DISPYEAR', '{ rok }'); //NEW
DEFINE('_DISPMONTH', '{ miesiac }'); //NEW
DEFINE('_DISPHOUR', '{ godzina }'); //NEW
DEFINE('_DISPDAY', '{ dzien }'); //NEW
DEFINE('_DISPTIME', '{ czas }'); //NEW
DEFINE('_ADDADDRESS', 'DODAJ Addr'); //NEW
DEFINE('_ADDIPFIELD', 'ADD Pole IP'); //NEW
DEFINE('_ADDTIME', 'DODAJ CZAS'); //NEW
DEFINE('_ADDTCPPORT', 'DODAJ Port TCP'); //NEW
DEFINE('_ADDTCPFIELD', 'DODAJ Pole TCP'); //NEW
DEFINE('_ADDUDPPORT', 'DODAJ Port UDP'); //NEW
DEFINE('_ADDUDPFIELD', 'DODAJ Pole UDP'); //NEW
DEFINE('_ADDICMPFIELD', 'DODAJ Pole ICMP'); //NEW
DEFINE('_ADDPAYLOAD', 'DODAJ Zawartosc'); //NEW
DEFINE('_MOSTFREQALERTS', 'Najczestsze Alarmy'); //NEW
DEFINE('_MOSTFREQPORTS', 'Najczestsze Porty'); //NEW
DEFINE('_MOSTFREQADDRS', 'Najczestsze adresy IP'); //NEW
DEFINE('_LASTALERTS', 'Ostatnie Alarmy'); //NEW
DEFINE('_LASTPORTS', 'Ostatnie Ports'); //NEW
DEFINE('_LASTTCP', 'Ostatnie Alarmy TCP'); //NEW
DEFINE('_LASTUDP', 'Ostatnie Alarmy UDP'); //NEW
DEFINE('_LASTICMP', 'Ostatnie Alarmy ICMP'); //NEW
DEFINE('_QUERYDB', 'Query DB'); //NEW
DEFINE('_QUERYDBP', 'Query+DB'); //NEW - Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Zaznaczone'); //NEW
DEFINE('_ALLONSCREEN', 'Wszystkie na Ekranie'); //NEW
DEFINE('_ENTIREQUERY', 'Cale Zapytanie'); //NEW
DEFINE('_OPTIONS', 'Opcje'); //NEW
DEFINE('_LENGTH', 'dlugosc'); //NEW
DEFINE('_CODE', 'kod'); //NEW
DEFINE('_DATA', 'dane'); //NEW
DEFINE('_TYPE', 'typ'); //NEW
DEFINE('_NEXT', 'Nastepny'); //NEW
DEFINE('_PREVIOUS', 'Poprzedni'); //NEW
//Menu items
DEFINE('_HOME', 'Strona Glowna');
DEFINE('_SEARCH', 'Szukaj');
DEFINE('_AGMAINT', 'Zarzadzanie Grupami Alarmow');
DEFINE('_USERPREF', 'Ustawienia Uzytkownika');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administracja');
DEFINE('_GALERTD', 'Wykresy Alarmow');
DEFINE('_GALERTDT', 'Wykres Czasu Wykrycia Alarmu');
DEFINE('_USERMAN', 'Zarzadzanie Uzytkownikami');
DEFINE('_LISTU', 'Lista uzytkownikow');
DEFINE('_CREATEU', 'Utworz uzytkownika');
DEFINE('_ROLEMAN', 'Zarzadzanie Rolami');
DEFINE('_LISTR', 'Lista Rol');
DEFINE('_CREATER', 'Utworz Role');
DEFINE('_LISTALL', 'Lista Wszystkich');
DEFINE('_CREATE', 'Utworz');
DEFINE('_VIEW', 'Wyswietl');
DEFINE('_CLEAR', 'Wyczysc');
DEFINE('_LISTGROUPS', 'Lista Grup');
DEFINE('_CREATEGROUPS', 'Utworz Grupe');
DEFINE('_VIEWGROUPS', 'Wyswietl Grupe');
DEFINE('_EDITGROUPS', 'Edytuj Grupe');
DEFINE('_DELETEGROUPS', 'Kasuj Grupe');
DEFINE('_CLEARGROUPS', 'Wyczysc Grupe');
DEFINE('_CHNGPWD', 'Zmien haslo');
DEFINE('_DISPLAYU', 'Wyswietl uzytkownika');
//base_footer.php
DEFINE('_FOOTER', ' (by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> i <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">Zespol BASE Project</A><BR>Built on ACID by Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Uzytkownik nie istnieje lub nieprawidlowe haslo!<br>Sprobuj ponownie');
// base_main.php
DEFINE('_MOSTRECENT', 'Ostatnie ');
DEFINE('_MOSTFREQUENT', 'Najczestsze ');
DEFINE('_ALERTS', ' Alarmow:');
DEFINE('_ADDRESSES', ' Adresow');
DEFINE('_ANYPROTO', 'wszystkie protokoly');
DEFINE('_UNI', 'unikalne');
DEFINE('_LISTING', 'lista');
DEFINE('_TALERTS', 'Dzisiejsze alarmy: ');
DEFINE('_SOURCEIP', 'IP Zrodlowy'); //NEW
DEFINE('_DESTIP', 'IP Docelowy'); //NEW
DEFINE('_L24ALERTS', 'Alarmy z ostatnich 24 godzin: ');
DEFINE('_L72ALERTS', 'Alarmy z ostatnich 72 godzin: ');
DEFINE('_UNIALERTS', ' Unikalnych Alarmow');
DEFINE('_LSOURCEPORTS', 'Ostatnie Porty Zrodlowe: ');
DEFINE('_LDESTPORTS', 'Ostatnie Porty Docelowe: ');
DEFINE('_FREGSOURCEP', 'Najczestsze Porty Zrodlowe: ');
DEFINE('_FREGDESTP', 'Najczestsze Porty Docelowe: ');
DEFINE('_QUERIED', 'Zapytanie');
DEFINE('_DATABASE', 'Baza danych:');
DEFINE('_SCHEMAV', 'Wersja:');
DEFINE('_TIMEWIN', 'Okno Czasowe:');
DEFINE('_NOALERTSDETECT', 'nie wykryto alarmow');
DEFINE('_USEALERTDB', 'Uzyj Bazy Alarmow'); //NEW
DEFINE('_USEARCHIDB', 'Uzyj Bazy Archiwum'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Profil Ruchu po Protokole'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Dodano Pomyslnie');
DEFINE('_NOPWDCHANGE', 'Nie mozna zmienic hasla: ');
DEFINE('_NOUSER', 'Uzytkownik nie istnieje!');
DEFINE('_OLDPWD', 'Wpisane stare haslo nie zgadza sie z zapisanym!');
DEFINE('_PWDCANT', 'Nie mozna zmienic hasla: ');
DEFINE('_PWDDONE', 'Twoje haslo zostalo zmienione!');
DEFINE('_ROLEEXIST', 'Role juz istnieje');
DEFINE('_ROLEIDEXIST', 'ID Roli juz istnieje');
DEFINE('_ROLEADDED', 'Pomyslnie dodano Role');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'Administracja Rolami');
DEFINE('_FRMROLEID', 'ID Roli:');
DEFINE('_FRMROLENAME', 'Nazwa Roli:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
DEFINE('_FRMROLEDESC', 'Opis:');
//base_useradmin.php
DEFINE('_USERADMIN', 'Administracja Uzytkownikami');
DEFINE('_FRMFULLNAME', 'Pelna Nazwa:');
DEFINE('_FRMROLE', 'Rola:');
DEFINE('_SUBMITQUERY', 'Wyslij Zapytanie'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
DEFINE('_FRMUID', 'ID Uzytkownika:');
//admin/index.php
DEFINE('_BASEADMIN', 'Administracja BASE');
DEFINE('_BASEADMINTEXT', 'Wybierz opcje po lewej.');
//base_action.inc.php
DEFINE('_NOACTION', 'No action was specified on the alerts');
DEFINE('_INVALIDACT', ' is an invalid action');
DEFINE('_ERRNOAG', 'Could not add alerts since no AG was specified');
DEFINE('_ERRNOEMAIL', 'Could not email alerts since no email address was specified');
DEFINE('_ACTION', 'ACTION');
DEFINE('_CONTEXT', 'kontekst');
DEFINE('_ADDAGID', 'DODAJ do GA (po ID)');
DEFINE('_ADDAG', 'DODAJ-Nowa-GA');
DEFINE('_ADDAGNAME', 'DODAJ do GA (po Nazwie)');
DEFINE('_CREATEAG', 'Utworz GA (po Nazwie)');
DEFINE('_CLEARAG', 'Wyczysc z GA');
DEFINE('_DELETEALERT', 'Kasuj alarm(y)');
DEFINE('_EMAILALERTSFULL', 'Email alarm(y) (pelny)');
DEFINE('_EMAILALERTSSUMM', 'Email alarm(y) (podsumowanie)');
DEFINE('_EMAILALERTSCSV', 'Email alarm(y) (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Archiwuj alarm(y) (kopiuj)');
DEFINE('_ARCHIVEALERTSMOVE', 'Archiwuj alarm(y) (przemiesc)');
DEFINE('_IGNORED', 'Zignorowane ');
DEFINE('_DUPALERTS', ' zduplikowane alarm(y)');
DEFINE('_ALERTSPARA', ' alarm(y)');
DEFINE('_NOALERTSSELECT', 'Nie zaznaczono alarmow albo');
DEFINE('_NOTSUCCESSFUL', 'nie powiodlo sie');
DEFINE('_ERRUNKAGID', 'Unknown AG ID specified (AG probably does not exist)');
DEFINE('_ERRREMOVEFAIL', 'Failed to remove new AG');
DEFINE('_GENBASE', 'Generated by BASE');
DEFINE('_ERRNOEMAILEXP', 'EXPORT ERROR: Could not send exported alerts to');
DEFINE('_ERRNOEMAILPHP', 'Check the mail configuration in PHP.');
DEFINE('_ERRDELALERT', 'Error Deleting Alert');
DEFINE('_ERRARCHIVE', 'Archive error:');
DEFINE('_ERRMAILNORECP', 'MAIL ERROR: No recipient Specified');
//base_cache.inc.php
DEFINE('_ADDED', 'Dodano ');
DEFINE('_HOSTNAMESDNS', ' nazw hostow do bufora IP DNS');
DEFINE('_HOSTNAMESWHOIS', ' nazw hostow do bufora Whois');
DEFINE('_ERRCACHENULL', 'Blad buforowania: NULL event row found?');
DEFINE('_ERRCACHEERROR', 'BLAD BUFOROWANIA ZDARZEN:');
DEFINE('_ERRCACHEUPDATE', 'Nie mozna odswiezyc bufora zdarzen');
DEFINE('_ALERTSCACHE', ' alarm(ow) do bufora');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Unable to open SQL trace file');
DEFINE('_ERRSQLCONNECT', 'Error connecting to DB :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Check the DB connection variables in <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : MySQL database name where the alerts are stored 
               = $alert_host     : host where the database is stored
               = $alert_port     : port where the database is stored
               = $alert_user     : username into the database
               = $alert_password : password for the username
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Error (p)connecting to DB :');
DEFINE('_ERRSQLDB', 'Database ERROR:');
DEFINE('_DBALCHECK', 'Checking for DB abstraction lib in');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Error loading the DB Abstraction library: </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Check the DB abstraction library variable <CODE>$DBlib_path</CODE> in <CODE>base_conf.php</CODE>
            <P>
            The underlying database library currently used is ADODB, that can be downloaded
            at <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Invalid Database Type Specified');
DEFINE('_ERRSQLDBTYPEINFO1', 'The variable <CODE>\$DBtype</CODE> in <CODE>base_conf.php</CODE> was set to the unrecognized database type of ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Only the following databases are supported: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE FATAL ERROR:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Zaladowano w');
DEFINE('_SECONDS', 'sekund');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Nie mozna rozwiazac adresu');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Query Results Output Header');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'nieznany SigName');
DEFINE('_ERRSIGPROIRITYUNK', 'nieznany SigPriority');
DEFINE('_UNCLASS', 'unclassified');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'dane zakodowane jako');
DEFINE('_SHORTJAN', 'Sty'); //NEW
DEFINE('_SHORTFEB', 'Lut'); //NEW
DEFINE('_SHORTMAR', 'Mar'); //NEW
DEFINE('_SHORTAPR', 'Kwi'); //NEW
DEFINE('_SHORTMAY', 'Maj'); //NEW
DEFINE('_SHORTJUN', 'Cze'); //NEW
DEFINE('_SHORTJLY', 'Lip'); //NEW
DEFINE('_SHORTAUG', 'Sie'); //NEW
DEFINE('_SHORTSEP', 'Wrz'); //NEW
DEFINE('_SHORTOCT', 'Paz'); //NEW
DEFINE('_SHORTNOV', 'Lis'); //NEW
DEFINE('_SHORTDEC', 'Gru'); //NEW
DEFINE('_DISPSIG', '{ sygnatura }'); //NEW
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
DEFINE('_SHORTSOURCE', 'Zrodlo'); //NEW
DEFINE('_SHORTDEST', 'Docelowy'); //NEW
DEFINE('_SHORTSOURCEORDEST', 'Zr lub Docel'); //NEW
DEFINE('_NOLAYER4', 'no layer4'); //NEW
DEFINE('_INPUTCRTENC', 'Input Criteria Encoding Type'); //NEW
DEFINE('_CONVERT2WS', 'Convert To (when searching)'); //NEW
DEFINE('_NODENCODED', '(no data conversion, assuming criteria in DB native encoding)');
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Session Registered');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Usunieto');
DEFINE('_FROMCRIT', 'z kryteriow');
DEFINE('_ERRCRITELEM', 'Niewlasciwy element kryteriow');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List');
DEFINE('_DISPLAYING', 'Wyswietlono');
DEFINE('_DISPLAYINGTOTAL', 'Wyswietlono alarmy od %d do %d na wszystkich %s');
DEFINE('_NOALERTS', 'Nie znaleziono alarmow.');
DEFINE('_QUERYRESULTS', 'Wyniki Zapytania');
DEFINE('_DISPACTION', '{ action }'); //NEW
DEFINE('_QUERYSTATE', 'Stan Zapytania');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'The specified AG name search is invalid.  Try again!');
DEFINE('_ERRAGNAMEEXIST', 'The specified AG does not exist.');
DEFINE('_ERRAGIDSEARCH', 'The specified AG ID search is invalid.  Try again!');
DEFINE('_ERRAGLOOKUP', 'Error looking up an AG ID');
DEFINE('_ERRAGINSERT', 'Error Inserting new AG');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Zarzadzanie Grupami Alarmow (GA)');
DEFINE('_ERRAGUPDATE', 'Blad odswiezania GA');
DEFINE('_ERRAGPACKETLIST', 'Error deleting packet list for the AG:');
DEFINE('_ERRAGDELETE', 'Blad kasowania GA');
DEFINE('_AGDELETE', 'Skasowano pomyslnie');
DEFINE('_AGDELETEINFO', 'informacja skasowana');
DEFINE('_ERRAGSEARCHINV', 'Niepoprawne kryterium wyszukiwania. Sprobuj ponownie!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Nie znaleziono GA spelniajacych kryterium.');
DEFINE('_NOALERTGOUPS', 'Nie ma Grup Alarmow');
DEFINE('_NUMALERTS', '# Alarmy');
DEFINE('_ACTIONS', 'Actions');
DEFINE('_SAVECHANGES', 'Zapisz Zmiany'); //NEW
DEFINE('_CONFIRMDELETE', 'Potwierdz Kasowanie'); //NEW
DEFINE('_CONFIRMCLEAR', 'Potwierdz Wyczyszczenie'); //NEW
DEFINE('_NOTASSIGN', 'jeszcze nie przypisano');
//base_common.php
DEFINE('_PORTSCAN', 'Portscan');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Unable to CREATE INDEX for');
DEFINE('_DBINDEXCREATE', 'Successfully created INDEX for');
DEFINE('_ERRSNORTVER', 'It might be an older version.  Only alert databases created by Snort 1.7-beta0 or later are supported');
DEFINE('_ERRSNORTVER1', 'The underlying database');
DEFINE('_ERRSNORTVER2', 'appears to be incomplete/invalid');
DEFINE('_ERRDBSTRUCT1', 'The database version is valid, but the BASE DB structure');
DEFINE('_ERRDBSTRUCT2', 'is not present. Use the <A HREF="base_db_setup.php">Setup page</A> to configure and optimize the DB.');
DEFINE('_ERRPHPERROR', 'PHP ERROR');
DEFINE('_ERRPHPERROR1', 'Incompatible version');
DEFINE('_ERRVERSION', 'Version');
DEFINE('_ERRPHPERROR2', 'of PHP is too old.  Please upgrade to version 4.0.4 or later');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite MySQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite PostgreSQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite MS SQL Server support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTYPE', 'Typ Wykresu:'); //NEW
DEFINE('_CHARTTYPES', '{ typ wykresu }'); //NEW
DEFINE('_CHARTPERIOD', 'Okres Wykresu:'); //NEW
DEFINE('_PERIODNO', 'bez okresu'); //NEW
DEFINE('_PERIODWEEK', '7 (tydzien)'); //NEW
DEFINE('_PERIODDAY', '24 (caly dzien)'); //NEW
DEFINE('_PERIOD168', '168 (24x7)'); //NEW
DEFINE('_CHARTSIZE', 'Rozmiar: (szer x wys)'); //NEW
DEFINE('_PLOTMARGINS', 'Margines Wykresu: (left x right x top x bottom)'); //NEW
DEFINE('_PLOTTYPE', 'Typ Wykresu:'); //NEW
DEFINE('_TYPEBAR', 'slupkowy'); //NEW
DEFINE('_TYPELINE', 'liniowy'); //NEW
DEFINE('_TYPEPIE', 'kolowy'); //NEW
DEFINE('_CHARTHOUR', '{godz}'); //NEW
DEFINE('_CHARTDAY', '{dzien}'); //NEW
DEFINE('_CHARTMONTH', '{mies'); //NEW
DEFINE('_GRAPHALERTS', 'Graph Alerts'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y AXIS CONTROLS'); //NEW
DEFINE('_CHARTTITLE', 'Chart Title:');
DEFINE('_CHRTTYPEHOUR', 'Time (hour) vs. Number of Alerts');
DEFINE('_CHRTTYPEDAY', 'Time (day) vs. Number of Alerts');
DEFINE('_CHRTTYPEWEEK', 'Time (week) vs. Number of Alerts');
DEFINE('_CHRTTYPEMONTH', 'Time (month) vs. Number of Alerts');
DEFINE('_CHRTTYPEYEAR', 'Time (year) vs. Number of Alerts');
DEFINE('_CHRTTYPESRCIP', 'Src. IP address vs. Number of Alerts');
DEFINE('_CHRTTYPEDSTIP', 'Dst. IP address vs. Number of Alerts');
DEFINE('_CHRTTYPEDSTUDP', 'Dst. UDP Port vs. Number of Alerts');
DEFINE('_CHRTTYPESRCUDP', 'Src. UDP Port vs. Number of Alerts');
DEFINE('_CHRTTYPEDSTPORT', 'Dst. TCP Port vs. Number of Alerts');
DEFINE('_CHRTTYPESRCPORT', 'Src. TCP Port vs. Number of Alerts');
DEFINE('_CHRTTYPESIG', 'Sig. Classification vs. Number of Alerts');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. Number of Alerts');
DEFINE('_CHRTBEGIN', 'Chart Begin:');
DEFINE('_CHRTEND', 'Chart End:');
DEFINE('_CHRTDS', 'Data Source:');
DEFINE('_CHRTX', 'X Axis');
DEFINE('_CHRTY', 'Y Axis');
DEFINE('_CHRTMINTRESH', 'Minimum Threshold Value');
DEFINE('_CHRTROTAXISLABEL', 'Rotate Axis Labels (90 degrees)');
DEFINE('_CHRTSHOWX', 'Show X-axis grid-lines');
DEFINE('_CHRTDISPLABELX', 'Display X-axis label every');
DEFINE('_CHRTDATAPOINTS', 'data points');
DEFINE('_CHRTYLOG', 'Y-axis logarithmic');
DEFINE('_CHRTYGRID', 'Show Y-axis grid-lines');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE Chart');
DEFINE('_ERRCHRTNOTYPE', 'No chart type was specified');
DEFINE('_ERRNOAGSPEC', 'No AG was specified.  Using all alerts.');
DEFINE('_CHRTDATAIMPORT', 'Starting data import');
DEFINE('_CHRTTIMEVNUMBER', 'Time vs. Number of Alerts');
DEFINE('_CHRTTIME', 'Time');
DEFINE('_CHRTALERTOCCUR', 'Alert Occurrences');
DEFINE('_CHRTSIPNUMBER', 'Source IP vs. Number of Alerts');
DEFINE('_CHRTSIP', 'Source IP Address');
DEFINE('_CHRTDIPALERTS', 'Destination IP vs. Number of Alerts');
DEFINE('_CHRTDIP', 'Destination IP Address');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP Port (Destination) vs. Number of Alerts');
DEFINE('_CHRTDUDPPORT', 'Dst. UDP Port');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP Port (Source) vs. Number of Alerts');
DEFINE('_CHRTSUDPPORT', 'Src. UDP Port');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP Port (Destination) vs. Number of Alerts');
DEFINE('_CHRTPORTDEST', 'Dst. TCP Port');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP Port (Source) vs. Number of Alerts');
DEFINE('_CHRTPORTSRC', 'Src. TCP Port');
DEFINE('_CHRTSIGNUMBER', 'Signature Classification vs. Number of Alerts');
DEFINE('_CHRTCLASS', 'Classification');
DEFINE('_CHRTSENSORNUMBER', 'Sensor vs. Number of Alerts');
DEFINE('_CHRTHANDLEPERIOD', 'Handling Period if necessary');
DEFINE('_CHRTDUMP', 'Dumping data ... (writing only every');
DEFINE('_CHRTDRAW', 'Drawing graph');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
DEFINE('_ERRCHRTNODATAPOINTS', 'No data points to plot');
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Maintenance');
DEFINE('_MNTPHP', 'PHP Build:');
DEFINE('_MNTCLIENT', 'CLIENT:');
DEFINE('_MNTSERVER', 'SERVER:');
DEFINE('_MNTSERVERHW', 'SERVER HW:');
DEFINE('_MNTPHPVER', 'PHP VERSION:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP Logging level:');
DEFINE('_MNTPHPMODS', 'Loaded Modules:');
DEFINE('_MNTDBTYPE', 'DB Type:');
DEFINE('_MNTDBALV', 'DB Abstraction Version:');
DEFINE('_MNTDBALERTNAME', 'ALERT DB Name:');
DEFINE('_MNTDBARCHNAME', 'ARCHIVE DB Name:');
DEFINE('_MNTAIC', 'Alert Information Cache:');
DEFINE('_MNTAICTE', 'Total Events:');
DEFINE('_MNTAICCE', 'Cached Events:');
DEFINE('_MNTIPAC', 'IP Address Cache');
DEFINE('_MNTIPACUSIP', 'Unikalnych IP Zrodlowych:');
DEFINE('_MNTIPACDNSC', 'DNS Cached:');
DEFINE('_MNTIPACWC', 'Whois Cached:');
DEFINE('_MNTIPACUDIP', 'Unikalnych IP Docelowych:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Invalid (sid,cid) pair');
DEFINE('_QAALERTDELET', 'Alert DELETED');
DEFINE('_QATRIGGERSIG', 'Triggered Signature');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'signature');
DEFINE('_QCIPADDR', 'IP addresses');
DEFINE('_QCIPFIELDS', 'IP fields');
DEFINE('_QCTCPPORTS', 'TCP ports');
DEFINE('_QCTCPFLAGS', 'TCP flags');
DEFINE('_QCTCPFIELD', 'TCP fields');
DEFINE('_QCUDPPORTS', 'UDP ports');
DEFINE('_QCUDPFIELDS', 'UDP fields');
DEFINE('_QCICMPFIELDS', 'ICMP fields');
DEFINE('_QCDATA', 'Data');
DEFINE('_QCERRCRITWARN', 'Criteria warning:');
DEFINE('_QCERRVALUE', 'A value of');
DEFINE('_QCERRFIELD', 'A field of');
DEFINE('_QCERROPER', 'An operator of');
DEFINE('_QCERRDATETIME', 'A date/time value of');
DEFINE('_QCERRPAYLOAD', 'A payload value of');
DEFINE('_QCERRIP', 'A IP address of');
DEFINE('_QCERRIPTYPE', 'An IP address of type');
DEFINE('_QCERRSPECFIELD', ' was entered for a protocol field, but the particular field was not specified.');
DEFINE('_QCERRSPECVALUE', 'was selected indicating that it should be a criteria, but no value was specified on which to match.');
DEFINE('_QCERRBOOLEAN', 'Multiple protocol field criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCERRDATEVALUE', 'was selected indicating that some date/time criteria should be matched, but no value was specified.');
DEFINE('_QCERRINVHOUR', '(Invalid Hour) No date criteria were entered with the specified time.');
DEFINE('_QCERRDATECRIT', 'was selected indicating that some date/time criteria should be matched, but no value was specified.');
DEFINE('_QCERROPERSELECT', 'was entered but no operator was selected.');
DEFINE('_QCERRDATEBOOL', 'Multiple Date/Time criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCERRPAYCRITOPER', 'was entered for a payload criteria field, but an operator (e.g. has, has not) was not specified.');
DEFINE('_QCERRPAYCRITVALUE', 'was selected indicating that payload should be a criteria, but no value on which to match was specified.');
DEFINE('_QCERRPAYBOOL', 'Multiple Data payload criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCMETACRIT', 'Meta Criteria');
DEFINE('_QCIPCRIT', 'IP Criteria');
DEFINE('_QCPAYCRIT', 'Payload Criteria');
DEFINE('_QCTCPCRIT', 'TCP Criteria');
DEFINE('_QCUDPCRIT', 'UDP Criteria');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCICMPCRIT', 'ICMP Criteria');
DEFINE('_QCERRINVIPCRIT', 'Invalid IP address criteria');
DEFINE('_QCERRCRITADDRESSTYPE', 'was entered for as a criteria value, but the type of address (e.g. source, destination) was not specified.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicating that an IP address should be a criteria, but no address on which to match was specified.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'was selected (at #');
DEFINE('_QCERRCRITIPIPBOOL', 'Multiple IP address criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria');
//base_qry_form.php
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMSORTORDER', 'Sort order');
DEFINE('_QFRMTIMEA', 'timestamp (ascend)');
DEFINE('_QFRMTIMED', 'timestamp (descend)');
DEFINE('_QFRMSIG', 'signature');
DEFINE('_QFRMSIP', 'source IP');
DEFINE('_QFRMDIP', 'dest. IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Statystki Sumaryczne');
DEFINE('_QSCTIMEPROF', 'Profil czasowy');
DEFINE('_QSCOFALERTS', 'alarmow');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Lista Alarmow');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategorii:');
DEFINE('_SCSENSORTOTAL', 'Sensorow/Wszystkich:');
DEFINE('_SCTOTALNUMALERTS', 'Laczna liczba alarmow:');
DEFINE('_SCSRCIP', 'Zrodlowych adresow IP:');
DEFINE('_SCDSTIP', 'Docelowych adresow IP:');
DEFINE('_SCUNILINKS', 'Unikalnych polaczen IP');
DEFINE('_SCSRCPORTS', 'Portow Zrodlowych: ');
DEFINE('_SCDSTPORTS', 'Portow Docelowych: ');
DEFINE('_SCSENSORS', 'Sensorow');
DEFINE('_SCCLASS', 'klasyfikacje');
DEFINE('_SCUNIADDRESS', 'Unikalnych adresow: ');
DEFINE('_SCSOURCE', 'Zrodlowy');
DEFINE('_SCDEST', 'Docelowy');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN EVENT ERROR: ');
DEFINE('_PSEVENTERRNOFILE', 'No file was specified in the \$portscan_file variable.');
DEFINE('_PSEVENTERROPENFILE', 'Unable to open Portscan event file');
DEFINE('_PSDATETIME', 'Data/Czas');
DEFINE('_PSSRCIP', 'Zrodlowy IP');
DEFINE('_PSDSTIP', 'Docelowy IP');
DEFINE('_PSSRCPORT', 'Port Zrodlowy');
DEFINE('_PSDSTPORT', 'Port Docelowy');
DEFINE('_PSTCPFLAGS', 'Flagi TCP');
DEFINE('_PSTOTALOCC', 'Total<BR> Occurrences');
DEFINE('_PSNUMSENSORS', 'Liczba Sensorow');
DEFINE('_PSFIRSTOCC', 'First<BR> Occurrence');
DEFINE('_PSLASTOCC', 'Ostatnie<BR> Occurrence');
DEFINE('_PSUNIALERTS', 'Unikalnych Alarmow');
DEFINE('_PSPORTSCANEVE', 'Portscan Events');
DEFINE('_PSREGWHOIS', 'Registry lookup (whois) in');
DEFINE('_PSNODNS', 'no DNS resolution attempted');
DEFINE('_PSNUMSENSORSBR', 'Num of <BR>Sensors');
DEFINE('_PSOCCASSRC', 'Occurances <BR>as Src.');
DEFINE('_PSOCCASDST', 'Occurances <BR>as Dest.');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
DEFINE('_PSWHOISINFO', 'Whois Information');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'Polaczen IP');
DEFINE('_SIPLSOURCEFGDN', 'Zrodlowy FQDN');
DEFINE('_SIPLDESTFGDN', 'Docelowy FQDN');
DEFINE('_SIPLDIRECTION', 'Kierunek');
DEFINE('_SIPLPROTO', 'Protokol');
DEFINE('_SIPLUNIDSTPORTS', 'Unikalnych Portow Docelowych');
DEFINE('_SIPLUNIEVENTS', 'Unikalnych Zdarzen');
DEFINE('_SIPLTOTALEVENTS', 'Wszystkich Zdarzen');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unikalnych');
DEFINE('_DSTPS', 'Portow Docelowych');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
DEFINE('_SRCPS', 'Portow Zrodlowych');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Lista Sensorow');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Time Profile of Alerts');
DEFINE('_BSTTIMECRIT', 'Time Criteria');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>No profiling criteria was specified!</B>  Click on "hour", "day", or "month" to choose the granularity of the aggregate statistics.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>The type of time parameter which will be passed was not specified!</B>  Choose either "on", to specify a single date, or "between" to specify an interval.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>No Year parameter was specified!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>No Month parameter was specified!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
DEFINE('_BSTERRNODAY', '<FONT><B>No Day parameter was specified!</B></FONT>');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Unikalnych Adresow Zrodlowych');
DEFINE('_SUASRCIP', 'Zrodlowy adres IP');
DEFINE('_SUAERRCRITADDUNK', 'BLAD KRYTERIUM: nieznany typ adresu -- przyjeto Docelowy');
DEFINE('_UNIDADD', 'Unikalnych Adresow Docelowych');
DEFINE('_SUADSTIP', 'Docelowy adres IP');
DEFINE('_SUAUNIALERTS', 'Unikalnych&nbsp;Alarmow');
DEFINE('_SUASRCADD', 'Zr.&nbsp;Adr.');
DEFINE('_SUADSTADD', 'Docel.&nbsp;Adr.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'Ustawienia uzytkownika');
DEFINE('_BASEUSERERRPWD', 'Your password can not be blank or the two passwords did not match!');
DEFINE('_BASEUSEROLDPWD', 'Stare Haslo:');
DEFINE('_BASEUSERNEWPWD', 'Nowe Haslo:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Powtorz Nowe Haslo:');
DEFINE('_LOGOUT', 'Wyloguj');
?>
