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
require_once 'sensor_filter.php';

$m_perms  = array('dashboard-menu', 'environment-menu', 'environment-menu');
$sm_perms = array('ControlPanelExecutive', 'EventsHids', 'EventsHidsConfig');


if (Session::menu_perms($m_perms, $sm_perms) == FALSE)
{
	if (Session::menu_perms($m_perms[0], $sm_perms[0]) == FALSE)	
	{
		Session::unallowed_section(NULL, 'noback',$m_perms[0], $sm_perms[0]);
	}
	else
	{
		Session::unallowed_section(NULL, 'noback',$m_perms[1], $sm_perms[1]);
	}
}


$version = $conf->get_conf('ossim_server_version');
$prodemo = (preg_match("/pro|demo/i",$version)) ? TRUE : FALSE;

session_write_close();

function SIEM_trends($h = 24) 
{
	global $tz;
	$tzc = Util::get_tzc($tz);
	$data = array();
	
	$db = new ossim_db(TRUE);
	$dbconn = $db->snort_connect();	
	
	$_asset_where = make_asset_filter();
	$asset_where  = $_asset_where[1];
		
    $sensor_where = make_ctx_filter().$asset_where;
    
	$sqlgraph = "SELECT SUM(acid_event.cnt) AS num_events, hour(convert_tz(timestamp,'+00:00','$tzc')) AS intervalo, 
	   day(convert_tz(timestamp,'+00:00','$tzc')) as suf 
	   FROM ac_acid_event acid_event
	   WHERE timestamp BETWEEN '".gmdate("Y-m-d H:00:00",gmdate("U")-(3600*$h))."' AND '".gmdate("Y-m-d H:59:59")."' $sensor_where 
	   GROUP BY suf, intervalo";
	
	$rg = $dbconn->CacheExecute($sqlgraph);
	
	if (!$rg)
	{
	    Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());
	} 
	else 
	{
	    while (!$rg->EOF) 
	    {
	        $data[$rg->fields['suf'].' '.$rg->fields['intervalo'].'h'] = $rg->fields['num_events'];
	        $rg->MoveNext();
	    }
	}
	$db->close();
	
	return $data;
}


function SIEM_trends_week($param = '') 
{
	global $tz;
	$tzc = Util::get_tzc($tz);
	
	$data = array();
	
	$plugins     = '';
	$plugins_sql = '';
	
	
	$db = new ossim_db(TRUE);
	$dbconn = $db->connect();			
	
	$_asset_where = make_asset_filter();
	$asset_where  = $_asset_where[1];	
		
    $sensor_where = make_ctx_filter().$asset_where;
	
	$tax_join = '';
	
	if (preg_match("/taxonomy\=(.+)/",$param,$found)) 
	{
		if ($found[1] == 'honeypot') 
		{
			$tax_join = 'alienvault.plugin_sid p, ';
			$tax_where = 'AND acid_event.plugin_id = p.plugin_id AND acid_event.plugin_sid = p.sid AND p.category_id = 19';
		}
		
		$param = '';
	} 
	elseif ($param == 'ossec%') 
	{
		$plugins_sql = 'AND acid_event.plugin_id between ' . OSSEC_MIN_PLUGIN_ID . ' AND ' . OSSEC_MAX_PLUGIN_ID;
        $plugins     = OSSEC_MIN_PLUGIN_ID . '-' . OSSEC_MAX_PLUGIN_ID;
	}
	
	$sqlgraph = "SELECT SUM(acid_event.cnt) as num_events, day(convert_tz(timestamp,'+00:00','$tzc')) AS intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) AS suf 
        FROM $tax_join alienvault_siem.ac_acid_event acid_event
        WHERE timestamp BETWEEN '".gmdate("Y-m-d 00:00:00",gmdate("U")-604800)."' AND '".gmdate("Y-m-d 23:59:59")."' $plugins_sql $sensor_where $tax_where 
        GROUP BY suf, intervalo 
        ORDER BY suf, intervalo";
	
	$rg = $dbconn->CacheExecute($sqlgraph);
	
	if (!$rg)
	{
	    Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());
	} 
	else 
	{
	    while (!$rg->EOF) 
	    {
	        $hours = $rg->fields['intervalo'].' '.substr($rg->fields['suf'], 0, 3);
	        $data[$hours] = $rg->fields['num_events'];
	        
	        $rg->MoveNext();
	    }
	}
	
	$db->close();
	
	return ($param != '') ? array($data,$plugins) : $data;
}


