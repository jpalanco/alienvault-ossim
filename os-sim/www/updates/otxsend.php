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


ob_implicit_flush();
require_once 'av_init.php';

Session::logcheck("configuration-menu", "ConfigurationMain");

// Titles
$titles = array(
    "reputation_destin"     => _("Destination"),
    "reputation_source"     => _("Source"),
    "reputation_destin_all" => _("Destination all"),
    "reputation_source_all" => _("Source all")
);

$db = new ossim_db();
$conn = $db->connect();

$send = GET('send');

ossim_valid($send, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("send"));

if (ossim_error()) 
{
    die(ossim_error());
}

$current_info = array();

// SEND INFO
if ($send > 0) 
{
	$cmd = "python /usr/share/ossim/scripts/send_reputation_feedback.py";
	
	system($cmd);	
	
}
else
{
    $cmd          = "python /usr/share/ossim/scripts/send_reputation_feedback.py --preview 2>/dev/null";
    $current_info = explode("\n", `$cmd`);
}


/*
// Example JSON
$current_info = explode("\n", '{"reputation_destin":{}}
{"reputation_source":{"204.197.248.31": {"1501,200": 2, "1577,51": 1, "1577,53": 1}}}
{"reputation_destin_all":{"78.46.71.209": {"1001,2013031": 120}, "184.72.131.60": {"1001,1201": 1}}}
{"reputation_source_all":{"122.155.2.8": {"1001,17750": 1}, "203.45.99.51": {"1001,17750": 1}, "209.144.165.18": {"1001,17750": 1}, "176.8.215.126": {"1001,2010935": 2, "1001,2010937": 2}, "217.91.28.18": {"1001,17750": 1}, "66.249.66.28": {"1001,17750": 1}, "74.231.247.158": {"1001,17750": 1}, "110.81.153.134": {"1505,30004": 1, "1001,2001219": 3}, "213.133.100.100": {"1001,254": 6}}}');
*/
for ($i = 0; $i < count($current_info); $i++) 
{
	$current_info[$i] = (array) json_decode($current_info[$i]);
}

$flag_button = FALSE;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache"/>
	
	<?php
            //CSS Files
            $_files = array();
    
            $_files[] = array('src' => 'av_common.css',        'def_path' => true);
    
            Util::print_include_files($_files, 'css');
    
            //JS Files
            $_files = array();
    
            $_files[] = array('src' => 'jquery.min.js',         'def_path' => true);
            $_files[] = array('src' => 'jquery-ui.min.js',      'def_path' => true);
            $_files[] = array('src' => 'notification.js',       'def_path' => true);

    
            Util::print_include_files($_files, 'js');
        ?>
	
	<style type='text/css'>
	   
	   #content
	   {
    	   padding:0 20px 15px 20px;
	   }
	   #notif_otx
	   {
    	   text-align: center;
    	   margin: 10px auto;
	   }
	   
	   .av_b_disabled
	   {
    	   padding: 5px 10px;
	   }
	   
	</style>
	
	<script type="text/javascript">
	   
        $(document).ready(function()
        {
           <?php
            if($send > 0)
            {
            ?>
                show_notification('notif_otx', "Information was sent successfully", 'nf_success', 1000);
                
                setTimeout(function()
                {
                    if (typeof parent.GB_hide == 'function')
                    {
                        parent.GB_close();   
                    }
                    
                }, 1700);
                
            <?php
            }
            ?>
            
            $('#send_otx_data').on('click', function()
            {
                $('#send_otx_data').addClass('av_b_processing');
                
                $('#form_otx_data').submit();
            });
           
        });
	   
	</script>
	
</head>

<body id='body_scroll'>

    <div id='notif_otx'></div>
    <?php
    if($send < 1)
    {
    ?>
    <div id="content">
    <form id='form_otx_data'>
        <input type="hidden" name="send" value="1">
        <table align="center" style="height:100%;width:100%;">
        	<tr>
        		<td colspan="4" class="center nobborder" style="color:#C67C1D;font-size:14px;height:50px">
            		<?php echo _("Current Threat Information") ?>
        		</td>
        	</tr>
        	<tr>
            <?php 
            foreach ($current_info as $line)
            {
                foreach ($line as $key=>$object) 
                {      
                ?>
                    <td class="center nobborder" valign="top">
                        <table class="transparent" align="center" width="100%">
            				<tr>
            					<th><?php echo $titles[$key] ?>:</th>
            				</tr>
            				<tr>
                				<td class="center nobborder">
                					<?php
                					$arr = (array)$object;
                					$i = 0;
                					if (count($arr) > 0) 
                					{
                					?>
                				        <table width="100%">
                                            <tr>
                                            	<th><?php echo _("Host") ?></th>
                                            	<th><?php echo _("Event") ?></th>
                                            	<th><?php echo _("Count") ?></th>
                                            </tr>
                                            <?php
                                            foreach ($arr as $ip => $plugins_obj) 
                                            { 
                                                $plugins_arr = (array)$plugins_obj; 
                                            
                                                foreach ($plugins_arr as $idsid => $num) 
                                                {
                                                    list($id, $sid) = explode(",", $idsid);
                                                    $event          = Plugin_sid::get_name_by_idsid($conn, $id, $sid);
                                                ?>
                            						<tr style="background-color:<?php echo ($i++%2 == 0) ? "#F2F2F2" : "#FFFFFF" ?>">
                            							<td><b><?php echo $ip ?></b></td>
                            							<td style="text-align:left"><?php echo $event ?></td>
                            							<td><?php echo $num ?></td>
                            						</tr>
                                				<?php 
                                				} 
                                            } 
                                            ?>
                                        </table>
                                    <?php 
                                        $flag_button = TRUE;
                                    } 
                                    else 
                                    {
                                        echo _("No data found");
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                <?php 
                }
            } 
            ?>
            </tr>
            <tr>
        		<td colspan="4" class="center nobborder" style='padding:30px 0 10px 0;'>
            		<input type='button' class='<?php echo ($flag_button) ? '' :  'av_b_disabled' ?>' <?php if (!$flag_button) echo 'disabled' ?> id='send_otx_data' value='<?php echo _('Send Now') ?>'/>
        		</td>
        	</tr>
        </table>
    </form>
    </div>
    <?php
    }
    ?>   
</body>
</html>
