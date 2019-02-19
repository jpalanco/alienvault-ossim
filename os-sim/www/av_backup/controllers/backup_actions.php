<?php
/**
* backup_actions.php
* 
* File backup_actions.php is used to:
* - Response ajax call from backup dataTable actions
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

session_write_close();

//Validate action type

$action = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $error = ossim_get_error_clean();

    Util::response_bad_request($error);
}


switch($action)
{
    case 'delete_backup':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_backup_action', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }


        $validate = array(
            'system_id'      => array('validation' => 'OSS_UUID',                      'e_message'  =>  'illegal:' . _('System ID')),
            'backup_files[]' => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_DOT', 'e_message'  =>  'illegal:' . _('Backup File'))
        );

        $system_id    = POST('system_id');
        $backup_files = POST('backup_files');
        
        
        $validation_errors = validate_form_fields('POST', $validate);
        
        
        if (!empty($validation_errors))
        {
            Util::response_bad_request(_('Validation error - backups could not be removed.'));
        }
        else
        {
            try
            {
                $backup_object = new Av_backup($system_id, 'configuration');
                $backup_object->delete_backup_by_files($backup_files);

                $data['status']      = 'success';
                $data['data']['msg'] = _('Your changes have been saved.');

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('An API error occurred - backup could not be deleted. Please try again.'));
            }
        }

    break;

    case 'launch_backup':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_backup_action', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }


        $validate = array(
            'system_id' => array('validation' => 'OSS_UUID',  'e_message'  =>  'illegal:' . _('System ID'))
        );

        $system_id = POST('system_id');

        $validation_errors = validate_form_fields('POST', $validate);

        if (!empty($validation_errors))
        {
            Util::response_bad_request(_('Validation error - backup has not launched. Please try again.'));
        }
        else
        {
            try
            {
                $backup_object = new Av_backup($system_id, 'configuration');
                $job_id        = $backup_object->run_backup();

                $data['status']         = 'success';
                $data['data']['msg']    = _('Backup successfully launched');
                $data['data']['job_id'] = $job_id;

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('An API error occurred - backup could not be launched. Please try again.'));
            }
        }

    break;
    
    
    case 'download_backup':
    
        //Validate Form token
    
        $token = POST('token');
    
        if (Token::verify('tk_backup_action', $token) == FALSE)
        {
            $error = Token::create_error_message();
    
            Util::response_bad_request($error);
        }
    
    
        $validate = array(
            'system_id'   => array('validation' => 'OSS_UUID',                      'e_message' =>  'illegal:' . _('System ID')),
            'backup_file' => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_DOT', 'e_message' =>  'illegal:' . _('Backup File'))
        );
    
        $system_id   = POST('system_id');
        $backup_file = POST('backup_file');
    
        $validation_errors = validate_form_fields('POST', $validate);
    
        if (!empty($validation_errors))
        {
            Util::response_bad_request(_('Validation error - unable to download backup file. Please try again.'));
        }
        else
        {
            try
            {
                $backup_object     = new Av_backup($system_id, 'configuration');
                $download_response = $backup_object->download_backup($backup_file);
    
                $data['status']         = 'success';
                $data['data']['msg']    = _('Backup file is ready for download.');
                $data['data']['job_id'] = $download_response['job_id'];
            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('An API error occurred - backup could not be downloaded. Please try again.'));
            }
        }
    
        break;

}


echo json_encode($data);

/* End of file backup_actions.php */
/* Location: /av_backup/controllers/backup_actions.php */
