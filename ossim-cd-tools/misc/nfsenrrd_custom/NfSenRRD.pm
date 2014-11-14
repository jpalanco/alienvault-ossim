#!/usr/bin/perl
#
#  Copyright (c) 2004, SWITCH - Teleinformatikdienste fuer Lehre und Forschung
#  All rights reserved.
#
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions are met:
#
#   * Redistributions of source code must retain the above copyright notice,
#     this list of conditions and the following disclaimer.
#   * Redistributions in binary form must reproduce the above copyright notice,
#     this list of conditions and the following disclaimer in the documentation
#     and/or other materials provided with the distribution.
#   * Neither the name of SWITCH nor the names of its contributors may be
#     used to endorse or promote products derived from this software without
#     specific prior written permission.
#
#  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
#  AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
#  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
#  ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
#  LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
#  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
#  SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
#  INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
#  CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
#  ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
#  POSSIBILITY OF SUCH DAMAGE.
#
#  $Author: peter $
#
#  $Id: NfSenRRD.pm 40 2012-01-15 14:12:15Z peter $
#
#  $LastChangedRevision: 40 $

my %sensornames = ();

package NfSenRRD;

use RRDs;
use Log;
use NfSen;
use strict;
use warnings;

our @RRD_DS = (
	'flows',
	'flows_tcp',
	'flows_udp',
	'flows_icmp',
	'flows_other',
	'packets',
	'packets_tcp',
	'packets_udp',
	'packets_icmp',
	'packets_other',
	'traffic',
	'traffic_tcp',
	'traffic_udp',
	'traffic_icmp',
	'traffic_other',
);

sub GetRRDoffset {

	$Log::ERROR = undef;

	my $rrd_version = $RRDs::VERSION;
	if ( !defined $rrd_version ) {
		$Log::ERROR = "Can't find out which version of RRD you are using!\n";
		return undef;
	}

	my $RRDoffset = 0;
	if ( $rrd_version < 1.1 ) { # it's RRD 1.0.x
		$RRDoffset = 77;
	}
	if ( $rrd_version >= 1.2 && $rrd_version < 1.5 ) {
		$RRDoffset = 67;
	}

	if ( !$RRDoffset ) {
		$Log::ERROR = "RRD version '$rrd_version' not yet supported!\n";
		return undef;
	}

	return $RRDoffset;

} # End of GetRRDoffset

sub SetupRRD {

	my $path	= shift;
	my $db		= shift;
	my $start 	= shift;
	my $force	= shift;

	$Log::ERROR = undef;
	my @DS;
	foreach my $ds ( @RRD_DS ) {
		push @DS, "DS:$ds:ABSOLUTE:600:U:U";
	}

	my $ERR;

	# Create the RRD DB only if it not exists, or we are forced
	# to delete the old one, and create a new one
	unlink "$path/$db.rrd" if $force;
	if ( -f "$path/$db.rrd" ) {
		print  "RRD DB '$db.rrd' already exists!\n";
		return;
	} 
	
	# RRD DB layout:
	#   1 x 5min =  5 min samples	 30 * 288 ( per day ) = 8640 => 30 days
	#   6 x 5min = 30 min samples 	 30 *  48 ( per day ) = 1440 => 30 days	
	#  24 x 5min =  2 hour samples 	 30 *  12 ( per day ) = 360  => 30 days
	# 288 x 5min =	1 day samples	700 *   1 ( per day ) = 700  => 700 days
	# Total data available 790 days
	my $old_umask = umask 0002;
	my $rrd_filename = "$path/$db.rrd";
	RRDs::create ( $rrd_filename, "--start", $start,
		@DS,
		"RRA:AVERAGE:0.5:1:8640",
		"RRA:AVERAGE:0.5:6:1440",
		"RRA:AVERAGE:0.5:24:360",
		"RRA:AVERAGE:0.5:288:700",
		"RRA:MAX:0.5:1:8640",
		"RRA:MAX:0.5:6:1440",
		"RRA:MAX:0.5:24:360",
		"RRA:MAX:0.5:288:700"
	);
	umask $old_umask;

	$ERR=RRDs::error;
	if ( $ERR ) {
		$Log::ERROR = "ERROR while creating RRD DB $db.rrd: $ERR";
	}
	# System Errors are not reported detailed enough, so report system errno, if 
	# file does not exists or is empty
	if ( !-f $rrd_filename || -z $rrd_filename ) {
		my $err = $!;
		warn "Unable to create DB file: $err\n";
		if ( !defined $Log::ERROR) {
			$Log::ERROR = "Unable to create DB file: $err";
		}
	}

} # End of SetupRRD

