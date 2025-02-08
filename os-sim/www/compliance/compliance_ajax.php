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


//First we check we have session active
Session::useractive();

//Then we check the permissions

//Then we check the permissions
if (!Session::logcheck_bool('configuration-menu', 'ComplianceMapping'))
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');

    echo json_encode($response);
    die();
}



/*
*
* <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------> 
*
*/


function modify_sids($conn, $data)
{
    $ref0       = $data['ref0'];
    $ref1       = $data['ref1'];
    $compliance = $data['compliance'];
    $version    = intval($data['version']);
    $sids       = $data['sids'];
    
    $response   = array();
       
    ossim_valid($ref0,          OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',',     'illegal:' . _("Rule 0 Reference"));
    ossim_valid($ref1,          OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',',     'illegal:' . _("Rule 1 Reference"));
    ossim_valid($compliance,    OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Compliance"));
    ossim_valid($sids,          OSS_DIGIT, OSS_NULLABLE,                'illegal:' . _("Directive SIDs"));
    
    if (ossim_error()) 
    {        
    	$response['error'] = TRUE;
        $response['msg']   = ossim_get_error();
                
        return $response;
    }

    $sids_str = implode(',', $sids);
        
	if($compliance == "PCI")
    {
        Compliance_pci::set_pci_version($version);
        
        $groups = Compliance_pci::get_groups($conn);
        $table  = $groups[$ref0]['subgroups'][$ref1]['table'];
        
        Compliance_pci::update_sids($conn, $table, $ref1, $sids_str);
    }
    elseif($compliance == "ISO27001")
    {
        $groups = Compliance_iso27001::get_groups($conn);
        $table  = $groups[$ref0]['subgroups'][$ref1]['table'];
        
        Compliance_iso27001::update_sids($conn, $table, $ref1, $sids_str);
    }
    else
    {
        $response['error'] = TRUE;
        $response['msg']   = _('Invalid Compliance type. Only PCI and ISO2700 allowed.');
        
        return $response;
    }
    
	$response['error'] = FALSE;
	$response['msg']   = '';
	
	return $response;
		
}




/*
*
* <------------------------   END OF THE FUNCTIONS   ------------------------> 
*
*/






/*
*
* <-------------------------   BODY OF THE SCRIPT   -------------------------> 
*
*/

$action = POST("action");   //Action to perform.
$data   = POST("data");     //Data related to the action.


ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    $response['error'] = TRUE ;
	$response['msg']   = ossim_get_error();
	ossim_clean_error();
	
	echo json_encode($response);
	
	die();
}

//Default values for the response.
$response['error'] = TRUE ;
$response['msg']   = _('Error');

//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
	if ( !Token::verify('tk_compliance', GET('token')) )
	{		
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{    	
        //List of all the possibles functions
        $function_list = array
        (
            'modify_sids'  => array('name' => 'modify_sids', 'params' => array('conn', 'data'))
        );
    
        $_function = $function_list[$action];
        
        //Checking we have a function associated to the action given
        if (is_array($_function) && function_exists($_function['name']))
        {
            $db     = new ossim_db();
            $conn   = $db->connect();
            
            //Now we translate the params list to a real array with the real parameters
            $params = array();
            
            foreach($_function['params'] as $p)
            {
                $params[] = $$p;
            }
            
            try
            {
                //Calling to the function 
                $response = call_user_func_array($_function['name'], $params);
                
                if ($response === FALSE)
                {
                    throw new Exception(_('Sorry, operation was not completed due to an error when processing the request. Try again later'));
                }
            }
            catch(Exception $e)
            {
                $response['error'] = TRUE ;
                $response['msg']   = $e->getMessage();
            }
            
            $db->close();
        }
        else
        {
           $response['error'] = TRUE ;
           $response['msg']   = _('Wrong Option Chosen'); 
        }
	}
}

//Returning the response to the AJAX call.
echo json_encode($response);
