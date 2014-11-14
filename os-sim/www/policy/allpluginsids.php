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

Session::logcheck("configuration-menu", "PluginGroups");

$id   = (GET('id') != "") ? GET('id') : POST('id');
$sids = GET('sids');
$pgid = GET('pgid');

ossim_valid($id,    OSS_DIGIT,                                          'illegal: ID');
ossim_valid($pgid,  OSS_HEX,                                            'illegal: Plugin Group ID');
ossim_valid($sids,  OSS_NULLABLE, OSS_DIGIT, ",-", "ANY", OSS_SPACE,    'illegal: sids');

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();


$back_button = "modifyplugingroupsform.php?action=edit&id=$pgid";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Plugin SIDs"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.highlight-3.js"></script>
	
	<script type="text/javascript">
        function highlight_text()
        {
            if ($('#stxt').val()!='') 
            {
                $('#content').removeHighlight().highlight($('#stxt').val());
            }
        }
        
        $(document).ready(function(){
            $('#b_highlight').click(function(){
                highlight_text()
            });
        });
            	    	
	</script>	
	
	<style type="text/css">
		.highlight 
		{ 
            background-color: yellow 
        }

        #back_icon 
        {
            position:relative;
            width: 97%;
            margin: 10px auto;
            height: 30px;     
        }
        
        #c_back_button
        {
            left: 0px !important;
        }
        
        #c_all_psid
        {
            margin: 10px auto 20px auto;
            width: 97%;
        }
        
        #content 
        {
            width: 100%;
            border-collapse: collapse;
            margin: auto;
        }
        
        #content td, #content th 
        {
            padding: 5px;
        }
        
        #content .th_event_type
        {
            width: 180px;
            white-space: nowrap;                
        }
        
        #c_div_h
        {
            width:97%;
            position: relative;
            right: 0px;
            text-align: right;
            margin: auto;    
        }
        
        #div_h
        {
           position: absolute;
           right: 5px;
           top: 3px;
           width: 400px;               
        }

	</style>
</head>
<body>
    <div id='back_icon'>        
        <div class="c_back_button" style='display: block;'>         
            <input type='button' class="av_b_back" onclick="document.location.href='<?php echo $back_button ?>';return false;"/>
        </div>        
    </div>

    <div id='c_all_psid'>
    
        <form method="GET" onsubmit="return false" style="dislay:none">
            <input type="hidden" name="id" value="<?=$id?>">
            
            <div id='c_div_h'>
                <div id='div_h'>
                    <input type="text" name="stxt" size="20" id="stxt"/>&nbsp;
                    <input type="button" id='b_highlight' class="small" value="<?php echo _("Highlight")?>"/>
                </div>
            </div>
            
            <table align="center" width="100%" id="content">
                <tr>
                    <th class='th_event_type headerpr'><?php echo  _("Event Type") ?></th>
                    <th class='headerpr'><?php echo  _("Event Type Name") ?></th>
                </tr>
                <?php
                if ($sids=="ANY" || $sids=="0") 
                {
                	$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id");    
                } 
                else 
                {
            		$sids = explode(",",$sids);
            		$range = "";
            		$sin = array();
            		foreach ($sids as $sid) 
            		{
            			if (preg_match("/(\d+)-(\d+)/",$sid,$found)) {
            				$range .= " OR (sid BETWEEN ".$found[1]." AND ".$found[2].")"; 
            			} 
            			else 
            			{ 
            				$sin[] = $sid;
            			}
            		}
            		if (count($sin)>0) $where = "sid in (".implode(",",$sin).") $range";
            		else $where = preg_replace("/^ OR /","",$range);
            	    $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id AND ($where)");
            	}
            	
                foreach($plugin_list as $plugin) 
                {
                	$i++;
                    ?>
            	    <tr class='<?php echo ($i%2) ? 'odd' : 'even' ?>'>
            	        <td class="noborder pleft pbottom" style="padding:3px 0px"><b><?php echo $plugin->get_sid() ?></b>&nbsp;</td>
            	        <td class="noborder pleft pbottom pright">
            	            <?php echo $plugin->get_name()?>
            	        </td>
            	    </tr>
            	    <?php
                }
                ?>
            </table>
        </form>
    </div>
</body>
</html>

<?php
$db->close();
