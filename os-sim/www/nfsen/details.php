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

require "process.php";

$DisplayOrder = array ( 'any', 'TCP', 'UDP', 'ICMP', 'other' );

$TypeOrder	  = array ( 'flows', 'packets', 'traffic');



/* 
 * scale factor: Number of 5min slices per pixel
 * graph with 576 pixel
 * 0.25 * 576 * 300 = 43200 => 12 hours
 * 0.5  * 576 * 300 = 86400 => 1 day
 * $scale * 172800 = time range of graph
 */
$WinSizeScale  = array ( 0.25, 0.5, 1, 2, 3.5, 7, 15, 30, 90, 120, 183 );
// labels of the scale selector
$WinSizeLabels = array ( '12 Hours', '1 day', '2 days', '4 days', '1 week', '2 weeks', '1 month', '2 months', '6 months', '8 months', '1 year' );

// Definitions for the netflow processing table
$TopNOption   = array ( 10, 20, 50, 100, 200, 500);

$ListNOption  = array ( 20, 50, 100, 500, 1000, 10000);

$IPStatOption = array ( 'Flow Records', 
						'Any IP Address', 'SRC IP Address', 'DST IP Address', 
						'Any Port', 'SRC Port', 'DST Port',  
						'Any interface', 'IN interface', 'OUT interface',
						'Any AS',  'SRC AS',   'DST AS',
						'Next Hop IP', 'Next Hop BGP IP', 'Router IP',
						'Proto', 'Direction',
						'SRC TOS', 'DST TOS', 'TOS',
						'Any Mask Bits', 'SRC Mask Bits', 'DST Mask Bits',  
						'Any VLAN ID', 'SRC VLAN ID', 'DST VLAN ID',  
						'SRC MAC', 'DST MAC', 'IN MAC', 'OUT MAC',
						'IN SRC MAC', 'OUT DST MAC', 'IN DST MAC', 'OUT SRC MAC',
						'MPLS Label 1', 'MPLS Label 2', 'MPLS Label 3', 'MPLS Label 4', 'MPLS Label 5', 'MPLS Label 6', 'MPLS Label 7', 'MPLS Label 8', 'MPLS Label 9', 'MPLS Label 10'
					);

$IPStatArg	  = array ( '-s record', 
						'-s ip',   '-s srcip',   '-s dstip', 
						'-s port', '-s srcport', '-s dstport', 
						'-s if',   '-s inif',    '-s outif',
						'-s as',   '-s srcas',   '-s dstas',
						'-s nhip', '-s nhbip', 	 '-s router',
						'-s proto', '-s dir',
						'-s srctos', '-s dsttos', '-s tos',	 
						'-s mask',   '-s srcmask','-s dstmask',
						'-s vlan',   '-s srcvlan','-s dstvlan',
						'-s srcmac', '-d dstmac', '-s inmac', '-s outmac',
						'-s insrcmac',   '-s outdstmac','-s indstmac', '-s outsrcmac',
						'-s mapls1', '-s mapls2', '-s mapls3', '-s mapls4', '-s mapls5', '-s mapls6', '-s mapls7', '-s mapls8', '-s mapls9', '-s mapls10',
					);

$IPStatOrder  = array ( 'flows', 'packets', 'bytes', 'pps', 'bps', 'bpp' );
$LimitScale	  = array ( '-', 'K', 'M', 'G', 'T' );

$OutputFormatOption = array ( 'auto', 'line', 'long', 'extended');


function get_tit_col($run){
	
	/*
	proto      IP protocol
	srcip      Source IP address
	dstip      Destination IP address
	srcip4/net IPv4 source IP address with applied netmask
	srcip6/net IPv6 source IP address with applied netmask
	dstip4/net IPv4 destination IP address with applied netmask
	dstip6/net IPv6 destination IP address with applied netmask
	srcnet     Apply netmask srcmask in netflow record for source IP
	dstnet     Apply netmask dstmask in netflow record for dest IP
	srcport    Source port
	dstport    Destination port
	srcmask    Source mask
	dstmask    Destination mask
	srcvlan    Source vlan label
	dstvlan    Destination vlan label
	srcas      Source AS number
	dstas      Destination AS number
	inif       SNMP input interface number
	outif      SNMP output interface number
	next       IP next hop
	bgpnext    BGP next hop
	insrcmac   In source MAC address
	outdstmac  out destination MAC address
	indstmac   In destintation MAC address
	outsrcmac  Out source MAC address
	tos        Source type of service
	srctos     Source type of Service
	dsttos     Destination type of Service
	mpls1      MPLS label 1
	mpls2      MPLS label 2
	mpls3      MPLS label 3
	mpls4      MPLS label 4
	mpls5      MPLS label 5
	mpls6      MPLS label 6
	mpls7      MPLS label 7
	mpls8      MPLS label 8
	mpls9      MPLS label 9
	mpls10     MPLS label 10
	*/
	
	$titcol = _("IP Addr");
	 
	$patterns[] = array("pattern" => "/\ssrcip /",                  "column" => _("Src IP"));
	$patterns[] = array("pattern" => "/\sdstip/",                   "column" => _("Dst IP"));
	$patterns[] = array("pattern" => "/\ssrcport/",                 "column" => _("Src Port"));
	$patterns[] = array("pattern" => "/\sdstport/",                 "column" => _("Dst Port"));
	$patterns[] = array("pattern" => "/\sport/",                    "column" => _("Port"));
	$patterns[] = array("pattern" => "/\s(if|inif|outif)/",         "column" => _("IF"));
	$patterns[] = array("pattern" => "/srcmac/",                    "column" => _("Src MAC"));
	$patterns[] = array("pattern" => "/dstmac/",                    "column" => _("Dst MAC"));
	$patterns[] = array("pattern" => "/inmac|outmac/",              "column" => _("MAC"));
	$patterns[] = array("pattern" => "/\s(as|srcas|dstas)/",        "column" => _("AS"));
	$patterns[] = array("pattern" => "/\sproto/",                   "column" => _("Proto ID"));
	$patterns[] = array("pattern" => "/\sdir/",                     "column" => _("Dir"));
	$patterns[] = array("pattern" => "/\s(srctos|dsttos|tos)/",     "column" => _("Type Of Service"));
	$patterns[] = array("pattern" => "/\s(vlan|srcvlan|dstvlan)/",  "column" => _("VLAN"));
	$patterns[] = array("pattern" => "/\s(mask|srcmask|dstmask)/",  "column" => _("Mask"));
	$patterns[] = array("pattern" => "/\s(mapls)/",                 "column" => _("MPLS"));
	
	
	
	
	foreach ($patterns as $p_key => $p_data){
		
		if ( preg_match($p_data["pattern"], $run) )
		{
			$titcol = $p_data["column"];
			break;
		}
	}
	
	return $titcol;
}

function TimeSlotUpdate ($detail_opts) {

	global $WinSizeScale;
	global $RRDoffset;

	if ( isset($_POST['tend']) ) {
		$_SESSION['tend'] = Util::htmlentities($_POST['tend']);
	}

	// make sure the tend mark is with the profile data
	if ( ($_SESSION['tend'] > $_SESSION['profileinfo']['tend']) ||
		 ($_SESSION['tend'] < $_SESSION['profileinfo']['tstart']) ) 
		$_SESSION['tend'] = $_SESSION['profileinfo']['tend'];

	/* 
	 * scale factor of the graph. The unit is number of 5min slots/px
	 * WinSizeScale defines all available scales. 
	 * The default for the graphs is 1 day => scale = 0.5
	 */
	$scale  = $WinSizeScale[$detail_opts['wsize']];
	$full_range = $scale * 172800;  // a common product

	/*
	 * start of the graph
	 */
	$_SESSION['tstart'] = $_SESSION['tend'] - $full_range;	// see scale factor

	/*
	 * Mark update: The tleft/tright markers are set by the following user inputs:
	 * - click into the graph
	 * - adjust graph by the button controls '>' ">>" etc.
	 * - enter the tleft marker into text field
	 */

	// process adjust buttons
	if ( isset($_POST['adjust']) ) {
		$_tmp = $_POST['adjust'];
		switch($_tmp) {
			case " > ":
				if ( ($_SESSION['tright'] + 300 ) <= $_SESSION['profileinfo']['tend']) {
					$_SESSION['tleft']  += 300;	// increase one 5 min slice
					$_SESSION['tright'] += 300;	// increase one 5 min slice
				} 
				break;
			case " < ":
				if ( ($_SESSION['tleft'] - 300 ) >= $_SESSION['profileinfo']['tstart']) {
					$_SESSION['tleft']  -= 300;	// decrease one 5 min slice
					$_SESSION['tright'] -= 300;	// decrease one 5 min slice
				} 
				break;
			case " << ":
				// check if shift is within profile else move to end of profile
				if ( ($_SESSION['tleft'] - $full_range) >= $_SESSION['profileinfo']['tstart'] ) 
					$_tmp = $full_range;
				else
					$_tmp = $_SESSION['tleft'] - $_SESSION['profileinfo']['tstart'];

				$_SESSION['tleft']	-= $_tmp;
				$_SESSION['tright']	-= $_tmp;
				$_SESSION['tstart'] -= $_tmp;
				$_SESSION['tend']	-= $_tmp;
				break;
			case " >> ":
				// check if shift is within profile else move to end of profile
				if ( ($_SESSION['tright'] + $full_range) <= $_SESSION['profileinfo']['tend'] ) {
					$_SESSION['tleft']	+= $full_range;
					$_SESSION['tright']	+= $full_range;
					$_SESSION['tstart'] += $full_range;
					$_SESSION['tend']	+= $full_range;
				} else {
					// move to end of profile - same as '>|'
					$_SESSION['tend']	= $_SESSION['profileinfo']['tend'];
					$_tmp = $_SESSION['profileinfo']['tend'] - $_SESSION['tright'];
					$_SESSION['tright']	= $_SESSION['profileinfo']['tend'];
					$_SESSION['tleft']	= $_SESSION['tleft'] + $_tmp;
					$_SESSION['tstart'] = $_SESSION['tend'] - $full_range;	// see scale
				}

				break;
			case " >| ":
					// move to end of profile
					$_SESSION['tend']	= $_SESSION['profileinfo']['tend'];
					$_tmp = $_SESSION['profileinfo']['tend'] - $_SESSION['tright'];
					$_SESSION['tright']	= $_SESSION['profileinfo']['tend'];
					$_SESSION['tleft']	= $_SESSION['tleft'] + $_tmp;
					$_SESSION['tstart'] = $_SESSION['tend'] - $full_range;	// see scale
				break;
			case " | ":
				// center tleft, if possible, otherwise move as much as we can
				$_tmp = ($full_range - ( $_SESSION['tright'] - $_SESSION['tleft']) ) >>1;
				// shift only multiple of 5min units
				$_tmp -= $_tmp % 300;
				$_SESSION['tend'] = min($_SESSION['tright'] + $_tmp, $_SESSION['profileinfo']['tend']);
				$_SESSION['tstart'] = $_SESSION['tend'] - $full_range;
				break;
			case " ^ ":
				$max_timeslot = FindMaxValue();
				if ( $max_timeslot ) {
					$_SESSION['tleft']  = $max_timeslot;
					$_SESSION['tright'] = $max_timeslot;
				} else {
					SetMessage('error', "Could not find max time slot");
				}
				break;
		}
	}

	// process tleft/tright set by js or bookmark
	if ( isset($_POST['tleft']) ) {
		$_tmp = $_POST['tleft'];
		if ( is_numeric($_tmp) ) {
			// make sure we fall at the beginning of a 5min slot
			$_SESSION['tleft'] = $_tmp - ($_tmp % 300);

			// if tleft is outside the profile, set tleft to end of profile
			if ( ($_SESSION['tleft'] < $_SESSION['tstart'])  || 
				 ($_SESSION['tleft'] > $_SESSION['tend'])) {
				$_SESSION['tleft'] = $_SESSION['tstart'];
				SetMessage('error', "Mark outside available timeframe");
			}
		}
	}

	if ( $_SESSION['tleft'] < $_SESSION['profileinfo']['tstart'] )
		$_SESSION['tleft'] = $_SESSION['profileinfo']['tstart'];

	if ( isset($_POST['tright']) ) {
		$_tmp = $_POST['tright'];
		if ( is_numeric($_tmp) ) {
			// make sure we fall at the beginning of a 5min slot
			$_SESSION['tright'] = $_tmp - ( $_tmp % 300);

			// if tright is outside the profile, reset tright to tleft + 300
			if ( ($_SESSION['tright'] < $_SESSION['tstart'])  || 
				 ($_SESSION['tright'] > $_SESSION['tend'])) {
				$_SESSION['tright']	= $_SESSION['tleft'];
				SetMessage('error', "Mark outside available timeframe");
			}
		}
	}


	// make sure right > left
	if ( $_SESSION['tright'] < $_SESSION['tleft'] ) {
		$_SESSION['tright'] = $_SESSION['tleft'];
	}

	/*
	* Follow me:
	* If the tleft is within 10% of the left or right margin
	* adjust the graph and move the tleft into the middle of
	* the graph
	*/
	$margin = 8640 * $scale;	// 10% of graph
	$offset = ($full_range - ( $_SESSION['tright'] - $_SESSION['tleft']) ) >>1;
	// shift only multiple of 5min units
	$offset -= $offset % 300;
	if ((($_SESSION['tright'] > ($_SESSION['tend'] - $margin)) ||
		 ($_SESSION['tleft'] < ($_SESSION['tstart'] + $margin))) && 
		(($_SESSION['tright'] + $offset) < $_SESSION['profileinfo']['tend']) ) {
		$_SESSION['tend'] = $_SESSION['tright'] + $offset;
		$_SESSION['tstart'] = $_SESSION['tend'] - $full_range;
	} 

} // End of TimeSlotUpdate

