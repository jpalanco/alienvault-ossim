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

$system_id = POST('system_id');
$action    = POST('action');

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));
ossim_valid($action,    OSS_LETTER, '_',            'illegal:' . _('Action'));

if (ossim_error())
{
    $config_nt = array(
            'content' => ossim_get_error(),
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'margin: auto; width: 80%;'
        );


    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    exit();
}

if ($action == 'check_reconfig_status')
{   
    sleep(1);
    
    $executing_reconfig = -1;
    
    //Check Reconfig status
    $reconfig = Av_center::get_task_status($system_id, 'alienvault-reconfig');

    
    if ($reconfig['status'] == 'running')
    {
        $executing_reconfig = 1;
    }
    else
    {
        $executing_reconfig = 0;
    }

    echo $executing_reconfig;
}
