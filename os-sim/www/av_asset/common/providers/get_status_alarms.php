<?php
/**
* get_status_alarms.php
*
* File get_status_alarms.php is used to:
* - Build JSON data that will be returned in response to the Ajax request made by Asset Tray
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

$asset_id   = POST('asset_id');
$asset_type = POST('asset_type');

Session::logcheck_by_asset_type($asset_type);
Session::logcheck_ajax('analysis-menu', 'ControlPanelAlarms');

session_write_close();

if (!valid_hex32($asset_id))
{
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}

// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


try
{
    $db   = new Ossim_db();
    $conn = $db->connect();

    if ($asset_id && $asset_type)
    {
        if (!array_key_exists($asset_type, $asset_types))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$asset_type];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            $error = sprintf(_('Error! %s is not allowed'), ucwords($asset_type));

            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
        }
        
        //Retrieving alarm data.
        list($_alarms, $alarm_count) = $class_name::get_alarms($conn, $asset_id, 0, 1);
        $alarms_risk                 = $class_name::get_highest_risk_alarms($conn, $asset_id);
        
        if ($alarm_count == 0)
        {
            $alarm_level = 0;
        }        
        elseif ($alarms_risk > 5)  //High Alarms
        {
            $alarm_level = 3;
        }
        else //Medium and Low Alarms
        {
            $alarm_level = 2;
        }
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving information'));
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


switch ($alarm_level)
{
    
    case 1:
    case 2:
        $tooltip = _("%s contains alarms with risk between 1 and 5.");
        $alarm_level = 2;
    break;
    
    case 3:
        $tooltip = _("%s contains alarms with risk greater than 5.");
    break;
    
    default:
        $tooltip = _("There are no alarms on this %s.");
}

$tooltip = sprintf($tooltip, ucfirst($asset_type));


$data = array(
    'value'   => intval($alarm_count),
    'level'   => $alarm_level,
    'tooltip' => $tooltip
);

$db->close();
echo json_encode($data);

/* End of file get_status_alarms.php */
/* Location: /av_asset/common/providers/get_status_alarms.php */
