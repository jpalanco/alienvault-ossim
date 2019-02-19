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

require_once 'av_init.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$type             = POST("type");
$sid              = POST("sid");
$fam              = POST("fam");
$cve              = POST("cve");
$action           = POST("action");
$plugins          = POST("plugins");
$sensor_id        = POST("sensor_id");
$sname            = POST("sname");
$sdescription     = POST("sdescription");
$user             = POST("user");
$old_owner        = POST("old_owner");
$old_name         = POST("old_name");
$entity           = POST("entity");
$sautoenable      = POST("sautoenable");
$_POST['tracker'] = intval(POST("tracker"));
$tracker          = POST("tracker");
$cloneid          = POST("cloneid");

$validate = array(
    'type'          =>  array('validation' => 'OSS_ALPHA, "_"',                                                'e_message'  =>  'illegal:' . _('Type')),
    'sid'           =>  array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                                       'e_message'  =>  'illegal:' . _('SID')),
    'fam'           =>  array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                                       'e_message'  =>  'illegal:' . _('Family')),
    'cve'           =>  array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_NULLABLE',                             'e_message'  =>  'illegal:' . _('CVE')),
    'action'        =>  array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_NULLABLE',                            'e_message'  =>  'illegal:' . _('Action')),
    'plugins'       =>  array('validation' => 'OSS_DIGIT, "\,", OSS_NULLABLE',                                 'e_message'  =>  'illegal:' . _('Plugins')),
    'sensor_id'     =>  array('validation' => 'OSS_HEX, OSS_NULLABLE',                                         'e_message'  =>  'illegal:' . _('Sensor ID')),
    'sname'         =>  array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE',   'e_message'  =>  'illegal:' . _('Profile name')),
    'old_name'      =>  array('validation' => 'OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE',                 'e_message'  =>  'illegal:' . _('Old profile name')),
    'sdescription'  =>  array('validation' => 'OSS_TEXT, OSS_NULLABLE',                                        'e_message'  =>  'illegal:' . _('Profile description')),
    'user'          =>  array('validation' => 'OSS_USER, OSS_NULLABLE, "\-"',                                  'e_message'  =>  'illegal:' . _('User')),
    'entity'        =>  array('validation' => 'OSS_USER, OSS_NULLABLE, "\-"',                                  'e_message'  =>  'illegal:' . _('Entity ID')),
    'old_owner'     =>  array('validation' => 'OSS_USER, OSS_NULLABLE, "\-", OSS_HEX',                         'e_message'  =>  'illegal:' . _('Old owner')),
    'sautoenable'   =>  array('validation' => 'OSS_LETTER, OSS_NULLABLE',                                      'e_message'  =>  'illegal:' . _('Sautoenable')),
    'tracker'       =>  array('validation' => 'OSS_DIGIT',                                                     'e_message'  =>  'illegal:' . _('Tracker')),
    'cloneid'       =>  array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                                       'e_message'  =>  'illegal:' . _('Clone ID'))
);


$validation_errors = validate_form_fields('POST', $validate);

if (is_array($validation_errors) && !empty($validation_errors))
{
    $data = array(
        "status"  => 'error',
        "message" => implode('<br/>', $validation_errors)
    );

    echo json_encode($data);
    exit();
}


$db     = new Ossim_db();
$dbconn = $db->connect();

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

switch ($type)
{
    case "save_database_plugins":

        $result = Vulnerabilities::saveplugins_in_db($dbconn, explode(",", $plugins), $sid, $fam, $cve, $action);

        break;

    case "save_sensor_plugins":
        $result = Vulnerabilities::check_profile_in_sensor ($dbconn, $sensor_id, $sid);

        if($result["status"] == "already_exits")
        {
            $result = Vulnerabilities::saveplugins_in_sensor( $dbconn, $sensor_id, $sid, $fam, $action);
        }

        break;

    case "plugins_available":

        $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins where sid=$sid");
        list($pcount)=$result->fields;

        $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins where enabled='Y' and sid=$sid");
        list($penabled)=$result->fields;

        if(intval($pcount) > 0)
        {
            $result = array(
                'status'  => 'OK' ,
                'message' => "<strong>$pcount</strong> "._("Plugins available")." - <strong>$penabled</strong> - "._("enabled"));
        }
        else
        {
            $result = array(
                'status'  => 'error' ,
                'message' => _('No plugins info available'));
        }

        break;

    case "save_prefs":

        if($sensor_id != '')
        {
            $result = Vulnerabilities::check_profile_in_sensor($dbconn, $sensor_id, $sid);

            if($result["status"] == "already_exits")
            {
                $result = Vulnerabilities::saveprefs_in_sensor($dbconn, $sensor_id, $sid); // OMP sensor
            }
        }
        else
        {
            $result = Vulnerabilities::saveprefs_in_db($dbconn, $sid, $_POST);
        }

        break;

    case "update": // autoenable section

        if($sensor_id != '')
        {
            $result = Vulnerabilities::check_profile_in_sensor ($dbconn, $sensor_id, $sid);

            if($result["status"] == "already_exits")
            {
                $result = Vulnerabilities::saveplugins_in_sensor($dbconn, $sensor_id, $sid); // OMP sensor
            }
        }
        else
        {
            $owner = '';

            if (intval($user)!=-1)
            {
                $owner = $user;
            }
            elseif (intval($entity)!=-1)
            {
                $owner = $entity;
            }

            if($owner == '')
            {
                $owner = Session::get_session_user();
            }

            $result = Vulnerabilities::update_db_profile($dbconn, $sid, $sname, $sdescription, $owner, $sautoenable, $tracker, $_POST);

            // change profiles owner and name in all sensors

            if(($old_owner != $owner || $old_name != $sname) && $result["status"] == "OK")
            {
                $result = Vulnerabilities::modify_profile_ID_in_sensors($dbconn, $old_name, $old_owner, $sname, $owner);
            }
        }

        break;

    case "new":

        if (intval($user)!=-1)
        {
            $owner = $user;
        }
        elseif (intval($entity)!=-1)
        {
            $owner = $entity;
        }

        if($owner == '')
        {
            $owner = Session::get_session_user();
        }

        if($sensor_id != '')
        {
            $result = Vulnerabilities::create_sensor_profile($dbconn, $sensor_id, $sname, $owner, $cloneid); // OMP sensor
        }
        else
        {
            $result = Vulnerabilities::create_db_profile($dbconn, $sname, $sdescription, $sautoenable, $owner, $cloneid, $tracker, $_POST);
        }

        break;

    case "delete_sensor_profile":

        $result = Vulnerabilities::delete_sensor_config($dbconn, $sensor_id, $sid);

        break;

    case "delete_db_profile":

        $result = Vulnerabilities::delete_db_profile($dbconn, $sid);

        break;

    default:

        $result = array(
            'status'  => 'error',
            'message' => _('Invalid option')
        );
}

if (preg_match("/Failed to acquire socket/", $result['message']))
{
    $result["message"] = _("Unable to connect to sensor, please check sensor status and Vuln Scanner Options.");
}

$result['message'] = preg_replace("/\s+'\s*'\s+/", "", $result['message']);

echo json_encode($result);

$dbconn->close();
