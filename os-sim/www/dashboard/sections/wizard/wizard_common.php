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
require_once'av_init.php';
	
function load_entities($dbconn)
{
    $entities      = Acl::get_entities_to_assign($dbconn);
    $json_entities = '';

    foreach ($entities as $entity => $name)
    {
        $json_entities .= '{ "txt":"'.utf8_encode($name).'", "id": "e_'.$entity.'", "desc": "ENTITY:'.utf8_encode($name).'"},';
    }

    return $json_entities;
}


function load_users($dbconn)
{
    $users      = Session::get_users_to_assign($dbconn);
    $json_users = '';

    foreach ($users as $user)
    {
        $json_users .= '{ "txt":"'.utf8_encode($user->get_name()).' [User]", "id": "u_'.$user->get_login().'" },';
    }

    return $json_users;
}


function draw_report_list($widget_content ='')
{
	if($widget_content != '')
	{
		if (base64_decode($widget_content, TRUE))
		{
			$widget_text = base64_decode($widget_content);
		} 
		else
		{
			$widget_text = $widget_content;
		}
		
		$array_text  = explode("###",$widget_text);
		$widget_text = $array_text[0];
		
	}
	else
	{
		$widget_text = '';
	}

	echo "	
		<tr>
			<td class='nobborder' style='height:100%'>";			
				include ("dashboard_report.php");		
	echo "
			</td>
		</tr>
		
		<tr>
			<td valign='top' class='nobborder' style='text-align:center;padding:15px 0px 0px 0px;'>								
				<strong>". _("Selected:")."</strong>&nbsp;
				<span id='dassets'>" .$widget_text. "</span>
				<input type='hidden' id='widget_content' name='widget_content' value='$widget_content'/>
			</td>
		</tr>	
			
		";	
	
}

function draw_custom_url($widget_content ='')
{
	$urls     = array();
	$urls_aux = array();
	$urls_aux = file("../widgets/files/internal_urls_list.txt") or exit(_("Unable to get the URL List"));
	
	foreach ($urls_aux as $u)
	{		

		if(preg_match("/(^\*)|(^\W)/",$u))
		{
			continue;
		}
		
		$url = explode("####", trim($u));
		
		//Validation
		ossim_valid($url[1],	OSS_TEXT,	'illegal:' . _("Internal Url"));
		ossim_valid($url[0],	OSS_DIGIT,	'illegal:' . _("Internal URL Title"));

		if (ossim_error()) 
		{
    		ossim_clean_error();
			continue;
		}
		//End of validation
		
		$urls[] = $url;
	}

	echo "
		<tr>
			<td class='nobborder'>
			<div style='width:75%;margin:0 auto;padding-top:20px;'>
				<table width='100%' align='center' class='table_data'>
					<thead>
						<th>". _('Available URLs') ."</th>
					</thead>
					<tbody>";
				
	$color    = 0;	
	$selected = '';
	
	foreach ($urls as $url)
	{		
        if($url[0] == $widget_content)
        { 
            $selected = $url[1];
        }
        
		$class = ($color%2 == 0) ? "lightgray" : "blank"; 
	
		echo
			"<tr class='$class' onclick='javascript:choose_option(\"".$url[0]."\");'>								
				<td class='td_report_name'>
					<a id='sel_$color' href='javascript:void(0);'>".$url[1]."</a>
				</td>
			</tr>";		
	
		$color++;
	}
			
	echo "
				</tbody>
			</table>
		</div>
				<input type='hidden' name='widget_content' id='widget_content' value='$widget_content'/>
			</td>
		</tr>";
		
	
	echo "
		<tr>
			<td class='nobborder'><br></td>
		</tr>
		<tr>
			<td class='nobborder' style='text-align:center;'><br>
				<div style='width:60%;margin:0 auto 0 auto;text-align:center;'>
					<strong>". _('Selected Option') .": </strong> <span style='width:250px'>$selected</span>
				</div>
			</td>
		</tr>
		<tr>
			<td class='nobborder'><br></td>
		</tr>";
		

}


