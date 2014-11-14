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

function Token(){} 

Token.add_to_forms = function()
{
    	        
	$('form').each(function(index)
	{
		var f_name = (typeof($(this).attr('name')) == 'undefined' ) ? '' : $(this).attr('name');
														
		if (f_name != '')
		{
			$.ajax(
			{
				url: "/ossim/session/token.php",
				global: false,
				type: "POST",
				data: "f_name="+ f_name,
				dataType: "json",
				success: function(data)
				{	
					if (typeof(data) != 'undefined' && data != null && data.status == 'OK')
					{											
						if ( $('#token_'+f_name).length >= 1 )
						{
							$('#token_'+f_name).remove();
						}
						
						var tk_html = "<input type='hidden' id='token_"+f_name+"' name='token' class='vfield' value='"+data.data+"'/>";
						$('form[name="'+f_name+'"]').append(tk_html);
					}
				}
			});
		}
	});
};

// When deleting users, hosts, ...

Token.get_token = function(action) 
{
    var ret = $.ajax({
        url: "/ossim/session/token.php",
        global: false,
        type: "POST",
        data: "f_name="+ action,
        async: false
    }).responseText;
    
    try
    {
        var obj = jQuery.parseJSON(ret);
        
        if(typeof(obj.data) == "string") 
        {
            return obj.data;
        }
        else 
        {
            return "";
        }
    }
    catch(err)
    {
        return "";
    }

};

	