<?php
/**
 * base_header.php
 * 
 * File base_header.php is used to:
 * - Be included by includes/base_output_html.inc.php as module of SIEM console
 * - Manage searches and current criterias
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
 * @package    ossim-framework\Siem
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';

Session::logcheck("analysis-menu", "EventsForensics");

// Parameters for date filters
$timetz      = $GLOBALS["timetz"];
$today_d     = gmdate("d",$timetz);
$today_m     = gmdate("m",$timetz);
$today_y     = gmdate("Y",$timetz);
$today_h     = gmdate("H",$timetz);
$yesterday_d = gmdate("d", strtotime("-1 day UTC",$timetz));
$yesterday_m = gmdate("m", strtotime("-1 day UTC",$timetz));
$yesterday_y = gmdate("Y", strtotime("-1 day UTC",$timetz));
$week_d      = gmdate("d", strtotime("-1 week UTC",$timetz));
$week_m      = gmdate("m", strtotime("-1 week UTC",$timetz));
$week_y      = gmdate("Y", strtotime("-1 week UTC",$timetz));
$month_d     = gmdate("d", strtotime("-1 month UTC",$timetz));
$month_m     = gmdate("m", strtotime("-1 month UTC",$timetz));
$month_y     = gmdate("Y", strtotime("-1 month UTC",$timetz));
$year_d      = gmdate("d", strtotime("-11 month UTC",$timetz));
$year_m      = gmdate("m", strtotime("-11 month UTC",$timetz));
$year_y      = gmdate("Y", strtotime("-11 month UTC",$timetz));

// Current signature/payload/IDM filter
$sterm = ($_GET['search_str'] != "") ? $_GET['search_str'] : ($_SESSION['search_str'] != "" ? $_SESSION['search_str'] : _("Search"));

?>

<!-- Solera Form -->
<form action="../conf/solera.php" method="post" id="solera_form">
<input type="hidden" name="from">
<input type="hidden" name="to">
<input type="hidden" name="src_ip">
<input type="hidden" name="dst_ip">
<input type="hidden" name="src_port">
<input type="hidden" name="dst_port">
<input type="hidden" name="proto">
</form>

<!-- MAIN HEADER TABLE -->
<table class="container">
<?php
if (count($database_servers)>0 && Session::menu_perms("configuration-menu", "PolicyServers") && Session::is_pro()) 
{ 
	// session server
	$ss = (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="") ? $_SESSION["server"][0] : "local";
	$sn = (is_array($_SESSION["server"]) && $_SESSION["server"][4]!="") ? $_SESSION["server"][4] : "snort";
	?>
	<tr>
	<td class='noborder' width="45" valign="top" style="padding:10px 0px 0px 0px">
		<table class='transparent' cellpadding="0" cellspacing="0">
	    	<tr>
	    	     <td class='noborder' align='left'>
	    	 
                    <button type="button" class='av_b_gray' onclick='$("#dbs").toggle();$("#img_home").attr("src",(($("#img_home").attr("src").match(/plus/)) ? "images/home_minus.png" : "images/home_plus.png"))'>
                        <img id='img_home' src="images/home_plus.png" align="absmiddle" width='15' height='15'/>
                    </button>		    	     		    	     		    	     		    	     
	    	     
                    <div style='position:relative;width:1px'>
                        <div id='dbs'>
                            <table class='noborder transparent' cellpadding='5' cellspacing='1'>		    	
                            
                                <?php 
                                foreach ($database_servers as $_db_aux) 
                                {                                
                                    $svar = intval($_db_aux->get_id());
                                    // 'end' tag to solve PHPIDS exception
                                    $name = ($ss==$_db_aux->get_ip() && $sn == $_db_aux->get_name()) ? "<b>".$_db_aux->get_name()."</b>" : $_db_aux->get_name();
                                    ?>
                                    <tr>
                                        <td class='left noborder' style='padding:5px'>
                                        <?php                                    
                                        if ($_db_aux->get_icon() != '')
                                        {
                                            ?>
                                            <img id='db_icon' style='width: 16px; height: 16px;' src='data:image/png;base64,<?php echo base64_encode($_db_aux->get_icon())?>' border='0' align='absbottom'/>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <img id='db_icon' style='width: 16px; height: 16px;' src='../forensics/images/server.png' border='0' align='absbottom'/>                                    
                                            <?php
                                        }                                          
                                        ?>                                
                                        </td>
                                        <td class='left noborder' nowrap>
                                            <a href='<?php echo preg_replace("/\&server\=[^\&]+/","",$actual_url) ?>server=<?php echo $svar ?>'><?php echo $name ?></a>
                                        </td>
                                    </tr>
                                    <?php 
                                }
                                ?>                                
                            
                                <tr>
                                    <td class='left noborder' style='padding:5px'>
                                        <img id='db_icon' style='width: 16px; height: 16px;' src='../forensics/images/home.png' border='0' align='absbottom'/>                                
                                    </td>
                                    <td class='left noborder'>
                                        <a href='<?php echo preg_replace("/\&server\=[^\&]+/","",$actual_url) ?>server=local'><?php echo ($ss=="local" ? "<b>"._("Local")."</b>" : _("local")) ?></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
	    	</tr>
	    </table>
	</td>
	</tr>
	<?php 
}
?>
<tr>
	<td valign="top" width="380" class="box" style="border-right:none">
	
	<!-- Query Main Form -->
    <form name="QueryForm" id="frm" action="base_qry_main.php" method="get" style="margin:0 auto">
    
    <input type='hidden' name="search" value="1" />
    <input type="hidden" name="sensor" id="sensor" value="<?php echo Util::htmlentities($_SESSION["sensor"])?>" />
    <input type="hidden" name="hidden_menu" id="hidden_menu" value="1" />
    <input type="submit" name="bsf" id="bsf" value="Query DB" style="display:none">

<table class="container">
<tr>
	<td colspan='2'>
			<!-- Signature, Payload, IDM Search input -->
			
					<div class='siem_form_search'>
                        
                        <div class='left_float'>
                            <input type="text" name="search_str" id="search_str" class="ne pholder" placeholder="<?php echo _('Search') ?>">
                        </div>
                        
                        <div class='left_float'>
                            <select name="submit" class="hselect" id='search_type_combo'>
                                <option value="<?php echo _("Signature") ?>"><?php echo _("Signature") ?></option>
                                <option value="Payload"><?php echo _("Payload") ?></option>
                                <?php if ($_SESSION["_idm"]) { ?>
                                <option value="<?php echo _("IDM Username") ?>"><?php echo _("IDM Username") ?></option>
                                <option value="<?php echo _("IDM Hostname") ?>"><?php echo _("IDM Hostname") ?></option>
                                <option value="<?php echo ("IDM Domain") ?>"><?php echo ("IDM Domain") ?></option>
                                <?php } ?>
                                <option value="<?php echo _("Src or Dst IP") ?>"><?php echo _("Src or Dst IP") ?></option>
                                <option value="<?php echo _("Src IP") ?>"><?php echo _("Src IP") ?></option>
                                <option value="<?php echo _("Dst IP") ?>"><?php echo _("Dst IP") ?></option>
                                <option value="<?php echo _("Src or Dst Host") ?>"><?php echo _("Src or Dst Host") ?></option>
                                <option value="<?php echo _("Src Host") ?>"><?php echo _("Src Host") ?></option>
                                <option value="<?php echo _("Dst Host") ?>"><?php echo _("Dst Host") ?></option>
                            </select>
                        </div>
							
                        <!-- GO SUBMIT BUTTON -->
                        <div class='left_float'>
                            <input type="submit" style="padding:6px 5px" value="<?php echo _('Go') ?>" id="go_button">
                        </div>
                        
                        <img id='help_tooltip' class='help_icon_1' src="/ossim/pixmaps/help_small.png">
                        
					
				
				
				<!-- Export data in PDF/CSV -->
				<?php
				// Events
				if (preg_match("/base_qry_main\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $export_pdf_mode = ($_SESSION['current_cview'] == "IDM" || $_SESSION['current_cview'] == "default") ? "Events_Report" : "";
				    $export_csv_mode = "Events_Report";
				    $csv_report_type = 33;
				}
				// Unique events
				elseif (preg_match("/base_stat_alerts\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $export_pdf_mode = "UniqueEvents_Report";
				    $export_csv_mode = "UniqueEvents_Report";
				    $csv_report_type = 36;
				}
				// Unique sensors
				elseif (preg_match("/base_stat_sensor\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $export_pdf_mode = "Sensors_Report";
				    $export_csv_mode = "Sensors_Report";
				    $csv_report_type = 38;
				}
				// Unique data sources
				elseif (preg_match("/base_stat_plugins\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $export_pdf_mode = "UniquePlugin_Report";
				    $export_csv_mode = "UniquePlugin_Report";
				    $csv_report_type = 46;
				}
				// Unique IPs Src/Dst
				elseif (preg_match("/base_stat_uaddr|base_stat_uidm/", $_SERVER['SCRIPT_NAME']) && ($_GET['addr_type'] == "1" || $_GET['addr_type'] == "2"))
				{
				    $export_pdf_mode = "UniqueAddress_Report".intval($_GET['addr_type']);
				    $export_csv_mode = "UniqueAddress_Report".intval($_GET['addr_type']);
				    $csv_report_type = 40;
				}
				// Unique ports
				elseif (preg_match("/base_stat_ports\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $_report_type    = ($_GET['proto'] == '6') ? 1 : (($_GET['proto'] == '17') ? 2 : 0);
				    $export_pdf_mode = ($_GET['port_type'] == 1) ? "SourcePort_Report$_report_type" : "DestinationPort_Report$_report_type";
				    $export_csv_mode = ($_GET['port_type'] == 1) ? "SourcePort_Report$_report_type" : "DestinationPort_Report$_report_type";
				    $csv_report_type = ($_GET['port_type'] == 1) ? 42 : 44;
				}
				// Unique IPLinks
				elseif (preg_match("/base_stat_iplink\.php/", $_SERVER['SCRIPT_NAME']) && GET('fqdn') == 'no')
				{
				    $export_pdf_mode = "UniqueIPLinks_Report";
				    $export_csv_mode = "UniqueIPLinks_Report";
				    $csv_report_type = 37;
				}
				// Unique Countries
				elseif (preg_match("/base_stat_country\.php/", $_SERVER['SCRIPT_NAME']))
				{
				    $export_pdf_mode = "UniqueCountryEvents_Report";
				    $export_csv_mode = "UniqueCountryEvents_Report";
				    $csv_report_type = 48;
				}
				else
				{
				    $export_pdf_mode = "";
				    $export_csv_mode = "";
				}
				
				
				// Deprecated
				$cloud_instance = ($conf->get_conf("cloud_instance", FALSE) == 1) ? TRUE : FALSE;
				
				if (($export_pdf_mode != '' || $export_csv_mode != '') && !$cloud_instance)
				{
    				?>
    				<div style='float:right'>
                        <a style='cursor:pointer' class='ndc' onclick="$('#export').toggle()"><img src="../pixmaps/forensic_download.png" border="0"/></a>
                        <div style="position:relative">
                            <div id="export">
                                <div>
                                    <table>

                                        <thea>
                                            <tr>
                                                <th id='title'>
                                                    <div id='c_close'>
                                                        <div id='b_close'>
                                                            <a style="cursor:pointer;" onclick="$('#export').toggle()">
                                                                <img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/>
                                                            </a>
                                                        </div>
                                                    </div>

                                                    <span><?php echo _("Export Mode")?></span>
                                                </th>
                                            </tr>
                                        </thead>
                                        
                                        <tbody>
                                        
                                            <?php
                                            // PDF
                                            if ($export_pdf_mode != '')
                                            {
                                                ?>
                                                <tr>
                                                    <td class='left'>
                                                        <a href="javascript:;" onclick="javascript:report_launcher('<?php echo $export_pdf_mode ?>','pdf');return false">
                                                            <img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"> <?php echo _("Download data as PDF Report") ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            
                                            // CSV
                                            if ($export_csv_mode != "")
                                            {
                                                ?>
                                                <tr>
                                                    <td class='left'>
                                                       <a href="javascript:;" onclick="javascript:report_launcher('<?php echo $export_csv_mode ?>','<?php echo $csv_report_type ?>');return false">
                                                           <img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"> <?php echo _("Download data in CSV format") ?>
                                                       </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
				    <?php 
				} 
				?>
				<div class='clear_layer'></div>
			</div>

			<!-- Date, DS, Sensor, Risk, Taxonomy, Reputation boxes -->
			
				    <div>
            				    
            				    <!-- Datetime -->
            				    
            				        <div class='siem_form_column'>
                                    <?php
                                    $urltimecriteria = Util::get_sanitize_request_uri($_SERVER['SCRIPT_NAME']);
                                    $params = "";
                                    // Clicked from qry_alert or clicked from Time profile must return to main
                                    if (preg_match("/base_qry_alert|base_stat_time/", $urltimecriteria)) {
                                        $urltimecriteria = "base_qry_main.php";
                                    }
                                    if ($_GET["addr_type"]  != "") $params.= "&addr_type=" .  urlencode($_GET["addr_type"]);
                                    if ($_GET["sort_order"] != "") $params.= "&sort_order=" . urlencode($_GET["sort_order"]);
                                    if ($_GET["proto"]      != "") $params.= "&proto=" . urlencode($_GET["proto"]);
                                    if ($_GET["port_type"]  != "") $params.= "&port_type=" . urlencode($_GET["port_type"]);
                                    ?>
                                    <div class='siem_form_title'><?php echo _("Show Events") ?></div>
                                    
    				                    <div>
    				                        <div class='siem_form_daterange'>
    				                        <input class="margin0" type="radio" <? if ($_GET['time_range'] == "day")   echo "checked" ?> name="selected_time_range" onclick="document.location.href='<?php echo Util::get_sanitize_request_uri($urltimecriteria) ?>?time_range=day&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $yesterday_m ?>&time%5B0%5D%5B3%5D=<?php echo $yesterday_d ?>&time%5B0%5D%5B4%5D=<?php echo $yesterday_y ?>&time%5B0%5D%5B5%5D=<?php echo $today_h ?>&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>'"/>
    				                        <?php echo _("Last Day") ?>
    				                        </div>
    				                        
    				                        <div class='siem_form_daterange'>
    				                        <input class="margin0" type="radio" <? if ($_GET['time_range'] == "week")  echo "checked" ?> name="selected_time_range" onclick="document.location.href='<?php echo Util::get_sanitize_request_uri($urltimecriteria) ?>?time_range=week&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $week_m ?>&time%5B0%5D%5B3%5D=<?php echo $week_d ?>&time%5B0%5D%5B4%5D=<?php echo $week_y ?>&time%5B0%5D%5B5%5D=<?php echo $today_h ?>&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>'"/>
    				                        <?php echo _("Last Week") ?>
    				                        </div>
    				                        
    				                        <div class='siem_form_daterange'>
    				                        <input class="margin0" type="radio" <? if ($_GET['time_range'] == "month") echo "checked" ?> name="selected_time_range" onclick="document.location.href='<?php echo Util::get_sanitize_request_uri($urltimecriteria) ?>?time_range=month&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $month_m ?>&time%5B0%5D%5B3%5D=<?php echo $month_d ?>&time%5B0%5D%5B4%5D=<?php echo $month_y ?>&time%5B0%5D%5B5%5D=<?php echo $today_h ?>&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>'"/>
    				                        <?php echo _("Last Month") ?>
    				                        </div>
    				                        
    				                        <div class='siem_form_daterange'>
    				                        <input class="margin0" type="radio" <? if ($_GET['time_range'] == "range") echo "checked" ?> name="selected_time_range" onclick='show_calendar()'/>
    				                        <?php echo _("Date Range") ?>
    				                        </div>
    				                        
    				                        
			                            <div class='datepicker_range siem_form_daterange'>
                                            <div class='calendar_from'>
                                                <div class='calendar'>
                                                    <input id='date_from' class='date_filter' type="input" value="<?php if ($_SESSION['time_range'] == "range") echo (($_SESSION['time'][0][4] != '') ? $_SESSION['time'][0][4] : '*')."-".(($_SESSION['time'][0][2] != '') ? $_SESSION['time'][0][2] : '*')."-".(($_SESSION['time'][0][3] != '') ? $_SESSION['time'][0][3] : '*') ?>">
                                                </div>
                                            </div>
                                            <div class='calendar_separator'>
                                                -
                                            </div>
                                            <div class='calendar_to'>
                                                <div class='calendar'>
                                                    <input id='date_to' class='date_filter' type="input" value="<?php if ($_SESSION['time_range'] == "range") echo (($_SESSION['time'][1][4] != '') ? $_SESSION['time'][1][4] : '*')."-".(($_SESSION['time'][1][2] != '') ? $_SESSION['time'][1][2] : '*')."-".(($_SESSION['time'][1][3] != '') ? $_SESSION['time'][1][3] : '*') ?>">
                                                </div>
                                            </div>
                                        </div>
    				                            
    				                    </div>
            				         </div>
            				    
            				        <div class='siem_form_column'>
            				            
            				            <!-- Data Sources -->
            				            <div class='siem_form_title'><?php echo _("Data Sources") ?></div>
            				            <div>
            				                    <select name="plugin" class="selectp" onchange="$('input[name=sourcetype],#category,#subcategory').val('');this.form.bsf.click()"><option value=''></option> 
                            					<?php
                            					// Get Plugins
                            					$snortsensors = GetPlugins($db);
                            					uksort($snortsensors, "strnatcasecmp");
                            					
                            					// Get selected
                            					$plugins_selected = array();
                            					if (preg_match('/\,/', $_SESSION['plugin']))
                            					{
                            					    $plugins_selected = explode(',', $_SESSION['plugin']);
                            					}
                            					elseif (preg_match('/(\d+)\-(\d+)/', $_SESSION['plugin'], $found) && $found[1] < $found[2])
                            					{
                            					    for ($i = $found[1]; $i <= $found[2]; $i++)
                            					    {
                            					        $plugins_selected[] = $i;
                            					    }
                            					}
                            					else
                            					{
                            					    $plugins_selected[] = $_SESSION['plugin'];
                            					}
                            					
                            					// Print select box
                            					foreach($snortsensors as $plugin_name => $pids)
                                             {
                            						$pid       = implode(",", $pids);
                            						$intersect = array_intersect($pids, $plugins_selected);
                            						$sel       = (count($intersect) > 0) ? "selected" : "";
                            						echo "<option value='".Util::htmlentities($pid)."' $sel>".Util::htmlentities(ucfirst($plugin_name))."</option>\n";
                            					}
                            					?>
                            					</select>
            				            </div>
                                    
                                    <!-- Risk -->
                                    <div class='siem_form_title'><?php echo _("Risk") ?></div>
                                    <div>
                                            <select name="ossim_risk_a" class="selectp" onchange="this.form.bsf.click()"><option value=' '>
                            					<option value="low"<?php if ($_SESSION['ossim_risk_a'] == "low") echo " selected" ?>><?php echo _("Low") ?></option>
                            					<option value="medium"<?php if ($_SESSION['ossim_risk_a'] == "medium") echo " selected" ?>><?php echo _("Medium") ?></option>
                            					<option value="high"<?php if ($_SESSION['ossim_risk_a'] == "high") echo " selected" ?>><?php echo _("High") ?></option>
                                            </select>
                                    </div>
                                    
                                    <!-- Sensors -->
            				            <div class='siem_form_title'><?php echo _("Sensors") ?></div>
            				            <div>
                        						<input type="text" size="10" name="sip" id="sip" class='search_sensor'>
                        			    </div>
                                    <!-- <tr><td style="padding-top:8px"><input type="slider" id="risk_slider" name="ossim_risk_a" value="2"/></td></tr> -->
            				        </div>
            				    
            				    
            				    <!-- Taxonomy -->
            				    
            				        <div class='siem_form_column'>
            				            
            				            <!-- Product Type -->
            				            <div class='siem_form_title'><?php echo _("Taxonomy") ?>: <?php echo _("Product Type") ?></div>
            				            <div>
            				                <select name="sourcetype" class="selectp" onchange="$('input[name=plugin]').val('');this.form.bsf.click()"><option value=''></option> 
										<?php
										$srctypes = GetSourceTypes($db);
										foreach ($srctypes as $srctype_id => $srctype_name) echo "<option value=\"$srctype_id\"".(($_SESSION["sourcetype"]==$srctype_id) ? " selected" : "").">" ._($srctype_name) ."</option>\n";
										?>
                                        </select>
                                    </div>
            				            
            				            <!-- Category -->
            				            <div class='siem_form_title'><?php echo _("Taxonomy") ?>: <?php echo _("Event Category") ?></div>
            				            <div>
            				                <select name="category[0]" id="category" class="selectp" onchange="$('input[name=plugin]').val('');$('input[name=hidden_menu]').val('0');this.form.bsf.click()"><option value=''></option> 
										<?php
										$categories = GetPluginCategories($db);
										foreach ($categories as $idcat => $category) echo "<option value=\"$idcat\"".(($_SESSION["category"][0]!=0 && $_SESSION["category"][0]==$idcat) ? " selected" : "").">" . _($category) . "</option>\n";
										?>
                                        </select>
                                     </div>
            				            
            				            <!-- Subcategory -->
            				            <?php
            				            // Show it only when the category is already selected
            				            if ($_SESSION["category"][0] > 0)
            				            {
            				            ?>
            				            <div class='siem_form_title'><?php echo _("Taxonomy") ?>: <?php echo _("Sub Category") ?></div>
            				            <div>
										<select name="category[1]" id="subcategory" class="selectp" onchange="$('input[name=plugin]').val('');this.form.bsf.click()"><option value=''></option> 
                                        <?php
                                        $subcategories = GetPluginSubCategories($db,$categories);
                                        if (is_array($subcategories[$_SESSION["category"][0]]))
                                        {
                                            foreach ($subcategories[$_SESSION["category"][0]] as $idscat => $subcategory)
                                            { 
											   echo "<option value=\"$idscat\"".(($_SESSION["category"][1]!=0 && $_SESSION["category"][1]==$idscat) ? " selected" : "").">$subcategory</option>\n";
                                            }
                                        }
                                        ?>
                                        </select>
                                     </div>
            				            <?php
            				            }
            				            ?>
            				        </div>
            				    
            				    
            				    <!-- Reputation -->
            				    <?php global $rep_activities, $rep_severities; ?>
            				    
            				        <div class='siem_form_column'>
            				            <div class='siem_form_title'><?php echo _("IP Reputation Activity") ?></div>
            				            <div>
            				                <select name="rep[0]" id="activity" class="selectp" onchange="this.form.bsf.click()"><option value=''></option> 
										    <option value='0'<?php echo ($_SESSION["rep"][0]=="0") ? " selected" : "";?>><?php echo _("ANY") ?></option> 
										<?
										foreach ($rep_activities as $idact => $act) echo "<option value=\"".Util::htmlentities($idact)."\"".(($_SESSION["rep"][0]!="" && $_SESSION["rep"][0]==$idact) ? " selected" : "").">".Util::htmlentities($act)."</option>\n";
										?>
                                        </select>
                                    </div>
            				            
            				            <div class='siem_form_title'><?php echo _("IP Reputation Severity") ?></div>
            				            <div>
            				                <select name="rep[1]" id="severity" class="selectp" onchange="this.form.bsf.click()"><option value=''></option> 
										<?
										foreach ($rep_severities as $sev) echo "<option value=\"".Util::htmlentities($sev)."\"".(($_SESSION["rep"][1]!="" && $_SESSION["rep"][1]==$sev) ? " selected" : "").">"._($sev)."</option>\n";
										?>
                                        </select>
                                     </div>
            				        </div>
            				        
            				        <div class='siem_form_column'>
            				        <?php
                                // Current criteria box
                                PrintCriteria2();
                                ?>
            				        </div>
            				        
            				        <div class='clear_layer'></div>
            				</div>
            
			        <div id="filters_buttons_div">
			            <div class="float_left">
			                <input type="button" class="av_b_secondary" value="+ <?php echo _("More Filters") ?>"  id="more_filters_button"/>
			                <input type="button" class="av_b_secondary" value="<?php echo _("Advanced Search") ?>" id="adv_search_button"/>
			             </div>
			             
			             <div class="float_right padding_right_5 task_info">
			                <span id="task" style="display:none;"><?php echo _("No pending tasks") ?>.</span>
			             </div>
			             
			        </div>
			
			
			        <div id="more_filters" style="display:none">
			        <!-- Hidden table more filters: User Data, Device, DS Groups, Host Groups, Net Groups -->
			
			        <table class="transparent">
			            <tr>
			                <td valign="top">
			                    <table class="transparent">
			                        <!-- User Data -->
			                        <tr>
										<td style="font-size:11px" nowrap><?php echo _("Extra Data")?>:</td>
										<td style="padding-left:10px;text-align:left;">
										<select name="userdata[0]">
											<option value="userdata1"<?= ($_SESSION["userdata"][0]=="userdata1") ? " selected" : ""; ?>>userdata1</option>
											<option value="userdata2"<?= ($_SESSION["userdata"][0]=="userdata2") ? " selected" : ""; ?>>userdata2</option>
											<option value="userdata3"<?= ($_SESSION["userdata"][0]=="userdata3") ? " selected" : ""; ?>>userdata3</option>
											<option value="userdata4"<?= ($_SESSION["userdata"][0]=="userdata4") ? " selected" : ""; ?>>userdata4</option>
											<option value="userdata5"<?= ($_SESSION["userdata"][0]=="userdata5") ? " selected" : ""; ?>>userdata5</option>
											<option value="userdata6"<?= ($_SESSION["userdata"][0]=="userdata6") ? " selected" : ""; ?>>userdata6</option>
											<option value="userdata7"<?= ($_SESSION["userdata"][0]=="userdata7") ? " selected" : ""; ?>>userdata7</option>
											<option value="userdata8"<?= ($_SESSION["userdata"][0]=="userdata8") ? " selected" : ""; ?>>userdata8</option>
											<option value="userdata9"<?= ($_SESSION["userdata"][0]=="userdata9") ? " selected" : ""; ?>>userdata9</option>
											<option value="filename"<?= ($_SESSION["userdata"][0]=="filename") ? " selected" : ""; ?>>filename</option>
											<option value="username"<?= ($_SESSION["userdata"][0]=="username") ? " selected" : ""; ?>>username</option>
											<option value="password"<?= ($_SESSION["userdata"][0]=="password") ? " selected" : ""; ?>>password</option>
										</select>
										<select name="userdata[1]">
											<option value="EQ"<?= ($_SESSION["userdata"][1]=="EQ") ? " selected" : ""; ?>>=</option>
											<option value="NE"<?= ($_SESSION["userdata"][1]=="NE") ? " selected" : ""; ?>><></option>
											<option value="LT"<?= ($_SESSION["userdata"][1]=="LT") ? " selected" : ""; ?>><</option>
											<option value="LOE"<?= ($_SESSION["userdata"][1]=="LOE") ? " selected" : ""; ?>><=</option>
											<option value="GT"<?= ($_SESSION["userdata"][1]=="GT") ? " selected" : ""; ?>>></option>
											<option value="GOE"<?= ($_SESSION["userdata"][1]=="GOE") ? " selected" : ""; ?>>>=</option>
											<option value="like"<?= ($_SESSION["userdata"][1]=="like") ? " selected" : ""; ?>>like</option>
										</select>
									</tr>
									<tr>
										<td></td>
										<td style="padding-left:10px;text-align:left">
										<table class="noborder">
											<tr>
												<td><input type="text" name="userdata[2]" style="width:158px;font-size:12px" value="<?php echo Util::htmlentities($_SESSION["userdata"][2]) ?>"/></td>
												<td><input type="button" value="<?php echo _("Apply")?>" onclick="this.form.bsf.click()" class="small av_b_secondary"/></td>
											</tr>
										</table>
										</td>
									</tr>
			                    </table>
			                </td>
			                
			                <td valign="top">
			                    <table class="transparent">
			                        
			                        <!-- DS Groups -->
			                        <tr class="noborder">
										<td nowrap>
											<div style='text-align: left; padding-bottom: 15px; clear: both;'>
												<div style='float: left; width:95px; font-size:11px; padding-top:4px'><?=_("DS Groups")?>:</div>
												<div style='float: left; margin-left: 5px;'>
													<select name="plugingroup" class="selectp" onchange="this.form.bsf.click()"><option value=''></option> 
													<?php
													$pg = GetPluginGroups($db);
													foreach ($pg as $idpg => $namepg) echo "<option value='$idpg'".(($_SESSION["plugingroup"]==$idpg) ? " selected" : "").">$namepg</option>\n";
													?>
													</select>
												</div>
											</div>
										</td>
									</tr>
									
									<!-- Network Groups -->
									<tr class="noborder">
										<td nowrap>
											<div style='text-align: left; padding-bottom: 15px; clear: both;'>
												<div style='float: left; width:95px; font-size:11px; padding-top:12px'><?=_("Network Groups")?>:</div>
												<div style='float: left; margin-left: 5px; padding-top:8px'>
													<select name="networkgroup" class="selectp" onchange="this.form.bsf.click()">
														<option value=''></option> 
														<?php
														$ng = GetOssimNetworkGroups();
														foreach ($ng as $ngid => $nameng) echo "<option value='$ngid'".(($_SESSION["networkgroup"]==$ngid) ? " selected" : "").">$nameng</option>\n";
														?>
													</select>
												</div>
											</div>
										</td>
									</tr>
			                    </table>
			                </td>
			                <td valign="top">
			                    <table class="transparent">
			                        
			                        <!-- Host Groups -->
			                        <tr class="noborder">
										<td nowrap>
											<div style='text-align: left; padding-bottom: 15px; clear: both;'>
												<div style='float: left; width:95px; font-size:11px; padding-top:4px'><?=_("Asset Groups")?>:</div>
												<div style='float: left; margin-left: 5px;'>
													<select name="addhomeips" class="selectp" onchange="this.form.bsf.click()"><option value='-1'></option> 
													<?php
													$hg = GetOssimHostGroups();
													foreach ($hg as $hgid => $namehg) 
													{
														echo "<option value='".$hgid."'".(($_SESSION["_hostgroup"]==$hgid) ? " selected" : "").">$namehg</option>\n";
													}
													?>
													</select>
												</div>
											</div>
										</td>
									</tr>
									
                                    <!-- Device -->
                                    <?php
                                    if (Session::is_pro())
                                    {
                                    ?>
									<tr class="noborder">
										<td nowrap>
											<div style='text-align: left; padding-bottom: 15px; clear: both;'>
												<div style='float: left; width:95px; font-size:11px; padding-top:12px'><?=_("Device")?>:</div>
												<div style='float: left; margin-left: 5px;padding-top:8px'>
													<input type="text" name="device" id="device_input" value="<?php if ($_SESSION['device'] != "") echo Util::htmlentities($_SESSION['device']) ?>" style="width:180px"/>
												</div>
											</div>
										</td>
									</tr>
									<?php } ?>
			                    </table>
			                </td>
			            </tr>
			        </table>
                </div>
	</td>
</tr>

<tr>
    <td style="padding:0px">
        <table class="container">
            <tr>
            		<td style="text-align:left">
            		<div id='tab_siem'>
            		    
            		    <!-- The target is actually processing by data-action_id, always reload the page -->
            		    <div id='null_aux_div'></div>
            		    
            		    <ul>
            		        <li>
                            <?php
                            if (preg_match("/base\_qry\_main/", $_SERVER['SCRIPT_NAME']))
                            {
                            ?>
                            <a href="#null_aux_div" data-action_id='0'>
                            <?php
                            }
                            else
                            {
                            ?>
                            <a href="#null_aux_div" data-action_id='1'>
                            <?php
                            }
                            ?>
                            <?php echo _("Events") ?>
                            </a>
            		        </li>
            		        <li>
        		                <a href="#null_aux_div" data-action_id='2'>
                            <?php echo _("Grouped"); ?>
                            </a>
            		        </li>
            		        <li>
                            <?php
                            if (preg_match("/base\_timeline/", $_SERVER['SCRIPT_NAME']))
                            {
                            ?>
    		                    <a href="#null_aux_div" data-action_id='3'>
        		                <?php
        		                }
        		                else
        		                {
        		                ?>
                            <a href="#null_aux_div" data-action_id='4'>
        		                <?php 
        		                }
        		                ?>
                            <?php echo _("Timeline") ?>
                            </a>
            		        </li>
            		    </ul>
            		</div>
            		</td>
            	</tr>        
        </table>
    </td>
</tr>

<!-- Grouped option -->
<tr>
    <td id="grouped_option" style="padding:3px;padding-left:0px;padding-top:10px;display:none">
        <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
                <td class="left siem_title_gray"><?php echo _("Group Events by") ?></td>
                <td class="left">
                <?php global $addr_type ?>
                    <!-- Level 1 grouping -->
                    <select name="groupby_1" id="groupby_1" onchange="group_selected(this.value)">
                        <option value=""><?php echo _("Select One") ?></option>
                        <option value="ip"         <?php if (preg_match("/base_stat_(uaddr|iplink)/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("IP") ?></option>
                        <option value="hostname"   <?php if (preg_match("/base_stat_uidm/", $_SERVER['SCRIPT_NAME']) && preg_match("/host/", $addr_type)) echo "selected" ?>><?php echo _("IDM Hostname") ?></option>
                        <option value="username"   <?php if (preg_match("/base_stat_uidm/", $_SERVER['SCRIPT_NAME']) && preg_match("/user/", $addr_type)) echo "selected" ?>><?php echo _("IDM Username") ?></option>
                        <option value="signature"  <?php if (preg_match("/base_stat_alerts\.php/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Signature") ?></option>
                        <option value="port"       <?php if (preg_match("/base_stat_ports/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Port") ?></option>
                        <option value="sensor"     <?php if (preg_match("/base_stat_sensor\.php/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Sensors") ?></option>
                        <option value="ptypes"     <?php if (preg_match("/base_stat_ptypes/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Product Type") ?></option>
                        <option value="plugins"    <?php if (preg_match("/base_stat_plugins/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Data Source") ?></option>
                        <option value="country"    <?php if (preg_match("/base_stat_country/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Country") ?></option>
                        <option value="categories" <?php if (preg_match("/base_stat_categories/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("Categories") ?></option>
                    </select>
                    
                    <!-- Level 2: IP -->
                    <div id="group_ip_select" style="display:<?php echo (preg_match("/base_stat_(uaddr|iplink)/", $_SERVER['SCRIPT_NAME'])) ? "inline" : "none" ?>">
                    <select name="groupby_ip" id="groupby_ip" onchange="group_selected(this.value)">
                        <option value="ipempty"><?php echo _("Select one") ?></option>
                        <option value="ipboth"      <?php if (preg_match("/base_stat_uaddress\.php/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("All") ?></option>
                        <option value="ipsrc"       <?php if (preg_match("/base_stat_uaddr\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == 1) echo "selected" ?>><?php echo _("Source") ?></option>
                        <option value="ipdst"       <?php if (preg_match("/base_stat_uaddr\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == 2) echo "selected" ?>><?php echo _("Destination") ?></option>
                        <option value="iplink"      <?php if (preg_match("/base_stat_iplink\.php/", $_SERVER['SCRIPT_NAME']) && $_GET['fqdn'] == 'no') echo "selected" ?>><?php echo _("IP Links") ?></option>
                        <option value="iplink_fqdn" <?php if (preg_match("/base_stat_iplink\.php/", $_SERVER['SCRIPT_NAME']) && $_GET['fqdn'] == 'yes') echo "selected" ?>><?php echo _("IP Links [FQDN]") ?></option>
                    </select>
                    </div>
                    
                    <!-- Level 2: Hostname -->
                    <div id="group_hostname_select" style="display:<?php echo (preg_match("/base_stat_uidm/", $_SERVER['SCRIPT_NAME']) && preg_match("/host/", $_GET['addr_type'])) ? "inline" : "none" ?>">
                    <select name="groupby_hostname" id="groupby_hostname" onchange="group_selected(this.value)">
                        <option value="hostnameempty"><?php echo _("Select one") ?></option>
                        <option value="hostnameboth" <?php if (preg_match("/base_stat_uidm\.php/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("All") ?></option>
                        <option value="hostnamesrc"  <?php if (preg_match("/base_stat_uidmsel\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == "src_hostname") echo "selected" ?>><?php echo _("Source") ?></option>
                        <option value="hostnamedst"  <?php if (preg_match("/base_stat_uidmsel\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == "dst_hostname") echo "selected" ?>><?php echo _("Destination") ?></option>
                    </select>
                    </div>
                    
                    <!-- Level 2: Username -->
                    <div id="group_username_select" style="display:<?php echo (preg_match("/base_stat_uidm/", $_SERVER['SCRIPT_NAME']) && preg_match("/user/", $_GET['addr_type'])) ? "inline" : "none" ?>">
                    <select name="groupby_username" id="groupby_username" onchange="group_selected(this.value)">
                        <option value="usernameempty"><?php echo _("Select one") ?></option>
                        <option value="usernameboth" <?php if (preg_match("/base_stat_uidm\.php/", $_SERVER['SCRIPT_NAME'])) echo "selected" ?>><?php echo _("All") ?></option>
                        <option value="usernamesrc"  <?php if (preg_match("/base_stat_uidmsel\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == "src_userdomain") echo "selected" ?>><?php echo _("Source") ?></option>
                        <option value="usernamedst"  <?php if (preg_match("/base_stat_uidmsel\.php/", $_SERVER['SCRIPT_NAME']) && $addr_type == "dst_userdomain") echo "selected" ?>><?php echo _("Destination") ?></option>
                    </select>
                    </div>
                    
                    <!-- Level 2: Port -->
                    <div id="group_port_select" style="display:<?php echo (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME'])) ? "inline" : "none" ?>">
                    <?php global $port_type ?>
                    <select name="groupby_port" id="groupby_port" onchange="group_selected(this.value)">
                        <option value="portempty"><?php echo _("Select one") ?></option>
                        <option value="portsrc" <?php if (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME']) && $port_type == "1") echo "selected" ?>><?php echo _("Source") ?></option>
                        <option value="portdst" <?php if (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME']) && $port_type == "2") echo "selected" ?>><?php echo _("Destination") ?></option>
                    </select>
                    </div>
                    
                    <!-- Level 3: Port Protocol -->
                    <div id="group_proto_select" style="display:<?php echo (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME'])) ? "inline" : "none" ?>">
                    <?php global $proto ?>
                    <select name="groupby_proto" id="groupby_proto" onchange="group_selected(this.value)">
                        <option value="portprotoempty"><?php echo _("Select one") ?></option>
                        <option value="portprotoany" <?php if (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME']) && $proto == "-1")  echo "selected" ?>><?php echo _("Any") ?></option>
                        <option value="portprototcp" <?php if (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME']) && $proto == "6")  echo "selected" ?>><?php echo _("TCP") ?></option>
                        <option value="portprotoudp" <?php if (preg_match("/base_stat_port/", $_SERVER['SCRIPT_NAME']) && $proto == "17") echo "selected" ?>><?php echo _("UDP") ?></option>
                    </select>
                    </div>
                </td>
                <td><input id="group_button" class="small av_b_secondary" type="button" value="<?php echo _("Group") ?>" onclick="go_stats()" style="display:none"/></td>
            </tr>
        </table>
    </td>
</tr>

</table>

</form>

</td>
</tr>
</table>
<?php

/* End of file base_header.php */
/* Location: ./forensics/base_header.php */