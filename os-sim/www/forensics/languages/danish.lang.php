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
DEFINE('_FRMLOGIN', 'Login:');
DEFINE('_FRMPWD', 'Password:');
DEFINE('_SOURCE', 'Kilde');
DEFINE('_SOURCENAME', 'Kilde Navn');
DEFINE('_DEST', 'Destination');
DEFINE('_DESTNAME', 'Dest. Navn');
DEFINE('_SORD', 'Kilde eller Dest.');
DEFINE('_EDIT', 'Rediger');
DEFINE('_DELETE', 'Slet');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Navn');
DEFINE('_INTERFACE', 'Brugerflade');
DEFINE('_FILTER', 'Filter');
DEFINE('_DESC', 'Beskrivelse');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'Rolle ID');
DEFINE('_ENABLED', 'Enabled');
DEFINE('_SUCCESS', 'Successful');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Signature');
DEFINE('_TIMESTAMP', 'Tidsmærke');
DEFINE('_NBSOURCEADDR', 'Kilde&nbsp;Adresse');
DEFINE('_NBDESTADDR', 'Dest.&nbsp;Adresse');
DEFINE('_NBLAYER4', 'Lag&nbsp;4&nbsp;Proto');
DEFINE('_PRIORITY', 'Prioritet');
DEFINE('_EVENTTYPE', 'hændelses type');
DEFINE('_JANUARY', 'Januar');
DEFINE('_FEBRUARY', 'Februar');
DEFINE('_MARCH', 'Marts');
DEFINE('_APRIL', 'April');
DEFINE('_MAY', 'Maj');
DEFINE('_JUNE', 'Juni');
DEFINE('_JULY', 'Juli');
DEFINE('_AUGUST', 'August');
DEFINE('_SEPTEMBER', 'September');
DEFINE('_OCTOBER', 'Oktober');
DEFINE('_NOVEMBER', 'November');
DEFINE('_DECEMBER', 'December');
DEFINE('_LAST', 'Sidst');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Alarmer');
DEFINE('_ADDRESS', 'Adresse');
DEFINE('_UNKNOWN', 'ukendt');
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
DEFINE('_HOME', 'Hjem');
DEFINE('_SEARCH', 'Søg');
DEFINE('_AGMAINT', 'Alarm Gruppe Vedligholdelse');
DEFINE('_USERPREF', 'Bruger Indstillinger');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administration');
DEFINE('_GALERTD', 'Graf Alarm Data');
DEFINE('_GALERTDT', 'Graf Alarm Opdagelses Tid');
DEFINE('_USERMAN', 'Bruger Styring');
DEFINE('_LISTU', 'Bruger Liste');
DEFINE('_CREATEU', 'Lav en ny bruger');
DEFINE('_ROLEMAN', 'Rolle Styring');
DEFINE('_LISTR', 'Rolle Liste');
DEFINE('_LOGOUT', 'Logout');
DEFINE('_CREATER', 'Lav en rolle');
DEFINE('_LISTALL', 'Vise alle');
DEFINE('_CREATE', 'Lav');
DEFINE('_VIEW', 'Vis');
DEFINE('_CLEAR', 'Ryd');
DEFINE('_LISTGROUPS', 'Gruppe Liste');
DEFINE('_CREATEGROUPS', 'Lav Gruppe');
DEFINE('_VIEWGROUPS', 'Vis Gruppe');
DEFINE('_EDITGROUPS', 'Rediger Gruppe');
DEFINE('_DELETEGROUPS', 'Slet Gruppe');
DEFINE('_CLEARGROUPS', 'Ryd Gruppe');
DEFINE('_CHNGPWD', 'Ændre password');
DEFINE('_DISPLAYU', 'Vis bruger');
//base_footer.php
DEFINE('_FOOTER', '( by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> and the <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Bruger eksistere ikke eller dit password var forkert!<br>Prøv venligst igen');
// base_main.php
DEFINE('_MOSTRECENT', 'Seneste ');
DEFINE('_MOSTFREQUENT', 'Oftes ');
DEFINE('_ALERTS', ' Alarmer:');
DEFINE('_ADDRESSES', ' Adresser:');
DEFINE('_ANYPROTO', 'alle protokoler');
DEFINE('_UNI', 'unik');
DEFINE('_LISTING', 'liste');
DEFINE('_TALERTS', 'Alarmer idag: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'De sidste 24 timers alarmer: ');
DEFINE('_L72ALERTS', 'De sidste 72 timers alarmer: ');
DEFINE('_UNIALERTS', ' Unikke Alarmer');
DEFINE('_LSOURCEPORTS', 'Sidste Kilde Porte: ');
DEFINE('_LDESTPORTS', 'Sidste Destination Porte: ');
DEFINE('_FREGSOURCEP', 'De Meste Brugte Kilde Porte: ');
DEFINE('_FREGDESTP', 'De Meste Brugte Destination Porte: ');
DEFINE('_QUERIED', 'Sat I Kø Den');
DEFINE('_DATABASE', 'Database:');
DEFINE('_SCHEMAV', 'Schema Version:');
DEFINE('_TIMEWIN', 'Tids Vindue:');
DEFINE('_NOALERTSDETECT', 'ingen alarmer fundet');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Lagt Til Vellykket');
DEFINE('_NOPWDCHANGE', 'Kan ikke ændre dit password: ');
DEFINE('_NOUSER', 'Bruger eksistere ikke!');
DEFINE('_OLDPWD', 'Det gamle password tastet ind matcher ikke vores registreringer!');
DEFINE('_PWDCANT', 'Kan ikke ændre dit password: ');
DEFINE('_PWDDONE', 'Dit password er blevet ændret!');
DEFINE('_ROLEEXIST', 'Rolle Eksistere Allerede');
DEFINE('_ROLEIDEXIST', 'Rolle ID Eksistere Allerede');
DEFINE('_ROLEADDED', 'Rolle Lagt Til Vellykket');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Rolle Administration');
DEFINE('_FRMROLEID', 'Rolle ID:');
DEFINE('_FRMROLENAME', 'Rolle Navn:');
DEFINE('_FRMROLEDESC', 'Beskrivelse:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE Bruger Administration');
DEFINE('_FRMFULLNAME', 'Fuldt Navn:');
DEFINE('_FRMROLE', 'Rolle:');
DEFINE('_FRMUID', 'Bruger ID:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Administration');
DEFINE('_BASEADMINTEXT', 'Vælg venligst en valgmulighed til venstre');
//base_action.inc.php
DEFINE('_NOACTION', 'Ingen handling var specificeret til alarmerne');
DEFINE('_INVALIDACT', ' er en ugyldig handling');
DEFINE('_ERRNOAG', 'Kunne ikke lægge nogen alarmer til da der ikke var specificeret nogen AG');
DEFINE('_ERRNOEMAIL', 'Kunne ikke emaile nogen alarmer da der ikke er nogen email adresse specificeret');
DEFINE('_ACTION', 'HANDLING');
DEFINE('_CONTEXT', 'indhold');
DEFINE('_ADDAGID', 'LÆG til AG (mth ID)');
DEFINE('_ADDAG', 'LÆG-Ny-AG');
DEFINE('_ADDAGNAME', 'LÆG til AG (mth Navn)');
DEFINE('_CREATEAG', 'Lav AG (mht Navn)');
DEFINE('_CLEARAG', 'Ryd AG');
DEFINE('_DELETEALERT', 'Slet alarm(er)');
DEFINE('_EMAILALERTSFULL', 'Email alarm(er) (fuld)');
DEFINE('_EMAILALERTSSUMM', 'Email alarm(er) (resultat)');
DEFINE('_EMAILALERTSCSV', 'Email alarm(er) (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Arkiv alarm(er) (kopier)');
DEFINE('_ARCHIVEALERTSMOVE', 'Arkiv alarm(er) (flyt)');
DEFINE('_IGNORED', 'Ignorede ');
DEFINE('_DUPALERTS', ' duplikat alarm(er)');
DEFINE('_ALERTSPARA', ' alarm(er)');
DEFINE('_NOALERTSSELECT', 'Ingen alarmer var valgt eller');
DEFINE('_NOTSUCCESSFUL', 'var ikke vellykket');
DEFINE('_ERRUNKAGID', 'Ukendt AG ID specificeret (AG eksistere sandsynligvis ikke)');
DEFINE('_ERRREMOVEFAIL', 'Kunne ikke fjerne ny AG');
DEFINE('_GENBASE', 'Udviklet af BASE');
DEFINE('_ERRNOEMAILEXP', 'EXPORT FEJL: Kunne ikke sende exporterede alarmer til');
DEFINE('_ERRNOEMAILPHP', 'Check mail configurationen i PHP.');
DEFINE('_ERRDELALERT', 'Fejl Ved Sletning Af Alarm');
DEFINE('_ERRARCHIVE', 'Arkiv fejl:');
DEFINE('_ERRMAILNORECP', 'MAIL FEJL: Ingen modtager specificeret');
//base_cache.inc.php
DEFINE('_ADDED', 'Lagt til ');
DEFINE('_HOSTNAMESDNS', ' hostnames til IP DNS cachen');
DEFINE('_HOSTNAMESWHOIS', ' hostnames til Whois cachen');
DEFINE('_ERRCACHENULL', 'Caching FEJL: TOM handlings linie fundet?');
DEFINE('_ERRCACHEERROR', 'HANDLINGS CACHING FEJL:');
DEFINE('_ERRCACHEUPDATE', 'Kunne ikke opdatere handlings cachen');
DEFINE('_ALERTSCACHE', ' alarm(er) til Alarm cachen');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Kunne ikke åbne SQL trace fil');
DEFINE('_ERRSQLCONNECT', 'Fejl ved forbindelse til DB :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Check DB forbindelses variablerne i <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : MySQL database navn alarmerne er gemt 
               = $alert_host     : host hvor databasen er gemt
               = $alert_port     : port hvor databasen er gemt
               = $alert_user     : brugername ind i databasen
               = $alert_password : password for bruger navnet
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Fejl under (p)forbindelsen til DB :');
DEFINE('_ERRSQLDB', 'Database FEJL:');
DEFINE('_DBALCHECK', 'Checker for DB abstraction lib i');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Fejl ved loading af DB Abstraction biblioteket: </B> fra ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Check DB abstraction bibliotekets variable <CODE>$DBlib_path</CODE> i <CODE>base_conf.php</CODE>
            <P>
            Det underliggende database bibliotek brugt er ADODB, som kan downloades
            på <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Invalid Database Type Specificeret');
DEFINE('_ERRSQLDBTYPEINFO1', 'Variablen <CODE>\$DBtype</CODE> i <CODE>base_conf.php</CODE> var sat til den ukendte database type af ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Kun de følgende databaser kan bruges: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE FATAL FEJL:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Loadet i');
DEFINE('_SECONDS', 'sekunder');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Kunne ikke resolve adresse');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Kø Resultater Uddata Hoved');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigNavn ukendt');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPrioritet ukendt');
DEFINE('_UNCLASS', 'unklassificeret');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'data krypteret som');
DEFINE('_NODENCODED', '(no data conversion, assuming criteria in DB native encoding)');
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
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Session Registered');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Removing');
DEFINE('_FROMCRIT', 'from criteria');
DEFINE('_ERRCRITELEM', 'Invalid criteria element');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List');
DEFINE('_DISPLAYING', 'Displaying');
DEFINE('_DISPLAYINGTOTAL', 'Displaying alerts %d-%d of %s total');
DEFINE('_NOALERTS', 'No Alerts were found.');
DEFINE('_DISPACTION', '{ action }'); //NEW
DEFINE('_QUERYRESULTS', 'Query Results');
DEFINE('_QUERYSTATE', 'Query State');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'The specified AG name search is invalid.  Try again!');
DEFINE('_ERRAGNAMEEXIST', 'The specified AG does not exist.');
DEFINE('_ERRAGIDSEARCH', 'The specified AG ID search is invalid.  Try again!');
DEFINE('_ERRAGLOOKUP', 'Error looking up an AG ID');
DEFINE('_ERRAGINSERT', 'Error Inserting new AG');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Alert Group (AG) Maintenance');
DEFINE('_ERRAGUPDATE', 'Error updating the AG');
DEFINE('_ERRAGPACKETLIST', 'Error deleting packet list for the AG:');
DEFINE('_ERRAGDELETE', 'Error deleting the AG');
DEFINE('_AGDELETE', 'DELETED successfully');
DEFINE('_AGDELETEINFO', 'information deleted');
DEFINE('_ERRAGSEARCHINV', 'The entered search criteria is invalid.  Try again!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'No AG found with that criteria.');
DEFINE('_NOALERTGOUPS', 'There are no Alert Groups');
DEFINE('_NUMALERTS', '# Alerts');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
DEFINE('_ACTIONS', 'Actions');
DEFINE('_NOTASSIGN', 'not assigned yet');
//base_common.php
DEFINE('_PORTSCAN', 'Portscan Traffic');
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
//base_graph_form.php
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
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
DEFINE('_CHRTDRAW', 'Drawing graph');
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
DEFINE('_MNTIPACUSIP', 'Unique Src IP:');
DEFINE('_MNTIPACDNSC', 'DNS Cached:');
DEFINE('_MNTIPACWC', 'Whois Cached:');
DEFINE('_MNTIPACUDIP', 'Unique Dst IP:');
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
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCUDPCRIT', 'UDP Criteria');
DEFINE('_QCICMPCRIT', 'ICMP Criteria');
DEFINE('_QCERRINVIPCRIT', 'Invalid IP address criteria');
DEFINE('_QCERRCRITADDRESSTYPE', 'was entered for as a criteria value, but the type of address (e.g. source, destination) was not specified.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicating that an IP address should be a criteria, but no address on which to match was specified.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'was selected (at #');
DEFINE('_QCERRCRITIPIPBOOL', 'Multiple IP address criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Sort order');
DEFINE('_QFRMTIMEA', 'timestamp (ascend)');
DEFINE('_QFRMTIMED', 'timestamp (descend)');
DEFINE('_QFRMSIG', 'signature');
DEFINE('_QFRMSIP', 'source IP');
DEFINE('_QFRMDIP', 'dest. IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Summary Statistics');
DEFINE('_QSCTIMEPROF', 'Time profile');
DEFINE('_QSCOFALERTS', 'of alerts');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Alert Listing');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Categories:');
DEFINE('_SCSENSORTOTAL', 'Sensors/Total:');
DEFINE('_SCTOTALNUMALERTS', 'Total Number of Alerts:');
DEFINE('_SCSRCIP', 'Src IP addrs:');
DEFINE('_SCDSTIP', 'Dest. IP addrs:');
DEFINE('_SCUNILINKS', 'Unique IP links');
DEFINE('_SCSRCPORTS', 'Source Ports: ');
DEFINE('_SCDSTPORTS', 'Dest Ports: ');
DEFINE('_SCSENSORS', 'Sensors');
DEFINE('_SCCLASS', 'classifications');
DEFINE('_SCUNIADDRESS', 'Unique addresses: ');
DEFINE('_SCSOURCE', 'Source');
DEFINE('_SCDEST', 'Destination');
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
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
DEFINE('_PSOCCASDST', 'Occurances <BR>as Dest.');
DEFINE('_PSWHOISINFO', 'Whois Information');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP Links');
DEFINE('_SIPLSOURCEFGDN', 'Source FQDN');
DEFINE('_SIPLDESTFGDN', 'Destination FQDN');
DEFINE('_SIPLDIRECTION', 'Direction');
DEFINE('_SIPLPROTO', 'Protocol');
DEFINE('_SIPLUNIDSTPORTS', 'Unique Dst Ports');
DEFINE('_SIPLUNIEVENTS', 'Unique Events');
DEFINE('_SIPLTOTALEVENTS', 'Total Events');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unique');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
DEFINE('_DSTPS', 'Destination Port(s)');
DEFINE('_SRCPS', 'Source Port(s)');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Sensor Listing');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Time Profile of Alerts');
DEFINE('_BSTTIMECRIT', 'Time Criteria');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>No profiling criteria was specified!</B>  Click on "hour", "day", or "month" to choose the granularity of the aggregate statistics.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>The type of time parameter which will be passed was not specified!</B>  Choose either "on", to specify a single date, or "between" to specify an interval.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>No Year parameter was specified!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
DEFINE('_BSTERRNOMONTH', '<FONT><B>No Month parameter was specified!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>No Day parameter was specified!</B></FONT>');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Unique Source Address(es)');
DEFINE('_SUASRCIP', 'Src IP address');
DEFINE('_SUAERRCRITADDUNK', 'CRITERIA ERROR: unknown address type -- assuming Dst address');
DEFINE('_UNIDADD', 'Unique Destination Address(es)');
DEFINE('_SUADSTIP', 'Dst IP address');
DEFINE('_SUAUNIALERTS', 'Unique&nbsp;Alerts');
DEFINE('_SUASRCADD', 'Src.&nbsp;Addr.');
DEFINE('_SUADSTADD', 'Dest.&nbsp;Addr.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE User preferences');
DEFINE('_BASEUSERERRPWD', 'Your password can not be blank or the two passwords did not match!');
DEFINE('_BASEUSEROLDPWD', 'Old Password:');
DEFINE('_BASEUSERNEWPWD', 'New Password:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'New Password Again:');
?>
