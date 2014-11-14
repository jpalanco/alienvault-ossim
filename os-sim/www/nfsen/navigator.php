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

Session::logcheck("environment-menu", "MonitorsNetflows");

function navigator()
{

	global $self;
	global $TabList;
	global $GraphTabs;

	$numtabs = count($TabList);

	$plugins  = GetPlugins();
	$profiles = GetProfiles();
	$profileswitch = $_SESSION['profileswitch'];

	switch ($_SESSION['profileinfo']['type'] & 3) {
		case 0:
			$profile_type = 'live';
			break;
		case 1:
			$profile_type = 'history';
			break;
		case 2:
			$profile_type = 'continuous';
			break;
		default:
			$type = 'unknown';
	}


	$profile_type .= ($_SESSION['profileinfo']['type'] & 4) > 0  ? '&nbsp;/&nbsp;shadow' : '';

	$perms = allowed_nfsen_section();

	$disabled =  '';
	
	if(!$perms)
	{
		$disabled = 'font-style:italic;color: #aeaeae;text-decoration:none;cursor:default;';
	}

    global $conf;
?>
    
    <!--
    <div style="position:absolute;right:30px;top:15px;vertical-align:bottom;width:450px;">
        <table border=0 align="right" style="margin:0px;padding:0px;background-color:transparent;border:0px none">
            <tr>
                <td align="right" class="black" nowrap style="background-color:transparent;border:0px none">
                    <a class="white" <?=($_SESSION['tab']==2) ? "style='font-weight:bold !important'" : "" ?> href="nfsen.php?tab=2"><?=_("Details")?></a>
                </td>
                <td style='padding:0px 7px 0px 7px' class='separator'><img src='/ossim/pixmaps/1x1.png'/></td>
                
                <td>
                    <a style="<?php echo $disabled ?><?=($_SESSION['tab']==0) ? ";font-weight:bold !important" : "" ?>" class="white" href="<?php echo (($perms) ? "nfsen.php?tab=0" : "javascript:;") ?>"><?=_("Overview")?>
                    </a>
                </td>
                
                <td style='padding:0px 7px 0px 7px' class='separator'><img src='/ossim/pixmaps/1x1.png'/></td>
                
                <td>                
                    <a style="<?php echo $disabled ?><?=($_SESSION['tab']==1) ? ";font-weight:bold !important" : "" ?>" class="white" href="<?php echo (($perms) ? "nfsen.php?tab=1" : "javascript:;") ?>"><?=_("Graphs")?>
                    </a>
                </td>
            </tr>
        </table>
    </div>
	-->
	
	
	<form action="<?php echo $self?>" name='navi' method="POST">
	<div class="shadetabs" style="display:none"><br>
        <table border='0' cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <ul>
                        <?php
                        for ($i = 0; $i < $numtabs; $i++) 
                        {
                            if ($i == $_SESSION['tab'])
                            {
                                echo "<li class='selected'><a href='$self?tab=$i'>" . $TabList[$i] . "</a></li>\n";
                            } 
                            else
                            {
                                echo "<li><a href='$self?tab=$i'>" . $TabList[$i] . "</a></li>\n";
                            }
                        }
                        ?>
                    </ul>
                </td>
                
                <td class="navigator">
                    <?php echo $profile_type;?>
                </td>
            
                <td class="navigator"><?=_("Profile")?>:</td>
            
            </tr>
    	</table>
    	
    	<input type="hidden" id="profilemenu_field" name="profileswitch" value="<?php echo Util::htmlentities($profileswitch);?>"> 
 	</div>

 	<?php
	$_tab = $_SESSION['tab'];
	
	if ($TabList[$_tab] == 'Graphs') 
	{
		$_sub_tab = $_SESSION['sub_tab'];
		$base_url = Menu::get_menu_url('/ossim/nfsen/nfsen.php?tab=1', 'environment', 'netflow', 'graph');
		?>
        <div class="shadetabs">
        <br>
            <table border='0' cellpadding="0" cellspacing="0" class="noborder" align="center">
                <tr>
                    <td class="noborder" style="padding-bottom:5px;text-align:center">
                    <?php
                    for ($i = 0; $i < count($GraphTabs); $i++) 
                    {
                        $g_url = $base_url . '&sub_tab=' . $i;
                        
                        if ($i > 0)
                        {
                            echo "| ";
                        }
                        
                        if ($i == $_sub_tab) 
                        {
                            echo "<a href='$g_url'><strong>" . $GraphTabs[$i] . "</strong></a>\n";
                    	} 
                        else 
                        {
                            echo "<a href='$g_url'>" . $GraphTabs[$i] . "</a>\n";
                        }                    
                    }
                    ?>                   
                    </td>
                </tr>
            </table>
		</div>
    <?php
	}

    if ($TabList[$_tab] == 'Plugins') 
    {
    	if (count($plugins) == 0) 
    	{
    	   ?>
    		<div class="shadetabs"><br>
    			<h3 style='margin-left: 10px;margin-bottom: 2px;margin-top: 2px;'>No plugins available!</h3>
    		</div>
    		<?php
    	} 
    	else 
    	{
    	    ?>
            <div class="shadetabs"><br>
                <table border='0' cellpadding="0" cellspacing="0">
                	<tr>
                		<td>
                			<ul>
                				<?php
                				for ($i = 0; $i <  count($plugins); $i++) 
                				{
                					if ($i == $_SESSION['sub_tab']) 
                					{
                						print "<li class='selected'><a href='$self?sub_tab=$i'>" . Util::htmlentities($plugins[$i]) . "</a></li>\n";
                					} 
                					else 
                					{
                						print "<li><a href='$self?sub_tab=$i'>" . Util::htmlentities($plugins[$i]) . "</a></li>\n";
                					}
                				}
                				?>
                			</ul>
                		</td>
                	</tr>
                </table>
            </div>
            <?php
    	}
    }

	print "</form>\n";
	print "<script language='Javascript' type='text/javascript'>\n";
	print "selectMenus['profilemenu'] = 0;\n";

	/*
	$i = 0;
	$savegroup = '';
	$groupid = 0;
    foreach ($profiles as $profileswitch) {
		if (preg_match("/^(.+)\/(.+)/", $profileswitch, $matches)) {
			$profilegroup = $matches[1];
			$profilename  = $matches[2];
            
            $profilename  = Util::htmlentities($profilename);
            $profilegroup = Util::htmlentities($profilegroup);
            $profileswitch = Util::htmlentities($profileswitch);
            
			if ($profilegroup == '.') {
				print "selectOptions[selectOptions.length] = '0||$profilename||./$profilename'; \n";
			} else {
				if ($profilegroup != $savegroup) {
					$savegroup = $profilegroup;
					print "selectOptions[selectOptions.length] = '0||$profilegroup||@@0.$i'; \n";
					$groupid = $i;
					$i++;
				}
				print "selectOptions[selectOptions.length] = '0.$groupid||$profilename||$profilegroup/$profilename'; \n";
			}
		} else {
			print "selectOptions[selectOptions.length] = '0||".Util::htmlentities($profileswitch)."||".Util::htmlentities($profileswitch)."'; \n";
		}
		$i++;
    }
	*/
	//print "selectRelateMenu('profilemenu', function() { document.navi.submit(); });\n";
	// print "selectRelateMenu('profilemenu', false);\n";

	print "</script>\n";
	print "<noscript><h3 class='errstring'>"._("Your browser does not support JavaScript! NfSen will not work properly!")."</h3></noscript>\n";
	$bk = base64_decode(urldecode($_SESSION['bookmark']));

} // End of navigator

