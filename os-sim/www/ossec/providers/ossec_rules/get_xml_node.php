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

require_once dirname(__FILE__).'/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


//Get XML node
$lk_value = POST('lk_value');

$lk_name  = $_SESSION['lk_name'];

$tree_lr = $_SESSION['_tree'];
$child 	 = Ossec::get_child($tree_lr, $lk_name, $lk_value);

$rule    = array (
    '@attributes'=> array($lk_name => '1'),
    '0'          => array('rule' => $child['tree'])
);

if (!empty($child))
{
	$xml_obj        = new Xml_parser($lk_name);
	$output         = $xml_obj->array2xml($rule);

	$data['status'] = 'success';
	$data['data']   = Ossec_utilities::formatOutput($output, $lk_name);

}
else
{
	$data['status'] = 'error';
	$data['data']   = _('Error! Information not available');
}

echo json_encode($data);
