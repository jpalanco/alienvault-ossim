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
DEFINE('_CHARSET', 'iso-8859-1');
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Logga in:');
DEFINE('_FRMPWD', 'Lösenord');
DEFINE('_SOURCE', 'Källa');
DEFINE('_SOURCENAME', 'Käll namn');
DEFINE('_DEST', 'Destination');
DEFINE('_DESTNAME', 'Dest. Namn');
DEFINE('_SORD', 'Src or Dest');
DEFINE('_EDIT', 'Edit');
DEFINE('_DELETE', 'Radera');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Namn');
DEFINE('_INTERFACE', 'Gränssnitt');
DEFINE('_FILTER', 'Filter');
DEFINE('_DESC', 'Beskrivning');
DEFINE('_LOGIN', 'Logga in');
DEFINE('_ROLEID', 'Roll ID');
DEFINE('_ENABLED', 'Möjliggjord');
DEFINE('_SUCCESS', 'Utfört');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Signatur');
DEFINE('_TIMESTAMP', 'Tid stämpel');
DEFINE('_NBSOURCEADDR', 'Källa&nbsp;Adress');
DEFINE('_NBDESTADDR', 'Dest.&nbsp;Adress');
DEFINE('_NBLAYER4', 'Layer&nbsp;4&nbsp;Proto');
DEFINE('_PRIORITY', 'Prioritet');
DEFINE('_EVENTTYPE', 'Händelse typ');
DEFINE('_JANUARY', 'Januari');
DEFINE('_FEBRUARY', 'Februari');
DEFINE('_MARCH', 'Mars');
DEFINE('_APRIL', 'April');
DEFINE('_MAY', 'Maj');
DEFINE('_JUNE', 'Juni');
DEFINE('_JULY', 'Juli');
DEFINE('_AUGUST', 'Augusti');
DEFINE('_SEPTEMBER', 'September');
DEFINE('_OCTOBER', 'Oktober');
DEFINE('_NOVEMBER', 'November');
DEFINE('_DECEMBER', 'December');
DEFINE('_LAST', 'Senaste');
DEFINE('_FIRST', 'Föregående'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Varningar');
DEFINE('_ADDRESS', 'Adress');
DEFINE('_UNKNOWN', 'Okänd');
DEFINE('_AND', 'OCH'); //NEW
DEFINE('_OR', 'ELLER'); //NEW
DEFINE('_IS', 'LIKA MED'); //NEW
DEFINE('_ON', 'on'); //NEW
DEFINE('_IN', 'in'); //NEW
DEFINE('_ANY', 'any'); //NEW
DEFINE('_NONE', 'none'); //NEW
DEFINE('_HOUR', 'Timme'); //NEW
DEFINE('_DAY', 'Dag'); //NEW
DEFINE('_MONTH', 'Månad'); //NEW
DEFINE('_YEAR', 'År'); //NEW
DEFINE('_ALERTGROUP', 'Varnings Grupp'); //NEW
DEFINE('_ALERTTIME', 'Varnings Tid'); //NEW
DEFINE('_CONTAINS', 'innehåller'); //NEW
DEFINE('_DOESNTCONTAIN', 'innehåller inte'); //NEW
DEFINE('_SOURCEPORT', 'Käll port'); //NEW
DEFINE('_DESTPORT', 'dest. port'); //NEW
DEFINE('_HAS', 'har'); //NEW
DEFINE('_HASNOT', 'har inte'); //NEW
DEFINE('_PORT', 'Port'); //NEW
DEFINE('_FLAGS', 'Flagor'); //NEW
DEFINE('_MISC', 'Misc'); //NEW
DEFINE('_BACK', 'Tillbaka'); //NEW
DEFINE('_DISPYEAR', '{ år }'); //NEW
DEFINE('_DISPMONTH', '{ månad }'); //NEW
DEFINE('_DISPHOUR', '{ timme }'); //NEW
DEFINE('_DISPDAY', '{ dag }'); //NEW
DEFINE('_DISPTIME', '{ tid }'); //NEW
DEFINE('_ADDADDRESS', 'Lägg till Address'); //NEW
DEFINE('_ADDIPFIELD', 'Lägg till IP fält'); //NEW
DEFINE('_ADDTIME', 'Lägg till Tid'); //NEW
DEFINE('_ADDTCPPORT', 'Lägg till TCP port'); //NEW
DEFINE('_ADDTCPFIELD', 'Lägg till TCP fält'); //NEW
DEFINE('_ADDUDPPORT', 'Lägg till UDP port'); //NEW
DEFINE('_ADDUDPFIELD', 'Lägg till UDP fält'); //NEW
DEFINE('_ADDICMPFIELD', 'Lägg till ICMP fält'); //NEW
DEFINE('_ADDPAYLOAD', 'Lägg till Payload'); //NEW
DEFINE('_MOSTFREQALERTS', 'Mest frekventa Varningar'); //NEW
DEFINE('_MOSTFREQPORTS', 'Mest frekventa Portar'); //NEW
DEFINE('_MOSTFREQADDRS', 'Mest frekventa IP adresser'); //NEW
DEFINE('_LASTALERTS', 'Last Alerts'); //NEW
DEFINE('_LASTPORTS', 'Last Ports'); //NEW
DEFINE('_LASTTCP', 'Last TCP Alerts'); //NEW
DEFINE('_LASTUDP', 'Last UDP Alerts'); //NEW
DEFINE('_LASTICMP', 'Last ICMP Alerts'); //NEW
DEFINE('_QUERYDB', 'Query DB'); //NEW
DEFINE('_QUERYDBP', 'Query+DB'); //NEW - Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Valda'); //NEW
DEFINE('_ALLONSCREEN', 'ALLA på skärmen'); //NEW
DEFINE('_ENTIREQUERY', 'Hela förfrågan'); //NEW
DEFINE('_OPTIONS', 'Options'); //NEW
DEFINE('_LENGTH', 'längd'); //NEW
DEFINE('_CODE', 'kod'); //NEW
DEFINE('_DATA', 'data'); //NEW
DEFINE('_TYPE', 'type'); //NEW
DEFINE('_NEXT', 'Nästa'); //NEW
DEFINE('_PREVIOUS', 'Föregående'); //NEW
//Menu items
DEFINE('_HOME', 'Hem');
DEFINE('_SEARCH', 'Sök');
DEFINE('_AGMAINT', 'Varnings grupp Skötsel');
DEFINE('_USERPREF', 'Användar inställningar');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administration');
DEFINE('_GALERTD', 'Graf varnings Data');
DEFINE('_GALERTDT', 'Graf varning avkänning tid');
DEFINE('_USERMAN', 'Användar Hantering');
DEFINE('_LISTU', 'Lista användare');
DEFINE('_CREATEU', 'Skapa en användare');
DEFINE('_ROLEMAN', 'Roll hantering');
DEFINE('_LISTR', 'Lista Roller');
DEFINE('_LOGOUT', 'Logout');
DEFINE('_CREATER', 'Skapa en roll');
DEFINE('_LISTALL', 'Lista alla');
DEFINE('_CREATE', 'Skapa');
DEFINE('_VIEW', 'Se/visa');
DEFINE('_CLEAR', 'Rensa');
DEFINE('_LISTGROUPS', 'Lista grupper');
DEFINE('_CREATEGROUPS', 'Skapa grupp');
DEFINE('_VIEWGROUPS', 'Se Grupp');
DEFINE('_EDITGROUPS', 'Redigera Grupp');
DEFINE('_DELETEGROUPS', 'Ta bort grupp');
DEFINE('_CLEARGROUPS', 'Rensa Grupp');
DEFINE('_CHNGPWD', 'Byta lösenord');
DEFINE('_DISPLAYU', 'Se användare');
//base_footer.php
DEFINE('_FOOTER', '( av <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> och <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw )'); //----
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Användare finns inte kontrollera användarnamn och lösenord!<br>Var vänlig försök igen');
// base_main.php
DEFINE('_MOSTRECENT', 'De senaste ');
DEFINE('_MOSTFREQUENT', 'Ofta förkommande ');
DEFINE('_ALERTS', ' Varningar:');
DEFINE('_ADDRESSES', ' Adresser:');
DEFINE('_ANYPROTO', 'Protokoll');
DEFINE('_UNI', 'Unik');
DEFINE('_LISTING', 'Lista');
DEFINE('_TALERTS', 'Dagens varningar: ');
DEFINE('_SOURCEIP', 'Käll IP'); //NEW
DEFINE('_DESTIP', 'Destinations IP'); //NEW
DEFINE('_L24ALERTS', 'Varningar dom senaste 24 timmarna  : ');
DEFINE('_L72ALERTS', 'Varningar dom senaste 72 timmarna: ');
DEFINE('_UNIALERTS', ' Unika varningar');
DEFINE('_LSOURCEPORTS', 'Senaste käll Port(ar): ');
DEFINE('_LDESTPORTS', 'Senaste Destination Port(ar): ');
DEFINE('_FREGSOURCEP', 'Mest förekommande käll Portar: ');
DEFINE('_FREGDESTP', 'Mest förkommande Destinations Portar: ');
DEFINE('_QUERIED', 'Queried on'); //----------------------------------------------
DEFINE('_DATABASE', 'Databas:');
DEFINE('_SCHEMAV', 'Diagram Version:');
DEFINE('_TIMEWIN', 'Tid Fönster:');
DEFINE('_NOALERTSDETECT', 'Inga varningar hittades');
DEFINE('_USEALERTDB', 'Välj Alert Databasen'); //NEW
DEFINE('_USEARCHIDB', 'Välj Archive Databasen'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Åtgärden lyckades');
DEFINE('_NOPWDCHANGE', 'Kunde inte ändra ditt lösenord!: ');
DEFINE('_NOUSER', 'Användare existerar inte!');
DEFINE('_OLDPWD', 'Det gamla lösenordet matcha inte våra uppgifter!');
DEFINE('_PWDCANT', 'Det gick inte ändra ditt lösenord: ');
DEFINE('_PWDDONE', 'Ditt lösenord har ändrats');
DEFINE('_ROLEEXIST', 'Rollen existerar redan!');
DEFINE('_ROLEIDEXIST', 'Roll ID finns redan!');
DEFINE('_ROLEADDED', 'Rollen har lagts till');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Roll Administration');
DEFINE('_FRMROLEID', 'Roll ID:');
DEFINE('_FRMROLENAME', 'Roll Namn:');
DEFINE('_FRMROLEDESC', 'Beskrivning:');
DEFINE('_UPDATEROLE', 'Uppdatera Roll'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE Användar Administration');
DEFINE('_FRMFULLNAME', 'Hela Namnet:');
DEFINE('_FRMROLE', 'Roll:');
DEFINE('_FRMUID', 'Användar ID:');
DEFINE('_SUBMITQUERY', 'Exekvera'); //NEW
DEFINE('_UPDATEUSER', 'Uppdatera Användare'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Administration');
DEFINE('_BASEADMINTEXT', 'Vänligen välj en valmöjlighet från vänster.');
//base_action.inc.php
DEFINE('_NOACTION', 'Inget agerande var specifierat på varningar');
DEFINE('_INVALIDACT', ' är ett felaktigt agerande');
DEFINE('_ERRNOAG', 'Kunde inte lägga til varningar då ingen AG var specifierad');
DEFINE('_ERRNOEMAIL', 'Kan inte e maila varningar då ingen email adress har specifierats');
DEFINE('_ACTION', 'Handling');
DEFINE('_CONTEXT', 'Innehål');
DEFINE('_ADDAGID', 'Lägga till till AG (Genom ID)');
DEFINE('_ADDAG', 'Lägg-till-ny-AG');
DEFINE('_ADDAGNAME', 'Lägga till till AG (genom Namn)');
DEFINE('_CREATEAG', 'Skapa AG (Genom namn)');
DEFINE('_CLEARAG', 'Rensa från AG');
DEFINE('_DELETEALERT', 'Radera Varing(ar)');
DEFINE('_EMAILALERTSFULL', 'Email Varning(ar) (Alla)');
DEFINE('_EMAILALERTSSUMM', 'Email Varning(ar) (sammanfattning)');
DEFINE('_EMAILALERTSCSV', 'Email Varning(ar) (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Arkiv Varning(ar) (Kopiera)');
DEFINE('_ARCHIVEALERTSMOVE', 'Arkiv Varning(ar) (Flytta)');
DEFINE('_IGNORED', 'Ignorerad ');
DEFINE('_DUPALERTS', ' Dubblet Varning(ar)');
DEFINE('_ALERTSPARA', ' Varning(ar)');
DEFINE('_NOALERTSSELECT', 'Inga varningar var valda till');
DEFINE('_NOTSUCCESSFUL', 'Gick inte utföra');
DEFINE('_ERRUNKAGID', 'Okänd AG ID specifierad (AG finns förmodligen inte)');
DEFINE('_ERRREMOVEFAIL', 'Kunde inte ta bort nya AG');
DEFINE('_GENBASE', 'Genererad av BASE');
DEFINE('_ERRNOEMAILEXP', 'EXPORT ERROR: Kunde inte skicka exporterade varningar till');
DEFINE('_ERRNOEMAILPHP', 'Kolla mail inställningar PHP.');
DEFINE('_ERRDELALERT', 'Fel vid radering av varning');
DEFINE('_ERRARCHIVE', 'Arkiv fel:');
DEFINE('_ERRMAILNORECP', 'POST FEL: Ingen mottagare har Specifierats');
//base_cache.inc.php
DEFINE('_ADDED', 'Added ');
DEFINE('_HOSTNAMESDNS', ' Värdnamn till IP DNS cachen');
DEFINE('_HOSTNAMESWHOIS', ' Värdnamn till Whois cachen');
DEFINE('_ERRCACHENULL', 'Caching FEL: Värdelös händelse rad funnen?');
DEFINE('_ERRCACHEERROR', 'Händelse CACHE fel:');
DEFINE('_ERRCACHEUPDATE', 'Kunde inte uppdatera händelse cachen');
DEFINE('_ALERTSCACHE', ' Varning(ar) till varnings cachen');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Förhindrad till att öppna SQL spår filen');
DEFINE('_ERRSQLCONNECT', 'Fel vid anslutande till DB :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Kontrollera DB anslutnings variabler i  <I>base_conf.php</I>
              <PRE>
               = $alert_dbname   : MySQL databas namn där varningar är sparade
               = $alert_host     : värd där databasen är sparad
               = $alert_port     : Port till databasen
               = $alert_user     : användarnamn till databasen
               = $alert_password : lösenord för användarnamn
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Fel (p)Kopplar till DB :');
DEFINE('_ERRSQLDB', 'Databas FEL:');
DEFINE('_DBALCHECK', 'Kontroll för DB abstraktion lib i');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Fel vid laddning av DB Abstraktion biblioteket </B> från ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Kontrollera DB abstraktion bibliotek variabel <CODE>$DBlib_path</CODE> i <CODE>base_conf.php</CODE>
            <P>
            Bakomliggande databas bibliotek som används för närvarande är ADODB, som kan laddas ner
            från <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Ogiltig Databas typ Specifierad');
DEFINE('_ERRSQLDBTYPEINFO1', 'variabel <CODE>\$DBtype</CODE> i <CODE>base_conf.php</CODE> var satt till anonym databas typ av ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Endast följande databaser är giltiga: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//###########################################################################################
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE FATAL ERROR:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Laddades på');
DEFINE('_SECONDS', 'sekunder');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Unable to resolve address');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Query Results Output Header');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName unknown');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority unknown');
DEFINE('_UNCLASS', 'unclassified');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'data encoded as');
DEFINE('_SHORTJAN', 'Jan'); //NEW
DEFINE('_SHORTFEB', 'Feb'); //NEW
DEFINE('_SHORTMAR', 'Mar'); //NEW
DEFINE('_SHORTAPR', 'Apr'); //NEW
DEFINE('_SHORTMAY', 'Maj'); //NEW
DEFINE('_SHORTJUN', 'Jun'); //NEW
DEFINE('_SHORTJLY', 'Jul'); //NEW
DEFINE('_SHORTAUG', 'Aug'); //NEW
DEFINE('_SHORTSEP', 'Sep'); //NEW
DEFINE('_SHORTOCT', 'Oct'); //NEW
DEFINE('_SHORTNOV', 'Nov'); //NEW
DEFINE('_SHORTDEC', 'Dec'); //NEW
DEFINE('_DISPSIG', '{ regel }'); //NEW
DEFINE('_DISPANYCLASS', '{ Klassifikation }'); //NEW
DEFINE('_DISPANYPRIO', '{ Prioritet }'); //NEW
DEFINE('_DISPANYSENSOR', '{ Sensor }'); //NEW
DEFINE('_DISPADDRESS', '{ adress }'); //NEW
DEFINE('_DISPFIELD', '{ fält }'); //NEW
DEFINE('_DISPPORT', '{ port }'); //NEW
DEFINE('_DISPENCODING', '{ kodning }'); //NEW
DEFINE('_DISPCONVERT2', '{ Konvertera Till }'); //NEW
DEFINE('_DISPANYAG', '{ Vilken grupp som helst }'); //NEW
DEFINE('_DISPPAYLOAD', '{ payload }'); //NEW
DEFINE('_DISPFLAGS', '{ flaggor }'); //NEW
DEFINE('_SIGEXACTLY', 'exakt'); //NEW
DEFINE('_SIGROUGHLY', 'ungefär'); //NEW
DEFINE('_SIGCLASS', 'Signatur Klassifikation'); //NEW
DEFINE('_SIGPRIO', 'Signature Prioritet'); //NEW
DEFINE('_SHORTSOURCE', 'Källa'); //NEW
DEFINE('_SHORTDEST', 'Mål'); //NEW
DEFINE('_SHORTSOURCEORDEST', 'Källa eller Dest.'); //NEW
DEFINE('_NOLAYER4', 'ingen layer4'); //NEW
DEFINE('_INPUTCRTENC', 'Kodnings Typ'); //NEW
DEFINE('_CONVERT2WS', 'Konventera Till (under sökning)'); //NEW
DEFINE('_NODENCODED', '(no data conversion, assuming criteria in DB native encoding)');
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Session Registrerad');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Raderar');
DEFINE('_FROMCRIT', 'från kriterium');
DEFINE('_ERRCRITELEM', 'Invalid criteria element');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List');
DEFINE('_DISPLAYING', 'Visar');
DEFINE('_DISPLAYINGTOTAL', 'Visar varningar %d-%d av %s total');
DEFINE('_NOALERTS', 'Inga varningar funna.');
DEFINE('_QUERYRESULTS', 'Query Results'); //---------------
DEFINE('_DISPACTION', '{ att göra }'); //NEW
DEFINE('_QUERYSTATE', 'Query State'); //--------------------
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Det Specifierade AG namnet du sökte efter är ogiltig.  Prova igen!');
DEFINE('_ERRAGNAMEEXIST', 'Den specifierade AG existerar.');
DEFINE('_ERRAGIDSEARCH', 'The specified AG ID search is invalid.  Try again!');
DEFINE('_ERRAGLOOKUP', 'Error looking up an AG ID');
DEFINE('_ERRAGINSERT', 'Error Inserting new AG');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Alert Group (AG) Maintenance');
DEFINE('_ERRAGUPDATE', 'Fel vid uppdatering av AG');
DEFINE('_ERRAGPACKETLIST', 'Fel raderar paket listan för AG:');
DEFINE('_ERRAGDELETE', 'FEL vid radering av AG');
DEFINE('_AGDELETE', 'Raderingen lyckades');
DEFINE('_AGDELETEINFO', 'information raderad');
DEFINE('_ERRAGSEARCHINV', 'The entered search criteria is invalid.  Try again!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'No AG found with that criteria.');
DEFINE('_NOALERTGOUPS', 'Det finns inga varnings grupper');
DEFINE('_NUMALERTS', '# Varningar');
DEFINE('_ACTIONS', 'Handlingar');
DEFINE('_SAVECHANGES', 'Spara Ändringarna'); //NEW
DEFINE('_CONFIRMDELETE', 'Bekräfta Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Bekräfta Rensning'); //NEW
DEFINE('_NOTASSIGN', 'not assigned yet');
//base_common.php
DEFINE('_PORTSCAN', 'Portscan Traffic'); //-------------------------------------------------------------------------------
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Kunde inte skapa INDEX för');
DEFINE('_DBINDEXCREATE', 'Lyckades skapa INDEX för');
DEFINE('_ERRSNORTVER', 'It might be an older version.  Only alert databases created by Snort 1.7-beta0 or later are supported');
DEFINE('_ERRSNORTVER1', 'The underlying database');
DEFINE('_ERRSNORTVER2', 'appears to be incomplete/invalid');
DEFINE('_ERRDBSTRUCT1', 'The database version is valid, but the BASE DB structure');
DEFINE('_ERRDBSTRUCT2', 'is not present. Use the <A HREF="base_db_setup.php">Setup page</A> to configure and optimize the DB.');
DEFINE('_ERRPHPERROR', 'PHP ERROR');
DEFINE('_ERRPHPERROR1', 'Incompatible version');
DEFINE('_ERRVERSION', 'Version');
DEFINE('_ERRPHPERROR2', 'Din verison av PHP är för gammal!.  Var vänlig och uppgradera till version 4.0.4 eller nyare!');
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
DEFINE('_CHARTTYPE', 'Graf typ:'); //NEW
DEFINE('_CHARTTYPES', '{ Graf typ }'); //NEW
DEFINE('_CHARTPERIOD', 'Graf Period:'); //NEW
DEFINE('_PERIODNO', 'ingen period'); //NEW
DEFINE('_PERIODWEEK', '7 (en vecka)'); //NEW
DEFINE('_PERIODDAY', '24 (hel dag)'); //NEW
DEFINE('_PERIOD168', '168 (24x7)'); //NEW
DEFINE('_CHARTSIZE', 'Size: (bredd x höjd)'); //NEW
DEFINE('_PLOTMARGINS', 'Graf Marginaler: (vänster x höger x uppe x nere)'); //NEW
DEFINE('_PLOTTYPE', 'Graf typ:'); //NEW
DEFINE('_TYPEBAR', 'Stapel'); //NEW
DEFINE('_TYPELINE', 'linje'); //NEW
DEFINE('_TYPEPIE', 'circle'); //NEW
DEFINE('_CHARTHOUR', '{timme}'); //NEW
DEFINE('_CHARTDAY', '{dag}'); //NEW
DEFINE('_CHARTMONTH', '{månad}'); //NEW
DEFINE('_GRAPHALERTS', 'Skapa Graf'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y Kontroller'); //NEW
DEFINE('_CHARTTITLE', 'Graf Namn:');
DEFINE('_CHRTTYPEHOUR', 'Tid (timme) vs. Nummer av varningar');
DEFINE('_CHRTTYPEDAY', 'Tid (dag) vs. Nummer av varningar');
DEFINE('_CHRTTYPEWEEK', 'Tid (vecka) vs. Nummer av varningar');
DEFINE('_CHRTTYPEMONTH', 'Tid (månad) vs. Nummer av varningar');
DEFINE('_CHRTTYPEYEAR', 'Tid (år) vs. Nummer av varningar');
DEFINE('_CHRTTYPESRCIP', 'Src. IP address vs. Nummer av varningar');
DEFINE('_CHRTTYPEDSTIP', 'Dst. IP address vs. Nummer av varningar');
DEFINE('_CHRTTYPEDSTUDP', 'Dst. UDP Port vs. Nummer av varningar');
DEFINE('_CHRTTYPESRCUDP', 'Src. UDP Port vs. Nummer av varningar');
DEFINE('_CHRTTYPEDSTPORT', 'Dst. TCP Port vs. Nummer av varningar');
DEFINE('_CHRTTYPESRCPORT', 'Src. TCP Port vs. Nummer av varningar');
DEFINE('_CHRTTYPESIG', 'Sig. Classification vs. Nummer av varningar');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. Nummer av varningar');
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
DEFINE('_MNTPHPLOGLVL', 'PHP Logg nivå:');
DEFINE('_MNTPHPMODS', 'Laddade Moduler:');
DEFINE('_MNTDBTYPE', 'DB Typ:');
DEFINE('_MNTDBALV', 'DB Abstraktion Version:');
DEFINE('_MNTDBALERTNAME', 'Varna DB Namn:');
DEFINE('_MNTDBARCHNAME', 'Arkiv DB Namn:');
DEFINE('_MNTAIC', 'Varnings information Cache:');
DEFINE('_MNTAICTE', 'Total Events:');
DEFINE('_MNTAICCE', 'Cached händelser:');
DEFINE('_MNTIPAC', 'IP Address Cache');
DEFINE('_MNTIPACUSIP', 'Unik käll IP:');
DEFINE('_MNTIPACDNSC', 'DNS Cachad:');
DEFINE('_MNTIPACWC', 'Whois Cachad:');
DEFINE('_MNTIPACUDIP', 'Unik Dst IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Felaktig (sid,cid) par');
DEFINE('_QAALERTDELET', 'Varning raderad');
DEFINE('_QATRIGGERSIG', 'Avfyrade Signature');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Snabb logning vald så paylod kommer inte visas'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'signature');
DEFINE('_QCIPADDR', 'IP addresser');
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
DEFINE('_QFRMSORTNONE', 'ingen'); //NEW
DEFINE('_QFRMSORTORDER', 'Sort order');
DEFINE('_QFRMTIMEA', 'timestamp (ascend)');
DEFINE('_QFRMTIMED', 'timestamp (descend)');
DEFINE('_QFRMSIG', 'signatur');
DEFINE('_QFRMSIP', 'Käll IP');
DEFINE('_QFRMDIP', 'dest. IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Statistik sammanfattning');
DEFINE('_QSCTIMEPROF', 'Tid profil');
DEFINE('_QSCOFALERTS', 'Varningar');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'listade varningar');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategorier:');
DEFINE('_SCSENSORTOTAL', 'Sensors/Totalt:');
DEFINE('_SCTOTALNUMALERTS', 'Total Number of Alerts:');
DEFINE('_SCSRCIP', 'Src IP addrs:');
DEFINE('_SCDSTIP', 'Dest. IP addrs:');
DEFINE('_SCUNILINKS', 'Unique IP links');
DEFINE('_SCSRCPORTS', 'Source Ports: ');
DEFINE('_SCDSTPORTS', 'Dest Ports: ');
DEFINE('_SCSENSORS', 'Sensors');
DEFINE('_SCCLASS', 'classifications');
DEFINE('_SCUNIADDRESS', 'Unika adresser: ');
DEFINE('_SCSOURCE', 'Source');
DEFINE('_SCDEST', 'Destination');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN EVENT ERROR: ');
DEFINE('_PSEVENTERRNOFILE', 'No file was specified in the \$portscan_file variable.');
DEFINE('_PSEVENTERROPENFILE', 'Unable to open Portscan event file');
DEFINE('_PSDATETIME', 'Datum/Tid');
DEFINE('_PSSRCIP', 'Käll IP');
DEFINE('_PSDSTIP', 'Destination IP');
DEFINE('_PSSRCPORT', 'Käll Port');
DEFINE('_PSDSTPORT', 'Destination Port');
DEFINE('_PSTCPFLAGS', 'TCP Flags');
DEFINE('_PSTOTALOCC', 'Total<BR> Occurrences');
DEFINE('_PSNUMSENSORS', 'Antal Sensors');
DEFINE('_PSFIRSTOCC', 'Första<BR> Occurrence');
DEFINE('_PSLASTOCC', 'Sista<BR> Occurrence');
DEFINE('_PSUNIALERTS', 'Unika Varningar');
DEFINE('_PSPORTSCANEVE', 'Portscan Events');
DEFINE('_PSREGWHOIS', 'Registry lookup (whois) in');
DEFINE('_PSNODNS', 'no DNS resolution attempted');
DEFINE('_PSNUMSENSORSBR', 'Num of <BR>Sensors');
DEFINE('_PSOCCASSRC', 'Occurances <BR>as Src.');
DEFINE('_PSOCCASDST', 'Occurances <BR>as Dest.');
DEFINE('_PSTOTALHOSTS', 'Totalt antal Hosts Scannade'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'visa'); //NEW
DEFINE('_PSEXTERNAL', 'extern'); //NEW
DEFINE('_PSWHOISINFO', 'Whois Information');
//base_stat_iplink.php-------------------------------------------------------------------------------------------------------
DEFINE('_SIPLTITLE', 'IP Länkar');
DEFINE('_SIPLSOURCEFGDN', 'Käll FQDN');
DEFINE('_SIPLDESTFGDN', 'Destination FQDN');
DEFINE('_SIPLDIRECTION', 'Direction');
DEFINE('_SIPLPROTO', 'Protokol');
DEFINE('_SIPLUNIDSTPORTS', 'Unika Dst Portar');
DEFINE('_SIPLUNIEVENTS', 'Unika händelser');
DEFINE('_SIPLTOTALEVENTS', 'Alla händelser');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unik');
DEFINE('_DSTPS', 'Destination Port(s)');
DEFINE('_OCCURRENCES', 'Förekommande'); //NEW
DEFINE('_SRCPS', 'Käll Port(ar)');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Lista Sensorer ');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Tid Profil av Varningar');
DEFINE('_BSTTIMECRIT', 'Time Criteria'); //Ingen profilering kriterium var specifierad
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Ingen profilering kriterium var specifierad</B>  Klicka på "timme", "dag", eller "månad" att välja kornighet av total statistik.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>Arten av tid parameter vilken kommer att vara passerade var inte specifierad!</B>  .</FONT>'); //Välj antingen "på", för att specifiera ett  datum, eller "mellan" för att specifiera en intervall.
DEFINE('_BSTERRNOYEAR', '<FONT><B>Ingen År parameter var specifierad!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Ingen Månad parameter var specifierad!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profil'); //NEW
DEFINE('_TIMEON', 'på'); //NEW
DEFINE('_TIMEBETWEEN', 'mellan'); //NEW
DEFINE('_PROFILEALERT', 'Profil Alert'); //NEW
DEFINE('_BSTERRNODAY', '<FONT><B>Ingen dag parameter var specifierad!</B></FONT>');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Unika käll adress(er)');
DEFINE('_SUASRCIP', 'Käll IP address');
DEFINE('_SUAERRCRITADDUNK', 'CRITERIA ERROR: unknown address type -- assuming Dst address');
DEFINE('_UNIDADD', 'Unik(a) Destination Adress(er)');
DEFINE('_SUADSTIP', 'Dst IP adress');
DEFINE('_SUAUNIALERTS', 'Unika&nbsp;Varningar');
DEFINE('_SUASRCADD', 'Src.&nbsp;Addr.');
DEFINE('_SUADSTADD', 'Dest.&nbsp;Addr.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE Användar inställningar');
DEFINE('_BASEUSERERRPWD', 'Ditt lösenord kan inte vara blankt eller så var lösenorden inte lika!');
DEFINE('_BASEUSEROLDPWD', 'Gammalt lösenord:');
DEFINE('_BASEUSERNEWPWD', 'Nytt lösenord:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Nytt lösenord igen:');
?>
