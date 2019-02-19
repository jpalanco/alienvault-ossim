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

Session::logcheck_ajax("dashboard-menu", "IPReputation");
session_write_close();


$maxrows = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$from    = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$sec     = (POST('sEcho'));



ossim_valid($maxrows,       OSS_DIGIT, 				   	  'illegal: iDisplayLength');
ossim_valid($from, 			OSS_DIGIT,         			  'illegal: iDisplayStart');
ossim_valid($sec, 			OSS_DIGIT,				  	  'illegal: sEcho');

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}

$total = 0;
$list  = array();
try
{
    $filters = array(
        'page'      => $from,
        'page_rows' => 10
    );
    
    $otx    = new Otx();
    list($total, $p_list) = $otx->get_pulse_list($filters);
    
    
    if ($total > 0 && is_array($p_list))
    {
        foreach ($p_list as $p)
        {
            $list[] = array($p);
        }
    }
}
catch (Exception $e)
{   
    Util::response_bad_request($e->getMessage());
}

// datatables response json
$response['sEcho']                = intval($sec);
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $list;

echo json_encode($response);