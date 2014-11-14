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
       
function Tree(type){
    
	this.tree_type     = type;
	this.tree_status   = '';
    this.json_tree     = null; 
    this.current_node  = null;
    this.root_node     = null;
	this.count         = 0;
	this.layer         = '';
	Tree.instance      = null;
	
	var new_tree = _get_json_tree(type);
				
	if (new_tree.status == 'error' || new_tree == false)
	{
		this.tree_status  = ( new_tree.status == 'error' ) ? new_tree.data : labels['error_ret_info'];
	}
	else
	{
		var layer = $('div[id^="srctree_"]').attr('id');
	
		if ( typeof(layer) != 'undefined' && layer != '' )
		{
			var counter = layer.replace('srctree_', '');	
				counter++;
			
			this.count  = counter;
			this.layer  = 'srctree_'+counter;
		}
		else
		{
			this.count         = 1;
			this.layer         = 'srctree_1';
		}
		
		this.json_tree = "[" +new_tree.data + "]"; 
	}
	
	Tree.instance = this;
	    
        
    this.get_current_system_id = function(){
        return this.current_node.data.key;
    };
    
    this.get_current_profiles = function(){
        return this.current_node.data.profiles;
    };
	
	this.get_node_by_key = function (key){
		var dtree = $('#'+this.layer).dynatree("getTree");
        var node  = dtree.getNodeByKey(key);
		return ( typeof(node) == 'object' ) ? node : false;
	};
    
    
    //Create a Dynatree    
    this.load_tree = function(){
               
        $('#tree_container_bt').append('<div id="'+this.layer+'" style="width:100%;"></div>');
		        
        $('#'+this.layer).dynatree({
            minExpandLevel: 2,
            onActivate: function(dtnode) {
                
                if (dtnode.data.key != '')
                {
                    Tree.instance.current_node = $('#'+Tree.instance.layer).dynatree("getActiveNode");
										 
                    if (Tree.instance.tree_status == '')
					{                    
                        var data = {
                                    system_id: Tree.instance.current_node.data.key, 
                                    profiles:  Tree.instance.current_node.data.profiles,
                                    host:      Tree.instance.current_node.data.tooltip
                                    };
                        
                        section = new Section(data, 'home', 1);
						section.load_section('home'); 
                    }
                }
            },
            children: eval(this.json_tree),
			onLazyRead: function(dtnode){
				dtnode.appendAjax({
					url: "data/sections/main/main.php",
					data: {action: "new_branch", key:dtnode.data.key, order: $('#tree_ordenation').val(), page: dtnode.data.page},
					type: "POST"
				});
			}
        });
        
        this.root_node = $('#'+this.layer).dynatree("getRoot");
        
    };
	
	this.change_tree = function (type){
		var xhr = $.ajax({
			type: "POST",
			data: "action=get_tree&tree_type="+type,
			dataType: 'json',
			url: "data/sections/main/main.php",
			cache: false,
			beforeSend: function( xhr ) {
							
				var height = $('#tree_container_bt').outerHeight();
				$('#tree_container_bt').css('height', height);  
																	
				var config  = {
					content: labels['loading'] + " ...",
					style: 'width: 280px; top: 20%; padding: 2px 0px; left: 50%; margin-left: -140px;',
					cancel_button: false
				};	
		
				var loading_box = Message.show_loading_box('s_box', config);	
												
				$("#tree_container_bt").html(loading_box);
			},
			error: function(data){
				
				if ( new_tree == false )
				{
					//Check expired session      
					var session = new Session(data, '');            
					
					if ( session.check_session_expired() == true )
					{
						session.redirect();
						return;
					}
				}
			
			},
			success: function(data){
								
				$("#tree_container_bt").html('');
				$('#tree_container_bt').css('height', 'auto'); 
				
				Tree.instance.tree_type     = type;
				Tree.instance.tree_status   = '';
				Tree.instance.json_tree     = null; 
				Tree.instance.current_node  = null;
				Tree.instance.root_node     = null;
				Tree.instance.count         = ++Tree.instance.coun;
				Tree.instance.layer         = 'srctree_'+Tree.instance.count;
				
				
				if ( data.status == 'error' )
				{
					$('#tree_ordenation').val($('#tree_ordenation option').get(0));
					
					Tree.instance.tree_status  = data.data;
					
					var config_nt = { content: data.data, 
										  options: {
											type:'nf_error',
											cancel_button: false
										  },
										  style: 'width: 60%; margin: auto; text-align:center;'
										};
						
					var nt            = new Notification('nt_1', config_nt);
					var notification  = nt.show();
					
					var layer = Tree.instance.layer;
					
					$('#'+layer).html(notification);
															
					$('#nt_1').fadeIn(2000);
															
					setTimeout('$("#nt_1").fadeOut(4000);', 10000);
				}
				else
				{
					Tree.instance.json_tree = "[" +data.data + "]"; 
				}
				
				Tree.instance.load_tree();
			}
		}); 
        
		ajax_requests.add_request(xhr);	
	};
	
	
	/******************************************************
	*****************  Private Functions  *****************
	*******************************************************/
	
	function _get_json_tree(type)
	{
		var ret  = false;
    
		$.ajax({
				url: "data/sections/main/main.php",
				global: false,
				type: "POST",
				data: "action=get_tree&tree_type="+type,
				dataType: "json",
				async:false,
				success: function(data){
					ret = data;
				}
			}
		);
		
		return ret;
	};
	
}