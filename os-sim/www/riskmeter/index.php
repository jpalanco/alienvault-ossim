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
Session::logcheck('dashboard-menu', 'ControlPanelMetrics');

/* get refresh page value */
$REFRESH_INTERVAL = 5;

function ordenar($a, $b) 
{
    return (($a['max_c'] + $a['max_a']) < ($b['max_c'] + $b['max_a'])) ? TRUE : FALSE;
}

$refresh  = GET('refresh');
$net_id   = GET('net_id');
$expand   = GET('expand'); // Net Group ID

ossim_valid($refresh, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Refresh interval'));
ossim_valid($net_id,  OSS_HEX, OSS_NULLABLE,   'illegal:' . _('Net ID'));
ossim_valid($expand,  OSS_HEX, OSS_NULLABLE,   'illegal:' . _('Net Group ID'));

if (ossim_error()) 
{
    die(ossim_error());
}

if (empty($refresh)) 
{
    $refresh = 10;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('Riskmeter');?></title>
        <meta http-equiv="refresh" content="<?php echo $refresh ?>">
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <style type='text/css'>
            #t_container
            {
                margin: 10px auto;
                text-align: center;   
            }
        </style>
    </head>
<body>

<?php

$db   = new Ossim_db();
$conn = $db->connect();

/* conf */
$conf = $GLOBALS['CONF'];
$THRESHOLD_DEFAULT = $conf->get_conf('threshold');

$BAR_LENGTH_LEFT  = 300;
$BAR_LENGTH_RIGHT = 200;
$BAR_LENGTH = $BAR_LENGTH_LEFT + $BAR_LENGTH_RIGHT;

/*
* Networks
*/
$net_stats  = Asset_net_qualification::get_list($conn, '', 'net_name');

$max_level       = max(Ossim_db::max_val($conn, 'compromise', 'net_qualification') , Ossim_db::max_val($conn, 'attack', 'net_qualification'));
$net_groups      = Net_group::get_list($conn);
$net_group_array = array();

if (is_array($net_stats)) 
{
    foreach($net_stats as $temp_net) 
    {
        foreach($net_groups as $net_group) 
        {
            $net_group_array[$ng_id]['id']          = $net_group->get_id();
            $net_group_array[$ng_id]['name']        = $net_group->get_name();
            $net_group_array[$ng_id]['threshold_c'] = Net_group::netthresh_c($conn, $net_group->get_id());
            $net_group_array[$ng_id]['threshold_a'] = Net_group::netthresh_a($conn, $net_group->get_id());

            if (Net_group::isNetInGroup($conn, $net_group->get_id(), $temp_net->get_net_id())) 
            {
                if (!isset($net_group_array[$ng_id]['compromise'])) 
                {
                    $net_group_array[$ng_id]['compromise'] = 0;
                    $net_group_array[$ng_id]['attack'] = 0;
                }
                $net_group_array[$ng_id]['compromise'] += $temp_net->get_compromise();
                $net_group_array[$ng_id]['attack']     += $temp_net->get_attack();
            }
        }
    }
}

?>
<table id='t_container'>
    <!-- configure refresh -->
    <tr>
        <td colspan="2" nowrap><?php echo _('Page Refresh'); ?>: 
            <!-- decrease refresh -->
            <a href="?refresh=<?php if ($refresh > $REFRESH_INTERVAL) echo $refresh - $REFRESH_INTERVAL; else echo $refresh; ?>"><b>-</b></a>
            <!-- end decrease refresh -->
            <?php echo $refresh ?>s

            <!-- increase refresh -->
            <a href="?refresh=<?php echo $refresh + $REFRESH_INTERVAL ?>"><b>+</b></a>
            <!-- end increase refresh -->

        </td>
        <td></td>
    </tr>
    <!-- end configure refresh -->

    <tr><td colspan="3"></td></tr>
    <tr><th align="center" colspan="3"><?php echo _('Global'); ?></th></tr>
    <tr><td colspan="3"></td></tr>
    <!-- rule for threshold -->
    <tr>
        <td></td><td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>">
            <img src="../pixmaps/gauge-red.jpg" height="6"  width="<?php echo $BAR_LENGTH_RIGHT; ?>">
        </td>
    </tr>
    <!-- end rule for threshold -->
    
    <tr>
        <td><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>"><?php echo _('Global'); ?> </a></td>
        <td align="center">
            <a href="<?php echo "../control_panel/show_image.php?range=day&id=global_".$_SESSION['_user']."&what=compromise&start=N-1D&type=global&zoom=1"?>" target="main">&nbsp;<img src="../pixmaps/graph.gif" border="0"/>&nbsp;</a>
        </td>
        
        <td class="left">
            <?php
            $compromise = Asset_host_qualification::get_global_compromise($conn);
            $attack     = Asset_host_qualification::get_global_attack($conn);
            /* calculate proportional bar width */
            $width_c = (($compromise / $THRESHOLD_DEFAULT) * $BAR_LENGTH_LEFT);
            $width_a = (($attack / $THRESHOLD_DEFAULT) * $BAR_LENGTH_LEFT);
            
            if ($compromise <= $THRESHOLD_DEFAULT) 
            {
                ?>
                <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $width_c ?>" title="<?php echo $compromise ?>">C=<?php echo $compromise;?>
                <?php
            } 
            else 
            {
                if ($width_c >= ($BAR_LENGTH)) 
                {
                    $width_c = $BAR_LENGTH;
                    $icon    = "../pixmaps/major-red.gif";
                } 
                else 
                {
                    $icon = "../pixmaps/major-yellow.gif";
                }
                ?>
                <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>"/>
                <img src="../pixmaps/solid-blue.jpg" border="0" height="12" width="<?php echo $width_c - $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>"/>C=<?php echo $compromise; ?>
                <img src="<?php echo $icon ?>">
                <?php
            }
    
            if ($attack <= $THRESHOLD_DEFAULT) 
            {
                ?>
                <br/><img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack;?>
                <?php
            } 
            else 
            {
                if ($width_a >= ($BAR_LENGTH)) 
                {
                    $width_a = $BAR_LENGTH;
                    $icon = "../pixmaps/major-red.gif";
                } 
                else 
                {
                    $icon = "../pixmaps/major-yellow.gif";
                }
                ?>
                <br/>
                <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>  
                <img src="../pixmaps/solid-red.jpg" border="0" height="12" width="<?php echo $width_a - $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>">A=<?php echo $attack; ?>
                <img src="<?php echo $icon ?>"/>
                <?php
            }
            ?>
        </td>
    </tr>
    
    <!-- rule for threshold -->
    <tr>
        <td></td><td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>">
            <img src="../pixmaps/gauge-red.jpg" height="5" width="<?php echo $BAR_LENGTH_RIGHT; ?>">
        </td>
    </tr>
    <!-- end rule for threshold -->

    <?php
    // Start group code
    if (is_array($net_group_array)) 
    {
        usort($net_group_array, 'ordenar');
        ?>

        <tr><td colspan="3"><br/></td></tr>
        <tr><th align="center" colspan="3"> <?php echo _('Groups'); ?> </th></tr>
        <tr><td colspan="3"></td></tr>

        <!-- rule for threshold -->
        <tr>
            <td></td><td></td>
            <td class="left">
                <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>"/>
                <img src="../pixmaps/gauge-red.jpg" height="5" width="<?php echo $BAR_LENGTH_RIGHT; ?>"/>
          </td>
        </tr>
        
        <!-- end rule for threshold -->
        <?php
        // Do real stuff
        $temporary = current($net_group_array);

        while ($temporary) 
        {
            $group_name = $temporary['name'];
            $group_id   = $temporary['id'];
            /* calculate proportional bar width */
            
            if(!isset($temporary['attack'])) 
            {   
                $temporary['attack'] = 0;
            }
            
            $width_c = ((($compromise = $temporary['compromise']) / $threshold_c = $temporary['threshold_c']) * $BAR_LENGTH_LEFT);
            $width_a = ((($attack = $temporary['attack']) / $threshold_a = $temporary['threshold_a']) * $BAR_LENGTH_LEFT);
            
            ?>
            <!-- C & A levels for each group -->
            <tr>
                <td align="center">
                    <?php
                    if (!empty($expand) && $expand == $group_id) 
                    { 
                        ?>
                        <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>"><?php echo Util::beautify($group_name); ?></a>
                        <?php
                    } 
                    else
                    { 
                        ?>
                        <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?expand=<?php echo $group_id; ?>"><?php echo Util::beautify($group_name); ?></a>
                        <?php
                    } 
                    ?>
                </td>
                
                <td align="center">
                    <a href="<?php echo "../control_panel/show_image.php?range=day&id=$group_id&what=compromise&start=N-1D&type=net&zoom=1"?>" target="main">&nbsp;<img src="../pixmaps/graph.gif" border="0"/>&nbsp;</a>
                </td>

                <td class="left">
                    <?php
                    if ($compromise <= $threshold_c) 
                    {
                        ?>
                        <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $width_c ?>" title="<?php echo $compromise ?>">C=<?php echo $compromise; ?>
                        <?php
                    } 
                    else 
                    {
                        if ($width_c >= ($BAR_LENGTH)) 
                        {
                            $width_c = $BAR_LENGTH;
                            $icon    = "../pixmaps/major-red.gif";
                        } 
                        else 
                        {
                            $icon = "../pixmaps/major-yellow.gif";
                        }
                        ?>
                        
                        <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>">
                       
                        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" width="<?php echo $width_c - $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>">C=<?php echo $compromise; ?>
                        <img src="<?php echo $icon ?>"/>
                        <?php
                    }
                
                    if ($attack <= $threshold_a) 
                    {
                        ?>
                        <br/>
                        <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack; ?>
                        <?php
                    } 
                    else 
                    {
                        if ($width_a >= ($BAR_LENGTH)) 
                        {
                            $width_a = ($BAR_LENGTH);
                            $icon    = "../pixmaps/major-red.gif";
                        } 
                        else 
                        {
                            $icon = "../pixmaps/major-yellow.gif";
                        }
                        ?>
                        
                        <br/><img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>
                        <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a - $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>">A=<?php echo $attack;?>
                        
                        <img src="<?php echo $icon ?>"/>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <!-- end C & A levels for each net -->
            <?php
            $temporary = next($net_group_array);
        }
    }
    // End group code

    ?>

    <tr><td colspan="3"><br/></td></tr>
    <tr><th align="center" colspan="3"> <?php echo _('Networks');?></th></tr>
    <tr><td colspan="3"></td></tr>


    <!-- rule for threshold -->
    <tr>
        <td></td><td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>">
            <img src="../pixmaps/gauge-red.jpg" height="5"  width="<?php echo $BAR_LENGTH_RIGHT; ?>">
        </td>
    </tr>
    <!-- end rule for threshold -->


    <?php
    if ($net_stats) 
    {
        foreach($net_stats as $stat) 
        {
            $net_id   = $stat->get_net_id();
            $net_name = $stat->get_net_name();
            $_net_aux = Asset_net::get_object($conn, $net_id);
            
            if (!Net_group::isNetInGroup($conn, $expand, $net_id))
            {
                if (($stat->get_compromise() < $_net_aux->get_threshold_c()) && ($stat->get_attack() < $_net_aux->get_threshold_a()) && (Net_group::isNetInAnyGroup($conn, $net_id))) 
                {
                    continue;
                }
            }
            /* get net threshold */
            if (is_object($_net_aux)) 
            {
                $threshold_c = $_net_aux->get_threshold_c();
                $threshold_a = $_net_aux->get_threshold_a();
            } 
            else 
            {
                $threshold_c = $threshold_a = $THRESHOLD_DEFAULT;
            }
            /* calculate proportional bar width */
            $width_c = ((($compromise = $stat->get_compromise()) / $threshold_c) * $BAR_LENGTH_LEFT);
            $width_a = ((($attack = $stat->get_attack()) / $threshold_a) * $BAR_LENGTH_LEFT);
            ?>
    
            <!-- C & A levels for each net -->
            <tr>
                <td align="center">
                <?php
                if (GET('expand')) 
                { 
                    ?>
                    <a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?expand=" . $expand . "&net_id=$net_id" ?>"><?php echo Util::beautify($net_name) ?></a>
                    <?php
                } 
                else 
                { 
                    ?>
                    <a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?net_id=$net_id" ?>"><?php echo Util::beautify($net_name) ?></a>
                    <?php
                } 
                ?>
                </td>
                
                <td align="center">
                    <a href="<?php echo "../control_panel/show_image.php?range=day&id=" . $net_id . "&what=compromise&start=N-1D&type=net&zoom=1"?>" target="main">&nbsp;<img src="../pixmaps/graph.gif" border="0"/>&nbsp;</a>
                </td>
    
                <td class="left">
                    <?php
                    if ($compromise <= $threshold_c) 
                    {
                        ?>
                        <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $width_c ?>" title="<?php echo $compromise ?>"/>C=<?php echo $compromise; ?>
                        <?php
                    } 
                    else 
                    {
                        if ($width_c >= ($BAR_LENGTH)) 
                        {
                            $width_c = $BAR_LENGTH;
                            $icon    = "../pixmaps/major-red.gif";
                        } 
                        else 
                        {
                            $icon = "../pixmaps/major-yellow.gif";
                        }
                        ?>
                        <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>"/>
                                    
                        <!-- <img src="../pixmaps/solid-blue.jpg" height="10" width="5"> -->
                        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" width="<?php echo $width_c - $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>"/>C=<?php echo $compromise; ?>
                              
                        <img src="<?php echo $icon ?>">
                        <?php
                    }
                    
                    if ($attack <= $threshold_a) 
                    {
                        ?>
                        <br/>
                        <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack; ?>
                        <?php
                    } 
                    else 
                    {
                        if ($width_a >= ($BAR_LENGTH)) 
                        {
                            $width_a = ($BAR_LENGTH);
                            $icon    = "../pixmaps/major-red.gif";
                        } 
                        else 
                        {
                            $icon = "../pixmaps/major-yellow.gif";
                        }
                        ?>
                        <br/><img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>
                        <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a - $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack; ?>
                        <img src="<?php echo $icon ?>">
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <!-- end C & A levels for each net -->
            <?php
        }
    }
    ?>

    <!-- rule for threshold -->
    <tr>
        <td></td><td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>"/>
            <img src="../pixmaps/gauge-red.jpg" height="5" width="<?php echo $BAR_LENGTH_RIGHT; ?>"/>
        </td>
    </tr>
    <!-- end rule for threshold -->

    <?php
    /*
    * Hosts
    */
    /*
    * If click on a net, only show hosts of this net
    */
    if (GET('net')) 
    {
        $_net_aux = Asset_net::get_object($conn, $net_id);
        if (is_object($_net_aux)) 
        {
            $ips = $_net_aux->get_ips();
            
            print "<h1>$ips</h1>";
            
            if ($ip_list = Asset_host_qualification::get_list($conn)) 
            {
                foreach($ip_list as $host_qualification) 
                {
                    if (Asset_host::is_ip_in_nets($host_qualification->get_host_ip() , $ips)) 
                    {
                        $ip_stats[] = new Asset_host_qualification($host_qualification->get_host_ip() , $host_qualification->get_compromise() , $host_qualification->get_attack());
                    }
                }
            }
        }
    } 
    else 
    {
        $ip_stats = Asset_host_qualification::get_list($conn, '', 'ORDER BY compromise + attack DESC');
    }
    
    //if (count($ip_stats) > 0) {
    $max_level = max(Ossim_db::max_val($conn, 'compromise', 'host_qualification'), Ossim_db::max_val($conn, 'attack', 'host_qualification'));
?>

    <tr><td colspan="3"><br/></td></tr>
    <tr>
        <th align="center" colspan="3">
            <a name="Hosts" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?#Hosts" title="Fix"><?php echo _('Hosts') ?></a>
        </th>
    </tr>
    <tr><td colspan="3"></td></tr>

    <!-- rule for threshold -->
    <tr>
        <td></td>
        <td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>">
            <img src="../pixmaps/gauge-red.jpg" height="5" width="<?php echo $BAR_LENGTH_RIGHT; ?>">
        </td>
    </tr>
    <!-- end rule for threshold -->

    <?php
    if (isset($ip_stats)) 
    {
        foreach($ip_stats as $stat) 
        {
            $host_id = $stat->get_host_id();
            /* get host threshold */
            
            $object = Asset_host::get_object($conn, $host_id);
            
            if (!empty($object)) 
            {
                $threshold_c = $object->get_threshold_c();
                $threshold_a = $object->get_threshold_a();
                $hostname    = $object->get_name();
            } 
            else 
            {
                $threshold_c = $threshold_a = $THRESHOLD_DEFAULT;
                $hostname = _('undefined');
            }
            /* calculate proportional bar width */
            $width_c = ((($compromise = $stat->get_compromise()) / $threshold_c) * $BAR_LENGTH_LEFT);
            $width_a = ((($attack = $stat->get_attack()) / $threshold_a) * $BAR_LENGTH_LEFT);
            
            $r_url   = Menu::get_menu_url("../report/metrics.php?host_id=$host_id", 'reports', 'usm_reports', 'overview');
            $cp_url  = Menu::get_menu_url("../control_panel/show_image.php?range=day&id=$host_id&what=compromise&start=N-1D&type=host&zoom=1", 'analysis', 'alarms', 'alarms');
            
            ?>

            <!-- C & A levels for each IP -->
            <tr>
                <td align="center">
                    <a href="<?php echo $r_url?>" title="<?php echo $ip ?>"><?php echo $hostname ?></a>
                    <?php echo Asset_host_properties::get_os_by_host($conn, $host_id); ?>
                </td>
                
                <td align="center">
                    <a href="<?php echo $cp_url?>">&nbsp;<img src="../pixmaps/graph.gif" border="0"/>&nbsp;</a>
                </td>

                <td class="left">
                <?php
                if ($compromise <= $threshold_c) 
                {
                    ?>
                    <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $width_c ?>" title="<?php echo $compromise ?>">C=<?php echo $compromise; ?>
                    <?php
                }
                else 
                {
                    if ($width_c >= ($BAR_LENGTH)) 
                    {
                        $width_c = $BAR_LENGTH;
                        $icon    = "../pixmaps/major-red.gif";
                    } 
                    else 
                    {
                        $icon = "../pixmaps/major-yellow.gif";
                    }
                    ?>
                    <img src="../pixmaps/solid-blue.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>"title="<?php echo $compromise ?>"/>
                    
                    <img src="../pixmaps/solid-blue.jpg" border="0" height="12" width="<?php echo $width_c - $BAR_LENGTH_LEFT ?>" title="<?php echo $compromise ?>"/>C=<?php echo $compromise; ?>
                    <img src="<?php echo $icon ?>"/>
                    <?php
                }
                
                if ($attack <= $threshold_a) 
                {
                    ?>
                    <br/><img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack;?>
                    <?php
                } 
                else 
                {
                    if ($width_a >= ($BAR_LENGTH)) 
                    {
                        $width_a = $BAR_LENGTH;
                        $icon    = "../pixmaps/major-red.gif";
                    } 
                    else 
                    {
                        $icon = "../pixmaps/major-yellow.gif";
                    }
                    ?>
                    <br/>
                    <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>
                    <img src="../pixmaps/solid-red.jpg" height="12" width="<?php echo $width_a - $BAR_LENGTH_LEFT ?>" title="<?php echo $attack ?>"/>A=<?php echo $attack; ?>
                    <img src="<?php echo $icon ?>"/>
                    <?php
                } /* foreach */
            } /* if */
        ?>
            </td>
        </tr>
        <!-- end C & A levels for each IP -->
        <?php
    }
    ?>
    <!-- rule for threshold -->
    <tr>
        <td></td><td></td>
        <td class="left">
            <img src="../pixmaps/gauge-blue.jpg" height="5" width="<?php echo $BAR_LENGTH_LEFT; ?>">
            <img src="../pixmaps/gauge-red.jpg" height="5" width="<?php echo $BAR_LENGTH_RIGHT; ?>">
        </td>
    </tr>
    <!-- end rule for threshold -->

    </table>
<br>
</body>
</html>

<?php $db->close(); ?>
