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


ini_set('memory_limit', '2048M');
set_time_limit(300);

require_once 'av_init.php';

Session::logcheck("dashboard-menu", "IPReputation");


$reputation = new Reputation();
$type       = GET("type");
$type       = ($type == '') ? 1 : intval($type);

if ($reputation->existReputation()) 
{

    //$db     = new ossim_db();
    //$dbconn = $db->connect();
    foreach ($_SESSION as $k => $v) 
    {
        if (preg_match("/^_repinfodb/",$k)) 
        {
            unset($_SESSION[$k]);
        }
    }
    
    list($ips,$cou,$order,$total) = $reputation->get_data($type);
    
    $activities = array_keys($ips);
    
   ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html lang="en">
    <head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo gettext("IP reputation");?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        
        <script type="text/javascript">
        
        function get_map()
        {
            $("#loading").html('<img width="16" align="absmiddle" src="../vulnmeter/images/loading.gif">&nbsp;&nbsp;<?php echo _("Drawing IPs over the world map, please wait a few seconds...")?>');
            
            $("#tr_loading").show();
            
            $("#iframe").html('');
            
            var iframe = "<iframe src='IPsGoogleMap.php?type=<?php echo $type ?>&act=" + escape($('#afilter').val()) + "' width='100%' height='100%' scrolling='no' frameborder='0'></iframe>";
            
            $("#iframe").append(iframe);
        }
               
        function show_map() 
        {
            $("#tr_loading").hide();
            
            <?php 
            if($type == 1 && $total == 0)
            {
            ?>  
                $('<div>', {  
                    id   : "empty_rep_msg",
                    text : "<?php echo _('There are currently no attacks to your system') ?>"
                }).prependTo('#iframe');

            <?php
            }
            ?>
        }
                
        function change_act(activity) 
        {
            var myselect=document.getElementById('afilter');
            
            for (var i=0; i<myselect.options.length; i++){
                myselect.options[i].selected=false;
            }
            
            for (var i=0; i<myselect.options.length; i++) 
            {
                if (myselect.options[i].value==activity){
                    myselect.options[i].selected=true;
                }
            }
            
            get_map();
        }
        
        $(document).ready(function () 
        {
            get_map();
            
            $('#type').on('change', function()
            {
                var opt = $(this).val();
                var url = 'index.php?type=' + opt;
                
                if (typeof top.av_menu.get_menu_url == 'function')
                {
                    url = top.av_menu.get_menu_url(url, 'dashboard', 'otx', '');
                }
                
                document.location.href = url;
                
            });
        });
        
        </script>
    </head>
    <body style="height:100%">

        <div class='otx_header'>
            <div class='otx_header_left'>

                <label for="type"><?php echo _("Source:");?></label>
                <select name="type" id="type">
                    <option value="0"<?php echo ($type==0) ? ' selected' : ''?>><?php echo _("Reputation Data");?></option>
                    <option value="1"<?php echo ($type==1) ? ' selected' : ''?>><?php echo _("SIEM Events");?></option>
                </select> 
                
                &nbsp;&nbsp;

                <label for="afilter"><?php echo _("Filter by Activity:");?></label>
                <select id="afilter" name="afilter" onchange="get_map();">
                    <option value="All"><?php echo _("All");?></option>
                    <?php
                    foreach ($activities as $activity) 
                    {
                        ?>
                        <option value="<?php echo $activity;?>"><?php echo $activity;?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>

        <div id="tr_loading">
            <div id="loading" class='otx_loading'></div>
        </div>
        
        <div id="iframe" class='otx_iframe'></div>

        <!-- CHART -->
        <iframe src="pie.php?type=<?php echo $type ?>" frameborder="0" class='otx_pieframe'></iframe>

            
        <br/><br/>
        
    </body>
    </html>
    <?php
}
?>