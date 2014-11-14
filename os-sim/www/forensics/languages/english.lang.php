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
DEFINE('_LOCALESTR1', _('eng_ENG.ISO8859-1'));
DEFINE('_LOCALESTR2', _('eng_ENG.utf-8'));
DEFINE('_LOCALESTR3', _('english'));
DEFINE('_STRFTIMEFORMAT', _('%a %B %d, %Y %H:%M:%S')); //see strftime() sintax
//common phrases
DEFINE('_CHARSET', _('iso-8859-1'));
DEFINE('_TITLE', _('Forensics Console ' . $BASE_installID));
DEFINE('_FRMLOGIN', _('Login:'));
DEFINE('_FRMPWD', _('Password:'));
DEFINE('_SOURCE', _('Source'));
DEFINE('_SOURCENAME', _('Source Name'));
DEFINE('_DEST', _('Destination'));
DEFINE('_DESTNAME', _('Dest. Name'));
DEFINE('_SORD', _('Src or Dest'));
DEFINE('_EDIT', _('Edit'));
DEFINE('_DELETE', _('Delete'));
DEFINE('_ID', _('ID'));
DEFINE('_NAME', _('Name'));
DEFINE('_INTERFACE', _('Interface'));
DEFINE('_FILTER', _('Filter'));
DEFINE('_DESC', _('Description'));
DEFINE('_LOGIN', _('Login'));
DEFINE('_ROLEID', _('Role ID'));
DEFINE('_ENABLED', _('Enabled'));
DEFINE('_SUCCESS', _('Successful'));
DEFINE('_SENSOR', _('Sensor'));
DEFINE('_SENSORS', _('Sensors'));
DEFINE('_SIGNATURE', _('Signature'));
DEFINE('_TIMESTAMP', _('Date'));
DEFINE('_NBSOURCEADDR', _('Source&nbsp;Address'));
DEFINE('_NBDESTADDR', _('Dest.&nbsp;Address'));
DEFINE('_NBLAYER4', _('Layer&nbsp;4&nbsp;Proto'));
DEFINE('_PRIORITY', _('Priority'));
DEFINE('_EVENTTYPE', _('event type'));
DEFINE('_JANUARY', _('January'));
DEFINE('_FEBRUARY', _('February'));
DEFINE('_MARCH', _('March'));
DEFINE('_APRIL', _('April'));
DEFINE('_MAY', _('May'));
DEFINE('_JUNE', _('June'));
DEFINE('_JULY', _('July'));
DEFINE('_AUGUST', _('August'));
DEFINE('_SEPTEMBER', _('September'));
DEFINE('_OCTOBER', _('October'));
DEFINE('_NOVEMBER', _('November'));
DEFINE('_DECEMBER', _('December'));
DEFINE('_LAST', _('Last'));
DEFINE('_FIRST', _('First'));
DEFINE('_TOTAL', _('Total'));
DEFINE('_ALERT', _('Event'));
DEFINE('_ADDRESS', _('Address'));
DEFINE('_UNKNOWN', _('unknown'));
DEFINE('_AND', _('AND'));
DEFINE('_OR', _('OR'));
DEFINE('_IS', _('is'));
DEFINE('_ON', _('on'));
DEFINE('_IN', _('in'));
DEFINE('_ANY', _('any'));
DEFINE('_NONE', _('none'));
DEFINE('_HOUR', _('Hour'));
DEFINE('_DAY', _('Day'));
DEFINE('_MONTH', _('Month'));
DEFINE('_YEAR', _('Year'));
DEFINE('_ALERTGROUP', _('Event Group'));
DEFINE('_ALERTTIME', _('Event Time'));
DEFINE('_CONTAINS', _('contains'));
DEFINE('_DOESNTCONTAIN', _('does not contain'));
DEFINE('_SOURCEPORT', _('source port'));
DEFINE('_DESTPORT', _('dest port'));
DEFINE('_HAS', _('has'));
DEFINE('_HASNOT', _('has not'));
DEFINE('_PORT', _('Port'));
DEFINE('_FLAGS', _('Flags'));
DEFINE('_MISC', _('Misc'));
DEFINE('_BACK', _('Back'));
DEFINE('_DISPYEAR', _('{ year }'));
DEFINE('_DISPMONTH', _('{ month }'));
DEFINE('_DISPHOUR', _('{ hour }'));
DEFINE('_DISPDAY', _('{ day }'));
DEFINE('_DISPTIME', _('{ time }'));
DEFINE('_ADDADDRESS', _('ADD Addr'));
DEFINE('_ADDIPFIELD', _('ADD IP Field'));
DEFINE('_ADDTIME', _('ADD TIME'));
DEFINE('_ADDTCPPORT', _('ADD TCP Port'));
DEFINE('_ADDTCPFIELD', _('ADD TCP Field'));
DEFINE('_ADDUDPPORT', _('ADD UDP Port'));
DEFINE('_ADDUDPFIELD', _('ADD UDP Field'));
DEFINE('_ADDICMPFIELD', _('ADD ICMP Field'));
DEFINE('_ADDPAYLOAD', _('ADD Payload'));
DEFINE('_MOSTFREQALERTS', _('Most Frequent Events'));
DEFINE('_MOSTFREQPORTS', _('Most Frequent Ports'));
DEFINE('_MOSTFREQADDRS', _('Most Frequent IP addresses'));
DEFINE('_LASTALERTS', _('Last Events'));
DEFINE('_LASTPORTS', _('Last Ports'));
DEFINE('_LASTTCP', _('Last TCP Events'));
DEFINE('_LASTUDP', _('Last UDP Events'));
DEFINE('_LASTICMP', _('Last ICMP Events'));
DEFINE('_QUERYDB', _('Query DB'));
DEFINE('_QUERYDBP', _('Query+DB')); //Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', _('Delete Selected'));
DEFINE('_ALLONSCREEN', _('Delete ALL on Screen'));
DEFINE('_ENTIREQUERY', _('Delete Entire Query'));
DEFINE('_OPTIONS', _('Options'));
DEFINE('_LENGTH', _('length'));
DEFINE('_CODE', _('code'));
DEFINE('_DATA', _('data'));
DEFINE('_TYPE', _('type'));
DEFINE('_NEXT', _('Next'));
DEFINE('_PREVIOUS', _('Previous'));
//Menu items
DEFINE('_HOME', _('Clear'));
DEFINE('_SEARCH', _('Search'));
DEFINE('_AGMAINT', _('Event Group Maintenance'));
DEFINE('_USERPREF', _('User Preferences'));
DEFINE('_CACHE', _('Cache & Status'));
DEFINE('_ADMIN', _('Administration'));
DEFINE('_GALERTD', _('Graph Event Data'));
DEFINE('_GALERTDT', _('Graph Event Detection Time'));
DEFINE('_USERMAN', _('User Management'));
DEFINE('_LISTU', _('List users'));
DEFINE('_CREATEU', _('Create a user'));
DEFINE('_ROLEMAN', _('Role Management'));
DEFINE('_LISTR', _('List Roles'));
DEFINE('_CREATER', _('Create a Role'));
DEFINE('_LISTALL', _('List All'));
DEFINE('_CREATE', _('Create'));
DEFINE('_VIEW', _('View'));
DEFINE('_CLEAR', _('Clear'));
DEFINE('_LISTGROUPS', _('List Groups'));
DEFINE('_CREATEGROUPS', _('Create Group'));
DEFINE('_VIEWGROUPS', _('View Group'));
DEFINE('_EDITGROUPS', _('Edit Group'));
DEFINE('_DELETEGROUPS', _('Delete Group'));
DEFINE('_CLEARGROUPS', _('Clear Group'));
DEFINE('_CHNGPWD', _('Change password'));
DEFINE('_DISPLAYU', _('Display user'));
//base_footer.php
DEFINE('_FOOTER', _(' (by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> and the <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw )'));
//index.php --Log in Page
DEFINE('_LOGINERROR', _('User does not exist or your password was incorrect!<br>Please try again'));
// base_main.php
DEFINE('_MOSTRECENT', _('Most recent '));
DEFINE('_MOSTFREQUENT', _('Most frequent '));
DEFINE('_ALERTS', _(' Events:'));
DEFINE('_ADDRESSES', _(' Addresses'));
DEFINE('_ANYPROTO', _('any protocol'));
DEFINE('_UNI', _('unique'));
DEFINE('_LISTING', _('listing'));
DEFINE('_TALERTS', _('Today\'s event: '));
DEFINE('_SOURCEIP', _('Source IP'));
DEFINE('_DESTIP', _('Destination IP'));
DEFINE('_L24ALERTS', _('Last 24 Hours events: '));
DEFINE('_L72ALERTS', _('Last 72 Hours events: '));
DEFINE('_UNIALERTS', _(' Unique Events'));
DEFINE('_LSOURCEPORTS', _('Last Source Ports: '));
DEFINE('_LDESTPORTS', _('Last Destination Ports: '));
DEFINE('_FREGSOURCEP', _('Most Frequent Source Ports: '));
DEFINE('_FREGDESTP', _('Most Frequent Destination Ports: '));
DEFINE('_QUERIED', _('Queried on'));
DEFINE('_DATABASE', _('Database:'));
DEFINE('_SCHEMAV', _('Schema Version:'));
DEFINE('_TIMEWIN', _('Time Window:'));
DEFINE('_NOALERTSDETECT', _('no events detected'));
DEFINE('_USEALERTDB', _('Use Event Database'));
DEFINE('_USEARCHIDB', _('Use Archive Database'));
DEFINE('_TRAFFICPROBPRO', _('Traffic Profile by Protocol'));
//base_auth.inc.php
DEFINE('_ADDEDSF', _('Added Successfully'));
DEFINE('_NOPWDCHANGE', _('Unable to change your password: '));
DEFINE('_NOUSER', _('User doesn\'t exist!'));
DEFINE('_OLDPWD', _('Old password entered doesn\'t match our records!'));
DEFINE('_PWDCANT', _('Unable to change your password: '));
DEFINE('_PWDDONE', _('Your password has been changed!'));
DEFINE('_ROLEEXIST', _('Role Already Exists'));
DEFINE('_ROLEIDEXIST', _('Role ID Already Exists'));
DEFINE('_ROLEADDED', _('Role Added Successfully'));
//base_roleadmin.php
DEFINE('_ROLEADMIN', _('BASE Role Administration'));
DEFINE('_FRMROLEID', _('Role ID:'));
DEFINE('_FRMROLENAME', _('Role Name:'));
DEFINE('_FRMROLEDESC', _('Description:'));
DEFINE('_UPDATEROLE', _('Update Role'));
//base_useradmin.php
DEFINE('_USERADMIN', _('BASE User Administration'));
DEFINE('_FRMFULLNAME', _('Full Name:'));
DEFINE('_FRMROLE', _('Role:'));
DEFINE('_FRMUID', _('User ID:'));
DEFINE('_SUBMITQUERY', _('Submit Query'));
DEFINE('_UPDATEUSER', _('Update User'));
//admin/index.php
DEFINE('_BASEADMIN', _('BASE Administration'));
DEFINE('_BASEADMINTEXT', _('Please select an option from the left.'));
//base_action.inc.php
DEFINE('_NOACTION', _('No action was specified on the events'));
DEFINE('_INVALIDACT', _(' is an invalid action'));
DEFINE('_ERRNOAG', _('Could not add events since no AG was specified'));
DEFINE('_ERRNOEMAIL', _('Could not email events since no email address was specified'));
DEFINE('_ACTION', _('ACTION'));
DEFINE('_CONTEXT', _('context'));
DEFINE('_ADDAGID', _('ADD to AG (by ID)'));
DEFINE('_ADDAG', _('ADD-New-AG'));
DEFINE('_ADDAGNAME', _('ADD to AG (by Name)'));
DEFINE('_CREATEAG', _('Create AG (by Name)'));
DEFINE('_CLEARAG', _('Clear from AG'));
DEFINE('_DELETEALERT', _('Delete event(s)'));
DEFINE('_EMAILALERTSFULL', _('Email event(s) (full)'));
DEFINE('_EMAILALERTSSUMM', _('Email event(s) (summary)'));
DEFINE('_EMAILALERTSCSV', _('Email event(s) (csv)'));
DEFINE('_ARCHIVEALERTSCOPY', _('Archive event(s) (copy)'));
DEFINE('_ARCHIVEALERTSMOVE', _('Archive event(s) (move)'));
DEFINE('_IGNORED', _('Ignored '));
DEFINE('_DUPALERTS', _(' duplicate event(s)'));
DEFINE('_ALERTSPARA', _(' event(s)'));
DEFINE('_NOALERTSSELECT', _('No events were selected or the'));
DEFINE('_NOTSUCCESSFUL', _('was not successful'));
DEFINE('_ERRUNKAGID', _('Unknown AG ID specified (AG probably does not exist)'));
DEFINE('_ERRREMOVEFAIL', _('Failed to remove new AG'));
DEFINE('_GENBASE', _('Generated by BASE'));
DEFINE('_ERRNOEMAILEXP', _('EXPORT ERROR: Could not send exported events to'));
DEFINE('_ERRNOEMAILPHP', _('Check the mail configuration in PHP.'));
DEFINE('_ERRDELALERT', _('Error Deleting Event'));
DEFINE('_ERRARCHIVE', _('Archive error:'));
DEFINE('_ERRMAILNORECP', _('MAIL ERROR: No recipient Specified'));
//base_cache.inc.php
DEFINE('_ADDED', _('Added '));
DEFINE('_HOSTNAMESDNS', _(' hostnames to the IP DNS cache'));
DEFINE('_HOSTNAMESWHOIS', _(' hostnames to the Whois cache'));
DEFINE('_ERRCACHENULL', _('Caching ERROR: NULL event row found?'));
DEFINE('_ERRCACHEERROR', _('EVENT CACHING ERROR:'));
DEFINE('_ERRCACHEUPDATE', _('Could not update event cache'));
DEFINE('_ALERTSCACHE', _(' Event(s) to the Event cache'));
//base_db.inc.php
DEFINE('_ERRSQLTRACE', _('Unable to open SQL trace file'));
DEFINE('_ERRSQLCONNECT', _('Error connecting to DB :'));
DEFINE('_ERRSQLCONNECTINFO', _('<P>Check the DB connection variables in <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : MySQL database name where the alerts are stored 
               = $alert_host     : host where the database is stored
               = $alert_port     : port where the database is stored
               = $alert_user     : username into the database
               = $alert_password : password for the username
              </PRE>
              <P>'));
DEFINE('_ERRSQLPCONNECT', _('Error (p)connecting to DB :'));
DEFINE('_ERRSQLDB', _('Database ERROR:'));
DEFINE('_DBALCHECK', _('Checking for DB abstraction lib in'));
DEFINE('_ERRSQLDBALLOAD1', _('<P><B>Error loading the DB Abstraction library: </B> from '));
DEFINE('_ERRSQLDBALLOAD2', _('<P>Check the DB abstraction library variable <CODE>$DBlib_path</CODE> in <CODE>base_conf.php</CODE>
            <P>
            The underlying database library currently used is ADODB, that can be downloaded
            at <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>'));
DEFINE('_ERRSQLDBTYPE', _('Invalid Database Type Specified'));
DEFINE('_ERRSQLDBTYPEINFO1', _('The variable <CODE>\$DBtype</CODE> in <CODE>base_conf.php</CODE> was set to the unrecognized database type of '));
DEFINE('_ERRSQLDBTYPEINFO2', _('Only the following databases are supported: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>'));
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', _('BASE FATAL ERROR:'));
//base_log_timing.inc.php
DEFINE('_LOADEDIN', _('Loaded in'));
DEFINE('_SECONDS', _('seconds'));
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', _('Unable to resolve address'));
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', _('Query Results Output Header'));
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', _('SigName unknown'));
DEFINE('_ERRSIGPROIRITYUNK', _('SigPriority unknown'));
DEFINE('_UNCLASS', _('unclassified'));
//base_state_citems.inc.php
DEFINE('_DENCODED', _('data encoded as'));
DEFINE('_NODENCODED', _('(no data conversion, assuming criteria in DB native encoding)'));
DEFINE('_SHORTJAN', _('Jan'));
DEFINE('_SHORTFEB', _('Feb'));
DEFINE('_SHORTMAR', _('Mar'));
DEFINE('_SHORTAPR', _('Apr'));
DEFINE('_SHORTMAY', _('May'));
DEFINE('_SHORTJUN', _('Jun'));
DEFINE('_SHORTJLY', _('Jly'));
DEFINE('_SHORTAUG', _('Aug'));
DEFINE('_SHORTSEP', _('Sep'));
DEFINE('_SHORTOCT', _('Oct'));
DEFINE('_SHORTNOV', _('Nov'));
DEFINE('_SHORTDEC', _('Dec'));
DEFINE('_DISPSIG', _('{ signature }'));
DEFINE('_DISPANYCLASS', _('{ any Classification }'));
DEFINE('_DISPANYPRIO', _('{ any Priority }'));
DEFINE('_DISPANYSENSOR', _('{ any Sensor }'));
DEFINE('_DISPADDRESS', _('{ address }'));
DEFINE('_DISPFIELD', _('{ field }'));
DEFINE('_DISPPORT', _('{ port }'));
DEFINE('_DISPENCODING', _('{ encoding }'));
DEFINE('_DISPCONVERT2', _('{ Convert To }'));
DEFINE('_DISPANYAG', _('{ any Event Group }'));
DEFINE('_DISPPAYLOAD', _('{ payload }'));
DEFINE('_DISPFLAGS', _('{ flags }'));
DEFINE('_SIGEXACTLY', _('exactly'));
DEFINE('_SIGROUGHLY', _('roughly'));
DEFINE('_SIGCLASS', _('Signature Classification'));
DEFINE('_SIGPRIO', _('Signature Priority'));
DEFINE('_SHORTSOURCE', _('Source'));
DEFINE('_SHORTDEST', _('Dest'));
DEFINE('_SHORTSOURCEORDEST', _('Src or Dest'));
DEFINE('_NOLAYER4', _('no layer4'));
DEFINE('_INPUTCRTENC', _('Input Criteria Encoding Type'));
DEFINE('_CONVERT2WS', _('Convert To (when searching)'));
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', _('PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>'));
DEFINE('_PHPERRORCSESSIONCODE', _('PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.'));
DEFINE('_PHPERRORCSESSIONVAR', _('PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.'));
DEFINE('_PHPSESSREG', _('Session Registered'));
//base_state_criteria.inc.php
DEFINE('_REMOVE', _('Removing'));
DEFINE('_FROMCRIT', _('from criteria'));
DEFINE('_ERRCRITELEM', _('Invalid criteria element'));
//base_state_query.inc.php
DEFINE('_VALIDCANNED', _('Valid Canned Query List'));
DEFINE('_DISPLAYING', _('Displaying'));
DEFINE('_DISPLAYINGTOTAL', _('Displaying events %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALSENSOR', _('Displaying sensors %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUEVENTS', _('Displaying unique events %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUADDRSRC', _('Displaying unique source addresses %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUADDRDST', _('Displaying unique destination addresses %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUADDRESS', _('Displaying unique addresses %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUPLUGINS', _('Displaying unique data sources %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALUIPLINKS', _('Displaying unique ip links %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPSRC', _('Displaying source ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPTCPSRC', _('Displaying source tcp ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPUDPSRC', _('Displaying source udp ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPDST', _('Displaying destination ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPTCPDST', _('Displaying destination tcp ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));
DEFINE('_DISPLAYINGTOTALPUDPDST', _('Displaying destination udp ports %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database.'));

DEFINE('_NOALERTS', _('No Events were found.'));
DEFINE('_QUERYRESULTS', _('Query Results'));
DEFINE('_QUERYSTATE', _('Query State'));
DEFINE('_DISPACTION', _('{ action }'));
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', _('The specified AG name search is invalid.  Try again!'));
DEFINE('_ERRAGNAMEEXIST', _('The specified AG does not exist.'));
DEFINE('_ERRAGIDSEARCH', _('The specified AG ID search is invalid.  Try again!'));
DEFINE('_ERRAGLOOKUP', _('Error looking up an AG ID'));
DEFINE('_ERRAGINSERT', _('Error Inserting new AG'));
//base_ag_main.php
DEFINE('_AGMAINTTITLE', _('Event Group (AG) Maintenance'));
DEFINE('_ERRAGUPDATE', _('Error updating the AG'));
DEFINE('_ERRAGPACKETLIST', _('Error deleting packet list for the AG:'));
DEFINE('_ERRAGDELETE', _('Error deleting the AG'));
DEFINE('_AGDELETE', _('DELETED successfully'));
DEFINE('_AGDELETEINFO', _('information deleted'));
DEFINE('_ERRAGSEARCHINV', _('The entered search criteria is invalid.  Try again!'));
DEFINE('_ERRAGSEARCHNOTFOUND', _('No AG found with that criteria.'));
DEFINE('_NOALERTGOUPS', _('There are no Event Groups'));
DEFINE('_NUMALERTS', _('# Events'));
DEFINE('_ACTIONS', _('Actions'));
DEFINE('_NOTASSIGN', _('not assigned yet'));
DEFINE('_SAVECHANGES', _('Save Changes'));
DEFINE('_CONFIRMDELETE', _('Confirm Delete'));
DEFINE('_CONFIRMCLEAR', _('Confirm Clear'));
//base_common.php
DEFINE('_PORTSCAN', _('Portscan Traffic'));
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', _('Unable to CREATE INDEX for'));
DEFINE('_DBINDEXCREATE', _('Successfully created INDEX for'));
DEFINE('_ERRSNORTVER', _('It might be an older version.  Only alert databases created by Snort 1.7-beta0 or later are supported'));
DEFINE('_ERRSNORTVER1', _('The underlying database'));
DEFINE('_ERRSNORTVER2', _('appears to be incomplete/invalid'));
DEFINE('_ERRDBSTRUCT1', _('The database version is valid, but the BASE DB structure'));
DEFINE('_ERRDBSTRUCT2', _('is not present. Use the <A HREF="base_db_setup.php">Setup page</A> to configure and optimize the DB.'));
DEFINE('_ERRPHPERROR', _('PHP ERROR'));
DEFINE('_ERRPHPERROR1', _('Incompatible version'));
DEFINE('_ERRVERSION', _('Version'));
DEFINE('_ERRPHPERROR2', _('of PHP is too old.  Please upgrade to version 4.0.4 or later'));
DEFINE('_ERRPHPMYSQLSUP', _('<B>PHP build incomplete</B>: <FONT>the prerequisite MySQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-mysql</CODE>)</FONT>'));
DEFINE('_ERRPHPPOSTGRESSUP', _('<B>PHP build incomplete</B>: <FONT>the prerequisite PostgreSQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-pgsql</CODE>)</FONT>'));
DEFINE('_ERRPHPMSSQLSUP', _('<B>PHP build incomplete</B>: <FONT>the prerequisite MS SQL Server support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--enable-mssql</CODE>)</FONT>'));
DEFINE('_ERRPHPORACLESUP', _('<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>'));
//base_graph_form.php
DEFINE('_CHARTTITLE', _('Chart Title:'));
DEFINE('_CHARTTYPE', _('Chart Type:'));
DEFINE('_CHARTTYPES', _('{ chart type }'));
DEFINE('_CHARTPERIOD', _('Chart Period:'));
DEFINE('_PERIODNO', _('no period'));
DEFINE('_PERIODWEEK', _('7 (a week)'));
DEFINE('_PERIODDAY', _('24 (whole day)'));
DEFINE('_PERIOD168', _('168 (24x7)'));
DEFINE('_CHARTSIZE', _('Size: (width x height)'));
DEFINE('_PLOTMARGINS', _('Plot Margins: (left x right x top x bottom)'));
DEFINE('_PLOTTYPE', _('Plot type:'));
DEFINE('_TYPEBAR', _('bar'));
DEFINE('_TYPELINE', _('line'));
DEFINE('_TYPEPIE', _('pie'));
DEFINE('_CHARTHOUR', _('{hour}'));
DEFINE('_CHARTDAY', _('{day}'));
DEFINE('_CHARTMONTH', _('{month}'));
DEFINE('_GRAPHALERTS', _('Graph Events'));
DEFINE('_AXISCONTROLS', _('X / Y AXIS CONTROLS'));
DEFINE('_CHRTTYPEHOUR', _('Time (hour) vs. Number of Events'));
DEFINE('_CHRTTYPEDAY', _('Time (day) vs. Number of Events'));
DEFINE('_CHRTTYPEWEEK', _('Time (week) vs. Number of Events'));
DEFINE('_CHRTTYPEMONTH', _('Time (month) vs. Number of Events'));
DEFINE('_CHRTTYPEYEAR', _('Time (year) vs. Number of Events'));
DEFINE('_CHRTTYPESRCIP', _('Src. IP address vs. Number of Events'));
DEFINE('_CHRTTYPEDSTIP', _('Dst. IP address vs. Number of Events'));
DEFINE('_CHRTTYPEDSTUDP', _('Dst. UDP Port vs. Number of Events'));
DEFINE('_CHRTTYPESRCUDP', _('Src. UDP Port vs. Number of Events'));
DEFINE('_CHRTTYPEDSTPORT', _('Dst. TCP Port vs. Number of Events'));
DEFINE('_CHRTTYPESRCPORT', _('Src. TCP Port vs. Number of Events'));
DEFINE('_CHRTTYPESIG', _('Sig. Classification vs. Number of Events'));
DEFINE('_CHRTTYPESENSOR', _('Sensor vs. Number of Events'));
DEFINE('_CHRTBEGIN', _('Chart Begin:'));
DEFINE('_CHRTEND', _('Chart End:'));
DEFINE('_CHRTDS', _('Data Source:'));
DEFINE('_CHRTX', _('X Axis'));
DEFINE('_CHRTY', _('Y Axis'));
DEFINE('_CHRTMINTRESH', _('Minimum Threshold Value'));
DEFINE('_CHRTROTAXISLABEL', _('Rotate Axis Labels (90 degrees)'));
DEFINE('_CHRTSHOWX', _('Show X-axis grid-lines'));
DEFINE('_CHRTDISPLABELX', _('Display X-axis label every'));
DEFINE('_CHRTDATAPOINTS', _('data points'));
DEFINE('_CHRTYLOG', _('Y-axis logarithmic'));
DEFINE('_CHRTYGRID', _('Show Y-axis grid-lines'));
//base_graph_main.php
DEFINE('_CHRTTITLE', _('BASE Chart'));
DEFINE('_ERRCHRTNOTYPE', _('No chart type was specified'));
DEFINE('_ERRNOAGSPEC', _('No AG was specified.  Using all events.'));
DEFINE('_CHRTDATAIMPORT', _('Starting data import'));
DEFINE('_CHRTTIMEVNUMBER', _('Time vs. Number of Events'));
DEFINE('_CHRTTIME', _('Time'));
DEFINE('_CHRTALERTOCCUR', _('Event Occurrences'));
DEFINE('_CHRTSIPNUMBER', _('Source IP vs. Number of Events'));
DEFINE('_CHRTSIP', _('Source IP Address'));
DEFINE('_CHRTDIPALERTS', _('Destination IP vs. Number of Events'));
DEFINE('_CHRTDIP', _('Destination IP Address'));
DEFINE('_CHRTUDPPORTNUMBER', _('UDP Port (Destination) vs. Number of Events'));
DEFINE('_CHRTDUDPPORT', _('Dst. UDP Port'));
DEFINE('_CHRTSUDPPORTNUMBER', _('UDP Port (Source) vs. Number of Events'));
DEFINE('_CHRTSUDPPORT', _('Src. UDP Port'));
DEFINE('_CHRTPORTDESTNUMBER', _('TCP Port (Destination) vs. Number of Events'));
DEFINE('_CHRTPORTDEST', _('Dst. TCP Port'));
DEFINE('_CHRTPORTSRCNUMBER', _('TCP Port (Source) vs. Number of Events'));
DEFINE('_CHRTPORTSRC', _('Src. TCP Port'));
DEFINE('_CHRTSIGNUMBER', _('Signature Classification vs. Number of Events'));
DEFINE('_CHRTCLASS', _('Classification'));
DEFINE('_CHRTSENSORNUMBER', _('Sensor vs. Number of Events'));
DEFINE('_CHRTHANDLEPERIOD', _('Handling Period if necessary'));
DEFINE('_CHRTDUMP', _('Dumping data ... (writing only every'));
DEFINE('_CHRTDRAW', _('Drawing graph'));
DEFINE('_ERRCHRTNODATAPOINTS', _('No data points to plot'));
DEFINE('_GRAPHALERTDATA', _('Graph Event Data'));
//base_maintenance.php
DEFINE('_MAINTTITLE', _('Maintenance'));
DEFINE('_MNTPHP', _('PHP Build:'));
DEFINE('_MNTCLIENT', _('CLIENT:'));
DEFINE('_MNTSERVER', _('SERVER:'));
DEFINE('_MNTSERVERHW', _('SERVER HW:'));
DEFINE('_MNTPHPVER', _('PHP VERSION:'));
DEFINE('_MNTPHPAPI', _('PHP API:'));
DEFINE('_MNTPHPLOGLVL', _('PHP Logging level:'));
DEFINE('_MNTPHPMODS', _('Loaded Modules:'));
DEFINE('_MNTDBTYPE', _('DB Type:'));
DEFINE('_MNTDBALV', _('DB Abstraction Version:'));
DEFINE('_MNTDBALERTNAME', _('ALERT DB Name:'));
DEFINE('_MNTDBARCHNAME', _('ARCHIVE DB Name:'));
DEFINE('_MNTAIC', _('Event Information Cache:'));
DEFINE('_MNTAICTE', _('Total Events:'));
DEFINE('_MNTAICCE', _('Cached Events:'));
DEFINE('_MNTIPAC', _('IP Address Cache'));
DEFINE('_MNTIPACUSIP', _('Unique Src IP:'));
DEFINE('_MNTIPACDNSC', _('DNS Cached:'));
DEFINE('_MNTIPACWC', _('Whois Cached:'));
DEFINE('_MNTIPACUDIP', _('Unique Dst IP:'));
//base_qry_alert.php
DEFINE('_QAINVPAIR', _('Invalid (sid,cid) pair'));
DEFINE('_QAALERTDELET', _('Event DELETED'));
DEFINE('_QATRIGGERSIG', _('Triggered Signature'));
DEFINE('_QANORMALD', _('Normal Display'));
DEFINE('_QAPLAIND', _('Plain Display'));
DEFINE('_QANOPAYLOAD', _('Fast logging used so payload was discarded'));
//base_qry_common.php
DEFINE('_QCSIG', _('signature'));
DEFINE('_QCIPADDR', _('IP addresses'));
DEFINE('_QCIPFIELDS', _('IP fields'));
DEFINE('_QCTCPPORTS', _('TCP ports'));
DEFINE('_QCTCPFLAGS', _('TCP flags'));
DEFINE('_QCTCPFIELD', _('TCP fields'));
DEFINE('_QCUDPPORTS', _('UDP ports'));
DEFINE('_QCUDPFIELDS', _('UDP fields'));
DEFINE('_QCICMPFIELDS', _('ICMP fields'));
DEFINE('_QCDATA', _('Data'));
DEFINE('_QCERRCRITWARN', _('Criteria warning:'));
DEFINE('_QCERRVALUE', _('A value of'));
DEFINE('_QCERRFIELD', _('A field of'));
DEFINE('_QCERROPER', _('An operator of'));
DEFINE('_QCERRDATETIME', _('A date/time value of'));
DEFINE('_QCERRPAYLOAD', _('A payload value of'));
DEFINE('_QCERRIP', _('A IP address of'));
DEFINE('_QCERRIPTYPE', _('An IP address of type'));
DEFINE('_QCERRSPECFIELD', _(' was entered for a protocol field, but the particular field was not specified.'));
DEFINE('_QCERRSPECVALUE', _('was selected indicating that it should be a criteria, but no value was specified on which to match.'));
DEFINE('_QCERRBOOLEAN', _('Multiple protocol field criteria entered without a boolean operator (e.g. AND, OR) between them.'));
DEFINE('_QCERRDATEVALUE', _('was selected indicating that some date/time criteria should be matched, but no value was specified.'));
DEFINE('_QCERRINVHOUR', _('(Invalid Hour) No date criteria were entered with the specified time.'));
DEFINE('_QCERRDATECRIT', _('was selected indicating that some date/time criteria should be matched, but no value was specified.'));
DEFINE('_QCERROPERSELECT', _('was entered but no operator was selected.'));
DEFINE('_QCERRDATEBOOL', _('Multiple Date/Time criteria entered without a boolean operator (e.g. AND, OR) between them.'));
DEFINE('_QCERRPAYCRITOPER', _('was entered for a payload criteria field, but an operator (e.g. has, has not) was not specified.'));
DEFINE('_QCERRPAYCRITVALUE', _('was selected indicating that payload should be a criteria, but no value on which to match was specified.'));
DEFINE('_QCERRPAYBOOL', _('Multiple Data payload criteria entered without a boolean operator (e.g. AND, OR) between them.'));
DEFINE('_QCMETACRIT', _('Meta Criteria'));
DEFINE('_QCIPCRIT', _('IP Criteria'));
DEFINE('_QCPAYCRIT', _('Payload Criteria'));
DEFINE('_QCTCPCRIT', _('TCP Criteria'));
DEFINE('_QCUDPCRIT', _('UDP Criteria'));
DEFINE('_QCICMPCRIT', _('ICMP Criteria'));
DEFINE('_QCLAYER4CRIT', _('Layer 4 Criteria'));
DEFINE('_QCERRINVIPCRIT', _('Invalid IP address criteria'));
DEFINE('_QCERRCRITADDRESSTYPE', _('was entered for as a criteria value, but the type of address (e.g. source, destination) was not specified.'));
DEFINE('_QCERRCRITIPADDRESSNONE', _('indicating that an IP address should be a criteria, but no address on which to match was specified.'));
DEFINE('_QCERRCRITIPADDRESSNONE1', _('was selected (at #'));
DEFINE('_QCERRCRITIPIPBOOL', _('Multiple IP address criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria'));
//base_qry_form.php
DEFINE('_QFRMSORTORDER', _('Sort order'));
DEFINE('_QFRMSORTNONE', _('none'));
DEFINE('_QFRMTIMEA', _('timestamp (ascend)'));
DEFINE('_QFRMTIMED', _('timestamp (descend)'));
DEFINE('_QFRMSIG', _('signature'));
DEFINE('_QFRMSIP', _('source IP'));
DEFINE('_QFRMDIP', _('dest. IP'));
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', _('Summary Statistics'));
DEFINE('_QSCTIMEPROF', _('Time profile'));
DEFINE('_QSCOFALERTS', _('of events'));
//base_stat_alerts.php
DEFINE('_ALERTTITLE', _('Event Listing'));
//base_stat_common.php
DEFINE('_SCCATEGORIES', _('Categories:'));
DEFINE('_SCSENSORTOTAL', _('Sensors/Total:'));
DEFINE('_SCTOTALNUMALERTS', _('Total Number of Events:'));
DEFINE('_SCSRCIP', _('Src IP addrs:'));
DEFINE('_SCDSTIP', _('Dest. IP addrs:'));
DEFINE('_SCUNILINKS', _('Unique IP links'));
DEFINE('_SCSRCPORTS', _('Source Ports: '));
DEFINE('_SCDSTPORTS', _('Dest Ports: '));
DEFINE('_SCSENSORS', _('Sensors'));
DEFINE('_SCCLASS', _('classifications'));
DEFINE('_SCUNIADDRESS', _('Unique addresses: '));
DEFINE('_SCSOURCE', _('Source'));
DEFINE('_SCDEST', _('Destination'));
DEFINE('_SCPORT', _('Port'));
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', _('PORTSCAN EVENT ERROR: '));
DEFINE('_PSEVENTERRNOFILE', _('No file was specified in the \$portscan_file variable.'));
DEFINE('_PSEVENTERROPENFILE', _('Unable to open Portscan event file'));
DEFINE('_PSDATETIME', _('Date/Time'));
DEFINE('_PSSRCIP', _('Source IP'));
DEFINE('_PSDSTIP', _('Destination IP'));
DEFINE('_PSSRCPORT', _('Source Port'));
DEFINE('_PSDSTPORT', _('Destination Port'));
DEFINE('_PSTCPFLAGS', _('TCP Flags'));
DEFINE('_PSTOTALOCC', _('Total<BR> Occurrences'));
DEFINE('_PSNUMSENSORS', _('Num of Sensors'));
DEFINE('_PSFIRSTOCC', _('First<BR> Occurrence'));
DEFINE('_PSLASTOCC', _('Last<BR> Occurrence'));
DEFINE('_PSUNIALERTS', _('Unique Events'));
DEFINE('_PSPORTSCANEVE', _('Portscan Events'));
DEFINE('_PSREGWHOIS', _('Registry lookup (whois) in'));
DEFINE('_PSNODNS', _('no DNS resolution attempted'));
DEFINE('_PSNUMSENSORSBR', _('Num of <BR>Sensors'));
DEFINE('_PSOCCASSRC', _('Occurances <BR>as Src.'));
DEFINE('_PSOCCASDST', _('Occurances <BR>as Dest.'));
DEFINE('_PSWHOISINFO', _('Whois Information'));
DEFINE('_PSTOTALHOSTS', _('Total Hosts Scanned'));
DEFINE('_PSDETECTAMONG', _('%d unique events detected among %d events on %s'));
DEFINE('_PSALLALERTSAS', _('all events with %s/%s as'));
DEFINE('_PSSHOW', _('show'));
DEFINE('_PSEXTERNAL', _('external'));
//base_stat_iplink.php
DEFINE('_SIPLTITLE', _('IP Links'));
DEFINE('_SIPLSOURCEFGDN', _('Source FQDN'));
DEFINE('_SIPLDESTFGDN', _('Destination FQDN'));
DEFINE('_SIPLDIRECTION', _('Direction'));
DEFINE('_SIPLPROTO', _('Protocol'));
DEFINE('_SIPLUNIDSTPORTS', _('Unique Dst Ports'));
DEFINE('_SIPLUNIEVENTS', _('Unique Events'));
DEFINE('_SIPLTOTALEVENTS', _('Total Events'));
//base_stat_ports.php
DEFINE('_UNIQ', _('Unique'));
DEFINE('_DSTPS', _('Destination Port(s)'));
DEFINE('_SRCPS', _('Source Port(s)'));
DEFINE('_OCCURRENCES', _('Occurrences'));
//base_stat_sensor.php
DEFINE('SPSENSORLIST', _('Sensor Listing'));
//base_stat_time.php
DEFINE('_BSTTITLE', _('Time Profile of Events'));
DEFINE('_BSTTIMECRIT', _('Time Criteria'));
DEFINE('_BSTERRPROFILECRIT', _('<FONT><B>No profiling criteria was specified!</B>  Click on "hour", "day", or "month" to choose the granularity of the aggregate statistics.</FONT>'));
DEFINE('_BSTERRTIMETYPE', _('<FONT><B>The type of time parameter which will be passed was not specified!</B>  Choose either "on", to specify a single date, or "between" to specify an interval.</FONT>'));
DEFINE('_BSTERRNOYEAR', _('<FONT><B>No Year parameter was specified!</B></FONT>'));
DEFINE('_BSTERRNOMONTH', _('<FONT><B>No Month parameter was specified!</B></FONT>'));
DEFINE('_BSTERRNODAY', _('<FONT><B>No Day parameter was specified!</B></FONT>'));
DEFINE('_BSTPROFILEBY', _('Profile by'));
DEFINE('_TIMEON', _('on'));
DEFINE('_TIMEBETWEEN', _('between'));
DEFINE('_PROFILEALERT', _('Profile Event'));
//base_stat_uaddr.php
DEFINE('_UNISADD', _('Unique Source Address(es)'));
DEFINE('_SUASRCIP', _('Src IP address'));
DEFINE('_SUAERRCRITADDUNK', _('CRITERIA ERROR: unknown address type -- assuming Dst address'));
DEFINE('_UNIDADD', _('Unique Destination Address(es)'));
DEFINE('_SUADSTIP', _('Dst IP address'));
DEFINE('_SUAUNIALERTS', _('Unique&nbsp;Events'));
DEFINE('_SUASRCADD', _('Src.&nbsp;Addr.'));
DEFINE('_SUADSTADD', _('Dest.&nbsp;Addr.'));
//base_user.php
DEFINE('_BASEUSERTITLE', _('BASE User preferences'));
DEFINE('_BASEUSERERRPWD', _('Your password can not be blank or the two passwords did not match!'));
DEFINE('_BASEUSEROLDPWD', _('Old Password:'));
DEFINE('_BASEUSERNEWPWD', _('New Password:'));
DEFINE('_BASEUSERNEWPWDAGAIN', _('New Password Again:'));
DEFINE('_LOGOUT', _('Logout'));
?>