sub SetupAlertRRD {

	my $path	= shift;
	my $db		= shift;
	my $start 	= shift;
	my $dsref	= shift;
	
	$Log::ERROR = undef;
	my @DS;
	foreach my $ds ( @$dsref ) {
		push @DS, "DS:$ds:ABSOLUTE:600:U:U";
	}

	my $ERR;

	# RRD DB layout:
	#   1 x 5min =  5 min samples	 30 * 288 ( per day ) = 8640 => 30 days
	#   6 x 5min = 30 min samples 	 30 *  48 ( per day ) = 1440 => 30 days	
	# Total data available 790 days
	my $old_umask = umask 0002;
	my $rrd_filename = "$path/$db.rrd";
	RRDs::create ( $rrd_filename, "--start", $start,
		@DS,
		"RRA:AVERAGE:0.5:1:8640",
		"RRA:AVERAGE:0.5:6:1440",
		"RRA:MAX:0.5:1:8640",
		"RRA:MAX:0.5:6:1440",
	);
	umask $old_umask;

	$ERR=RRDs::error;
	if ( $ERR ) {
		$Log::ERROR = "ERROR while creating RRD DB $db.rrd: $ERR";
	}
	# System Errors are not reported detailed enough, so report system errno, if 
	# file does not exists or is empty
	if ( !-f $rrd_filename || -z $rrd_filename ) {
		my $err = $!;
		warn "Unable to create DB file: $err\n";
		if ( !defined $Log::ERROR) {
			$Log::ERROR = "Unable to create DB file: $err";
		}
	}

} # End of SetupAlertRRD


sub DeleteRRD {
	my $path	= shift;
	my $db		= shift;

	unlink "$path/$db.rrd"

} # End of DeleteRRD


sub UpdateDB {
	my $path 		= shift;
	my $db 			= shift;
	my $timestamp	= shift;
	my $srclist 	= shift;
	my $valuelist 	= shift;

	RRDs::update ("$path/$db.rrd", "--template", 
		"$srclist",
		"$timestamp:$valuelist"
	);
	
	my $ERR=RRDs::error;
	$Log::ERROR = $ERR ? "ERROR while updating RRD DB $db.rrd: $ERR" : undef;

	return defined $Log::ERROR ? 0 : 1;

} # End of UpdateDB

sub GetSensorName {

	my $uuid = shift;
	return $sensornames{$uuid} if ($sensornames{$uuid} ne "");
	my $name = `/usr/share/ossim/www/nfsen/getsensorname.php '$uuid'`;
	$name    =~ s/[\n\t\r]+//g;
	$sensornames{$uuid} = $name;
	
	return $name;
}

