<?php
/**
 * index.php
 * 
 * File index.php is used to:
 * - Show the Asset Details page for a Host or a Network received by GET('id') parameter
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

// Change flow mode for background ajax tasks
ob_implicit_flush();

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

$max_hosts = 10;

$id           = GET('id');
$load_section = GET('load_section');
$load_tab     = GET('load_tab');

ossim_valid($id,           OSS_HEX,                 'illegal:' . _('Asset ID'));
ossim_valid($load_section, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Section'));
ossim_valid($load_tab,     OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Tab'));

if (ossim_error()) 
{
	die(ossim_error());
}

// Database Object
$db   = new ossim_db();
$conn = $db->connect();

// ********** Select mode of details: Asset, Network, Asset Group ***********

//This var is used for groups, when we dont have visibility for all the elements within the group
$button_disabled       = '';
$img_disabled          = '';
$flag_restricted_group = FALSE;
// Host
if (Asset_host::is_in_db($conn, $id))
{	
	if (Asset_host::is_allowed($conn, $id))
	{
    	$asset_object = Asset_host::get_object($conn, $id);
    	$asset_type   = 'host';
    	$breadcrumb   = array(
    	    'section' => _('Assets'),
    	    'current' => _('Asset Details')
    	);
	}
	else
	{
    	Av_exception::throw_error(Av_exception::USER_ERROR, _('Host not allowed'));
	}
	
	$can_i_modify_ips = Asset_host::can_i_modify_ips($conn, $id);
	
	if ($can_i_modify_ips == FALSE)
	{
    	$img_disabled = 'img_disabled';
	}
		
}
// Network
elseif (Asset_net::is_in_db($conn, $id))
{
	if (Asset_net::is_allowed($conn, $id))
	{
    	$asset_object = Asset_net::get_object($conn, $id);
    	$asset_type   = 'net';
    	$breadcrumb   = array(
    	    'section' => _('Networks'),
    	    'current' => _('Network Details')
    	);
    }
    else
	{
    	Av_exception::throw_error(Av_exception::USER_ERROR, _('Net not allowed'));
	}
	
	$can_i_modify_ips = Asset_net::can_i_modify_ips($conn, $id);
	
	if ($can_i_modify_ips == FALSE)
	{
    	$img_disabled = 'img_disabled';
	}
}
// Asset Group
elseif (Asset_group::is_in_db($conn, $id))
{
    $asset_object = Asset_group::get_object($conn, $id);
    $asset_type   = 'group';
    $breadcrumb   = array(
        'section' => _('Groups'),
        'current' => _('Group Details')
    );    
    
    try
    {
        $asset_object->can_i_edit($conn);
    }
    catch(Exception $e)
    {
        $img_disabled          = 'img_disabled';
        $button_disabled       = 'disabled';
        $flag_restricted_group = TRUE;
    }
    
}
// Not found
else
{
    Av_exception::throw_error(Av_exception::USER_ERROR, _('Asset not found in the system'));
}

// Save session object
$_SESSION['asset_detail'][$id] = serialize($asset_object);

// Get default system ip
$system_list = Av_center::get_avc_list($conn);
$system_ip   = @$system_list['data'][strtolower(Util::get_system_uuid())]['admin_ip'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('Asset Details')?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.editinplace.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>
	<script type="text/javascript" src="/ossim/js/messages.php"></script>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
	<script type="text/javascript" src="https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>	
	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
	<script type="text/javascript" src="/ossim/js/av_scan.js.php"></script>
	<script type="text/javascript" src="/ossim/js/av_map.js.php"></script>
	<script type="text/javascript">
	   <?php require_once 'js/asset_details.js.php';?>
	</script>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/top.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/assets/asset_details.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	
	<style type="text/css">

		/* editinplace plugin specific CSS */
		.inplace_field
		{
			width: 98%;
			height: 65px;
			background-color:#F2F2F2;
		}
		
		/* datatable plugin specific CSS */
		.loading_panel, .dataTables_processing 
		{
		    border-radius: 5px;
		   -moz-border-radius: 5px;
		   -webkit-border-radius: 5px;
		   -khtml-border-radius: 5px;
		    border: solid 5px #CCCCCC !important;
			position: absolute; 
			width: 30%; 
			height: auto; 
			margin: auto; 
			top:45%; 
			left: 0;
			right: 0;
			z-index: 200001; 
			background:#F2F2F2 !important; 
			font-size: 11px; 
			color: #222222 !important;
			text-align:center;
			padding: 5px 10px;
		}
		
		.input_search 
		{
			color: #222 !important;
		}
		
		/* Alarms console specific CSS */
		.img_intent
		{
		    height: 24px;
		}
		
		.nowrap
		{
			white-space: nowrap;
		}
		
	</style>
	
	
    
	<script type='text/javascript'>
 
    	/**
    	* DOCUMENT READY
    	*/
    	$(document).ready(function()
        {                
            if (typeof top.av_menu.set_bookmark_params == 'function')
            {
                top.av_menu.set_bookmark_params("<?php echo $id ?>");
            }        
    
            // General info background load
            load_info();
        	
    		// Snapshot background load
    		load_snapshot();
    
    		// HIDS led background load
    		load_hids();
    
    		//Jquery TABS
    		$("#tabs-list").tabs(
    		{
    			selected: 0,
    			select: function(event, ui)
    			{
        			var section = $(ui.tab).data('id');

        			if (section == 'general')
        			{
            			load_section_content('general', 'software');
        			}
        			else if (section == 'activity')
        			{
            			load_section_content('activity', 'alarms')
        			}
        			else if (section == 'location')
        			{
            			load_section_content('location', '')
        			}
        			else if (section == 'assets')
        			{
            			load_section_content('assets', '')
        			}
        			else if (section == 'history')
        			{
            			load_section_content('history', '')
        			}
        			else if (section == 'notes')
        			{
            			load_section_content('notes', '');
        			}
    			}
    		});	
		
    		// Menu tabs
    		load_section('<?php echo ($load_section != '') ? $load_section : "general" ?>', '<?php echo ($load_tab != '') ? $load_tab : "software" ?>');
    		
    		// Notes
    		init_notes();
    
    	    // Toggle Details info box
    	    $('.view_details').click(function(e)
    	    {
        	    toggle_more_info();
    	    });
    
        	    // Adding click event on export host icon
            $('#export_hosts').on('click', function()
            {
                export_hosts(); 
            });
    
            // Adding click event on delete icon
            $('#delete_asset').on('click', function()
            {
                if ($(this).hasClass('img_disabled'))
                {
                    return false;
                }
                
                delete_asset('<?php echo $asset_type ?>', '<?php echo $id ?>'); 
            });
    
            // Tooltips
            $('.tipinfo').tipTip({
    			content: function (e) {
    				return $(this).attr('txt')
    			}
    	    });
    		
    		// Greybox
    		$(".detail_edit_button").click(function(e)
    		{
    			e.stopPropagation();
                
                if ($(this).prop('disabled'))
        		{
            		return false;
        		}
        		
                var url = '';
                var t   = this.value;
                
                <?php
                if ($asset_type == 'host')
                {
                    ?>
                    url = '<?php echo "/ossim/host/host_form.php?id=$id"?>';
                    <?php
                }
                elseif ($asset_type == 'net')
                {
                    ?>
                    url = '<?php echo "/ossim/net/net_form.php?id=$id"?>';
                    <?php
                }
                elseif ($asset_type == 'group')
                {
                    ?>
                    url = '<?php echo "/ossim/group/group_form.php?id=$id"?>';
                    <?php
                }    
                ?>            
            
                if (url != '')
                {
                    if (!parent.is_lightbox_loaded(window.name))
                    {             
                        GB_show(t, url,'80%','720');
                    }
                    else
                    {
                        document.location.href = url;
                    }
                }            
            
                return false;
    		});
        		
        		
    		$(".greybox_availability").click(function(e){
    		    e.stopPropagation();
    			var t   = this.value;
    			var url = "../host/host_services.php?id=<?php echo $id ?>";
    			GB_show(t, url,'700','700');
    			return false;
    		});
		
		
    		$(".greybox_inventory").click(function(e){
    		    e.stopPropagation();
    			var t   = this.value;
    			var url = "../host/host_properties.php?id=<?php echo $id ?>";
    			GB_show(t, url,'700','700');
    			return false;
    		});
    		
    		
    		$(".greybox_plugins").click(function(e){
    		    e.stopPropagation();
    			var t   = this.value;
    			var url = "enable_plugin.php?asset_id=<?php echo $id ?>";
    			GB_show(t, url,'500','900');
    			return false;
    		});

		
    		$(".greybox_assets").off('click');
    		$(document).on("click", ".greybox_assets", function(e)
    		{
        		e.stopPropagation();
        		
        		if ($(this).prop('disabled'))
        		{
            		return false;
        		}
    		    
    			var t   = this.value;
    			var url = "add_to_group.php?group_id=<?php echo $id ?>";
    			
    			GB_show(t, url,'700','700');
    			
    			return false;
    		});
    
    		// Notes Save
    		$(".notes_save_button").click(function(e){
    			change_note(0, $('#note_txt').val());
    		});
    		
    		<?php 
    		if ($flag_restricted_group)
    		{
        	   $msg = _('You cannot see all the members of the group and so you cannot modify the group');	
        	   
        	   echo 'show_notification("messages_box", "'. $msg .'", "nf_info", 10000, true);';
    		}
    		?>    
		
    		// Suggestions
    		$('.suggestion_message').on('click',function()
    		{
    		    var sid  = $(this).data('id');
        		var that = $(this);
        		$('#s_'+sid).toggle();
        		if ($('.carrot', $(this)).hasClass('green'))
        		{
            		$('.carrot', $(this)).removeClass('green').addClass('gray');
                    // Set message as viewed
                    if (that.hasClass('bold'))
                    {
                        $.ajax(
                        {
                            type: "GET",
                            data: { id: sid+'_<?php echo Util::uuid_format($id) ?>' },
                            url: "set_viewed.php",
                            cache: false,
                            success: function(data)
                            {
                                that.removeClass('bold');
                            }
                        });        		
                    }
        		}
        		else
        		{
            		$('.carrot', $(this)).removeClass('gray').addClass('green');
        		}
    		});
    		
    		$(".suggestion_action a[href*='enable_plugin']").on('click',function()
    		{
                GB_show("<?php echo _("Enable Plugin for Asset") ?>",this.href, 600,'80%');
                return false;		
    		});		
        });
    
	</script>	
