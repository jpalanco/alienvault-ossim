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

if (!Session::am_i_admin()) 
{
    Session::unallowed_section(null, FALSE);
    exit();
}

set_time_limit(0);
ignore_user_abort(TRUE);
ob_implicit_flush(TRUE);

$version = GET('version');
$type    = GET('type');
$force   = GET('force');

ossim_valid($version, OSS_DIGIT, OSS_LETTER, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Version"));
ossim_valid($type, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,               'illegal:' . _("Type"));
ossim_valid($force, OSS_ALPHA, OSS_NULLABLE,                         'illegal:' . _("Force"));

if (ossim_error()) 
{
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>
<?php

$upgrade = new Upgrade();

if (GET('submit')) 
{
    $ok = $upgrade->needs_upgrade();
    
    if (!$ok) 
    {
        echo ossim_error(_("No upgrades needed"), AV_INFO);
        exit();
    }
    
    if (ossim_error()) 
    {
        echo ossim_error(_("Not clean installation detected. Refusing to apply upgrades, please do it manually"));
        exit();
    }
    
    $upgrade->apply_needed();
    
        
    echo "<br><br>
    <form>
        <table width='100%' class='noborder' style='background:transparent;'>
            <tr>
                <td class='nobborder' style='text-align:center;'>
                    <input type='button' onclick=\"top.frames['main'].window.location.reload();\" value='"._("Continue")."'/>
                </td>
            </tr>
        </table>
    </form>";
    
    exit();
 
}
// Force a certain upgrade
if (GET('version') && GET('type') && GET('force')) 
{
    $upgrades = $upgrade->get_all();
    
	if (!isset($upgrades[$version])) 
	{
        echo ossim_error(_("Error! No valid version upgrade"));
        exit();
    }
    
	switch ($type) 
	{
        case 'php_pre':
            $file = $upgrades[$version]['php']['file'];
            $upgrade->create_php_upgrade_object($file, $version);
            // XXX Move that to the main class
            echo "<pre>" . _("Starting PHP PRE script") . "...\n";
            $upgrade->php->start_upgrade();
            echo "\n" . _("PHP PRE script ended") . "</pre>";
            $upgrade->destroy_php_upgrade_object();
        break;

        case 'php_post':
            $file = $upgrades[$version]['php']['file'];
            $upgrade->create_php_upgrade_object($file, $version);
            echo "<pre>" . _("Starting PHP POST script") . "...\n";
            $upgrade->php->end_upgrade();
            echo "\n" . _("PHP POST script ended") . "</pre>";
            $upgrade->destroy_php_upgrade_object();
        break;

        case 'sql':
            $file = $upgrades[$version]['sql']['file'];
            $upgrade->execute_sql($file, TRUE);
        break;
    }
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    
    echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '">' . _("Continue") . '</a>';
    exit();
}

?>
<br/>
<table width="50%" align="center">
    <tr>
        <th><?php echo gettext("Alienvault SIEM Version Detected"); ?>:</th><td><?php echo $upgrade->ossim_current_version ?>&nbsp;</td>
    </tr>
    <tr>
        <th><?php echo gettext("Schema Version Detected"); ?>:</th><td><?php echo $upgrade->ossim_schema_version ?>&nbsp;</td>
    </tr>
    <tr>
        <th><?php echo gettext("Database Type Detected"); ?>:</th><td><?php echo $upgrade->ossim_dbtype ?>&nbsp;</td>
    </tr>
</table>
<br/>

<?php

function print_upgrade_link($file, $type, $label, $version, $required) 
{
    echo "$file&nbsp; (";
    
    if (!$required) 
    {
        $confirm =  Util::js_entities(_('This will force only this upgrade and ' . 'may cause unexpected results. Use the \\\'Apply Changes\\\' ' . 'button instead.\n\nContinue anyway?'));
        echo "<a href=\"?version=$version&type=$type&force=1\" class='av_l_main' onClick=\"return confirm('$confirm')\">$label</a>)";
    } 
    else 
    {
        echo "$label)";
    }
}

$list[0]['name'] = _("Required upgrades");
$list[0]['upgrades'] = $upgrade->get_needed();
$list[0]['required'] = TRUE;

// this method search for errors and sets them via ossim_set_error()
$upgrade->needs_upgrade();

if (ossim_error())
{
	echo ossim_error();
}	
	
$list[1]['name']     = _("All upgrades");
$list[1]['upgrades'] = $upgrade->get_all();
$list[1]['required'] = FALSE;


foreach($list as $k => $v) 
{
    ?>
    <div class='sec_title'><?php echo _($v['name']) ?></div>
    <?php
    if (!count($v['upgrades'])) 
    {        
        ?>
        <br/><i><center><?php echo _("No upgrades") ?></center></i>
        <?php
        continue;
    } 
    ?>
    
    <form>
        <table class='table_list' align="center">
            <tr>
                <th><?php echo gettext("Version");?></th>
                <th><?php echo gettext("Required"); ?></th>
            </tr>
            
            <?php
			foreach($v['upgrades'] as $version => $actions) 
			{ 
				?>
				<tr>
					<td><?php echo $version?></td>
					<td style="text-align: left;">
					<?php
						$pos   = 0;
						$php   = isset($actions['php']['file']) ? $actions['php']['file'] : '';
						$sql   = isset($actions['sql']['file']) ? $actions['sql']['file'] : '';
						$error = isset($actions['error']['file']) ? $actions['error']['file'] : '';
						
						if ($error) 
						{
							echo "<font color=red>$error</font>";
							continue;
						}
						
						if ($php && ++$pos) 
						{
							echo "<br/>{$pos}º ";
							print_upgrade_link($php, 'php_pre', gettext("PHP script: PRE") , $version, $v['required']);
						}
						
						if ($sql && ++$pos) 
						{
							echo "<br/>{$pos}º ";
							print_upgrade_link($sql, 'sql', gettext("SQL schema update") , $version, $v['required']);
							Upgrade::info_upgrade ($v['required'], basename($sql));
						}
						
						if ($php && ++$pos) 
						{
							echo "<br/>{$pos}º ";
							print_upgrade_link($php, 'php_post', gettext("PHP script: POST") , $version, $v['required']);
						}
						
						echo "<br/>&nbsp;";
					?>
				</tr>
				<?php
			} 
			?>
		</table>
		<br/>
        <?php
        if ($v['required']) 
    	{ 
    		?>
    		<center>
    		<input type="submit" name="submit" value="<?php echo _("Apply Changes") ?>" onClick="return confirm('<?php echo  Util::js_entities(_("IMPORTANT: Please make sure you have made a backup of the database before continue")) ?>')"/>
    		</center>
    		<?php
        } 
    	?>
    </form>
	<br>
	<?php
} 
?>