<?php
header("Content-type: text/javascript");
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
?>

function change_entity()
{
	var selected_ctx = $('#policy_entity').val();
	
	if(selected_ctx != '')
	{
		document.location.href='policy.php?ctx='+selected_ctx;
	}
	
	return false;
}

function action(com,grid,fg,fp) 
{
	var ctx_g = fp.ctxGroup;
	var items = $('.trSelected', grid);
	
	if (com=='<?php echo _("Delete selected")?>') {
		//Delete host by ajax
		if (typeof(items[0]) != 'undefined') {
			if (confirm('<?php echo  Util::js_entities(_("The following policies will be removed. This action can not be undone. Are you sure you want to continue?"))?>')) {
				var dtoken = Token.get_token("delete_policy");
				var ids='';
				for (var i=0;i<items.length;i++) {
					ids = ids + (ids!='' ? ',' : '') + items[i].id.substr(3);
				}
				$.ajax({
					type: "GET",
					url: "deletepolicy.php?confirm=yes&id="+urlencode(ids)+"&token="+dtoken,
					data: "",
					success: function(msg) {
						if(msg.match("Action not allowed")) 
						{
							notify('<?php echo _("Action not allowed") ?>', 'nf_error');
						}
						else
						{
							fg.populate();
							$('.reload').addClass('reload_red').removeClass('reload').css({paddingLeft:20});
						}
					}
				});
			}
		}
		else alert('<?php echo Util::js_entities(_("Please select a policy rule"))?>');
	}
	
	else if (com=='<?php echo _("Modify")?>') {
		if (typeof(items[0]) != 'undefined') document.location.href = 'newpolicyform.php?ctx='+ ctx_g +'&id='+urlencode(items[0].id.substr(3))
		else alert('<?php echo Util::js_entities(_("Please select a policy rule"))?>');
	}
	
	else if (com=='<?php echo _("Duplicate selected")?>') {
		if (typeof(items[0]) != 'undefined') document.location.href = 'newpolicyform.php?ctx='+ ctx_g +'&id='+urlencode(items[0].id.substr(3))+'&clone=1'
		else alert('<?php echo Util::js_entities(_("Please select a policy rule"))?>');
	}
	
	else if (com=='<?php echo _("New")?>') {
		document.location.href = 'newpolicyform.php?ctx='+ ctx_g +'&group='+fp.idGroup;
	}
	
	else if (com=='<?php echo _("Reload Policies")?>') {
		document.location.href = '../conf/reload.php?what=policies&back=<?php echo urlencode('../policy/policy.php'); ?>'
	}
	
	else if (com=='<?php echo _("<b>Enable/Disable</b> policy")?>' || com=='enabledisable') {
		//Activate/Deactivate selected items or all by default via ajax
		if (typeof(items[0]) != 'undefined') {
			if (confirm('<?php echo  Util::js_entities(_("The following policies will be Enabled/Disabled. Are you sure you want to continue?"))?>')) {
				var dtoken = Token.get_token("delete_policy");
				var ids='';
				for (var i=0;i<items.length;i++) {
					ids = ids + (ids!='' ? ',' : '') + items[i].id.substr(3);
				}
				$.ajax({
					type: "GET",
					url: "deletepolicy.php?activate=change&id="+urlencode(ids)+"&token="+dtoken,
					data: "",
					success: function(msg) {
						if(msg.match("Action not allowed")) 
						{
							notify('<?php echo _("Action not allowed") ?>', 'nf_error');
						}
						else
						{
							$('.reload',grid).addClass('reload_red').removeClass('reload').css({paddingLeft:20});
							fg.populate();
						}
					}
				});
			}
		}
		else alert('<?php echo Util::js_entities(_("Please select a policy rule"))?>');
	}
}


function save_state(p,state) 
{
	$.ajax({
		type: "POST",
		url: "../conf/layout.php",
		data: { name: 'group_layout_'+p.idGroup+'_'+p.ctxGroup, category: 'policy', layout:serialize(state) },
		success: function(msg) {}
	});
}


