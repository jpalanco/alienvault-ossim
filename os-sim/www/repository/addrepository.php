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

Session::logcheck("configuration-menu", "Osvdb");

$user      = $_SESSION["_user"];
$conf      = $GLOBALS["CONF"];
$link_type = (GET('linktype') != "") ? GET('linktype') : "incident";
$id        = GET('id');
$id_link   = GET('id_link');
$type_link = GET('type_link');
$linkdoc   = GET('linkdoc');


ossim_valid($link_type, 		OSS_ALPHA, OSS_NULLABLE, 				'illegal:' . _("link type"));
ossim_valid($id, 				OSS_DIGIT, OSS_NULLABLE, 				'illegal:' . _("id"));
ossim_valid($id_link, 			OSS_DIGIT, 								'illegal:' . _("id_link"));
ossim_valid($type_link, 		OSS_ALPHA, 								'illegal:' . _("type_link"));
ossim_valid($linkdoc, 			OSS_DIGIT, OSS_NULLABLE, 				'illegal:' . _("Linkdoc"));
ossim_valid(GET('insert'), 		OSS_DIGIT, OSS_NULLABLE, 				'illegal:' . _("Insert"));
ossim_valid(GET('newlinkname'),	OSS_ALPHA, OSS_PUNC, '#', OSS_NULLABLE,	'illegal:' . _("Newlinkname"));
ossim_valid(GET('key_delete'), 	OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 		'illegal:' . _("Key_delete"));
ossim_valid(GET('id_delete'), 	OSS_DIGIT, OSS_NULLABLE, 				'illegal:' . _("Id_delete"));


if (ossim_error()) 
{
    die(ossim_error());
}

// DB connect
$db   = new ossim_db();
$conn = $db->connect();


// New link on relationships
if ($linkdoc != "" && GET('insert') == "1")
{
    $name_link = ( $id !="" ) ? $conn->GetOne("SELECT title from incident as total WHERE id=$id") : "";
    Repository::insert_relationships($conn, $linkdoc , $type_link, $id_link);
}

// Delete link on relationships
if (GET('key_delete') != "" && GET('id_delete') != "") 
{
    Repository::delete_relationships($conn, GET('id_delete') , GET('key_delete'));
}

$rel_list = Repository::get_relationships_by_link($conn, $id_link);

$pholder  = _('Type to Start Searching the Document to Link');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
		
	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',             'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',   'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',                   'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');
    ?>
	
	<style type="text/css">
        
        #kdb_container
        {
            min-height: 250px;
        }
                
        #kdb_rel_list	
        {
            margin: 30px auto 25px auto;
            width: 90%;
            
        }
        
        #kdb_rel_link	
        {
            width: 90%;
            border: none;
            margin: 10px auto;
        }
        
        #search_results 
        {
            width: 500px;
            position: relative;
            height: 1px;
            color: #D8000C;
            margin: auto;            
        }
        
        #search_results div
        {
            position: absolute;
            top: 1px;
            left: 0px;
            right: 0px;            
        }
        
        #search_results div span
        {
            text-align: center;
        }    
        
        #document_search_box
        {
            width: 100%;
            padding: 3px;
        }
        
	</style>

	<script type="text/javascript">
	
		$(document).ready(function() 
		{
            var acOptions = {
            minChars: 2,
            mustMatch: true,
            max: 100,
            dataType: 'json', // this parameter is currently unused
            extraParams: {
                format: 'json' // pass the required context to the Zend Controller
            },
            scroll: true,
            scrollHeight: 150,
            parse: function(data) {
                var parsed = [];
                                                
                if (typeof(data) != 'undefined' && data != null && typeof(data.documents) != 'undefined')
                {                         
                    data = data.documents;
             
                    for (var i = 0; i < data.length; i++) {
                        parsed[parsed.length] = {
                            data: data[i],
                            value: data[i].documenTitle,
                            result: data[i].documenTitle
                        };
                    }
                }
         
                return parsed;
            },
            formatItem: function(item) {
                return item.documenTitle;
            }
            };

            $('#document_search_box').autocomplete('ajax_autocomplete.php', acOptions).result(function(e, data) {             
                                                        
                if (typeof(data) != 'undefined' && data != null)
                {
                    $('#document_id').val(data.documentId);
                    
                    $('#search_results div').empty();                            
                }
                else
                {
                    $("#document_id").val('');                                                  
                }            
            });
            
            
            $('#document_search_box').placeholder();
        });
        
        
		function send_form()
		{
			if($("#document_id").val() != '')
			{
    			document.flinks.insert.value = '1';
    			document.flinks.submit();
			}
			else
			{
    			var doc_not_found = '<span class="small"><?php echo _('Error! Document not found.  Please, you must link an existing document')?></span>';
                                    
                $('#search_results div').html(doc_not_found);      
			}			
		}
		
		//parent.document.getElementById('rep_iframe').height='<?php echo (120 + (count($rel_list) * 25)) ?>';
		
	</script>

</head>

<body>
	
	<div id="kdb_container">
    	<form name="flinks" method="GET">
    		<input type="hidden" name="id_link" value="<?php echo $id_link?>"/>
    		<input type="hidden" name="id" value="<?php echo $id?>"/>
    		<input type="hidden" name="type_link" value="<?php echo $type_link?>"/>
    		<input type="hidden" name="insert" value="0"/>
    
    		<table id='kdb_rel_link'>
    			<tr>
    				<th colspan='2'><?php echo _("Document")?></th>
    			</tr>
    			<tr>
    				<td class='nobborder left' style="width: 90%">
                        <input type="text" id="document_search_box" placeholder="<?php echo $pholder ?>" value=""/>
                        <input type="hidden" name="linkdoc" id="document_id" value=""/> 
    				</td>
    				<td class='nobborder right'>
    					<input type="button" class="small" value="Link" onclick="send_form();"/>
    		        </td>		        
    			</tr>
    			<tr>
    			     <td colspan="2">
        			     <div id='search_results'><div></div></div>    			     
    			     </td>
    			</tr>
    		</table>
    	</form>
    	
    	<?php
    	if (count($rel_list) > 0) 
    	{ 
    	?>
    		<table id='kdb_rel_list' class="table_list">
    			<tr>
    				<th><?php echo _("Name")?></th>
    				<th><?php echo _("Action")?></th>
    			</tr>
    			<?php
    			foreach($rel_list as $rel)
    			{
    				?>
    				<tr>
    					<td class="nobborder"><?php echo $rel['title'] ?></td>
    					<td class="nobborder" style='text-align:center;'>
    					   <a href="addrepository.php?id=<?php echo $id ?>&id_link=<?php echo $id_link ?>&key_delete=<?php echo $id_link ?>&id_delete=<?php echo $rel['id_document'] ?>&type_link=<?php echo $type_link ?>">
    					       <img src="../repository/images/del.gif" border="0"/>
    					   </a>
					   </td>
    				</tr>
    				<?php
    			} ?>
    		</table>
    	<?php
    	} 
    	?>
	</div>
</body>
</html>

<?php 
$db->close();