function channellist_validate (&$channellist, $opts) {

	if ( empty($channellist) ) 
		return 0;

	$_channels = array();
	foreach ( explode('!', $channellist) as $channel ) {
		if ( !array_key_exists($channel, $_SESSION['profileinfo']['channel']) ) {
			SetMessage('warning', "Requested channel '$channel' does not exists in this profile");
		} else {
			$_channels[] = $channel;
		}
	}

	$channellist = implode('!', $_channels);
	return 0;

} // End of channellist_validate

function srcselector_validate (&$channel_array, $opts) {

	$_channels = array();

	$channel_array = (is_array($channel_array))	? $channel_array : array();
	foreach ( $channel_array as $channel ) {
		if(empty($channel)) continue;
		if ( !array_key_exists($channel, $_SESSION['profileinfo']['channel']) ) {
			SetMessage('warning', "Requested channel '$channel' does not exists in this profile");
		} else {
			$_channels[] = $channel;
		}
	}
	$channel_array = $_channels;
	if ( count($channel_array) == 0 ) {
		SetMessage('warning', "At least one channel is required");
		return 1;
	} else {
		return 0;
	}

} // End of srcselector_validate

function get_channel_list()
{
	$_channels = array();
	foreach ( array_keys($_SESSION['profileinfo']['channel']) as $channel ) 
	{
		$_channels[] = $channel;
	}

	return $_channels;
}