</head>

<body>
<?php require '../host_report_menu.php';?>

<!-- Path -->
<div class='breadcrumb_back top_margin'>
    <div class='breadcrumb_item'>
        <a href='javascript:;' onclick='go_back();'><?php echo $breadcrumb['section'] ?></a>
    </div>
    
    <div class='breadcrumb_separator'>
        <img src='/ossim/pixmaps/xbreadcrumbs/separator.gif' />
    </div>
    
    <div class='breadcrumb_item last'>
        <?php echo $breadcrumb['current'] ?>
    </div>
    
    <div style='clear:both;'>&nbsp;</div>
</div>

<div class='detail_container'>

<!-- Header Title -->
<div>
    
    <?php
    // Exception for groups (icons are only for hosts and nets)
    if ($asset_type != 'group')
    {
        ?>
        <div id='info_icon' class='detail_header_icon'>
            
        </div>
        <?php
    }
    ?>
    
    <div class='detail_header_left'>
        
        <div id='info_title' class='detail_title_large'>
            
        </div>
        	
        <div id='info_subtitle' class='detail_subtitle'>
            
        </div>
	</div>
	
	<div id='messages_box'></div>
	
	<div class='detail_header_right'>
	    
	    <?php
	    if ($asset_type != 'host')
	    {
    	    ?>
    	    <div class='detail_delete'>
    	       <img class='tipinfo' txt='<?php echo _('Export assets to CSV') ?>' id='export_hosts' src='/ossim/pixmaps/forensic_download.png'/>
    	   </div>
    	    <?php
	    }
	    ?>
	    
	    <div class='detail_delete'>
	       <img class='tipinfo  <?php echo $img_disabled ?>' txt='<?php echo _('Delete') ?>' id='delete_asset' src='/ossim/pixmaps/delete.png'/>
	   </div>
	    
	</div>
	
	<div class='detail_clear'></div>
