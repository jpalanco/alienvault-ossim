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

/*
    We load the config file with try catch to avoid problem 
    with database exceptions during updates.
*/
try
{
    //Config File
    require_once dirname(__FILE__) . '/../../../config.inc';
    //Translation File
    require AVC_PATH . '/data/sections/common/code_translation.php';
}
catch(Exception $e)
{
    ;
}

if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/common/real_time.php')
{
    exit();
}

$system_id  = POST('system_id');
$id_section = POST('id_section');

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-',          'illegal:' . _('System ID'));
ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET, 'illegal:' . _('Section'));


$data = array();
if (!ossim_error())
{
    if ($id_section == 'home')
    {
        session_write_close();

        try
        {
            $data['status'] = 'success';

            //System Status
            $data['data']['general_status'] = Av_center::get_system_status($system_id, 'general', TRUE);

            //Network Status
            $data['data']['network_status'] = Av_center::get_system_status($system_id, 'network', TRUE);
        }
        catch(Exception $e)
        {
             $data['status'] = 'error';
             $data['data']['general_status'] = NULL;
             $data['data']['network_status'] = NULL;
        }
    }
    elseif ($id_section == 'sw_pkg_installing')
    {
        $us = Av_center::get_update_status($system_id);
        if ($us['status'] == 'fail')
        {
            $us['status'] = 'error';
            
            $code_id = $us['error_id'];
            
            if (!empty($code_id) && !empty($__m_updates[$code_id]))
            {
                $us['msg']  = preg_replace('/\.\s*$/', ': ', $us['msg']);
                $us['msg'] .= $__m_updates[$code_id];
            }
            
            if (file_exists($us['log']))
            {
                $us['msg'] .= '<br/><br/>' . _(" For further information please check the following log: ") . $us['log'];
            }
        }
        elseif($us['status'] == 'finished')
        {
            //Refresh software information (Cache will be flushed)
            try
            {
                Av_center::get_system_status($system_id, 'software', TRUE);
            }
            catch (\Exception $e)
            {
                ;
            }
        }

        $data['status'] = $us['status'];
        $data['data']   = $us['msg'];
    }
    elseif ($id_section == 'sw_pkg_busy')
    {
        $us =  Av_center::check_update_running($system_id);
	$data = json_decode($us);
    }

    echo json_encode($data);
}
