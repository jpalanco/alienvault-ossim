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


function delete_nfsen_source($data)
{
    if(!Session::am_i_admin())
    {
        $return['error'] = TRUE ;
        $return['msg']  = _('Action not authorized');

        return $return;
    }

    require_once '../sensor/nfsen_functions.php';

    $sensor = $data['sensor'];

    ossim_valid($sensor,    OSS_ALPHA,  'illegal:' . _('Nfsen Source'));

    if (ossim_error())
    {
        $info_error = _('Error').': '.ossim_get_error();
        ossim_clean_error();
        
        $return['error'] = TRUE;
        $return['msg']   = $info_error;
        return $return;
    }
    
    $res = delete_nfsen($sensor);
    
    if($res['status'] == 'success')
    {
        $return['error'] = FALSE;
        $return['msg']  = _('Source deleted successfully');

        //To forcer load variables in session again
        unset($_SESSION['tab']);
    }
    else
    {
        $return['error'] = TRUE;
        $return['msg']   = $res['data'];
    }       
    
    return $return;    
}



$action = POST('action');
$data   = POST('data');

ossim_valid($action,    OSS_DIGIT,  'illegal:' . _('Action'));

if (ossim_error()) 
{
    $response['error'] = TRUE;
    $response['msg']   = ossim_error();

    echo json_encode($response);

    exit();
}

$db     = new ossim_db(TRUE);
$conn   = $db->connect();

if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    /*
    if ( !Token::verify('tk_deploy_ajax', GET('token')) )
    {
        $response['error'] = TRUE;
        $response['msg']   = 'Invalid Action';
        
        echo json_encode($response);
        
        $db->close();
        exit();
    }
    */
    
    switch($action)
    {    
        case 1:         
            $response = delete_nfsen_source($data);           
        break;
            
        default:
            $response['error'] = TRUE;
            $response['msg']   = 'Wrong Option Chosen';
    }
    
    echo json_encode($response);

}

$db->close();
?>