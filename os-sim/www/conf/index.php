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

if ($_GET['section'] == 'vulnerabilities')
{ 
	header('Location:../vulnmeter/webconfig.php');
}
elseif ($_GET['section'] == 'hids')
{ 
	header('Location:../ossec/config.php');
}
elseif ($_GET['section'] == 'wids')
{ 
	header('Location:../wireless/setup.php');
}
elseif ($_GET['section'] == 'assetdiscovery')
{ 
	header('Location:../net/assetdiscovery.php');
}


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
$flag_status    = $_GET['status'];
$error_string   = $_GET['error'];
$warning_string = $_GET['warning'];
$word           = (POST('word') != '') ? POST('word') : ((GET('word') != '') ? GET('word') : '');

ossim_valid($section, OSS_ALPHA, OSS_NULLABLE,                                                                    'illegal:' . _('Section'));
ossim_valid($flag_status, OSS_DIGIT, OSS_NULLABLE,                                                                'illegal:' . _('Flag status'));
ossim_valid($error_string, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, OSS_SPACE, OSS_COLON, OSS_SCORE, '\/',            'illegal:' . _('Error string'));
ossim_valid($warning_string, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, OSS_SPACE, OSS_COLON, OSS_SCORE, '\.,\/\(\)',   'illegal:' . _('Warning string'));
ossim_valid($word, OSS_INPUT, OSS_NULLABLE,		                                                                  'illegal:' . _('Find Word'));

if (ossim_error()) 
{
    die(ossim_error());
}

if ($flag_status == 1)
{ 
	$status_message = _('Configuration successfully updated');
}
elseif($flag_status == 2)
{
	$status_message =  $error_string;
}

//Connect to db */
$db    = new ossim_db();
$conn  = $db->connect();


//Sensor List
$_list_data  = Av_sensor::get_list($conn, array('order_by' => 'name ASC'));
$all_sensors = $_list_data[0];

$sensor_list = array('0' => 'First available sensor');

foreach ($all_sensors as $sensor_id => $sensor)
{
	$sensor_list[$sensor['name']] = $sensor['name'].' ['.$sensor['ip'].']';
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
		foreach ($entities_all as $k => $v )
		{
			if(!Acl::is_logical_entity($conn, $k))
			{
				$entities[$k] = $v;
			}
		}
	}
	else{
		$entities[''] = '- '._('No entities found').' -';	
	}

	asort($entities);
}

// OTX
$open_threat_exchange_last = $conf->get_conf("open_threat_exchange_last");
// Show the contribute button or the yes/no select box
if ($conf->get_conf("open_threat_exchange") != '')
{
    $otx_option = array(
        'type' => array(
            'yes' => _('Yes'),
            'no' => _('No')
        ),
        'help' => _('Send information about logs'),
        'desc' => _('Contribute threat information to AlienVault OTX?'),
        'section' => 'otx',
        'id' => 'otx_select',
        'onchange' => 'change_otx(this.value)',
        'advanced' => 1
    );
}
else
{
    $otx_option = array(
        'type' => 'html',
        'help' => _('Send information about logs'),
        'desc' => _('Contribute threat information to AlienVault OTX?'),
        'section' => 'otx',
        'id' => 'otx_select',
        'value' => '<select id="otx_select" onchange="change_otx(this.value)" style="display:none"><option value="yes">'._('Yes').'</option><option value="no">'._('No').'</option></select><input type="button" class="av_b_secondary small" value="'._('Contribute').'" id="otx_contribute">',
        'advanced' => 1
    );
}