# 
# static sub - used internally only
sub GenGraph {
	my $profile 	= shift;
	my $profilegroup = shift;
	my $vlabel  	= shift;
	my $what 		= shift;
	my $extension 	= shift;
	my $title		= shift;
	my $tstart		= shift;
	my $tend		= shift;
	
	my $profiledir  = NfProfile::ProfilePath($profile, $profilegroup);
	my %profileinfo = NfProfile::ReadProfile($profile, $profilegroup);
	my @DEFS;
	my @AllChannels = sort {
    	my $num1 = "$profileinfo{'channel'}{$a}{'sign'}$profileinfo{'channel'}{$a}{'order'}";
    	my $num2 = "$profileinfo{'channel'}{$b}{'sign'}$profileinfo{'channel'}{$b}{'order'}";
    	$num2 <=> $num1;
	} keys %{$profileinfo{'channel'}};

	foreach my $channel ( @AllChannels ) {
		# my $sampling_rate = exists NfConf::sources{$channel}{'sampling_rate'} ? NfConf::sources{$channel}{'sampling_rate'} : 1;
		$profileinfo{'channel'}{$channel}{'scale'} = $profileinfo{'channel'}{$channel}{'sign'} eq "-" ? -1 : 1;
		push @DEFS, "DEF:data${channel}=$NfConf::PROFILESTATDIR/$profiledir/${channel}.rrd:${what}:AVERAGE";
		if ( $what eq 'traffic' ) {
			push @DEFS, "CDEF:$channel=data${channel},". 8*$profileinfo{'channel'}{$channel}{'scale'} .",*";
		} else {
			push @DEFS, "CDEF:$channel=data${channel},$profileinfo{'channel'}{$channel}{'scale'},*";
		}
	}

	my @DEFSpos;
	my @DEFSneg;
	
	foreach my $channel ( @AllChannels ) {
    	if ( $profileinfo{'channel'}{$channel}{'sign'} eq "-" ) {
        	push @DEFSneg, $channel;
    	} else {
        	unshift @DEFSpos, $channel;
    	}
	}

	my @STACK;

	if ( scalar @DEFSpos > 0 ) {
		my $def = shift @DEFSpos;

		my $sname = NfSenRRD::GetSensorName $def;
		push @STACK, "AREA:${def}$profileinfo{'channel'}{$def}{'colour'}:$sname";
		foreach my $def ( @DEFSpos ) {
			my $sname = NfSenRRD::GetSensorName $def;
			push @STACK, "STACK:${def}$profileinfo{'channel'}{$def}{'colour'}:$sname";
		}
	}
	if ( scalar @DEFSneg > 0 ) {
		my $def = shift @DEFSneg;

		my $sname = NfSenRRD::GetSensorName $def;
		push @STACK, "AREA:${def}$profileinfo{'channel'}{$def}{'colour'}:$sname";
		foreach my $def ( @DEFSneg ) {
			my $sname = NfSenRRD::GetSensorName $def;
			push @STACK, "STACK:${def}$profileinfo{'channel'}{$def}{'colour'}:$sname";
		}
	}

	my $graph_filename = "$NfConf::PROFILESTATDIR/$profiledir/${what}${extension}.png";

#open(F,">>/tmp/rrds");
#print F "\n$graph_filename;".join(',',@DEFS).";".join(',',@STACK)."\n";
#close F;

	my ($averages,$xsize,$ysize) = RRDs::graph $graph_filename,
		@DEFS,
		"--imgformat", "PNG",
		"--title", 			"$title" ,
		"--vertical-label", "$vlabel",
		"--start",  		"$tstart",
		"--end",  			"$tend" ,
		"-w",  "576",  "-h",  "200",
		@STACK;

	my $ERR=RRDs::error;

	# System Errors are not reported detailed enough, so report system errno, if 
	# file does not exists or is empty
	if ( !-f $graph_filename || -z $graph_filename ) {
		warn "Unable to create graph: $!\n";
	}

	return $ERR ? $ERR : undef;

} # End of GenGraph

