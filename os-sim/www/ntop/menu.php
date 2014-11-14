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
Session::logcheck("environment-menu", "MonitorsNetwork");

$interface = GET('interface');
$proto     = GET('proto');

ossim_valid($interface, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,  'illegal:' . _('Interface'));
ossim_valid($proto, OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _('Protocol'));

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();

$filters = array(
    'where'    => 'sensor_properties.has_ntop = 1', 
    'order_by' => 'priority DESC'
);


$ntop_list = array();

list($aux_ntop_list, $_total) = Av_sensor::get_list($conn, $filters);


if ($_total > 0)
{
    foreach($aux_ntop_list as $s_id => $s)
    {
        try
        {
            $i_faces = Av_sensor::get_interfaces($s['ip']);

            if(is_array($i_faces) && !empty($i_faces))
            {
                $ntop_list[$s_id] = $s;
                $ntop_list[$s_id]['i_faces'] = $i_faces;
            }
        }
        catch (Exception $e)
        {
            ;
        }
    }
    
    
    //Sensor by default
    if($sensor == '')
    {
        $s_id   = key($ntop_list);
        $sensor = $ntop_list[$s_id]['ip'];
    }    
}

if(!$_total) 
{
    echo ossim_error(_('There are not sensors with NTOP enabled'), AV_WARNING);
    exit();
}


// Get link
$ntop_links = Av_sensor::get_ntop_link($sensor);
$ntop       = $ntop_links['ntop'];

if ($link_ip != '')
{
    $ntop  .= (!preg_match("/\/$/",$ntop) ? '/' : '').$link_ip.".html";
}

// Check access
if (!Av_sensor::is_ntop_wrapper($ntop_links['testntop']))
{
    $ntop = 'errmsg.php';
}
?>

