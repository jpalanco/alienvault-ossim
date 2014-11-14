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


$id   = (GET('id') != "")? GET('id') : POST('id');
$sids = POST('field');
$pgid = POST('pgid');

ossim_valid($id,    OSS_DIGIT,  'illegal:' . _("ID"));
ossim_valid($pgid,  OSS_HEX,    'illegal: Plugin Group ID');
ossim_valid($sids,  OSS_NULLABLE, OSS_DIGIT, ",-", "ANY", OSS_SPACE);

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
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
    <link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" rel="stylesheet" />
	<style>
		/*Multiselect loading styles*/        
		#ms_body 
        {
			height: 295px;
			margin:2px 20px 0px 20px;
		}
		
		#load_ms 
        {
			margin:auto; 
			padding-top: 105px; 
			text-align:center;
		}	
        
        #pluginsids
        {
            width:97%;
        }
        .c_back_button
        {
            left:-2px;
            top:0px;
        }

        #back_icon
        {
            position:relative;
            margin:5px 20px 0px 20px;
        }

        #av_msg_info {
            top: 0px !important;
        }

	</style>
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect_search.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>    
    <script type="text/javascript" src="../js/split.js"></script>    

    <script type='text/javascript' src='/ossim/js/notification.js'></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>

    <script>

        var customDataParser = function(data) 
        {
            if ( typeof data == 'string' ) {
                var pattern = /^(\s\n\r\t)*\+?$/;
                var selected, line, lines = data.split(/\n/);
                data = {};
                $('#msg').html('');
                for (var i in lines) {
                    line = lines[i].split("=");
                    if (!pattern.test(line[0])) {
                        if (i==0 && line[0]=='Total') {
                            $('#msg').html("<?=_("Total plugin sids found:")?> <b>"+line[1]+"</b>");
                        } else {
                            // make sure the key is not empty
                            selected = (line[0].lastIndexOf('+') == line.length - 1);
                            if (selected) line[0] = line.substr(0,line.length-1);
                            // if no value is specified, default to the key value
                            data[line[0]] = {
                                selected: false,
                                value: line[1] || line[0]
                            };
                        }
                    }
                }
            } else {
                this._messages($.ui.multiselect.constante.MESSAGE_ERROR, $.ui.multiselect.locale.errorDataFormat);
                data = false;
            }
            return data;
        }

        $(document).ready(function()
        {
            $(".multiselect").multiselect({
                searchDelay: 700,
                dividerLocation: 0.5,
				sortable: 'both',
                remoteUrl: 'get_plugin_sids.php',
                remoteParams: { plugin_id: '<?=$id?>' },
                nodeComparator: function (node1,node2){ return 1 },
                dataParser: customDataParser,
                sizelength: 73
            });
            
        });

        function makesel() 
        { 
            var id   = "<?php echo $id?>";
            var sids = getselectedcombovalue('pluginsids');

            if (sids == '')
            {
                sids = []
                sids.push('ANY')
            }

            $.ajax(
            {
                data:  {"action": 1, "data":  {"plugin_group": "<?php echo $pgid ?>", "plugin_id": id, "plugin_sids": sids}},
                type: "POST",
                url: "plugin_groups_ajax.php", 
                dataType: "json",
                success: function(data)
                { 
                    if(!data.error)
                    {
                        document.location.href='<?php echo $back_button ?>';
                    } 
                    else
                    {
                        notify(data.msg, 'nf_error');
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown)
                { 
                    
                    notify(errorThrown, 'nf_error');
                }
            });
            

        }

    </script>
</head>
<body>

    <div id='back_icon' style='height:30px;'>           
        <div class="c_back_button" style='display: block;'>         
            <input type='button' class="av_b_back" onclick="document.location.href='<?php echo $back_button ?>';return false;"/> 
        </div> 
    </div>

    <form id="formpluginsids" action="pluginsids.php" method="POST" style="dislay:none">
        <input type="hidden" name="id" value="<?=$id?>">
        <input type="hidden" name="sids" id="sids" value="">
    </form>

	<div id='ms_body'>
        <!--<div id='load_ms'><img src='../pixmaps/loading.gif'/></div>-->
		<form>
			<select id="pluginsids" style="height:255px;" class="multiselect" multiple="multiple" name="sids[]">
			<?
			if ($sids!="ANY" && $sids!="") {
				$sids = explode(",",$sids);
				$range = "";
				$sin = array();
				foreach ($sids as $sid) {
					if (preg_match("/(\d+)-(\d+)/",$sid,$found)) {
						$range .= " OR (sid BETWEEN ".$found[1]." AND ".$found[2].")"; 
					} else { 
						$sin[] = $sid;
					}
				}
				if (count($sin)>0) $where = "sid in (".implode(",",$sin).") $range";
				else $where = preg_replace("/^ OR /","",$range);
				$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id AND ($where)");
				foreach($plugin_list as $plugin) {
					$_id = $plugin->get_sid();
					$_name = "$_id - ".trim($plugin->get_name());
					//if (strlen($name)>73) $name=substr($name,0,70)."...";
					echo "<option value='$_id' selected>$_name</option>\n";
				}
			}
			?>
			</select>
			
			<div style='padding-top:5px;'>				
				<div id="msg"></div>
				<div style='padding-top:2px;'><?php echo _('Empty selection means <b>ANY</b>') ?></div>
			</div>
			
			<div style='padding-top:15px;text-align:center;'>
				<input type="button" onclick="makesel()" value="Submit selection"/>
			</div>
		</form>
	</div>


</body>
</html>
<?
$db->close();
