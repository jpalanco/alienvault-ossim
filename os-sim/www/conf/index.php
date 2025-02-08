<?php
/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2013 AlienVault
 * All rights reserved.
 *
 * This package is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 dated June, 1991.
 * You may not use, modify or distribute this program under any other version
 * of the GNU General Public License.
 *
 * This package is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this package; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA  02110-1301  USA
 *
 *
 * On Debian GNU/Linux systems, the complete text of the GNU General
 * Public License can be found in `/usr/share/common-licenses/GPL-2'.
 *
 * Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */


/**
 * Function list:
 * - valid_value()
 * - submit()
 */

require_once 'av_init.php';
require_once 'languages.inc';

if (!Session::am_i_admin())
{
    echo ossim_error(_("You don't have permission to see this page"));

    exit();
}


$tz = Util::get_timezone();

$ossim_conf     = $GLOBALS['CONF'];

$section        = (POST('section') != '') ? POST('section') : GET('section');
$flag_status    = GET('status');
$flag_reconfig  = GET('reconfig');
$error_string   = GET('error');
$warning_string = GET('warning');
$word           = (POST('word') != '') ? POST('word') : ((GET('word') != '') ? GET('word') : '');
$restart_server = 0;

ossim_valid($section, OSS_ALPHA, OSS_NULLABLE,                                                                          'illegal:' . _('Section'));
ossim_valid($flag_status, OSS_DIGIT, OSS_NULLABLE,                                                                      'illegal:' . _('Flag status'));
ossim_valid($flag_reconfig, OSS_DIGIT, OSS_NULLABLE,                                                                    'illegal:' . _('Flag reconfig'));
ossim_valid($warning_string, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, OSS_SPACE, OSS_COLON, OSS_SCORE, '\.,\/\(\)\[\]\'',   'illegal:' . _('Warning string'));
ossim_valid($word, OSS_INPUT, OSS_NULLABLE,                                                                             'illegal:' . _('Find Word'));

if (ossim_error())
{
    die(ossim_error());
}

if ($flag_status == 1)
{
    if ($flag_reconfig)
    {
        $status_message = _('Your new configuration will be applied once AlienVault Reconfig completes. This might take several minutes.');
    }
    else
    {
        $status_message = _('Configuration successfully updated');
    }
}
elseif($flag_status == 2)
{
    $status_message =  $error_string;
}

//Connect to db */
$db    = new ossim_db();
$conn  = $db->connect();

$alienvault_conn = new Alienvault_conn();
$provider_registry = new Provider_registry();
$api_client = new Alienvault_client($alienvault_conn, $provider_registry);

$product = (Session::is_pro()) ? "USM" : "OSSIM";

//Sensor List
$_list_data  = Av_sensor::get_list($conn, array('order_by' => 'name ASC'));
$all_sensors = $_list_data[0];

$sensor_list = array('0' => 'First available sensor');

foreach ($all_sensors as $sensor_id => $sensor)
{
    $sensor_list[$sensor['name']] = $sensor['name'].' ['.$sensor['ip'].']';
}

$default_entities['optgroup1'] = _('Users');
$users = Session::get_list($conn);
foreach ($users as $usr)
{
    $default_entities[$usr->get_login()] = $usr->get_name();
}

if (Session::is_pro())
{
    //menu template list
    list($templates, $num_templates) = Session::get_templates($conn);

    if (count($templates) < 1)
    {
        $templates[0] = array('id'=>'', 'name'=>'- No templates found -');
    }

    $menus = array();

    foreach($templates as $template)
    {
        $menus[$template['id']] = $template['name'];
    }

    //Entity list
    $entities_all = Acl::get_entities_to_assign($conn);

    if (is_array($entities_all) && count($entities_all) > 0)
    {
        $default_entities['optgroup2'] = _('Entities');
        foreach ($entities_all as $k => $v )
        {
            $default_entities[$k] = $v;

            if(!Acl::is_logical_entity($conn, $k))
            {
                $entities[$k] = $v;
            }
        }
    }
    else
    {
        $entities[''] = '- '._('No entities found').' -';
    }

    asort($entities);
}


function getCertificateData ($file) {
    $result = false;
    $file_destination = '/var/tmp/' . $file['name'];
    $is_upload_file = move_uploaded_file($file['tmp_name'],$file_destination);
    $is_valid_type =  mime_content_type($file_destination) == 'text/plain';

    if( $is_upload_file  &&  $is_valid_type) {
        $result =   file_get_contents($file_destination);
        unlink($file_destination);
    }
    return $result;
}

function setCertificateData ($data) {
    return $data == 'clear' ? '' : $data;
}

$CONFIG = array(
    'Metrics' => array(
        'title' => _('Metrics'),
        'desc' => _('Configure metric settings'),
        'advanced' => 0,
        'section' => 'metrics',
        'conf' => array(
            'def_asset' => array(
                'type' => array(
                    '0' => 0,
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5
                ),
                'default' => 2,
                'help' => _('The default value, from 0 to 5, that is assigned to an asset when it is created. This value is used to calculate the event risk.') ,
                'desc' => _('Default Asset value'),
                'advanced' => 0 ,
                'section' => 'metrics'
            ),
            'server_logger_if_priority' => array(
                'type' => array(
                    '0' => 0,
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5
                ),
                'help' => _('The threshold to determine whether the events are stored in SIEM'),
                'desc' => _('Security Events process priority threshold'),
                'advanced' => 1,
                'section' => 'metrics',
                'disabled' => (Session::is_pro()) ? 0 : 1
            )
        )
    ),
    'Ossim Framework' => array(
        'title' => Session::is_pro() ? _('USM Framework') : _('Ossim Framework'),
        'desc'  => _('PHP Configuration (graphs, acls, database api) and links to other applications'),
        'advanced' => 1,
        'section' => 'alarms',
        'conf' => array(
            'nfsen_in_frame' => array(
                'type'  => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help'  => '',
                'desc'  => _('Open Remote Netflow in the same frame'),
                'advanced' => 1
            ),
            'internet_connection' => array(
                'type'  => array(
                    '0' => _('No'),
                    '1' => _('Yes'),
                    '2' => _('Force Yes')
                ),
                'help' => _("You can configure if you have an internet connection available so that you can load external libraries.<br/><ul><li>No: It will not load external libraries.</li><li>Yes: It will check if we have internet connection and if so, it will load external libraries.</li><li>Force Yes: It will always try to load external libraries.</li></ul>This option requires to login again."),
                'desc' => _('Internet Connection Availability'),
                'advanced' => 1
            ),
            'framework_https_cert_plain' => array(
                'type' => 'textarea',
                'help' => _('The certificate is used to secure your services and encrypt transmitted data. Please upload the ASCII PEM encoded X.509 certificate file starting with “——BEGIN CERTIFICATE—“ and ending with “—END CERTIFICATE——“ lines.'),
                'desc' => _('Web Server SSL Certificate (PEM format)'),
                'advanced' => 1,
                'aspass'    => 1,
                'ssl_remove_button' => 1
            ),
            'framework_https_pem_plain' => array(
                'type' => 'textarea',
                'help' => _('Warning: This key must be kept in secret! This private key is used to secure and verify connections using the certificate provided above. Please upload the ASCII PEM encoded private key file starting with the “——BEGIN RSA PRIVATE KEY—“ and ending with “—END RSA PRIVATE KEY——“ lines.'),
                'desc' => _('Web Server SSL Private Key (PEM format)'),
                'advanced' => 1,
                'aspass'    => 1
            ),
            'framework_https_ca_cert_plain' => array(
                'type' => 'textarea',
                'help' => _('These certificates issued by Certificate Authorities (trusted third party) are used to certify the ownership of a public key by the named subject of the certificate. Upload the ASCII PEM encoded X.509 certificates file including the “——BEGIN CERTIFICATE—“ and “—END CERTIFICATE——“ lines.'),
                'desc' => _('Web Server SSL CA Certificates (PEM format) <i>[optional]</i>'),
                'advanced' => 1,
                'aspass'    => 1
            ),
            'default_sender_email_address' => array(
                'type' => 'text',
                'help' => _("Default Sender's Email Address that will be used to send email notifications"),
                'desc' => _("Sender's Email Address for notifications"),
                'advanced' => 1
            ),
        )
    ),
    'IDM' => array(
        'title' => _('IDM'),
        'desc' => _('Configure IDM settings'),
        'advanced' => 1,
        'section' => 'idm',
        'conf' => array(
            'idm_user_login_timeout' => array(
                'type' => 'text',
                'help' => _('If a user does not log in a host after # hours the IDM will not enrich the events with that user log in information. Set a default session timeout for IDM User Login events. Value 0 disables this feature. The server will be restarted.'),
                'desc' => _('IDM user login timeout'),
                'advanced' => 1,
                'section' => 'idm'
            ),
        )
    ),
    'Backup' => array(
        'title' => _('Backup'),
        'desc' => _('Backup configuration: backup database, directory, interval'),
        'advanced' => 0,
        'section' => 'siem,alarms,raw_logs',
        'conf' => array(
            'backup_store' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('Enable/Disable SIEM Events database backup.<br/>The events out of active window will be stored in backup files'),
                'desc' => _('Enable SIEM database backup'),
                'advanced' => 1
            ),
            'backup_events_min_free_disk_space' => array(
                'type' => array(
                    '10' => _('10%'),
                    '15' => _('15%')
                ),
                'help' => _('Minimum free disk space for the SIEM backups in percentage.'),
                'desc' => _('Allowed free disk  space for the SIEM backups'),
            ),
            'frameworkd_backup_storage_days_lifetime' => array(
                'type' => 'text',
                'help' => _('Number of Backup files (One file per day of Siem events) are stored in hard-disk'),
                'desc' => _('Number of Backup files to keep in the filesystem'),
                'section' => 'siem',
                'advanced' => 0
            ),
            'backup_day' => array(
                'type' => 'text',
                'help' => _('Number of days Siem events are stored in SQL Database<br/>(0 value means no backup)'),
                'desc' => _('Events to keep in the Database (Number of days)'),
                'section' => 'siem',
                'advanced' => 0
            ),
            'backup_events' => array(
                'type' => 'text',
                'help' => _('Maximum number of events stored in SQL Database<br/>(0 value means no limit)'),
                'desc' => _('Events to keep in the Database (Number of events)'),
                'section' => 'siem',
                'advanced' => 0
            ),
            'backup_hour' => array(
                'type' => 'text',
                'id'   => 'backup_timepicker',
                'help' => _('Backup start time in format HH:MM'),
                'desc' => _('Backup start time'),
                'advanced' => 0
            ),
            'backup_netflow' => array(
                'type' => 'text',
                'help' => _('Number of days to store flows on netflows for'),
                'desc' => _('Active Netflow Window'),
                'advanced' => 0
            ),
            'alarms_expire' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no'  => _('No')
                ),
                'section' => 'alarms',
                'help' => _('Keep alarms on database or expire by Lifetime value'),
                'desc' => _('Alarms Expire'),
                'onchange' => 'change_alarms_lifetime(this.value)' ,
                'value' => ($conf->get_conf('alarms_lifetime') > 0) ? 'yes' : 'no' ,
                'advanced' => 0
            ),
            'alarms_lifetime' => array(
                'type' => 'text',
                'section' => 'alarms',
                'id'   => 'alarms_lifetime',
                'help' => _('Maximum number of days to keep alarms in the database (0 means alarms never expire)'),
                'desc' => _('Alarms Lifetime'),
                'style' => ($conf->get_conf('alarms_lifetime') > 0) ? '' : 'color:gray' ,
                'advanced' => 0
            ),
            'logger_expire' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no'  => _('No')
                ),
                'section' => 'raw_logs',
                'id'   => 'logger_expiration',
                'help' => _('Keep logs on Logger storage or expire by Lifetime value'),
                'desc' => _('Logger Expiration'),
                'onchange' => 'change_logger_lifetime(this.value)' ,
                'value' => ($conf->get_conf('logger_storage_days_lifetime') > 0) ? 'yes' : 'no' ,
                'advanced' => 0,
                'disabled' => (Session::is_pro()) ? 0 : 1
            ),
            'logger_storage_days_lifetime' => array(
                'type' => 'text',
                'section' => 'raw_logs',
                'id'   => 'logger_storage_days_lifetime',
                'help' => _('Maximum number of days to keep logs in the system (0 means logs never expire)'),
                'desc' => _('Active Logger Window'),
                'onchange' => 'check_logger_lifetime(this.value)' ,
                'style' => ($conf->get_conf('logger_storage_days_lifetime') > 0) ? '' : 'color:gray' ,
                'advanced' => 0,
                'disabled' => (Session::is_pro()) ? 0 : 1
            ),
            'backup_conf_pass' => array(
                'type' => 'password',
                'id'   => 'backup_encryption',
                'desc' => _('Password to encrypt backup files'),
                'help' => _('Password length must be between').' '.$conf->get_conf('pass_length_min')._(' and ').$conf->get_conf('pass_length_max').' '._('characters').
                    '.<br> The following characters are prohibited: ;, |, &, <, >, \n, \s, (, ), [, ], {, }, ?, *, ^, \\',
                'advanced' => 0
            )
        )
    ),
    'Vulnerability Scanner' => array(
        'title' => _('Vulnerability Scanner'),
        'desc' => _('Vulnerability Scanner configuration'),
        'advanced' => 0,
        'section' => 'vulnerabilities',
        'conf' => array(
            'gvm_host' => array(
                'type' => 'text',
                'help' => _('Only for non distributed scans'),
                'desc' => _('Scanner host'),
                'advanced' => 1 ,
                'section' => 'vulnerabilities'
            ),
            'gvm_pre_scan_locally' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('If enabled, the Pre-scan option will be selected when a scan job is created'),
                'desc' => _('Enable Pre-Scan locally'),
                'advanced' => 1 ,
                'section' => 'vulnerabilities'
            ),
            'vulnerability_incident_threshold' => array(
                'type' => array(
                    '1' => 'Info',
                    '2' => 'Low',
                    '5' => 'Medium',
                    '6' => 'High',
                    '11' => _('Disabled')
                ),
                'help' => _('Any vulnerability with a higher risk level than this value will automatically generate a vulnerability ticket.'),
                'desc' => _('Vulnerability Ticket Threshold'),
                'advanced' => 0 ,
                'section' => 'vulnerabilities'
            ),
            'close_vuln_tickets_automatically' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('Tickets related to vulnerabilities will be automatically closed if the vulnerabilities found in the previous scans are not present in the latest scan.').'<br/>'.
                  _('This option could cause false positives if the scans are not executed under the same conditions.  Therefore, watch out for the following cases:').
                    '<br/>
                     <ul>
                        <li>DHCP environments: The same IP address points to different target hosts between scans.</li>
                        <li>Scan timeout: Timeout expired before finishing the scan (for example, target host having a high load or high network latency)</li>
                        <li>Scan credentials: Credentials have changed between scans (For example, invalid credentials or no credentials used in the latest scan)</li>
                        <li>Target host not accessible: The target host is not alive in the latest scan.</li>
                        <li>Others</li>                        
                     </ul>',
                'desc' => _('Close tickets automatically'),
                'advanced' => 0 ,
                'section' => 'vulnerabilities'
            )
        )
    ),
    'User Log' => array(
        'title' => _('User activity'),
        'desc' => _('User action logging'),
        'advanced' => 0,
        'section' => 'userlog',
        'conf' => array(
            'session_timeout' => array(
                'type' => 'text',
                'help' => _('Expired timeout for current session in minutes. (0=unlimited)'),
                'desc' => _('Session Timeout (minutes)'),
                'advanced' => 0 ,
                'section' => 'userlog'
            ),
            'log_syslog' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('If enabled, log user activities into /var/log/syslog instead of the database') ,
                'desc' => _('Log to syslog'),
                'advanced' => 0 ,
                'section' => 'userlog',
                'id' => 'sys_log'

            ),
            'user_action_log' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('If enabled, user activities are logged'),
                'desc' => _('Enable User Log'),
                'advanced' => 0 ,
                'section' => 'userlog',
                'id' => 'user_log'
            )
        )
    ),
    'Login' => array(
        'title' => _('Login methods/options'),
        'desc' => _('Setup main login methods/options'),
        'advanced' => 1,
        'section' => 'users',
        'conf' => array(
            'remote_key' => array(
                'type' => 'password',
                'help' => _('To apply this change restart your session'),
                'desc' => _('Remote login key'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_enable_ldap' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => '',
                'desc' => _('Enable LDAP for login'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_server' => array(
                'type' => 'text',
                'help' => 'LDAP server IP or host name',
                'desc' => _('LDAP server address'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_port' => array(
                'type' => 'text',
                'help' => 'TCP port to connect LDAP server<br/>By default the port is 389 or 636 if you use SSL',
                'id' => 'ldap_port',
                'desc' => _('LDAP server port'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_ssl' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('use LDAP server SSL?'),
                'desc' => _('LDAP server SSL'),
                'onchange' => 'change_ldap_port(this.value)' ,
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_tls' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('use LDAP server with TLS?'),
                'desc' => _('LDAP server TLS'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_baseDN' => array(
                'type' => 'text',
                'help' => 'Example: dc=local,dc=domain,dc=net' ,
                'desc' => _('LDAP server baseDN'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_filter_to_search' => array(
                'type' => 'text',
                'help' => _('Filter to search the users for ossim in LDAP<br />Example for LDAP:<br/> (&(cn=%u)(objectClass=account)) <b>or</b> (uid=%u) <b>or</b> (&(cn=%u)(objectClass=OrganizationalPerson))<br/>Example for AD:<br/> (&(sAMAccountName=%u)(objectCategory=person)) <b>or</b> (userPrincipalName=%u) %u is the user'),
                'desc' => _('LDAP server filter for LDAP users'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_bindDN' => array(
                'type' => 'text',
                'help' => _('Account to search the user in LDAP <br/>Example: user@example.com'),
                'desc' => _('LDAP Username'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_valid_pass' => array(
                'type' => 'password',
                'help' => _('Password of Ldap Username'),
                'desc' => _('LDAP password for Username'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_require_a_valid_ossim_user' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('If enabled, the login process requires that the user exists in both the LDAP server and USM Appliance.'),
                'desc' => _('LDAP requires a valid OSSIM user for login'),
                'advanced' => 1 ,
                'onchange' => (Session::is_pro()) ? 'change_ldap_need_user(this.value)' : '' ,
                'section' => 'users'
            ),
            'login_create_not_existing_user_entity' => array(
                'type' => $entities ,
                'help' => _('The default entity assigned to new LDAP users when an OSSIM user is not required.'),
                'id'   => 'user_entity',
                'desc' => _('Entity for new LDAP user'),
                'advanced' => 1 ,
                'section' => 'users',
            ),
            'login_create_not_existing_user_menu' => array(
                'type' => $menus ,
                'help' => _('The default menu template assigned to new LDAP users when an OSSIM user is not required.'),
                'id'   => 'user_menu',
                'desc' => _('Menus for new LDAP user'),
                'advanced' => 1 ,
                'section' => 'users',
            )
        )
    ),
    'Passpolicy' => array(
        'title' => _('Password policy'),
        'desc' => _('Setup login password policy options'),
        'advanced' => 1,
        'section' => 'users',
        'conf' => array(
            'pass_length_min' => array(
                'type' => 'text',
                'help' => _('Number (default = 7)'),
                'desc' => _('Minimum password length'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_length_max' => array(
                'type' => 'text',
                'help' => _('Number (default = 32)'),
                'desc' => _('Maximum password length'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_history' => array(
                'type' => 'text',
                'help' => _('The number of different passwords that are saved into the database to verify whether a password has been used recently (0 disables this option).'),
                'desc' => _('Password history'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_complex' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('If enabled, the password must contain three of these character types: lowercase letters, uppercase letters, numbers, and special characters'),
                'desc' => _('Complexity'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_expire_min' => array(
                'type' => 'text',
                'help' => _('The minimum password lifetime prevents users from circumventing').'<br/>'._('the requirement to change passwords by doing five password changes<br> in a minute to return to the currently expiring password. (0 to disable) (default 0)'),
                'desc' => _('Minimum password lifetime in minutes'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_expire' => array(
                'type' => 'text',
                'help' => _('After these days the login ask for new password. (0 to disable) (default 0)'),
                'desc' => _('Maximum password lifetime in days'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'failed_retries' => array(
                'type' => 'text',
                'help' => _('Number of failed attempts prior to lockout'),
                'desc' => _('Failed logon attempts'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'unlock_user_interval' => array(
                'type' => 'text',
                'help' => _('Account lockout duration in minutes (0 = never auto-unlock)'),
                'desc' => _('Account lockout duration'),
                'advanced' => 1 ,
                'section' => 'users'
            )
        )
    ),
    'IncidentGeneration' => array(
        'title' => _('Tickets'),
        'desc' => _('Tickets parameters'),
        'advanced' => 0,
        'section' => 'tickets,alarms',
        'conf' => array(
            'alarms_generate_incidents' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('Enabling this option will lead to automatic ticket generation <br/>upon arrival of alarms.'),
                'desc' => _('Open Tickets for new alarms automatically?'),
                'section' => 'tickets,alarms',
                'advanced' => 0
            ),
            'incidents_incharge_default' => array(
                'type' => $default_entities,
                'help' => _('The automatic ticket generation will use the selected in-charge user or entity. Admin user by default'),
                'desc' => _('Automatic ticket generation default in-charge user/entity'),
                'section' => 'tickets,alarms',
                'advanced' => 0
            ),
            'tickets_send_mail' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no'  => _('No')
                ),
                'help' => _('If enabled, sends an email to the person in charge of the ticket when a new ticket is created or updated.'),
                'desc' => _('Send email notification'),
                'section'  => 'tickets',
                'advanced' => 0
            ),
            'tickets_max_days' => array(
                'type' => 'text',
                'help' => _('Sends an email for tickets that remain open after the specified period of time (in days)') ,
                'desc' => _('Open tickets reminder'),
                'advanced' => 0 ,
                'section' => 'tickets'
            ),
            'tickets_template_link' => array(
                'type' => 'link',
                'value'=> "<a target='".((POST('section') != '' || GET('section') != '') ? '_parent' : 'main')."' href='/ossim/conf/emailtemplate.php'>"._('Click here').'</a>',
                'help' => '',
                'desc' => _('Email Template for tickets'),
                'advanced' => 0,
                'section' => 'tickets'
            )
        )
    ),
    'netflow' => array(
        'title' => _('Netflow'),
        'desc' => _('Set thresholds to create events on anomalous bandwidth usage in your environment (values are in MB)'),
        'advanced' => 0,
        'section' => 'netflow',
        'conf' => array(
            'tcp_max_download' => array(
                'desc' => _('TCP Maximum Download'),
                'help' => _('Maximum number of megabytes processed for TCP inbound traffic.'),
                'section' => 'netflow',
                'advanced' => 1
            ),
            'tcp_max_upload' => array(
                'desc' => _('TCP Maximum Upload'),
                'help' => _('Maximum number of megabytes processed for TCP outbound traffic.'),
                'section' => 'netflow',
                'advanced' => 1
            ),
            'udp_max_download' => array(
                'desc' => _('UDP Maximum Download'),
                'help' => _('Maximum number of megabytes processed for UDP inbound traffic.'),
                'section' => 'netflow',
                'advanced' => 1
            ),
            'udp_max_upload' => array(
                'desc' => _('UDP Maximum Upload'),
                'help' => _('Maximum number of megabytes processed for UDP outbound traffic.'),
                'section' => 'netflow',
                'advanced' => 1
            ),
            'inspection_window' => array(
                'desc' => _('Inspection window (hours)'),
                'help' => _('Process flows for the number of hours specified. For example, 3, 4, or 5. This tells USM Appliance to process flows starting from the number of hours before until the current time.'),
                'section' => 'netflow',
                'advanced' => 1
            ),
            'agg_function' => array(
                'type' => array(
                    1 => _('Yes'),
                    0 => _('No')
                ),
                'default' => 0,
                'desc' => _('If enabled, summarizes traffic by remote IP'),
                'section' => 'netflow',
                'advanced' => 0
            )
        )
    ),
    'detection' => array(
        'title' => _('Detection'),
        'desc' => _('Detection configuration'),
        'advanced' => 0,
        'section' => 'detection',
        'conf' => array(
            'hids_update_rate' => array(
                'type' => array_map(function($v){ return ($v*5)+10;}, array_flip(range(10, 60, 5))),
                'default' => 60,
                'desc' => _('Refresh rate (in minutes)'),
                'help' => _('How often the HIDS agent information is updated in the database'),
                'section' => 'detection',
                'advanced' => 0
            )
        )
    ),
);

if (Session::is_pro()) {
    //z for last in list
    $CONFIG["zAutoUpdate"] = array(
        'title' => _('Automatic updates'),
        'desc' => _('Schedule automatic updates'),
        'advanced' => 0,
        'section' => 'updates',
        'conf' => array(
            'feed_auto_updates' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('If enabled, plugin updates and threat intelligence updates run automatically. System updates must be run manually.'),
                'desc' => _('Automatically run Plugin updates and Threat Intelligence updates'),
                'section' => 'updates',
                'advanced' => 0,
                'onchange' => 'change_feed_auto_update_time(this.value)',
                'value' => ($conf->get_conf('feed_auto_updates') == "yes") ? "yes" : "no"
            ),
            'feed_auto_update_time' => array(
                'type' => array_map(function($i) {return str_pad($i, 2, "0", STR_PAD_LEFT).":00";},range(0,23)),
                'help' => _('Schedule is based on the system timezone configured in the console.'),
                'desc' => _('Schedule automatic updates to run'),
                'section' => 'updates',
                'advanced' => 0,
                'id'    => 'feed_auto_update_time',
                'disabled' => ($conf->get_conf('feed_auto_updates') == "yes") ? 0 : 1
            )
        )
    );
}


ksort($CONFIG);

function custom_actions($api_client, $ossim_conf, $actions)
{
    global $restart_server;

    if (array_key_exists('idm_user_login_timeout', $actions)){
        $restart_server = 1;
    }

    if (array_key_exists('feed_auto_updates', $actions) || array_key_exists('feed_auto_update_time', $actions)){
        $status = ($ossim_conf->get_conf('feed_auto_updates') == "yes") ? TRUE : FALSE;
        $time = ($ossim_conf->get_conf('feed_auto_update_time') == "") ? 0 : $ossim_conf->get_conf('feed_auto_update_time');

        $api_client->system()->set_auto_update($status, $time);
    }

    if (array_key_exists('hids_update_rate', $actions)){
        $refresh_rate = ($ossim_conf->get_conf('hids_update_rate') == "") ? 60 : $ossim_conf->get_conf('hids_update_rate');

        $api_client->system()->set_hids_update_rate($refresh_rate);
    }
}

function valid_value($key, $value, $numeric_values)
{
    if (in_array($key, $numeric_values))
    {
        if (!is_numeric($value))
        {
            $error_msg = _('Error!').' '."<strong>$key</strong>".' '._('must be numeric');

            $error = new Av_error();
            $error->set_message($error_msg);
            $error->display();
        }
        else {
            if ($key == 'hids_update_rate' && ($value < 10 || $value > 60)) {
                $error_msg = _('Error!').' '."<strong>$key</strong>".' '._('must have a value between 10 and 60');

                $error = new Av_error();
                $error->set_message($error_msg);
                $error->display();
            }
        }
    }

    return TRUE;
}

function submit()
{
    ?>
    <script type='text/javascript'>
        function av_notification()
        {
            if (notify.permissionLevel() != notify.PERMISSION_GRANTED)
            {
                notify.requestPermission(av_notification);
            }

            notificationw = notify.createNotification(
                "<?php echo Util::js_entities(html_entity_decode(_('Thank you'))) ?>",
                {
                    body: "<?php echo Util::js_entities(html_entity_decode(_('Notifications enabled successfully')))?>",
                    icon: "/ossim/pixmaps/statusbar/logo_siem_small.png"
                }
            );

            setTimeout (function() { notificationw.close(); }, '10000');
        }

        $(document).ready(function()
        {
            $('#update').on('click', function () {
                var token = Token.get_token("save_config");
                $('#idf [name=token]').val(token);
                var result = $('#sys_log').val();
                if(result === '1'){
                    $('#user_log').val(result);
                }

                <?php Util::execute_command('/usr/bin/sudo /etc/init.d/ossim-framework restart > /dev/null 2>/dev/null &'); ?>
            });
            if (notify.isSupported)
            {
                $('#enable_notifications').show();
            }
        });
    </script>
    <!-- submit -->
    <input type="button" class='av_b_secondary' id="enable_notifications" onclick="av_notification()" value=" <?php echo _("Enable Desktop Notifications"); ?> "/>

    <input type="submit" name="update" id="update" value=" <?php echo _('Update configuration'); ?> "/>
    <!-- end sumbit -->
    <?php
}
if (POST('update'))
{
    $token  = POST("token");
    if (Token::verify('tk_save_config', $token) == FALSE)
    {
        $error = Token::create_error_message();
        Util::response_bad_request($error);
    }

    $numeric_values = array(
        'backup_events',
        'alarms_lifetime',
        'logger_storage_days_lifetime',
        'frameworkd_backup_storage_days_lifetime',
        'backup_netflow',
        'internet_connection',
        'frameworkd_port',
        'frameworkd_donagios',
        'frameworkd_listener',
        'frameworkd_scheduler',
        'backup_port',
        'backup_day',
        'vulnerability_incident_threshold',
        'user_action_log',
        'log_syslog',
        'pass_length_min',
        'pass_length_max',
        'pass_history',
        'pass_expire_min',
        'pass_expire',
        'failed_retries',
        'unlock_user_interval',
        'tickets_max_days',
        'idm_user_login_timeout',
        'tcp_max_download',
        'tcp_max_upload',
        'udp_max_download',
        'udp_max_upload',
        'inspection_window',
        'def_asset',
        'hids_update_rate'
    );

    $passwords = array(
        'remote_key',
        'backup_pass',
        'login_ldap_valid_pass',
        'snort_pass',
        'solera_pass',
        'backup_conf_pass'
    );


    $config = new Config();

    $pass_fields = array();

    foreach ($CONFIG as $conf)
    {
        foreach ($conf['conf'] as $k => $v)
        {
            if ($v['type'] == 'password')
            {
                $pass_fields[$k] = 1;
            }
        }
    }

    $flag_status    = 1;
    $flag_reconfig  = 0;
    $error_string   = '';
    $warning_string = '';
    $certs          = FALSE;
    $certs_clear    = FALSE;
    $cert_options   = array('framework_https_ca_cert_plain', 'framework_https_cert_plain', 'framework_https_pem_plain');

    for ($i = 0; $i < POST('nconfs'); $i++)
    {
        $post_key = POST("conf_$i") ;
        $post_value = POST("value_$i");

        //Restart server if the value is changed
        if($post_key == "server_logger_if_priority" && $post_value != $ossim_conf->get_conf($post_key))
        {
            $restart_server = 1;
        }

        if($post_key == "def_asset" && (!is_numeric($post_value) || $post_value < 0 || $post_value > 5))
        {
            $error_string .= _('Default Asset Value must be an integer between 0 and 5.');
            $flag_status = 2;
            continue;
        }

        if($post_key == "pass_length_max")
        {
            $pass_length_max = $post_value;
            continue;
        }

        if($post_key == "pass_expire")
        {
            $pass_expire_max = $post_value;
        }

        if($post_key == "pass_expire_min")
        {
            $pass_expire_min = $post_value;
        }

        //There's an exception when the post_key 'logger_storage_days_lifetime' is detected and it's not pro version because of Logger is not installed
        if($post_key == "logger_storage_days_lifetime" && !Session::is_pro()) {
            $post_key = NULL;
        }

        if(in_array($post_key, $numeric_values) && (!is_numeric($post_value) || !is_int($post_value*1) || $post_value*1 < 0))
        {
            $variable = $_SESSION['_main']['conf_'.$i];

            if(empty($warning_string)) {
                $warning_string .= _("Configuration successfully updated, but The following errors occurred:");
            }

            $warning_string     .= " "._("Invalid $variable, it has to be equal or greater than zero.");

            $flag_status         = 3;

            $_POST["value_$i"]   = 0;
        }

        if($post_key == "pass_length_min")
        {
            if ($post_value < 1)
            {
                $_POST["value_$i"] = 7;
            }

            $pass_length_min = $post_value;
        }

        if($post_key == "default_sender_email_address") {
            if (!security_class::valid_email($post_value)) {
                ossim_clean_error();
                $error_string .= _("Error! Invalid Sender's Email Address.  Entered value:")." <strong>".Util::htmlentities($post_value)."</strong>";
                $flag_status = 2;
                continue;
            }
        }

        //Passwords array contains some variables to validate with OSS_PASSWORD constant
        if(in_array($post_key, $passwords))
        {
            ossim_valid($post_value, OSS_NULLABLE, OSS_PASSWORD, 'illegal:' . $post_key);
            $is_fake_pass = Util::is_fake_pass($post_value);

            if (($post_key== "backup_conf_pass") && $post_value && (! $is_fake_pass)) {
                $raw_password = $post_value;
                $len = strlen($raw_password);
                $min = $config->get_conf('pass_length_min');
                $max = $config->get_conf('pass_length_max');
                $symbols_prohibited = array(';', '|', '&', '<', '>', '\n', '(', ')', '[', ']', '{', '}', '?', '*', '^', ' ');

                foreach ($_POST as $key => $value) {
                    if ($key == "pass_length_min") {
                        $key = str_replace("conf_","value_");
                        $min = POST($key);
                        continue;
                    }
                    if ($key == "pass_length_max") {
                        $key = str_replace("conf_","value_");
                        $max = POST($key);
                        continue;
                    }
                }
                if ($len < $min) {
                    $error_string .= ' '._('Backup password is not long enough').' ['._('Minimum password size is').' '.$min.']';
                    $flag_status = 2;
                }
                elseif ($len > $max) {
                    $error_string .= ' '._('Backup password is too long').' ['._('Maximum password size is').' '.$max.']';
                    $flag_status = 2;
                }
                elseif ($raw_password != str_replace($symbols_prohibited, "", $raw_password)) {

                    $symbols_found = [];

                    foreach ($symbols_prohibited as $symbol) {

                        if (strpos($raw_password, $symbol) !== false){

                            if (preg_match('/\s/',$symbol)) {
                                $symbols_found [] =  'white spaces' ;
                                break;
                            }
                            $symbols_found [] =  $symbol ;
                        }
                    }

                    $symbols_found_str =  implode(" , ", $symbols_found);
                    $error_string .= ' '._('Backup password contains prohibited characters.') . ' Please delete prohibited characters: <i> ' .$symbols_found_str . '</i>';
                    $flag_status = 2;
                }
            }
        }
        else
        {
            ossim_valid($post_value, OSS_ALPHA, OSS_NULLABLE, OSS_SCORE, OSS_DOT, OSS_PUNC, "\{\}\|;\(\)\%\\", 'illegal:' . $post_key);
        }

        if($post_value != '')
        {
            if (!(ossim_error() || (valid_value($post_key, $post_value, $numeric_values))))
            {
                if ($flag_status == 2)
                {
                    $error_string .= ' ';
                }

                $flag_status   = 2;
            }
        }

        // Check and Get certificates
        if(in_array($post_key, $cert_options) && !empty($_FILES["value_$i"]['name'])) {
            $cert_data = getCertificateData($_FILES["value_$i"]);

            if (!empty($cert_data) && $cert_data != false) {

                $_POST["value_$i"] = $cert_data;
                $certs = TRUE;

            } else {
                $error_string .= _("Web Server SSL Certificate can't be uploaded. Please upload the certificate in PEM format.");
                $flag_status   = 2;
                $certs = FALSE;
                break;
            }
        }
        if (in_array($post_key, $cert_options) &&  $post_value == 'clear')
        {
            $certs = TRUE;
            $certs_clear = TRUE;
        }
    }

    $custom_actions = array();

    if ($flag_status != 2)
    {
        for ($i = 0; $i < POST('nconfs'); $i++)
        {
            $post_key = POST("conf_$i") ;
            $post_value = POST("value_$i");

            if (isset($post_key) )
            {
                if (($pass_fields[$post_key] == 1 && Util::is_fake_pass($post_value)) || $post_value == 'skip_this_config_value')
                {
                    continue;
                }
                else
                {
                    $before_value = $ossim_conf->get_conf($post_key);
                    $config->update($post_key, $post_value);

                    if ($post_value != $before_value)
                    {
                        Log_action::log(7, array("variable: ".$post_key));

                        $custom_actions[$post_key] = trim($post_value);
                    }
                }
            }
        }
    }


    // check valid pass length max
    if(intval($pass_length_max) < intval($pass_length_min) || intval($pass_length_max) < 1 || intval($pass_length_max) > 255)
    {
        $config->update('pass_length_max' , 255);
    }
    else
    {
        $config->update('pass_length_max' , intval($pass_length_max));
    }

    // check valid expire min - max
    if ($pass_expire_max * 60 * 24 < $pass_expire_min)
    {
        $config->update('pass_expire_min' , 0);
    }

    // Set certificates
    $lastconfig = new Config(); // To get latest inserted values

    if ($certs) {

        $framework_https_cert_plain = setCertificateData($lastconfig->get_conf('framework_https_cert_plain'));
        $framework_https_pem_plain = setCertificateData($lastconfig->get_conf('framework_https_pem_plain'));
        $framework_https_ca_cert_plain = setCertificateData($lastconfig->get_conf('framework_https_ca_cert_plain'));

        $response = $api_client->system()->set_system_certificates($framework_https_cert_plain, $framework_https_pem_plain, $framework_https_ca_cert_plain);

        $response = @json_decode($response, TRUE);

        if (!$certs_clear)
        {
            foreach ($cert_options as $cert_option) {

                $config->update($cert_option, 'default');
            }
        }

        if (!$response || $response['status'] == 'error')
        {
            $error_string = sprintf(_('Unable to set SSL certificate: %s'),$response['message']);
            $flag_status  = 2;

            foreach  ($cert_options as $cert_option){

                $config->update($cert_option,'clear');
            }
        }

        $flag_reconfig = 1;
    }

    // API calls to update the values or restart services
    if (!empty($custom_actions)){
        // Special cases

        $ossim_conf = new Ossim_conf();
        $GLOBALS['CONF'] = $ossim_conf;

        custom_actions($api_client, $ossim_conf, $custom_actions);
    }

    $url = $_SERVER['SCRIPT_NAME'] . "?word=" . $word . "&section=" . $section . "&status=" . $flag_status . "&error=" . urlencode($error_string) . "&warning=" . urlencode($warning_string) . '&reconfig=' . $flag_reconfig;
    if ($restart_server)
    {
        header("Location: ".AV_MAIN_PATH."/conf/reload.php?what=directives&back=".urlencode($url));
    }
    else
    {
        header("Location: $url");
    }

    exit();
}

$default_open = REQUEST('open');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _('Advanced Configuration');?></title>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php

    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
        array('src' => 'tipTip.css',                    'def_path' => TRUE),
        array('src' => 'jquery.timepicker.css',         'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');


    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
        array('src' => 'utils.js',                      'def_path' => TRUE),
        array('src' => 'token.js',                      'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
        array('src' => 'jquery.placeholder.js',         'def_path' => TRUE),
        array('src' => 'greybox.js',                    'def_path' => TRUE),
        array('src' => 'jquery.timepicker.js',          'def_path' => TRUE),
        array('src' => 'desktop-notify.js',             'def_path' => TRUE) // Include unobstructive notification functions
    );

    Util::print_include_files($_files, 'js');

    ?>

    <script type='text/javascript'>
        var IE = document.all ? true : false

        if (!IE)
        {
            document.captureEvents(Event.MOUSEMOVE)
        }

        document.onmousemove = getMouseXY;


        var tempX = 0;
        var tempY = 0;

        var difX = 15;
        var difY = 0;

        function getMouseXY(e)
        {
            if (IE)
            {
                // grab the x-y pos.s if browser is IE
                tempX = event.clientX + document.body.scrollLeft + difX
                tempY = event.clientY + document.body.scrollTop + difY
            }
            else
            {
                // grab the x-y pos.s if browser is MOZ
                tempX = e.pageX + difX
                tempY = e.pageY + difY
            }
            if (tempX < 0){tempX = 0}
            if (tempY < 0){tempY = 0}

            var dh = document.body.clientHeight+ window.scrollY;
            if (document.getElementById("numeroDiv").offsetHeight+tempY > dh)
            {
                tempY = tempY - (document.getElementById("numeroDiv").offsetHeight + tempY - dh)
            }

            document.getElementById("numeroDiv").style.left = tempX+"px";
            document.getElementById("numeroDiv").style.top = tempY+"px";

            return true
        }


        function GB_onclose()
        {
            document.location.reload();
        }


        function show_tooltips()
        {

            $(".conf_help").each(function(index)
            {
                var help_id   = '#'+$(this).attr('id');
                var id        = $(this).attr('id').replace('help_', '');
                var info_id   = '#info_' + id;

                var data      = $(info_id).html().split('###');
                var conf_info = '<table class="t_conf_info" border="0" cellpadding="1" cellspacing="1">' +
                    '<tr>' +
                    '<td>' +
                    '<b>'+ data[0] +'</b><br><i>'+ data[1] + '</i>' +
                    '</td>' +
                    '</tr>' +
                    '</table>'

                $(help_id).tipTip({defaultPosition: 'top', maxWidth: "400px", content: conf_info, edgeOffset: 18});
            });
        }


        // show/hide some options
        <?php
        if ($ossim_conf->get_conf("server_sem") == 'yes')
        {
            echo "var valsem = 1;";
        }
        else
        {
            echo "var valsem = 0;";
        }

        if ($ossim_conf->get_conf("server_sim") == 'yes')
        {
            echo "var valsim = 1;";
        }
        else
        {
            echo "var valsim = 0;";
        }
        ?>


        function enableall()
        {
            tsim('yes')
            tsem('yes')
        }


        function tsim(val)
        {
            if (val == 'yes')
            {
                valsim = 1;
            }
            else
            {
                valsim = 0;
            }

            $('#correlate_select').css('color','black');
            $('#cross_correlate_select').css('color','black');
            $('#store_select').css('color','black');
            $('#qualify_select').css('color','black');

            if (valsim == 0)
            {
                $('#correlate_select').css('color','gray');
                $('#cross_correlate_select').css('color','gray');
                $('#store_select').css('color','gray');
                $('#qualify_select').css('color','gray');
            }

            if (valsim == 0 && valsem == 0)
            {
                $('#forward_alarm_select').css('color','gray');
                $('#forward_event_select').css('color','gray');
            }
            else
            {
                <?php
                if (Session::is_pro())
                {
                ?>
                $('#forward_alarm_select').css('color','black');
                $('#forward_event_select').css('color','black');
                <?php
                }
                ?>
            }
        }

        function tsem(val)
        {
            if (val == 'yes')
            {
                valsem = 1;
            }
            else
            {
                valsem = 0;
            }


            $('#sign_select').css('color','black');

            if (valsem == 0)
            {
                $('#sign_select').css('color','gray');
            }
            if (valsim == 0 && valsem == 0)
            {

                $('#forward_alarm_select').css('color','gray');
                $('#forward_event_select').css('color','gray');
            }
            else
            {
                $('#forward_alarm_select').css('color','black');
                $('#forward_event_select').css('color','black');
            }
        }


        function setvalue(id,val,checked)
        {
            var current = document.getElementById(id).value;
            current = current.replace(val,"");

            if (checked)
            {
                current += val;
            }

            document.getElementById(id).value = current;
        }

        function fword()
        {
            if($("#word").val().length>1)
            {
                $("#search").addClass('av_b_processing');
                $("#fword").val($("#word").val());
                $("#fidf").submit();
            }
            else
            {
                alert('<?php echo Util::js_entities(_('The search word must have at least two characters'))?>');
            }
        }

        function change_alarms_lifetime(val)
        {
            if (val == 'yes')
            {
                $('#alarms_lifetime').css('color','black').val('90');
            }
            else
            {
                $('#alarms_lifetime').css('color','gray').val('0');
            }
        }

        function change_feed_auto_update_time(val) {
            var disabled = (val == 'no');
            var color = disabled ? 'gray' : 'black';
            $('#feed_auto_update_time').prop('disabled',disabled).css('color',color);
        }

        function check_logger_lifetime(val)
        {
            if (val > 0 && !confirm('<?php echo Util::js_entities(_("This option will set a threshold and permanently delete records from your system. Would you like to continue?")) ?>') )
            {
                $('#logger_expiration option[value="no"]').attr("selected", "selected");
                $('#logger_storage_days_lifetime').css('color','gray').val('0');
            }
        }

        function change_logger_lifetime(val)
        {
            if (val == 'yes')
            {
                $('#logger_storage_days_lifetime').css('color','black').val('365');
                if ( !confirm('<?php echo Util::js_entities(_("This option will set a threshold and permanently delete records from your system. Would you like to continue?")) ?>') )
                {
                    $('#logger_expiration option[value="no"]').attr("selected", "selected");
                    $('#logger_storage_days_lifetime').css('color','gray').val('0');
                }
            }
            else
            {
                $('#logger_storage_days_lifetime').css('color','gray').val('0');
            }
        }

        function change_ldap_port(val)
        {
            if (val == 'no' && document.getElementById('ldap_port').value == '636')
            {
                document.getElementById('ldap_port').value = '389';
            }
            else if (val == 'yes' && document.getElementById('ldap_port').value == '389')
            {
                document.getElementById('ldap_port').value = '636';
            }
        }

        <?php
        if (session::is_pro())
        {
            ?>
            function change_ldap_need_user(val)
            {
                if (val == 'no')
                {
                    $('#user_entity').removeAttr('disabled');
                    $('#user_menu').removeAttr('disabled');
                }
                else
                {
                    $('#user_entity').attr('disabled','disabled');
                    $('#user_menu').attr('disabled','disabled');
                }
            }
        <?php
        }
        else
        {
            //it is because the opensource version not have entities or menu template
            unset($CONFIG['Login']['conf']['login_create_not_existing_user_entity']);
            unset($CONFIG['Login']['conf']['login_create_not_existing_user_menu']);
        }
        ?>

        // Use this function to enable or disable some options from a select
        function enable_disable(val, classname)
        {
            if (val == 'yes')
            {
                $('.'+classname).removeAttr('disabled');
            }
            else if (val == 'no')
            {
                $('.'+classname).attr('disabled','disabled');
            }
        }

        $(document).ready(function()
        {

            if (parent.is_lightbox_loaded(window.name))
            {
                $('.conf_table').addClass('gb_conf_table');
            }

            <?php
            if (GET('section') == "" && POST('section') == "" )
            {
            ?>
            $("#basic-accordion").accordion(
                {
                    autoHeight: false,
                    //navigation: true,
                    collapsible: true,
                    active: false,

                });
            <?php
            }
            ?>

            $('#search').placeholder();

            // enable/disable by default
            $('input:hidden').each(function()
            {

                if ($(this).val()=='server_sim')
                {
                    var idi = $(this).attr('name').substr(5);
                    tsim($("select[name='value_"+idi+"']").val());
                }

                if ($(this).val()=='server_sem')
                {
                    var idi = $(this).attr('name').substr(5);
                    tsem($("select[name='value_"+idi+"']").val());
                }
            });

            $('.conf_items').each(function(index)
            {
                $(this).find("tr:last td").css('border', 'none');
            });

            <?php
            if (isset($default_open))
            {
            $default_open = intval($default_open);
            ?>
            $("#basic-accordion").accordion('activate', <?php echo $default_open?>);
            <?php
            }
            ?>

            $('#idf').bind('keypress', function(event)
            {
                if(event.keyCode==13)
                {
                    event.preventDefault();
                    var id_focus = event.target.id;

                    if (id_focus == 'word')
                    {
                        if ($('#word').val() != '')
                        {
                            fword();
                        }
                    }
                    else
                    {
                        $('#update').trigger('click');
                    }
                }
            });

            $('#search').bind('click', function() { fword(); });

            <?php
            if (session::is_pro())
            {
            ?>
            change_ldap_need_user('<?php echo ($ossim_conf->get_conf('login_ldap_require_a_valid_ossim_user'))?>');
            <?php
            }
            ?>

            // Initialize time inputs
            $('#backup_timepicker').timepicker({
                timeFormat: 'H:i',
                disableFocus: true,
                maxTime: '23:30'
            }).on("timeFormatError", function() { $(this).val('01:00') });

            show_tooltips();



            //SSL Certificate

            $('.clear_ssl_button').on("click", function() {

                $('.clear_ssl').val('clear');
                var $parent = $(this).parent();
                $parent.append("<label class='info_ssl_label'><?php echo _('Please press Update Configuration to complete the removal')?></label>");
                $(this).remove();

            });

            $('[data-bind="browse"]').click(function(){

                $(this).parent().find('.sll_data').click();
            });

            $('.sll_data').change(function() {

                var fileData = $(this).val();
                var $parent = $(this).parent();
                var $inputText = $parent.find('input:text');
                var $label  = $parent.find('.ssl_file_name');
                var $span  = $parent.find('.ssl_file_name span');
                var fileName = fileData.match(/[^\/\\]+$/)[0];

                $inputText.remove();
                $label.css("display","inline-block");
                $span.addClass('ssl_file_name_selected');
                $span.text(fileName);

            });

            //end SSL

        });


    </script>

</head>

<body>

<div id='av_info'></div>

<div id="numeroDiv" style="position:absolute; z-index:999; left:0px; top:0px; height:80px; visibility:hidden; display:none"></div>
<?php

//$advanced = (POST('adv') == "1") ? true : ((GET('adv') == "1") ? true : false);

//Since 4.3.0, show advanced options always (false when only a section is shown)
$advanced = ($section != '') ? 0 : 1;

$onsubmit = ($advanced == '1') ? "onsubmit='enableall();'" : "";

if ($flag_status == 1)
{
    $txt   = $status_message;
    $ntype = 'nf_success';
}
elseif($flag_status == 2)
{
    $txt   = "<div style='margin: 5px 0px 0px 5px;'>"._('The following errors occurred:')."</div>";
    $txt  .= "<div style='margin: 3px 0px 0px 10px;'>".$status_message."</div>";
    $ntype = "nf_error";
}
elseif($flag_status == 3)
{
    $txt   = $warning_string;
    $ntype = "nf_warning";
}

unset($_SESSION['_main']);

if($flag_status == 1 || $flag_status == 2 || $flag_status == 3)
{
    $config_nt = array(
        'content' => $txt,
        'options' => array (
            'type'          => $ntype,
            'cancel_button' => TRUE
        ),
        'style'   => 'width: 60%; margin: 20px auto; text-align: left;'
    );

    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
}

?>

<form method="POST" id="idf" style="margin:0px auto" <?php echo $onsubmit;?>  enctype="multipart/form-data" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" autocomplete="off">
    <input type="hidden" name="token" value=""/>
    <table align='center' class='conf_table'>

        <tr>
            <td class="conf_table_left">
                <div id="basic-accordion">
                    <?php
                    $count  = 0;
                    $div    = 0;
                    $found  = 0;
                    $arr    = array();

                    foreach($CONFIG as $key => $val)
                    {
                        if ($advanced || ($section == '' && !$advanced && $val["advanced"] == 0) || ($section != "" && preg_match("/$section/",$val['section'])))
                        {
                            $s = $word;

                            if ($s != '')
                            {
                                foreach($val['conf'] as $conf => $type)
                                {
                                    if ($advanced || ($section == "" && !$advanced && $type["advanced"] == 0) || ($section != "" && preg_match("/$section/",$type['section'])))
                                    {
                                        $pattern = preg_quote($s, "/");

                                        if (preg_match("/$pattern/i", $type["desc"]))
                                        {
                                            $found = 1;

                                            array_push($arr, $conf);
                                        }
                                    }
                                }
                            }
                            ?>

                            <h3 class="<?php echo ($found == 1) ? 'header_found' : '' ?>">
                                <a href='#'><?php echo $val["title"] ?></a>
                            </h3>


                            <div class="accordion_child">
                                <table class='conf_items'>
                                    <?php
                                    print "<tr><td colspan='3'>" . $val["desc"] . "</td></tr>";

                                    if ($advanced && $val["title"]=="Policy")
                                    {
                                        $url = Menu::get_menu_url('policy/reorderpolicies.php', 'configuration', 'threat_intelligence', 'policy');
                                        ?>
                                        <tr>
                                            <td colspan="3" align="center" class='nobborder'>
                                                <a href='<?php echo $url?>'>[ <?php echo _("Re-order Policies") ?>]<a/>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    //If 'Log to syslog' option is enabled, 'Enable User Log' option should be enabled too.
                                    if($ossim_conf->get_conf('log_syslog'))
                                    {
                                        unset($val['conf']['user_action_log']['type'][0]);
                                    }

                                    foreach($val['conf'] as $conf => $type)
                                    {
                                        if ($advanced || ($section == '' && !$advanced && $type['advanced'] == 0) || ($section != "" && preg_match("/$section/",$type['section'])))
                                        {
                                            $conf_value = $ossim_conf->get_conf($conf);
                                            $var        = ($type['desc'] != '') ? $type['desc'] : $conf;

                                            $_SESSION['_main']['conf_'.$count] = $var;
                                            ?>

                                            <tr <?php if (in_array($conf, $arr)) echo "bgcolor=#DFF2BF" ?>>
                                                <input type="hidden" name="conf_<?php echo $count ?>" value="<?php echo $conf ?>"/>

                                                <td <?php if ($type['style'] != "") echo "style='".$type['style']."'" ?> class="left <?php if ($type['classname'] != "") echo $type['classname'] ?>">
                                                    <strong><?php echo (in_array($conf, $arr)) ? "<span style='color:#16A7C9'>".$var."</span>" : $var; ?></strong>
                                                </td>

                                                <td class="left" style="white-space:nowrap">
                                                    <?php
                                                    if($type["type"] !== "label") {
                                                        $input = '';

                                                        $disabled = ($type['disabled'] == 1 || $ossim_conf->is_in_file($conf)) ? "class='disabled' style='color:gray' disabled='disabled'" : '';
                                                        $style    = ($type['style'] != '') ? "style='".$type["style"]."'" : '';

                                                        /* select */
                                                        if (is_array($type['type']))
                                                        {
                                                            // Multiple checkbox
                                                            if ($type['checkboxlist'])
                                                            {
                                                                $input .= "<input type='hidden' name='value_$count' id='".$type['id']."' value='$conf_value'/>";
                                                                foreach($type['type'] as $option_value => $option_text)
                                                                {
                                                                    $input.= "<input type='checkbox' onclick=\"setvalue('".$type['id']."',this.value,this.checked);\"";

                                                                    if (preg_match("/$option_value/",$conf_value))
                                                                    {
                                                                        $input.= " checked='checked' ";
                                                                    }

                                                                    $input.= "value='$option_value'/>$option_text<br/>";
                                                                }
                                                                // Select combo
                                                            }
                                                            else
                                                            {
                                                                $select_change = ($type['onchange'] != "") ? "onchange=\"".$type['onchange']."\"" : "";
                                                                $select_id = ($type['id'] != "") ? "id=\"".$type['id']."\"" : "";
                                                                $input.= "<select name='value_$count' $select_change $select_id $disabled>";
                                                                if (!$conf_value && $type['default'] != '')
                                                                {
                                                                    $conf_value = $type['default'];
                                                                }

                                                                if ($type['value'] != '')
                                                                {
                                                                    $conf_value = $type['value'];
                                                                }

                                                                if ($conf_value == '')
                                                                {
                                                                    $input.= "<option value=''></option>";
                                                                }

                                                                $grp = false;
                                                                foreach($type['type'] as $option_value => $option_text)
                                                                {
                                                                    if ( preg_match("/^optgroup\d+/",$option_value) )
                                                                    {
                                                                        if ($grp)
                                                                        {
                                                                            $input.= "</optgroup>";
                                                                        }
                                                                        $input .= "<optgroup label=\"$option_text\">";
                                                                        $grp = true;
                                                                        continue;
                                                                    }
                                                                    $input.= "<option ";

                                                                    if ($conf_value == $option_value)
                                                                    {
                                                                        $input.= " selected=\"selected\" ";
                                                                    }

                                                                    $input.= "value=\"$option_value\">$option_text</option>";
                                                                }
                                                                if ($grp)
                                                                {
                                                                    $input.= "</optgroup>";
                                                                }
                                                                $input.= "</select>";
                                                                // Add some extra text
                                                                if (!empty($type['more']))
                                                                {
                                                                    $input .= $type['more'];
                                                                }
                                                            }
                                                        }
                                                        /* textarea */
                                                        elseif ($type['type'] == 'textarea')
                                                        {
                                                            $input.="<div class='clear_ssl_block'>";

                                                            if($type['ssl_remove_button'] && $conf_value == 'default' ) {
                                                                $input.= "<div  class='clear_ssl_button '>Remove</div >";
                                                            }
                                                            if($type['aspass'] ) {
                                                                $input.=  ($conf_value == 'default')  ? "<input class='clear_ssl' type='hidden' size='30' name=\"value_$count\" value=\"default\">" : "<label class='ssl_file_name'><span></span></label><input style='height: 23px'  data-bind='browse'  type='text'> <input type=\"button\" data-bind='browse'  value='Browse'>  <input  class='sll_data' type='file' size='30' name=\"value_$count\" > " ;
                                                            }else {
                                                                $input.= "<textarea rows='3' cols='40' name=\"value_$count\" $disabled>$conf_value</textarea>";
                                                            }

                                                            $input.="</div>";
                                                        }
                                                        /* link */
                                                        elseif ($type['type'] == 'link')
                                                        {
                                                            $input.= ( $type['disabled'] == 1 ) ? '<span class="disabled" style="color:gray">'._("Feature not available").'</span>' : $type['value'];
                                                        }
                                                        /* Custom HTML value is ignored */
                                                        elseif ($type["type" ]== 'html')
                                                        {
                                                            $input.= $type['value']."<input type='hidden' name='value_$count' value='skip_this_config_value'>";
                                                        }
                                                        /* datetime */
                                                        elseif ($type["type" ]=='datetime')
                                                        {
                                                            $val = json_decode($conf_value);
                                                            $id1 = "datetime-value_{$type['id']}_date";
                                                            $id2 = "datetime-value_{$type['id']}_time";
                                                            $id3 = "datetime-value_{$type['id']}";
                                                            $onchnage = "var obj = {day: \$(\"#$id1\").val(),time: \$(\"#$id2\").val()}; \$(\"#$id3\").val(JSON.stringify(obj));";
                                                            $input.= "
                                            <input type='hidden' name='value_{$count}' value='$conf_value' id='$id3'/>
                                            <select name='value_{$count}_date' id='$id1' onchange='$onchnage' $disabled>
						<option value=''>"._("Day of the week")."</option>";
                                                            foreach (array(_("Sunday"),_("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday")) as $i=>$day) {
                                                                $input.= "<option ".($i==$val->day ? 'selected="selected"' : "")." value='$i'>$day</option>";
                                                            }
                                                            $input.= "</select>
                                            <select name='value_{$count}_time' id='$id2' onchange='$onchnage' $disabled>
                                                <option value=''>"._("Time")."</option>";
                                                            for ($i=0;$i<24;$i++) {
                                                                $input.= "<option ".($i==$val->time ? 'selected="selected"' : "")." value='$i'>".str_pad($i, 2, "0", STR_PAD_LEFT).":00</option>";
                                                            }
                                                            $input.= "</select>";
                                                        }
                                                        /* input */
                                                        else
                                                        {
                                                            $conf_value = ($type['type']=="password") ? Util::fake_pass($conf_value) : str_replace("'", "&#39;", $conf_value);
                                                            $autocomplete = ($type['type']=="password") ? "autocomplete='off'" : "";
                                                            $select_change = ($type['onchange'] != "") ? "onchange=\"".$type['onchange']."\"" : "";
                                                            $input_id = ($type['id'] != '') ? "id=\"".$type['id']."\"" : "";
                                                            $classname = ($type['classname'] != '') ? "class=\"".$type['classname']."\"" : "";
                                                            $input.= "<input type='" . $type['type'] . "' size='30' name='value_$count' $style $input_id $classname value='$conf_value' $select_change $disabled $autocomplete/>";
                                                        }

                                                        echo $input;
                                                    }
                                                    ?>
                                                </td>

                                                <td class='conf_help_td'>
                                                    <?php  if(!empty($type["help"])) { ?>
                                                        <?php
                                                        $conf_info = str_replace("'", "&apos;", $var)."###".str_replace("\n", " ", str_replace("'", "&apos;", $type["help"]));
                                                        $help_id = 'help_'.$count;
                                                        $info_id = 'info_'.$count;

                                                        ?>
                                                        <img src="/ossim/pixmaps/help_small.png" id='<?php echo $help_id?>' class='conf_help help_icon_small'/>
                                                        <div class='conf_info' id='<?php echo $info_id?>'><?php echo $conf_info?></div>
                                                    <?php }?>
                                                </td>

                                            </tr>

                                            <?php
                                            $count+= 1;
                                        }
                                    }
                                    ?>
                                </table>

                            </div>
                            <?php
                            $div++;
                            $found = 0;
                        }
                    }
                    ?>
                </div>

            </td>

            <td class="conf_table_right">

                <input type="text" placeholder="<?php echo _('Find Word') ?>" id="word" name="word" value="<?php echo $s ?>"/>
                <input type="button" name="search" class='av_b_secondary small' id='search' value="<?php echo _('Search')?>"/>

                <input type='hidden' name="adv" value="<?php echo ($advanced) ? '1' : '' ?>"/>
                <input type='hidden' name="section" value="<?php echo $section ?>"/>
                <input type="hidden" name="nconfs" value="<?php echo $count ?>"/>

                <br/><br/><br/>
                <?php
                submit();
                ?>

            </td>
        </tr>
    </table>
</form>
<form method="POST" id="fidf" style='display:none' action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" autocomplete="off">
    <input type='hidden' name="adv" value="<?php echo ($advanced) ? '1' : '' ?>"/>
    <input type='hidden' name="section" value="<?php echo $section ?>"/>
    <input type="hidden" name="nconfs" value="<?php echo $count ?>"/>
    <input type="hidden" id="fword" name="word"/>
</form>
<a name="end"></a>
</body>
</html>
