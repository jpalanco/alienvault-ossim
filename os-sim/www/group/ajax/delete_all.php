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
require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

$search = utf8_decode(POST('search'));

ossim_valid($search, OSS_NOECHARS, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,    'illegal: search');

if (ossim_error())
{
    $data['status']  = 'error';
    $data['data']    = $GLOBALS['ossim_last_error'];
    
    echo json_encode($data);
    exit();
}

//Validate Form token

$token = POST('token');

if (Token::verify('tk_delete_all', $token) == FALSE)
{		
	$data['status']  = 'error';
	$data['data']    = Token::create_error_message();
	
	echo json_encode($data);
    exit();	
}

session_write_close();


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

try
{
    $filters = array();
    if ($search != '')
    {
        $search           = escape_sql($search, $conn);
        $filters['where'] = " g.name LIKE '%$search%' OR g.owner LIKE '%$search%'";
    }
    
    $host_perm_where = Asset_host::get_perms_where();
    
    $net_perm_where  = Asset_net::get_perms_where();
    
    if (!empty($host_perm_where) || !empty($net_perm_where))
    {      
        $exp_msg = _('You do not have permission to do this action');
        
        Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
    }    

    //Delete all filtered groups
    Asset_group::delete_all_from_db($conn, $filters);
   
    $data['status']  = 'OK';
	$data['data']    = _('Groups deleted successfully');
}
catch(Exception $e)
{
    $error_msg = $e->getMessage();
    
    if (empty($error_msg))
    {
        $error_msg = _('Sorry, operation was not completed due to an unknown error');
    }
    
    $data['status']  = 'error';
	$data['data']    = $error_msg;
}

$db->close();

echo json_encode($data);

/* End of file delete_all.php */
/* Location: ./group/ajax/delete_all.php */