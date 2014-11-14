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
DEFINE('_FRMPWD', 'Passwort:');
DEFINE('_SOURCE', 'Quellen');
DEFINE('_SOURCENAME', 'Quellname');
DEFINE('_DEST', 'Ziele');
DEFINE('_DESTNAME', 'Zielname');
DEFINE('_SORD', 'Quelle oder Ziel');
DEFINE('_EDIT', 'Bearbeiten');
DEFINE('_DELETE', 'Entfernen');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Name');
DEFINE('_INTERFACE', 'Schnittstelle');
DEFINE('_FILTER', 'Filter');
DEFINE('_DESC', 'Beschreibung');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'Benutzertyp ID');
DEFINE('_ENABLED', 'Aktiv');
DEFINE('_SUCCESS', 'Erfolgreich');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Signatur');
DEFINE('_TIMESTAMP', 'Zeitstempel');
DEFINE('_NBSOURCEADDR', 'Quelladresse');
DEFINE('_NBDESTADDR', 'Zieladresse');
DEFINE('_NBLAYER4', 'Schicht&nbsp;4&nbsp;Protokoll');
DEFINE('_PRIORITY', 'Priorit&auml;t');
DEFINE('_EVENTTYPE', 'Ereignistyp');
DEFINE('_JANUARY', 'Januar');
DEFINE('_FEBRUARY', 'Februar');
DEFINE('_MARCH', 'M&auml;rz');
DEFINE('_APRIL', 'April');
DEFINE('_MAY', 'Mai');
DEFINE('_JUNE', 'Juni');
DEFINE('_JULY', 'Juli');
DEFINE('_AUGUST', 'August');
DEFINE('_SEPTEMBER', 'September');
DEFINE('_OCTOBER', 'Oktober');
DEFINE('_NOVEMBER', 'November');
DEFINE('_DECEMBER', 'Dezember');
DEFINE('_LAST', 'letzte(r/s)');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Warnungen');
DEFINE('_ADDRESS', ' Adressen');
DEFINE('_UNKNOWN', 'unbekannt');
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
DEFINE('_HOME', '&Uuml;bersicht');
DEFINE('_SEARCH', 'Suchen');
DEFINE('_AGMAINT', 'Warngruppen');
DEFINE('_USERPREF', 'Benutzereinstellungen');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administration');
DEFINE('_GALERTD', 'Warnungen grafisch aufbereiten');
DEFINE('_GALERTDT', 'Warnungsstatistik');
DEFINE('_USERMAN', 'Benutzerverwaltung');
DEFINE('_LISTU', 'Benutzer anzeigen');
DEFINE('_CREATEU', 'Benutzer erstellen');
DEFINE('_ROLEMAN', 'Benutzertypen verwalten');
DEFINE('_LISTR', 'Benutzertypen anzeigen');
DEFINE('_CREATER', 'Benutzertyp erstellen');
DEFINE('_LISTALL', 'Alle anzeigen');
DEFINE('_CREATE', 'Erstellen');
DEFINE('_VIEW', 'Anzeigen');
DEFINE('_CLEAR', 'Leeren');
DEFINE('_LISTGROUPS', 'Gruppen auflisten');
DEFINE('_CREATEGROUPS', 'Gruppe erstellen');
DEFINE('_VIEWGROUPS', 'Gruppe anzeigen');
DEFINE('_EDITGROUPS', 'Gruppe bearbeiten');
DEFINE('_DELETEGROUPS', 'Gruppe entfernen');
DEFINE('_CLEARGROUPS', 'Gruppe l&ouml;schen');
DEFINE('_CHNGPWD', 'Passwort &auml;ndern');
DEFINE('_DISPLAYU', 'Benutzer anzeigen');
//base_footer.php
DEFINE('_FOOTER', '( by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> und das <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Erstellt mit ACID von Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Benutzer bzw. Passwort nicht korrekt!<br>Bitte nochmal versuchen');
// base_main.php
DEFINE('_MOSTRECENT', 'Letzten ');
DEFINE('_MOSTFREQUENT', 'H&auml;ufigsten ');
DEFINE('_ALERTS', ' Warnungen:');
DEFINE('_ADDRESSES', ' Adressen:');
DEFINE('_ANYPROTO', 'alle Protokolle');
DEFINE('_UNI', 'unterschiedliche');
DEFINE('_LISTING', 'alle');
DEFINE('_TALERTS', 'Warnungen heute: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'Warnungen der letzten 24 Stunden: ');
DEFINE('_L72ALERTS', 'Warnungen der letzten 72 Stunden: ');
DEFINE('_UNIALERTS', ' verschiedene Warnungen');
DEFINE('_LSOURCEPORTS', 'Letzte Quellports: ');
DEFINE('_LDESTPORTS', 'Letzte Zielports: ');
DEFINE('_FREGSOURCEP', 'H&auml;ufigste Quellports: ');
DEFINE('_FREGDESTP', 'H&auml;ufigste Zielports: ');
DEFINE('_QUERIED', 'Stand:');
DEFINE('_DATABASE', 'Datenbank:');
DEFINE('_SCHEMAV', 'Schema Version:');
DEFINE('_TIMEWIN', 'Zeitraum:');
DEFINE('_NOALERTSDETECT', 'keine Warnungen');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Erfolgreich hinzugef&uuml;gt');
DEFINE('_NOPWDCHANGE', 'Es ist nicht m&ouml;glich Ihr Passwort zu &auml;ndern: ');
DEFINE('_NOUSER', 'Benutzer existiert nicht!');
DEFINE('_OLDPWD', 'Es wurde ein altes Passwort angegeben, das nicht zu unseren Daten passt!');
DEFINE('_PWDCANT', 'Es ist nicht m&ouml;glich Ihr Passwort zu &auml;ndern: ');
DEFINE('_PWDDONE', 'Ihr Passwort wurde ge&auml;ndert!');
DEFINE('_ROLEEXIST', 'Benutzertyp existiert bereits');
DEFINE('_ROLEIDEXIST', 'Benutzertyp ID existiert bereits');
DEFINE('_ROLEADDED', 'Benutzertyp erfolgreich hinzugef&uuml;gt');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Benutzertyp Verwaltung');
DEFINE('_FRMROLEID', 'Benutzertyp ID:');
DEFINE('_FRMROLENAME', 'Benutzertyp Name:');
DEFINE('_FRMROLEDESC', 'Beschreibung:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE Benutzerverwaltung');
DEFINE('_FRMFULLNAME', 'Voller Name:');
DEFINE('_FRMROLE', 'Benutzertyp:');
DEFINE('_FRMUID', 'Benutzer ID:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Administration');
DEFINE('_BASEADMINTEXT', 'Bitte w&auml;hlen Sie eine Option.');
//base_action.inc.php
DEFINE('_NOACTION', 'Es wurde keine Aktion f&uuml;r die Warnungen festgelegt.');
DEFINE('_INVALIDACT', ' ist eine ung&uuml;ltige Aktion');
DEFINE('_ERRNOAG', 'Konnte keine Warnungen hinzuf&uuml;gen, da keine Warngruppen festgelegt sind.');
DEFINE('_ERRNOEMAIL', 'Konnte Warnmeldungen nicht per eMail versenden, da keine eMail Adresse angegeben wurde.');
DEFINE('_ACTION', 'AKTION');
DEFINE('_CONTEXT', 'Kontext');
DEFINE('_ADDAGID', 'Zur Warngruppe hinzuf&uuml;gen (nach ID)');
DEFINE('_ADDAG', 'Neue Warngruppe hinzuf&uuml;gen');
DEFINE('_ADDAGNAME', 'Zur Warngruppe hinzuf&uuml;gen (nach Name)');
DEFINE('_CREATEAG', 'Warngruppe erstellen (nach Name)');
DEFINE('_CLEARAG', 'Aus Warngruppe l&ouml;schen');
DEFINE('_DELETEALERT', 'Warnung(en) l&ouml;schen');
DEFINE('_EMAILALERTSFULL', 'Warnung(en) per eMail versenden (vollst&auml;ndig) ');
DEFINE('_EMAILALERTSSUMM', 'Warnung(en) per eMail versenden (Zusammenfassung)');
DEFINE('_EMAILALERTSCSV', 'Warnung(en) per eMail versenden  (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Warnungen archivieren (kopieren)');
DEFINE('_ARCHIVEALERTSMOVE', 'Warnungen archivieren (verschieben)');
DEFINE('_IGNORED', 'Ignoriert ');
DEFINE('_DUPALERTS', ' doppelte Warnung(en)');
DEFINE('_ALERTSPARA', ' Warnung(en)');
DEFINE('_NOALERTSSELECT', 'Es wurden keine Warnungen gew&auml;hlt oder ');
DEFINE('_NOTSUCCESSFUL', 'war nicht erfolgreich');
DEFINE('_ERRUNKAGID', 'Es wurde eine unbekannte Warngruppen ID angegeben');
DEFINE('_ERRREMOVEFAIL', 'Konnte neue Warngruppe nicht entfernen');
DEFINE('_GENBASE', 'Erzeugt von BASE');
DEFINE('_ERRNOEMAILEXP', 'EXPORT FEHLER: Konnte die exportierten Warnungen nicht an folgende Adresse senden: ');
DEFINE('_ERRNOEMAILPHP', '&Uuml;berpr&uuml;fen Sie die Mail Konfiguration in PHP.');
DEFINE('_ERRDELALERT', 'Fehler beim l&ouml;schen der Warnung');
DEFINE('_ERRARCHIVE', 'ARCHIV FEHLER:');
DEFINE('_ERRMAILNORECP', 'MAIL FEHLER: Es wurde kein Empf&auml;nger festgelegt');
//base_cache.inc.php
DEFINE('_ADDED', 'Es wurde(n) ');
DEFINE('_HOSTNAMESDNS', ' Hostnamen zum IP DNS Cache hinzugef&uuml;gt');
DEFINE('_HOSTNAMESWHOIS', ' Hostnames zum Whois Cache hinzugef&uuml;gt');
DEFINE('_ERRCACHENULL', 'CACHE FEHLER: KEINE Ereignis Zeilen gefunden?');
DEFINE('_ERRCACHEERROR', 'EREIGNIS CACHING FEHLER:');
DEFINE('_ERRCACHEUPDATE', 'Konte den Ereigniscache nicht aktualisieren.');
DEFINE('_ALERTSCACHE', ' Warnung(en) hinzugef&uuml;gt');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Konnte die SQL Protokolldatei nicht &ouml;ffnen');
DEFINE('_ERRSQLCONNECT', 'Fehler bei der Datenbankverbindung :');
DEFINE('_ERRSQLCONNECTINFO', '<P>&Uuml;berpr&uuml;fen Sie die Datenbankverbindungs-Variablen in der <I>base_conf.php</I>
              <PRE>
               = $alert_dbname   : MySQL Datenbankname in der die Warnungen gespeichert werden
               = $alert_host     : Host auf dem die Datenbank gespeichert ist
               = $alert_port     : Port where the database is stored ?
               = $alert_user     : Benutzername in der Datenbank
               = $alert_password : Passwort zum Benutzernamen
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Fehler (p)beim Verbinden mit der Datenbank:');
DEFINE('_ERRSQLDB', 'DATENBANK FEHLER:');
DEFINE('_DBALCHECK', 'Suche nach der Datenbank Abstraktionsbibliothek in');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Fehler beim lader der Datenbank Abstraktionsbibliothek: </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P>&Uuml;berpr&uuml;fen Sie die Variable f&uuml; die Datenbank Abstraktionsbibliothek <CODE>$DBlib_path</CODE> in der Datei <CODE>base_conf.php</CODE>
            <P>
            Die zugrundeliegende Datenbankbibliothek, die z. Zt. verwendet wird ist ADODB, erh&auml;ltlich
            bei <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Ung&uuml;ltiger Datenbanktyp');
DEFINE('_ERRSQLDBTYPEINFO1', 'Die Variable <CODE>\$DBtype</CODE> in der Datei <CODE>base_conf.php</CODE> wurde auf eine nicht unterst&uuml;tzte Datenbank gesetzt: ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Nur die folgenden Datenbanken werden unterst&uuml;tzt: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE FATALER FEHLER:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Geladen in');
DEFINE('_SECONDS', 'Sekunden');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Kann Adresse nicht aufl&ouml;sen');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Abfrageergebnisse Kopfzeile');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'Signaturbezeichnung unbekannt');
DEFINE('_ERRSIGPROIRITYUNK', 'Signaturpriorit&auml;t unbekannt');
DEFINE('_UNCLASS', 'nicht zugeordnet');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'Daten kodiert als');
DEFINE('_NODENCODED', '(keine Daten&uuml;bersetzung, behandle Kriterium als DB native)');
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
DEFINE('_PHPERRORCSESSION', 'PHP FEHLER: Es wurde eine benutzerdefinierte (user) PHP Sitzung erkannt. Wie auch immer, BASE wurde nicht zum benutzen des \'custom handler\' eingerichtet.  Setzen Sie <CODE>use_user_session=1</CODE> in der <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP FEHLER: Es wurde eine benutzerdefinierte PHP Sitzung konfiguriert, aber der angegebene \'handler code\'  in der Zeile  <CODE>user_session_path</CODE> ist ung&uuml;ltig.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP FEHLER: Es wurde eine benutzerdefinierte PHP Sitzung konfiguriert, aber die Aufnahme dieses handlers wurde in BASE nicht eingestellt.  Wenn ein benutzerdefinierter Sitzungs handler gew&uuml;nscht wird, setzen Sie die <CODE>user_session_path</CODE> Variable in der <CODE>base_conf.php</CODE> Datei.');
DEFINE('_PHPSESSREG', 'Sitzung registriert');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'L&ouml;schen');
DEFINE('_FROMCRIT', 'aus  Kriterium');
DEFINE('_ERRCRITELEM', 'Ung&uuml;ltiges Kriterium.');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'G&uuml;ltige Abfrageliste');
DEFINE('_DISPLAYING', 'Anzeigen');
DEFINE('_DISPLAYINGTOTAL', 'Anzeigen der Warnungen %d-%d von insgesamt %s');
DEFINE('_NOALERTS', 'Keine Warnungen gefunden.');
DEFINE('_QUERYRESULTS', 'Abfrageergebnis');
DEFINE('_QUERYSTATE', 'Abfragestatus');
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Die angegebene Warngruppen Namens-Suche ist ung&uuml;ltig.  Bitte nochmal versuchen!');
DEFINE('_ERRAGNAMEEXIST', 'Die angegebene Warngruppe existiert nicht.');
DEFINE('_ERRAGIDSEARCH', 'Die angegebene Warngruppen ID-Suche ist ung&uuml;ltig.  Bitte nochmal versuchen!');
DEFINE('_ERRAGLOOKUP', 'Fehler beim Suchen einer Warngruppen ID');
DEFINE('_ERRAGINSERT', 'Fehler beim Einf&uuml;gen der neuen Warngruppe');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Warngruppen verwalten');
DEFINE('_ERRAGUPDATE', 'Fehler beim aktualisieren der Warngruppe');
DEFINE('_ERRAGPACKETLIST', 'Fehler beim l&ouml;schen der Paketliste aus der Warngruppe:');
DEFINE('_ERRAGDELETE', 'Fehler beim l&ouml;schen der Warngruppe');
DEFINE('_AGDELETE', 'Erfolgreich gel&ouml;scht');
DEFINE('_AGDELETEINFO', 'Information entfernt');
DEFINE('_ERRAGSEARCHINV', 'Das eingegebene Suchkriterium ist ung&uuml;ltig. Bitte nochmal versuchen!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Keine Warngruppe mit diesem Kriterium gefunden.');
DEFINE('_NOALERTGOUPS', 'Es existieren keine Warngruppen');
DEFINE('_NUMALERTS', '# Warnungen');
DEFINE('_ACTIONS', 'Optionen');
DEFINE('_NOTASSIGN', 'noch nicht zugeordnet');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Portscan Traffic');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Indexerzeugung nicht m&ouml;glich f&uuml;r');
DEFINE('_DBINDEXCREATE', 'Index erfolgreich erzeugt f&uuml;r');
DEFINE('_ERRSNORTVER', 'Es scheint eine &auml;ltere Version zu sein.  Nur \'Warnungs-Datenbanken\' erstellt durch Snort ab 1.7-beta0 werden unterst&uuml;tzt');
DEFINE('_ERRSNORTVER1', 'Die zugrundeliegende Datenbank');
DEFINE('_ERRSNORTVER2', 'scheint unvollst&auml;ndig/ung&uuml;ltig zu sein.');
DEFINE('_ERRDBSTRUCT1', 'Die Datenbankversion ist g&uuml;ltig, aber die BASE Datenbankstruktur');
DEFINE('_ERRDBSTRUCT2', 'fehlt. Benutzen Sie die <A HREF="base_db_setup.php">Setup Seite</A>, zum Konfigurieren und Optimieren der Datenbank.');
DEFINE('_ERRPHPERROR', 'PHP FEHLER');
DEFINE('_ERRPHPERROR1', 'Inkompatible Version');
DEFINE('_ERRVERSION', 'Version');
DEFINE('_ERRPHPERROR2', 'von PHP ist zu alt.  Bitte installieren Sie eine neuere Version ab 4.0.4');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP unvollst&auml;ndig</B>: <FONT>die MySQL Unterst&uuml;tzung, welche zum Auslesen der
               Warnungsdatenbank ben&ouml;tigt wird, wurde nicht in PHP aufgenommen.
               Bitte kompilieren Sie PHP mit der ben&ouml;tigten Bibliothek (<CODE>--with-mysql</CODE>)</FONT> erneut');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP unvollst&auml;ndig</B>: <FONT>die PostgreSQL Unterst&uuml;tzung, welche zum Auslesen der
               Warnungsdatenbank ben&ouml;tigt wird, wurde nicht in PHP aufgenommen.
               Bitte kompilieren Sie PHP mit der ben&ouml;tigten Bibliothek (<CODE>--with-pgsql</CODE>)</FONT> erneut');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP unvollst&auml;ndig</B>: <FONT>die MS SQL Server Unterst&uuml;tzung, welche zum Auslesen der
                   Warnungsdatenbank ben&ouml;tigt wird, wurde nicht in PHP aufgenommen.
                   Bitte kompilieren Sie PHP mit der ben&ouml;tigten Bibliothek (<CODE>--enable-mssql</CODE>)</FONT> erneut');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Diagrammtitel:');
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
DEFINE('_CHRTTYPEHOUR', 'Stunden und Anzahl Warnungen');
DEFINE('_CHRTTYPEDAY', 'Tage und Anzahl Warnungen');
DEFINE('_CHRTTYPEWEEK', 'Wochen und Anzahl Warnungen');
DEFINE('_CHRTTYPEMONTH', 'Monate und Anzahl Warnungen');
DEFINE('_CHRTTYPEYEAR', 'Jahre und Anzahl Warnungen');
DEFINE('_CHRTTYPESRCIP', 'Quell IP Adressen und Anzahl Warnungen');
DEFINE('_CHRTTYPEDSTIP', 'Ziel IP Adressen und Anzahl Warnungen');
DEFINE('_CHRTTYPEDSTUDP', 'Ziel UDP Ports und Anzahl Warnungen');
DEFINE('_CHRTTYPESRCUDP', 'Quell UDP Ports und Anzahl Warnungen');
DEFINE('_CHRTTYPEDSTPORT', 'Ziel TCP Ports und Anzahl Warnungen');
DEFINE('_CHRTTYPESRCPORT', 'Quell TCP Ports und Anzahl Warnungen');
DEFINE('_CHRTTYPESIG', 'Signaturen und Anzahl Warnungen');
DEFINE('_CHRTTYPESENSOR', 'Sensoren und Anzahl Warnungen');
DEFINE('_CHRTBEGIN', 'Diagrammanfang:');
DEFINE('_CHRTEND', 'Diagrammende:');
DEFINE('_CHRTDS', 'Datenquelle:');
DEFINE('_CHRTX', 'X Achse');
DEFINE('_CHRTY', 'Y Achse');
DEFINE('_CHRTMINTRESH', 'Mindestwert');
DEFINE('_CHRTROTAXISLABEL', 'Achsenbezeichnungen drehen (90 Grad)');
DEFINE('_CHRTSHOWX', 'Linieneinteilungen der X-Achse anzeigen');
DEFINE('_CHRTDISPLABELX', 'Alle Stufen der X-Achse anzeigen');
DEFINE('_CHRTDATAPOINTS', 'Datenpunkte');
DEFINE('_CHRTYLOG', 'Y-Achse logarhythmisch');
DEFINE('_CHRTYGRID', 'Linieneinteilungen der Y-Achse anzeigen');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE Diagramm');
DEFINE('_ERRCHRTNOTYPE', 'Es wurde kein Diagrammtyp festgelegt');
DEFINE('_ERRNOAGSPEC', 'Es wurde keine Warngruppe festgelegt.  Benutze alle Warngruppen.');
DEFINE('_CHRTDATAIMPORT', 'Beginne mit dem Import der Daten');
DEFINE('_CHRTTIMEVNUMBER', 'Zeit und Anzahl Warnungen');
DEFINE('_CHRTTIME', 'Zeit');
DEFINE('_CHRTALERTOCCUR', 'Warnungsvorkommen');
DEFINE('_CHRTSIPNUMBER', 'Quell IP und Anzahl Warnungen');
DEFINE('_CHRTSIP', 'Quell IP Adresse');
DEFINE('_CHRTDIPALERTS', 'Ziel IP und Anzahl Warnungen');
DEFINE('_CHRTDIP', 'Ziel IP Adresse');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP Port (Ziel) und Anzahl Warnungen');
DEFINE('_CHRTDUDPPORT', 'Ziel UDP Port');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP Port (Quelle) und Anzahl Warnungen');
DEFINE('_CHRTSUDPPORT', 'Quell UDP Port');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP Port (Ziel) und Anzahl Warnungen');
DEFINE('_CHRTPORTDEST', 'Ziel TCP Port');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP Port (Quelle) und Anzahl Warnungen');
DEFINE('_CHRTPORTSRC', 'Quell TCP Port');
DEFINE('_CHRTSIGNUMBER', 'Signatur und Anzahl Warnungen');
DEFINE('_CHRTCLASS', 'Klassifizierung');
DEFINE('_CHRTSENSORNUMBER', 'Sensor und Anzahl Warnungen');
DEFINE('_CHRTHANDLEPERIOD', 'Behandlungszeitraum, wenn n&ouml;tig');
DEFINE('_CHRTDUMP', 'Ausgabe der Daten ... (schreibe nur jeden');
DEFINE('_CHRTDRAW', 'Ausgabe der Grafik');
DEFINE('_ERRCHRTNODATAPOINTS', 'Es gibt keine Datenpunkte f&uuml;r die Grafik');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Wartung');
DEFINE('_MNTPHP', 'PHP Build:');
DEFINE('_MNTCLIENT', 'CLIENT:');
DEFINE('_MNTSERVER', 'SERVER:');
DEFINE('_MNTSERVERHW', 'SERVER HW:');
DEFINE('_MNTPHPVER', 'PHP VERSION:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP Logging level:');
DEFINE('_MNTPHPMODS', 'Geladene Module:');
DEFINE('_MNTDBTYPE', 'DB Typ:');
DEFINE('_MNTDBALV', 'Version der DB Abstraktionsbibliothek:');
DEFINE('_MNTDBALERTNAME', 'DB Name der Warnungen:');
DEFINE('_MNTDBARCHNAME', 'DB Name des Archivs:');
DEFINE('_MNTAIC', 'Warnungen im Cache:');
DEFINE('_MNTAICTE', 'Anzahl Ereignisse:');
DEFINE('_MNTAICCE', 'Gecachte Ereignisse:');
DEFINE('_MNTIPAC', 'IP Adress-Cache');
DEFINE('_MNTIPACUSIP', 'Unterschiedliche Quell IPs:');
DEFINE('_MNTIPACDNSC', 'DNS gepuffert:');
DEFINE('_MNTIPACWC', 'Whois gepuffert:');
DEFINE('_MNTIPACUDIP', 'Unterschiedliche Ziel IPs:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Ung&uuml;ltiges (sid,cid) Paar');
DEFINE('_QAALERTDELET', 'Warnung gel&ouml;scht');
DEFINE('_QATRIGGERSIG', 'Ausl&ouml;sende Signatur');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'Signatur');
DEFINE('_QCIPADDR', 'IP Adresse');
DEFINE('_QCIPFIELDS', 'IP Felder');
DEFINE('_QCTCPPORTS', 'TCP Ports');
DEFINE('_QCTCPFLAGS', 'TCP Flags');
DEFINE('_QCTCPFIELD', 'TCP Felder');
DEFINE('_QCUDPPORTS', 'UDP Ports');
DEFINE('_QCUDPFIELDS', 'UDP Felder');
DEFINE('_QCICMPFIELDS', 'ICMP Felder');
DEFINE('_QCDATA', 'Daten');
DEFINE('_QCERRCRITWARN', 'Kriterium Warnung:');
DEFINE('_QCERRVALUE', 'Ein Wert von');
DEFINE('_QCERRFIELD', 'Ein Feld von');
DEFINE('_QCERROPER', 'Ein operator von');
DEFINE('_QCERRDATETIME', 'Ein Datums-/Zeit-Angabe von');
DEFINE('_QCERRPAYLOAD', 'Eine Payload Angabe von');
DEFINE('_QCERRIP', 'Eine IP Adresse von');
DEFINE('_QCERRIPTYPE', 'Eine IP Adresse vom Typ');
DEFINE('_QCERRSPECFIELD', ' wurde f&uuml;r ein Protokoll-Feld angegeben, aber das betreffende Feld wurde nicht festgelegt.');
DEFINE('_QCERRSPECVALUE', 'wurde f&uuml;r die Suche festgelegt, aber kein Wert wonach gesucht werden soll.');
DEFINE('_QCERRBOOLEAN', 'Bitte verwenden Sie einen boolschen Operator (z. B.  "AND", "OR") bei mehreren Protokollen.');
DEFINE('_QCERRDATEVALUE', 'gibt an, dass nach einer Datums-/Zeitangabe gesucht werden soll, aber es wurde kein Wert festgelegt.');
DEFINE('_QCERRINVHOUR', '(Ung&uuml;ltige Stunde) Es existiert keine Datumsangabe mit dieser Uhrzeit.');
DEFINE('_QCERRDATECRIT', 'gibt an, dass nach einer Datums-/Zeitangabe gesucht werden soll, aber es wurde kein Wert festgelegt.');
DEFINE('_QCERROPERSELECT', 'wurde eingegeben, aber es wurde kein Operator gew&auml;hlt.');
DEFINE('_QCERRDATEBOOL', 'Mehrfache Datums-/Zeitangaben wurden ohne boolsche Operatoren (z. B. AND, OR) festgelegt.');
DEFINE('_QCERRPAYCRITOPER', 'wurde f&uuml;r ein Payload-Feld angegeben, aber ein Operator (z. B. has, has not) wurde nicht angegeben.');
DEFINE('_QCERRPAYCRITVALUE', 'gibt an, dass nach Payloads gesucht werden soll, es wurde aber kein Wert festgelegt.');
DEFINE('_QCERRPAYBOOL', 'Mehrfache Payload-Angaben wurden gemacht, es fehlt aber dazwischen ein boolscher Operator (z. B. AND, OR), bitte festlegen.');
DEFINE('_QCMETACRIT', 'Meta Angabe');
DEFINE('_QCIPCRIT', 'IP Angabe');
DEFINE('_QCPAYCRIT', 'Payload Angabe');
DEFINE('_QCTCPCRIT', 'TCP Angabe');
DEFINE('_QCUDPCRIT', 'UDP Angabe');
DEFINE('_QCICMPCRIT', 'ICMP Angabe');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Ung&uuml;ltige IP Adressangabe');
DEFINE('_QCERRCRITADDRESSTYPE', 'wurde angegeben, aber der Adresstyp (z. B. Quelle, Ziel) wurde nicht bestimmt.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'es wurde angegeben, dass nach einer IP-Adresse gesucht werden soll, aber wie die Adresse lautet, nicht.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'wurde gew&auml;hlt (als #');
DEFINE('_QCERRCRITIPIPBOOL', 'Bitte verwenden Sie einen boolschen Operator (z. B.  "AND", "OR") bei mehrfachen IP Adressangaben.');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Sortierfolge');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMTIMEA', 'Zeitstempel (aufsteigend)');
DEFINE('_QFRMTIMED', 'Zeitstempel (absteigend)');
DEFINE('_QFRMSIG', 'Signatur');
DEFINE('_QFRMSIP', 'Quell IP');
DEFINE('_QFRMDIP', 'Ziel IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Zusammenfassende Statistiken');
DEFINE('_QSCTIMEPROF', 'Zeitprofil');
DEFINE('_QSCOFALERTS', 'Warnungen');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Liste der Warnungen');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategorien: ');
DEFINE('_SCSENSORTOTAL', 'Sensoren/insgesamt:');
DEFINE('_SCTOTALNUMALERTS', 'Warnungen insgesamt:');
DEFINE('_SCSRCIP', 'Quell IP Adressen:');
DEFINE('_SCDSTIP', 'Ziel IP Adressen:');
DEFINE('_SCUNILINKS', 'Unterschiedliche IP Verbindungen:');
DEFINE('_SCSRCPORTS', 'Quellports: ');
DEFINE('_SCDSTPORTS', 'Zielports: ');
DEFINE('_SCSENSORS', 'Sensoren');
DEFINE('_SCCLASS', 'Klassifikationen');
DEFINE('_SCUNIADDRESS', 'Unterschiedliche Adressen: ');
DEFINE('_SCSOURCE', 'Quelle');
DEFINE('_SCDEST', 'Ziel');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN EREIGNIS FEHLER: ');
DEFINE('_PSEVENTERRNOFILE', 'Es wurde keine Datei in der \$portscan_file Variable festgelegt.');
DEFINE('_PSEVENTERROPENFILE', 'Kann die "Portscan Ereignis-Datei" nicht &ouml;ffnen');
DEFINE('_PSDATETIME', 'Datum/Zeit');
DEFINE('_PSSRCIP', 'Quell IP');
DEFINE('_PSDSTIP', 'Ziel IP');
DEFINE('_PSSRCPORT', 'Quellport');
DEFINE('_PSDSTPORT', 'Zielport');
DEFINE('_PSTCPFLAGS', 'TCP Flags');
DEFINE('_PSTOTALOCC', 'Anzahl<BR> Vorkommen');
DEFINE('_PSNUMSENSORS', 'Anzahl Sensoren');
DEFINE('_PSFIRSTOCC', 'Erstes<BR> Vorkommen');
DEFINE('_PSLASTOCC', 'Letztes<BR> Vorkommen');
DEFINE('_PSUNIALERTS', 'Unterschiedliche Warnungen');
DEFINE('_PSPORTSCANEVE', 'Portscan Events');
DEFINE('_PSREGWHOIS', 'Verzeichnissuche (whois) in');
DEFINE('_PSNODNS', 'keine DNS Aufl&ouml;sung erfolgt');
DEFINE('_PSNUMSENSORSBR', 'Anzahl <BR>Sensoren');
DEFINE('_PSOCCASSRC', 'Vorkommen <BR>als Quelle');
DEFINE('_PSOCCASDST', 'Vorkommen <BR>as Ziel');
DEFINE('_PSWHOISINFO', 'Whois Information');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP Verbindungen');
DEFINE('_SIPLSOURCEFGDN', 'Quell FQDN');
DEFINE('_SIPLDESTFGDN', 'Ziel FQDN');
DEFINE('_SIPLDIRECTION', 'Richtung');
DEFINE('_SIPLPROTO', 'Protokoll');
DEFINE('_SIPLUNIDSTPORTS', 'Unterschiedliche Zielports');
DEFINE('_SIPLUNIEVENTS', 'Unterschiedliche Ereignisse');
DEFINE('_SIPLTOTALEVENTS', 'Ereignisse insgesamt');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unterschiedliche(r)');
DEFINE('_DSTPS', 'Zielport(s)');
DEFINE('_SRCPS', 'Quellport(s)');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Sensorenliste');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Warnungsabfrage');
DEFINE('_BSTTIMECRIT', 'Zeitangabe');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Bitte geben Sie an, in welche Einheiten unterteilt werden soll!</B>  W&auml;hlen Sie "Stunden", "Tage", oder "Monate".</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>Bitte geben Sie an, wie die Datumsangabe behandelt werden soll!</B>  W&auml;hlen Sie "am", fr ein bestimmtes Datum, oder "zwischen" um einen Zeitraum anzugeben.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Es wurde kein Jahr angegeben!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Es wurde kein Monat angegeben!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Es wurde kein Tag angegeben!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Unterschiedliche Quelladresse(n)');
DEFINE('_SUASRCIP', 'Quell IP Adresse');
DEFINE('_SUAERRCRITADDUNK', 'KRITERIUMFEHLER: unbekannter Adresstyp -- verwende Ziel Adresse');
DEFINE('_UNIDADD', 'Unterschiedliche Zieladresse(n)');
DEFINE('_SUADSTIP', 'Ziel IP Adresse');
DEFINE('_SUAUNIALERTS', 'Unterschiedliche&nbsp;Warnungen');
DEFINE('_SUASRCADD', 'Quell&nbsp;Adr.');
DEFINE('_SUADSTADD', 'Ziel&nbsp;Adr.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE Benutzereinstellungen');
DEFINE('_BASEUSERERRPWD', 'Ihr Passwort darf nicht leer sein bzw. die beiden Passw&ouml;rter stimmen nicht &uuml;berein!');
DEFINE('_BASEUSEROLDPWD', 'Altes Passwort:');
DEFINE('_BASEUSERNEWPWD', 'Neues Passwort:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Neues Passwort (Best&auml;tigung):');
DEFINE('_LOGOUT', 'Logout');
?>
