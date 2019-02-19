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

$_back_url = Menu::get_menu_url(AV_MAIN_PATH . '/av_plugin/index.php', 'configuration', 'deployment', 'plugins');
$back_link = '<br/><a href="'.$_back_url.'">'._('Return to Plugins').'</a>';


//Validate Form token
$token = POST('token');

if (Token::verify('tk_plugin_download', $token) == FALSE)
{
    $config_nt['content'] = Token::create_error_message().$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}


$validate = array(
    'plugin'   => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_DOT', 'e_message'  =>  'illegal:' . _('Plugin File'))
);

$plugin = POST('plugin');

$validation_errors = validate_form_fields('POST', $validate);

if (!empty($validation_errors))
{
    $config_nt['content'] = _('Validation error - unable to download plugin file. Please try again.').$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}



$file_output = '';

try
{
    $av_plugin   = new Av_plugin();
    $file_output = $av_plugin->download_plugin($plugin);
}
catch(Exception $e)
{
    $config_nt['content'] = $e->getMessage().$back_link;
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}


$f_length = mb_strlen($file_output, '8bit');

header("Content-Type: application/force-download");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=\"$plugin\";");
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header('Content-Length: '.$f_length);


echo $file_output;

/* End of file download_plugin.php */
/* Location: /av_plugin/controllers/download_plugin.php */
