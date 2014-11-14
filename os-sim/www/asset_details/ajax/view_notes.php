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

$id      = GET('id');
$type    = GET('type'); // host, host_group, net, net_group
$id_note = intval(GET('id_note'));


if ($type == 'host' || $type == 'host_group')
{
    Session::logcheck('environment-menu', 'PolicyHosts');
}
elseif ($type == 'net' || $type == 'net_group')
{
    Session::logcheck('environment-menu', 'PolicyNetworks');
}
else
{
	ossim_error(_('Invalid asset type value'));
	
	exit();
}
	
ossim_valid($id,   OSS_HEX,               'illegal:' . _('Asset ID'));
ossim_valid($type, OSS_LETTER, OSS_SCORE, 'illegal:' . _('Asset Type'));


if (ossim_error())
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();

$msg = '';

if ($id_note>0) 
{ // delete note

    if (Notes::delete($conn, $id_note))
    {
        $msg = 'deleted';
    }
    else
    {
        $msg = 'error';
    }

}
elseif (POST('action')=='new') //insert Note
{ 

    $txt = POST('txt');
    ossim_valid($txt,  OSS_TEXT, OSS_PUNC_EXT, 'illegal:' . _('Note text'));

    if (ossim_error())  
    {    
        echo ossim_error();    
    } 
    else 
    {        
        if (Notes::insert($conn, $type, gmdate('Y-m-d H:i:s'), Session::get_session_user(), $id, $txt))
        {
            $msg = 'created';
        }
        else
        {
            $msg = 'error';
        }
    }
}
elseif (POST('action') == 'update') //Update note (sometimes called by asset details)
{
    $txt     = POST('txt');
    $id_note = intval(POST('id_note'));
    
    ossim_valid($txt,  OSS_TEXT, OSS_PUNC_EXT, 'illegal:' . _('Note text'));

    if ( ossim_error() )  
    {
        die(ossim_error());
    }
    
    if (Notes::update($conn, $id_note, gmdate('Y-m-d H:i:s'), $txt))
    {
        echo json_encode(array('state' => 'OK'));
    }
    else
    {
        echo json_encode(array('state' => 'ERR'));
    }
    
    exit(); 
}
elseif (POST('action') == 'new_ajax') //New note via ajax (always called by asset details)
{
	$txt = POST('txt');
	ossim_valid($txt,  OSS_TEXT, OSS_PUNC_EXT, 'illegal:' . _('Note text'));

	if (ossim_error())
	{
	   die(ossim_error());
	}
	
	if (Notes::insert($conn, $type, gmdate('Y-m-d H:i:s'), Session::get_session_user(), $id, $txt))
	{
		echo json_encode(array('state' => 'OK'));
	}
	else
	{
		echo json_encode(array('state' => 'ERR'));
	}

	exit();
}

