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

$db = new ossim_db();
$conn = $db->connect();

$attr   = GET('attr');
$table  = GET('table');
$ref    = GET('ref');
$toggle = GET('toggle');

ossim_valid($attr, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("Attribute value"));
ossim_valid($table, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Table value"));
ossim_valid($ref, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,    'illegal:' . _("Ref value"));
ossim_valid($toggle, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("toggle"));

if (ossim_error()) {
	die(ossim_error());
}

if ($attr != "" && $table != "" && $ref != "") {
	Compliance_iso27001::update_attr($conn,$table,$ref,$attr);
}

$groups = Compliance_iso27001::get_groups($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> - Compliance </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript">
        var toggled = "<?php echo $toggle?>"; // Subgroup toggled variable
              		
		function toggle_group(id) {
            toggled = id;
            document.getElementById(id).style.display = "table-row";
            var button = id+"_button";
            document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"untoggle_group('"+id+"');return false;\"><img src='../pixmaps/minus-small.png' border='0'></a>";
        }
        
        function untoggle_group(id) {
            toggled = "";
            document.getElementById(id).style.display = "none";
            var button = id+"_button";
            document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"toggle_group('"+id+"');return false;\"><img src='../pixmaps/plus-small.png' border='0'></a>";
        }
		
		function hide_plugins (ref) {
            var td = "SIDS_"+ref;
            document.getElementById(td).innerHTML = "";
            plus = "plus_"+ref;
            document.getElementById(plus).innerHTML = "<a href='' onclick=\"get_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'></a>";
        }
		
		function get_plugins (ref) {
            var td = "SIDS_"+ref;
            document.getElementById(td).innerHTML = "<img src='../pixmaps/loading.gif' alt='Loading' style='width: 16px; height: 16px; vertical-align: middle;'><span style='margin-left: 3px;'><?php echo _("Loading")?> ...</span>";
            $.ajax({
                type: "GET",
                url: "plugins_response.php?ref="+ref+"&compliance=ISO27001",
                data: "",
                success: function(msg){
                    document.getElementById(td).innerHTML = msg;
                    
					plus = "plus_"+ref;
                    document.getElementById(plus).innerHTML = "<a href='' onclick=\"hide_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
                }
            });
        }
              
        // GrayBox
      	function GB_onclose() 
      	{
            document.location.href='/ossim/compliance/iso27001.php?toggle='+toggled;
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
        });
    </script>
    
    <style type='text/css'>
        #table_sg td{
            padding: 0px 5px;
        }
    </style>
    
</head>

<body>

<?php 
    //Local menu		      
    include_once '../local_menu.php';
?>