function Process_Details_tab ($tab_changed, $profile_changed) {

	global $DisplayOrder;
	global $TypeOrder;
	global $ListNOption;
	global $TopNOption;
	global $IPStatOption;
	global $IPStatOrder;
	global $WinSizeScale;
	global $LimitScale;
	global $OutputFormatOption;

	$_SESSION['refresh'] = 0;
	unset($_SESSION['run']);

	if ( $profile_changed || $tab_changed) {
		unset($_SESSION['detail_opts']);
	}

	// register 'get-detailsgraph' command for rrdgraph.php
	if ( !array_key_exists('rrdgraph_cmds', $_SESSION) || 
		 !array_key_exists('get-detailsgraph', $_SESSION['rrdgraph_cmds']) ) {
		$_SESSION['rrdgraph_cmds']['get-detailsgraph'] = 1;
		$_SESSION['rrdgraph_getparams']['profile'] = 1;
	} 

	// process channels. 'channeltrigger' is a hidden field, always present to
	// trigger this block
	// put a compiled list into the POST array, which is scanned afterwards

	if ( isset($_POST['srctrigger']) ) 
	{
		if ( isset($_POST['MultipleSources']) ) 
		{
			if ( $_POST['MultipleSources'] == 'All' ) 
			{
				$_channels = get_channel_list();

				if(!empty($_channels))
				{
					$_POST['channellist'] = implode('!', $_channels);
				}
				else
				{
					$_POST['channellist'] = '';
				}
				
			} 
			else 
			{
				$_POST['channellist'] = '';
			}

		} 
		else 
		{
			$_channels = array();
			foreach ( array_keys($_SESSION['profileinfo']['channel']) as $channel ) 
			{
				if ( array_key_exists($channel, $_POST) ) 
				{
					$_channels[] = $channel;
				}
			}
			
			$_POST['channellist'] = implode('!', $_channels);
		}
	}

	// move type from URL get to POST
	if ( isset($_GET['type']) ) {
		$_POST['type'] = Util::htmlentities($_GET['type']);
	}

	if ( isset($_GET['proto']) ) {
		$_POST['proto'] = Util::htmlentities($_GET['proto']);
	}

	if ( isset($_COOKIE['statpref']) ) {
		$_POST['statpref'] = Util::htmlentities($_COOKIE['statpref']);
	}

	if ( isset($_COOKIE['statvisible']) ) {
		$_POST['statvisible'] = Util::htmlentities($_COOKIE['statvisible']);
	}

	// to get the defaults in the parse_opts array
	if ( $tab_changed || $profile_changed ) {
		unset($_SESSION['detail_opts']);
		$detail_opts = array();
	} else {
		$detail_opts = array_key_exists('detail_opts', $_SESSION) ?  $_SESSION['detail_opts'] : array();
	}

	//getting default channel list
	if(!empty($detail_opts['channellist']))
	{
		$default_chanellist = $detail_opts['channellist'] ;
	}
	else
	{
		$_channels          = get_channel_list();
		$default_chanellist = implode('!', $_channels);
	}
 

	// process form data
	$parse_opts = array( 
		// type
		"type" 		=> array( "required" => 0, 
							  "default"  => array_key_exists('type', $detail_opts) ? $detail_opts['type'] : 'flows', 
							  "allow_null" => 0,
							  "match" => $TypeOrder , 
							  "validate" => NULL),
		// proto
		"proto" 	=> array( "required" => 0, 
							  "default"  => array_key_exists('proto', $detail_opts) ? $detail_opts['proto'] : 'any', 
							  "allow_null" => 0,
							  "match" => $DisplayOrder , 
							  "validate" => NULL),
		// wsize
		"wsize" 	=> array( "required" => 0, 
							  "default"  => array_key_exists('wsize', $detail_opts) ? $detail_opts['wsize'] : 1, 
							  "allow_null" => 0,
							  "match" => range(0, count($WinSizeScale)-1) , 
							  "validate" => NULL),

		// ratescale - absolute or per sec values
		"ratescale" => array( "required" => 0, 
							  "default"  => array_key_exists('ratescale', $detail_opts) ? $detail_opts['ratescale'] : 1, 
							  "allow_null" => 0,
							  "match" => array( 0, 1 ), 
							  "validate" => NULL),

		// logscale - lin/log display
		"logscale" 	=> array( "required" => 0, 
							  "default"  => array_key_exists('logscale', $detail_opts) ? $detail_opts['logscale'] : 0, 
							  "allow_null" => 0,
							  "match" => array( 0, 1 ), 
							  "validate" => NULL),

		// linegraph - line/stacked graphs
		"linegraph" => array( "required" => 0, 
							  "default"  => array_key_exists('linegraph', $detail_opts) ? $detail_opts['linegraph'] : 0, 
							  "allow_null" => 0,
							  "match" => array( 0, 1 ), 
							  "validate" => NULL),

		// linegraph - line/stacked graphs
		"cursor_mode" => array( "required" => 0, 
								"default"  => array_key_exists('cursor_mode', $detail_opts) ? 
												$detail_opts['cursor_mode'] : 1, 
							    "allow_null" => 0,
							    "match" => array( 0, 1 ), 
								"validate" => NULL),

		// list of displayed channels in graphs
		"channellist" => array( "required" => 0, 
								"default"  =>  $default_chanellist ,
								"allow_null" => 1,
								"match" => "/^[a-zA-Z0-9\-\!]*$/" , 
								"validate" => 'channellist_validate'),

		// 
		"statpref" => array( "required" => 0, 
								"default"  => '0:0:0',
								"allow_null" => 0,
								"match" => "/^[0-5]:[0-5]:[0-5]$/" , 
								"validate" => NULL),

		// 
		"statvisible" => array( "required" => 0, 
								"default"  => 1,
								"allow_null" => 0,
								"match" => array( 0, 1 ),
								"validate" => NULL),

	);

	list ($detail_opts, $has_errors) = ParseForm($parse_opts);
	//if ( $has_errors )
	//	return;



	$_SESSION['detail_opts'] = $detail_opts;
	// for bookmarking only
	$_SESSION['channellist'] = $detail_opts['channellist'];

	// set cookie for javascript functions
	$_COOKIE['statpref'] = $detail_opts['statpref'];
	$_COOKIE['statvisible'] = $detail_opts['statvisible'];


	if ( ( $tab_changed || $profile_changed) || isset($_POST['wsize']) ||
		(!isset($_SESSION['tend']) || !isset($_SESSION['tleft']) || !isset($_SESSION['tright']) )) 
    {
		
		$_SESSION['tend'] = $_SESSION['profileinfo']['tend'];
		
		if (($_SESSION['tend'] - 43200) < $_SESSION['profileinfo']['tstart'] )
		{
			// the middle of the graph is outside the profile, so set the mark 
			// to the beginning of the profile
			$_SESSION['tleft']  = $_SESSION['profileinfo']['tstart'];	
		}
		else
		{
    		$scale  = $WinSizeScale[$detail_opts['wsize']];
			// set the tleft to the middle of the graph
			$_SESSION['tleft']  = $_SESSION['tend'] - (14400 * $scale);
		}
		
		//$_SESSION['tright'] = $_SESSION['tleft'];
        $_SESSION['tright'] = $_SESSION['tend'];

		if ( !array_key_exists('DefaultFilters', $_SESSION ) )
			DefaultFilters();

		$_SESSION['process_form'] = array();
		
	}

	TimeSlotUpdate($detail_opts);

	// process the input data from the netflow processing form

	// to get the defaults in the parse_opts array
	if ( array_key_exists('process', $_POST) ) {
		$process_form = array();
	}
	else {
		$process_form = array_key_exists('process_form', $_SESSION) ?  $_SESSION['process_form'] : array();
	}


	$default_selector = array();
	if(is_array($process_form['srcselector']))
	{
		$default_selector = $process_form['srcselector'];
	}
	else{
		if(!empty($process_form['srcselector']))
		{
			$default_selector[] = $process_form['srcselector'];
		}
	}
	if( empty($default_selector) )
	{
		if(!empty($detail_opts['channellist']))
		{
			$default_selector = explode('!', $detail_opts['channellist']);
		}
	}

	$parse_opts = array( 
		"modeselect" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('modeselect', $process_form) ? 
										$process_form['modeselect'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => array( 0, 1),
							  	  "validate" => NULL),
		"srcselector"	=> array( "required" => 0, 
								  "default"  => $default_selector,
							  	  "allow_null" => 1,
					  		  	  "match" 	 => null,
							  	  "validate" => 'srcselector_validate'),
		"DefaultFilter" => array( "required" => 0, 
							  	  "default"  => -1,
							  	  "allow_null" => 0,
							  	  "match" => array_merge ( 
										array(-1), array_keys($_SESSION['DefaultFilters'])),
							  	  "validate" => NULL),
		"filter"		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('filter', $process_form) ? 
										implode("\n", $process_form['filter']) : NULL,
							  	  "allow_null" => 1,
					  		  	  "match" => "/^[\s!-~]*$/", 
							  	  "validate" => 'filter_validate'),
		"filter_name" 	=> array( "required" => 0, 
								  "default"  => '',
								  "allow_null" => 0,
								  "match" => "/^$|^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
								  "validate" => NULL),
		"filter_edit" 	=> array( "required" => 0, 
								  "default"  => null,
								  "allow_null" => 1,
								  "match" => array_merge ( array (null), array_keys($_SESSION['DefaultFilters'])),
								  "validate" => NULL),
		"filter_delete" => array( "required" => 0, 
								  "default"  => null,
								  "allow_null" => 1,
								  "match" => array_merge ( array (null), array_keys($_SESSION['DefaultFilters'])),
								  "validate" => NULL),
		"listN" 		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('listN', $process_form) ?
										$process_form['listN'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => range(0, count($ListNOption)-1) , 
							  	  "validate" => NULL),
		"aggr_bidir" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_bidir', $process_form) ?
										$process_form['aggr_bidir'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_proto" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_proto', $process_form) ?
										$process_form['aggr_proto'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_srcip" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_srcip', $process_form) ?
										$process_form['aggr_srcip'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_srcport" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_srcport', $process_form) ?
										$process_form['aggr_srcport'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_dstip" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_dstip', $process_form) ?
										$process_form['aggr_dstip'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_dstport" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_dstport', $process_form) ?
										$process_form['aggr_dstport'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"aggr_srcselect" => array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_srcselect', $process_form) ?
										$process_form['aggr_srcselect'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => array( 0, 1, 2 ),
							  	  "validate" => NULL),
		"aggr_dstselect" => array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_dstselect', $process_form) ?
										$process_form['aggr_dstselect'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => array( 0, 1, 2 ),
							  	  "validate" => NULL),
		"aggr_srcnetbits" => array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_srcnetbits', $process_form) ?
										$process_form['aggr_srcnetbits'] : 24,
							  	  "allow_null" => 0,
							  	  "match" => "/^[0-9]+$/" , 
							  	  "validate" => NULL),
		"aggr_dstnetbits" => array( "required" => 0, 
							  	  "default"  => array_key_exists('aggr_dstnetbits', $process_form) ?
										$process_form['aggr_dstnetbits'] : 24,
							  	  "allow_null" => 0,
							  	  "match" => "/^[0-9]+$/" , 
							  	  "validate" => NULL),
		"timesorted" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('timesorted', $process_form) ?
										$process_form['timesorted'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"IPv6_long" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('IPv6_long', $process_form) ?
										$process_form['IPv6_long'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"output" 		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('output', $process_form) ?
										$process_form['output'] : 'extended',
							  	  "allow_null" => 0,
							  	  "match" => array_key_exists('formatlist', $_SESSION) ? 
										array_keys($_SESSION['formatlist']) : array('extended'),
							  	  "validate" => NULL),
		"customfmt" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('customfmt', $process_form) ?
										$process_form['customfmt'] : '',
							  	  "allow_null" => 1,
							  	  "match" => "/^$|^[\s!-~]+$/",
							  	  "validate" => NULL),
		"fmt_save" 		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('fmt_save', $process_form) ?
										$process_form['fmt_save'] : '',
							  	  "allow_null" => 0,
							  	  "match" => "/^$|^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
							  	  "validate" => NULL),
		"fmt_delete" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('fmt_delete', $process_form) ?
										$process_form['fmt_delete'] : '',
							  	  "allow_null" => 0,
							  	  "match" => "/^$|^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
							  	  "validate" => NULL),
		// stat type inputs
		"topN" 			=> array( "required" => 0, 
							  	  "default"  => array_key_exists('topN', $process_form) ?
										$process_form['topN'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => range(0, count($TopNOption)-1) , 
							  	  "validate" => NULL),
		"stattype" 		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('stattype', $process_form) ?
										$process_form['stattype'] : 1,
							  	  "allow_null" => 0,
							  	  "match" => range(0, count($IPStatOption)-1) , 
							  	  "validate" => NULL),
		"statorder" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('statorder', $process_form) ?
										$process_form['statorder'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => range(0, count($IPStatOrder)-1) , 
							  	  "validate" => NULL),
		"limitoutput" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('limitoutput', $process_form) ?
										$process_form['limitoutput'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( '', 'checked' ),
							  	  "validate" => NULL),
		"limitwhat" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('limitwhat', $process_form) ?
										$process_form['limitwhat'] : '',
							  	  "allow_null" => 0,
							  	  "match" => array( 0, 1),
							  	  "validate" => NULL),
		"limithow" 		=> array( "required" => 0, 
							  	  "default"  => array_key_exists('limithow', $process_form) ?
										$process_form['limithow'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => array( 0, 1),
							  	  "validate" => NULL),
		"limitsize" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('limitsize', $process_form) ?
										$process_form['limitsize'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => "/^[0-9]+$/" , 
							  	  "validate" => NULL),
		"limitscale" 	=> array( "required" => 0, 
							  	  "default"  => array_key_exists('limitscale', $process_form) ?
										$process_form['limitscale'] : 0,
							  	  "allow_null" => 0,
							  	  "match" => range(0, count($LimitScale)-1) , 
							  	  "validate" => NULL),

	);

	list ($process_form, $has_errors) = ParseForm($parse_opts);
	$_SESSION['process_form'] = $process_form;

	//if ( $has_errors )
	//	return;

	if ( array_key_exists('fmt_save', $_POST) ) {
		if (!get_magic_quotes_gpc()) {
   			$cmd_opts['formatdef'] = addslashes($process_form['customfmt']);
		} else {
   			$cmd_opts['formatdef'] = $process_form['customfmt'];
		}
		$cmd_opts['format'] = $process_form['fmt_save'];
		$cmd_opts['overwrite'] = 1;
		$cmd_out = nfsend_query("add-format", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			unset($_SESSION['formatlist']);
			$_SESSION['process_form']['output'] = $process_form['fmt_save'];
		}
	} 

	if ( array_key_exists('fmt_delete', $_POST) ) {
		$_tmp = Util::htmlentities($_POST['fmt_delete']);
		if ( array_key_exists($_tmp, $OutputFormatOption)) {
			SetMessage('error', "Can not delete built in format '$_tmp'");
		} else if ( !array_key_exists($_tmp, $_SESSION['formatlist'])) {
			SetMessage('error', "Unknon format '$_tmp'");
		} else {
			$cmd_opts['format'] = $_tmp;
			$cmd_out =  nfsend_query("delete-format", $cmd_opts, 0);
			unset($_SESSION['formatlist']);
			$_SESSION['process_form']['output'] = $parse_opts['output']['default'];
		}
	}
	
	if ( !array_key_exists('formatlist', $_SESSION) ) {
		foreach ( $OutputFormatOption as $opt ) {
			$_SESSION['formatlist'][$opt] = $opt;
		}
		$cmd_out =  nfsend_query("get-formatlist", array(), 0);
		if ( is_array($cmd_out) ) {
			foreach ( $cmd_out as $key => $value ) 
				$_SESSION['formatlist'][$key] = $value;
		}
		$_SESSION['formatlist']['custom ...'] = 0;
	}

	if ( array_key_exists('filter_save_x', $_POST) ) {
		$cmd_opts['filtername'] = $process_form['filter_name'];
		$cmd_opts['overwrite'] = 1;
		$cmd_opts['filter'] = $process_form['filter'];
		$cmd_out = nfsend_query("add-filter", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			unset($_SESSION['DefaultFilters']);
			$_SESSION['process_form']['DefaultFilter'] = -1;
			$_SESSION['process_form']['filter'] = array();
		}
	}

	if ( array_key_exists('filter_edit_x', $_POST) ) {
		$cmd_opts['filter'] = $process_form['filter_name'];
		$cmd_out = nfsend_query("get-filter", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			$_SESSION['process_form']['editfilter'] = $cmd_out['filter'];
		}
		$_SESSION['anchor'] = '#processing';
	}

	if ( array_key_exists('filter_delete_x', $_POST) ) {
		$cmd_opts['filtername'] = $process_form['filter_name'];
		$cmd_out = nfsend_query("delete-filter", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			unset($_SESSION['DefaultFilters']);
			$_SESSION['process_form']['DefaultFilter'] = -1;
		}
	}

	if ( ( count(array_diff($_SESSION['process_form']['filter'], $_SESSION['auto_filter'] ) ) == 0  ) ) {
		if ( $_SESSION['detail_opts']['proto'] == 'any' ) {
			$_SESSION['process_form']['filter'] = array ();
		} else if ( $_SESSION['detail_opts']['proto'] == 'other' ) {
			$_SESSION['process_form']['filter'] = array ( 'not (proto tcp or proto udp or proto icmp or proto icmp6)' );
		} else {
			$_SESSION['process_form']['filter'] = array ( 'proto ' . $_SESSION['detail_opts']['proto'] );
		}
		$_SESSION['auto_filter'] = $_SESSION['process_form']['filter'];
	} else {
		$_SESSION['auto_filter'] = array();
	}

	DefaultFilters();
	if ( array_key_exists('process', $_POST) ) {
		$run = CompileCommand($process_form['modeselect']);
		$_SESSION['anchor'] = '#processing';
	} else
		$run = null;

	$_SESSION['run'] = $run;

} // End of Process_Details_tab

function DisplayDetails () {
	
	global $self;

	global $DisplayOrder;
	global $TypeOrder;
	global $WinSizeLabels;
	global $RRDoffset;
	global $GMToffset;
	global $TZname;

	require_once 'av_init.php';
	
	$db_aux = new ossim_db();
	$conn_aux = $db_aux->connect();
	
?>
    <script language="Javascript" src="js/detail.js" type="text/javascript">
    </script>
<?php

	$tright       = UNIX2DISPLAY($_SESSION['tright']);
	$tleft        = UNIX2DISPLAY($_SESSION['tleft']);
	$profile      = $_SESSION['profile'];
	$profilegroup = $_SESSION['profilegroup'];
	$detail_opts  = $_SESSION['detail_opts'];
	
	$is_shadow    = ($_SESSION['profileinfo']['type'] & 4) > 0;
	
	print "<h3>Profile: ".Util::htmlentities($profile)."</h3>\n";

	if($detail_opts['channellist'] != '') {
		$_tmp = explode('!', $detail_opts['channellist']);
	}
	else
	{
		$_tmp = array();
	}

	if(count($_tmp) == 0)
	{
		$graph_channels = '@EMPTY';
	}
	else
	{
		$graph_channels = '';

		foreach($_tmp as $_t)
		{
			$graph_channels .= $_t . ';' . Av_sensor::get_nfsen_channel_name($conn_aux, $_t) .':';
		}

		$graph_channels = preg_replace('/:$/', '', $graph_channels);
	}


?>
	<table style='font-size:14px;font-weight:bold;width:100%' class='transparent'>
	<tr>
<?php
	for ( $i=1; $i < count($DisplayOrder); $i++ ) {
		$label = $DisplayOrder[$i];
		if ( $label == $detail_opts['proto'] )
			$label = 'any';

		print "<td>";
		print $label;
		print "</td>\n";
	}
	print "<td style='font-size:14px;font-weight:bold'>".gettext("Profile info:")."</td>\n";	// Empty right element
	print "</tr><tr>\n";

	for ( $i=1; $i < count($DisplayOrder); $i++ ) {
		$label = $DisplayOrder[$i];
		if ( $label == $detail_opts['proto'] )
			$label = 'any';
		print "<td>\n";

		if($graph_channels != '@EMPTY')
		{
			$arg = implode ( " ", array( $graph_channels, $label, 
			$detail_opts['type'], $_SESSION['profileinfo']['tstart'], $_SESSION['tstart'], 
			$_SESSION['tend'], $_SESSION['tleft'], $_SESSION['tright'], 288, 100, 1, 
			$detail_opts['logscale'], $detail_opts['linegraph']));
			$arg = urlencode($arg);
		}
		else
		{
			$arg = '';
		}
		
		print "<a href='$self&proto=$label'> " .  
			"<img src=rrdgraph.php?cmd=get-detailsgraph&profile=" . Util::htmlentities($_SESSION['profileswitch']) . 
				"&arg=$arg border='0' width='165' height='81' alt='$label'></a>\n";
		print "</td>\n";
	}
	print "<td style='vertical-align: bottom;'>\n";
	$str = strftime("%b %d %Y - %H:%M", $_SESSION['profileinfo']['tstart']);

	$expire = $_SESSION['profileinfo']['expire'];
	if ( $expire == 0 ) {
		$expire = 'never';
	} else {
		$d = (int)($expire / 24 );
		$h = $expire % 24;
		$d_ext = $d == 1 ? '' : 's';
		$h_ext = $h == 1 ? '' : 's';
		if ( $d ) {
			$expire = "$d day${d_ext} $h hour{$h_ext}";
		} else {
			$expire = "$h hour${h_ext}";
		}
	}

	$maxsize = ScaleBytes($_SESSION['profileinfo']['maxsize'], 1, 1024.0);
	if ( $maxsize == 0 )
		$maxsize = 'unlimited';
	print "<table style='font-size:12px;font-weight:bold;border:none;width:100%'>\n";
	switch ( $_SESSION['profileinfo']['type'] & 3 ) {
		case 0:
			$type = 'live';
			break;
		case 1:
			$type = 'history';
			break;
		case 2:
			$type = 'continuous';
			break;
		default:
			$type = 'unknown';
	}
	$type .= $is_shadow  ? '&nbsp;/&nbsp;shadow' : '';

	print "<tr><td>Type:</td><td>$type</td></tr>";
	print "<tr><td>Max:</td><td>".Util::htmlentities($maxsize)."</td></tr>";
	print "<tr><td>Exp:</td><td>$expire</td></tr>";
	print "<tr><td>Start:</td><td>$str $TZname</td></tr>\n";
	$str = strftime("%b %d %Y - %H:%M", $_SESSION['profileinfo']['tend']);
	print "<tr><td style='border:none'>End:</td><td style='border:none'>$str $TZname</td></tr>\n";
	print "</table>\n";

	print "</td>\n";

	if($graph_channels != '@EMPTY')
	{
		$arg = implode ( " ", array( $graph_channels, 
				$detail_opts['proto'], $detail_opts['type'], $_SESSION['profileinfo']['tstart'], 
				$_SESSION['tstart'], $_SESSION['tend'], $_SESSION['tleft'], $_SESSION['tright'], 
				576, 200, 0, $detail_opts['logscale'], $detail_opts['linegraph']));
		$arg = urlencode($arg);
	}
	else
	{
		$arg = '';
	}
?>
	</tr>
	<tr>
		<td colspan='4' align="center"   valign="top">
			<br>
			<img id='MainGraph' style='position:relative; top:0px; left:0px;' onclick="DragCursor.set(event);" src=rrdgraph.php?cmd=get-detailsgraph&profile=<?php echo Util::htmlentities($_SESSION['profileswitch']); ?>&arg=<?php echo $arg?> border='0' alt='main-graph'>
			<img id="CursorDragHandle" style="position:absolute;display:none; " src="icons/cursor-line.png" alt="Line Cursor">
			<img id="StartDragHandle" 	style="position:absolute;display:none" src="icons/cursor-start.png" alt="Start Cursor">
			<div id="StartLine" style="position: absolute;display:none; width: 1px; height: 200px; background-color: black;"></div>
			<div id="SpanBox"  style="position:absolute;display:none; height: 200px; background-color: #B1FFA1; filter:alpha(opacity=40); opacity:.40;"></div>
			<div id="StopLine"  style="position: absolute;display:none; width: 1px; height: 200px; background-color: black;"></div>
			<img id="StopDragHandle"  	style="position:absolute;display:none" src="icons/cursor-stop.png" alt="Stop Cursor">
			<form style="display:inline;" name="slotselectform" id="slotselectform" action="<?php echo $self;?>" method="POST">
				<input type="hidden" name="cursor_mode" id="cursor_mode" value="">
				<input type="hidden" name="tleft" id="tleft" value="">
				<input type="hidden" name="tright" id="tright" value="">
			</form>
		</td>
		<td style="vertical-align: bottom;"> 
				<table style="margin-bottom:1pt;border:none;width:100%">
				<tr>
					<td><span style='font-size:14px;font-weight:bold;margin-bottom:1pt'>
							t<SUB><?=_("start")?></SUB>
						</span>
					</td>
					<td style="font-size:16px;color:#555555;">
						<!-- input type="text" name="box_tleft" id="box_tleft" value="" SIZE="16" MAXLENGTH="16" style='font-size:10px;' readonly -->
						<b id='box_tleft'>- <?=_("update")?> -</b>
					</td>
				</tr><tr>
					<td><span style='font-size:14px;font-weight:bold;margin-bottom:1pt'>
							t<SUB><?=_("end")?></SUB>
						</span>
					</td>
					<td style="font-size:16px;color:#555555;">
						<!-- input type="text" name="box_tright" id="box_tright" value="" SIZE="16" MAXLENGTH="16" style='font-size:10px;' readonly -->
						<b id='box_tright'>- <?=_("update")?> -</b>
					</td>
				</tr>
				</table>

		<table style='font-size:14px;font-weight:bold;border:none;width:100%'>
<?php
	for ( $i=1; $i < count($TypeOrder); $i++ ) {
		$label = $TypeOrder[$i];
		if ( $label == $detail_opts['type'] )
			$label = 'flows';
		// Make first letter uppercase of label to print
		$printlabel = strtoupper($label[0]).substr($label,1);
		print "<tr><td>".(($printlabel != "") ? gettext($printlabel) : "&nbsp;")."</td></tr>\n";
		print "<tr><td style='border:none'>\n";

		if($graph_channels != '@EMPTY')
		{
			$arg = implode ( " ", array( $graph_channels, 
					$detail_opts['proto'], $label, $_SESSION['profileinfo']['tstart'], 
					$_SESSION['tstart'], $_SESSION['tend'], $_SESSION['tleft'], 
					$_SESSION['tright'], 288, 100, 1, $detail_opts['logscale'], $detail_opts['linegraph']));
			$arg = urlencode($arg);
		}
		else
		{
			$arg = '';
		}
		print "<a href='$self&type=$label'> " .  "<img src=rrdgraph.php?cmd=get-detailsgraph&profile=" . 
			Util::htmlentities($_SESSION['profileswitch']) .  "&arg=$arg border='0' width='165' height='81' alt='$label'></a>";
		print "</td></tr>\n";
	}

?>
		</table>
		</td>
	</tr>
	<tr>

	<td colspan='4' style="border:none">
		<table border="0" style='width:100%;margin:0pt;border:none'>
		<tr>
		<td style="border:none">
			<?=_("Select")?>&nbsp;
			<select name="CursorMode" id="ModeSelector" onchange="SetCursorMode(<?php echo Util::htmlentities($_SESSION['tstart']) . ", " . Util::htmlentities($_SESSION['tend']). ", " . Util::htmlentities($_SESSION['profileinfo']['tstart']) . ", " . Util::htmlentities($_SESSION['tleft']). ", " . Util::htmlentities($_SESSION['tright']). ",576, $RRDoffset" ?>)" size=1>
<?php
			if ( $detail_opts['cursor_mode'] == 1 ) {
				print "<option value='0'>"._("Single Timeslot")."\n";
				print "<option value='1' selected>"._("Time Window")."\n";
			} else {
				print "<option value='0' selected>"._("Single Timeslot")."\n";
				print "<option value='1'>"._("Time Window")."\n";
			}
?>
			</select>
		</td>

		<td style="text-align: right;border:none">
			<?=_("Display")?>:&nbsp;
			<form action="<?php echo $self;?>" style="display:inline;" method="POST">
			<select name='wsize' onchange='this.form.submit();' size=1>
<?php
			$size_label = 'unknown';
			for ( $i = 0; $i < count($WinSizeLabels); $i++ ) {
				if ( $i == $detail_opts['wsize'] ) {
					print "<option value='$i' selected>" . $WinSizeLabels[$i] . "\n";
					$size_label = $WinSizeLabels[$i];
				} else {
					print "<option value='$i'>" . $WinSizeLabels[$i] . "\n";
				}
			}
			$status = $detail_opts['cursor_mode'] == 0 && !$is_shadow ? '' : 'disabled';
			$peak_search_label = $status == '' ? _("Search Peak") : _("Peak search not available");
			
?>
			</select>
			<input class="small av_b_secondary" name="adjust" value=" << " type="submit" title="<?=_("Back")?> <?php echo $size_label;?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" < " type="submit" title="<?=_("Previous time slot")?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" | " type="submit" title="<?=_("Center cursor")?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" ^ " type="submit" <?php echo $status; ?> title="<?php echo $peak_search_label?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" > " type="submit" title="<?=_("Next time slot")?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" >> " type="submit" title="<?=_("Forward")?> <?php echo $size_label;?>">&nbsp;
			<input class="small av_b_secondary" name="adjust" value=" >| " type="submit" title="<?=_("Goto last slot")?>">
			</form>
		</td>
		</tr>
		</table>
	</td> <td style="border:none">
		<form action="<?php echo $self;?>" method="POST">
			<table style="border:none;width:100%">
			<tr>
				<td style="border:none">
					<input type="radio" onClick='this.form.submit();' name="logscale" value="0"
					<?php if ( $detail_opts['logscale'] == 0 ) print "checked"; ?> ><?=_("Lin Scale")?>
				</td>
				<td style="border:none">
					<input type="radio" onClick='this.form.submit();' name="linegraph" value="0"
					<?php if ( $detail_opts['linegraph'] == 0 ) print "checked"; ?> ><?=_("Stacked Graph")?>
				</td>
			</tr>
			<tr>
				<td style="border:none">
					<input type="radio" onClick='this.form.submit();' name="logscale" value="1"
					<?php if ( $detail_opts['logscale'] == 1 ) print "checked"; ?> ><?=_("Log Scale")?>
				</td>
				<td style="border:none">
					<input type="radio" onClick='this.form.submit();' name="linegraph" value="1"
					<?php if ( $detail_opts['linegraph'] == 1 ) print "checked"; ?> ><?=_("Line Graph")?>
				</td>
			</tr>
			</table>

		</form>
	</td>
	</tr><tr>
		<td colspan='4' style="border:none"></td>
		<td style="border:none"></td>
	</tr>
	</table>
<?php
	if ( $_SESSION['tleft'] == $_SESSION['tright'] ) {
		$tslot = "timeslot " . strftime("%b %d %Y - %H:%M", $_SESSION['tleft']);
	} else {
		$tslot = "timeslot " . strftime("%b %d %Y - %H:%M", $_SESSION['tleft']) . 
			" - " . strftime("%b %d %Y - %H:%M", $_SESSION['tright']);
	}

	$num_channels = count(array_keys($_SESSION['profileinfo']['channel']));
	$proto_index = 1;
	foreach ( $DisplayOrder as $p ) {
		if ( $detail_opts['proto'] == $p ) {
			break;
		}
		$proto_index++;
	}

	$statpref = explode(':', $detail_opts['statpref']);
	$max_colspan = 0;
	for ( $i=0; $i<count($statpref); $i++ ) {
		$pref = $statpref[$i];
		if ( $pref == 0 ) {
			$arrow_style[] = '';
			$arrow_style[] = 'style="display:none;"';
			$colspan[] = 5;
			$max_colspan += 5;
		} else {
			$arrow_style[] = 'style="display:none;"';
			$arrow_style[] = '';
			$statpref[$i] = $proto_index;
			$colspan[] = 1;
			$max_colspan += 1;
		}
	}
	$_i = 0;

	if ( $detail_opts['statvisible'] == 1 ) {
		$stattable_style = '';
		$down_arrow_style  = '';
		$right_arrow_style = 'style="display:none"';
	} else {
		$stattable_style = 'display:none;';
		$down_arrow_style  = 'style="display:none"';
		$right_arrow_style = '';
	}

?>
	<br><br><b>
	<table style="width:100%" cellspacing='0' cellpadding='0'>
        <tr>
            <th class='thold'>
            	<a href="#null" onClick="ShowHideStat();" 
            		title="<?=_("Hide Statistic")?>" ><IMG SRC="icons/arrow.yellow.down.png" name="flows" id="stat_arrow_down" border="0"
            		<?php echo $down_arrow_style; ?> align="left" alt="<?=_("Hide stat table")?>"></a>
            	<a href="#null" onClick="ShowHideStat();" 
            		title="<?=_("Show Statistic")?>" ><IMG SRC="icons/arrow.yellow.right.png" name="flows" id="stat_arrow_right" border="0"
            		<?php echo $right_arrow_style; ?> align="left" alt="<?=_("Show stat table")?>"></a>
            	Statistics <?php echo $tslot;?></b>
            </th>
        </tr>
	</table>
	<form action="<?php echo $self;?>" method="POST" id="statsform">
	<input type="hidden" name="srctrigger" value="src">
	<table id="stattable" style='font-size:14px;font-weight:bold;<?php echo $stattable_style; ?>;border:none;width:100%' cellspacing='0' cellpadding='3'>
		<tr align='center'>
			<th class='theader' colspan="2"><?=_("Channel")?></th>
			<th class='theader' id="label0" colspan='<?php echo $colspan[0];?>'>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 0, <?php echo $proto_index; ?>);" 
					title="<?=_("Collapse")?>" ><IMG SRC="icons/arrow.blue.down.png" name="flows" id="arrow0_down" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 0, <?php echo $proto_index; ?>);" 
					title="<?=_("Expand")?>" ><IMG SRC="icons/arrow.blue.right.png" name="flows" id="arrow0_right" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a><?=_("Flows")?>
			</th>
			<th class='theader' id="label1" colspan='<?php echo $colspan[1];?>'>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 1, <?php echo $proto_index; ?>);" 
					title="<?=_("Collapse")?>" ><IMG SRC="icons/arrow.blue.down.png" name="flows" id="arrow1_down" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 1, <?php echo $proto_index; ?>);" 
					title="<?=_("Expand")?>" ><IMG SRC="icons/arrow.blue.right.png" name="flows" id="arrow1_right" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a><?=_("Packets")?>
			</th>
			<th class='theader' id="label2" colspan='<?php echo $colspan[2];?>'>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 2, <?php echo $proto_index; ?>);" 
					title="<?=_("Collapse")?>" ><IMG SRC="icons/arrow.blue.down.png" name="flows" id="arrow2_down" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a>
				<a href="#null" onClick="CollapseExpandStat(<?php echo $num_channels?>, 2, <?php echo $proto_index; ?>);" 
					title="<?=_("Expand")?>" ><IMG SRC="icons/arrow.blue.right.png" name="flows" id="arrow2_right" border="0"
					<?php echo $arrow_style[$_i++]; ?> align="left" alt="<?=_("collapse")?>"></a><?=_("Traffic")?>
			</th>
		</tr>
		<tr>
		<td colspan="2">&nbsp;</td>
