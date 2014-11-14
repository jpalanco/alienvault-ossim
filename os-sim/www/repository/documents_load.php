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


Session::logcheck("configuration-menu", "Osvdb");


$maxrows    = (POST('iDisplayLength') != "") ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != "") ? POST('sSearch') : "";
$from       = (POST('iDisplayStart') != "") ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != "") ? POST('iSortCol_0') : "";
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');

switch ($order) 
{
	case 0:
		$order = 'id';
		break;
	
	case 1:
		$order = 'title';
		break;
	
	case 2:
		$order = 'date';
		break;
	
	default:
		$order = 'date';
		break;

}

$torder = (!strcasecmp($torder, 'asc')) ? 0 : 1;


ossim_valid($maxrows, 		OSS_DIGIT, 				   	'illegal: Config Param');
ossim_valid($search_str, 	OSS_INPUT, OSS_NULLABLE,   	'illegal: Search String');
ossim_valid($from, 			OSS_DIGIT,         			'illegal: Config Param');
ossim_valid($order, 		OSS_ALPHA,       			'illegal: Config Param');
ossim_valid($torder, 		OSS_DIGIT, 				    'illegal: Config Param');
ossim_valid($sec, 			OSS_DIGIT,				  	'illegal: Config Param');


if (ossim_error()) {

    $response['sEcho']                = 1;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';
	
	echo json_encode($response);
	exit;
}

$db       = new ossim_db();
$conn     = $db->connect();

$user     = Session::get_session_user();

$response = array();
$data     = array();
$perms    = Repository::get_perms($conn);


list($repository_list, $total) = Repository::get_list($conn, $from, $maxrows, $search_str, $order, $torder);


foreach ($repository_list as $repository_object) 
{
	$id_doc    = $repository_object->id_document;
	$date      = $repository_object->date;
	$atch      = $repository_object->atch;
	$rel       = $repository_object->rel;
	$relevance = $repository_object->get_relevance();
	$in_charge = $repository_object->in_charge;
	$creator   = $repository_object->creator;
	
	
	/*****  Title Column  *****/
	$title = "<a href='repository_document.php?id_document=$id_doc&options=1' class='greyboxw' title='".$repository_object->title ."'>". $repository_object->title ."</a>";
	
	
	/*****  Owner Column  *****/
	$username_show = $in_charge;								
	if ($in_charge == '0')
	{
		$username_show = _("All");
	}
	elseif (Session::is_pro() && valid_hex32($in_charge) && $in_charge != '00000000000000000000000000000000') 
	{
		$username_show = Acl::get_entity_name($conn, $in_charge);
	}	
	
	
	
	/*****  Attach Column  *****/
	$attached_docuemts = "
		<table align='center' class='transparent'>
			<tr>
				<td class='transparent'>";
				
	if (count($atch) > 0) 
	{
		$attached_docuemts .= "
			<div id='noti_Container'>
				<a href='repository_attachment.php?id_document=$id_doc' class='greybox' title='". _("Attachements for Document") ."'><img src='images/attach.gif' border=0></a>
				<div class='noti_bubble'><span>". count($atch) ."</span></div>
			</div>";
	} 
	else
	{
		$attached_docuemts .= "<a href='repository_attachment.php?id_document=$id_doc' class='greybox' title='". _("Attachements for Document") ."'><img src='images/attach.gif' border=0 ></a>";
	}
	
	$attached_docuemts .= "
				</td>
			</tr>
		</table>
	";
	
	
	
	/*****  Links Column  *****/
	$linked_docuemts = "
		<table align='center' class='transparent'>
			<tr>
				<td class='transparent'>";
				
	if (count($rel) > 0) 
	{
		$linked_docuemts .= "
			<div id='noti_Container'>
				<a href='repository_links.php?id_document=$id_doc' class='greybox' title='". _("Relationships for Document") ."'><img src='images/linked2.gif' border=0 ></a>
				<div class='noti_bubble'><span>". count($rel) ."</span></div>
			</div>";
	} 
	else
	{
		$linked_docuemts .= "<a href='repository_links.php?id_document=$id_doc' class='greybox' title='".  _("Relationships for Document") ."'><img src='images/linked2.gif' border=0 ></a>";
	}
	
	$linked_docuemts .= "
				</td>
			</tr>
		</table>
	";

	/*****  Action Column  *****/
	if (Repository::can_i_modify($creator, $perms)) 
	{
		$edit_options = "	
			<a href='repository_delete.php?id_document=$id_doc' onclick='deletesubmit($id_doc);return false;'>
				<img src='../pixmaps/delete.gif' border='0' title='"._("Delete Document") ."'/>
			</a>
			
			<a href='repository_editdocument.php?id_document=$id_doc' title='". _("Edit Document") ."'>
				<img src='../pixmaps/pencil.png' border='0' title='". _("Edit Document") ."'/>
			</a>
		
			<a class='greyboxo' href='change_user.php?id_document=$id_doc' title='". _("Change owner") ."'>
				<img src='../pixmaps/group.png' title='". _("Change owner") ."' border='0'/>
			</a>
		";
	}
	else
	{
		$edit_options = "-";
	}

	

	/*****  Document Info  *****/
	$data[]= array(
				$id_doc, 
				$title, 
				$date, 
				$username_show, 
				$attached_docuemts, 
				$linked_docuemts, 
				$edit_options
			);
}		
							

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);							


$db->close(); 
