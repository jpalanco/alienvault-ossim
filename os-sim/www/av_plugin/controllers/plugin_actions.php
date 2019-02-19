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


//Config File
require_once 'av_init.php';

if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

define("MAX_FILESIZE", 1000000);

//Validate action type

$action = POST('action');

ossim_valid($action, OSS_LETTER, '_', 'illegal:' . _('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_plugin_actions', $token) == FALSE)
{
    $error = Token::create_error_message();

    Util::response_bad_request($error);

}

switch ($action)
{
    // Wizard Step 1 (Upload Plugin)
    case 'upload_plugin_file':
        // Set file header data for the next step
        Av_plugin::set_wizard_data('file', POST('file'));
        Av_plugin::set_wizard_data('fbase', base64_encode(json_encode(array("filename" => POST('fbase')))));
        break;
        
        
    
    // Delete Trashcan from plugin list
    case 'delete_plugin':
        
        $validate = array(
            'plugin_list[]' => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_DOT', 'e_message'  =>  'illegal:' . _('Plugin List'))
        );
        
        
        $plugin_list = POST('plugin_list');
        
        
        $validation_errors = validate_form_fields('POST', $validate);
        
        
        if (!empty($validation_errors))
        {
            Util::response_bad_request(_('Validation error - plugins could not be removed.'));
        }
        else
        {
            try
            {
                $av_plugin = new Av_plugin();
                
                foreach ($plugin_list as $plugin)
                {
                    $av_plugin->delete_plugin($plugin);
                }
        
                $data['status']      = 'success';
                $data['data']['msg'] = _('Your changes have been saved.');
        
            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('An API error occurred - plugin could not be deleted. Please try again.'));
            }
        }
        
        
        break;
    case 'rollback' :
	$filename = base64_decode(json_decode(base64_decode(Av_plugin::get_wizard_data('fbase')))->filename);
        $args       = " AND filename = '$filename' ";
        $db    = new ossim_db();
        $conn  = $db->connect();

        $pt = Asec::get_suggestion_patterns($conn, $args);
	foreach ($pt['data'] as $p) {
		foreach ($p['patterns'] as $pid => $pattern) {
			Asec::set_pattern_status($conn, $pid, 2, '');
		}
	}
	Av_plugin::clear();
        $data['status']      = 'success';
        $data['data']['msg'] = _('Your changes have been rolled back.');
	echo json_encode($data);
        break;
}

