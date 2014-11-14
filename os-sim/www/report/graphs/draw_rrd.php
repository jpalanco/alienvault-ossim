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
if (!in_array($what, array('compromise', 'attack', 'eps', 'ser_lev', 'bp', 'stat')))
{
    mydie(sprintf(_("Invalid param '%s' with value '%s'") , 'what', $what));
}

if (!in_array($type, array('host', 'net', 'eps', 'global', 'level', 'bp', 'stat')))
{
    mydie(sprintf(_("Invalid param '%s' with value '%s'") , 'type', $type));
}




// Where to find the RRD file
switch ($type)
{
    case 'host':
        $rrdpath = $conf->get_conf('rrdpath_host');
    break;

    case 'net':
        $rrdpath = $conf->get_conf('rrdpath_net');
    break;

    case 'global':
        $rrdpath = $conf->get_conf('rrdpath_global');
    break;

    case 'level':
        $rrdpath = $conf->get_conf('rrdpath_level');
    break;

    case 'bp':
        $rrdpath = $conf->get_conf('rrdpath_bps');
    break;

    case 'stat':
        $rrdpath = $conf->get_conf('rrdpath_stats');
    break;

    case 'eps':
        $rrdpath = "/var/lib/ossim/rrd/event_stats/";
    break;

}
//
// Graph style
//
$font    = $conf->get_conf('font_path');
$tmpfile = tempnam('/tmp', 'OSSIM');

register_shutdown_function('clean_tmp');

if ($what == 'compromise')
{
    $ds     = 'ds0';
    $color1 = '#0000ff';
    $color2 = '#ff0000';
}
elseif ($what == 'attack')
{
    $ds     = 'ds1';
    $color1 = '#ff0000';
    $color2 = '#0000ff';
}
elseif ($what == 'ser_lev')
{
    $color1 = '#24d428';
    $color2 = '#000000';
}
elseif ($what == 'bp')
{
    $color1 = '#ff0000';
    $color2 = '#000000';
}
elseif ($what == 'stat')
{
    $ds     = 'ds0';
    $color1 = '#11ff11';
    $color2 = '#000000';
}
elseif ($what == 'eps')
{
    $ds     = 'ds0';
    $color1 = '#f000f0';
    $color2 = '#000000';
}


//
// Threshold calculations
//
// default values
$threshold_a = $threshold_c = $conf->get_conf('threshold');

$hostname = $id;

if ($type == 'host' || $type == 'net')
{
    $match = ($type == 'host') ? 'hostname' : 'name';
    $sql   = "SELECT threshold_c, threshold_a, $match FROM $type WHERE id = UNHEX(?)";

    if (!$rs = $conn->Execute($sql, array($id)))
    {
        mydie($conn->ErrorMsg());
    }

    if (!$rs->EOF)
    {
        // If a specific threshold was set for this host, use it
        $threshold_c = $rs->fields['threshold_c'];
        $threshold_a = $rs->fields['threshold_a'];
        $hostname    = $rs->fields[$match];
    }
}
elseif ($type == 'eps')
{
    $hostname = Session::get_entity_name($conn, $id);
}


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

if ($type == 'bp')
{
    $hostname = ucfirst(str_replace('-', ' of ', $hostname));
    $hostname = ucfirst(str_replace('_', ' ', $hostname));
} 
elseif ($type != 'host' && $type != 'net')
{
    // beautify in case of "global_admin"
    $hostname = ucfirst(str_replace('_', ' ', $hostname));
}

$params = "graph $tmpfile " . "-s $start -e $end " . "-t '$hostname " . _("Metrics") . "' " . "--font TITLE:12:$font --font AXIS:7:$font " . "-r --zoom $zoom ";

if ($type != 'level' and $type != 'bp' and $type != 'stat' and $what != "eps")
{
    $ymax = (int)2.5 * $threshold_a;
    $ymin = - 1 * (int)(2.5 * $threshold_c);
    $params.= "-u $ymax -l $ymin ";
}

