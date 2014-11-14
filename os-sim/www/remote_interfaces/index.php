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

Session::logcheck('configuration-menu', 'PolicyServers');

if (!Session::is_pro()) 
{
	Session::unallowed_section(NULL, 'noback');
}

// load column layout
require_once '../conf/layout.php';

$category    = 'policy';
$name_layout = 'remote_interface_layout';

$layout      = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="X-UA-Compatible" content="IE=7" />
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
    <script type="text/javascript" src="../js/urlencode.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    
    <script type='text/javascript'>
        
		var notif_style = 'width:400px; text-align:center; margin:0px auto';
    	
    	function delete_ri(ri_id)
		{
    		var dtoken = Token.get_token("ri_form");
    		          
            $.ajax(
            {
				type: "POST",
				url: "ri_actions.php",
				data: {
				    "action": "delete_ri",
				    "id": ri_id,
				    "token" : dtoken
				},
				dataType: "json",
				beforeSend: function()
				{
					$("#flextable").changeStatus('<?=_('Deleting Remote Interface')?>...', false);
					
					$('#av_msg_info').remove();
					$('#av_ri_notif').empty();						
				},
				error: function(data)
				{
				    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
                    var _msg = "<?php echo _('Sorry, operation was not completed due to an unknown error')?>";
					
					show_notification('av_ri_notif', _msg, 'nf_error', 7500, true, notif_style);	
				},
				success: function(data) 
				{			    
                    var cnd_1  = (typeof(data) == 'undefined' || data == null);
                    var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');
                                        
                    if (!cnd_1 && !cnd_2)
                    {                        
                        if (data.num_interfaces == '0')
                        {                           
                            parent.av_menu.remove_ri_link();
                        }
                        
                        show_notification('av_ri_notif', data.data, 'nf_success', 7500, true, notif_style);
    				    
    				    $('.flexigrid .reload').addClass('reload_red').removeClass('reload');
    				    $("#flextable").flexReload();                    
                    }
                    else
                    {                                                                        
                        show_notification('av_ri_notif', data.data, 'nf_error', 7500, true, notif_style);
                    }		
				}				
			});
		}

		
		function linked_to(rowid) 
		{
			document.location.href = 'new_ri_form.php?id='+urlencode(rowid);
		}
		
		
		function action(com, grid) 
		{
			var items = $('.trSelected', grid);
			 
			if (com == '<?php echo _('Delete selected')?>') 
			{
				//Delete host by ajax
				if (typeof(items[0]) != 'undefined') 
				{
					var dtoken = Token.get_token("delete_ri");
					
					var msg = "<?php echo  Util::js_entities(_('Remote Interface will be deleted. Are you sure?'))?>";
					
					if (confirm(msg)) 
					{
						delete_ri(items[0].id.substr(3));
					}
				}
				else
				{
				    alert('<?php echo Util::js_entities(_('You must select a Remote Interface'))?>');
				}
			}
			else if (com == '<?php echo _('Modify')?>') 
			{
				if (typeof(items[0]) != 'undefined') 
				{
				    document.location.href = 'new_ri_form.php?id='+urlencode(items[0].id.substr(3))
				}
				else
				{
				    alert('<?php echo Util::js_entities(_('You must select a Remote Interface'))?>');
				}
			}
			else if (com == '<?php echo _('New')?>') 
			{
				document.location.href = 'new_ri_form.php'
			}
		}
		
		
		function save_layout(clayout) 
		{
			$("#flextable").changeStatus('<?=_('Saving column layout')?>...', false);
			
			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { 
				    name:"<?php echo $name_layout ?>", 
				    category:"<?php echo $category ?>", 
				    layout:serialize(clayout) 
				},
				success: function(msg) {
					$("#flextable").changeStatus(msg, true);
				}
			});
		}
		

        function menu_action(com, id, fg, fp) 
        {       
            if (com == 'delete') 
            {
                if (typeof(id) != 'undefined') 
                {
					var dtoken = Token.get_token("delete_ri");
					
					var msg = "<?php echo  Util::js_entities(_('Remote Interface will be deleted. Are you sure?'))?>";
					
					if (confirm(msg)) 
					{
						delete_ri(id);
					}
                }
                else 
                {
                    alert('<?php echo Util::js_entities(_('Remote interface unselected'))?>');
                }
            }

            if (com == 'modify') 
            {
                if (typeof(id) != 'undefined') 
                {
                    document.location.href = 'new_ri_form.php?id='+urlencode(id);
                }
                else
                { 
                    alert('<?php echo Util::js_entities(_('Remote interface unselected'))?>');
                }
            }
            
            if (com == 'new')
            {
                document.location.href = 'new_ri_form.php'
            }

        }        
        
        $(document).ready(function(){ 
            <?php 
			if (GET('msg') == 'saved') 
			{ 
				?>
				notify('<?php echo _('The Remote Interface has been saved successfully')?>', 'nf_success');
				<?php 
			}
			elseif (GET('msg') == 'deleted') 
			{ 
				?>
				notify('<?php echo _('The Remote Interface has been deleted successfully')?>', 'nf_success');
				<?php 
			} 
			elseif (GET('msg') == 'unknown_error') 
			{ 
				?>
				notify('<?php echo _('Sorry, operation was not completed due to an unknown error')?>', 'nf_error');
				<?php 
			}
			elseif (GET('msg') == 'unallowed') 
			{ 
				?>
				notify('<?php echo _('Sorry, action not allowed')?>', 'nf_error');
				<?php 
			}  			
			?>			
			
			$("#flextable").flexigrid({
        		url: 'get_ri.php',
        		dataType: 'xml',
        		colModel : [
            		<?php
                    $default = array(
                        "ID" => array(
                            _('ID'),
                            40,
                            'true',
                            'center',
                            false
                        ) ,
                        "ip" => array(
                            _('IP'),
                            120,
                            'true',
                            'center',
                            false
                        ) ,
                        "name" => array(
                            _('Name'),
                            240,
                            'true',
                            'center',
                            false
                        ) ,
                        "status" => array(
                            _('Status'),
                            760,
                            'false',
                            'left',
                            false
                        )
                    );
                    
                    list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
                    echo "$colModel\n";
                    ?>
        		],
        		buttons : [
        			{name: '<?php echo _("New")?>', bclass: 'add', onpress : action},
        			{separator: true},
        			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
        			{separator: true},
        			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action}
        			],
        		sortname: "<?php echo $sortname ?>",
        		sortorder: "<?php echo $sortorder ?>",
        		usepager: true,
        		pagestat: '<?=_('Displaying')?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("web interfaces")?>',
        		nomsg: '<?=_('No remote interfaces found in the system')?>',
        		useRp: true,
        		rp: 20,
                contextMenu: 'myMenu',
        		onContextMenuClick: menu_action,
        		showTableToggleBtn: false,
        		singleSelect: true,
        		width: get_flexi_width(),
        		height: 'auto',
        		onColumnChange: save_layout,
        		onDblClick: linked_to,
        		onEndResize: save_layout
        	}); 
        });    
        
    </script>
    
    <style type='text/css'>
    	table, th, tr, td 
		{
			background:transparent;			
			border:none;
			padding:0px; 
			margin:0px;
		}
		
		input, select 
		{
			border: 1px solid #8F8FC6;
			font-size:12px; 
			font-family:arial; 
			vertical-align:middle;
			padding:0px;
			margin:0px;
		}
		
		#av_ri_notif
		{
    	    position: absolute;
    	    width: 100%;
    	    top: 40px;
    		text-align: center;
            margin: 0 auto;
            z-index: 999;
		}	
    </style>	
</head>

<body style="margin:0px">	
	
	<?php 
        //Local menu		      
        include_once '../local_menu.php';
    ?>
    
    
    <div id='av_ri_notif'></div>
    
	<table id="flextable" style="display:none"></table>
	
	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu" style="width:150px">
        <li class="hostreport">
            <a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"> <?=_('Modify')?></a>
        </li>
        <li class="hostreport">
            <a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"> <?=_('Delete')?></a>
        </li>
        <li class="hostreport">
            <a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"> <?=_('New Remote Interface')?></a>
        </li>
    </ul>

</body>
</html>