function draw_rss_url($widget_content ='')
{

	echo "
		<tr>
			<td class='nobborder' style='text-align:center;'><br>
				<div style='width:60%;margin:0 auto 0 auto;text-align:center;'>
					<strong>". _('RSS Url') .": </strong> <input style='width:250px' type='text' name='widget_content' id='widget_content' value='$widget_content'/>
				</div>
			</td>
		</tr>
		<tr>
			<td class='nobborder'><br></td>
		</tr>
		<tr>
			<td class='nobborder' style='text-align:center;'>". _('OR CHOOSE ONE OF THE FOLLOWING FEEDS') .":</td>
		</tr>
		<tr>
			<td class='nobborder'><br></td>
		</tr>";
							
		
	$feeds     = array();
	$feeds_aux = array();
	$feeds_aux = file("../widgets/files/rss_feed_list.txt") or exit(_("Unable to get the RSS collection"));
	
	foreach($feeds_aux as $f)
	{		

		if(preg_match("/(^\*)|(^\W)/",$f))
		{
			continue;
		}
		
		$feed = explode("####", trim($f));
		
		//Validation
		ossim_valid($feed[1],	OSS_URL_ADDRESS,	'illegal:' . _("RSS Url"));
		ossim_valid($feed[0],	OSS_TEXT,		 	'illegal:' . _("RSS Title"));

		if (ossim_error()) 
		{
    		ossim_clean_error();
			continue;
		}
		//End of validation
		
		$feeds[] = $feed;
	}

	echo "
		<tr>
			<td class='nobborder'>
				<div style='width:75%;margin:0 auto;padding-top:10px;'>
					<table width='100%' align='center' class='table_data'>
						<thead>
							<th>". _('Available RSS Feeds') ."</th>
						</thead>
						<tbody>";
				
	$color = 0;	
					
	foreach($feeds as $feed)
	{
		$class  = ($color%2 == 0) ? "lightgray" : "blank"; 
		echo
			"<tr class='$class' onclick='javascript:choose_option(\"".$feed[1]."\");'>								
				<td class='td_report_name'>
					<a id='sel_$color' href='javascript:void(0);'>".$feed[0]."</a>
				</td>
			</tr>";		
	
		$color++;
	}
			
	echo "
					</tbody>
				</table>
			</div>
			</td>
		</tr>";
		
	
	echo "
		<tr>
			<td class='nobborder'><br></td>
		</tr>";	
		
}


function draw_image_url($widget_content ='', $widget_media='')
{
	$img_url = ((empty($widget_media))? $widget_content : '');

	echo "
		<tr>
			<td class='nobborder' style='text-align:center;'><br>
				<table width='60%' align='center' class='transparent'>
					<tr>
						<td width='30%' class='nobborder' style='text-align:right;'>". _('Image Url') .": </strong></td> 
						<td width='70%' class='nobborder' style='text-align:left;'><input style='width:250px' type='text' name='widget_content' value='$img_url'/></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class='nobborder' style='text-align:center;'><br><b>". _('or') ."</b></td>
		</tr>";	

	
	echo "
		<tr>
			<td class='nobborder' style='text-align:center;'><br>
				<table width='60%' align='center' class='transparent'>
					<tr>
						<td width='30%' class='nobborder' style='text-align:right;'>". _("Upload image file") .": </td> 
						<td width='70%' class='nobborder' style='text-align:left;'><input type='file' class='ne1' size='40' name='img_file' /></td> 
					</tr>
				</table>						
			</td>
		</tr>";
		
	if (!empty($widget_media))
	{
		echo "
			<tr>
				<td class='nobborder' style='text-align:center;'>
					<div style='width:60%;margin:10px auto 0 auto;text-align:center;'>
						<img src='data:image/png;base64, ".base64_encode($widget_media) ."' width='200px' height='100px' border='0' align='middle'>
					</div>
				</td>			
			</tr>";
	}	

	echo "
	
		<tr>
			<td class='nobborder' style='text-align:center;'>
				<table width='50%' align='center' style='margin-top:50px;'>
					<tr>
						<td width='40%' class='nobborder' valign='top'>
							<span><b>". _("File Format Supported") .":</b><br>  GIF, JPG, PNG, SWF, SWC, PSD, TIFF, BMP, IFF, JP2, JPX, JB2, JPC, XBM, WBMP</span>
						</td>
						<td width='50%' class='nobborder' valign='top' style='text-align:right;'>
							<span><b>". _("Max Size") .":</b> 250Kb </span>
						</td>
					</tr>
				</table
			</td>
		</tr>
		<tr>
			<td class='nobborder'><br></td>
		</tr>";	

}