function Logger_trends() 
{
	/*
	 * DEPRECATED
	 */
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title><?php echo _('Event Trends')?></title>
	<script language="javascript" src="../js/raphael/raphael.js"></script>
    <script language="javascript" src="../js/jquery.min.js"></script>
    <style type="text/css"> 
        body 
        { 
            overflow:hidden;        
        } 
    </style>
</head>
<?php
$max = 16;

$hours  = array(); 
$trend  = array();
$trend2 = array();
//
if (GET('type') == 'siemday') 
{ 
    $js = 'analytics';
    
    $data = SIEM_trends($max);
       for ($i=$max-1; $i>=0; $i--) 
    {
    	$h = gmdate('j G', $timetz-(3600*$i)).'h';
    	
    	$hours[] = preg_replace("'/\d+ /", '', $h);
    	$trend[] = ($data[$h]!="") ? $data[$h] : 0;
    }    
    
    $f_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d";
    
} 
elseif (GET('type') == 'siemweek') 
{ 
    $js   = 'analytics';
    $data = SIEM_trends_week();
    $max  = 7;
    
    for ($i = $max-1; $i >= 0; $i--) 
    {
    	$d = gmdate('j M', $timetz-(86400*$i));
    	
    	$hours[] = $d;
    	$trend[] = ($data[$d] != '') ? $data[$d] : 0;
    }
   
    $f_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=ZZ&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=ZZ&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d";
    
}
elseif (GET('type') == 'hids') 
{ 

    $js = 'analytics';
    
    list($data, $plugins) = SIEM_trends_week("ossec%");
    
    $max = 7;
    
    for ($i= $max-1; $i >= 0; $i--)
    {
    	$d = gmdate('j M', $timetz-(86400*$i));
    	$hours[] = $d;
    	$trend[] = ($data[$d]!="") ? $data[$d] : 0;
    }    

    $f_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=ZZ&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=ZZ&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&plugin=".$plugins;

} 
elseif (GET('type') == 'honeypotweek') 
{ 
    $js   = 'analytics';
    $data = SIEM_trends_week('taxonomy=honeypot');
    $max  = 7;
    for ($i = $max-1; $i>= 0; $i--) 
    {
    	$d = gmdate('j M', $timetz-(86400*$i));
    	$hours[] = $d;
    	$trend[] = ($data[$d]!="") ? $data[$d] : 0;
    }
  
    $f_url = "../forensics/base_qry_main.php?clear_allcriteria=1&category%5B0%5D=19&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=ZZ&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=ZZ&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d";

} 
else 
{
    $js    = 'analytics_duo';
    $data  = SIEM_trends();
    $data2 = ($prodemo) ? Logger_trends() : array();
    
    for ($i = $max-1; $i >= 0; $i--) 
    {
    	$h = gmdate('j G', $timetz-(3600*$i)).'h';
    	
    	$hours[]  = preg_replace("/^\d+ /", '', $h);
    	$trend[]  = ($data[$h] != '') ? $data[$h] : 0;
    	$trend2[] = ($data2[$h] != '') ? $data2[$h] : 0;
    }
    
    $f_url = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d";
    
    $f_url_y = "../forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-86400)."&time[0][3]=".gmdate("d",$timetz-86400)."&time[0][4]=".gmdate("Y",$timetz-86400)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz-86400)."&time[1][3]=".gmdate("d",$timetz-86400)."&time[1][4]=".gmdate("Y",$timetz-86400)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d";    
}

$empty = TRUE;
?>
<body scroll="no" style="overflow:hidden;font-family:arial;font-size:11px">		
	<table id="data" style="display:none">
        <tfoot>
            <tr>
            	<?php
            	for ($i = 0;$i < $max;$i++) 
            	{
        			$day = ($hours[$i] != '') ? $hours[$i] : '-';
        			echo "<th>$day</th>\n";
            	}
            	?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<?php	
            	for ($i = 0;$i < $max; $i++) 
            	{
        			$value = ($trend[$i] != '') ? $trend[$i] : 0;
        			
                    if ($value != 0) 
                    {
                        $empty = FALSE;
                    }
        			echo "<td>$value</td>\n"; 
            	}
            	?>
            </tr>
        </tbody>
    </table>
    <table id="data2" style="display:none">
        <tbody>
            <tr>
            	<?php	
            	for ($i = 0;$i < $max; $i++) 
            	{
            		$value = ($trend2[$i] != '') ? $trend2[$i] : 0;
            		
            		if ($value != 0) 
                    {
                        $empty = FALSE;
                    }
            			
            		echo "<td>$value</td>\n"; 
            	}
            	?>
            </tr>
        </tbody>
    </table>
	
    <script language="javascript">
    	<?php 
    	if ($empty) 
    	{
    	   echo "var max_aux=100;\n";     	
        }
        ?>
             
        logger_url   = "<?php echo Menu::get_menu_url('../sem/index.php?start='.urlencode(gmdate("Y-m-d",$timetz).' HH:00:00').'&end='.urlencode(gmdate("Y-m-d",$timetz).' HH:59:59') , 'analysis', 'raw_logs', 'raw_logs')?>";
        
        logger_url_y = "<?php echo Menu::get_menu_url('../sem/index.php?start='.urlencode(gmdate("Y-m-d",$timetz-86400).' HH:00:00').'&end='.urlencode(gmdate("Y-m-d",$timetz-86400)." HH:59:59"), 'analysis', 'raw_logs', 'raw_logs')?>";     
        
        
        siem_url = '<?php echo Menu::get_menu_url($f_url, 'analysis', 'security_events', 'security_events')?>';
        
        siem_url_y = '<?php echo Menu::get_menu_url($f_url_y, 'analysis', 'security_events', 'security_events')?>';        
        
        h_now = '<?=gmdate("H",$timetz)?>';
        
    </script>
    
    <?php 
    if (!empty($hours)) 
    { 
        ?>	
    	<script src="../js/raphael/<?=$js?>.js"></script>
    	<script src="../js/raphael/popup.js"></script>		
    	<div id="holder" style='height:100%;width:100%;margin:0;'></div>
	   <?php 
	} 
	else
	{    	
	    ?>	
    	<table style="width:100%;margin-top:25px">
    	<tr><td style="text-align:center"><img src="../pixmaps/shape.png" align="center" border="0"/></td></tr>
    	<tr><td style="text-align:center;color:gray"><?=_('No events found')?></td></tr>
    	</table>
	   	<?php 
    } 
    ?>
</body>
</html>