<?php	
			for ( $i=0; $i<3; $i++ ) {
				$col = 1;
				foreach ( array( gettext("all:"), 'tcp:', 'udp:', 'icmp:', gettext("other:") ) as $_type ) {
					if ( $statpref[$i] == 0 ) {
						$_style = '';
					} else {
						if ( $statpref[$i] == $col ) 
							$_style = '';
						else
							$_style = 'style="display:none;"';
					}
					print "<td id='id.0.$i.$col' $_style>$_type</td>";
					$col++;
				}
			}
		print "</tr>\n";

	// for simplified testing
	foreach ( explode('!', $detail_opts['channellist']) as $channel ) {
		$_SelectedChannels[$channel] = 1;
	}

	foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
		$flows_sum[$_type] = 0;
		$packets_sum[$_type] = 0;
		$bits_sum[$_type] = 0;
	}

	$row     = 1;
	$rateval = 1;

	foreach ( $_SESSION['profileinfo']['channel'] as $channel ) {

		$ch_name  = $channel['name'];
		$ch_id    = $channel['id'];

		if( Session::am_i_admin() ) {
			$del_incon = "<img src='/ossim/vulnmeter/images/delete.gif' align='sub' onclick='remove_source(\"$ch_id\")' style='cursor:pointer;' border='0'>";
		}
		else {
			$del_incon = '';
		}
	
		print "<tr>\n";

		# channel exists in profile
		$statinfo = ReadStat($profile, $profilegroup, $ch_id);

		$rateval = 1;
		if ( $detail_opts['ratescale'] == 1 ) {
			$rateval = $_SESSION['tright'] - $_SESSION['tleft'] + 300;
		}

		$bgcolour = "bgcolor='" . Util::htmlentities($channel['colour']) . "'";
		print "<td $bgcolour style='width:10px'></td>\n";
		
		if ( array_key_exists($ch_id, $_SelectedChannels) ) {
			print "<td align=left><input type='checkbox' name='".Util::htmlentities($ch_id)."' value='".Util::htmlentities($ch_id)."' onClick='this.form.submit();' checked>$ch_name $del_incon</td>\n";
			$cellcolour = "bgcolor='#f2f2f2'";
		} else {
			print "<td align=left><input type='checkbox' name='".Util::htmlentities($ch_id)."' value='".Util::htmlentities($ch_id)."' onClick='this.form.submit();'>$ch_name $del_incon</td>\n";
			$cellcolour = '';
		}

		# print flows
		$i = 0;
		$col = 1;
		$mark_char = ($_SESSION['profileinfo']['type'] & 4 ) > 0 ? 'S' : 'x';
		foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
			if ( $statinfo ) {
				$_val = ScaleValue($statinfo['flows' . $_type], $rateval);
				$flows_sum[$_type] += $statinfo['flows' . $_type];
			} else {
				$_val = $mark_char;
			}
			if ( $statpref[$i] == 0 ) {
				$_style = '';
			} else {
				if ( $statpref[$i] == $col ) 
					$_style = '';
				else
					$_style = 'style="display:none;"';
			}
			print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
			$col++;
		}


		# print packets
		$i = 1;
		$col = 1;
		foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
			if ( $statinfo ) {
				$_val = ScaleValue($statinfo['packets' . $_type], $rateval);
				$packets_sum[$_type] += $statinfo['packets' . $_type];
			} else {
				$_val = $mark_char;
			}
			if ( $statpref[$i] == 0 ) {
				$_style = '';
			} else {
				if ( $statpref[$i] == $col ) 
					$_style = '';
				else
					$_style = 'style="display:none;"';
			}
			print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
			$col++;
		}

		# prints bits
		$i = 2;
		$col = 1;
		foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
			if ( $statinfo ) {
				$_val = ScaleBytes($statinfo['traffic' . $_type], $rateval, 1000.0);
				$bits_sum[$_type] += $statinfo['traffic' . $_type];
			} else {
				$_val = $mark_char;
			}
			if ( $statpref[$i] == 0 ) {
				$_style = '';
			} else {
				if ( $statpref[$i] == $col ) 
					$_style = '';
				else
					$_style = 'style="display:none;"';
			}
			print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
			$col++;
		}
		print "</tr>\n";
		$row++;
	}