</div>

<!-- Details Info -->

<div class='detail_more sec_title top_margin view_details'>+ <?php echo _('More Details') ?></div>

<div id='details_info'>

<?php
// Host (3 columns layout)
if ($asset_type == 'host')
{
    ?>
    <div class='detail_3column'>
        <div>
            <div class='detail_title'>
                <?php echo _('Networks')?>
            </div>
            
            <div id='info_networks' class='detail_content'>
                
            </div>
        </div>
        
        <div>
            <div class='detail_title'>
                <?php echo _('Operating System')?>
            </div>
            
            <div id='info_os' class='detail_content'>
                
            </div>
        </div>
        
        <div>
            <div class='detail_title'>
                <?php echo _('Asset Value')?>
            </div>
            
            <div class='detail_content'>
                <?php 
                for ($i = 0; $i <= 5; $i++)
                {
                    ?>
                    <span class='info_asset_value' id='info_asset_value_<?php echo $i ?>'><?php echo $i ?></span>
    			    <?php
                } 
                ?>
            </div>
        </div>
        
    </div>
    <div class='detail_3column'>
        
        <div>
            <div class='detail_title'>
                <?php echo _('Sensors')?>
            </div>
            
            <div id='info_sensors' class='detail_content'>
                
            </div>
        </div>
        
        <div>
            <div class='detail_title'>
                <?php echo _('Asset Type')?>
            </div>
            
            <div id='info_asset_type' class='detail_content'>
                
            </div>
        </div>
        
    </div>
    
    <div class='detail_3column'>
        <div class='detail_title'>
            <?php echo _('Description')?>
        </div>
        
        <div id='info_description' class='detail_content'>
            
        </div>
    </div>
    <?php
}
// Network and Asset Group (2 columns layout)
else
{
    ?>
    <div class='detail_2column'>
        
        <div>
            <div class='detail_title'>
                <?php echo _('Owner')?>
            </div>
            
            <div id='info_owner' class='detail_content'>
            
            </div>
        </div>
        
        <?php
        if ($asset_type == 'net')
        {
            ?>
            <div>
                <div class='detail_title'>
                    <?php echo _('CIDR')?>
                </div>
                
                <div id='info_cidr' class='detail_content'>
                    
                </div>
            </div>
        
            <div>
                <div class='detail_title'>
                    <?php echo _('Sensors')?>
                </div>
                
                <div id='info_sensors' class='detail_content'>
                    
                </div>
            </div>
            <?php
        }
        ?>        
    </div>
    
    <div class='detail_2column'>
        <div class='detail_title'>
            <?php echo _('Description')?>
        </div>
        
        <div id='info_description' class='detail_content'>
            
        </div>
    </div>
    <?php
}
?>

    <div class='detail_edit_column'>
        <input type="button" <?php echo $button_disabled ?> class="greybox detail_edit_button" value="<?php echo _('Edit')?>"/>
    </div>

    <div class='detail_clear'></div>
    
    <br/>
    
    <div>
        <hr class='detail_separator'></hr>
    </div>
    
