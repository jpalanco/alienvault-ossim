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
$family_id        = POST('family_id');
$category_id      = POST('category_id');
$cve              = POST('cve');
$plugin           = POST('plugin');
$action           = POST("action");
$enabled_plugins  = POST("enabled_plugins");
$disabled_plugins = POST("disabled_plugins");
$families         = POST("families");
$enable_all       = POST("enable_all");
$disable_all      = POST("disable_all");
$sensor_id        = POST("sensor_id");
$sname            = POST("sname");
$sdescription     = POST("sdescription");
$user             = POST("user");
$old_owner        = POST("old_owner");
$old_name         = POST("old_name");
$entity           = POST("entity");
$cloneid          = POST("cloneid");


$validate = array(
    'type'             => array('validation' => 'OSS_ALPHA, "_"',                                              'e_message'  =>  'illegal:' . _('Type')),
    'sid'              => array('validation' => 'OSS_SHA1, OSS_NULLABLE',                                      'e_message'  =>  'illegal:' . _('SID')),
    'family_id'        => array('validation' => 'OSS_SHA1, OSS_NULLABLE',                                      'e_message'  =>  'illegal:' . _('Family ID')),
    'category_id'      => array('validation' => 'OSS_SHA1, OSS_NULLABLE',                                      'e_message'  =>  'illegal:' . _('Category ID')),
    'plugin'           => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, "`", OSS_NULLABLE',       'e_message'  =>  'illegal:' . _('Plugin')),
    'cve'              => array('validation' => 'OSS_CVE_ID, OSS_NULLABLE',                                    'e_message'  =>  'illegal:' . _('CVE')),
    'action'           => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_NULLABLE',                          'e_message'  =>  'illegal:' . _('Action')),
    'enabled_plugins'  => array('validation' => 'OSS_DIGIT, "\,", OSS_NULLABLE',                               'e_message'  =>  'illegal:' . _('Enabled Plugins')),
    'disabled_plugins' => array('validation' => 'OSS_DIGIT, "\,", OSS_NULLABLE',                               'e_message'  =>  'illegal:' . _('Disabled Plugins')),
    'families'         => array('validation' => 'OSS_SHA1, "\,", OSS_NULLABLE',                                'e_message'  =>  'illegal:' . _('Families')),
    'enable_all'       => array('validation' => 'OSS_BINARY, OSS_NULLABLE',                                    'e_message'  =>  'illegal:' . _('Enable All')),
    'select_all'       => array('validation' => 'OSS_BINARY, OSS_NULLABLE',                                    'e_message'  =>  'illegal:' . _('Disable All')),
    'sname'            => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE', 'e_message'  =>  'illegal:' . _('Profile name')),
    'old_name'         => array('validation' => 'OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE',               'e_message'  =>  'illegal:' . _('Old profile name')),
    'sdescription'     => array('validation' => 'OSS_TEXT, OSS_PUNC_EXT, OSS_NULLABLE',                        'e_message'  =>  'illegal:' . _('Profile description')),
    'user'             => array('validation' => 'OSS_USER, OSS_NULLABLE, "\-"',                                'e_message'  =>  'illegal:' . _('User')),
    'entity'           => array('validation' => 'OSS_USER, OSS_NULLABLE, "\-"',                                'e_message'  =>  'illegal:' . _('Entity ID')),
    'old_owner'        => array('validation' => 'OSS_USER, OSS_NULLABLE, "\-", OSS_HEX',                       'e_message'  =>  'illegal:' . _('Old owner')),
    'cloneid'          => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_NULLABLE',                         'e_message'  =>  'illegal:' . _('Clone ID'))
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
        $search_filters = array(
            'family_id'   => $family_id,
            'category_id' => $category_id,
            'plugin'      => $plugin,
            'cve'         => $cve,
        );

        $plugins = array(
            'enabled'      => explode(",", $enabled_plugins),
            'enable_all'   => $enable_all,
            'disabled'     => explode(",", $disabled_plugins),
            'disable_all'  => $disable_all
        );

        $result = Vulnerabilities::saveplugins_in_db($dbconn, $sid, $search_filters, $plugins);

        break;

    case "save_sensor_plugins":
        $families = explode(",", $families);
        $result = Vulnerabilities::saveplugins_in_sensor($dbconn, $sensor_id, $sid, $families);

        break;

    case "plugins_available":

        $p_data = Vulnerabilities::get_plugin_stats_by_profile($dbconn, $sid);

        if(is_array($p_data))
        {
            $result = array(
                'status'  => 'success',
                'message' => $p_data
            );
        }
        else
        {
            $result = array(
                'status'  => 'error',
                'message' => _('No plugins info available')
            );
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
            else {
                $result = array(
                    'status'  => 'error',
                    'message' => _('Profile not found')
                );
            }
        }
        else
        {
            $result = Vulnerabilities::saveprefs_in_db($dbconn, $sid, $_POST);
        }

        break;

    case "update": // From autoenable section

        if($sensor_id != '')
        {
            $result = Vulnerabilities::check_profile_in_sensor ($dbconn, $sensor_id, $sid);

            if($result["status"] == "already_exits")
            {
                $result = Vulnerabilities::save_autoenable_plugins_in_sensor($dbconn, $sensor_id, $sid);
            }
            else {
                $result = array(
                    'status'  => 'error',
                    'message' => _('Profile not found')
                );
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

            $result = Vulnerabilities::update_db_profile($dbconn, $sid, $sname, $sdescription, $owner, $_POST);

            // change profiles owner and name in all sensors

            if(($old_owner != $owner || $old_name != $sname) && $result["status"] == "OK")
            {
                $result = Vulnerabilities::modify_profile_ID_in_sensors($dbconn, $sname, $owner);
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
            $result = Vulnerabilities::create_sensor_profile($dbconn, $sensor_id, $sname, $owner, $cloneid);
        }
        else
        {
            $result = Vulnerabilities::create_db_profile($dbconn, $sname, $sdescription, $owner, $cloneid, $_POST);
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

if ($result['status'] == 'error'){
    if (preg_match("/Failed to acquire socket/", $result['message']))
    {
        $result["message"] = _("Unable to connect to sensor, please check sensor status and Vuln Scanner Options");
    }
}

if (is_string($result['message'])){
    $result['message'] = preg_replace("/\s+'\s*'\s+/", "", $result['message']);
}

echo json_encode($result);

$dbconn->close();