<table id="main_table">
	<?php
	
	$group_id = 0;
	
    foreach ($groups as $title=>$data) 
    { 
        $td_class = ($group_id % 2 == 0) ? 'odd' : 'even';
        
        ?>
        <tr>
        <?php 
        if ( $title != $toggle ) 
        { 
            ?>
            <td width="10" class="nobborder <?php echo $td_class?>" id="<?php echo $title?>_button"><a href="javascript:;" onclick="toggle_group('<?php echo $title?>');return false;"><img src="../pixmaps/plus-small.png" alt="toggle" border="0"></a></td>
            <?php 
        } 
        else 
        { 
            ?>
            <td width="10" class="nobborder <?php echo $td_class?>" id="<?php echo $title?>_button"><a href="javascript:;" onclick="untoggle_group('<?php echo $title?>');return false;"><img src="../pixmaps/minus-small.png" alt="toggle" border="0"></a></td>
            <?php
        } 
        ?>
            <td class="left <?php echo $td_class?>"><?php echo $title." ".preg_replace("/\<\-+|\-+\>/","",$data['title'])?></td>
        </tr>
        
        <tr id="<?php echo $title?>" <?php if ($toggle != $title) { ?>style="display:none"<?php } ?>>
            <td class="nobborder wtd"></td>
            <td class="nobborder wtd">
                <table class="w100 table_list">
                    <tr>
                        <td class="wtd ftd t_title"></td>
                        <td class="wtd std t_title"></td>
                        <th class="setd t_title"><?php echo _("Security Controls")?></th>
                        <th class="atd t_title"><?php echo _("Applies")?></th>
                        <th class="itd t_title"><?php echo _("Implemented")?></th>
                        <th class="jtd t_title"><?php echo _("Justification")?></th>
                        <th class="t_title"><?php echo _("Data Sources")?></th>
                    </tr>
                    
                    <?php 
                    foreach ($data['subgroups'] as $s_title=>$subgroup) 
                    { 
                        ?>
                        <tr>
                            <td class="wtd"></td>
                            <td class="nobborder wtd center" id="plus_<?php echo $title?>_<?php echo $subgroup['Ref']?>">
								<?php 
								if ($subgroup['SIDSS_Ref'] != "") 
								{ 
									?>
									<a href="javascript:;" onclick="get_plugins('<?php echo $title?>_<?php echo $subgroup['Ref']?>');return false;"><img src="../pixmaps/plus-small.png" border="0"></a>
									<?php 
								} 
								?>
							</td>
                            <td class="nobborder">
								<b><?php echo $subgroup['Ref']?></b> <?php echo $subgroup['Security_controls']?>
							</td>
                            
							<td class="nobborder" style="text-align:center;">
								<?php 
									if ( $subgroup['Selected'] )
									{
										?>
										<a style='color:green;' href="iso27001.php?attr=Selected&table=<?php echo $subgroup['table']?>&ref=<?php echo $subgroup['Ref']?>&toggle=<?php echo $title?>"><strong><?php echo _("Selected")?></strong></a>
										<?php
									}
									else
									{
										?>
										<a style='color:red;' href="iso27001.php?attr=Selected&table=<?php echo $subgroup['table']?>&ref=<?php echo $subgroup['Ref']?>&toggle=<?php echo $title?>"><strong><?php echo _("Excluded")?></strong></a>
										<?php
									}
								?>
							</td>
                            
							<td class="nobborder" style="text-align:center;">
								<?php 																													
								if ( $subgroup['Selected'] )
								{
									?>
									<a href='iso27001.php?attr=Implemented&table=<?php echo $subgroup['table']?>&ref=<?php echo $subgroup['Ref']?>&toggle=<?php echo $title?>'>
										<?php
										if ( $subgroup['Implemented'] )
										{
										
											?>
											<img src='../pixmaps/tick.png' border='0' alt='<?php echo _("Click to set false")?>' title='<?php echo _("Click to set false")?>'/>
											<?php
										}
										else
										{
											?>
											<img src='../pixmaps/cross.png' border='0' alt='<?php echo _("Click to set true")?>' title='<?php echo _("Click to set true")?>'/>
											<?php
										}
										?>
									</a>
									<?php
								}
								else
								{
									?>
									<img src='../pixmaps/cross.png' border='0' alt='<?php echo _('Excluded')?>' title='<?php echo _('Excluded')?>'/>
									<?php
								}
								?>
							</td>
							
							<?php $style = ( $subgroup['Justification'] != "" ) ? 'style="text-align:left;"' : 'style="text-align:center;"' ?>
							                               
							<td class="nobborder" <?php echo $style?>>
								<?php
								if ( $subgroup['Justification'] != '' )
								{
									?>
									<a href="field_edit.php?ref=<?php echo $subgroup['Ref']?>&table=<?php echo $subgroup['table']?>&field=Justification&compliance=ISO27001" class="greybox_small" title="<?php echo  _('Justification') ?>">
										<?php echo $subgroup['Justification']?>
										<img align="absmiddle" src="../pixmaps/tables/table_edit.png" border="0" alt="<?php echo _("Edit")?>" title="<?php echo _("Edit")?>"/>
									</a>
									<?php
								}
								else
								{
									?>
									<a href="field_edit.php?ref=<?php echo $subgroup['Ref']?>&table=<?php echo $subgroup['table']?>&field=Justification&compliance=ISO27001" class="greybox_small" title="<?php echo  _('Justification') ?>">
										<img align="absmiddle" src="../pixmaps/tables/table_row_insert.png" border="0" alt="<?php echo _("Insert")?>" title="<?php echo _("Insert")?>"/>
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
									<a href="plugins_edit.php?ref=<?php echo $title?>_<?php echo $subgroup['Ref']?>&compliance=ISO27001" class="greybox" title="<?php echo _('Plugin Sids') ?>">
										<img align="absmiddle" src="../pixmaps/tables/table_edit.png" border="0" alt="<?php echo _("Edit")?>" title="<?php echo _("Edit")?>">
									</a>
									<?php
								}
								else
								{
									?>
									<a href="plugins_edit.php?ref=<?php echo $title?>_<?php echo $subgroup['Ref']?>&compliance=ISO27001" class="greybox" title="<?php echo _('Plugin Sids') ?>">
										<img align="absmiddle" src="../pixmaps/tables/table_row_insert.png" border="0" alt="<?php echo _("Insert")?>" title="<?php echo _("Insert")?>"/>
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
							<tr>
								<td colspan="2" class="nobborder wtd">&nbsp;</td>
								<td colspan="6" class="nobborder left" id="SIDS_<?php echo $title?>_<?php echo $subgroup['Ref']?>"></td>
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