$notes = Notes::get_list($conn," AND type='$type' AND asset_id = UNHEX('$id') ORDER BY date DESC");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.editinplace.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	
	
	<style type='text/css'>
	
		body 
		{
			margin: 0px;
		}
		
		#container 
		{
			width:95%;
			margin:15px;
		}
		
		.note_container
		{
			width:100%;
		}
		
		.note_info
		{
			float:left;
			text-align:center;
			width: 100px;
			padding: 15px 0px 0px 0px;
		}
		
		
		#ncontainer 
		{
			width:100%;
			margin:0 auto;
			padding-top:10px;

		}
		
		.paper 
		{
			position:relative;
			width:100%;
			height:120px;
			background-color:#FFFBEA;
			border:1px solid #e3e3e3;			
		}

		div.note_txt 
		{
			margin: 20px;
			color: #807160;
			font-family:Georgia;
			font-style:italic;
			font-size:12px;
			overflow:auto;
			height:90px;
			background: url(/ossim/pixmaps/bg-sidenote-middle.png) repeat 50% 50%;
			
		}

		.tape
		{
			position:absolute;
			top:-7px; 
			left:35%;			
			width: 130px;
			height: 23px;
			background-color:#fff;
			opacity:0.6;
			border-left: 1px dashed rgba(0, 0, 0, 0.1);
			border-right: 1px dashed rgba(0, 0, 0, 0.1);
			-webkit-box-shadow: 0px 0px 1px 0px #cccccc;
			-moz-box-shadow: 0px 0px 1px 0px #cccccc;
			box-shadow: 0px 0px 1px 0px #cccccc;
			-webkit-transform: rotate(-2deg) skew(0,0) translate(0%,-5px);
			-moz-transform: rotate(-2deg) skew(0,0) translate(0%,-5px);
			-o-transform: rotate(-2deg) skew(0,0) translate(0%,-5px);
			transform: rotate(-2deg) skew(0,0) translate(0%,-5px);
		}
		
		.left-shadow
		{
			width: 90px;
			height: 90px;
			bottom:-6px; left:-13px;
			position:absolute;
			z-index:-6;
			display: inline-block;
			-webkit-box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			-moz-box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			-moz-transform: scale(1) rotate(274deg) translate(20px, 25px) skew(9deg, 0deg);
			-webkit-transform: scale(1) rotate(274deg) translate(20px, 25px) skew(9deg, 0deg);
			-o-transform: scale(1) rotate(274deg) translate(20px, 25px) skew(9deg, 0deg);
			-ms-transform: scale(1) rotate(274deg) translate(20px, 25px) skew(9deg, 0deg);
			transform: scale(1) rotate(274deg) translate(20px, 25px) skew(9deg, 0deg);
		}
		
		.right-shadow
		{
			width: 110px;
			height: 110px;
			bottom:-13px; right:-4px;
			position:absolute;
			z-index:-6;
			display: inline-block;
			-webkit-box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			-moz-box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			box-shadow: -10px -10px 10px rgba(0, 0, 0, 0.4);
			-moz-transform: scale(1) rotate(184deg) translate(20px, 25px) skew(9deg, 0deg);
			-webkit-transform: scale(1) rotate(184deg) translate(20px, 25px) skew(9deg, 0deg);
			-o-transform: scale(1) rotate(184deg) translate(20px, 25px) skew(9deg, 0deg);
			-ms-transform: scale(1) rotate(184deg) translate(20px, 25px) skew(9deg, 0deg);
			transform: scale(1) rotate(184deg) translate(20px, 25px) skew(9deg, 0deg);
		}
			
		.inplace_field
		{
			width: 98%;
			height: 65px;
			background-color:transparent;
		}
				
		.inplace_buttons
		{
			margin:0 auto;
			text-align: center;
		}
		
		button.small
		{
			padding: 1px !important;
		}
		
		textarea 
		{
			font-size:12px !important;
			width:70%;
		}  			
	</style>
	
	<script type='text/javascript'>
	
		function change_note(id, txt)
		{
			var flag_change = false;
			
            $.ajax({
				data:  {action: 'update', id_note: id, txt: txt},
				type: "POST",
                url: "view_notes.php?type=<?php echo $type?>&id=<?php echo $id?>",
				dataType: "json",
                async: false,
                success: function(msg) {
                    if (msg.state=="OK")  flag_change = true;
                    if (msg.state=="ERR") flag_change = false;
                },
				error: function(XMLHttpRequest, textStatus, errorThrown) {						
					flag_change = false;
			    }
            });
			
			return flag_change;
		}
	
		$(document).ready(function()
		{		
			$(".editInPlace").editInPlace(
			{
				callback: function(unused, enteredText, prevtxt) {
					var id  = $(this).attr('note');
					if(change_note(id, enteredText))
					{
						return enteredText;
					} 
					else
					{
						return prevtxt;
					}						
				},
				preinit: function(node) {
						var txt = $(node).html();
						txt = txt.replace(/<br>/g, "\n");
						txt = txt.replace(/\n+/g, "\n");
						$(node).html(txt);
				},
				postclose: function(node) {
						var txt = $(node).html();
						txt = txt.replace(/\n/g, '<br>');
						$(node).html(txt);
				},
				text_size: 14,
				bg_over: '#ffc',
				field_type: "textarea",
				on_blur : 'save',
				value_required: true,
				show_buttons:   true,
				save_button:   '<button class="inplace_save eipbutton small"><?php echo _('Save') ?></button>',
				cancel_button: '<button class="inplace_cancel eipbutton av_b_secondary small"><?php echo _('Cancel') ?></button>'	
			});
			
			<?php 
			
			if (!empty($msg))
			{
				switch ($msg)
				{				
					case 'created':
						$msg_txt = _('Note created successfully');
						$nf_type =  'nf_success';
					break;
					
					case 'updated':
						$msg_txt = _('Note updated successfully');
						$nf_type =  'nf_success';
					break;
					
					case 'deleted':
						$msg_txt = _('Note deleted successfully');
						$nf_type =  'nf_success';
					break;

					case 'error':
						$msg_txt = '';
					break;
				}
				
									
				if (!empty($msg_txt))
				{
					?>
												
					var config_nt = { 
						content: '<?php echo $msg_txt?>', 
						options: {
							type: '<?php echo $nf_type?>',
							cancel_button: true
						},
						style: 'display:none; width: 400px; margin: 10px auto 5px auto; text-align:center;'
					};
					
					nt = new Notification('nt_notes',config_nt);
																	
					$('body').before(nt.show());
					nt.fade_in(1000);
					setTimeout('nt.fade_out(2000);', 5000);
					<?php
				}
			}
			?>
		});
	
	</script>
	
