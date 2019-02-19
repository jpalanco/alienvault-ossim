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

$what = GET('what');
$back = GET('back');
ossim_valid($what, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('What'));
ossim_valid($back, OSS_TEXT, OSS_PUNC_EXT,  'illegal:' . _('Back'));

if (ossim_error()) 
{
    die(ossim_error());
}

/* what to reload... */
if (empty($what)) 
{
    $what = 'all';
}
if(!preg_match('#^\.{0,2}(/?[a-zA-Z0-9]+)+(\.[a-zA-Z]{1,4}|/|\?([a-zA-Z0-9\[\]_-]+=[a-zA-Z0-9_-]*\&?)+)*$#',$back)) {
    die(ossim_error("Error in the '"._("Back")."' field ('$back' not allowed)."));
}

$back = htmlspecialchars(urldecode($back));

if ($what == 'policies')
{
	Session::logcheck('configuration-menu', 'PolicyPolicy');
}
else 
{	
	Session::logcheck('configuration-menu', 'PolicyServers'); // Who manage server can reload server conf
}


// Refresh cache
Util::memcacheFlush();
Util::resend_asset_dump($what);

$conf = $GLOBALS['CONF'];
    									
if (!$conf)
{
    $conf = new Ossim_conf();
    $GLOBALS['CONF'] = $conf;
}

/* Get the port and IP address of the server */
$address = '127.0.0.1';
$port    = $conf->get_conf('server_port');


/* Create socket */
$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket < 0) 
{
    $error = sprintf(_("socket_create() failed: reason: %s\n") , socket_strerror($socket));
    echo ossim_error($error);
}


/* Connect */
$result = @socket_connect($socket, $address, $port);
if ($result < 0) 
{
    $error = sprintf(_("socket_connect() failed: reason: %s %s\n") , $result, socket_strerror($result));
    echo ossim_error($error);
}

$in  = 'connect id="1" type="web"' . "\n";
$out = '';
@socket_write($socket, $in, strlen($in));
$out = @socket_read($socket, 2048);

if (strncmp($out, 'ok id="1"', 9) != 0) 
{
    // If the server is down / unavailable, clear the need to reload
    // Switch off web indicator
    
    if ($what == 'all') 
    {
        Web_indicator::set_off('Reload_policies');
        Web_indicator::set_off('Reload_sensors');
        Web_indicator::set_off('Reload_servers');
    }
    else
    { 
        Web_indicator::set_off('Reload_' . $what);
    }
    
    $error  = sprintf(_("Unable to connect to %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
    
    echo ossim_error($error);
    exit();
}

// ********** Reload action: 2 modes ***********
// Note: Since 01/09/2014 the Directive_editor::reload_directives() is unified here
//       And reload_plugins does the same action (ossim-server restart)


// 1-. Server daemon hard restart mode
if ($what == 'directives' || $what == 'plugins')
{
    Util::execute_command('sudo /etc/init.d/ossim-server restart > /dev/null 2>&1 &');
}
// 2-. Server socket mode
else
{
    $in  = 'reload-' . $what . ' id="2"' . "\n";
    $out = '';
    
    @socket_write($socket, $in, strlen($in));
    $out = @socket_read($socket, 2048);
    
    if (strncmp($out, 'ok id="2"', 9) != 0) 
    {
        $error  = sprintf(_("Bad response from %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
        echo ossim_error($error);
        exit;
    }
    
    @socket_shutdown($socket);
    @socket_close($socket);
}

// Switch off web indicator

if ($what == 'all') 
{
    Web_indicator::set_off('Reload_policies');
    Web_indicator::set_off('Reload_sensors');
    Web_indicator::set_off('Reload_servers');
} 
else
{
    Web_indicator::set_off('Reload_' . $what);
}

// ReloadPolicy key deprecated, now using Reload_policies always
// Reset main indicator if no more policy reload need
/*
if (!Web_indicator::is_on('Reload_policies') && !Web_indicator::is_on('Reload_sensors') && !Web_indicator::is_on('Reload_plugins') 
    && !Web_indicator::is_on('Reload_directives') && !Web_indicator::is_on('Reload_servers')) {
    Web_indicator::set_off('ReloadPolicy');
}
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
    <body>
        <br/>
        <p><?php echo _('Reload completed successfully'); ?></p>
        <?php
        sleep(1);
        ?>
        <script type="text/javascript">
            $(document).ready(function(){
                if (typeof(top.refresh_notifications) == 'function')
                {
                    top.refresh_notifications()
                }
                    
                document.location.href = '<?php echo str_replace("'","\'",urldecode($back)) ?>';
            });
        </script>
    </body>
</html>