?>		
	<tr bgcolor='#dee7ec'>
	<td colspan="2">&nbsp;</td>
<?php
	# reprint the header as a footer
	for ( $i=0; $i<3; $i++ ) {
		$col = 1;
		foreach ( array( 'all:', 'tcp:', 'udp:', 'icmp:', 'other:' ) as $_type ) {
			if ( $statpref[$i] == 0 ) {
				$_style = '';
			} else {
				if ( $statpref[$i] == $col )
					$_style = '';
				else
					$_style = 'style="display:none;"';
			}
			print "<td id='id.$row.$i.$col' $_style>$_type</td>";
			$col++;
		}
	}
	print "</tr>\n";
	$row++;
?>
	<tr>
	<td colspan="2">TOTAL</td>
<?php
	# print flows
	$i = 0;
	$col = 1;
	$mark_char = ($_SESSION['profileinfo']['type'] & 4 ) > 0 ? 'S' : 'x';
	foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
		$_val = ScaleValue($flows_sum[$_type], $rateval);
		if ( $statpref[$i] == 0 ) {
				$_style = '';
		} else {
				if ( $statpref[$i] == $col )
						$_style = '';
				else
						$_style = 'style="display:none;"';
		}
		print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
		$col++;
	}

	# print packets
	$i = 1;
	$col = 1;
	foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
		$_val = ScaleValue($packets_sum[$_type], $rateval);
		if ( $statpref[$i] == 0 ) {
				$_style = '';
		} else {
				if ( $statpref[$i] == $col )
						$_style = '';
				else
						$_style = 'style="display:none;"';
		}
		print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
		$col++;
	}
	
	# prints bits
	$i = 2;
	$col = 1;
	foreach ( array( '', '_tcp', '_udp', '_icmp', '_other' ) as $_type ) {
			$_val = ScaleBytes($bits_sum[$_type], $rateval, 1000.0);
			if ( $statpref[$i] == 0 ) {
					$_style = '';
			} else {
					if ( $statpref[$i] == $col )
							$_style = '';
					else
							$_style = 'style="display:none;"';
			}
			print "<td $cellcolour id='id.$row.$i.$col' $_style align=right>$_val&nbsp;</td>";
			$col++;
	}
	print "</tr>\n";
	$row++;
?>

		<tr><td colspan="2">
		<INPUT class="small av_b_secondary" TYPE="submit" NAME="MultipleSources" Value="<?=_("All")?>">
		<INPUT class="small av_b_secondary" TYPE="submit" NAME="MultipleSources" Value="<?=_("None")?>">
		</td>
		<td colspan='15' style='text-align: left;'>
		<?=_("Display")?>:
		<input type="radio" onClick='this.form.submit();' name="ratescale" value="0"
		<?php if ( $detail_opts['ratescale'] == 0 ) print "checked"; ?> ><?=_("Sum")?>
		<input type="radio" onClick='this.form.submit();' name="ratescale" value="1"
		<?php if ( $detail_opts['ratescale'] == 1 ) print "checked"; ?> ><?=_("Rate")?> &nbsp;
<?php 
		if ( !$statinfo ) {
			print "x: "._("No Data available");
		}
?>
		</td></tr>
	</table>
	</form>

	<style type="text/css">
		#av_msg_container {
			width: 60% !important;
		}
		
		.flowlist .tr_flow_data{
    		font-family: Courier, "Courier New", monospace !important;
        }
		
	</style>
    <script language="Javascript" type="text/javascript">

    function remove_source(id)
    {
    	if( confirm("<?php echo Util::js_entities( _('This Nfsen source is going to be deleted. This action cannot be undone. Are you soure?') ) ?>") )
    	{
	    	$.ajax({
				data:  {"action": 1, "data": {"sensor": id}},
				type: "POST",
				url: "nfsen_ajax.php", 
				dataType: "json",
				beforeSend: function () 
				{
					window.scrollTo(0, 0);

					var layer = "<div id='loading_container' style='width:100%;height:100%;position:absolute;top:0px;left:0px;'></div>";
	            	$('body').append(layer);
	            	
					show_loading_box('loading_container', '<?php echo Util::js_entities(_("Deleting Nfsen source...")) ?>', '');
				},
				success: function(data)
				{ 
					hide_loading_box();
					$('#loading_container').remove();
					if(data.error)
					{
						notify(data.msg, 'nf_error');
					} 
					else
					{
						notify(data.msg, 'nf_success');
						setTimeout("document.location.reload();", 1500);
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
				{
					hide_loading_box();
					$('#loading_container').remove();
					
					notify(textStatus, 'nf_error');
				}
			});
    	}
    }

	window.onload=function() {
		SetCookieValue("statvisible", <?php echo $detail_opts['statvisible'] ? 1 : 0 ?>);
		var curdate = new Date();
		GMToffset  = <?php echo $GMToffset;?> + curdate.getTimezoneOffset() * 60;
		CursorMode = <?php echo Util::htmlentities($detail_opts['cursor_mode']);?>;
		if ( CursorMode == 0 )
			SlotSelectInit(<?php echo preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tstart'])) . ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tend'])). ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['profileinfo']['tstart'])) . ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tleft'])). ",576, $RRDoffset" ?>);
		else
			WSelectInit(<?php echo preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tstart'])) . ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tend'])). ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['profileinfo']['tstart'])) . ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tleft'])) . ", " . preg_replace('/[,\)]/', '', Util::htmlentities($_SESSION['tright'])) . ",576, $RRDoffset" ?>);
<?php
	if ( array_key_exists('anchor', $_SESSION) ) {
		print "location.hash='" . Util::htmlentities($_SESSION['anchor']) . "';";
		unset($_SESSION['anchor']);
	}
?>
	}


    </script>

<?php
$db_aux->close($conn_aux);
} // End of DisplayDetails