function draw_gauge_list($gauge_list, $widget_text)
{
	echo "	
	<tr>
		<td class='nobborder'>
			<div style='width:75%;margin:0 auto;padding-top:10px;'>
					<table width='100%' align='center' class='table_data'>
						<thead>
							<th>". _('Available Gauges') ."</th>
						</thead>
						<tbody>";
					
						if (is_array($gauge_list) && !empty($gauge_list))
						{

							$color = 0;					
							foreach ($gauge_list as $gauge => $gid)
							{
								$class  = ($color%2 == 0) ? "lightgray" : "blank"; 
								$border = (($color+1) == count($gauge_list) ) ? "nobborder " : "";
										
								echo
									"<tr class='$class' onclick='javascript:choose_class($gid);'>								
										<td class='td_report_name $border' style='text-align:center'>
											<a id='sel_$color' href='javascript:void(0);'>$gauge</a>
										</td>
									</tr>";		
							
								$color++;
							}

						}
	echo "
					</tbody>
				</table>
			</div>
		</td>
	</tr>
	<tr>
		<td class='nobborder'>								
			<br><br><br>
		</td>
	</tr>			
	<tr>
		<td valign='top' class='nobborder' style='text-align:center;padding:2px 0px 0px 0px;'>								
			<strong>". _("Selected:")."</strong>&nbsp;
			<span id='dassets'>" .$widget_text. "</span>
		</td>
	</tr>		
	";	
}


function draw_accordion($categories_list, $widget_id, $widget_text)
{
	$t_no_data_available = utf8_encode(_("No data available"));
	
	echo "	
	<tr>
		<td class='nobborder'><br>
			<table align='center' width='97%' border='0'>
				<tr>
					<td class='nobborder' style='text-align:center;'>";	

					
	if (is_array($categories_list) && !empty($categories_list))
	{
		echo "<div id='accordion' style='overflow:auto'>";
		
		$i = 1;
		
		foreach ($categories_list as $category => $widgets)
		{
			echo "<h3 id='$i'><a href='#'>$category</a></h3>";
			echo "<div>";
			
			echo draw_carrousel($i, $widgets, $widget_id);	
										
			echo "</div>";
			
			$i++;
		}
		
		echo "</div>";
	}
	else
	{
		echo "<span>$t_no_data_available</span>";
	}
	

	echo "
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class='nobborder'>								
			<br><br><br>
		</td>
	</tr>			
	<tr>
		<td valign='top' class='nobborder' style='text-align:center;padding:2px 0px 0px 0px;'>								
			<strong>". _("Selected:")."</strong>&nbsp;
			<span id='dassets'>" .$widget_text. "</span>
		</td>
	</tr>		
	";				
	
}


function draw_carrousel($id, $widgets, $selected)
{
	
	$size = count($widgets);
	
	echo "
	<table align='center' class='transparent' style='height:130px;margin:0px auto 5px auto;'>
		<tr>
			<td class='nobborder' style='text-align:center;'>
				<div id='jCButton$id' class='carousel'>											
					<button id='prev$id' class='prev small av_b_secondary'><<</button>
					<button id='next$id' class='next small av_b_secondary'>>></button>
					<div id='jCarouselLite$id' class='jCarouselLite' >
						<ul id='$size'>
	";
	
	foreach ($widgets as $title => $wid)
	{
		$class_widget = ($wid == $selected) ? 'carousel_selected' : 'carousel_unselected';
		
		echo "<li>
			<div class='div_jc' id='$wid'>
				<a href='javascript:;' onclick='javascript:set_widget_class($wid);'><div class='widget_class $class_widget wclass$wid'><img src='/ossim/dashboard/pixmaps/thumbs/widgets/$wid.png' width='130' height='75' alt='' /></div></a>
				<div style='width:150px;padding-top:5px'>$title</div>
			</div>
		</li>\n";
	}
	
	echo "					</ul>
					</div>						
				</div>  
			</td>				
		</tr>
		<tr>
			<td class='noborder' style='text-align:center'>
			</td>
		</tr>
	</table>
	";
	
}


