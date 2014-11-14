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
DEFINE('_TITLE', 'Motore di Sicurezza e Analisi Basilare (BASE) ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Login:');
DEFINE('_FRMPWD', 'Password:');
DEFINE('_SOURCE', 'Sorgente');
DEFINE('_SOURCENAME', 'Nome Sorgente');
DEFINE('_DEST', 'Destinazione');
DEFINE('_DESTNAME', 'Nome Dest.');
DEFINE('_SORD', 'Sorg. o Dest.');
DEFINE('_EDIT', 'Modifica');
DEFINE('_DELETE', 'Elimina');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Nome');
DEFINE('_INTERFACE', 'Interfaccia');
DEFINE('_FILTER', 'Filtro');
DEFINE('_DESC', 'Descrizione');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'ID Ruolo');
DEFINE('_ENABLED', 'Abilitato');
DEFINE('_SUCCESS', 'Completo');
DEFINE('_SENSOR', 'Sensore');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Firma');
DEFINE('_TIMESTAMP', 'Orario');
DEFINE('_NBSOURCEADDR', 'Indirizzo Sorgente');
DEFINE('_NBDESTADDR', 'Indirizzo Destinatario');
DEFINE('_NBLAYER4', 'Layer 4 Proto');
DEFINE('_PRIORITY', 'Priorità');
DEFINE('_EVENTTYPE', 'tipo evento');
DEFINE('_JANUARY', 'Gennaio');
DEFINE('_FEBRUARY', 'Febbraio');
DEFINE('_MARCH', 'Marzo');
DEFINE('_APRIL', 'Aprile');
DEFINE('_MAY', 'Maggio');
DEFINE('_JUNE', 'Giugno');
DEFINE('_JULY', 'Luglio');
DEFINE('_AUGUST', 'Agosto');
DEFINE('_SEPTEMBER', 'Settembre');
DEFINE('_OCTOBER', 'Ottobre');
DEFINE('_NOVEMBER', 'Novembre');
DEFINE('_DECEMBER', 'Dicembre');
DEFINE('_LAST', 'Ultimo');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Avvisi');
DEFINE('_ADDRESS', 'Indirizzo');
DEFINE('_UNKNOWN', 'sconosciuto');
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
DEFINE('_HOME', 'Home');
DEFINE('_SEARCH', 'Cerca');
DEFINE('_AGMAINT', 'Manutenzione Gruppo Avvertimenti');
DEFINE('_USERPREF', 'Preferenze Utente');
DEFINE('_CACHE', 'Cache & Stato');
DEFINE('_ADMIN', 'Amministrazione');
DEFINE('_GALERTD', 'Grafico Dati di Avvertimento');
DEFINE('_GALERTDT', 'Grafico Ora di Rilevamento');
DEFINE('_USERMAN', 'Gestione utenti');
DEFINE('_LISTU', 'Lista utenti');
DEFINE('_CREATEU', 'Crea utente');
DEFINE('_ROLEMAN', 'Gestione Ruoli');
DEFINE('_LISTR', 'Lista Ruoli');
DEFINE('_LOGOUT', 'Logout');
DEFINE('_CREATER', 'Crea Ruolo');
DEFINE('_LISTALL', 'Lista Completa');
DEFINE('_CREATE', 'Crea');
DEFINE('_VIEW', 'Visualizza');
DEFINE('_CLEAR', 'Svuota');
DEFINE('_LISTGROUPS', 'Lista Gruppi');
DEFINE('_CREATEGROUPS', 'Crea Gruppo');
DEFINE('_VIEWGROUPS', 'Visualizza Gruppo');
DEFINE('_EDITGROUPS', 'Modifica Gruppo');
DEFINE('_DELETEGROUPS', 'Elimina Gruppo');
DEFINE('_CLEARGROUPS', 'Svuota Gruppo');
DEFINE('_CHNGPWD', 'Cambia password');
DEFINE('_DISPLAYU', 'Visualizza Utente');
//base_footer.php
DEFINE('_FOOTER', '( by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> e il <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">team di sviluppo BASE Project</A><BR>Programmato grazie a ACID by Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Dati di login errati!<br>Per favore riprova');
// base_main.php
DEFINE('_MOSTRECENT', 'Il più recente ');
DEFINE('_MOSTFREQUENT', 'Il più frequente ');
DEFINE('_ALERTS', ' Avvertimenti:');
DEFINE('_ADDRESSES', ' Indirizzi:');
DEFINE('_ANYPROTO', 'qualsiasi protocollo');
DEFINE('_UNI', 'unico');
DEFINE('_LISTING', 'lista');
DEFINE('_TALERTS', 'Avvisi di oggi: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'Avvisi ultime 24 ore: ');
DEFINE('_L72ALERTS', 'Avvisi ultime 72 ore: ');
DEFINE('_UNIALERTS', ' Unici Avvisi');
DEFINE('_LSOURCEPORTS', 'Ultime Porte Sorgente: ');
DEFINE('_LDESTPORTS', 'Ultime Porte di Destinazione: ');
DEFINE('_FREGSOURCEP', 'Porte Sorgenti più frequenti: ');
DEFINE('_FREGDESTP', 'Porte di Destinazione più frequenti: ');
DEFINE('_QUERIED', 'Query per Database');
DEFINE('_DATABASE', ':');
DEFINE('_SCHEMAV', 'Versione Schema:');
DEFINE('_TIMEWIN', 'Finestra Orario:');
DEFINE('_NOALERTSDETECT', 'nessun avviso rilevato');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Aggiunto con successo');
DEFINE('_NOPWDCHANGE', 'Impossibile cambiare la password: ');
DEFINE('_NOUSER', 'L\'utente non esiste!');
DEFINE('_OLDPWD', 'La vecchia password inserita non è corretta!');
DEFINE('_PWDCANT', 'Impossibile cambiare la password: ');
DEFINE('_PWDDONE', 'Password cambiata!');
DEFINE('_ROLEEXIST', 'Ruolo già esistente');
DEFINE('_ROLEIDEXIST', 'ID Ruolo già esistente');
DEFINE('_ROLEADDED', 'Ruolo aggiunto con successo');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'Amministrazione Ruoli BASE');
DEFINE('_FRMROLEID', 'ID Ruolo:');
DEFINE('_FRMROLENAME', 'Nome Ruolo:');
DEFINE('_FRMROLEDESC', 'Descrizione:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'Amministrazione utenti BASE');
DEFINE('_FRMFULLNAME', 'Nome completo:');
DEFINE('_FRMROLE', 'Ruolo:');
DEFINE('_FRMUID', 'ID Utente:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'Amministrazione BASE');
DEFINE('_BASEADMINTEXT', 'Scegliere un\'opzione dalla destra.');
//base_action.inc.php
DEFINE('_NOACTION', 'Nessuna azione specificata sugli avvertimenti');
DEFINE('_INVALIDACT', ' non è un\'azione valida');
DEFINE('_ERRNOAG', 'Impossibile aggiungere l\'avvertimento perchè non è definito il gruppo');
DEFINE('_ERRNOEMAIL', 'Impossibile inviare e-amil di notifica perchè non sono specificati indirizzi');
DEFINE('_ACTION', 'AZIONE');
DEFINE('_CONTEXT', 'contesto');
DEFINE('_ADDAGID', 'Aggiungi A Gruppo (per ID)');
DEFINE('_ADDAG', 'Nuovo Gruppo');
DEFINE('_ADDAGNAME', 'Aggiungi to AG (by Name)');
DEFINE('_CREATEAG', 'Crea Gruppo (per Nome)');
DEFINE('_CLEARAG', 'Cancella da Gruppo');
DEFINE('_DELETEALERT', 'Cancella avvertimento/i');
DEFINE('_EMAILALERTSFULL', 'Invia Avviso/i (completo)');
DEFINE('_EMAILALERTSSUMM', 'Invia Avviso/i (sommario)');
DEFINE('_EMAILALERTSCSV', 'Invia Avviso/i (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Archivia Avvertimento/i (copia)');
DEFINE('_ARCHIVEALERTSMOVE', 'Archivia Avvertimento/i (sposta)');
DEFINE('_IGNORED', 'Ignorato ');
DEFINE('_DUPALERTS', ' avvertimento/i duplicato/i');
DEFINE('_ALERTSPARA', ' avvertimento/i');
DEFINE('_NOALERTSSELECT', 'Nessun avvertimento selezionato oppure');
DEFINE('_NOTSUCCESSFUL', 'non ha avuto buon fine');
DEFINE('_ERRUNKAGID', 'ID Gruppo sconosciuto (forse non esiste)');
DEFINE('_ERRREMOVEFAIL', 'Impossibile rimuovere il Gruppo');
DEFINE('_GENBASE', 'Generato da BASE');
DEFINE('_ERRNOEMAILEXP', 'Errore di Esportazione: Impossibile spedire gli avvertimenti esportati a');
DEFINE('_ERRNOEMAILPHP', 'Controllare la configurazione e-mail PHP.');
DEFINE('_ERRDELALERT', 'Errore durante la cancellazione dell\'avvertimento');
DEFINE('_ERRARCHIVE', 'Errore di archiviazione:');
DEFINE('_ERRMAILNORECP', 'ERRORE E-MAIL: Destinatario non specificato');
//base_cache.inc.php
DEFINE('_ADDED', 'Aggiunti ');
DEFINE('_HOSTNAMESDNS', ' nomi host alla cache IP DNS');
DEFINE('_HOSTNAMESWHOIS', ' nomi host alla cache Whois');
DEFINE('_ERRCACHENULL', 'Errore cache: trovato valore nullo?');
DEFINE('_ERRCACHEERROR', 'ERRORE CACHE EVENTO:');
DEFINE('_ERRCACHEUPDATE', 'Impossibile aggiornare la cache eventi');
DEFINE('_ALERTSCACHE', ' avvertimento/i alla cache Avvertimenti');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Impossibile aprire il file SQL di trace');
DEFINE('_ERRSQLCONNECT', 'Errore di connessione al database :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Controllare le variabili di configurazione in <I>base_conf.php</I>
              <PRE>
               = $alert_dbname   : Nome database MySQL dove sono memorizzati gli avvertimenti
               = $alert_host     : nome host del server MySQL
               = $alert_port     : porta del server MySQL
               = $alert_user     : nome utente MySQL
               = $alert_password : password utente MySQL
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Errore di connessione al database :');
DEFINE('_ERRSQLDB', 'ERRORE Database:');
DEFINE('_DBALCHECK', 'Controllo librerie di astrazione database in');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Impossibile caricare le librerie di Astrazione DB: </B> da ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Controllare la variabile di astrazione DB <CODE>$DBlib_path</CODE> in <CODE>base_conf.php</CODE>
            <P>
            L\'interfaccia attualmente in uso è ADODB, scaricabile direttamente da
            <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Tipo Database specificato non valido');
DEFINE('_ERRSQLDBTYPEINFO1', 'La variabile <CODE>\$DBtype</CODE> in <CODE>base_conf.php</CODE> è impostata al tipo sconosciuto di database ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Solo i seguenti database sono supportati: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'ERRORE FATALE BASE:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Caricato in');
DEFINE('_SECONDS', 'secondi');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Impossibile risolvere l\'indirizzo');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Intestazione dell\'output Query');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName sconosciuto');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority sconosciuto');
DEFINE('_UNCLASS', 'non classificato');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'dati codificati come');
DEFINE('_NODENCODED', '(nessuna conversione di dati, si utilizza la codifica DB)');
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
DEFINE('_PHPERRORCSESSION', 'ERRORE PHP: rilevata sessione utente PHP. BASE non è impostato per questo tipo di gestione.  Impostare <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'ERRORE PHP: un gestore sessione utente è già configurato, ma il codice handle specificato in <CODE>user_session_path</CODE> non è valido.');
DEFINE('_PHPERRORCSESSIONVAR', 'ERRORE PHP: un gestore sessione utente è già configurato, ma la sua implementazione non è specificata in BASE. Se si vuole utilizzare un gestore di sessioni utente, impostare <CODE>user_session_path</CODE> in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Sessione Registrata');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Rimozione');
DEFINE('_FROMCRIT', 'dai criteri');
DEFINE('_ERRCRITELEM', 'Elementi di criterio non validi');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Lista Query non riuscite');
DEFINE('_DISPLAYING', 'Visualizzazione');
DEFINE('_DISPLAYINGTOTAL', 'Visualizzazione avvertimenti %d-%d di %s totali');
DEFINE('_NOALERTS', 'Nessun Avvertimento trovato.');
DEFINE('_QUERYRESULTS', 'Risultato Query');
DEFINE('_QUERYSTATE', 'Stato Query');
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Il nome Gruppo specificato non è valido.  Riprovare!');
DEFINE('_ERRAGNAMEEXIST', 'Il Gruppo specificato non esiste.');
DEFINE('_ERRAGIDSEARCH', 'L\'ID gruppo non è valido.  Riprovare!');
DEFINE('_ERRAGLOOKUP', 'Errore di ricerca Gruppo per ID');
DEFINE('_ERRAGINSERT', 'Errore inserimento nuovo Gruppo');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Manutenzione Gruppi');
DEFINE('_ERRAGUPDATE', 'Errore aggiornamento Gruppi');
DEFINE('_ERRAGPACKETLIST', 'Errore di rimozione lista pacchetti Gruppo:');
DEFINE('_ERRAGDELETE', 'Errore di rimozione Gruppo');
DEFINE('_AGDELETE', 'ELIMINAZIONE completata');
DEFINE('_AGDELETEINFO', 'informazione eliminata');
DEFINE('_ERRAGSEARCHINV', 'Criterio di ricerca immesso non valido. Riprova!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Nessun Gruppo trovato in base ai criteri specificati.');
DEFINE('_NOALERTGOUPS', 'Non ci sono Gruppi Avvertimenti');
DEFINE('_NUMALERTS', '# Avvertiment');
DEFINE('_ACTIONS', 'Azioni');
DEFINE('_NOTASSIGN', 'da assegnare');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Traffico Portscan');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Impossibile CREARE INDICE per');
DEFINE('_DBINDEXCREATE', 'INDICE creato correttamente per');
DEFINE('_ERRSNORTVER', 'Potrebbe essere una vecchia versione. Solo i database avvertimenti creati da Snort 1.7-beta0 o successivi sono supportati');
DEFINE('_ERRSNORTVER1', 'Il database sottostante');
DEFINE('_ERRSNORTVER2', 'sembra essere incompleto/non valido');
DEFINE('_ERRDBSTRUCT1', 'La versione database è valida, ma la struttura DB BASE');
DEFINE('_ERRDBSTRUCT2', 'non è presente. Utilizzare il <A HREF="base_db_setup.php">Setup</A> per configurare e ottimizzare il database.');
DEFINE('_ERRPHPERROR', 'ERRORE PHP');
DEFINE('_ERRPHPERROR1', 'Versione non compatibile');
DEFINE('_ERRVERSION', 'La versione');
DEFINE('_ERRPHPERROR2', 'di PHP è troppo vecchia.  Aggiornarla alla 4.0.4 or successiva');
DEFINE('_ERRPHPMYSQLSUP', '<B>Pacchetto PHP mancante</B>: <FONT>il modulo MySQL, necessario per la lettura
               del database Avvertimenti, non è incluso in PHP.
               Per favore ricompilare PHP includendo MySQL (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>Pacchetto PHP mancante</B>: <FONT>il modulo PostgreSQL necessario per la lettura
               del database Avvertimenti, non è incluso in PHP.
               Per favore ricompilare PHP includendo PostgreSQL (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>Pacchetto PHP mancante</B>: <FONT>il modulo MS SQL necessario per la lettura
               del database Avvertimenti, non è incluso in PHP.
               Per favore ricompilare PHP includendo MySQL (<CODE>--enable-sql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Titolo Grafico:');
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
DEFINE('_CHRTTYPEHOUR', 'Tempo (ora) vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEDAY', 'Tempo (giorno) vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEWEEK', 'Tempo (settimana) vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEMONTH', 'Tempo (mese) vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEYEAR', 'Tempo (anno) vs. Numero Avvertimenti');
DEFINE('_CHRTTYPESRCIP', 'IP Sorg. vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEDSTIP', 'IP Dest. vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEDSTUDP', 'Dest. UDP vs. Numero Avvertimenti');
DEFINE('_CHRTTYPESRCUDP', 'Sorg. UDP vs. Numero Avvertimenti');
DEFINE('_CHRTTYPEDSTPORT', 'Dest. TCP vs. Numero Avvertimenti');
DEFINE('_CHRTTYPESRCPORT', 'Sorg. TCP vs. Numero Avvertimenti');
DEFINE('_CHRTTYPESIG', 'Classifica Signature vs. Numero Avvertimenti');
DEFINE('_CHRTTYPESENSOR', 'Sensore vs. Numero Avvertimenti');
DEFINE('_CHRTBEGIN', 'Inizio Grafico:');
DEFINE('_CHRTEND', 'Fine Grafico:');
DEFINE('_CHRTDS', 'Sorgente Dati:');
DEFINE('_CHRTX', 'Asse X');
DEFINE('_CHRTY', 'Asse Y');
DEFINE('_CHRTMINTRESH', 'Valore Treshold minimo');
DEFINE('_CHRTROTAXISLABEL', 'Ruotare etichette asse X (90°)');
DEFINE('_CHRTSHOWX', 'Mostra griglia asse X');
DEFINE('_CHRTDISPLABELX', 'Mostra etichetta asse X ogni');
DEFINE('_CHRTDATAPOINTS', 'punti dati');
DEFINE('_CHRTYLOG', 'Asse Y logaritmico');
DEFINE('_CHRTYGRID', 'Mostra griglia asse Y');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'Grafico BASE');
DEFINE('_ERRCHRTNOTYPE', 'Tipo di grafico non specificato');
DEFINE('_ERRNOAGSPEC', 'Gruppo non specificato. Verranno considerati tutti gli avvertimenti.');
DEFINE('_CHRTDATAIMPORT', 'Inizio importazione dati');
DEFINE('_CHRTTIMEVNUMBER', 'Tempo vs. Numero Avvertimenti');
DEFINE('_CHRTTIME', 'Orario');
DEFINE('_CHRTALERTOCCUR', 'Occorrenze Avvertimento');
DEFINE('_CHRTSIPNUMBER', 'IP Sorgente vs. Numero Avvertimenti');
DEFINE('_CHRTSIP', 'IP Sorgente');
DEFINE('_CHRTDIPALERTS', 'IP Destinazione vs. Numero Avvertimenti');
DEFINE('_CHRTDIP', 'IP Destinazione');
DEFINE('_CHRTUDPPORTNUMBER', 'Porta UDP (Destinazione) vs. Numero Avvertimenti');
DEFINE('_CHRTDUDPPORT', 'Porta UDP Dest.');
DEFINE('_CHRTSUDPPORTNUMBER', 'Porta UDP (Sorgente) vs. Numero Avvertimenti');
DEFINE('_CHRTSUDPPORT', 'Porta UDP Sorgente');
DEFINE('_CHRTPORTDESTNUMBER', 'Porta TCP (Destinazione) vs. Numero Avvertimenti');
DEFINE('_CHRTPORTDEST', 'Porta TCP Dest.');
DEFINE('_CHRTPORTSRCNUMBER', 'Porta TCP (Sorgente) vs. Numero Avvertimenti');
DEFINE('_CHRTPORTSRC', 'Src. TCP Port');
DEFINE('_CHRTSIGNUMBER', 'Classificazione Signature vs. Numero Avvertimenti');
DEFINE('_CHRTCLASS', 'Classificazione');
DEFINE('_CHRTSENSORNUMBER', 'Sensore vs. Numero Avvertimenti');
DEFINE('_CHRTHANDLEPERIOD', 'Periodo di riferimento, se necessario');
DEFINE('_CHRTDUMP', 'Elaborazione dati ... (scrittura solo ogni');
DEFINE('_CHRTDRAW', 'Creazione grafico');
DEFINE('_ERRCHRTNODATAPOINTS', 'Nessun punto dati da riportare');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Manutenzione');
DEFINE('_MNTPHP', 'Pacchetto PHP:');
DEFINE('_MNTCLIENT', 'CLIENT:');
DEFINE('_MNTSERVER', 'SERVER:');
DEFINE('_MNTSERVERHW', 'HW SERVER:');
DEFINE('_MNTPHPVER', 'VERSIONE PHP:');
DEFINE('_MNTPHPAPI', 'API PHP:');
DEFINE('_MNTPHPLOGLVL', 'Livelo log PHP:');
DEFINE('_MNTPHPMODS', 'Modulei caricati:');
DEFINE('_MNTDBTYPE', 'Tipo DB:');
DEFINE('_MNTDBALV', 'Versione Astrazione DB:');
DEFINE('_MNTDBALERTNAME', 'Nome DB AVVERTIMENTI:');
DEFINE('_MNTDBARCHNAME', 'Nome DB ARCHIVIO:');
DEFINE('_MNTAIC', 'Cache informazioni Avvisi:');
DEFINE('_MNTAICTE', 'Eventi totali:');
DEFINE('_MNTAICCE', 'Eventi nella cache:');
DEFINE('_MNTIPAC', 'Cache indirizzi IP');
DEFINE('_MNTIPACUSIP', 'IP sorgenti unici:');
DEFINE('_MNTIPACDNSC', 'DNS nella cache:');
DEFINE('_MNTIPACWC', 'Whois nella cache:');
DEFINE('_MNTIPACUDIP', 'IP Destinazione unici:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Coppia (sid,cid) non valida');
DEFINE('_QAALERTDELET', 'Avviso CANCELLATO');
DEFINE('_QATRIGGERSIG', 'Signature Triggered');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'signature');
DEFINE('_QCIPADDR', 'indirizzi IP');
DEFINE('_QCIPFIELDS', 'campi IP');
DEFINE('_QCTCPPORTS', 'Porte TCP');
DEFINE('_QCTCPFLAGS', 'Flag TCP');
DEFINE('_QCTCPFIELD', 'Campi TCP');
DEFINE('_QCUDPPORTS', 'Porte UDP');
DEFINE('_QCUDPFIELDS', 'Campi UDP');
DEFINE('_QCICMPFIELDS', 'Campi ICMP');
DEFINE('_QCDATA', 'Dati');
DEFINE('_QCERRCRITWARN', 'Avviso criteri:');
DEFINE('_QCERRVALUE', 'Un valore di');
DEFINE('_QCERRFIELD', 'Un campo di');
DEFINE('_QCERROPER', 'Un operatore di');
DEFINE('_QCERRDATETIME', 'Una data/ora di');
DEFINE('_QCERRPAYLOAD', 'Un valore payload di');
DEFINE('_QCERRIP', 'Un indirizzo IP di');
DEFINE('_QCERRIPTYPE', 'Un indirizzo IP del tipo');
DEFINE('_QCERRSPECFIELD', ' è stato inserito per il campo protocollo, ma il campo stesso non è stato specificato.');
DEFINE('_QCERRSPECVALUE', 'è stato indicato come criterio, ma non è stato valorizzato.');
DEFINE('_QCERRBOOLEAN', 'Necessari operatori logici in caso di immissione di più protocolli (AND, OR).');
DEFINE('_QCERRDATEVALUE', 'è stato selezionato indicando che deve rispettare criteri data/ora non specificati.');
DEFINE('_QCERRINVHOUR', '(Ora non valida) Nessun criterio dati specificato valido per l\'ora indicata.');
DEFINE('_QCERRDATECRIT', 'è stato selezionato indicando che deve rispettare criteri data/ora non specificati.');
DEFINE('_QCERROPERSELECT', 'è stato immesso senza indicare operatori.');
DEFINE('_QCERRDATEBOOL', 'Necessari operatori logici in caso di immissione di più criteri data/ora (AND, OR).');
DEFINE('_QCERRPAYCRITOPER', 'è stato inserito per un campo criteri payload, ma non è stato specificato un operatore logico.');
DEFINE('_QCERRPAYCRITVALUE', 'è stato selezionato indicando che il payload deve essere un criterio, ma non è stato specificato alcun valore da verificare.');
DEFINE('_QCERRPAYBOOL', 'Inseriti più criteri payload senza un operatore booleano separatore.');
DEFINE('_QCMETACRIT', 'Criteri Meta');
DEFINE('_QCIPCRIT', 'Criteri IP');
DEFINE('_QCPAYCRIT', 'Criteri Payload');
DEFINE('_QCTCPCRIT', 'Criteri TCP');
DEFINE('_QCUDPCRIT', 'Criteri UDP');
DEFINE('_QCICMPCRIT', 'Criteri ICMP');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Criterio IP Non Valido');
DEFINE('_QCERRCRITADDRESSTYPE', 'è stato inserito come valore del criterio, ma il tipo di indirizzo (es. sorgente, destinazione) non è stato specificato.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicando che un indirizzo IP sia un criterio, ma non è stato specificato alcun indirizzo da verificare.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'è stato selzionato (al #');
DEFINE('_QCERRCRITIPIPBOOL', 'Inseriti criteri IP multipli senza operatore booleano separatore (es. AND, OR)');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Ordina per');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMTIMEA', 'istante (ascendemte)');
DEFINE('_QFRMTIMED', 'istante (discendente)');
DEFINE('_QFRMSIG', 'signature');
DEFINE('_QFRMSIP', 'IP sorgente');
DEFINE('_QFRMDIP', 'IP destinazione');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Sommario Statistiche');
DEFINE('_QSCTIMEPROF', 'Profilo tempo');
DEFINE('_QSCOFALERTS', 'di avvisi');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Lista Avvisi');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Categorie:');
DEFINE('_SCSENSORTOTAL', 'Sensori/Totale:');
DEFINE('_SCTOTALNUMALERTS', 'Totale Avvisi:');
DEFINE('_SCSRCIP', 'Srg. IP:');
DEFINE('_SCDSTIP', 'Dest. IP:');
DEFINE('_SCUNILINKS', 'Link IP unici');
DEFINE('_SCSRCPORTS', 'Porte sorgenti: ');
DEFINE('_SCDSTPORTS', 'Porte destinatarie: ');
DEFINE('_SCSENSORS', 'Sensori');
DEFINE('_SCCLASS', 'classificazioni');
DEFINE('_SCUNIADDRESS', 'Indirizzi unici: ');
DEFINE('_SCSOURCE', 'Sorgente');
DEFINE('_SCDEST', 'Destinazione');
DEFINE('_SCPORT', 'Porta');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'ERRORE EVENTO PORTSCA: ');
DEFINE('_PSEVENTERRNOFILE', 'Nessun file specificato nella variabile \$portscan_file.');
DEFINE('_PSEVENTERROPENFILE', 'Impossibile aprire il file degli eventi Portscan');
DEFINE('_PSDATETIME', 'Data/Ora');
DEFINE('_PSSRCIP', 'IP Sorgente');
DEFINE('_PSDSTIP', 'IP Destinazione');
DEFINE('_PSSRCPORT', 'Porta Sorgente');
DEFINE('_PSDSTPORT', 'Porta Destinazione');
DEFINE('_PSTCPFLAGS', 'Flag TCP');
DEFINE('_PSTOTALOCC', 'Occorrenze<BR> Totali');
DEFINE('_PSNUMSENSORS', 'Num Sensori');
DEFINE('_PSFIRSTOCC', 'Prima<BR> Occorrenza');
DEFINE('_PSLASTOCC', 'Ultima<BR> Occorrenza');
DEFINE('_PSUNIALERTS', 'Avvisi Unici');
DEFINE('_PSPORTSCANEVE', 'Eventi Portscan');
DEFINE('_PSREGWHOIS', 'Lookup registro (whois) in');
DEFINE('_PSNODNS', 'nessuna risoluzione DNS tentata');
DEFINE('_PSNUMSENSORSBR', 'Num di <BR>Sensori');
DEFINE('_PSOCCASSRC', 'Occorrenze <BR>come Srg.');
DEFINE('_PSOCCASDST', 'Occorrenze <BR>come Dest.');
DEFINE('_PSWHOISINFO', 'Informazioni Whois');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP Link');
DEFINE('_SIPLSOURCEFGDN', 'Sorgente FQDN');
DEFINE('_SIPLDESTFGDN', 'Destinazione FQDN');
DEFINE('_SIPLDIRECTION', 'Direzione');
DEFINE('_SIPLPROTO', 'Protocollo');
DEFINE('_SIPLUNIDSTPORTS', 'Porte Dest. Uniche');
DEFINE('_SIPLUNIEVENTS', 'Eventi Unici');
DEFINE('_SIPLTOTALEVENTS', 'Eventi Totali');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unici');
DEFINE('_DSTPS', 'Porta/e Destinazione');
DEFINE('_SRCPS', 'Porta/e Sorgente');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Lista Sensori');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Orario Profilo di Avvertimenti');
DEFINE('_BSTTIMECRIT', 'Criteri Tempo');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Nessun criterio di profilo specificato!</B>  Cliccare su "ora", "giorno", o "mese" per scegliere come raggruppare le statistiche.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>il tipo di parametro che verrà passato non è specificato!</B>  Scegliere "il" per specificare una singola data, o "tra" per un intervallo.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Nessun parametro anno specificato!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Nessun parametro mese specificato!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Nessun parametro giorno specificato!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Indirizzo/i IP Sorgente unico/i');
DEFINE('_SUASRCIP', 'Indirizzi IP sorg.');
DEFINE('_SUAERRCRITADDUNK', 'ERRORE CRITERI: tipo indirizzo sconosciuto -- si assume come Destinazione');
DEFINE('_UNIDADD', 'Indirizzo/i IP Destinazione unico/i');
DEFINE('_SUADSTIP', 'Indirizzi IP dest.');
DEFINE('_SUAUNIALERTS', 'Avvisi&nbsp;unici');
DEFINE('_SUASRCADD', 'Indir.&nbsp;Sorg.');
DEFINE('_SUADSTADD', 'Indir.&nbsp;Dest.');
//base_user.php
DEFINE('_BASEUSERTITLE', 'Preferenze Utente BASE');
DEFINE('_BASEUSERERRPWD', 'La password non può essere vuota o le due password non combaciano!');
DEFINE('_BASEUSEROLDPWD', 'Vecchia Password:');
DEFINE('_BASEUSERNEWPWD', 'Nuova Password:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Ripeti Nuova Password:');
?>
