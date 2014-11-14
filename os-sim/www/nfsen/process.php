<?php
require_once ('av_init.php');
Session::logcheck("environment-menu", "MonitorsNetflows");

$is_bidir = 0;

function CompileAggregateFormat($process_form) {

	global $is_bidir;

	if ( $process_form['aggr_bidir'] == 'checked' ) {
		$is_bidir = 1;
		return ' -B';
	}

	$is_bidir = 0;
	$aggregate = array();
	if ( $process_form['aggr_proto'] == 'checked' )
		$aggregate[] = 'proto';
	if ( $process_form['aggr_srcip'] == 'checked' ) {
		switch($process_form['aggr_srcselect']) {
			case 0;
				$aggregate[] = 'srcip';
				break;
			case 1;
				$aggregate[] = 'srcip4/' . $process_form['aggr_srcnetbits'];
				break;
			case 2;
				$aggregate[] = 'srcip6/' . $process_form['aggr_srcnetbits'];
				break;
		}
	}
	if ( $process_form['aggr_srcport'] == 'checked' )
		$aggregate[] = 'srcport';
	if ( $process_form['aggr_dstip'] == 'checked' ) {
		switch($process_form['aggr_dstselect']) {
			case 0;
				$aggregate[] = 'dstip';
				break;
			case 1;
				$aggregate[] = 'dstip4/' . $process_form['aggr_dstnetbits'];
				break;
			case 2;
				$aggregate[] = 'dstip6/' . $process_form['aggr_dstnetbits'];
				break;
		}
	}
	if ( $process_form['aggr_dstport'] == 'checked' )
		$aggregate[] = 'dstport';

	if ( count($aggregate) > 0 )
		return ' -A ' . implode(',', $aggregate);
	else
		return '';
	
} // End of CompileAggregateFormat

function CompileOutputFormat($process_form) {

	global $is_bidir;

	$format = $process_form['output'];
	if ( $format == 'custom ...' ) {
		$formatdef = $process_form['customfmt'];
	} else {
		$formatdef = $_SESSION['formatlist'][$format];
	}
	if ( $format == 'auto') 
		return "";
	if ( $format == $formatdef ) 
		if ( $is_bidir )
			return " -o bi$format";
		else
			return " -o $format";
	else
		return " -o 'fmt:$formatdef'";

} // End of CompileOutputFormat

function CompileCommand($mode) {

	global $ListNOption;
	global $TopNOption;
	global $IPStatOption;
	global $IPStatOrder;
	global $IPStatArg;
	global $LimitScale;
	global $OutputFormatArg;

	$process_form = $_SESSION['process_form'] ;
	$profile 	  = $_SESSION['profile'];
	$profilegroup = $_SESSION['profilegroup'];

	// get the sources selected for processing
	$args = '';

	// From the argument checks, we know at least one source is selected
	// multiple sources
	if ( $_SESSION['tleft'] == $_SESSION['tright'] ) {
		// a single 5 min timeslice
		$tslot1 = UNIX2ISO($_SESSION['tleft']);
		$subdirs = SubdirHierarchy($_SESSION['tleft']);
		if ( strlen($subdirs) == 0 ) 
			$args .= " -r nfcapd.$tslot1";
		else
			$args .= " -r $subdirs/nfcapd.$tslot1";

	} else {
		// several 5 min timeslices
		$tslot1 = UNIX2ISO($_SESSION['tleft']);
		$subdirs1 = SubdirHierarchy($_SESSION['tleft']);
		$tslot2 = UNIX2ISO($_SESSION['tright']);
		$subdirs2 = SubdirHierarchy($_SESSION['tright']);
		if ( strlen($subdirs1) == 0 ) 
			$args .= " -R nfcapd.$tslot1:nfcapd.$tslot2";
		else
			$args .= " -R $subdirs1/nfcapd.$tslot1:$subdirs2/nfcapd.$tslot2";
	}

	// process list request
	if ( $mode == 0 ) {
		$_tmp = CompileAggregateFormat($process_form);
		if ( $_tmp != '' ) {
			$args .= " -a $_tmp";
		}
		// process list request
		$args .= CompileOutputFormat($process_form);
		// IPv6 long listing
		$args .= $process_form['IPv6_long'] == 'checked' ? " -6" : '';
		// sort the flows from all sources
		$args .= $process_form['timesorted'] == 'checked' ? " -m" : '';
		// list this number of flows
		$args .= " -c " . $ListNOption[$process_form['listN']];
	}

	// process stat request
	if ( $mode == 1 ) {
		$args .= " -n " . $TopNOption[$process_form['topN']];
		// -s record
		$type_index  = $process_form['stattype'];
		$order_index = $process_form['statorder'];
		$args .= ' ' . $IPStatArg[$type_index] . '/' . $IPStatOrder[$order_index];

		if ( $process_form['stattype'] == 0 ) {
			$args .= CompileAggregateFormat($process_form);
			$args .= CompileOutputFormat($process_form);
		}
		// IPv6 long listing
		$args .= $process_form['IPv6_long'] == 'checked' ? " -6" : '';

		// limits -L/-l
		if ( $process_form['limitoutput'] == 'checked' ) {
			$args .= $process_form['limitwhat'] == 1 ? " -L " : " -l ";
			if ( $process_form['limithow'] == 1 )
				$args .= '-';
			$args .= $process_form['limitsize'];
			if ( $process_form['limitscale'] > 0 )
				$args .= $LimitScale[$process_form['limitscale']];
		}
	} 

	return "$args";

} // End of CompileCommand


?>
