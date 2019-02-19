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


Session::logcheck("environment-menu", "ToolsScan");

// get upload dir from ossim config file

$conf = $GLOBALS["CONF"];


$link_type = (GET('linktype') != "") ? GET('linktype') : "host";
$id        = GET('id'); // Can Be Host Group ID
$name      = GET('name');


ossim_valid($name,          OSS_TEXT,           'illegal:' . _("name"));
ossim_valid($id,            OSS_HEX,            'illegal:' . _("id"));
ossim_valid($link_type,     OSS_ALPHA, '_',     'illegal:' . _("link_type"));

if (ossim_error()) 
{
    die(ossim_error());
}

// To link back
$referer = array(
	'net_group' => '/ossim/netgroup/netgroup.php'
);



// DB connect
$db   = new ossim_db();
$conn = $db->connect();

$user = Session::get_session_user();


list($document_list, $documents_num_rows) = Repository::get_list($conn);

// New link on relationships
if (GET('linkdoc') != "" && GET('insert') == "1") 
{
    $aux = explode("####", GET('newlinkname'));
    Repository::insert_relationships($conn, GET('linkdoc') , $link_type, $id);
}

// Delete link on relationships
if (GET('key_delete') != "" && GET('id_delete') != "") 
{
    Repository::delete_relationships($conn, GET('id_delete') , GET('key_delete'));
}


$rel_list = Repository::get_relationships_by_link($conn, $id);


?>
<html>
<head>
  <title> <?php echo _("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  
  <script>
      function close_kdb_win()
      {
          if (typeof parent.GB_close == 'function')
          {
              parent.GB_close();
          }
          
          return false;   
      }
  </script>
</head>

<body style="margin:5px" onload="self.focus()">

    <form name="flinks" method="GET">
    
    <input type="hidden" name="id"       value="<?php echo $id ?>">
    <input type="hidden" name="name"     value="<?php echo $name ?>">
    <input type="hidden" name="linktype" value="<?php echo $link_type ?>">
    <input type="hidden" name="insert"   value="0">
    
    <table align="center" style="width:90%;margin:15px auto 25 auto;">
    	<tr>
    	   <th>
    	       <?php echo _("RELATIONSHIPS for ". str_replace("_", " ", $link_type)) ?>: <?php echo $name ?>
    	   </th>
        </tr>
    	<?php
        if (count($rel_list) > 0) 
        { 
        ?>
        
    	<tr>
    		<td>
    			<table class="noborder" align="center" style="width:80%;margin:10px auto;">
    				<tr>
    					<th><?php echo _("Name") ?></th>
    					<th><?php echo _("Action") ?></th>
    				</tr>
    				<?php
                    foreach($rel_list as $rel) 
                    {
                        $del_url = "asset_repository.php?id=$id&name=$name&key_delete=$id&id_delete=". $rel['id_document'] ."&linktype=$link_type";
                    ?>
    				<tr>
    					<td class="nobborder">
    					   <?php echo $rel['title'] ?>
    				    </td>
    					<td class="nobborder" style="text-align:center">
    					   <a href="<?php echo $del_url ?>">
    					       <img src="../pixmaps/delete.gif" border="0" />
    					   </a>
    				    </td>
    				</tr>
    				<?php
                    } 
                    ?>
    			</table>
    		</td>
    	</tr>
    	<?php
        } 
        ?>
        
    	<tr>
    		<td class="noborder">
    			<table class="noborder" align="center" style="width:80%;margin:10px auto;">
    				<tr>
    					<th colspan="2"><?=_("Document")?></th>
    				</tr>
    				<tr>
    					<td class="noborder">
    						<select style="width:80%" name="linkdoc">
    						<?php
                            foreach($document_list as $document) 
                            {
                            ?>
                                <option value="<?php echo $document->get_id() ?>"><?php echo $document->get_title() ?> </option>
                            <?php
                            } 
                            ?>
    						</select>
    					</td>
    					<td class="noborder">
    					   <input type="button" class="small" value="<?=_("Link")?>" onclick="document.flinks.insert.value='1';document.flinks.submit();">
    				    </td>
    				</tr>
    			</table>
    		</td>
    	</tr>
        
    	<tr>
    	   <td align="center" class="noborder" style="padding:15px 0;">
    	       <input type="button" onclick="close_kdb_win();" value="<?=_("Finish")?>">
    	   </td>
        </tr>
        
    </table>
    </form>
</body>
</html>
<?php

$db->close();