sub UpdateGraphs {
	my $profile   = shift;
	my $profilegroup = shift;
	my $tend	  = shift;
	my $force_all = shift;

	my %graphs = (
		'flows'		=> 'Flows/s',
		'packets'	=> 'Packets/s',
		'traffic'	=> 'Bits/s'
	);

	my $timeslot = NfSen::UNIX2ISO($tend);
	my ($year, $month, $day, $hour, $min ) = $timeslot =~ /^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/;

	# convert to integer
	$hour += 0;
	$min  += 0;

	my $title;
	my @statinfo;
	my $err = undef;
	$Log::ERROR = undef;
	foreach my $type ( keys %graphs ) {
		# Update every cycle
		$title = "$graphs{$type}: " . scalar localtime ($tend-86400) . " - " . scalar localtime $tend;
		$err = GenGraph($profile, $profilegroup, $graphs{$type}, $type, '-day', $title, $tend - 86400, $tend);
		if ( $err ) {
			warn "Error GenGraph: Profile: $profile, $type-day: $err";
			$Log::ERROR = "Error GenGraph: Profile: $profile, $type-day: $err";
		}

		# update every 30 min
		if ( $force_all || ($min == 30 || $min == 0) ) {
			$title = "$graphs{$type}: " . scalar localtime ($tend-604800) . " - " . scalar localtime $tend;
			$err = GenGraph($profile, $profilegroup, $graphs{$type}, $type, '-week', $title, $tend - 604800, $tend);
			if ( $err ) {
				warn "Error GenGraph: Profile: $profile, $type-day: $err";
				$Log::ERROR = "Error GenGraph: Profile: $profile, $type-day: $err";
			}
		}

		# update every 2h
		if ( $force_all || ($min == 0 && ( $hour % 2 ) == 0) ) {
			$title = "$graphs{$type}: " . scalar localtime ($tend-2592000) . " - " . scalar localtime $tend;
			$err = GenGraph($profile, $profilegroup, $graphs{$type}, $type, '-month', $title, $tend - 2592000, $tend);
			if ( $err ) {
				warn "Error GenGraph: Profile: $profile, $type-day: $err";
				$Log::ERROR = "Error GenGraph: Profile: $profile, $type-day: $err";
			}
		}

		# update every 24h
		if ( $force_all || ($hour == 0 && $min == 0) ) {
			$title = "$graphs{$type}: " . scalar localtime ($tend-31536000) . " - " . scalar localtime $tend;
			$err = GenGraph($profile, $profilegroup, $graphs{$type}, $type, '-year', $title, $tend - 31536000, $tend);
			if ( $err ) {
				warn "Error GenGraph: Profile: $profile, $type-day: $err";
				$Log::ERROR = "Error GenGraph: Profile: $profile, $type-day: $err";
			}
		}
	}

	return defined $Log::ERROR ? 1 : 0;

} # End of UpdateGraphs

