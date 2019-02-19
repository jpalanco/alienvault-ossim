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
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

session_write_close();
set_time_limit(0);
ob_end_clean();

// Error message options
$config_nt = array(
    'content' => '',
    'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => false
    ),
    'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
);

$_back_url = Menu::get_menu_url(AV_MAIN_PATH . '/av_backup/index.php', 'configuration', 'administration', 'backups', 'backups_configuration');
$back_link = '<br/><a href="'.$_back_url.'">'._('Return to configuration backups').'</a>';


//Validate Form token
$token = POST('token');

if (Token::verify('tk_backup_download', $token) == FALSE)
{
    $config_nt['content'] = Token::create_error_message().$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}


$validate = array(
    'system_id'   => array('validation' => 'OSS_UUID',                      'e_message'  =>  'illegal:' . _('System ID')),
    'backup_file' => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_DOT', 'e_message'  =>  'illegal:' . _('Backup File')),
    'job_id'      => array('validation' => 'OSS_UUID',                      'e_message'  =>  'illegal:' . _('Job ID'))
);

$system_id   = POST('system_id');
$backup_file = POST('backup_file');
$job_id      = POST('job_id');

$validation_errors = validate_form_fields('POST', $validate);

if (!empty($validation_errors))
{
    $config_nt['content'] = _('Validation error - unable to download backup file. Please try again.').$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}



$backup_object = new Av_backup($system_id, 'configuration');
$f_length      = $backup_object->get_session_file_size($backup_file);
$file          = $backup_object->download_path.'/'.$backup_file;


if (!file_exists($file))
{
    $config_nt['content'] = _('File not found. Please try again.').$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}


// Don't send the headers still we know the file is right
$headers_sent = FALSE;


$pointer = 0;

while ($pointer < $f_length)
{
    $rs = fopen($file, "r");
    
    fseek($rs, $pointer);
    
    $val = fgets($rs, 1048576);
    
    $pointer = ftell($rs);
    
    fclose($rs);
    
    
    
    if (strlen($val) > 0)
    {
        // Now we can start to download
        if (!$headers_sent)
        {
            header("Content-Type: application/force-download");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=\"$backup_file\";");
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header('Content-Length: '.$f_length);
        
            ob_clean();
            flush();
        
            $headers_sent = TRUE;
        }
        
        echo $val;
        
        ob_clean();
        flush();
    }
    else
    {
        //If no data, check the celery task to see if there is an error.
        $job_status = $backup_object->get_backup_status($job_id);
        
        if ($job_status['job_status'] == 'task-failed')
        {
            fclose($rs);
        
            unlink($file);
        
            $config_nt['content'] = _('The process ended unexpectedly. Please try again.').$back_link;
        
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        
            die();
        }
        
        if ($job_status['job_status']   == 'task-succeeded'
        && $job_status['job_result'][0] == FALSE
        && $job_status['job_result'][1] != '')
        {
            fclose($rs);
            
            unlink($file);
        
            $config_nt['content'] = _('Error getting backup file. Please check the system is reachable.').$back_link;
        
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        
            die();
        }
        
        sleep(5);
    }
}

unlink($file);

/* End of file download_backup.php */
/* Location: /av_backup/controllers/download_backup.php */