</head>

<body id='body_scroll' style="background-color:#fafafa">

<div id='container'>
	<table width="95%" class='transparent'  align='center'>

	<?php
	foreach($notes as $note) 
	{ 
        if (Session::is_admin($conn,$note->get_user()) || $note->get_user() == AV_DEFAULT_ADMIN) 
        {
            $icon = '/ossim/pixmaps/user-business.png';
        }
        elseif ( Session::is_pro() && Acl::is_proadmin($conn, $note->get_user()) )
        {
            $icon = '/ossim/pixmaps/user-gadmin.png';
        }
        else 
        {
            $icon = '/ossim/pixmaps/user-green.png';
        }
	?>
	
		<tr>
			<td class="nobborder" width='25%' height="100px">

				<div class='note_info'>
					<table class="noborder" align="center">
    					<tr>
    					  <td><img align="absmiddle" alt="Entity admin" src="<?php echo $icon?>"/></td>
    					  <td><b> <?php echo $note->get_user() ?> </b></td>
    					</tr>
					</table>
					<br/>
					<?php echo $note->get_date() ?>
					<br/><br/>
					<?php 
					if (Session::get_session_user()==$note->get_user()) 
					{
                        ?> 
                        <input type='button' class='button av_b_secondary small' onclick="document.location.href='view_notes.php?type=<?php echo $type?>&id=<?php echo $id?>&id_note=<?php echo $note->get_id()?>'" value='<?php echo _("Delete")?>'/>                     
                        <?php 
    				} 
    				?>
				</div>				
			</td>
			<td class="nobborder" width='75%' height="100px" align='center'>
					<!-- side note -->
					
					<div id="ncontainer">
						<div class="paper">
							<div class="tape"></div>
							<div class='note_txt <?php echo (Session::get_session_user()==$note->get_user()) ? "editInPlace" : ""?>' note='<?php echo $note->get_id() ?>'>
								<?php
									echo nl2br(Util::htmlentities($note->get_txt()));
								?>
							</div>
							<div class="left-shadow"></div>
							<div class="right-shadow"></div>
						</div><!--end paper-->
					</div><!--end container-->
					<!-- side note -->				 
			</td>
		</tr>
		<tr><td colspan='2' height='15px'></td></tr>
		
		<?php
	}	
	?>
	</table>
					
	<div style='background:transparent;text-align:center;margin-top:35px;'>
        <form action="view_notes.php?type=<?php echo $type?>&id=<?php echo $id?>" method="post">
            <textarea name="txt" rows="5"><?php if ( !ossim_error() ) echo Util::htmlentities(POST('txt')); ?></textarea>
            <br/><br/>
            <input type="submit" value="<?php echo _("Add new") ?>"/>
            <input type="hidden" name="action" value="new"/>
        </form>
	</div>

</div>
									

	
<?php
$db->close();
?>
</body>
</html>
