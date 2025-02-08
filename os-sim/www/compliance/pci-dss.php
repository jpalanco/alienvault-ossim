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

Session::logcheck("configuration-menu", "ComplianceMapping");

$table   = GET('table');
$ref     = GET('ref');
$toggle  = GET('toggle');
$version = intval(GET('pci_version'));

ossim_valid($table, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Table value"));
ossim_valid($ref, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,    'illegal:' . _("Ref value"));
ossim_valid($toggle, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("toggle"));

if (ossim_error()) 
{
	die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();


Compliance_pci::set_pci_version($version);

if ($table != "" && $ref != "") 
{
	Compliance_pci::update_attr($conn,$table,$ref);
}

$sections = Compliance_pci::get_requirement_names();
$groups   = Compliance_pci::get_groups($conn);

?>
<html>
    
<head>
	<title> <?php echo _("OSSIM Framework"); ?> - Compliance </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css" />
	<link rel="stylesheet" type="text/css" href="/ossim/style/configuration/threat_intelligence/compliance_pci.css" />
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	
	<script type="text/javascript">
    	var toggled = "<?php echo $toggle?>"; // Subgroup toggled variable
    	
    	function toggle_group(id) 
    	{
    		toggled = id;
    		document.getElementById(id).style.display = "table-row";
    		var button = id+"_button";
    		document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"untoggle_group('"+id+"');return false;\"><img src='../pixmaps/minus-small.png' border='0'></a>";
    	}
    	
    	function untoggle_group(id) 
    	{
    		toggled = "";
    		document.getElementById(id).style.display = "none";
    		var button = id+"_button";
    		document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"toggle_group('"+id+"');return false;\"><img src='../pixmaps/plus-small.png' border='0'></a>";
    	}
    	
    	function hide_plugins (ref) 
    	{
    		var td = "SIDS_"+ref;
    		document.getElementById(td).innerHTML = "";
    		plus = "plus_"+ref;
    		document.getElementById(plus).innerHTML = "<a href='' onclick=\"get_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'></a>";
    	}
    	
    	function get_plugins (ref) 
    	{
    		var td = "SIDS_"+ref;
    		document.getElementById(td).innerHTML = "<img src='../pixmaps/loading.gif' alt='Loading' style='width: 16px; height: 16px; vertical-align: middle;'><span style='margin-left: 3px;'><?php echo _("Loading")?> ...</span>";
    		$.ajax({
    			type: "GET",
    			url: "plugins_response.php?ref="+ref+"&compliance=PCI&pci_version=<?php echo $version?>",
    			data: "",
    			success: function(msg){
    				document.getElementById(td).innerHTML = msg;
    				
    				plus = "plus_"+ref;
    				document.getElementById(plus).innerHTML = "<a href='' onclick=\"hide_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
    			}
    		});
    	}
    		
    	function GB_onclose() 
    	{
    		document.location.href='/ossim/compliance/pci-dss.php?&pci_version=<?php echo $version ?>&toggle='+toggled;
    	}
    	
    	$(document).ready(function()
    	{
    		GB_TYPE = 'w';
    		
    		$("a.greybox").click(function()
    		{
    			var t = this.title || $(this).text() || this.href;
    			GB_show(t, this.href, 600, '70%');
    			return false;
    		});
    		
    		$("a.greybox_small").click(function()
    		{
    			var t = this.title || $(this).text() || this.href;
    			GB_show(t, this.href, 250, 450);
    			return false;
    		});
    		
            $(".scriptinfo").tipTip({maxWidth: "500px"});
           	
    	});
    	
    </script>
    
</head>

<body>
<?php 
    //Local menu		      
    include_once '../local_menu.php';
?>

    <table id="main_table">
    <?php
    $group_id = 0;
    
    foreach ($groups as $title => $data) 
    {
        $td_class = ($group_id % 2 == 0) ? 'odd' : 'even';
        
    	?>
    	<tr>
    		<?php 
    		if ( $title != $toggle ) 
    		{ 
    			?>
    			<td width="10" class="nobborder <?php echo $td_class?>" id="<?php echo $title?>_button"><a href="javascript:;" onclick="toggle_group('<?php echo $title?>');return false;"><img src="../pixmaps/plus-small.png" alt="toggle" border="0"/></a></td>
    			<?php 
    		} 
    		else 
    		{ 
    			?>
    			<td width="10" class="nobborder <?php echo $td_class?>" id="<?php echo $title?>_button"><a href="javascript:;" onclick="untoggle_group('<?php echo $title?>');return false;"><img src="../pixmaps/minus-small.png" alt="untoggle" border="0"/></a></td>
    			<?php 
    		} 
    		?>
    		<th class="<?php echo $td_class?>" style="text-align:left;padding:5px"><?php echo $sections[$data['title']]?></th>
    	</tr>
    
    	<tr id="<?php echo $title?>" <?php echo ($toggle != $title) ? 'style="display:none"' : '' ?>>
    		<td class="nobborder wtd"></td>
    		<td class="nobborder wtd">
    			<table class="table_list" width="100%">
    				<tr>
    				    <td class="nobborder wtd"></td>
    					<td class="nobborder wtd"></td>
    					<th class="t_title"><?php echo _("Security Controls")?></th>
    					<th class="t_title" width="50"><?php echo _("Implemented")?></th>
    					<th class="t_title" width="50"><?php echo _("Comments")?></th>
    					<th class="t_title" width="50"><?php echo _("Data Sources")?></th>
    				</tr>
    				<?php
    				foreach ($data['subgroups'] as $s_title => $subgroup) 
    				{
        				$ref     = $subgroup['Ref'];
    					$tab     = explode(".", $ref);
    					$rule_id = $title . '_' . $ref;
    					$padding = (count($tab)<=3) ? "style='padding-left:10px'" : "style='padding-left:20px'";
    					?>
    					<tr>
    						<td class="nobborder wtd"></td>
    						<td class="nobborder wtd" id="plus_<?php echo $rule_id ?>">
    							<?php 
    							if ( $subgroup['SIDSS_Ref'] != "" ) 
    							{ 
    				            ?>
    								<a href="javascript:;" onclick="get_plugins('<?php echo $rule_id ?>');return false;">
        								<img src="../pixmaps/plus-small.png" border="0"/>
        				            </a>
    							<?php 
    							} 
    							?>
    						</td>
    						
    						<td class="nobborder" <?php echo $padding?>>
    						<?php 
                                $txt      = nl2br(str_replace("\"","'",$subgroup['testing_procedures']));
        						$tip_text = "<div class='tip_proc'>"._("TESTING PROCEDURES")."</div>$txt";
        				    ?>
    							
    							<strong><?php echo $ref ?></strong>
    							<span class="scriptinfo" title="<?php echo $tip_text ?>">
        							<?php echo $subgroup['Security_controls']?>
    							</span>
    
    						</td>
    						
    						<td class="nobborder" style="text-align:center">
    							<?php 
        				        $url = "pci-dss.php?table=".$subgroup['table']."&ref=$ref&toggle=$title&pci_version=$version";
    							if ( $subgroup['operational'] )
    							{
    								?>
    								<a href="<?php echo $url ?>">
    									<img src='../pixmaps/tick.png' title='<?php echo _("Click to set false")?>'/>
    								</a>
    								<?php
    							}
    							else
    							{
    								?>
    								<a href="<?php echo $url ?>">
    								    <img src='../pixmaps/cross.png' title='<?php echo _("Click to set true")?>'/>
    								</a>
    								<?php
    							}
    							?>
    						</td>
    						
    						<?php $style = ( $subgroup['comments'] == "" ) ? "style='text-align:center'" : "style='text-align:center'"; ?>
    						
    						<td class="nobborder" <?php echo $style?>>
    							<?php 
    							if ( $subgroup['comments'] != "" ) 
    							{
    								echo $subgroup['comments'];
    								?>
    								<a href="field_edit.php?ref=<?php echo $ref ?>&table=<?php echo $subgroup['table'] ?>&field=comments&compliance=PCI&pci_version=<?php echo $version?>" class="greybox_small" title="<?php echo _("New comment") ?>">
    									<img align="absmiddle" src="../pixmaps/tables/table_edit.png" title="<?php echo _("Edit")?>"/>
    								</a>
    								<?php
    							}
    							else
    							{
    								?>
    								<a href="field_edit.php?ref=<?php echo $ref ?>&table=<?php echo $subgroup['table'] ?>&field=comments&compliance=PCI&pci_version=<?php echo $version?>" class="greybox_small" title="<?php echo _("New comment") ?>">
    									<img align="absmiddle" src="../pixmaps/tables/table_row_insert.png" title="<?php echo _("Insert")?>"/>
    								</a>
    								<?php
    							}
    							?>
    						</td>
    						
    						<td class="nobborder" style="text-align:center">
    							<?php
    							if ( $subgroup['SIDSS_Ref'] != '' && preg_match("/^[\d\s\,]+$/", $subgroup['SIDSS_Ref']) )
    							{
    								$directives = Plugin_sid::get_list($conn,"WHERE plugin_id=1505 AND sid IN (".$subgroup['SIDSS_Ref'].")");
    
    								echo count($directives)." Ref. ";
    								
    								?>
    								<a href="plugins_edit.php?ref=<?php echo $rule_id ?>&compliance=PCI&pci_version=<?php echo $version?>" class="greybox" title="<?php echo _("New compliance mapping rule") ?>">
    									<img align="absmiddle" src="../pixmaps/tables/table_edit.png" title="<?php echo _("Edit")?>"/>
    								</a>
    								<?php
    							}
    							else
    							{
    								?>
    								<a href="plugins_edit.php?ref=<?php echo $rule_id ?>&compliance=PCI&pci_version=<?php echo $version?>" class="greybox" title="<?php echo _("Edit compliance mapping rule") ?>">
    									<img align="absmiddle" src="../pixmaps/tables/table_edit.png" title="<?php echo _("Edit")?>"/>
    								</a>
    								<?php
    							}
    							?>
    						</td>
    					</tr>
    					
    					<?php
    					if ($subgroup['SIDSS_Ref'] != "") 
    					{ 
    						?>
    						<tr><td class='hidden_row'></td></tr>
    						<tr>
    							<td colspan="2" class="hidden_row nobborder wtd"></td>
    							<td colspan="5" class="hidden_row nobborder" <?php echo $padding?> id="SIDS_<?php echo $rule_id ?>"></td>
    						</tr>
    						<?php						
    					} 
    				} 
    				?>
    			</table>
    		</td>
    	</tr>
    	<?php
    	$group_id++;
    } 
    ?>
    </table>
</body>

</html>

<?php
    
$db->close();