</div>

<!-- SNAPSHOT -->
<div>
    
    <div class='detail_snap_title'><b><?php echo _('Snapshot')?></b></div>

    <?php
	// Number of hosts
	if ($asset_type == 'net' || $asset_type == 'group')
	{
	   ?>
    	<div class='snap_column_left'>
    		<div class="snap_box" id="snap_hosts" onclick="load_section('assets')">
    			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
    		</div>
    		<div class='snap_box_title'>
    	        <?php echo _("Assets")?>
    	    </div>
    	</div>
    	<?php 
    } 
    ?>
	
    <div class='snap_column<?php if ($asset_type == 'host') { ?>_left<?php }?>'>
		<div class="snap_box" id="snap_software" onclick="load_section('general', 'software')">
			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
		</div>
		<div class='snap_box_title'>
	        <?php echo _('Software<br/>Packages')?>
	    </div>
	</div>
	
	<?php
	// Number of users
    ?>
    <div class='snap_column'>
		<div class="snap_box" id="snap_users" onclick="load_section('general', 'users')">
			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
		</div>
		<div class='snap_box_title'>
	        <?php echo _('Users')?>
	    </div>
	</div>
	
	<?php
	// Vulns
	?>
    <div class='snap_column'>
		<div class="snap_box" id="snap_vulns">
			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
		</div>
		<div class='snap_box_title'>
	        <?php echo _('Vulnerabilities')?>
	    </div>
	</div>
	
	<?php
	// Alarms
	?>
    <div class='snap_column'>
		<div class="snap_box" id="snap_alarms" onclick="load_section('activity', 'alarms')">
			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
		</div>
		<div class='snap_box_title'>
	        <?php echo _('Alarms') ?>
	    </div>
	</div>
	
	<?php
    // Number of siem events
	?>
    <div class='snap_column'>
		<div class="snap_box" id="snap_events" onclick="load_section('activity', 'events')">
			<img src="/ossim/pixmaps/loading.gif" class='detail_loading'/>
		</div>
		<div class='snap_box_title'>
	        <?php echo _('Events') ?>
	    </div>
	</div>
	
	<div class='detail_clear'></div>
