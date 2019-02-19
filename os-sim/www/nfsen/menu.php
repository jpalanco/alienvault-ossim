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

Session::logcheck("environment-menu", "MonitorsNetflows");

$self                = "/ossim/nfsen/nfsen.php?tab=2&m_opt=$m_opt&sm_opt=$sm_opt&h_opt=$h_opt";
$db_aux              = new ossim_db();
$conn_aux            = $db_aux->connect();

$aux_ri_interfaces   = Remote_interface::get_list($conn_aux, "WHERE status = 1");
$ri_list             = $aux_ri_interfaces[0];
$ossim_conf          = $GLOBALS["CONF"];
$nfsen_in_frame      = ($ossim_conf->get_conf("nfsen_in_frame") == 1) ? "true" : "false";
$db_aux->close();

?>      
        <script type="text/javascript">
            
            function send(ip,name)
            {                
                var newremoteconsole;
                var nfsen_in_frame = <?=($nfsen_in_frame)?>;
                $("#FlowProcessingForm").attr("action", "https://" + ip + $("#FlowProcessingForm").attr("laction"));
                if(nfsen_in_frame || ip == '<?php echo Util::get_default_admin_ip() ?>'){
                    $("#FlowProcessingForm").attr("target", "main");
                }else{
                    $("#FlowProcessingForm").attr("target", ip);
                    var width = 1200;
                    var height = 720;
                    var left = (screen.width/2)-(width/2);
                    var top = (screen.height/2)-(height/2);
                    var strWindowFeatures = "menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes, toolbar=no, personalbar=yes, chrome=yes, centerscreen=yes, top="+top+", left="+left+", height="+height+",width="+width;
                    newremoteconsole = window.open('about:blank',ip, strWindowFeatures);
                }
                
                if (ip != '<?php echo Util::get_default_admin_ip() ?>'){
                    $("#FlowProcessingForm").append("<input type='hidden' id='login' name='login' value='<?=($_SESSION["_remote_login"])?>' />");
                    $("#FlowProcessingForm").append("<input type='hidden' id='name' name='name' value='" + name + "' />");
                }else{
                    $("#FlowProcessingForm").append("<input type='hidden' id='process' name='process' value='Process' />");
                }
                <?php
                if (isset($_POST))
                {
                    foreach($_POST as $key => $value)
                    {
                        if ($key == "srcselector") continue;
                        if(is_array($value))
                        {
                            foreach($value as $valuearray)
                            {
                                ?>
                                $("#FlowProcessingForm").append("<input type='hidden' name='<?php echo Util::htmlentities($key) ?>[]' value='<?php echo Util::htmlentities($valuearray) ?>' />");
                                <?
                            }
                        }else{
                            ?>
                            $("#FlowProcessingForm").append("<input type='hidden' name='<?php echo Util::htmlentities($key) ?>' value='<?php echo Util::htmlentities($value) ?>' />");
                            <?
                        }
                    }
                }
                ?>
                
                if(!(nfsen_in_frame || ip == '<?php echo Util::get_default_admin_ip() ?>')){
                    newremoteconsole.focus();
                }
                $("#FlowProcessingForm").submit();
                
                if (ip != '<?php echo Util::get_default_admin_ip() ?>')
                {
                    $("#login").remove();
                }
                else
                {
                    $("#process").remove();
                }
                
                <?php
                if (isset($_POST))
                {
                    foreach($_POST as $key => $value)
                    {
                        if(is_array($value))
                        {
                            foreach($value as $valuearray)
                            {
                                ?>
                                if ($("#<?php echo Util::htmlentities($key) ?>").length){ $("#<?php echo Util::htmlentities($key) ?>").remove(); }
                                <?
                            }
                        }else{
                            ?>
                            if ($("#<?php echo Util::htmlentities($key) ?>").length){ $("#<?php echo Util::htmlentities($key) ?>").remove(); }
                            <?
                        }
                    }
                }
                ?>
            }

        </script>
        <?php
        // Hide top interface select when no selection is available
        $_table_style = (count($ri_list) > 0) ? '' : 'display:none';
        ?>
        <table id='c_nfsen' style='<?php echo $_table_style ?>'>
            <tr>
				<td class='noborder' style='text-align:left;width: 40%'>
					<?php echo _("Traffic Console")?>:
					<select name="interface" size='1' id="interface">
						<option value="<?php echo Util::get_default_admin_ip() ?>">Local</option>
						<?php
							foreach($ri_list as $r_interface)
							{
								$selected = (isset($_POST['ip']) && $r_interface->get_ip() == $_POST['ip']) ? "selected='selected'" : "" ;
								echo("<option value='".$r_interface->get_ip()."' ".$selected.">".$r_interface->get_name()." [".$r_interface->get_ip()."]"."</option>");
							}
						?>
					</select>
				</td>
			</tr>
        </table>
        <form action="<?php echo $self;?>" id="FlowProcessingForm" target="nfsen" method="POST" laction="<?php echo $self;?>"></form>
    
