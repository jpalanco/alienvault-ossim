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
require_once (dirname(__FILE__) . '/../../../config.inc');

session_write_close();


if (POST('action') == 'create_pbar')
{
    $id        = POST('id');
    $title     = POST('title');
    $width     = POST('width');
    $progress  = POST('progress');
    $style     = POST('style');
    
    ossim_valid($id, OSS_ALPHA, OSS_PUNC, OSS_SPACE, '-',               'illegal:' . _('ID'));
    ossim_valid($title, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,   'illegal:' . _('Title'));
    ossim_valid($width, OSS_DIGIT,                                      'illegal:' . _('Width'));
    ossim_valid($progress, OSS_DIGIT,                                   'illegal:' . _('Progress'));
    ossim_valid($style, OSS_LETTER, '-',                                'illegal:' . _('Style'));
    
    $width = $width.'px';
    
	if (!ossim_error())
	{ 
        echo Avc_utilities::create_progress_bar($id, $title, $width, $progress, $style);
	}
    else
	{
		$config_nt = array(
			'content' => ossim_error(),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'margin: auto; width: 90%; text-align: center;'
		); 
					
			
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
	}
}
elseif (POST('action') == 'create_vpbar')
{
	$id        = POST('id');
    $title     = POST('title');
    $width     = POST('width');
	$height    = POST('height');
    $progress  = POST('progress');
    $style     = POST('style');
    
    ossim_valid($id, OSS_ALPHA, OSS_PUNC, OSS_SPACE, '-',               'illegal:' . _('ID'));
    ossim_valid($title, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,   'illegal:' . _('Title'));
    ossim_valid($width, OSS_DIGIT,                                      'illegal:' . _('Width'));
	ossim_valid($height, OSS_DIGIT,                                     'illegal:' . _('Height'));
    ossim_valid($progress, OSS_DIGIT,                                   'illegal:' . _('Progress'));
    ossim_valid($style, OSS_LETTER, '-',                                'illegal:' . _('Style'));
    
    $width  = $width."px";
	$height = $height."px";
    
    if (!ossim_error())
    { 
        echo Avc_utilities::create_vprogress_bar($id, $title, $width, $height, $progress, $style);
	}
    else
	{
		$config_nt = array(
			'content' => ossim_error(),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'margin: auto; width: 90%; text-align: center;'
		); 
					
			
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
	}
}
elseif (POST('action') == 'get_system_info')
{
    $system_id = POST('system_id');
	
	ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));
    
    if (ossim_error())
	{ 
		$data['status']  = 'error';
		$data['data']    = ossim_get_error();
		
		echo json_encode($data);
		exit();
	}
    
    $db   = new ossim_db();
    $conn = $db->connect();

    $res = Av_center::get_system_info_by_id($conn, $system_id);
    $db->close();
    
    echo json_encode($res);
}
?>
