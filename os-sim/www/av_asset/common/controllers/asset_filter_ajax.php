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
if (!Session::logcheck_bool('environment-menu', 'PolicyHosts'))
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');

    echo json_encode($response);
    die();
}



/*
* <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------>
*/


/*
* This function modified a single filter.
*
* @param  $conn     object  DB Connection
* @param  $filters  object  Filter List Object to modify
* @param  $data     array   Filter to be modified and reload option
*
* @return array
*
*/
function modify_filter($conn, $filters, $data)
{
    //Default values to be returned
    $return['error']      = FALSE ;
    $return['msg']        = '';


    //Block between try-catch just in case any exception is launched.
	try
	{
        $f_id       = intval($data['id']); //ID of the filter
        $f_val      = $data['filter']; //New value for the filter
        $f_del      = intval($data['delete']); //New value for the filter

        //Modify the filter. If there is any kind of error, an exception will arise
        $filters->modify_filter($f_id, $f_val, $f_del);

        /*
        Option to force reload the asset group.
        When we are in the lightbox with the extra filter, this option must be false to improve the performance.
        Once we close the greybox, then we should reload the group.
        */
        $op_reload  = intval($data['reload']);

        //If we are forcing to reload, we call to the function that reload the group
        if ($op_reload)
        {
            $return = reload_asset_group_assets($conn, $filters);
        }
	}
	//In case of exception, we catch it and we return the exception error message.
	catch(Exception $e)
	{
        $return['error'] = TRUE ;
        $return['msg']   = $e->getMessage();
    }

    //Saving the object in session once the modifications have been saved.
    $filters->store_filter_list_session();

	return $return;
}


/*
* This function reload the assets from a group.
* The procedure to reload assets is executed with the value of the filters applied.
*
* @param  $conn     object  DB Connection
* @param  $filters  object  Filter List Object to reload
* @param  $data     array   Extra options
*
* @return array
*
*/
function reload_asset_group_assets($conn, $filters, $data = array())
{
    //Default values to be returned
    $return['error']      = FALSE ;
    $return['msg']        = '';


    $force = intval($data['force']);

    //If we reload, this means the extra filters window has been closed, so we delete the copy of the object
    Filter_list::delete_filter_copy();

    //Getting the number of filters to be applied
    $cont = $filters->get_num_filter_added();

    //Trying to achieve the action, in case of error an exception will arise
    try
    {
        //If there are no filters, this means we don't filter by any criteria
        if ($cont == 0)
        {
            //Cleaning the table user_host_filter
            $filters->empty_filter_search($conn);
        }
        else
        {
            //Force to execute the procedure to fill the table user_host_filter
            if ($force)
            {
                $filters->force_reload();
            }

            //Executing the procedure to populate the table the table user_host_filter
            $filters->apply_filter_search($conn);

        }

    }
    //In case of exception, we catch it and we return the exception error message.
    catch(Exception $e)
	{
        $return['error'] = TRUE ;
        $return['msg']   = $e->getMessage();
    }

    return $return;

}


/*
* Function to clear the filters applied in the group into DB
*
* @return array
*
*/
function restart_search($conn, $data)
{
    $return['error'] = FALSE ;
    $return['msg']   = '';
    
    $type = $data['type'];
    
    ossim_valid($type,  'asset','group','network',     'illegal:' . _("List Type"));
    
    if (ossim_error())
    {
        $error = ossim_get_error();
    	ossim_clean_error();

    	$return['error'] = TRUE ;
        $return['msg']   = $error;
        
        return $return;
    }
    
    Filter_list::delete_filters_from_session();
    
    try
    {
        $object = 'Filter_' . $type . '_list';
        
        if (!class_exists($object))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid List Type'));
        }
        
        $filter_list = new $object($conn);

        $filter_list->store_filter_list_session();
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE ;
        $return['msg']   = $e->getMessage();

    }

	return $return;
}


/*
* Function to restore the object if we cancel the extra filter selection
*
* @return array
*
* @extra  Everytime we go into the extra filter screen, we get a copy of the current object.
*         That copy is the one we restore if we cancel the selection.
*
*/
function cancel_filter_list()
{
    Filter_list::restore_filter_copy();

    $return['error'] = FALSE ;
    $return['msg']   = '';


	return $return;
}


/*
* <------------------------   END OF THE FUNCTIONS   ------------------------>
*/





/*
* <-------------------------   BODY OF THE SCRIPT   ------------------------->
*/

$action = POST('action');   //Action to perform.
$data   = POST('data');     //Data related to the action.


ossim_valid($action,	OSS_INPUT,	'illegal:' . _('Action'));

if (ossim_error())
{
    $response['error'] = TRUE ;
	$response['msg']   = ossim_get_error();

	ossim_clean_error();

	echo json_encode($response);
}


$db   = new ossim_db();
$conn = $db->connect();

//Default values for the response.
$response['error'] = TRUE ;
$response['msg']   = _('Error');

//checking if it is an ajax request
if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
	if (!Token::verify('tk_asset_filter_value', GET('token')))
	{
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
        //Getting the object with the filters.
    	$filters = Filter_list::retrieve_filter_list_session();

    	if ($filters === FALSE && $action != 'restart_search' && $action != 'cancel_filter_list')
    	{
        	$response['error'] = TRUE ;
        	$response['msg']   = _('Sorry, operation was not completed due to an error when processing the request. Please Restart the Search');

        	echo json_encode($response);

        	die();
    	}

        switch($action)
        {
            //One filter is going to be modified
        	case 'modify_filter':
        		$response = modify_filter($conn, $filters, $data);
        		break;

            //The group's assets are going to be recalculated.
            case 'reload_group':
        		$response = reload_asset_group_assets($conn, $filters, $data);

        		break;

            //The filters are going to be restarted.
            case 'restart_search':
        		$response = restart_search($conn, $data);

        		break;

            //Cancel extra filter selection --> Restore Object
            case 'cancel_filter':
        		$response = cancel_filter_list();

        		break;

        	default:
        		$response['error'] = TRUE ;
        		$response['msg']   = _('Wrong Option Chosen');
        }

	}
}

//Returning the response to the AJAX call.
echo json_encode($response);

$db->close();
