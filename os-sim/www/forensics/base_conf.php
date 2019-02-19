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


if (count($argv)==0) 
{
    require_once 'av_init.php';
    Session::logcheck("analysis-menu", "EventsForensics");
}
$BASE_VERSION = '';
/*
Set the below to the language you would like people to use while viewing
your install of BASE.
*/
$BASE_Language = 'english';
/*
Set the $Use_Auth_System variable to 1 if you would like to force users to
authenticate to use the system.  Only turn this off if the system is not
accessible to the public or the network at large.  i.e. a home user testing it
out!
*/
$Use_Auth_System = 0;
/*
Set the below to 0 to remove the links from the display of alerts.
*/
$BASE_display_sig_links = 1;
/*
Set the base_urlpath to the url location that is the root of your BASE install.
This must be set for BASE to function! Do not include a trailing slash!
But also put the preceding slash. e.g. Your URL is http://127.0.0.1/base
set this to /base
*/
$BASE_urlpath = '/ossim/forensics'; /* WARNING. must not end with slash / */
/* Unique BASE ID.  The below variable, if set, will append its value to the
* title bar of the browser.  This is for people who manage multiple installs
* of BASE and want a simple way to differentiate them on the task bar.
*/
$BASE_installID = '';
/* Custom footer addition.  The below variable, if set, will cause
*  base_main.php to include what ever file is specified.
*  A sample custom footer file is in the contrib directory
*/
$base_custom_footer = '';
/* Path to the DB abstraction library
*  (Note: DO NOT include a trailing backslash after the directory)
*   e.g. $foo = '/tmp'      [OK]
*        $foo = '/var/tmp/'     [OK]
*        $foo = 'c:\tmp'    [OK]
*        $foo = 'c:\tmp\'   [WRONG]
*/
$DBlib_path = '/usr/share/php/adodb';
/* The type of underlying alert database
*
*  MySQL       : 'mysql'
*  PostgresSQL : 'postgres'
*  MS SQL Server : 'mssql'
*  Oracle      : 'oci8'
*/
/* Alert DB connection parameters
*   - $alert_dbname   : MySQL database name of Snort alert DB
*   - $alert_host     : host on which the DB is stored
*   - $alert_port     : port on which to access the DB
*   - $alert_user     : login to the database with this user
*   - $alert_password : password of the DB user
*
*  This information can be gleaned from the Snort database
*  output plugin configuration.
*/
//##### Begin of variables configured through dbconfig-common
$alert_user     = trim(Util::execute_command('grep ^ossim_user /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));
$alert_password = trim(Util::execute_command('grep ^ossim_pass /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));
$basepath       = '';
$alert_dbname   = 'alienvault_siem';
$alert_host     = trim(Util::execute_command('grep ^ossim_host /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));
$alert_port     = '';
$DBtype         = 'mysqli';

// Adjust dbconfig-common names
if ($DBtype == 'pgsql') $DBtype = 'postgres';
//##### End of variables configured through dbconfig-common
/* Archive DB connection parameters */
$archive_exists = 0; // Set this to 1 if you have an archive DB
$archive_dbname = 'alienvault_siem';
$archive_host = 'localhost';
$archive_port = '';
$archive_user = 'root';
// Fortify alert on empty password
//$archive_password = '';
/* Type of DB connection to use
*   1  : use a persistant connection (pconnect)
*   2  : use a normal connection (connect)
*/
$db_connect_method = 2;
$db_memcache = 60; // 0 do not use memcache
/* Use referential integrity
*   1  : use
*   0  : ignore (not installed)
*
* Note: Only PostgreSQL and MS-SQL Server databases support
*       referential integrity.  Use the associated
*       create_acid_tbls_?_extra.sql script to add this
*       functionality to the database.
*
*       Referential integrity will greatly improve the
*       speed of record deletion, but also slow record
*       insertion.
*/
$use_referential_integrity = 0;
/* Use OSSIM session
*   1  : use
*   0  : ignore
* Ossim framework and acid must be instaled in the same host
*/
$use_ossim_session = 1;
$ossim_login_path = "/ossim/session/login.php";
$ossim_acid_aco_section = "analysis-menu";
$ossim_acid_aco = "EventsForensics";
$ossim_domain_aco_section = "DomainAccess";
$ossim_domain_aco = "Nets";
/* Variable to start the ability to handle themes... */

/* Chart default colors - (red, green, blue)
*    - $chart_bg_color_default    : background color of chart
*    - $chart_lgrid_color_default : gridline color of chart
*    - $chart_bar_color_default   : bar/line color of chart
*/
$chart_bg_color_default = array(
    255,
    255,
    255
);
$chart_lgrid_color_default = array(
    205,
    205,
    205
);
$chart_bar_color_default = array(
    190,
    5,
    5
);
/* Maximum number of rows per criteria element */
$MAX_ROWS = 10;
/* Number of rows to display for any query results */
$show_rows = (preg_match('/base_stat_.*/', $_SERVER['SCRIPT_NAME'])) ? 25 : 50;
/* Number of items to return during a snapshot
*  Last _X_ # of alerts/unique alerts/ports/IP
*/
$last_num_alerts = 15;
$last_num_ualerts = 15;
$last_num_uports = 15;
$last_num_uaddr = 15;
/* Number of items to return during a snapshot
*  Most Frequent unique alerts/IPs/ports
*/
$freq_num_alerts = 5;
$freq_num_uaddr = 15;
$freq_num_uports = 15;
/* Number of scroll buttons to use when displaying query results */
$max_scroll_buttons = 12;
/* Debug mode     - how much debugging information should be shown
* Timing mode    - display timing information
* SQL trace mode - log SQL statements
*   0 : no extra information
*   1 : debugging information
*   2 : extended debugging information
*
* HTML no cache - whether a no-cache directive should be sent
*                 to the browser (should be = 1 for IE)
*
* SQL trace file - file to log SQL traces
*/
$debug_mode = 0;
$debug_time_mode = 1;
$html_no_cache = 1;
$sql_trace_mode = 0;
$sql_trace_file = '/var/tmp/debug_sql';
/* Auto-Screen refresh
* - Refresh_Stat_Page - Should certain statistics pages refresh?
* - Stat_Page_Refresh_Time - refresh interval (in seconds)
*/
$refresh_stat_page = 1;
$stat_page_refresh_time = 180;
/* Display First/Previous/Last timestamps for alerts or
* just First/Last on the Unique Alert listing.
*    1: yes
*    0: no
*/
$show_previous_alert = 0;
/* Sets maximum execution time (in seconds) of any particular page.
* Note: this overrides the PHP configuration file variable
*       max_execution_time.  Thus script can run for a total of
*       ($max_script_runtime + max_execution_time) seconds
*/
$max_script_runtime = 900;
/* How should the IP address criteria be entered in the Search screen?
*   1 : each octet is a separate field
*   2 : entire address is as a single field
*/
$ip_address_input = 2;
/* Should a combo box with possible signatures be displayed on the
* search form. (Requires Javascript)
*   0 : disabled
*   1 : show only non pre-processor signatures (e.g., ignore portscans)
*   2 : show all signatures
*/
$use_sig_list = 0;
/* Resolve IP to FQDN (on certain queries?)
*    1 : yes
*    0 : no
*/
$resolve_IP = 0;
/* automatically expand the IP Criteria and Payload Criteria sections on the Search screen?)
*    1 : yes
*    0 : no - you need to click on them to see them
*/
$show_expanded_query = 0;
/* Should summary stats be calculated on every Query Results page
* (Enabling this option will slow page loading time)
*/
$show_summary_stats = 0;
/* DNS cache lifetime (in minutes) */
$dns_cache_lifetime = 10080;
/* Whois information cache lifetime (in minutes) */
$whois_cache_lifetime = 40320;
/* Snort spp_portscan log file */
$portscan_file = '';
/* Show part of portscan payload in signature */
$portscan_payload_in_signature = '1';
/* Event cache Auto-update
*
*  Should the event cache be verified and updated on every
*  page log?  Otherwise, the cache will have to be explicitly
*  updated from the 'cache and status' page.
*
*  Note: enabling this option could substantially slow down
*  the page loading time when there are many uncached alerts.
*  However, this is only a one-time penalty.
*
*   1 : yes
*   0 : no
*/
$event_cache_auto_update = 1;
/* Maintain a history of the visited pages so that the "Back"
* button can be used.
*
* Note: Enabling this option will cause the PHP-session to
* grow substantially after many pages have been viewed causing
* a slow down in page loading time. Periodically return to the
* main page to clear the history.
*
*   1 : yes
*   0 : no
*/
$maintain_history = 0;
/* Level of detail to display on the main page.
*
* Note: The presence of summary statistics will slow page loading time
*
*   1 : show both the links and summary statistics
*   0 : show only the links and a count of the number of alerts
*/
$main_page_detail = 1;
/* avoid count(*) whenever possible
*
* Note: On some databases (e.g., postgres) this can greatly increase
* performance if you have a large number of events. On other databases
* (e.g., mysql) this will have little to no effect. Enabling this
* option will prevent the number of events in the database from being
* shown on the main screen and will remove the percentages associated
* with the number of events on the alert screen.
*/
$avoid_counts = 0;
/* show links to first/last/previous event on alert screen
*
* Note: Enabling this can slow down loading of the alert screen on large
* databases
*/
$show_first_last_links = 0;
/*
* External URLs
*/
/* Whois query */
$external_whois_link = 'http://www.dnsstuff.com/tools/whois/?ip=';
/* Alternative query */
//  $external_whois_link = 'http://www.samspade.org/t/ipwhois?a=';
/* DNS query */
$external_dns_link = 'http://www.dnsstuff.com/tools/ipall/?ip=';
/* Alternative query */
//  $external_dns_link = 'http://www.samspade.org/t/dns?a=';
/* SamSpade "all" query */
$external_all_link = 'http://www.whois.sc/';
/* TCP/UDP port database */
$external_port_link = array(
    'sans' => 'http://isc.sans.org/port_details.php?port=',
    'tantalo' => 'http://ports.tantalo.net/?q=',
    'sstats' => 'http://www.securitystats.com/tools/portsearch.php?type=port&select=any&Submit=Submit&input='
);
/* Signature references */
$external_sig_link = array(
    'bugtraq' => array(
        'http://www.securityfocus.com/bid/',
        '',''
    ) ,
    'snort' => array(
        'http://www.snort.org/pub-bin/sigs.cgi?sid=',
        '',''
    ) ,
    'cve' => array(
        'http://cve.mitre.org/cgi-bin/cvename.cgi?name=',
        '',''
    ) ,
    'mcafee' => array(
        'http://vil.nai.com/vil/content/v_',
        '.htm',''
    ) ,
    'icat' => array(
        'http://nvd.nist.gov/nvd.cfm?cvename=CAN-',
        '',''
    ) ,
    'nessus' => array(
        'http://www.nessus.org/plugins/index.php?view=single&id=',
        '',''
    ) ,
    'kdb' => array(
        Menu::get_menu_url('../repository/index.php', 'configuration', 'threat_intelligence', 'knowledgebase'),
        '','main'
    ) ,
    'url' => array(
        'http://',
        '',''
    ) ,
    'local' => array(
        'signatures/',
        '.txt',''
    )
);
// No longer valid:
// 'arachnids' => array('http://www.whitehats.com/info/ids', ''),
/* Email Alert action
*
* - action_email_from : email address to use in the FROM field of the mail message
* - action_email_subject : subject to use for the mail message
* - action_email_msg : additional text to include in the body of the mail message
* - action_email_mode : specifies how the alert information should be enclosed
*     0 : alerts should be in the body of the message
*     1 : alerts should be enclosed in an attachment
*/
$action_email_from = 'BASE Alert <base>';
$action_email_subject = 'BASE Incident Report';
$action_email_msg = '';
$action_email_mode = 0;
/* Custom (user) PHP session handlers
*
* - use_user_session : sets whether user PHP session can be used (configured
*                      with the session.save_handler variable in php.ini)
*      0 : no
*      1 : yes (assuming that 'user_session_path' and 'user_session_function'
*               are configured correctly)
* - user_session_path : file to include that implements the custom PHP session
*                       handler
* - user_session_function : function to invoke in the custom session
*                           implementation that will register the session handler
*                           functions
*/
$use_user_session = 0;
$user_session_path = '';
$user_session_function = '';
/**
 * This option is used to set if BASE will use colored results
 * based on the priority of alerts
 * 0 : no
 * 1 : yes
 */
$colored_alerts = 0;
// Red, yellow, orange, gray, white, blue
$priority_colors = array(
    'FF0000',
    'FFFF00',
    'FF9900',
    '999999',
    'FFFFFF',
    '006600'
);
$Geo_IPfree_file_ascii = "/usr/share/ossim/www/forensics/ips-ascii.txt";


$otx_pulse_url  = Otx::OTX_URL . "pulse/__PULSEID__" . Otx::get_anchor();
$otx_detail_url = AV_MAIN_PATH . "/otx/views/view_my_pulses.php?type=event&id=__EVENTID__";
$otx_unknown    = _('No information available. You are no longer subscribed to this pulse.');
$otx_plugin_id  = 1701;

$gmaps_url      = "https://maps.google.com/maps/@__LAT__,__LONG__,10z";

/*
The below line should not be changed!
*/
$BASE_path = '/usr/share/ossim/www/forensics/';
// _BASE_INC is a variable set to prevent direct access to certain include files....
define("_BASE_INC", 1);
// Include for languages
require ("$BASE_path/languages/$BASE_Language.lang.php");
?>
