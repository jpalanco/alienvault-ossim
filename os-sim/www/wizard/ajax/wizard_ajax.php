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

if (!Session::am_i_admin())
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    exit -1;
}



/*
*
* <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------> 
*
*/


function check_wizard_object($wizard)
{
    if ($wizard === FALSE)
    {
        throw new Exception(_("There was an error, the Welcome_wizard object doesn't exist. Try again later"));
    }
}


function initialize_wizard()
{    
    //The object is created and saved in $_SESSION
    $wizard = new Welcome_wizard();
            
    $response['error']  = FALSE ;
    $response['data']   = array();
    
    return $response;
}


function change_step($wizard, $data)
{
    check_wizard_object($wizard);
    
    $step   = intval($data['step']);
    
    $finish = $wizard->change_step($step);
    
    $data               = array();
    $data['step']       = $wizard->get_current_step();
    $data['completed']  = $wizard->get_last_completed_step();
    $data['finish']     = $finish;
    
    $response['error']  = FALSE ;
    $response['data']   = $data;

    return $response;
}



function exit_wizard($data)
{
    $status = (intval($data['exit']) == 1) ? 0 : 2;
     
    Welcome_wizard::clean_wizard($status);
    
    $response['error']  = FALSE ;
    $response['data']   = '';
    
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
$response['msg']   = _('Error when processing the request');

//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
	if ( !Token::verify('tk_welcome_wizard', GET('token')) )
	{		
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
        //Getting the object with the filters. Unserialize needed.
    	$wizard = Welcome_wizard::get_instance();
    	
        //List of all the possibles functions
        $function_list = array
        (
            'start_wizard' => array('name' => 'initialize_wizard', 'params' => array()),
            'change_step'  => array('name' => 'change_step',       'params' => array('wizard', 'data')),
            'exit_wizard'  => array('name' => 'exit_wizard',       'params' => array('data'))
        );
    
        $_function = $function_list[$action];
        
        //Checking we have a function associated to the action given
        if (is_array($_function) && function_exists($_function['name']))
        {
            
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