sub GenDetailsGraph {
	my $profileinfo	= shift;	# profile name
	my $detailargs	= shift;

	my $sources 	= shift @{$detailargs};	# ':' separated list of sources
	my $proto   	= shift @{$detailargs};	# proto type: 'any', 'TCP', 'UDP', 'ICMP', 'other'
	my $type    	= shift @{$detailargs};	# 'flows', 'packets', 'traffic'
	my $pstart		= shift @{$detailargs};	# profile start time - UNIX format
	my $tstart		= shift @{$detailargs};	# start time - UNIX format
	my $tend		= shift @{$detailargs};	# end time - UNIX format
	my $tleft   	= shift @{$detailargs};	# left time of marker - UNIX format; 0 is no marker
	my $tright  	= shift @{$detailargs};	# right time of marker - UNIX format; 0 is no marker
	my $width		= shift @{$detailargs};	# width of graph
	my $heigh		= shift @{$detailargs};	# heigh of graph
	my $light		= shift @{$detailargs};	# light version ( small graphs ) - no title no span window
	my $logscale	= shift @{$detailargs};	# linear or log y-Axis
	my $linegraph	= shift @{$detailargs};	# linear or log y-Axis

	sub SendFile {
		my $file = shift;

		my $buf;
		open(GIFILE,$file) || return;;
		while (read(GIFILE, $buf, 8192)) {
			print $buf;
		}

	} # End of SendFile

	if ( $sources eq '' ) {
		SendFile("$NfConf::HTMLDIR/icons/EmptyGraph.png");
		return 'ok';
	}


	my %sources_names = ();
	my @sources_aux   = ();

	foreach my $src (split ':', $sources)
	{
		my @tmp = split ';', $src;
		push @sources_aux, $tmp[0];
		$sources_names{$tmp[0]} = ($tmp[1] eq '') ? (NfSenRRD::GetSensorName $tmp[0]) : $tmp[1];

	}
	$sources = join ':', @sources_aux;


	my $profile 	 = $$profileinfo{'name'};
	my $profilegroup = $$profileinfo{'group'};

	# generate the title: e.g. flows -> Flows/s
	my $title = $type eq 'traffic' ? "bits/s" : "$type/s";
	$title =~ s/^(\w)/\u$1/;

	my $ds_extension;
	if ( $proto eq 'any' ) {
		$title    .= " any protocol";
		$ds_extension = '';
	} else {
		$title    .= " proto $proto";
		$ds_extension = "_" . lc $proto;
	}

	my $datestr;
	if ( $tleft ) {
		$datestr = scalar localtime($tleft);
	} else {
		$datestr = scalar localtime($tstart) . " - " . scalar localtime($tend);
	}

	my @DEFS = sort {
    	my $num1 = "$$profileinfo{'channel'}{$a}{'sign'}$$profileinfo{'channel'}{$a}{'order'}";
    	my $num2 = "$$profileinfo{'channel'}{$b}{'sign'}$$profileinfo{'channel'}{$b}{'order'}";
    	$num2 <=> $num1;
	} split ':', $sources;
	
	my @DEFSpos;
	my @DEFSneg;
	my $max_label_length = 0;
	foreach my $channel ( @DEFS ) {
    	if ( $$profileinfo{'channel'}{$channel}{'sign'} eq "-" ) {
        	push @DEFSneg, $channel;
    	} else {
        	unshift @DEFSpos, $channel;
    	}
		if ( length($channel) > $max_label_length ) {
			$max_label_length = length($channel);
		}
	}

	my @rrdargs = ();
	push @rrdargs, "-";	# output graphics to stdout

	foreach my $def ( @DEFS ) {
		$$profileinfo{'channel'}{$def}{'scale'} = $$profileinfo{'channel'}{$def}{'sign'} eq "-" ? -1 : 1;
		push @rrdargs, "DEF:data${def}=$NfConf::PROFILESTATDIR/$profilegroup/$profile/${def}.rrd:${type}${ds_extension}:AVERAGE";
		if ( $type eq 'traffic' ) {
			push @rrdargs, "CDEF:$def=PREV(data${def}),". 8*$$profileinfo{'channel'}{$def}{'scale'} .",*";
		} else {
			push @rrdargs, "CDEF:$def=PREV(data${def}),$$profileinfo{'channel'}{$def}{'scale'},*";
		}
	}
	push @rrdargs, "--imgformat", "PNG";
	push @rrdargs, "--logarithmic" if $logscale;
	push @rrdargs, "--title", "$datestr $title" unless $light;
	push @rrdargs, "--vertical-label", "$title" unless $light;
	push @rrdargs, "--no-minor", if $light;
	push @rrdargs, "--no-legend", if $light;
	push @rrdargs, "--start",  "$tstart";
	push @rrdargs, "--end",  "$tend";
	push @rrdargs, "-w";
	push @rrdargs, "$width";
	push @rrdargs, "-h";
	push @rrdargs, "$heigh";

	my $def = $DEFS[0];
	my $HasLabel;

	# graph outside profile boundary? - grayout time before profile start
	if ( !$light && ($tstart < $pstart) ) {
		if ( scalar @DEFSpos > 0 ) {
			push @rrdargs, "CDEF:outofdata1=$def,POP,TIME,$pstart,LE,INF,UNKN,IF";
			push @rrdargs, "AREA:outofdata1#DDDDDD";
		}
		if ( scalar @DEFSneg > 0 ) {
			push @rrdargs, "CDEF:outofdata2=$def,POP,TIME,$pstart,LE,NEGINF,UNKN,IF";
			push @rrdargs, "AREA:outofdata2#DDDDDD";
		}
	}

	if ( $linegraph ) {
		foreach my $def ( @DEFS ) {
			my $sname = $sources_names{$def};
			$HasLabel = $light ? "" : ":$sname". ' ' x ($max_label_length - length($sname)) ;
			push @rrdargs, "LINE1:$def$$profileinfo{'channel'}{$def}{'colour'}${HasLabel}";
		}
	} else {
		if ( scalar @DEFSpos > 0 ) {
			$def = shift @DEFSpos;
			my $sname = $sources_names{$def};
			$HasLabel = $light ? "" : ":$sname". ' ' x ($max_label_length - length($sname)) ;
	
			push @rrdargs, "AREA:$def$$profileinfo{'channel'}{$def}{'colour'}${HasLabel}";
			foreach my $def ( @DEFSpos ) {
				my $sname = $sources_names{$def};
				$HasLabel = ":$sname". ' ' x ($max_label_length - length($sname))  unless $light;
				push @rrdargs, "STACK:$def$$profileinfo{'channel'}{$def}{'colour'}${HasLabel}";
			}
		}
		if ( scalar @DEFSneg > 0 ) {
			$def = shift @DEFSneg;
			my $sname = $sources_names{$def};
			$HasLabel = $light ? "" : ":$sname". ' ' x ($max_label_length - length($sname)) ;
	
			push @rrdargs, "AREA:$def$$profileinfo{'channel'}{$def}{'colour'}${HasLabel}";
			foreach my $def ( @DEFSneg ) {
				my $sname = $sources_names{$def};
				$HasLabel = ":$sname" . ' ' x ($max_label_length - length($sname))  unless $light;
				push @rrdargs, "STACK:$def$$profileinfo{'channel'}{$def}{'colour'}${HasLabel}";
			}
		}
	}
	
#	if ( $tleft ) {
#		push @rrdargs, "VRULE:$tleft#000000";
#	}

	my ($averages,$xsize,$ysize) = RRDs::graph( @rrdargs );
	if (my $ERROR = RRDs::error) {
		SendFile("$NfConf::HTMLDIR/icons/ErrorGraph.png");
		return "$ERROR: Arg: '$profile', '$sources', '$proto', '$type', '$pstart', '$tstart', '$tend', '$tleft', '$tright', '$width', '$heigh', '$light', '$logscale', '$linegraph'";
	} else {
		return "ok";
	}

} # End of GenDetailsGraph