</div>

<!-- TABS -->
<br/>
<br/>

<div id='tabs-list'>
	<ul>
        <li>
            <a data-id='general' href="#tab_content">
                <?php echo _('General') ?>
            </a>
        </li>
        
        <li>
            <a data-id='activity' href="#tab_content">
                <?php echo _('Activity') ?>
            </a>
        </li>
                
        
        <?php
        if ($asset_type == "host")
        {
            ?>
            <li>
                <a data-id='location' href="#tab_content">
                    <?php echo _('Location') ?>
                </a>
            </li>    		
            <?php
        }
	    else
	    {
	        ?>
	       <li>
                <a data-id='assets' href="#tab_content">
                    <?php echo _('Assets') ?>
                </a>
            </li> 
            <?php
	    }

        if ($asset_type == 'group')
        {
            ?>
            <li>
                <a data-id='history' href="#tab_content">
                    <?php echo _('History') ?>
                </a>
            </li> 
            <?php
        }
        ?>
        
        <li>
            <a data-id='notes' href="#tab_content">
                <?php echo _('Notes') ?>
            </a>
        </li>

	</ul>
	
	<div id='tab_content' style='display:none'></div>
	
</div>


<!-- SECTION MODULES (including '/modules/') -->
<div id="section_general" class="section"><?php require 'modules/general.php'?></div>

<div id="section_activity" class="section" style="display:none"><?php require 'modules/activity.php' ?></div>

    <?php
    if ($asset_type == 'host')
    {
        ?>
        <div id="section_location" class="section" style="display:none"><?php require 'modules/location.php' ?></div>
        <?php
    }
    else
    {
        ?>
        <div id="section_assets" class="section" style="display:none"><?php require 'modules/assets.php' ?></div>
        <?php
    }
    ?>

    <?php
    if ($asset_type == 'group')
    {
        ?>
        <div id="section_history" class="section" style="display:none"><?php include 'modules/history.php' ?></div>
        <?php
    }
    ?>