function toggle_group_order(p,state) {
	$.ajax({
		type: "GET",
		url: "changepolicygroup.php",
		data: { group: p.idGroup, order: state, ctx: p.ctxGroup },
		success: function(msg) {
			document.location.reload();
		}
	});
}


function swap_rows(fg) {
	
	$.ajax({
		type: "GET",
		url: "changepolicy.php",
		data: { src: fg.drow, dst: fg.hrow },
		success: function(msg) {
			$('.reload').addClass('reload_red').removeClass('reload').css({paddingLeft:20});
			fg.populate();
		}
	});
}


function swap_rows_grid(s,d) {
	
	$.ajax({
		type: "GET",
		url: "changepolicy.php",
		data: { src: s, dst: d },
		success: function(msg) {
			$('.reload').addClass('reload_red').removeClass('reload').css({paddingLeft:20});
			refresh_all();
		}
	});
}
		
function menu_action(com,id,fg,fp) 
{
	var ctx_g = fp.ctxGroup;
	
	if (com=='enabledisable') {
		if (confirm('<?php echo  Util::js_entities(_("This policy will be Enabled/Disabled. Are you sure you want to continue?"))?>')) {
			var dtoken = Token.get_token("delete_policy");
			//Activate/Deactivate by ajax
			$.ajax({
				type: "GET",
				url: "deletepolicy.php?activate=change&id="+urlencode(id)+"&token="+dtoken,
				data: "",
				success: function(msg) {
					if(msg.match("Action not allowed")) 
					{
						notify('<?php echo _("Action not allowed") ?>', 'nf_error');
					}
					else
					{
						$('.reload',fg.gDiv).removeClass('reload').addClass('reload_red').css({paddingLeft:20});
						fg.populate();
					}
				}
			});
		}
		
	} 
	
	else if (com=='insertafter') {
		// new policy after selected
		document.location.href = "newpolicyform.php?ctx="+ ctx_g +"&insertafter="+urlencode(id)+'&group='+fp.userdata1;
	} 
	
	else if (com=='insertbefore') {
		// new policy before selected
		document.location.href = "newpolicyform.php?ctx="+ ctx_g +"&insertbefore="+urlencode(id)+'&group='+fp.userdata1;
	} 
	
	else if (com=='modify') {
		// modify selected policy
		document.location.href = 'newpolicyform.php?ctx='+ ctx_g +'&id='+urlencode(id);
	} 
	
	else if (com=='delete') {
		var dtoken = Token.get_token("delete_policy");
		// delete selected policy
		if (confirm('<?php echo  Util::js_entities(_("This policy will be removed. This action can not be undone. Are you sure you want to continue?"))?>')) { 
			$.ajax({
				type: "GET",
				url: "deletepolicy.php?confirm=yes&id="+urlencode(id)+"&token="+dtoken,
				data: "",
				success: function(msg) {
					if(msg.match("Action not allowed")) 
					{
						notify('<?php echo _("Action not allowed") ?>', 'nf_error');
					}
					else
					{
						fg.populate();
						$('.reload').addClass('reload_red').removeClass('reload').css({paddingLeft:20});
					}
				}
			});
		}
	} 
	
	else if (com=='duplicate') 
	{
		// duplicate selected policy
		document.location.href = 'newpolicyform.php?ctx='+ ctx_g +'&id='+urlencode(id)+'&clone=1';
	} 
	
	else if (com=='viewgroup') 
	{
		// view groups
		var href = 'plugingroups.php?id='+urlencode(id)+'&collection=1#'+urlencode(id);
		GB_show('<?php echo _("DS Groups")?>',href,450,'90%');
		//document.location.href = 'plugingroups.php?id='+urlencode(id)+'&collection=1';
	}
	
}

function reorder_policies() {

	if (confirm('<?php echo  Util::js_entities(_("Policies are going to be reordered. This action can not be undone. Are you sure you want to continue?"))?>')) 
	{ 
		
		document.location.href='policy.php?reorder=1';
	}
}