function DisplayProcessing() 
{
	global $self;
	global $ListNOption;
	global $TopNOption;
	global $OutputFormatOption;
	global $IPStatOption;
	global $IPStatOrder;
	global $LimitScale;

	require_once 'av_init.php';
    
    $geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");
	    
	$db_aux   = new ossim_db();
	$conn_aux = $db_aux->connect(); 
	
	$aux_ri_interfaces = Remote_interface::get_list($conn_aux, "WHERE status = 1");
	$ri_list           = $aux_ri_interfaces[0];
	$ri_total          = $aux_ri_interfaces[1];
		    
	$ri_data = array();
    
    if ($ri_total > 0)
    {
        foreach($ri_list as $r_interface)
        {
            $ri_data[] = array(
                "name"   => $r_interface->get_name(),
                "id"     => "web_interfaces",
                "target" => "_blank",
                "url"    => $r_interface->get_ip()
            );
        }
    }
    
	
	
    $type = ($detail_opts['type']=="flows") ? 0 : ($detail_opts['type']=="packets" ? 1 : 2);

    if ($ri_total >= 0)
    {
        echo('<a name="processing"></a>');
    }
        
	$detail_opts  = $_SESSION['detail_opts'];
	$process_form = $_SESSION['process_form'];

?>
    <table style='width:100%;margin-top:15px;margin-bottom:5px;border:none'><tr>
    <td class='nobborder'><b><?=_("Netflow Processing")?></b></td>
    <td class='noborder nfsen_menu'>
        <a href='javascript:lastsessions()'><?=_("List last 500 sessions")?></a> |
        &nbsp;<a href='javascript:launch("2","<?=$type?>")'><?=_("Top 10 Src IPs")?></a> |
        &nbsp;<a href='javascript:launch("3","<?=$type?>")'><?=_("Top 10 Dst IPs")?></a> |
        &nbsp;<a href='javascript:launch("5","<?=$type?>")'><?=_("Top 10 Src Port")?></a> |
        &nbsp;<a href='javascript:launch("6","<?=$type?>")'><?=_("Top 10 Dst Port")?></a> |
        &nbsp;<a href='javascript:launch("13","<?=$type?>")'><?=_("Top 10 Proto")?></a>
    </td></tr></table>


<form action="<?php echo $self;?>" onSubmit="return ValidateProcessForm()" id="FlowProcessingForm" method="POST" laction="<?php echo $self;?>">
<?php
if(preg_match("/^\d+$/", $_SESSION['tend'])) { ?>
    <input type="hidden" name="tend" value="<?php echo intval($_SESSION['tend']);?>" />
<?php 
} 
if(preg_match("/^\d+$/", $_SESSION['tleft'])) { ?>
    <input type="hidden" name="tleft" value="<?php echo intval($_SESSION['tleft']);?>" />
<?php
}
if(preg_match("/^\d+$/", $_SESSION['tright'])) { ?>
    <input type="hidden" name="tright" value="<?php echo intval($_SESSION['tright']);?>" />
<?php
}
if( $_SESSION["detail_opts"]["cursor_mode"] != "" ) {?>
    <input type="hidden" name="cursor_mode" value="<?php echo Util::htmlentities($_SESSION["detail_opts"]["cursor_mode"]);?>" />
<?php
}
if( $_SESSION["detail_opts"]["wsize"] != "" ) {?>
    <input type="hidden" name="wsize" value="<?php echo Util::htmlentities($_SESSION["detail_opts"]["wsize"]);?>" />
<?php
}
if( $_SESSION["detail_opts"]["logscale"] != "" ) {?>
    <input type="hidden" name="logscale" value="<?php echo Util::htmlentities($_SESSION["detail_opts"]["logscale"]);?>" />
<?php
}
if( $_SESSION["detail_opts"]["linegraph"] != "" ) {?>
    <input type="hidden" name="linegraph" value="<?php echo Util::htmlentities($_SESSION["detail_opts"]["linegraph"]);?>" />
<?php
}
?>
<input type="hidden" name="login" value="<?php echo Util::htmlentities($_SESSION["_remote_login"])?>" />
<table class='nfsen_filters'>
	<tr>
		<th class="thold"><?php echo _("Source")?></th>
		<th class="thold"><?php echo _("Filter")?></th>
		<th class="thold"><?php echo _("Options")?></th>
	</tr>

	<tr>
		<td style='vertical-align:top'>
			<select name="srcselector[]" id='SourceSelector' size="6" style="width: 100%" multiple='multiple'>
			<?php
			foreach ( $process_form['srcselector'] as $selected_channel ) {
				$_tmp[$selected_channel] = 1;
			}
			$i = 0;
			foreach ( $_SESSION['profileinfo']['channel'] as $channel ) 
			{
				$channel_name = $channel['name'];
				$checked      = array_key_exists($channel['id'], $_tmp) ? 'selected' : '';

				echo "<OPTION value='". Util::htmlentities($channel['id']) ."' $checked>$channel_name</OPTION>\n";
			}
			?>
			</select>
			<div style='margin: 5px auto'>
				<input class="small av_b_secondary" type="button" name="JSbutton2" value="All Sources" onClick="SelectAllSources()"/>
			</div>
		</td>
	
		<td style="vertical-align:top;">
			<textarea name="filter" id="filter" multiline="true" wrap="phisical" rows="6" cols="50" maxlength="10240"><?php
				if (is_array($process_form))
					$display_filter = array_key_exists('editfilter', $process_form) ? $process_form['editfilter'] : $process_form['filter'];
				else
					$display_filter = array();
				if (count($display_filter) < 1 && GET('ip') != "" && GET('ip2') != "") {
					$display_filter[0] = "(src ip ".GET('ip')." and dst ip ".GET('ip2').") or (src ip ".GET('ip2')." and dst ip ".GET('ip').")";
				} elseif (count($display_filter) < 1 && GET('ip') != "") {
					$display_filter[0] = "src ip ".GET('ip')." or dst ip ".GET('ip');
				} elseif (preg_match("/(\d+\.\d+\.\d+\.\d+)/",$display_filter[0]) && GET('ip') != "" &&  GET('ip2') != "") {
					$ip1 = GET('ip'); $ip2 = GET('ip2');
					$filter = "(src ip $ip1 and dst ip $ip2) or (src ip $ip2 and dst ip $ip1)";
					$display_filter[0] = preg_replace("/\(src ip \d+\.\d+\.\d+\.\d+ and dst ip \d+\.\d+\.\d+\.\d+\) or \(src ip \d+\.\d+\.\d+\.\d+ and dst ip \d+\.\d+\.\d+\.\d+\)/",$filter,$display_filter[0]);
					$display_filter[0] = preg_replace("/src ip \d+\.\d+\.\d+\.\d+ or dst ip \d+\.\d+\.\d+\.\d+/",$filter,$display_filter[0]);
				} elseif (preg_match("/(\d+\.\d+\.\d+\.\d+)/",$display_filter[0]) && GET('ip') != "") {
					$filter = "src ip ".GET('ip')." or dst ip ".GET('ip');
					$display_filter[0] = preg_replace("/\(src ip \d+\.\d+\.\d+\.\d+ and dst ip \d+\.\d+\.\d+\.\d+\) or \(src ip \d+\.\d+\.\d+\.\d+ and dst ip \d+\.\d+\.\d+\.\d+\)/",$filter,$display_filter[0]);
					$display_filter[0] = preg_replace("/src ip \d+\.\d+\.\d+\.\d+ or dst ip \d+\.\d+\.\d+\.\d+/",$filter,$display_filter[0]);
				}
				foreach ( $display_filter as $line ) {
					print str_replace("&amp;", "&", Util::htmlentities(htmlspecialchars(stripslashes($line)))) . "\n";
				}
			?></textarea>
			<?php
			$deletefilter_display_style = (is_array($process_form) && array_key_exists('editfilter', $process_form)) ?
				'' : 'style="display:none;"';
			?>
			
			<input type="image" name="filter_delete" id="filter_delete" title="<?=_("Delete filter")?>" align="right"
				onClick="HandleFilter(3)" value="" src="icons/trash.png" <?php echo $deletefilter_display_style; ?>>
			<!-- <input type="image" name="filter_save" id="filter_save" title="Save filter" align="right"
				onClick="HandleFilter(2)" 
				value="" src="icons/save.png"> -->
			<input type="hidden" name="filter_name" id="filter_name" value="none">
			<div style='margin: 5px auto'>
				<span id="filter_span">and</span>
				<select name="DefaultFilter" id="DefaultFilter" onChange="HandleFilter(0)" size="1">
				<?php
				print "<option value='-1' label='none'>&lt;none&gt;</option>\n";
					foreach ( $_SESSION['DefaultFilters'] as $name ) {
						$checked = $process_form['DefaultFilter'] == $name ? 'selected' : '';
						print "<option value='".Util::htmlentities($name)."' $checked>".Util::htmlentities($name)."</option>\n";
					}

				$editfilter_display_style = 'style="display:none;"';
				foreach ( $_SESSION['DefaultFilters'] as $name ) {
					if ( $process_form['DefaultFilter'] == $name ) 
						$editfilter_display_style = '';
				}
				?>
				</select>
				
				<input type="image" name="filter_save" id="filter_save" title="<?=_("Save filter")?>"
					onClick="HandleFilter(2)" value="" src="icons/save.png" border="0" align="absmiddle"> 		
				
				<input type="image" name="filter_edit" id="filter_edit" title="Edit filter" <?php echo $editfilter_display_style; ?>
					onClick="HandleFilter(1)" value="" src="icons/edit.png">
			</div>
			
			<script language="Javascript" type="text/javascript">
				var DefaultFilters = new Array();
				<?php
				foreach ($_SESSION['DefaultFilters'] as $name ) {
					print "DefaultFilters.push('".Util::htmlentities($name)."');\n";
				}
				if ( array_key_exists('editfilter', $process_form) ) {
					print "edit_filter = '" . Util::htmlentities($process_form['DefaultFilter']) . "';\n";
				}
				?>
			</script>
		</td>
		<!-- Options start here -->
		<td style='padding: 0px;vertical-align:top;border:none;'>
			<table border="0" id="ProcessOptionTable" style="font-size:14px;font-weight:bold;width:100%;border:none">
				<tr>
					<td class='TDnfprocLabel' style='white-space:nowrap'>
					<?php
							$i = 0;
							foreach ( array('List Flows', 'Stat TopN') as $s ) {
								$checked = $process_form['modeselect'] == $i ? 'checked' : '';
								print "<input type='radio' onClick='SwitchOptionTable($i)' name='modeselect' id='modeselect$i' value='$i' $checked>$s&nbsp;";
								$i++;
							}
							$list_display_style = $process_form['modeselect'] == 0 ? '' : 'style="display:none;"';
							$stat_display_style = $process_form['modeselect'] == 0 ? 'style="display:none;"' : '';
							$formatselect_display_opts = $process_form['modeselect'] == 1 && $process_form['stattype'] != 0 ? 'style="display:none;"' : '';
					?>
				   </td>
				   
				   <td class='TDnfprocControl' >
						<table class='noborder' style='margin: auto;'>
							<tr>
								<td class='nobborder'><input class="small av_b_secondary" type="button" name="JSbutton1" value="<?php echo _("Clear Form")?>" onClick="ResetProcessingForm()"/></td>
								<td class='nobborder'><input class="small" type="submit" name="process" value="<?php echo _("Process")?>" id="process_button" onClick="clean_remote_data();form_ok=true;" size="1"/></td>
								<?php 
								if( count($RemoteInterfacesData)>0 && (!isset($_POST['login']))) 
								{ 
									?>
									<td class='nobborder'><input type="button" name="remote_process" value="<?php echo _("Remote Process")?>" id="remote_process_button" onclick="$('#rinterfaces').toggle()"/>
										<div id='container_rmp' style='position:relative;'>
											<div id="rinterfaces" style="position:absolute; top:0; right:0;display:none; margin:1px 0px 0px 2px; text-align:right;">
												<?php
												foreach($RemoteInterfacesData as $data) 
												{
													$short_name = (strlen($data['name']) > 12) ? substr($data['name'],0,12)."..." : $data['name'] ;
													?>
													<input type="button" onclick="remote_interface('<?php echo $data["url"]; ?>')" style="width:180px; font-size: 11px;" title="<?php echo $data["name"]." [".$data["url"]."]";?>" value="<?php echo $short_name." [".$data["url"]."]";?>"/><br />
													<?php
												}
												?>
											</div>
										</div>
									</td>
									<?php
								}
								?>
							</tr>
						</table>
					</td>			
				</tr>
				
				<tr id="listNRow" <?php echo $list_display_style;?>>
					<td class='TDnfprocLabel'><?=_("Limit to")?>:</td>
					<td class='TDnfprocControl'>
						<select name="listN" id="listN" style="margin-left:1" size="1">
						<?php
						for($i=0; $i<count($ListNOption); $i++ ) {
							$checked = $process_form['listN'] == $i ? 'selected' : '';
							print "<OPTION value='$i' $checked>" . $ListNOption[$i] . "</OPTION>\n";
						}
						?>
						</select><?=_("Flows")?><br>
					</td>
				</tr>
				
				<tr id="topNRow" <?php echo $stat_display_style;?>>
					<td class='TDnfprocLabel'><?=_("Top")?>:</td>
					<td class='TDnfprocControl'> 
						<select name="topN" id="TopN" size="1">
							<?php
							for($i=0; $i<count($TopNOption); $i++ ) {
								$checked = $process_form['topN'] == $i ? 'selected' : '';
								print "<OPTION value='$i' $checked>" . $TopNOption[$i] . "</OPTION>\n";
							}
							?>
						</select>
					</td>
				</tr>
				
				<tr id="stattypeRow" <?php echo $stat_display_style;?>>
					<td class="TDnfprocLabel"><?=_("Stat")?>:</td>
					<td class="TDnfprocControl">
						<select name="stattype" id="StatTypeSelector" onChange="ShowHideOptions()" size="1">
						<?php
						for($i=0; $i<count($IPStatOption); $i++ ) {
							$checked = $process_form['stattype'] == $i ? 'selected' : '';
							print "<OPTION value='$i' $checked>" . $IPStatOption[$i] . "</OPTION>\n";
						}
						?>
						</select>
						order by&nbsp;
						<select name='statorder' id="statorder" size='1'>
						<?php
						for($i=0; $i<count($IPStatOrder); $i++ ) {
							$checked = $process_form['statorder'] == $i ? 'selected' : '';
							print "<OPTION value='$i' $checked>" . $IPStatOrder[$i] . "</OPTION>\n";
						}
						?>
						</select>					
					</td>
				</tr>
				
				<tr id="AggregateRow" <?php echo $formatselect_display_opts?>>
					<td class='TDnfprocLabel'><?=_("Aggregate")?></td>
					<td class='TDnfprocControl'>
						<input type="checkbox" name="aggr_bidir" id="aggr_bidir" value="checked" onClick="ToggleAggregate();"
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_bidir']);?>>&nbsp;<?=_("bi-directional")?><br>
						<input type="checkbox" name="aggr_proto" id="aggr_proto" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_proto']);?>>&nbsp;<?=_("proto")?><br>
						<input type="checkbox" name="aggr_srcport" id="aggr_srcport" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_srcport']);?>>&nbsp;<?=_("srcPort")?>
						<input type="checkbox" name="aggr_srcip" id="aggr_srcip" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_srcip']);?>>&nbsp;
						<select name="aggr_srcselect" id="aggr_srcselect" onChange="NetbitEntry('src')" size="1">
							<?php
							$i = 0;
							foreach ( array('srcIP', 'srcIPv4/', 'srcIPv6/') as $s ) {
								$checked = $process_form['aggr_srcselect'] == $i ? 'selected' : '';
								print "<option value='$i' $checked>$s</option>\n";
								$i++;
							}
							$_style = $process_form['aggr_srcselect'] == 0 ? 'style="display:none"' : '';
							?>
						</select>
						<input size="3" type="text" name="aggr_srcnetbits" id="aggr_srcnetbits" 
							value="<?php echo Util::htmlentities($process_form['aggr_srcnetbits']);?>" <?php echo $_style;?>><br>
						<input type="checkbox" name="aggr_dstport" id="aggr_dstport" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_dstport']);?>>&nbsp;<?=_("dstPort")?>
						<input type="checkbox" name="aggr_dstip" id="aggr_dstip" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['aggr_dstip']);?>>&nbsp;
						<select name="aggr_dstselect" id="aggr_dstselect" onChange="NetbitEntry('dst')" size="1">
							<?php
							$i = 0;
							foreach ( array('dstIP', 'dstIPv4/', 'dstIPv6/') as $s ) {
								$checked = $process_form['aggr_dstselect'] == $i ? 'selected' : '';
								print "<option value='$i' $checked>$s</option>\n";
								$i++;
							}
							$_style = $process_form['aggr_dstselect'] == 0 ? 'style="display:none"' : '';
							?>
						</select>
						<input size="3" type="text" name="aggr_dstnetbits" id="aggr_dstnetbits" 
							value="<?php echo Util::htmlentities($process_form['aggr_dstnetbits']);?>" <?php echo $_style;?>><br>
					</td>
				</tr>
				
				<tr id="timesortedRow" <?php echo $list_display_style;?>>
					<td class='TDnfprocLabel'><?=_("Sort")?>:</td>
					<td class='TDnfprocControl'>
						<input type="checkbox" name="timesorted" id="timesorted" value="checked" 
							style="margin-left:1" <?php echo Util::htmlentities($process_form['timesorted']);?>>
						<?=_("start time of flows")?></td>
				</tr>
				
				<tr id="limitoutputRow" <?php echo $stat_display_style;?>>
					<td class='TDnfprocLabel'><?=_("Limit")?>:</td>
					<td class='TDnfprocControl'>
						<input type="checkbox" name="limitoutput" id="limitoutput" value="checked" style="margin-left:1" 
							size="1" <?php echo Util::htmlentities($process_form['limitoutput']);?>>
						<select name="limitwhat" id="limitwhat" size="1">
						<?php
						$i = 0;
						foreach ( array(gettext("Packets"), gettext("Traffic")) as $s ) {
							$checked = $process_form['limitwhat'] == $i ? 'selected' : '';
							print "<option value='$i' $checked>$s</option>\n";
							$i++;
						}
						?>
						</select>
						<select name="limithow" id="limithow" size="1">
						<?php
						$i = 0;
						foreach ( array('&gt;', '&lt;') as $s ) {
							$checked = $process_form['limithow'] == $i ? 'selected' : '';
							print "<option value='$i' $checked>$s</option>\n";
							$i++;
						}
						?>
						</select>
						<input type="text" name="limitsize" id="limitsize" value="<?php echo Util::htmlentities($process_form['limitsize']); ?>" SIZE="6" MAXLENGTH="8">
						<select name="limitscale" id="limitscale" size="1" style="margin-left:1">
						<?php
						$i = 0;
						foreach ( $LimitScale as $s ) {
							$checked = $process_form['limitscale'] == $i ? 'selected' : '';
							print "<option value='$i' $checked>$s</option>\n";
							$i++;
						}
						?>
						</select>
					</td>
				</tr>

				<tr id="outputRow">
					<td class='TDnfprocLabel'><?=_("Output")?>:</td>
					<td class='TDnfprocControl'>
						<span id="FormatSelect" <?php echo $formatselect_display_opts?>>
						<select name="output" id="output" onChange="CustomOutputFormat()"  style="margin-left:1" size="1">
						<?php
						foreach ($_SESSION['formatlist'] as $key => $value ) {
							$checked = $process_form['output'] == $key ? 'selected' : '';
							print "<OPTION value='".Util::htmlentities($key)."' $checked>".Util::htmlentities($key)."</OPTION>\n";
						}
						$fmt = $_SESSION['formatlist'][$process_form['output']];
						if ( $process_form['output'] == $fmt ) { // built in format
							$space_display_style = '';
							$edit_display_style = 'style="display:none"';
						} else {
							$space_display_style = 'style="display:none"';
							$edit_display_style = '';
						}
						?>
						</select>
						<script language="Javascript" type="text/javascript">
							var fmts = new Hash();
						<?php
						foreach ($_SESSION['formatlist'] as $key => $value )
							print "fmts.setItem('".Util::htmlentities($key)."', '".Util::htmlentities($value)."');\n";
						?>
						</script>
						<img src="icons/space.png" border="0" alt='space' id='space' <?php echo $space_display_style ?>/>
						<a href="#null" onClick="EditCustomFormat()"
							title="<?=_("Edit format")?>" ><IMG SRC="icons/edit.png" name="fmt_doedit" id="fmt_doedit" border="0" 
							<?php echo $edit_display_style; ?> alt="Edit format"></a>
						</span>
						<input type="checkbox" name="IPv6_long" id="IPv6_long" style="margin-left:1" value="checked" <?php echo Util::htmlentities($process_form['IPv6_long']);?>>
						&nbsp;/ <?=_("IPv6 long")?>
						<?php
						$fmt_edit_display_style = $process_form['output'] == 'custom ...' ? '' : 'style="display:none"';
						?>
						<span id="fmt_edit" <?php echo $fmt_edit_display_style?>>
						<br><?=_("Enter custom output format")?>:<br>
						<input size="30" type="text" name="customfmt" id="customfmt" 
							value="<?php echo Util::htmlentities($process_form['customfmt']);?>" >
						<input type="image" name="fmt_save" id="fmt_save" title="<?=_("Save format")?>" 
							onClick="SaveOutputFormat()" 
							value="" src="icons/save.png">
						<input type="image" name="fmt_delete" id="fmt_delete" title="<?=_("Delete format")?>" 
							onClick="DeleteOutputFormat()" 
							value="" src="icons/trash.png" <?php echo $edit_display_style; ?>>
						</span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<!--
