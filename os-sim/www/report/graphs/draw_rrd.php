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

$m_perms  = array ('dashboard-menu', 'analysis-menu');
$sm_perms = array ('ControlPanelMetrics', 'EventsForensics');

if (!Session::menu_perms($m_perms, $sm_perms))
{
    Session::unallowed_section(FALSE);
}


/*
 This will show errors (both PHP Errors and those detected in the code)
 as graphics, so they can be read.
*/
function mydie($errno, $errstr = '', $errfile = '', $errline = '')
{
    global $conf;
    $jpgraph = $conf->get_conf('jpgraph_path');
    include_once "$jpgraph/jpgraph.php";
    $err = ($errstr) ? $errstr : $errno;

    if ($errfile)
    {
        switch ($errno)
        {
            case 1:
                $errprefix = 'Error';
            break;

            case 2:
                $errprefix = 'Warning';
            break;

            case 8:
                $errprefix = 'Notice';
            break;

            default:
                return; // dont show E_STRICT errors
        }

        $err = "$errprefix: $err in '$errfile' line $errline";
    }

    $error = new JpGraphError();
    $error->Raise($err);

    echo "$err";
    exit();
}


function clean_tmp()
{
    global $tmpfile;

    @unlink($tmpfile);
}


$db   = new ossim_db();
$conn = $db->connect();

$conf = $GLOBALS['CONF'];
$rrdtool_bin = $conf->get_conf('rrdtool_path').'/rrdtool';


set_error_handler('mydie');
$id    = GET('id');
$what  = GET('what');
$type  = GET('type');
$start = GET('start');
$end   = GET('end');
$zoom  = GET('zoom') ? GET('zoom') : 1;

ossim_valid($id,    OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_SCORE, 'illegal:' . _('ID'));
ossim_valid($start, OSS_LETTER, OSS_DIGIT, OSS_SCORE,          'illegal:' . _('Start param'));
ossim_valid($end,   OSS_LETTER, OSS_DIGIT, OSS_SCORE,          'illegal:' . _('End param'));
ossim_valid($zoom,  OSS_DIGIT, OSS_DOT,                        'illegal:' . _('Zoom parameter'));
ossim_valid($what,  OSS_ALPHA, OSS_SCORE,                      'illegal:' . _('What'));
ossim_valid($type,  OSS_ALPHA,                                 'illegal:' . _('Type'));


if (ossim_error())
{
    mydie(strip_tags(ossim_get_error_clean()));
}

//
// params validations
//
if ($what != 'eps')
{
    mydie(sprintf(_("Invalid param '%s' with value '%s'") , 'what', $what));
}

if ($type != 'eps')
{
    mydie(sprintf(_("Invalid param '%s' with value '%s'") , 'type', $type));
}


// Where to find the RRD file
$rrdpath = "/var/lib/ossim/rrd/event_stats/";

//
// Graph style
//
$font    = $conf->get_conf('font_path');
$tmpfile = tempnam('/tmp', 'OSSIM');

register_shutdown_function('clean_tmp');

$ds     = 'ds0';
$color1 = '#f000f0';
$color2 = '#000000';


$hostname = Session::get_entity_name($conn, $id);


//
// RRDTool cmd execution
//
if (!is_file("$rrdpath/$id.rrd"))
{
    //mydie(sprintf(_("No RRD available for: '%s' at '%s'") , $ip, $rrdpath));
    $norrdfile = "../../pixmaps/norrd.png";
    if (!$fp = @fopen($norrdfile, 'r'))
    {
        mydie(_("Could not read $norrdfile file"));
    }

    header("Content-Type: image/png");
    header("Content-Length: " . filesize($norrdfile));
    fpassthru($fp);
    fclose($fp);
    exit();
}


$_cmd    = '? graph ? -s ? -e ? -t ? --font ? --font ? -r --zoom ? --vertical-label=EPS --lower-limit=0 ? ? ?';
$_params = array(
        $rrdtool_bin,
        $tmpfile,
        $start,
        $end,
        $hostname.' '._('Metrics'),
        'TITLE:12:'.$font,
        'AXIS:7:'.$font,
        $zoom,
        "DEF:obs=$rrdpath/$id.rrd:ds0:AVERAGE",
        "CDEF:bp=obs,obs,+,2,/",
        "AREA:bp$color1: $hostname "
);
    

try
{
    $output = Util::execute_command($_cmd, $_params, 'array');
    
    if (preg_match('/^ERROR/i', $output[0]))
    {
        mydie(_("rrdtool cmd failed with error"));
    }
}
catch(Exception $e)
{
    mydie(_("rrdtool cmd failed with error"));
}

//
// Output generated image
//
if (!$fp = @fopen($tmpfile, 'r'))
{
    mydie(sprintf(_("Could not read rrdtool created image: '%s'") , $tmpfile));
}

header("Content-Type: image/png");
header("Content-Length: " . filesize($tmpfile));
fpassthru($fp);
fclose($fp);
