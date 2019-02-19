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
require_once 'incident_common.php';
Session::logcheck("analysis-menu", "IncidentsTags");
if (!Session::menu_perms("analysis-menu", "IncidentsTags") && !Session::am_i_admin())
{  
    Session::unallowed_section(NULL);
}

$db   		= new ossim_db();
$conn 		= $db->connect();

function error($error) {
    $error  = array_filter($error);
    if ($error) {
       echo json_encode(array("status" => "error", "data" => $error));
       die;
    }
}

function success($action) {
    echo '{"status":"OK","data":"'._("Label successfully $action").'"}';
    die;
}

function verify_tag_incident($component_ids,$tag_id) {
    $error = array();
    ossim_valid($tag_id, OSS_DIGIT, 'illegal:' . _("Tag id"));
    $error["tag_id"] = ossim_get_error();
    foreach ($component_ids as $cid) {
        ossim_valid($tag_id, OSS_DIGIT, 'illegal:' . _("Incident id"));
        $error["component_id"] = ossim_get_error();
        break;
    }
    error($error);
}

//get is only for validation here.
$flag = "";
if ($action = GET("action")) {
   $flag = "validate";
} else {
   $action = POST("action");
}

$tag = new Incident_tag($conn);
if ($action == "tags") {
    $list = $tag->get_list();
    $data = array("status" => "OK", "data" => array());
    $incidents = $tag->get_plain_id_list();
    $count = (new Incident())->get_tickets_count($conn);
    foreach ($list as $value) {
        $id = $value["id"];
        $components = isset($incidents[$id]) ? $incidents[$id] : array();
        $compcount = count($components);
        $state = $compcount == 0 ? 0 : ($count == $compcount ? 1 : 2);
        $data["data"][$id] = array(
            "id"         => $id,
            "class"      => $value["class"],
            "name"       => $value["name"],
            "components" => $components,
            "mark_state" => $state
        );
    }
    echo json_encode($data);
    die;
} elseif ($action == "add_components") {
    if (!Token::verify('tk_av_dropdown_tag_token', POST('token'))) {
        echo '{"status":"error","data":["error": "Action not available"]}';
        die;
    }
    $criteria = get_criteria();
    $component_ids = isset($_GET["allaction"]) && GET("allaction") == "on" ? get_ids($conn,$criteria) : POST("component_ids");
    $tag_id = POST("tag_id");
    $incident = new Incident();
    verify_tag_incident($component_ids,$tag_id);
    foreach ($component_ids as $cid) {
        $incident->insert_incident_tag($conn,$cid,$tag_id);
    }
    success(sprintf(_("added to %s assets"),count($component_ids)));
} elseif ($action == "delete_components") {
    if (!Token::verify('tk_av_dropdown_tag_token', POST('token'))) {
        echo '{"status":"error","data":["error": "Action not available"]}';
        die;
    }
    $criteria = get_criteria();
    $component_ids = isset($_GET["allaction"]) && GET("allaction") == "on" ? get_ids($conn,$criteria) : POST("component_ids");
    $tag_id = POST("tag_id");
    verify_tag_incident($component_ids,$tag_id);
    $tag->delete_incident_ids($tag_id,$component_ids);
    success(sprintf(_("deleted from %s assets"),count($component_ids)));
} elseif ($action == "save_tag") {
    if ($flag != "validate" && !Token::verify('tk_tag_form', POST('token'))) {
        echo '{"status":"error","data":["error": "Action not available"]}';
        die;
    }
    $name = POST("tag_name");
    $action = POST("tag_action");
    $class = POST("tag_class");
    $id = POST("tag_id");
    $description = POST("tag_descritpion");
    $error = array();
    ossim_valid($id, OSS_DIGIT,OSS_NULLABLE, 'illegal:' . _("Id"));
    $error["tag_id"] = ossim_get_error();
    ossim_valid($name, OSS_LETTER,OSS_PUNC,OSS_DIGIT, 'illegal:' . _("Name"));
    $error["tag_name"] = ossim_get_error();
    ossim_valid($description, OSS_TEXT,OSS_NULLABLE, 'illegal:' . _("Description"));
    $error["tag_descritpion"] = ossim_get_error();
    ossim_valid($class, OSS_TEXT, 'illegal:' . _("Tag class"));
    $error["tag_class"] = ossim_get_error();
    error($error);
    if ($flag == "validate") {
        echo '{"status":"OK","data":[]}';
        die;
    }
    if ($id) {
        $tag->update($id, $name, $description, $class); 
        $text = "updated";
    } else {
        $tag->insert($name, $description, $class);
        $text = "inserted";
    }
    success($text);
} elseif($action == "delete_tag") {
    if (!Token::verify('tk_tag_form', POST('token'))) {
        echo '{"status":"error","data":["error": "Action not available"]}';
        die;
    }
    $id = POST("tag_id");
    ossim_valid($id, OSS_DIGIT,OSS_NULLABLE, 'illegal:' . _("Id"));
    $error["tag_id"] = ossim_get_error();
    error($error);
    $tag->delete($id);
    success("deleted");
} else {
    if ($ssearch = POST(sSearch)) { 
        ossim_valid($ssearch, OSS_LETTER,OSS_PUNC,OSS_DIGIT, 'illegal:' . _("Search"));
        $error["search"] = ossim_get_error();
        error($error);
        $ssearch = mysql_real_escape_string($ssearch);
        $ssearch = " WHERE name LIKE '%$ssearch%' ";
    }
    $list = $tag->get_list($ssearch);
    $count = count($list);
    $res = array("sEcho" => intval(REQUEST('sEcho')),"iTotalRecords" => $count, "iTotalDisplayRecords"=> $count, "aaData" => array());
    foreach ($list as $item) {
        $res["aaData"][] = array(
	    "DT_RowId" => $item["id"],
            "0" => isset($item["class"]) && $item["class"] ? $item["class"] : "av_tag_1",
            "1" => $item["name"],
            "2" => $item["descr"]
        );
    }
}
echo json_encode($res);