if ($what != "ser_lev" and $what != "bp" and $what != "stat" and $what != "eps")
{
    $params.= "DEF:obs=$rrdpath/$id.rrd:$ds:AVERAGE " . "DEF:obs2=$rrdpath/$id.rrd:ds1:AVERAGE " . "CDEF:negcomp=0,obs,- " . "AREA:obs2$color2:" . _("Attack") . " " . "AREA:negcomp$color1:" . _("Compromise") . " " . "HRULE:$threshold_a#000000 " . "HRULE:-$threshold_c#000000 ";
}
elseif ($what == "ser_lev")
{
    $params.= " --vertical-label=Percentage " . " --upper-limit=100  --lower-limit=0 " . "DEF:obs=$rrdpath/$id.rrd:ds0:AVERAGE " . "DEF:obs2=$rrdpath/$id.rrd:ds1:AVERAGE " . "CDEF:ser_lev=obs,obs2,+,2,/ " . "AREA:ser_lev$color1:'" . _("Service level") . "'";
}
elseif ($what == "bp")
{
    $params.= " --vertical-label=" . _("Risk") . " --upper-limit=10  --lower-limit=0 " . "DEF:obs=$rrdpath/$id.rrd:ds0:AVERAGE " . "CDEF:bp=obs,obs,+,2,/ " . "AREA:bp$color1:'" . _("Risk") . "'";
}
elseif ($what == "eps")
{
    $params.= " --vertical-label=EPS --lower-limit=0  DEF:obs=$rrdpath/$id.rrd:ds0:AVERAGE CDEF:bp=obs,obs,+,2,/ AREA:bp$color1:' $hostname '";
}
elseif ($what == "stat")
{
    $label = "Stat";

    switch ($id)
    {
        case "sensors":
            $label = _("Active Sensors");
        break;

        case "sensors_total":
            $label = _("Total Sensors");
        break;

        case "uniq_events":
            $label = _("Unique Events");
        break;

        case "categories":
            $label = _("Unique Categories");
        break;

        case "total_events":
            $label = _("Total Events");
        break;

        case "src_ips":
            $label = _("Unique Source IPs");
        break;

        case "dst_ips":
            $label = _("Unique Dest IPs");
        break;

        case "uniq_ip_links":
            $label = _("Unique IP links");
        break;

        case "source_ports":
            $label = _("Source Ports");
        break;

        case "dest_ports":
            $label = _("Destination Ports");
        break;

        case "source_ports_udp":
            $label = _("UDP Source Ports");
        break;

        case "source_ports_tcp":
            $label = _("TCP Source Ports");
        break;

        case "dest_ports_udp":
            $label = _("UDP Dest Ports");
        break;

        case "dest_ports_tcp":
            $label = _("TCP Dest Ports");
        break;

        case "tcp_events":
            $label = _("Total TCP Events");
        break;

        case "udp_events":
            $label = _("Total UDP Events");
        break;

        case "icmp_events":
            $label = _("Total ICMP Events");
        break;

        case "portscan_events":
            $label = _("Total Portscan Events");
        break;

        default:
            $label = _("Stat");
        break;
    }

    $params.= " -t \"$label\"  --lower-limit=0  " . "DEF:pred=$rrdpath/$id.rrd:ds0:HWPREDICT " . "DEF:ctr=$rrdpath/$id.rrd:ds0:AVERAGE " . "DEF:dev=$rrdpath/$id.rrd:ds0:DEVPREDICT " . "DEF:fail=$rrdpath/$id.rrd:ds0:FAILURES " . "CDEF:lower=pred,dev,2,*,- " . "CDEF:upper=dev,4,* " . "VDEF:vmin=ctr,MINIMUM " . "VDEF:vmax=ctr,MAXIMUM " . "VDEF:vavg=ctr,AVERAGE " . "VDEF:vcur=ctr,LAST " . "TICK:fail#ffffa0:1 " . "LINE1:lower#CCFF80: " . "AREA:upper#CCFF80::STACK " . "LINE3:pred#99BF60: " . "LINE0:upper#CCFF80:\"Predicted range\" " . "DEF:obs=$rrdpath/$id.rrd:ds0:AVERAGE " . "CDEF:bp=obs,obs,+,2,/ " . "AREA:bp$color1:'" . $label . "' " . "GPRINT:vcur:\"%6.0lf\" ";
}

//echo "Ejecutando: $rrdtool_bin $params<br>";
//error_log("$rrdtool_bin $params 2>&1\n", 3, "/tmp/debug.log");
exec("$rrdtool_bin $params 2>&1", $output, $exit_code);
if (preg_match('/^ERROR/i', $output[0]) || $exit_code != 0)
{
    mydie(sprintf(_("rrdtool cmd failed with error: '%s' (exit code: %s)") , $output[0], $exit_code));
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