sub GenAlertGraph {
	my $alertname	= shift;	# alert name
	my $detailargs	= shift;

	my $type    	= shift @{$detailargs};	# 0: 'flows', 1: 'packets', 2: 'bytes'
	my $tstart		= shift @{$detailargs};	# start time - UNIX format
	my $tend		= shift @{$detailargs};	# end time - UNIX format
	my $sources 	= shift @{$detailargs};	# ':' separated list of sources
	my $width		= shift @{$detailargs};	# width of graph
	my $heigh		= shift @{$detailargs};	# heigh of graph
	my $events		= shift @{$detailargs};	# ':' separated list of last trigger events

	if ( $sources eq '' ) {
		SendFile("$NfConf::HTMLDIR/icons/EmptyGraph.png");
		return 'ok';
	}

	if ( $type == 0 ) {
		$type = 'flows';
	} elsif ( $type == 1 ) {
		$type = 'packets';
	} elsif ( $type == 2 ) {
		$type = 'bytes';
	} else {
		return "Unknown type '$type'";
	}
	# generate the title: e.g. flows -> Flows/s
	my $title = $type eq 'bytes' ? "bits/s" : "$type/s";
	$title =~ s/^(\w)/\u$1/;

	my $datestr = scalar localtime($tstart) . " - " . scalar localtime($tend);

	my @DEFS = split ':', $sources;
	
	my @rrdargs = ();
	push @rrdargs, "-";	# output graphics to stdout

	foreach my $def ( @DEFS ) {
		push @rrdargs, "DEF:data${def}=$NfConf::PROFILESTATDIR/~$alertname/avg-$type.rrd:${def}:AVERAGE";
		if ( $type eq 'bytes' ) {
			push @rrdargs, "CDEF:$def=PREV(data${def}),8,*";
		} else {
			push @rrdargs, "CDEF:$def=PREV(data${def}),1,*";
		}
	}
	push @rrdargs, "--imgformat", "PNG";
	push @rrdargs, "--title", "$datestr $title";
	push @rrdargs, "--vertical-label", "$title";
	push @rrdargs, "--start",  "$tstart";
	push @rrdargs, "--end",  "$tend";
	push @rrdargs, "-w";
	push @rrdargs, "$width";
	push @rrdargs, "-h";
	push @rrdargs, "$heigh";

	foreach my $event ( split ':', $events ) {
		if ( $event > 0 && $event > $tstart && $event < $tend ) {
			push @rrdargs, "VRULE:$event#000080";
		}
	}

	my @AVGcolours = ( '#ff0000', '#800000', '#008080', '#00ffff', '#ffff00', '#808000', '#0000ff'  );
	foreach my $def ( @DEFS ) {
		my $colour = shift @AVGcolours;
		push @rrdargs, "LINE1:${def}${colour}:$def";
	}

	my ($averages,$xsize,$ysize) = RRDs::graph( @rrdargs );
	if (my $ERROR = RRDs::error) {
		SendFile("$NfConf::HTMLDIR/icons/ErrorGraph.png");
		return "ERR failed $ERROR";
	} else {
		return "ok";
	}

} # End of GenAlertGraph
