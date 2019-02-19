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

if (!Session::am_i_admin()) 
{
	Session::unallowed_section(null,'noback');
}

$validate = array (
	"locations_id"      => array("validation" => "OSS_HEX, OSS_NULLABLE"                    , "e_message" => 'illegal:' . _("ID")),
	"ctx"               => array("validation" => "OSS_HEX, OSS_NULLABLE"                    , "e_message" => 'illegal:' . _("CTX")),
	"l_name"            => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_DOT" , "e_message" => 'illegal:' . _("Name")),
	"desc"              => array("validation" => "OSS_TEXT, OSS_NULLABLE"                   , "e_message" => 'illegal:' . _("Description")),
	"search_location"   => array("validation" => "OSS_TEXT"                                 , "e_message" => 'illegal:' . _("Location")),
	"latitude"          => array("validation" => "OSS_DIGIT, '\.\-', OSS_NULLABLE"          , "e_message" => 'illegal:' . _("Latitude")),
	"longitude"         => array("validation" => "OSS_DIGIT, '\.\-', OSS_NULLABLE"          , "e_message" => 'illegal:' . _("Longitude")),
	"country"           => array("validation" => "OSS_LETTER, OSS_NULLABLE"                 , "e_message" => 'illegal:' . _("Country")),
	"sensor_list"       => array("validation" => "OSS_HEX, OSS_NULLABLE"                    , "e_message" => 'illegal:' . _("Sensor List")),
);


if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);

    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    
    echo json_encode($data);
    exit();
}

$validation_errors = validate_form_fields('POST', $validate);

if (POST('ajax_validation_all') == TRUE)
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    else
    {
        $data['status'] = 'OK';
        $data['data']   = '';
    }

    echo json_encode($data);
    exit();
}


//Checking form token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (Token::verify('tk_form_wi', POST('token')) == FALSE)
    {
        Util::response_bad_request(Token::create_error_message());
    }
}

//Perform action
if (is_array($validation_errors) && !empty($validation_errors))
{
    $error_msg = '<div style="padding-left:5px">'._('The following errors occurred').":</div>
        <div style='padding: 5px 5px 5px 15px;'>".implode('<br/>', $validation_errors).'</div>';

    Util::response_bad_request($error_msg);
}


$locations_id = POST('locations_id');
$name         = POST('l_name');
$ctx          = POST('ctx');
$desc         = POST('desc');
$location     = POST('search_location');
$longitude    = POST('longitude');
$latitude     = POST('latitude');
$cou          = POST('country'); 
$sensor_list  = POST('sensor_list');

try
{
    $db   = new ossim_db();
    $conn = $db->connect();
    
    if (empty($locations_id))
    {
        $locations_id = Locations::insert($conn, $ctx, $name, $desc, $location, $latitude, $longitude, $cou);
    }
    else
    {
    	Locations::update($conn, $locations_id, $name, $desc, $location, $latitude, $longitude, $cou);
    }
    
    Locations::save_location_sensors($conn, $locations_id, $sensor_list);
    
    Util::memcacheFlush();
    
    $db->close();
}
catch(Exception $e)
{
    Util::response_bad_request($e->getMessage());
}
