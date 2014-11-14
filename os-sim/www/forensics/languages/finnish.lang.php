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
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID); //#
DEFINE('_FRMLOGIN', 'Login:'); //#
DEFINE('_FRMPWD', 'Salasana:');
DEFINE('_SOURCE', 'L‰hde');
DEFINE('_SOURCENAME', 'L‰hteen nimi');
DEFINE('_DEST', 'Kohde');
DEFINE('_DESTNAME', 'Kohteen Nimi');
DEFINE('_SORD', 'L‰hteen nimi');
DEFINE('_EDIT', 'Muokkaa');
DEFINE('_DELETE', 'Poista');
DEFINE('_ID', 'ID'); //#
DEFINE('_NAME', 'Nimi');
DEFINE('_INTERFACE', 'K‰yttˆliittym‰');
DEFINE('_FILTER', 'Suodatin');
DEFINE('_DESC', 'Kuvaus');
DEFINE('_LOGIN', 'Login'); //#
DEFINE('_ROLEID', 'Role ID'); //#
DEFINE('_ENABLED', 'Toiminnassa');
DEFINE('_SUCCESS', 'Onnistunut');
DEFINE('_SENSOR', 'Sensori'); //#
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Signature'); //#
DEFINE('_TIMESTAMP', 'Aikaleima');
DEFINE('_NBSOURCEADDR', 'L‰hde&nbsp;Osoite');
DEFINE('_NBDESTADDR', 'Kohde&nbsp;Osoite');
DEFINE('_NBLAYER4', 'Layer&nbsp;4&nbsp;Proto'); //#
DEFINE('_PRIORITY', 'T‰rkeysj‰rjetys');
DEFINE('_EVENTTYPE', 'tapahtumatyyppi');
DEFINE('_JANUARY', 'tammikuu');
DEFINE('_FEBRUARY', 'helmikuu');
DEFINE('_MARCH', 'maaliskuu');
DEFINE('_APRIL', 'huhtikuu');
DEFINE('_MAY', 'toukokuu');
DEFINE('_JUNE', 'kes‰kuu');
DEFINE('_JULY', 'hein‰kuu');
DEFINE('_AUGUST', 'elokuu');
DEFINE('_SEPTEMBER', 'syyskuu');
DEFINE('_OCTOBER', 'lokakuu');
DEFINE('_NOVEMBER', 'marraskuu');
DEFINE('_DECEMBER', 'joulukuu');
DEFINE('_LAST', 'Viimeinen');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'H‰lytykset');
DEFINE('_ADDRESS', 'Osoite');
DEFINE('_UNKNOWN', 'tuntematon');
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
DEFINE('_HOME', 'Koti');
DEFINE('_SEARCH', 'Etsi');
DEFINE('_AGMAINT', 'H‰lytys-ryhm‰:n Yll‰pito');
DEFINE('_USERPREF', 'K‰ytt‰j‰n asetukset');
DEFINE('_CACHE', 'V‰limuisti & Status');
DEFINE('_ADMIN', 'Hallinta');
DEFINE('_GALERTD', 'Graph H‰lytys Data'); //#
DEFINE('_GALERTDT', 'Graph H‰lytys Detection Aika'); //#
DEFINE('_USERMAN', 'K‰ytt‰jien Hallinta');
DEFINE('_LISTU', 'Listaa k‰ytt‰j‰t');
DEFINE('_CREATEU', 'Luo k‰ytt‰j‰t');
DEFINE('_ROLEMAN', 'Role Hallinta'); //#
DEFINE('_LISTR', 'Listaa roles'); //#
DEFINE('_CREATER', 'Luo role'); //#
DEFINE('_LISTALL', 'Listaa Kaikki');
DEFINE('_CREATE', 'Luo');
DEFINE('_VIEW', 'Katsele');
DEFINE('_CLEAR', 'Tyhj‰‰');
DEFINE('_LISTGROUPS', 'Listaa Ryhm‰t');
DEFINE('_CREATEGROUPS', 'Luo Ryhm‰t');
DEFINE('_VIEWGROUPS', 'N‰yt‰ Ryhm‰t');
DEFINE('_EDITGROUPS', 'Muuta Ryhm‰t');
DEFINE('_DELETEGROUPS', 'Poista Ryhm‰t');
DEFINE('_CLEARGROUPS', 'Clear Ryhm‰t'); //#
DEFINE('_CHNGPWD', 'Vaihda salasana');
DEFINE('_DISPLAYU', 'N‰yt‰ K‰ytt‰j‰');
//base_footer.php
DEFINE('_FOOTER', '( <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnsonilta</A> ja <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Projektin Tiimilt‰</A><BR>Rakennettu ACID:n(Roman Danyliw) p‰‰lle )'); //#
//index.php --Log in Page
DEFINE('_LOGINERROR', 'K‰ytt‰j‰‰ ei ole tai antamasi salasana on v‰‰r‰!<br>Yrit‰ uudelleen');
// base_main.php
DEFINE('_MOSTRECENT', 'Uusin ');
DEFINE('_MOSTFREQUENT', 'Tiheiten Esiintyv‰ ');
DEFINE('_ALERTS', ' H‰lytykset:');
DEFINE('_ADDRESSES', ' Osoitteet');
DEFINE('_ANYPROTO', 'mik‰ tahansa protokolla');
DEFINE('_UNI', 'uniikki');
DEFINE('_LISTING', 'listaus');
DEFINE('_TALERTS', 'T‰m‰np‰iv‰iset h‰lytykset: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'Viimeisen 24 Tunnin h‰lytykset: ');
DEFINE('_L72ALERTS', 'Viimeisen 72 Tunnin h‰lytykset: ');
DEFINE('_UNIALERTS', ' Uniikit h‰lytykset');
DEFINE('_LSOURCEPORTS', 'Viimeisimm‰t L‰hde-Portit: ');
DEFINE('_LDESTPORTS', 'Viimeisimm‰t Kohde-Portit: ');
DEFINE('_FREGSOURCEP', 'Tiheiten Esiintyv‰t L‰hde-Portit: ');
DEFINE('_FREGDESTP', 'Tiheiten Esiintyv‰t Kohde-Portit: ');
DEFINE('_QUERIED', 'Queried on'); //#
DEFINE('_DATABASE', 'Tietokanta:');
DEFINE('_SCHEMAV', 'Scheman Versio:'); //#
DEFINE('_TIMEWIN', 'Aika-ikkuna:');
DEFINE('_NOALERTSDETECT', 'h‰lytyksi‰ ole havaittu');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Lis‰tty Onnistuneesti');
DEFINE('_NOPWDCHANGE', 'Salasananasi vaihtaminen ei onnistu: ');
DEFINE('_NOUSER', 'K‰ytt‰j‰‰ ei ole!');
DEFINE('_OLDPWD', 'Annettua vanhaa salasanaa ei tunnisteta!');
DEFINE('_PWDCANT', 'Salasanasi vaihtaminen ei onnistu: ');
DEFINE('_PWDDONE', 'Salasanasi on vaihdettu!');
DEFINE('_ROLEEXIST', 'Role On Jo Olemassa'); //#
DEFINE('_ROLEIDEXIST', 'Role ID On Jo Olemassa'); //#
DEFINE('_ROLEADDED', 'Role lis‰tty'); //#
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Role Administration'); //#
DEFINE('_FRMROLEID', 'Role ID:'); //#
DEFINE('_FRMROLENAME', 'Role Nimi:'); //#
DEFINE('_FRMROLEDESC', 'Kuvaus:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE K‰ytt‰j‰ Hallinta');
DEFINE('_FRMFULLNAME', 'Koko nimi:');
DEFINE('_FRMROLE', 'Role:'); //#
DEFINE('_FRMUID', 'K‰ytt‰j‰ ID:'); //#
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Hallinta');
DEFINE('_BASEADMINTEXT', 'Valitse yksi vaihtoehto vasemmalta.');
//base_action.inc.php
DEFINE('_NOACTION', 'Yht‰‰n action ei m‰‰ritelty on the alerts'); //#
DEFINE('_INVALIDACT', ' on laiton(invalid) action'); //#
DEFINE('_ERRNOAG', 'H‰lytyksen lis‰‰minen ei onnistunut koska AG:a ei m‰‰ritelty ');
DEFINE('_ERRNOEMAIL', 'H‰lytysten mailaaminen ei onnistunut koska email-osoitetta ei ole m‰‰ritelty');
DEFINE('_ACTION', 'ACTION'); //#
DEFINE('_CONTEXT', 'konteksti');
DEFINE('_ADDAGID', 'Lis‰‰ AG:iin ( ID:ll‰'); //#
DEFINE('_ADDAG', 'Lis‰‰ uusi AG'); //#
DEFINE('_ADDAGNAME', 'Lis‰‰ AG:iin (Nimell‰');
DEFINE('_CREATEAG', 'Luo AG (Nimell‰');
DEFINE('_CLEARAG', 'Posta AG:sta');
DEFINE('_DELETEALERT', 'Poista h‰lytykset');
DEFINE('_EMAILALERTSFULL', 'Email h‰lytykset (t‰ysi)');
DEFINE('_EMAILALERTSSUMM', 'Email h‰lytykset (yhteenveto)');
DEFINE('_EMAILALERTSCSV', 'Email h‰lytykset (csv)'); //#
DEFINE('_ARCHIVEALERTSCOPY', 'Arkistoi h‰lytykset (kopioi)');
DEFINE('_ARCHIVEALERTSMOVE', 'Arkistoi h‰lytykset (siirrÔøΩ');
DEFINE('_IGNORED', 'J‰tetty Huomiotta ');
DEFINE('_DUPALERTS', ' useasti esiintyv‰t h‰lytykset');
DEFINE('_ALERTSPARA', ' h‰lytykset');
DEFINE('_NOALERTSSELECT', 'Yht‰‰n h‰lytyst‰ valittu tai');
DEFINE('_NOTSUCCESSFUL', 'ei onnistunut');
DEFINE('_ERRUNKAGID', 'Tuntematon AG ID annettu (AG:a ei luultavasti ole olemassa)'); //#
DEFINE('_ERRREMOVEFAIL', 'Uuden AG:n poistaminen ei onnistunut');
DEFINE('_GENBASE', 'BASE:n generoima');
DEFINE('_ERRNOEMAILEXP', 'EXPORT ERROR: Exported h‰lytykset l‰hett‰minen to'); //#
DEFINE('_ERRNOEMAILPHP', 'Tarkista PHP:n s‰hkˆpostiasetukset.');
DEFINE('_ERRDELALERT', 'Error Poistettaessa H‰lytyst‰'); //#
DEFINE('_ERRARCHIVE', 'Arkisto error:'); //#
DEFINE('_ERRMAILNORECP', 'MAIL ERROR: Vastaanottajaa ei m‰‰ritelty'); //#
//base_cache.inc.php
DEFINE('_ADDED', 'Lis‰tty ');
DEFINE('_HOSTNAMESDNS', ' hostnames to the IP-DNS-v‰limuistiin'); //#
DEFINE('_HOSTNAMESWHOIS', ' hostnames to the Whois cache'); //#
DEFINE('_ERRCACHENULL', 'Caching ERROR: NULL event row found?'); //#
DEFINE('_ERRCACHEERROR', 'EVENT CACHING ERROR:'); //#
DEFINE('_ERRCACHEUPDATE', 'Tapahtumav‰limuistin p‰ivitt‰minen ei onnistunut');
DEFINE('_ALERTSCACHE', ' h‰lytykset H‰lytysv‰limuistiin');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'SQL trace tidoston avaminen ei onnistu'); //#
DEFINE('_ERRSQLCONNECT', 'Virhe yhdistettaess‰ tietokantaan :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Tarkista DB connection variables(tietokantayhteyden muuttujat) tiedostosta <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : MySQL tietokannan nimi johon h‰lytykset on tallennettu 
               = $alert_host     : is‰nt‰ johon tietokanta on tallennettu
               = $alert_port     : portti johon tietokanta on tallennettu
               = $alert_user     : k‰ytt‰j‰nimi tietokantaan
               = $alert_password : salasana k‰ytt‰j‰nimelle
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Error ?(p)? yhditett‰ess‰ tietokantaan :'); //#
DEFINE('_ERRSQLDB', 'Tietokanta ERROR:'); //#
DEFINE('_DBALCHECK', 'Tarkastaa tietokannan-abstraktio-kirjastoa in');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Virhe Ladattaessa tietokannan-abstraktio-kirjastoa : </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Tarkista tietokannan-abstraktio-kirjasto muuttuja <CODE>$DBlib_path</CODE> tiedostossa <CODE>base_conf.php</CODE>
            <P>
             T‰ll‰ hetkell‰ k‰ytˆssa oleva tietokanta-kirjasto on nimelt‰‰n ADODB, jonka voi ladata osoitteesta
             <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'M‰‰ritelty tietokannan tyyppi on virheellinen');