<div id='c_ntop'>
    <div class='c_ntop_left'>
            <!-- change sensor -->
            <form method="GET" action="index.php" style="margin:1px">
                <input type="hidden" name="opc" value="<?=$opc?>">
                <?php echo gettext("Sensor"); ?>:&nbsp;
                <select name="sensor" onChange="submit()">
                    <?php
                    /*
                    * default option (ntop_link at configuration)
                    */
                    /* Get highest priority sensor first */

                    foreach($ntop_list as $s_id => $s)
                    {
                        $option = '<option ';

                        if ($sensor == $s['ip'])
                        {
                            $option.= " selected='selected' ";
                        }
                        
                        $s_data  = $s['ip'].' ['.$s['name'].']';

                        $option.= ' value="'.$s['ip'].'">'.$s_data.'</option>';

                        echo "$option\n";
                    }

                    ?>
                </select>
            </form>
        <!-- end change sensor -->
    </div>

    <div class='c_ntop_middle'>

            <!-- interface selector -->
            <?php
            if ($interface) 
            {
                $fd = @fopen("$ntop/switch.html", "r");
                if ($fd != NULL) 
                {
                    while (!feof($fd)) 
                    {
                        $buffer = fgets($fd, 4096);
                        if (ereg("VALUE=([0-9]+)[^0-9]*$interface.*", $buffer, $regs)) 
                        {
                            $fd2 = @fopen("$ntop/switch.html?interface=$regs[1]", "r");
                            if ($fd2 != NULL) 
                            {
                                fclose($fd2);
                            }
                        }
                    }
                    fclose($fd);
                }
            }
            ?>

            <form method="GET" action="index.php" style="margin:1px">

                <input type="hidden" name="proto" value="<?php echo $proto ?>"/>
                <input type="hidden" name="port" value="<?php echo $port ?>"/>
                <input type="hidden" name="sensor" value="<?php echo $sensor ?>"/>

                <?php echo _("Interface"); ?>:&nbsp;
                <select name="interface" onChange="submit()">

                    <?php
                    if($_total)
                    {
                        foreach($ntop_list as $s_id => $s)
                        {
                            if ($sensor == $s['ip']) 
                            {
                                foreach($s['i_faces'] as $i_face => $i_data)
                                {
                                    if (($interface == '' && ($i_data['role'] == 'admin')) || $interface == $i_face)
                                    {
                                        $selected = " selected='selected'";
                                    }
                                    else
                                    {
                                        $selected = '';
                                    }
                                    
                                    $interface_name = ($i_data['name'] != '') ? $i_data['name'] : $i_face;
                                    $interface_name = Security_report::Truncate($interface_name, 30, '...');
                                    
                                    ?>
                                    <option <?php echo $selected?> value="<?php echo $i_face;?>"><?php echo $interface_name;?></option>
                                    <?php
                                }
                            }
                        }
                    }
                    else 
                    {
                        echo "<option value=''>- "._('No interfaces found')." -";
                    }

                    $db->close();
                    ?>
                </select>
            </form>
            <!-- end interface selector -->
        </div>
        
        <div class='c_ntop_right'>

            <?php
            if ($opc == "")
            { 
                ?>
                <!--<a href="<?php echo "$ntop/trafficStats.html" ?>"  target="ntop"><?php echo gettext("Global"); ?></a><br/> -->
                    <a href="<?php echo "$ntop/NetNetstat.html" ?>"            target="ntop"><?php echo gettext("Sessions"); ?></a> |
                    <a href="<?php echo "$ntop/sortDataProtos.html" ?>"        target="ntop"><?php echo gettext("Protocols"); ?> </a> |
                    <a href="<?php echo "$ntop/localRoutersList.html" ?>"      target="ntop"><?php echo gettext("Gateways, VLANs"); ?> </a> |
                    <a href="<?php echo "$ntop/localHostsFingerprint.html" ?>" target="ntop"><?php echo gettext("OS and Users"); ?> </a> |
                    <a href="<?php echo "$ntop/domainStats.html" ?>"           target="ntop"><?php echo gettext("Domains"); ?> </a> 
                
                <?php
            } 


            if ($opc == "services")
            { 
                ?>
                    <a href="<?php echo "$ntop/sortDataIP.html?showL=0" ?>" target="ntop"><?php echo gettext("By host: Total"); ?></a> |
                    <a href="<?php echo "$ntop/sortDataIP.html?showL=1" ?>" target="ntop"><?php echo gettext("By host: Sent"); ?></a> |
                    <a href="<?php echo "$ntop/sortDataIP.html?showL=2" ?>" target="ntop"><?php echo gettext("By host: Recv"); ?></a> |
                    <a href="<?php echo "$ntop/ipProtoDistrib.html" ?>"     target="ntop"><?php echo gettext("Service statistic"); ?></a> |
                    <a href="<?php echo "$ntop/ipProtoUsage.html" ?>"       target="ntop"><?php echo gettext("By client-server"); ?></a> 
                
                <?php
            } 


            if ($opc == "throughput")
            { 
                ?>
                    <a href="<?php echo "$ntop/sortDataThpt.html?col=1&showL=0" ?>"   target="ntop"><?php echo gettext("By host: Total"); ?></a> |
                    <a href="<?php echo "$ntop/sortDataThpt.html?col=1&showL=1" ?>"   target="ntop"><?php echo gettext("By host: Sent"); ?></a> |
                    <a href="<?php echo "$ntop/sortDataThpt.html?col=1&showL=2" ?>"   target="ntop"><?php echo gettext("By host: Recv"); ?></a> |
                    <a href="<?php echo "$ntop/thptStats.html?col=1" ?>"              target="ntop"><?php echo gettext("Total (Graph)"); ?></a>
                <?php
            } 

            if ($opc == "matrix")
            { 
                ?>
                    <a href="<?php echo "$ntop/ipTrafficMatrix.html" ?>" target="ntop"><?php echo gettext("Data Matrix"); ?></a> |
                    <a href="<?php echo "$ntop/dataHostTraffic.html" ?>" target="ntop"><?php echo gettext("Time Matrix"); ?> </a>
                <?php
            }


            if ($opc == "gateways")
            { 
                ?>
                [   <a href="<?php echo "$ntop/localRoutersList.html" ?>" target="ntop"><?php echo gettext("Gateways"); ?></a>  |
                    <a href="<?php echo "$ntop/vlanList.html" ?>" target="ntop"><?php echo gettext("VLANs"); ?></a> ]
                <?php
            }
            ?>
    </div>
    
    <div class='clear_layer'></div>
</div>

</body>
</html>