$CONFIG = array(
    'Ossim Framework' => array(
        'title' => _('Ossim Framework'),
        'desc'  => _('PHP Configuration (graphs, acls, database api) and links to other applications'),
        'advanced' => 1,
    	'section' => 'alarms',
            'conf' => array(
                'use_resolv' => array(
                    'type' => array(
                        '0' => _('No'),
                        '1' => _('Yes')
                    ),
                    'help' => '' ,
                    'desc' => _('Resolve IPs'),
                    'section' => 'alarms',
                    'advanced' => 1
                ),
                'nfsen_in_frame' => array(
                    'type'  => array(
                        '0' => _('No'),
                        '1' => _('Yes')
                    ),
                    'help'  => '',
                    'desc'  => _('Open Remote NFsen in the same frame'),
                    'advanced' => 1
                ),
                'ntop_link' => array(
                    'type'  => $sensor_list,
                    'help'  => '' ,
                    'desc'  => _('Default Ntop Sensor'),
                    'advanced' => 1
                ),             
                'md5_salt' => array(
                    'type' => 'text',
                    'help' => '' ,
                    'desc' => _('MD5 salt for passwords'),
                    'advanced' => 1
                )
            )
        ),
    'Metrics' => array(
        'title' => _('Metrics'),
        'desc' => _('Configure metric settings'),
        'advanced' => 0,
    	'section' => 'metrics',
        'conf' => array(
            'recovery' => array(
                'type' => 'text',
                'help' => '' ,
                'desc' => _('Recovery Ratio'),
                'advanced' => 0 ,
    			'section' => 'metrics'
            ),
            'threshold' => array(
                'type' => 'text',
                'help' => '' ,
                'desc' => _('Global Threshold'),
                'advanced' => 0 ,
            	'section' => 'metrics'
            ),
            'def_asset' => array(
                'type' => 'text',
                'help' => '' ,
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
                'help' => _("Store in SIEM if event's priority >= this value").",<br>&nbsp;&nbsp;&nbsp;"._('CLI action required:').' '._('Maintenance->Alienvault Services->Restart Alienvault Server Service'),
                'desc' => _('Security Events process priority threshold'),
                'advanced' => 1,
                'section' => 'metrics',
                'disabled' => (Session::is_pro()) ? 0 : 1
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
            'frameworkd_backup_storage_days_lifetime' => array(
                'type' => 'text',
                'help' => _('Number of days Siem events are stored in hard-disk'),
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
                'help' => _('Maximum number of events stored in SQL Database<br/>(0 value does no limit)'),
                'desc' => _('Events to keep in the Database (Number of events)'),
            	'section' => 'siem',
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
                'help' => _('Number of days to keep alarms for (0 never expires)'),
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
                'help' => _('Number of days to keep Logs for (0 never expires)'),
                'desc' => _('Active Logger Window'),
                'onchange' => 'check_logger_lifetime(this.value)' ,
            	'style' => ($conf->get_conf('logger_storage_days_lifetime') > 0) ? '' : 'color:gray' ,
                'advanced' => 0,
                'disabled' => (Session::is_pro()) ? 0 : 1
            )
        )
    ),
    'Vulnerability Scanner' => array(
        'title' => _('Vulnerability Scanner'),
        'desc' => _('Vulnerability Scanner configuration'),
        'advanced' => 0,
    	'section' => 'vulnerabilities',
        'conf' => array(
            'nessus_user' => array(
                'type' => 'text',
                'help' => '' ,
                'desc' => _('Scanner Login'),
                'advanced' => 1 ,
            	'section' => 'vulnerabilities'
            ),
            'nessus_pass' => array(
                'type' => 'password',
                'help' => '' ,
                'desc' => _('Scanner Password'), 
                'advanced' => 1 ,
            	'section' => 'vulnerabilities'
            ),
            'nessus_host' => array(
                'type' => 'text',
                'help' => _('Only for non distributed scans'),
                'desc' => _('Scanner host'),
                'advanced' => 1 ,
            	'section' => 'vulnerabilities'
            ),
            'nessus_port' => array(
                'type' => 'text',
                'help' => _('Defaults to port 1241 on Nessus, 9390 on OpenVAS'),
                'desc' => _('Scanner port'),
                'advanced' => 1 ,
            	'section' => 'vulnerabilities'
            ),
            'nessus_pre_scan_locally' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => _('Do not pre-scan from scanning sensor'),
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
                'help' => _('Any vulnerability with a higher risk level than this value will get <br/> insertedautomatically into DB.'),
                'desc' => _('Vulnerability Ticket Threshold'),
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
            'user_life_time' => array(
                'type' => 'text',
                'help' => _('Expired life time for current user in days. (0=never expires)'),
                'desc' => _('User Life Time (days)'),
                'advanced' => 0 ,
    			'section' => 'userlog'
            ),
            'user_action_log' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => '',
                'desc' => _('Enable User Log'),
                'advanced' => 0 ,
                'section' => 'userlog'
            ),
            'log_syslog' => array(
                'type' => array(
                    '0' => _('No'),
                    '1' => _('Yes')
                ),
                'help' => '' ,
                'desc' => _('Log to syslog'),
                'advanced' => 0 ,
                'section' => 'userlog'
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
                'help' => 'Ldap server IP or host name',
                'desc' => _('Ldap server address'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_port' => array(
                'type' => 'text',
                'help' => 'TCP port to connect Ldap server<br/>By default the port is 389 or 636 if you use SSL',
                'id' => 'ldap_port',
                'desc' => _('Ldap server port'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_ssl' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('use Ldap server ssl?'),
                'desc' => _('Ldap server ssl'),
                'onchange' => 'change_ldap_port(this.value)' ,
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_baseDN' => array(
                'type' => 'text',
                'help' => 'Example: dc=local,dc=domain,dc=net' ,
                'desc' => _('Ldap server baseDN'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_filter_to_search' => array(
                'type' => 'text',
                'help' => _('Filter to search the users for ossim in LDAP<br />Example for LDAP:<br/> (&(cn=%u)(objectClass=account)) <b>or</b> (uid=%u) <b>or</b> (&(cn=%u)(objectClass=OrganizationalPerson))<br/>Example for AD:<br/> (&(sAMAccountName=%u)(objectCategory=person)) <b>or</b> (userPrincipalName=%u) %u is the user'),
                'desc' => _('Ldap server filter for LDAP users'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_bindDN' => array(
                'type' => 'text',
                'help' => _('Account to search the user in LDAP <br/>Example: user@example.com'),
                'desc' => _('Ldap Username'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_valid_pass' => array(
                'type' => 'password',
                'help' => _('Password of Ldap Username'),
                'desc' => _('Ldap password for Username'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'login_ldap_require_a_valid_ossim_user' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => '',
                'desc' => _('Require a valid ossim user for login?'),
                'advanced' => 1 ,
                'onchange' => (Session::is_pro()) ? 'change_ldap_need_user(this.value)' : '' ,
                'section' => 'users'
            ),
            'login_create_not_existing_user_entity' => array(
                'type' => $entities ,
                'help' => '',
                'id'   => 'user_entity',
                'desc' => _('Entity for new user'),
                'advanced' => 1 ,
                'section' => 'users',
            ),
            'login_create_not_existing_user_menu' => array(
                'type' => $menus ,
                'help' => '',
                'id'   => 'user_menu',
                'desc' => _('Menus for new user'),
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
                'help' => _('Number (default = 0) -> 0 disable'),
                'desc' => _('Password history'),
                'advanced' => 1 ,
                'section' => 'users'
            ),
            'pass_complex' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no' => _('No')
                ),
                'help' => _('3 of these group of characters -> lowercase, uppercase, numbers and special characters'),
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
			'tickets_send_mail' => array(
                'type' => array(
                    'yes' => _('Yes'),
                    'no'  => _('No')
                ),
                'help' => '',
                'desc' => _('Send email notification'),
            	'section'  => 'tickets',
                'advanced' => 0
            ),
            'tickets_max_days' => array(
                'type' => 'text',
                'help' => '' ,
                'desc' => _('Maximum days for email notification'),
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
    'OTX' => array(
        'title' => _('Open Threat Exchange'),
        'desc' => _('Open Threat Exchange Configuration'),
        'advanced' => 1,
    	'section' => 'otx',
        'conf' => array(
            'open_threat_exchange' => $otx_option,
            'open_threat_exchange_token' => array(
                    'type' => 'html',
                    'classname' => 'otx_token',
                    'id' => 'otx_token',
                    'help' => _('OTX Token'),
                    'desc' => _('OTX Token'),
                    'section' => 'otx',
                    'style' => ($conf->get_conf('open_threat_exchange') == 'yes') ? '' : 'color:gray' ,
                    'value' => "<input type='text' id='otx_token' value='".$conf->get_conf('open_threat_exchange_key')."' placeholder='"._('Enter Token')."'> <input type='button' class='av_b_secondary small' value='".(($conf->get_conf('open_threat_exchange_key') != '') ? _('Submit') : _('Join Now'))."' id='send_otx_token' disabled>" ,
                    'advanced' => 1
            ),
            'open_threat_exchange_username' => array(
                'type' => 'html',
                'classname' => 'otx',
                'help' => _('OTX Username'),
                'desc' => _('OTX Username'),
                'section' => 'otx',
            	    'style' => ($conf->get_conf('open_threat_exchange') == 'yes') ? '' : 'color:gray' ,
            	    'value' => "<span id='otx_username' class='otx' ".(($conf->get_conf('open_threat_exchange') == 'yes') ? '' : "style='color:gray'").">".$conf->get_conf('open_threat_exchange_username')."</span>" ,
                'advanced' => 1
            ),
            'open_threat_exchange_last' => array(
                'type' => 'html',
            	'classname' => 'otx',
            	'help' => _('Last contribution to OTX'),
            	'desc' => _('Last contribution to OTX'),
            'section' => 'otx',
            	'value'=> "<span class='otx' ".(($conf->get_conf('open_threat_exchange') != 'yes') ? "style='color:gray'" : "").">".(($open_threat_exchange_last == "") ? "<span style='margin-right:15px;'>Never</span>" : "<b>".gmdate("Y-m-d H:i:s", strtotime($open_threat_exchange_last." GMT")+(3600*$tz))."</b>")."</span> <input type='button' value='Send now' onclick=\"GB_show('"._("Send Threat Information")."', '../updates/otxsend.php', 450, '70%');\" class='av_b_secondary small otx' ".(($conf->get_conf('open_threat_exchange') != 'yes') ? "disabled='disabled'" : "").">",
           		'style' => ($conf->get_conf('open_threat_exchange') == 'yes') ? '' : 'color:gray' ,
                'advanced' => 1
            )
        )
    ),
);

ksort($CONFIG);

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
    }
    
    return TRUE;
}

function submit()
{
	?>
		<!-- submit -->
		<input type="button" class='av_b_secondary' id="enable_notifications" onclick="av_notification()" value=" <?php echo _("Enable Desktop Notifications"); ?> "/><br>
		
		<script type='text/javascript'>
		function RequestPermission(callback) 
		{ 
		    window.webkitNotifications.requestPermission(callback);     		
		}
		
		
		function av_notification() 
		{
			if (window.webkitNotifications.checkPermission() > 0) 
			{
    			RequestPermission(av_notification);
  			}
  			notificationw = window.webkitNotifications.createNotification('/ossim/statusbar/av_icon.png',
  			   "<?php echo Util::js_entities(html_entity_decode(_('Thank you'))) ?>",
  			   "<?php echo Util::js_entities(html_entity_decode(_('Notifications enabled successfully')))?>");
  			
  			notificationw.show();
  			
  			setTimeout (function() { notificationw.cancel(); }, '10000');
		}
		if (window.webkitNotifications) 
		{ 
		    $('#enable_notifications').show(); 
		}
		</script>
		
		<input type="submit" name="update" id="update" value=" <?php echo _('Update configuration'); ?> "/>
		
		<br/><br/>
		<!-- end sumbit -->
	<?php
}
if (POST('update'))
{
    $numeric_values = array(
        'backup_events',
        'alarms_lifetime',
        'logger_storage_days_lifetime',
        'frameworkd_backup_storage_days_lifetime',
        'backup_netflow',
        'server_port',
        'use_resolv',
        'use_ntop_rewrite',
        'use_munin',
        'frameworkd_port',
        'frameworkd_controlpanelrrd',
        'frameworkd_donagios',
        'frameworkd_alarmincidentgeneration',
        'frameworkd_optimizedb',
        'frameworkd_listener',
        'frameworkd_scheduler',
        'frameworkd_businessprocesses',
        'frameworkd_eventstats',
        'frameworkd_backup',
        'frameworkd_alarmgroup',
        'snort_port',
        'recovery',
        'threshold',
        'backup_port',
        'backup_day',
        'nessus_port',
        'nessus_distributed',
        'vulnerability_incident_threshold',
        'have_scanmap3d',
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
        'smtp_port'
    );

    $passwords = array(
        'remote_key',
        'backup_pass',
        'login_ldap_valid_pass',
        'snort_pass',
        'solera_pass',
        'nessus_pass'
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
	$error_string   = '';
    $warning_string = '';
	
	for ($i = 0; $i < POST('nconfs'); $i++)
	{
	    if(POST("conf_$i") == "nessus_path")
	    {
    	    $_POST["value_$i"] = "/usr/bin/omp";
	    }
	    
	    if(POST("conf_$i") == "nessus_updater_path")
	    {
    	    $_POST["value_$i"] = "/usr/sbin/openvas-nvt-sync";
	    }
	    
	    if(POST("conf_$i") == 'scanner_type')
	    {
             $_POST["value_$i"] = 'openvas3omp';
        }
	
        if(POST("conf_$i") == "pass_length_max")
		{
            $pass_length_max = POST("value_$i");
            continue;
        }
		
		if(POST("conf_$i") == "pass_expire")
		{
            $pass_expire_max = POST("value_$i");
        }
		
		if(POST("conf_$i") == "pass_expire_min")
		{
            $pass_expire_min = POST("value_$i");
        }

		if(in_array(POST("conf_$i"), $numeric_values) && intval(POST("value_$i")) < 0)
		{
            $variable = $_SESSION['_main']['conf_'.$i];
            
            if(empty($warning_string)) {
                $warning_string .= _("Configuration successfully updated, but we found the following errors:");
            }
            
            $warning_string     .= " "._("Invalid $variable, it has to be equal or greater than zero.");

            $flag_status         = 3;
            
            $_POST["value_$i"]   = 0;
        }
        
        if( POST("conf_$i") == "pass_length_min" )
		{
            if (POST("value_$i") < 1) 
            {
                $_POST["value_$i"] = 7;
            }
            
            $pass_length_min = POST("value_$i");
        }
		
        // passwords array contains some variables to validate with OSS_PASSWORD constant

        if(in_array(POST("conf_$i"), $passwords)) 
        {
            ossim_valid(POST("value_$i"), OSS_NULLABLE, OSS_PASSWORD, 'illegal:' . POST("conf_$i"));
        }
        else 
        {
            ossim_valid(POST("value_$i"), OSS_ALPHA, OSS_NULLABLE, OSS_SCORE, OSS_DOT, OSS_PUNC, "\{\}\|;\(\)\%\\", 'illegal:' . POST("conf_$i"));
        }
        
        if(POST("value_$i") != '') 
        {
            if (!(ossim_error() || (valid_value(POST("conf_$i"), POST("value_$i"), $numeric_values, $s_error))))
            {
                if ($flag_status == 2)
                {
                    $error_string .= ' ';
				}
				
                $error_string .= $s_error;
                $flag_status   = 2;
            }
        }
	}
	if ($flag_status != 2)
	{
		for ($i = 0; $i < POST('nconfs'); $i++)
		{
			if ( isset($_POST["conf_$i"]) && isset($_POST["value_$i"]) )
			{
				if (($pass_fields[POST("conf_$i")] == 1 && Util::is_fake_pass(POST("value_$i"))) || POST("value_$i") == 'skip_this_config_value')
				{
					continue;
				}
				else
				{
					$before_value = $ossim_conf->get_conf(POST("conf_$i")); 
					$config->update(POST("conf_$i"), POST("value_$i"));
					
					if (POST("value_$i") != $before_value)
					{ 
						Log_action::log(7, array("variable: ".POST("conf_$i")));
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

    
    
	header("Location: " . $_SERVER['SCRIPT_NAME'] . "?word=" . $word . "&section=" . $section . "&status=" . $flag_status . "&error=" . urlencode($error_string) . "&warning=" . urlencode($warning_string));

    exit();
}

if (REQUEST('reset'))
{
    if (!(GET('confirm'))) 
	{
		?>
        <p align="center">
			<b><?php echo _('Are you sure ?') ?></b><br/>
			<a href="?reset=1&confirm=1"><?php echo _('Yes') ?></a>&nbsp;|&nbsp;
			<a href="main.php"><?php echo _('No') ?></a>
        </p>
		<?php
        exit();
    }
	
    
    $config = new Config();
    $config->reset();
    
    header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?word=' . $word . '&section=' . $section);
    exit();
}

$default_open = intval(GET('open'));
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
            array('src' => 'tipTip.css',                    'ef_path' => TRUE)
        );
    
        Util::print_include_files($_files, 'css');
    
    
        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
            array('src' => 'utils.js',                      'def_path' => TRUE),
            array('src' => 'notification.js',               'def_path' => TRUE),
            array('src' => 'token.js',                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',         'def_path' => TRUE),
            array('src' => 'greybox',                       'def_path' => TRUE)
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
                
            $(".conf_help").each(function(index) {
            
            	var help_id = '#'+$(this).attr('id');
            	var id      = $(this).attr('id').replace('help_', '');
            	var info_id = '#info_' + id;
            	           	           	
            	var data = $(info_id).text().split('###');  
            	            	          	
            	var conf_info = '<table class="t_conf_info" border="0" cellpadding="1" cellspacing="1">' +
            	                      '<tr>' +
            	                            '<td>' +
            	                                 '<b>'+ data[0] +'</b><br><i>'+ data[1] + '</i>' +
            	                            '</td>' + 
            	                      '</tr>' + 
            	                 '</table>'
                        	
            	
            	$(help_id).tipTip({defaultPosition: 'top', maxWidth: "400px", content: conf_info, edgeOffset: 3});
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
                $("#idf").submit();
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
				$('#alarms_lifetime').css('color','black').val('7');
			} 
			else 
			{
				$('#alarms_lifetime').css('color','gray').val('0');
			}
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

        //********************** OTX **************************

	    // Change OTX select option
	    function change_otx(val)
	    {
		    if (val == 'yes')
		    {
		        
                // Activate Join Now button only the first time
    	            // If user already has a token, it activates when input focus
    	            if ($('#otx_token').val() == '')
                {
		            $('#send_otx_token').attr('disabled', false);
                }

    		        $('.otx_token').css('color', '');
    
    		        if ($('#otx_username').html() != '')
    		        {
    		            $('.otx').css('color', '');
    		            $('.otx').attr('disabled', false);
		        }
		    }
		    else
		    {
		        $('#send_otx_token').attr('disabled', true);
		        $('.otx').attr('disabled', true);
		        $('.otx_token').css('color', 'gray');
		        $('.otx').css('color', 'gray');
		    }
	    }

	    // Send OTX Token
	    function get_otx_user()
	    {
	        var data      = {};
	        data['token'] = $('#otx_token').val();
	            
	        var ctoken = Token.get_token("configuration_main");
        	    	$.ajax(
        	    	{
        	    		url: "<?php echo AV_MAIN_PATH ?>/conf/ajax/get_otx_user.php?token="+ctoken,
        	    		data: data,
        	    		type: "POST",
        	    		dataType: "json",
        	    		beforeSend: function()
        	    		{
        	        		$('#send_otx_token').addClass('av_b_processing');
        	    		},
        	    		success: function(data)
        	    		{    		    
        	        		if (typeof data != 'undefined' && data != null)
        	        		{
        	        		    $('#send_otx_token').removeClass('av_b_processing');
        	        		    
        	            		if (data.error)
        	            		{
        	                		show_notification('av_info', data.msg, 'nf_error', 5000);
        	                		
        	                		return false;
        	            		}
        	            		// Successfully activated in OTX
        	            		else
        	            		{
                	            	$('#otx_contribute').hide();
                	            	$('#otx_select').show();
                    	            $('#otx_username').html(data.msg);
                    	            $('.otx').removeAttr('disabled');
                    	            $('.otx').css('color', '');
                    	            $('#send_otx_token').val('<?php echo _('Submit') ?>');
                    	            $('#send_otx_token').attr('disabled', true);
                    	            
                    	            if (typeof parent.load_notifications == 'function')
                    	            {
                        	            parent.load_notifications();
                    	            }
        	            		}
        	            }
        	                
        	    		},
        	    		error: function(XMLHttpRequest, textStatus, errorThrown) 
        	    		{	
        	    		    $('#send_otx_token').removeClass('av_b_processing');
        	    		    
        	            //Checking expired session
        	        		var session = new Session(XMLHttpRequest, '');
    	                if (session.check_session_expired() == true)
    	                {
    	                    session.redirect();
    	                    return;
    	                }
    
    	                show_notification('av_info', errorThrown, 'nf_error', 5000);
        	                
        	    		}
        	    	});
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
                $("#basic-accordian").accordion(
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
			if (intval(GET('passpolicy')) == 1)  
			{    			
			 ?>
                $('#test9-header').click(); 
            <?php  
			}  

			if ($_GET["open"]=='0')  
			{    			
			 ?>
                $('#test-header').click(); 
            <?php  
            } elseif ($default_open>0)  
			{    			
			 ?>
                $('#test<?=$default_open?>-header').click(); 
            <?php  
            }  
            ?>
            
            $('#idf').bind('keypress', function(event) 
            {
                if( event.keyCode==13)
                {
                    event.preventDefault();
                    var id_focus = event.target.id
                                                                                             
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

            // OTX
            // Popup
            $('#otx_contribute').on('click', function()
            {
                var url  = "https://www.alienvault.com/my-account/customer/signup-or-thanks/?ctype=<?php echo (Session::is_pro()) ? 'usm' : 'ossim' ?>";
                
                av_window_open(url, 
                {
                    width: 800,
                    height: 750,
                    title: 'otxwindow'
                })
            });
            // Activate token submit button only when token is pasted
            $('#otx_token').on('focus', function()
            {
                $('#send_otx_token').attr('disabled', false);
            });
            // Join Now/Submit action
            $('#send_otx_token').on('click', function()
            {
                get_otx_user();
            });
            
            show_tooltips();
              
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
        $txt   = _('We found the following errors');
        $txt  .= "<BR/>".$status_message;
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
                'style'   => 'width: 60%; margin: 20px auto; text-align: center;'
            ); 
                            
        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    
	?>
	
	<form method="POST" id="idf" style="margin:0px auto" <?php echo $onsubmit;?> action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" autocomplete="off" />

	<table align='center' class='conf_table'>
	
	<tr>
		<td class="conf_table_left">
			<div id="basic-accordian">
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
										<strong><?php echo (in_array($conf, $arr)) ? "<span style='color:#4F8A10'>".$var."</span>" : $var; ?></strong>
									</td>
								
									<td class="left" style="white-space:nowrap">
									<?php
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
												
												if ($type['value'] != '') 
												{ 
												   $conf_value = $type['value']; 
												}
												
												if ($conf_value == '') 
												{
													$input.= "<option value=''></option>";
												}
												
												foreach($type['type'] as $option_value => $option_text)
												{
													$input.= "<option ";
													
													if ($conf_value == $option_value)
													{ 
														$input.= " selected='selected' ";
													}
													
													$input.= "value='$option_value'>$option_text</option>";
												}
												
												$input.= "</select>";
											}
										}
										/* textarea */
										elseif ($type['type'] == 'textarea')
										{
											$input.= "<textarea rows='2' cols='28' name=\"value_$count\" $disabled>$conf_value</textarea>";
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
										/* input */
										else
										{
											$conf_value = ($type['type']=="password") ? Util::fake_pass($conf_value) : $conf_value;
											$select_change = ($type['onchange'] != "") ? "onchange=\"".$type['onchange']."\"" : "";
											$input_id = ($type['id'] != '') ? "id=\"".$type['id']."\"" : "";
											$classname = ($type['classname'] != '') ? "class=\"".$type['classname']."\"" : "";
											$input.= "<input type='" . $type['type'] . "' size='30' name='value_$count' $style $input_id $classname value='$conf_value' $select_change $disabled/>";
										}
										
										echo $input;
										
									?>
									</td>
				
									<td class='conf_help_td'>															
										<?php
										$conf_info = str_replace("'", "\'", $var)."###".str_replace("\n", " ", str_replace("'", "\'", $type["help"]));
										$help_id = 'help_'.$count;
										$info_id = 'info_'.$count;
										
										?>												
										<img src="../pixmaps/help_small.png" id='<?php echo $help_id?>' class='conf_help help_icon_small'/>
										<div class='conf_info' id='<?php echo $info_id?>'><?php echo $conf_info?></div>
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
<a name="end"></a>
</body>
</html>