<tr>
	<td></td><td></td>
	<td align="right" style="border:none">
		<input type="button" name="JSbutton1" value="<?=_("Clear Form")?>" onClick="ResetProcessingForm()">
		<input type="submit" name="process" value="<?=_("process")?>" id="process_button" onClick="form_ok=true;" size="1">
	</td>
</tr>
-->
</table>
</form>

<div id="lookupbox">
	<div id="lookupbar" align="right" style="background-color:olivedrab"><img src="icons/close.png"
		onmouseover="this.style.cursor='pointer';" onClick="hidelookup()" title="Close lookup box"></div>
	<iframe id="cframe" src="" frameborder="0" scrolling="auto" width="100%" height="166"></iframe>
</div>


<?php	
        if ( !array_key_exists('run', $_SESSION) )
			return;

		print "<div class='flowlist'>\n";
		$run = $_SESSION['run'];
		if ( $run != null ) {
			$filter = $process_form['filter'];
			if ( $process_form['DefaultFilter'] != -1 ) {
				$cmd_opts['and_filter'] = $process_form['DefaultFilter'];
			} 
			$cmd_opts['type'] 	 = ($_SESSION['profileinfo']['type'] & 4) > 0 ? 'shadow' : 'real';
			$cmd_opts['profile'] = $_SESSION['profileswitch'];
			$cmd_opts['srcselector'] = implode(':', $process_form['srcselector']);
			#print "<pre>\n";
			$patterns = array();
			$replacements = array();
			$patterns[0] = '/(\s*)([^\s]+)/';
			$replacements[0] = "$1<a href='#null' onClick='lookup(\"$2\", this, event)' title='lookup $2'>$2</a>";

			// gets HAP4NfSens plugin id. returns -1 if HAP4NfSen is not installed.
			function getHAP4NfSenId() {
				$plugins = GetPlugins();
				for ( $i=0;$i<count($plugins);$i++ ) {
					$plugin = $plugins[$i];
					if ($plugin == "HAP4NfSen") {
						return $i;
					}
				}
				return -1;
			}

			ClearMessages();
			$cmd_opts['args'] = "-T $run";
			$cmd_opts['filter'] = $filter;
			
			$titcol  = get_tit_col($run);
			$cmd_out = nfsend_query("run-nfdump", $cmd_opts);


			if ( !is_array($cmd_out) ) {
				ShowMessages();
			} else {
				$conf = $GLOBALS["CONF"];
				$solera = ($conf->get_conf("solera_enable", FALSE)) ? true : false;
                
                $db = new ossim_db();
                $conn = $db->connect();
                $sensors = $hosts = $ossim_servers = array();

                $tz                     = Util::get_timezone();
                list($hosts, $host_ids) = Asset_host::get_basic_list($conn, array(), TRUE);
                $entities               = Session::get_all_entities($conn);                
                $_sensors               = Av_sensor::get_basic_list($conn);
                foreach ($_sensors as $s_id => $s)
                {
                    $sensors[$s['ip']] = $s['name'];
                }
                
                			
        	        /*$hap4nfsen_id = getHAP4NfSenId();
        	        if ($hap4nfsen_id >= 0) {
					// ICMP "port" filter are no currently supported by the HAP4NfSen plugin
					function isChecked(&$form, $name) { // helper function used to find out, if an option is checked
						return $form[$name]=="checked";
					}
					$ip_and_port_columns = preg_match('/(flow records)/i', $IPStatOption[$process_form['stattype']]) &&
						((isChecked($process_form,'aggr_srcip') && isChecked($process_form,'aggr_srcport')) ||
						(isChecked($process_form,'aggr_dstip') && isChecked($process_form,'aggr_dstport')));
					$ip_contains_port =  $_SESSION["process_form"]["modeselect"]=='0' || !preg_match('/[ip|flow_records]/i', $IPStatOption[$process_form['stattype']]) ||
								(preg_match('/(flow records)/i', $IPStatOption[$process_form['stattype']]) && !( // no boxes checked
								isChecked($process_form,'aggr_srcip') || isChecked($process_form,'aggr_srcport') ||
								isChecked($process_form,'aggr_dstip') || isChecked($process_form,'aggr_dstport')));
        	                        $_SESSION["plugin"][$hap4nfsen_id]["cmd_opts"] = $cmd_opts;
					$hap_pic = "<img src=\"plugins/HAP4NfSen/graphviz.png\" valign=\"middle\" border=\"0\" alt=\"HAP\" />";
					$default_pattern = array_pop($patterns);
					$default_replacement = array_pop($replacements);
					if ($ip_contains_port) { // matches cases like ip:port
						$max_prot_length = 5; // max. port length = 5 chars(highest port number = 65535)
						for ($i=$max_prot_length;$i>=1;$i--) {
							$diff = ($max_prot_length-$i); // difference between actual and max port length
							$ip_port_pattern_icmp = "/(\s*)([^\s|^:]+)(:)(0\s{4}|\d\.\d\s{2}|\d{2}\.\d\|\d\.\d{2}\s|\d{2}\.\d{2})/";
							$ip_port_pattern_normal = "/(\s*)([^\s|^:]+)(:)([\d|\.]{{$i}})(\s{{$diff}})/";
							$spaces = '';
							for ($k=0;$k<$diff;$k++) {$spaces = $spaces . ' ';} // spaces required to align hap viewer icons
                                                	array_push($patterns, $ip_port_pattern_icmp);
							array_push($replacements,  $default_replacement .
								"$3$4 <a href=\"nfsen.php?tab=5&sub_tab=" . $hap4nfsen_id . "&ip=$2&mode=new\" title='HAP graphlet for $2'>$hap_pic</a> ");
							array_push($patterns, $ip_port_pattern_normal);
                                                	array_push($replacements,  $default_replacement .
								"$3$4$spaces <a href=\"nfsen.php?tab=5&sub_tab=" . $hap4nfsen_id . "&ip=$2&port=$4&mode=new\" title='HAP graphlet for $2 on port $4'>$hap_pic</a> ");
						}
						array_push($patterns, '/(\sIP\sAddr:Port)/i');
                                        	array_push($replacements, "$1  $hap_pic");
					} else {
						if ($ip_and_port_columns) { // matches cases when both ip and port are available but are located in separate columns
							// ICMP verion
							$ip_and_port_pattern = "/(\s*)([^\s]+)(\s+)(0|\d\.\d)/";
							$ip_and_port_replacement = "$1$2$3$4 " .
								"<a href=\"nfsen.php?tab=5&sub_tab=" . $hap4nfsen_id . "&ip=$2&mode=new\" title='HAP graphlet for $2'>$hap_pic</a>";
							array_push($patterns, $ip_and_port_pattern);
							array_push($replacements, $ip_and_port_replacement);
							// non-ICMP version with port filter
                                                        $ip_and_port_pattern = "/(\s*)([^\s]+)(\s*)([\d|.]+)/";
                                                        $ip_and_port_replacement = "$1$2$3$4 " .
                                                                "<a href=\"nfsen.php?tab=5&sub_tab=" . $hap4nfsen_id . "&ip=$2&port=$4&mode=new\" title='HAP graphlet for $2 on port $4'>$hap_pic</a>";
                                                        array_push($patterns, $ip_and_port_pattern);
                                                        array_push($replacements, $ip_and_port_replacement);
							array_push($patterns, '/(\s\s(Src\sIP\sAddr\s*Src\sPt|Dst\sIP\sAddr\s*Dst\sPt))/i');
                                                        array_push($replacements, "$1 $hap_pic");
						} else { // matches all other cases
							array_push($patterns, $default_pattern);
                                        		array_push($replacements,  $default_replacement . 
								" <a href=\"nfsen.php?tab=5&sub_tab=" . $hap4nfsen_id . "&ip=$2&mode=new\" title='HAP graphlet for $2'>$hap_pic</a>");
							array_push($patterns, '/(\s(|\s(Src|Dst))\sIP\sAddr)/i');
                                                	array_push($replacements, "$1 $hap_pic");
						}
					}
	                        }

				if ( array_key_exists('arg', $cmd_out) ) {
					print "** nfdump " . $cmd_out['arg'] . "\n";
				}
				if ( array_key_exists('filter', $cmd_out) ) {
					print "nfdump filter:\n";
					foreach ( $cmd_out['filter'] as $line ) {
						print "$line\n";
					}
				}
				foreach ( $cmd_out['nfdump'] as $line ) {
					print preg_replace($patterns, $replacements, $line) . "\n";
				}*/
				
				
				# parse command line
                #2009-12-09 17:08:17.596    40.262 TCP        192.168.1.9:80    ->   217.126.167.80:51694 .AP.SF   0       70   180978        1    35960   2585     1
                $list = (preg_match("/ extended /",$cmd_out['arg'])) ? 1 : 0;
                $regex = ($list) ? "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+->\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMG]?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*)/" : "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMGT]?)\s+(.*?)\s+(.*?)\s+(.*)/";
                echo '<div class="nfsen_list_title">'._('Flows Info').'</div>';
                echo "<table class='table_list'>";
                $geotools = false;
                if ($list && file_exists("../kml/GoogleEarth.php")) {
                	$geotools = true;
                	$geoips = array();
                
					$geotools_src = " <a href='' onclick='window.open(\"../kml/TourConfig.php?type=ip_src&ip=&flows=1\",\"Flows sources - Goggle Earth API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img align='absmiddle' src='../pixmaps/google_earth_icon.png' border='0'></a>&nbsp;&nbsp;<a href='' onclick='window.open(\"../kml/IPGoogleMap.php?type=ip_src&ip=&flows=1\",\"Flows sources - Goggle Maps API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img align='absmiddle' src='../pixmaps/google_maps_icon.png' border='0'></a>";
					$geotools_dst = " <a href='' onclick='window.open(\"../kml/TourConfig.php?type=ip_dst&ip=&flows=1\",\"Flows destinations - Goggle Earth API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img align='absmiddle' src='../pixmaps/google_earth_icon.png' border='0'></a>&nbsp;&nbsp;<a href='' onclick='window.open(\"../kml/IPGoogleMap.php?type=ip_dst&ip=&flows=1\",\"Flows destinations - Goggle Maps API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img align='absmiddle' src='../pixmaps/google_maps_icon.png' border='0'></a>";
                
                }
                echo ($list) ? "
                
                <tr>
                    <th>"._("Date flow start")."<br><span style='font-size:8px'>".Util::timezone($tz)."</style></th>
                    <th>"._("Duration")."</th>
                    <th>"._("Proto")."</th>
                    <th>"._("Src IP Addr:Port")."$geotools_src</th>
                    <th>"._("Dst IP Addr:Port")."$geotools_dst</th>
                    <th>"._("Flags")."</th>
                    <th>"._("Tos")."</th>
                    <th>"._("Packets")."</th>
                    <th>"._("Bytes")."</th>
                    <th>"._("pps")."</th>
                    <th>"._("bps")."</th>
                    <th>"._("Bpp")."</th>
                    <th>"._("Flows")."</th>
                	".($solera ? "<th></th>" : "")."
                    </tr>" : "<tr>
                    <th>"._("Date flow seen")."<br><span style='font-size:8px'>".Util::timezone($tz)."</style></th>
                    <th>"._("Duration")."</th>
                    <th>"._("Proto")."</th>
                    <th>".$titcol."</th>
                    <th>"._("Flows")."(%)</th>
                    <th>"._("Packets")."(%)</th>
                    <th>"._("Bytes")."(%)</th>
                    <th>"._("pps")."</th>
                    <th>"._("bps")."</th>
                    <th>"._("Bpp")."</th>
                	".($solera ? "<th></th>" : "")."
                    </tr>";
                $status = $errors = array();
                $rep = new Reputation();
                //print_r($cmd_out['arg']);
                //print_r($cmd_out['nfdump']);

                foreach ( $cmd_out['nfdump'] as $k => $line ) 
                {
                    
                    #capture status
                    if (preg_match("/^(Summary|Time window|Total flows processed|Sys)\:/",$line,$found))
                    {
                        $status[$found[1]] = str_replace($found[1].":","",$line);
                    }
                    
                    # capture errors
                    if (preg_match("/ error /i",$line,$found)) 
                    {
                        if (preg_match("/stat\(\) error/i",$line))
                        {
                            $errors[] = _('The netflow information you are trying to access either has not been processed yet or does not exist. Please check your date filters.');
                            Av_exception::write_log(Av_exception::USER_ERROR, $line);
                        }
                        else
                        {
                            $errors[] = $line;
                        }
                    }  
                    
                    # print results
                    $line = preg_replace("/\(\s(\d)/","(\\1",$line); // Patch for ( 0.3)
                    $line = preg_replace("/(\d)\s*([KMGT])/","\\1\\2",$line); // Patch for 1.2 M(99.6)
                    $line = preg_replace("/(\d+)(TCP|UDP|ICMP|IGMP)\s/","\\1 \\2 ",$line); // Patch for 9.003TCP
                    $start = $end = $proto = "";
                    $ips = $ports = array();
                    if (preg_match($regex,preg_replace('/\s*/', ' ', $line),$found)) 
                    {
                    echo "<tr class='tr_flow_data'>\n";

                    foreach ($found as $ki => $field) if ($ki>0) 
                    {
                            $wrap = ($ki==1) ? "nowrap" : "";
                            $field = Util::htmlentities(preg_replace("/(\:\d+)\.0$/","\\1",$field));
                            if (preg_match("/(\d+\.\d+\.\d+\.\d+)(.*)/",$field,$fnd)) 
                            {
                                # match ip (resolve and geolocalize)
                                $ip = $fnd[1];
                                $port = $fnd[2];
                                
                                list($name,$ctx,$host_id) = GetDataFromSingleIp($ip,$hosts);
                                if ($name == "" && $sensors[$ip]!="")
                                {
                                    $name = $sensors[$ip];
                                }

                                $output  = Asset_host::get_extended_name($conn, $geoloc, $ip, $ctx, $host_id, '');
                        	    $homelan = $output['is_internal'] || ($name != "" && $name != $ip);
                        	    $icon    = $output['html_icon'];
                        	    		
                                # reputation info
                                if (!is_array($_SESSION["_repinfo_ips"][$ip]))                                 
                                {
                                    $_SESSION["_repinfo_ips"][$ip] = $rep->get_data_by_ip($ip);                                
                                }
                                $rep_icon    = $rep->getrepimg($_SESSION["_repinfo_ips"][$ip][0],$_SESSION["_repinfo_ips"][$ip][1],$_SESSION["_repinfo_ips"][$ip][2],$ip);
                                $rep_bgcolor = $rep->getrepbgcolor($_SESSION["_repinfo_ips"][$ip][0]);
                        	    

                        	    $style_aux = ($homelan) ? 'style="font-weight:bold"' : '';
                        	    $bold_aux1 = ($homelan) ? '<b>' : '';
                        	    $bold_aux2 = ($homelan) ? '<b>' : '';

                                $field = '<div id="'.$ip.';'.Util::htmlentities($name).';'.$host_id.'" id2="'.$ip.';'.$ip.'" ctx="'.$ctx.'" class="HostReportMenu">'. $icon . ' <a '.$style_aux.' href="javascript:;">'.Util::htmlentities($name).'</a>' . $bold_aux1 . $port . $bold_aux2 . ' ' . $rep_icon . '</div>';
                                
                                $wrap = "nowrap style='$rep_bgcolor'";
                                $ips[] = $ip;
                                if ($geotools) {
                                	if ($ki == 4) {
                                		$geoips['ip_src'][$ip]++;
                                	} elseif ($ki == 5) {
                                		$geoips['ip_dst'][$ip]++;
                                	}
                                }
                                $ports[] = str_replace(":","",$port);
                            }
                            if (preg_match("/(\d+-\d+-\d+ \d+:\d+:\d+)(.*)/",$field,$fnd)) {
                            	# match date
                            	$start = $end = $fnd[1];
                            	$time = strtotime($fnd[1]);
                            	$field = Util::htmlentities(gmdate("Y-m-d H:i:s",$time+(3600*$tz)).".".$fnd[2]);
                            }
                            if (preg_match("/(TCP|UDP|ICMP|RAW)/",$field,$fnd)) {
                            	# match date
                            	$proto = strtolower($fnd[1]);
                            }                            
                            print "<td $wrap>$field</td>";
                        }
	                    // solera deepsee integration
			            if ($solera) {
			                echo "<td><a href=\"javascript:;\" onclick=\"solera_deepsee('".Util::htmlentities($start)."','".Util::htmlentities($end)."','".Util::htmlentities($ips[0])."','".Util::htmlentities($ports[0])."','".Util::htmlentities($ips[1])."','".Util::htmlentities($ports[1])."','".Util::htmlentities($proto)."')\"><img src='/ossim/pixmaps/solera.png' border='0' align='absmiddle'></a></td>";
			            }
			            echo "</tr>\n";
                    }
                }
                echo "</table>";
                if ($geotools) {
                	foreach ($geoips as $type=>$list) {
                		$ipsfile = fopen("/var/tmp/flowips_".Session::get_session_user().".$type","w");
                		foreach ($list as $ip=>$val) {
                			fputs($ipsfile,"$ip\n");
                		}
                		fclose($ipsfile);
                	}
                }
                #Summary: total flows: 20, total bytes: 7701, total packets: 133, avg bps: 60, avg pps: 0, avg bpp: 57
                #Time window: 2009-12-10 08:21:30 - 2009-12-10 08:38:26
                #Total flows processed: 21, Records skipped: 0, Bytes read: 1128
                #Sys: 0.000s flows/second: 0.0        Wall: 0.000s flows/second: 152173.9
                if (count($status)>0) 
                {
                    echo "<table class='transparent' style='margin-bottom:5px;width:100%'>";
                        foreach ($status as $key => $line) 
                        {
                            $line = preg_replace("/(Wall)\:/","<span class='th_summary'>\\1</span>",$line);
                            $line = preg_replace("/\,\s+(.*?)\:/"," <span class='th_summary'>\\1</span>",$line);
                            echo "<tr>
                                    <td class='nobborder' style='padding: 4px;'>
                                        <span class='th_summary'>$key</span>
                                        $line
                                    </td>
                                  </tr>";
                        }
                    echo "</table>";
                }
                # stat() error '/home/dk/nfsen/profiles-data/live/device2/2009/12/10/nfcapd.200912100920': File not found!
                if (count($errors)>0) 
                {
                    foreach ($errors as $line) 
                    {
                        echo "<div class='details_error'>"._("ERROR FOUND: "). "$line</div>";
                    }
                }
                $conn->disconnect();				
			}
			#print "</pre>\n";
		}
		print "</div>\n";

	$db_aux->close();
	$geoloc->close();

	return;

} # End of DisplayProcessing

?>