<div id="section_notes" class="section" style="display:none"><?php include 'modules/notes.php'?></div>

</div>

<div class='snap_column_leds'>
        
    <!-- Environment -->
    <div class='environment_header sec_title'><?php echo _('Environment Status') ?></div>
    
    <?php
    $led_classes = array('led_red', 'led_green', 'led_yellow');    
    ?>    
    
    <div class='environment_content'>    
        <div>
            <table class='transparent'>
                <tr>
                    <td>
                        <div id="hids_led" class=''><img src="../pixmaps/loading3.gif" width='9'/></div>
                    </td>
                    <td>
                        <a href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/ossec/status.php', 'environment', 'detection', 'hids') ?>">HIDS</a>
                    </td>
                </tr>
            </table>
        </div>
        	
        <div>
            <table class='transparent'>
                <tr>
                    <td>
                        <?php
                        $is_autodetected = intval($asset_object->is_autodetected($conn));
                        
                        $autodetected_class = 'led_gray';                                        
                        
                        if (array_key_exists($is_autodetected, $led_classes))
                        {
                            $autodetected_class = $led_classes[$is_autodetected];
                        }
                        ?> 
                        
                        <div class='detail_led <?php echo $autodetected_class?>'></div>
                    </td>
                    <td>
                        <a href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/av_inventory/index.php', 'configuration', 'deployment', 'scheduler')?>"><?php echo _('Automatic Asset Discovery')?></a>
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <table class='transparent'>
                <tr>
                    <td>
                        <div id='info_nagios' class='detail_led'></div>
                    </td>
                    <td>
                        <a href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/nagios/index.php', 'environment', 'availability', 'monitoring') ?>"><?php echo _('Availability Monitoring')?></a>
                    </td>
                </tr>
            </table>
        </div>
    </div>		
    
    
    <!-- Suggestions -->
    <div class='suggestions_header sec_title'><?php echo _('Suggestions') ?></div>
    
    <div class='suggestions_content'>
    <?php
        $message = _("Currently no suggestions");
        try
        {
            $status = new System_status();

            $filters = array(
                'component_id' => Util::uuid_format($id),
                'level'        => 'info,warning,error',
                'order_by'     => $order,
                'order_desc'   => 'desc'
            );

            list($message_list, $total) = $status->get_status_messages($filters);
        }
        catch(Exception $e)
        {
            $total        = 0;
            $message_list = array();
            $message      = $e->getMessage();
        }

        if (!$total)
        {
            ?>
            <div class="no_suggestions"><?php echo $message?></div>
            <?php
        }   
        else
        {
            $i = 0;            
            foreach ($message_list as $msg)
            {
                $class = ($i++ % 2 != 0) ? "odd" : "even";
                $sid   = 's_'.$msg['message_id'];
                
                if ($msg["viewed"] == false)
                {
                    $class .= ' bold';
                }
                
                echo "<div data-id='".$msg['message_id']."' class='suggestion_message $class'>
                           <div style='padding-right:9px'>".$msg['description']."</div>
                           <div class='carrot green'></div>
                      </div>
                      <div id='$sid' class='hidden'>";
                
                // Get details
                try
                {
                    list($detail) = $status->get_message_detail($msg['message_id']);                                                                                                                                         
                }
                catch(Exception $e)
                {
                    $detail       = array("content" => $e->getMessage(), "actions" => array());
                }
                
                echo "<div class='suggestion_detail'>".$status->format_message($detail, $msg)."</div>\n";
                if (count($detail["actions"]))
                {
                    echo '<ul class="suggestion_action">';
                }
                
                foreach ($detail["actions"] as $action)
                {
                    echo "<li>".$status->format_action_link($action, $id)."</li>\n";
                }
                
                if (count($detail["actions"]))
                {
                    echo '</ul>';
                }
                
                echo "</div>";
            }
        } 
    ?>
    </div>
    
</div>

</body>
</html>

<?php
$db->close();

/* End of file index.php */
/* Location: ./asset_details/index.php */