function draw_text($params, $report_id, $selected_value)
{
	//Drawing an Slider
	if (preg_match("/.*OSS_DIGIT.*/",$params[3]))
	{
		$slider_id      = "slider".$report_id."_".$params[1];
		$span_id        = "amount".$report_id."_".$params[1];
		$hidden_id      = "i".$report_id."_".$params[1];
		$hidden_name    = $hidden_id;
		$span_text      = $hidden_value = ($selected_value != "") ? $selected_value : $params[4]; 		
		                                
        if (intval($params[6])>0) 
        {   
            $step = intval($params[6]);
        }
        else
        {
            $step = ($params[5] >= 1000) ? 50 : 5;
        }
                  
        ?>
        
        <td class='db_w_label'> 
            <?php echo _($params[0]).": "?>
        </td>
        
		<td class='db_w_input'>
		
			<div id="<?php echo $slider_id?>" class="db_w_slider"></div>

			<div id="<?php echo $span_id?>" class="db_w_slider_legend"><?php echo $span_text?></div>
			
			<input type="hidden" id="<?php echo $hidden_id ?>" name="<?php echo $hidden_name?>" value="<?php echo $hidden_value ?>"/>
		</td>
		
		<script type='text/javascript'>
			$("#<?php echo $slider_id ?>").slider({
				animate: true,
				range: "min",
				value: <?php echo $hidden_value ?>,
				min:   <?php echo $params[4] ?>,
				max:   <?php echo $params[5] ?>,
				step:  <?php echo $step ?>,
				slide: function(event, ui) {
					$("#<?php echo $span_id ?>").html(ui.value);
					$("#<?php echo $hidden_id ?>").val(ui.value);
				}
			});
		</script>
		<?php
	}
	else
	{
		$input_size  = ($params[5]!="") ? " maxlength='".$params[5]."'" : ""; 
		$input_name  = "i".$report_id."_".$params[1];
		$input_value = ( $selected_value != "" ) ? $selected_value : $params[4];

    ?>
    		
		<td class='db_w_label'> 
            <?php echo _($params[0]).": " ?>
        </td>
        <td class='db_w_input'>
            <input type="text" <?php echo $input_size ?> name="<?php echo $input_name ?>" value="<?php echo $input_value ?>"/>
        </td>
	
	<?php
	}
}


function draw_select($params, $report_id, $selected_value)
{
															
	$values       = explode(",",$params[4]);
	$texts        = ($params[6] != "") ? explode(",",$params[6]) : $values;
	$disabled     = "";
	$params[5]    = ($selected_value != "") ? $selected_value : $params[5]; //default value	

	$select_name  = "i".$report_id."_".$params[1];
	$select_id    = "";
	$onchange     = "";
		
	?>
	
	<td class='db_w_label'>
        <?php echo _($params[0]).": " ?> 
    </td>
    
    <td class='db_w_input db_w_left'>
        <select style='width:50%' name="<?php echo $select_name?>" <?php echo $select_id?> <?php echo $onchange?> <?php echo $disabled?>>				
			<?php				
			$i = 0;														
			foreach ($values as $val)
			{
				$selected = ( $params[5] == $val ) ? "selected='selected'" : "";
				echo "<option value='$val' $selected>".$texts[$i]."</option>";
				$i++;
			}
	
			?>
		</select>
    </td>
    
	<?php
}

		
function draw_radiobutton($params, $widget_id, $selected_value)
{
	$values = explode(",",$params[4]);
	
    if (count(values) == 0)
    {
        exit;
    }
	
	$texts = ($params[6] != "") ? explode(",",$params[6]) : $values;
	
	if ($selected_value != "")
	{
		$svalue = $selected_value;
	}
	else
	{
		$svalue = ( $params[5]=="" ) ? $values[0] : $params[5];
	}
		
	$r_name  = "i".$widget_id."_".$params[1];
	$checked = (intval($svalue)== 1) ? "checked='checked'" : "";
	
	?>
	
    <td class='db_w_label'>
        <?php echo _($params[0]).": " ?>
    </td>
    
    <td class='db_w_input db_w_left'>
    
	<?php 

	$i = 0;
	
	foreach ($values as $val)
	{
		$checked = ($svalue== $val) ? "checked='checked'" : "";
		
		echo "<input type='radio' name='$r_name' value='$val' $checked>"._($texts[$i])."<br/>";

		$i++;
	}
	
	echo "</td>";

}


function display_errors($info_error)
{

    $errors    = implode ("</div><div style='padding-top: 3px;'>", $info_error);
            
    $error_msg = "<div style='padding-bottom: 3px;'>"._("The following errors occurred:")."</div>
                    <div style='margin-left: 15px;'><div>$errors</div></div>";
    
	$style     = (empty($style)) ? 'margin: 20px auto; width: 60%; text-align:left' : $style;
	
    $config_nt = array(
        'content' => $error_msg,
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => true
        ),
        'style'   => $style
    ); 
                    
    $nt = new Notification('nt_1', $config_nt);
    
    return $nt->show(false);		
}	