DEFINE('_ERRSQLDBTYPEINFO1', 'Muuttuja <CODE>\$DBtype</CODE> tiedostossa <CODE>base_conf.php</CODE> oli asetettu mainittuun virheelliseen tietokannan tyyppiin ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Vain seuraavat tietokannat ovat tuettuja: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE FATAL ERROR:'); //#
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Ladattu');
DEFINE('_SECONDS', 'sekunnissa');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Unable to resolve osoite'); //#
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Query Results Output Header'); //#
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName unknown'); //#
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority unknown'); //#
DEFINE('_UNCLASS', 'unclassified'); //#
//base_state_citems.inc.php
DEFINE('_DENCODED', 'data encoded as'); //#
DEFINE('_NODENCODED', '(ei datan konversiota, assuming criteria tietokannassa native koodaus)'); //#
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
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session on havaittu. Kuitenkaan, BASE:‰ ei ole asetettu to explicitly use this custom handler. Aseta <CODE>use_user_session=1</CODE> tiedostossa <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session handler on konfiguroitu, mutta annettu handler m‰‰ritelty tiedotossa <CODE>user_session_path</CODE> on laiton(invalid).'); //#
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler on konfiguroitu, mutta the implementation of this handler ei ole m‰‰ritelty BASE:ss‰.  If a custom session handler on toivottavaa, aseta <CODE>user_session_path</CODE> muuttuja tiedostossa <CODE>base_conf.php</CODE>.'); //#
DEFINE('_PHPSESSREG', 'Session Registered'); //#
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Poistamassa');
DEFINE('_FROMCRIT', 'from criteria'); //#
DEFINE('_ERRCRITELEM', 'Invalid criteria element'); //#
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List'); //#
DEFINE('_DISPLAYING', 'Displaying'); //#
DEFINE('_DISPLAYINGTOTAL', 'Displaying alerts %d-%d of %s total'); //#
DEFINE('_NOALERTS', 'Ei havaittuja h‰lytyksi‰.');
DEFINE('_QUERYRESULTS', 'Query Results'); //#
DEFINE('_QUERYSTATE', 'Query State'); //#
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Annettu AG nimi-etsint‰ on laiton(invalid).  Yrit‰ uudelleen!');
DEFINE('_ERRAGNAMEEXIST', 'Annettu AG:a ei ole olemassa.');
DEFINE('_ERRAGIDSEARCH', 'Annettu AG ID etsint‰ on laiton(invalid).  Yrit‰ uudelleen!');
DEFINE('_ERRAGLOOKUP', 'Error looking up an AG ID:ta'); //#
DEFINE('_ERRAGINSERT', 'Error Asetettaessa uutta AG:a'); //#
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'H‰lytys-ryhm‰ (Alert Group - AG) Yll‰pito'); //#
DEFINE('_ERRAGUPDATE', 'Error p‰ivitett‰ess‰ AG:a'); //#
DEFINE('_ERRAGPACKETLIST', 'Error poistettaessa AG:n paketti-listaa:'); //#
DEFINE('_ERRAGDELETE', 'Error poistettaessa AG:a'); //#
DEFINE('_AGDELETE', 'POISTETTU Onnistuneesti');
DEFINE('_AGDELETEINFO', 'tieto poistettu');
DEFINE('_ERRAGSEARCHINV', 'Annetty hakukriteeri ei ole laillinen(valid).  Yrit‰ uudelleen!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Yht‰‰n AG:a ei lˆydetty tuolla kriteerill‰.');
DEFINE('_NOALERTGOUPS', 'Yht‰‰n AG:a ei lˆydy');
DEFINE('_NUMALERTS', '# H‰lytykset');
DEFINE('_ACTIONS', 'Actions'); //#
DEFINE('_NOTASSIGN', 'not assigned yet'); //#
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Portscan Traffic'); //#
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Indexin luominen ei onnistu for'); //#
DEFINE('_DBINDEXCREATE', 'Onnistuneesti luotu INDEXI for'); //#
DEFINE('_ERRSNORTVER', 'Se saattaa olla vanhempaa versiota. Vain Snort 1.7-beta:lla tai uudemalla luodut tietokannat ovat tuettuja ');
DEFINE('_ERRSNORTVER1', 'K‰ytˆss‰ oleva tietokanta');
DEFINE('_ERRSNORTVER2', 'n‰ytt‰‰ olevan ep‰t‰ydellinen/laiton(invalid)');
DEFINE('_ERRDBSTRUCT1', 'Tietokannan versio on k‰yp‰, mutta BASE-tietokannan rakenne');
DEFINE('_ERRDBSTRUCT2', 'ei ole saatavilla. K‰yt‰ <A HREF="base_db_setup.php">Asetus Sivua</A> konfiguroidaksesi ja optimoidaksesi tietokannan.');
DEFINE('_ERRPHPERROR', 'PHP ERROR'); //#
DEFINE('_ERRPHPERROR1', 'Yhteensopimaton versio');
DEFINE('_ERRVERSION', 'Versio');
DEFINE('_ERRPHPERROR2', '(PHP) on liian vanha. P‰ivit‰ PHP:n versioon 4.0.4 tai uudempaan');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP k‰‰nnˆs(build) ep‰t‰ydellinen</B>: <FONT>the prerequisite MySQL:n tuki joka vaaditaan h‰lytystietokannan lukemiseen ei ole k‰‰nnetty PHP:n sis‰‰n.
                   K‰‰nn‰ PHP uudelleen tarvittavien kirjastojen kanssa (<CODE>--with-mysql</CODE>)</FONT>'); //#
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP k‰‰nnˆs(build) ep‰t‰ydellinen</B>: <FONT>the prerequisite PostgreSQL:n tuki joka vaaditaan h‰lytystietokannan lukemiseen ei ole k‰‰nnetty PHP:n sis‰‰n.
                   K‰‰nn‰ PHP uudelleen tarvittavien kirjastojen kanssa (<CODE>--with-pgsql</CODE>)</FONT>'); //#
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP k‰‰nnˆs(build) ep‰t‰ydellinen</B>: <FONT>the prerequisite MS SQL Serverin tuki joka vaaditaan h‰lytystietokannan lukemiseen ei ole k‰‰nnetty PHP:n sis‰‰n.
                   K‰‰nn‰ PHP uudelleen tarvittavien kirjastojen kanssa (<CODE>--enable-mssql</CODE>)</FONT>'); //#
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Kuvaajan Otsikko:');
DEFINE('_CHRTTYPEHOUR', 'Aika (tunti) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEDAY', 'Aika (p‰iv‰) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEWEEK', 'Aika (viikko) vs. H‰lytysten M‰‰r‰');
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
DEFINE('_CHARTMONTH', '{m√™s}'); //NEW
DEFINE('_GRAPHALERTS', 'Graph Alerts'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y AXIS CONTROLS'); //NEW
DEFINE('_CHRTTYPEMONTH', 'Aika (kuukausi) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEYEAR', 'Aika (vuosi) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPESRCIP', 'L‰hde IP osoite vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEDSTIP', 'Kohde IP osoite vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEDSTUDP', 'Kohde UDP Portti vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPESRCUDP', 'L‰hde UDP Portti vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPEDSTPORT', 'Kohde TCP Portti vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPESRCPORT', 'L‰hde TCP Portti vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTYPESIG', 'Sig. Classification vs. H‰lytysten M‰‰r‰'); //#
DEFINE('_CHRTTYPESENSOR', 'Sensori vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTBEGIN', 'Kuvaaja Alkaa(Chart Begi):');
DEFINE('_CHRTEND', 'Kuvaaja Loppuu(Chart End):');
DEFINE('_CHRTDS', 'Data L‰hde:');
DEFINE('_CHRTX', 'X-akseli');
DEFINE('_CHRTY', 'Y-akseli');
DEFINE('_CHRTMINTRESH', 'Minimum Threshold Value'); //#
DEFINE('_CHRTROTAXISLABEL', 'Pyˆrit‰ Akseli Labels (90 astetta)'); //#
DEFINE('_CHRTSHOWX', 'N‰yt‰ X-akseli grid-lines'); //#
DEFINE('_CHRTDISPLABELX', 'Display X-akseli label every'); //#
DEFINE('_CHRTDATAPOINTS', 'data points'); //#
DEFINE('_CHRTYLOG', 'Y-akseli logarithmic'); //#
DEFINE('_CHRTYGRID', 'N‰yt‰ Y-akseli grid-lines'); //#
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE Kuvaaja');
DEFINE('_ERRCHRTNOTYPE', 'Kuvaajan tyyppi‰ ei ole m‰‰ritelty');
DEFINE('_ERRNOAGSPEC', 'Yht‰‰n AG:a ei m‰‰ritelty. K‰ytet‰‰n kaikkia h‰lytyksi‰.');
DEFINE('_CHRTDATAIMPORT', 'Starting data import'); //#
DEFINE('_CHRTTIMEVNUMBER', 'Aika vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTTIME', 'Aika');
DEFINE('_CHRTALERTOCCUR', 'H‰lytysten Esiintym‰t');
DEFINE('_CHRTSIPNUMBER', 'L‰hde IP vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTSIP', 'L‰hde IP Osoite');
DEFINE('_CHRTDIPALERTS', 'Kohde IP vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTDIP', 'Kohde IP Osoite');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP Portti (Kohde) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTDUDPPORT', 'Kohde UDP Portti');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP Portti (L‰hde) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTSUDPPORT', 'L‰hde UDP Portti');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP Portti (Kohde) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTPORTDEST', 'Kohde TCP Portti');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP Portti (L‰hde) vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTPORTSRC', 'L‰hde TCP Portti');
DEFINE('_CHRTSIGNUMBER', 'Signature Classification vs. H‰lytysten M‰‰r‰'); //#
DEFINE('_CHRTCLASS', 'Classification'); //#
DEFINE('_CHRTSENSORNUMBER', 'Sensori vs. H‰lytysten M‰‰r‰');
DEFINE('_CHRTHANDLEPERIOD', 'K‰sittelyaika jos tarpeellista ');
DEFINE('_CHRTDUMP', 'Dumping data ... (writing only every'); //#
DEFINE('_CHRTDRAW', 'Piirt‰m‰ss‰ graafia');
DEFINE('_ERRCHRTNODATAPOINTS', 'No data points to plot'); //#
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Yll‰ito');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
DEFINE('_MNTPHP', 'PHP k‰‰nnˆs(build)versio:');
DEFINE('_MNTCLIENT', 'ASIAKAS:');
DEFINE('_MNTSERVER', 'SERVERI:');
DEFINE('_MNTSERVERHW', 'SERVER HW:'); //#
DEFINE('_MNTPHPVER', 'PHP VERSIONUMERO:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP Logging level:'); //#
DEFINE('_MNTPHPMODS', 'Ladatutu Moduulit:');
DEFINE('_MNTDBTYPE', 'Tietokannan Tyyppi:');
DEFINE('_MNTDBALV', 'Tietokannan-abstrtaktio-versio:');
DEFINE('_MNTDBALERTNAME', 'ALERT DB Name:'); //#
DEFINE('_MNTDBARCHNAME', 'ARCHIVE DB Name:'); //#
DEFINE('_MNTAIC', 'H‰lytystietojen V‰limuisti:');
DEFINE('_MNTAICTE', 'Tapahtumia Yhteens‰:');
DEFINE('_MNTAICCE', 'Cached Events:'); //#
DEFINE('_MNTIPAC', 'IP Osoitteiden v‰limuisti');
DEFINE('_MNTIPACUSIP', 'Uniikki L‰hde IP:');
DEFINE('_MNTIPACDNSC', 'DNS Cached:'); //#
DEFINE('_MNTIPACWC', 'Whois Cached:'); //#
DEFINE('_MNTIPACUDIP', 'Uniikki Kohde IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Invalid (sid,cid) pair'); //#
DEFINE('_QAALERTDELET', 'H‰lytys POISTETTU');
DEFINE('_QATRIGGERSIG', 'Triggered Signature'); //#
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'signature'); //#
DEFINE('_QCIPADDR', 'IP osoitteet');
DEFINE('_QCIPFIELDS', 'IP kent‰t');
DEFINE('_QCTCPPORTS', 'TCP portit');
DEFINE('_QCTCPFLAGS', 'TCP flags'); //#
DEFINE('_QCTCPFIELD', 'TCP kent‰t');
DEFINE('_QCUDPPORTS', 'UDP portit');
DEFINE('_QCUDPFIELDS', 'UDP kent‰t');
DEFINE('_QCICMPFIELDS', 'ICMP kent‰t');
DEFINE('_QCDATA', 'Data');
DEFINE('_QCERRCRITWARN', 'Criteria varoitus:'); //#
DEFINE('_QCERRVALUE', 'A value of'); //#
DEFINE('_QCERRFIELD', 'A field of'); //#
DEFINE('_QCERROPER', 'An operator of'); //#
DEFINE('_QCERRDATETIME', 'A date/time value of'); //#
DEFINE('_QCERRPAYLOAD', 'A payload value of'); //#
DEFINE('_QCERRIP', 'A IP osoite of'); //#
DEFINE('_QCERRIPTYPE', 'IP-osoite tyypilt‰‰n');
DEFINE('_QCERRSPECFIELD', ' annettiin protkolla-kentt‰‰ varten, mutta kyseist‰ kentt‰‰ ei m‰‰ritelty.'); //#
DEFINE('_QCERRSPECVALUE', 'was selected indicating that it should be a criteria, but no value was specified on which to match.'); //#
DEFINE('_QCERRBOOLEAN', 'Multiple protocol field criteria entered without a boolean operator (e.g. AND, OR) between them.'); //#
DEFINE('_QCERRDATEVALUE', 'was selected indicating that some date/time criteria should be matched, but no value was specified.'); //#
DEFINE('_QCERRINVHOUR', '(Invalid Hour) No date criteria were entered with the specified time.'); //#
DEFINE('_QCERRDATECRIT', 'was selected indicating that some date/time criteria should be matched, but no value was specified.'); //#
DEFINE('_QCERROPERSELECT', 'annettiin mutta yht‰‰n operaattoria ei valittu.');
DEFINE('_QCERRDATEBOOL', 'Usea Pvm./Aika kriteeri annettu ilman boolean-operaattoreita(esim. AND, OR) niiden v‰liss‰.');
DEFINE('_QCERRPAYCRITOPER', 'was entered for a payload criteria field, but an operator (e.g. has, has not) was not specified.'); //#
DEFINE('_QCERRPAYCRITVALUE', 'was selected indicating that payload should be a criteria, but no value on which to match was specified.'); //#
DEFINE('_QCERRPAYBOOL', 'Multiple Data payload criteria entered without a boolean operator (e.g. AND, OR) between them.'); //#
DEFINE('_QCMETACRIT', 'Meta Criteria'); //#
DEFINE('_QCIPCRIT', 'IP Criteria'); //#
DEFINE('_QCPAYCRIT', 'Payload Criteria'); //#
DEFINE('_QCTCPCRIT', 'TCP Criteria'); //#
DEFINE('_QCUDPCRIT', 'UDP Criteria'); //#
DEFINE('_QCICMPCRIT', 'ICMP Criteria'); //#
DEFINE('_QCERRINVIPCRIT', 'Invalid IP osoite criteria'); //#
DEFINE('_QCERRCRITADDRESSTYPE', 'was entered for as a criteria value, but the type of osoite (e.g. source, destination) was not specified.'); //#
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicating that an IP osoite should be a criteria, but no osoite on which to match was specified.'); //#
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRCRITIPADDRESSNONE1', 'was selected (at #'); //#
DEFINE('_QCERRCRITIPIPBOOL', 'Multiple IP osoite criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria'); //#
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Sort order'); //#
DEFINE('_QFRMTIMEA', 'aikaleima (nouseva)');
DEFINE('_QFRMTIMED', 'aikaleima (laskeva)');
DEFINE('_QFRMSIG', 'signature'); //#
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMSIP', 'l‰hde IP');
DEFINE('_QFRMDIP', 'kohde IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Summary Statistics'); //#
DEFINE('_QSCTIMEPROF', 'Aika profiili');
DEFINE('_QSCOFALERTS', 'h‰lytyksist‰');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'H‰lytyslistaus');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Categories:'); //#
DEFINE('_SCSENSORTOTAL', 'Sensorit/YhteensÔøΩ');
DEFINE('_SCTOTALNUMALERTS', 'H‰lytysten kokonaism‰‰r‰');
DEFINE('_SCSRCIP', 'L‰hde IP osoite:');
DEFINE('_SCDSTIP', 'Kohde IP osoite:');
DEFINE('_SCUNILINKS', 'Unikiit IP linkit');
DEFINE('_SCSRCPORTS', 'L‰hde Portit: ');
DEFINE('_SCDSTPORTS', 'Kohde Portit: ');
DEFINE('_SCSENSORS', 'Sensorit');
DEFINE('_SCCLASS', 'luokittelut');
DEFINE('_SCUNIADDRESS', 'Uniikit osoitteet: ');
DEFINE('_SCSOURCE', 'L‰hde');
DEFINE('_SCDEST', 'Kohde');
DEFINE('_SCPORT', 'Portti');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN EVENT ERROR: '); //#
DEFINE('_PSEVENTERRNOFILE', 'Yht‰‰n tiedostoa ei ole asetettu \$portscan_file muuttujaan.');
DEFINE('_PSEVENTERROPENFILE', 'Porttiskannaus-tapahtuma-tiedoston(portscan event file) avaaminen ei onnistu');
DEFINE('_PSDATETIME', 'Pvm./Aika');
DEFINE('_PSSRCIP', 'L‰hde IP');
DEFINE('_PSDSTIP', 'Kohde IP');
DEFINE('_PSSRCPORT', 'L‰hde Portti');
DEFINE('_PSDSTPORT', 'Kohde Portti');
DEFINE('_PSTCPFLAGS', 'TCP Flags'); //#
DEFINE('_PSTOTALOCC', 'Esiintymi‰<BR> Yhteens‰');
DEFINE('_PSNUMSENSORS', 'Sensorien M‰‰r‰');
DEFINE('_PSFIRSTOCC', 'Ensimm‰inen<BR> Esiintym‰');
DEFINE('_PSLASTOCC', 'Viimeinen<BR> Esiintym‰');
DEFINE('_PSUNIALERTS', 'Uniikit H‰lytykset');
DEFINE('_PSPORTSCANEVE', 'Porttiskannaukset');
DEFINE('_PSREGWHOIS', 'Registry lookup (whois) in'); //#
DEFINE('_PSNODNS', 'yht‰‰n DNS resolution ei yritetty'); //#
DEFINE('_PSNUMSENSORSBR', 'Sensorien <BR>M‰‰r‰');
DEFINE('_PSOCCASSRC', 'Ilmentym‰t <BR>as L‰hde'); //#
DEFINE('_PSOCCASDST', 'Ilmentym‰t <BR>as Kohde'); //#
DEFINE('_PSWHOISINFO', 'Whois Information'); //#
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP Linkit');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
DEFINE('_SIPLSOURCEFGDN', 'L‰hde FQDN'); //#
DEFINE('_SIPLDESTFGDN', 'Kohde FQDN'); //#
DEFINE('_SIPLDIRECTION', 'Suunta');
DEFINE('_SIPLPROTO', 'Protokolla');
DEFINE('_SIPLUNIDSTPORTS', 'Uniikki Kohde Portti');
DEFINE('_SIPLUNIEVENTS', 'Uniikit Tapahtumat');
DEFINE('_SIPLTOTALEVENTS', 'Kaikki Tapahtumat');
//base_stat_ports.php
DEFINE('_UNIQ', 'Uniikki');
DEFINE('_DSTPS', 'Kohde Portit');
DEFINE('_SRCPS', 'L‰hde Portit');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Sensori Listaus');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_time.php
DEFINE('_BSTTITLE', 'H‰lytysten Aikaprofiili');
DEFINE('_BSTTIMECRIT', 'Aika Kriteeri');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Yht‰‰n profilointikriteeri‰ ei annettu!</B>  klikkaa "tunti(hour)", "p‰iv‰(day)" tai "kuukausi(month)" valitaksesi the granularity of the aggregate statistics.</FONT>'); //#
DEFINE('_BSTERRTIMETYPE', '<FONT><B>The type of time parameter which will be passed was not specified!</B>  Valitse joko "on", valitaksesi yksitt‰isen p‰iv‰n, tai "between" valitaksesi tietyn aik‰v‰lin.</FONT>'); //#
DEFINE('_BSTERRNOYEAR', '<FONT><B>Yht‰‰n "vuosi(year)"-parametria ei annettu!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Yht‰‰n "kuukausi(month)"-parametria ei annettu!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Yht‰‰n "p‰iv‰(day)"-parametria ei annettu!</B></FONT>');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Uniikit L‰hde-IP-osoitteet');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
DEFINE('_SUASRCIP', 'L‰hde-IP-osoitteet');
DEFINE('_SUAERRCRITADDUNK', 'CRITERIA ERROR: tuntematon osoitteen tyyppi -- assuming Kohde osoite'); //#
DEFINE('_UNIDADD', 'Uniikit Kohde-IP-osoitteet');
DEFINE('_SUADSTIP', 'Kohde-IP-osoitteet');
DEFINE('_SUAUNIALERTS', 'Uniikite&nbsp;H‰lytykset');
DEFINE('_SUASRCADD', 'L‰hde&nbsp;Osoite');
DEFINE('_SUADSTADD', 'Kohde&nbsp;Osoite');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE K‰ytt‰j‰n asetukset');
DEFINE('_BASEUSERERRPWD', 'Salasana ei voi olla tyhj‰ tai salasanat eiv‰t t‰sm‰‰!');
DEFINE('_BASEUSEROLDPWD', 'Vanha Salasana:');
DEFINE('_BASEUSERNEWPWD', 'Uusi Salasana:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Uusi Salasana Uudestaan:');
DEFINE('_LOGOUT', 'Kirjaudu ulos');
?>
