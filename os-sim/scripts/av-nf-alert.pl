#!/usr/bin/perl

# AlienVault-Netflow plugin
# Author: js aka PacketInspector
# Re-enginnered by: apobereyko\@alienvault.com
# Plugin $plugin_name id:$plugin_id version:$plugin_version

use strict;
use warnings;

use DateTime;
use Date::Parse;
use Getopt::Std;
use Sys::Syslog;
use POSIX;
use Socket;

use experimental qw/ switch /;

use vars qw/ %opt /;


### Configuration variables

my $ossim_setup_conf = '/etc/ossim/ossim_setup.conf';

my $netflows_dir = '/var/cache/nfdump/flows/live';

my $nfdump_output_format = 'fmt:%ts;%te;%td;%pr;%sa;%da;%sp;%dp;%byt;%bpp;%pps;%fl;%flg';

my $protocols_filter = "";

my $aggregation_rule_download = "";
my $aggregation_rule_upload = "";
my @aggregation_rules_download = ( "dstip,proto", "dstip,proto,srcip" );
my @aggregation_rules_upload = ( "srcip,proto", "srcip,proto,dstip");

# Netflow window - legnth of time to go back and look for transfers (in hours); 1 hour by default
my $inspection_window = 1;

# Address filters for nfdump
my $dst_address_list = "";
my $src_address_list = "";

# AV Framework connectivity
my $framework_host;
my $framework_port = "40003";
my $framework_control_msg = "control action=\"get_alert_nf_setup\"";
my $framework_control_resp;
my @framework_control_resp_fields;

# Alert Thresholds (if you change these remake the SQL...)
# [sid] => [ [bytes_count], [protocol_number], [message_template] ]
my %download_alerts = (
	101 => [0, 6, 'TCP Download exceeded threshold'],
	102 => [0, 17, 'UDP Download exceeded threshold']
);
my %upload_alerts = (
	201 => [0, 6, 'TCP Upload exceeded threshold'],
	202 => [0, 17, 'UDP Upload exceeded threshold']
);

my $megabyte_multiplier = 1048576;

# Polling Interval - Copy of Watchdog in minutes which seems fixed in ossim-agent
my $polling_interval = 3;

# Identifiers for plugin generation
my $plugin_id = 1853;
my $plugin_name = 'AlienVault-Netflow';
my $plugin_version = "0.0.1";
my $plugin_desc = 'AlienVault-Netflow Alerts';


### Procedures

sub ABOUT_MESSAGE {
	print "$plugin_name $plugin_version\n";
}


sub parse_line_custom($) {

	my $line = shift;

	my @fields = split /\;/;

	# Remove spaces added by Nfdump
	s/^\s+|\s+$//g for(@fields);

	# Change fields to unixtime
	$fields[0] = floor(str2time($fields[0]));
	$fields[1] = floor(str2time($fields[1]));

	# Add pretty bytes
	push @fields, scaledbytes($fields[8]);

	# Add Flows/second
	my $duration = $fields[2];

	if ( $duration > 0) {
		push @fields, $fields[11] / $duration;
	} else {
		push @fields, 0;
	}

	return @fields;

}


sub send_message($) {

	my $log = shift;
	openlog($plugin_name, '', 'local6');
	syslog("notice", $log);
	closelog();

}


#Straight up copy+paste: http://www.perlmonks.org/?node_id=378580
sub scaledbytes($) {

	(sort { length $a <=> length $b }
	map { sprintf '%.3g%s', $_[0]/1024**$_->[1], $_->[0] }
	[" bytes"=>0],[KB=>1],[MB=>2],[GB=>3],[TB=>4],[PB=>5],[EB=>6])[0]

}


sub extend_protocols_filter($) {

	if ( ! $protocols_filter ) {
		$protocols_filter = "and ( proto " . "$_[0] ";
	} else {
		$protocols_filter = $protocols_filter . "or proto " . "$_[0] ";
	}

}


##### MAIN #####

# Required for taint-mode only:
$ENV{ 'PATH' } = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
delete @ENV{'IFS', 'CDPATH', 'ENV', 'BASH_ENV'};

$Getopt::Std::STANDARD_HELP_VERSION = 1;
getopts('ah', \%opt);

if ( $opt{'a'} || $opt{'h'} ) {
	ABOUT_MESSAGE();
	exit;
}


if ( -e $ossim_setup_conf && -s $ossim_setup_conf && -r $ossim_setup_conf ) {

	my $framework_ip_line=`/bin/grep "^framework_ip=" $ossim_setup_conf`;
	chomp($framework_ip_line);
	$framework_host=(split /=/, $framework_ip_line)[1];

	my $networks = `/bin/grep "^networks=" $ossim_setup_conf`;
	chomp($networks);
	my ($net) = (split /=/, $networks)[1];
	my @netblocks = split /,/, $net;

	# Filters for nfdump
	$dst_address_list = join(' or dst net ', @netblocks);
	$src_address_list = join(' or src net ', @netblocks);

}

if ( $framework_host ) {

	my $proto = getprotobyname('tcp');

	my($framework_socket);

	socket($framework_socket, AF_INET, SOCK_STREAM, $proto) or die "ERROR: failed to create network socket: $!";

	my $framework_ip_addr = inet_aton($framework_host) or die "ERROR: failed to resolve hostname: $framework_host";
	my $framework_addr_port = sockaddr_in($framework_port, $framework_ip_addr);

	connect($framework_socket, $framework_addr_port) or die "ERROR: failed to connect to framework host $framework_host on port $framework_port: $!";
	send($framework_socket , "$framework_control_msg\n" , 0);

	while ( my $responce = <$framework_socket> ) {
		if ( $responce =~ /^control.*ackend$/ ) {
			$framework_control_resp = $responce;
			chomp($framework_control_resp);
			last;
		}
	}

	close($framework_socket);

	if ( $framework_control_resp =~ /^.*(errno=\"\d+\").*(error=\".+\").*$/ ) {
		die "ERROR: framework returned responce with: $1 $2\n"
	}

} else {

	die "ERROR: no framework host defined in $ossim_setup_conf!";

}


$framework_control_resp =~ s/ +/ /g;
@framework_control_resp_fields = split /\s/, $framework_control_resp;


foreach my $field (@framework_control_resp_fields) {

	chomp($field);
	$field =~ s/\"//g;
	my $value = (split /=/, $field)[1];

	given ( $field ) {

		when ( /^agg_function=/ ) {
			$aggregation_rule_download = $aggregation_rules_download[$value];
			$aggregation_rule_upload = $aggregation_rules_upload[$value];
		};

		when ( /^inspection_window=/ ) {
			$inspection_window = $value;
		};

		when ( /^tcp_max_download=/ ) {
			$download_alerts{'101'}[0] = $value * $megabyte_multiplier;
		};

		when ( /^tcp_max_upload=/ ) {
			$upload_alerts{'201'}[0] = $value * $megabyte_multiplier;
		};

		when ( /^udp_max_download=/ ) {
			$download_alerts{'102'}[0] = $value * $megabyte_multiplier;
		};

		when ( /^udp_max_upload=/ ) {
			$upload_alerts{'202'}[0] = $value * $megabyte_multiplier;
		};

	}

}


# Set default aggregation function (to be on the safe side...)
if ( ! $aggregation_rule_download ) { $aggregation_rule_download = $aggregation_rules_download[0]; };
if ( ! $aggregation_rule_upload ) { $aggregation_rule_upload = $aggregation_rules_upload[0]; };

if ( $download_alerts{'101'}[0] > 0 || $upload_alerts{'201'}[0] > 0 ) { extend_protocols_filter("tcp"); }
if ( $download_alerts{'102'}[0] > 0 || $upload_alerts{'202'}[0] > 0 ) { extend_protocols_filter("udp"); };

# Finalize the protocols filter
if ( $protocols_filter ) {
	$protocols_filter .= ")";
}

my $current_time = time();

# Polling time interval for nfdump to check
my $nfdump_check_start = DateTime->now(time_zone=> "local")->subtract( hours => $inspection_window)->strftime("%Y/%m/%d.%H:%M:%S");
my $nfdump_check_now = DateTime->now(time_zone=> "local")->strftime("%Y/%m/%d.%H:%M:%S");

my $nf_dump_cmd_download = "/usr/bin/nfdump -R '$netflows_dir' -t '$nfdump_check_start-$nfdump_check_now' -A $aggregation_rule_download -q -N -n 0 -o '$nfdump_output_format' -s record/bytes '( (dst net $dst_address_list) and not (src net $src_address_list) ) $protocols_filter'";
my $nf_dump_cmd_upload = "/usr/bin/nfdump -R '$netflows_dir' -t '$nfdump_check_start-$nfdump_check_now' -A $aggregation_rule_upload -q -N -n 0 -o '$nfdump_output_format' -s record/bytes '( (src net $src_address_list) and not (dst net $dst_address_list) ) $protocols_filter'";

my $nf_dl_output = `$nf_dump_cmd_download`;
my $nf_up_output = `$nf_dump_cmd_upload`;

# Process download flows
foreach (split(/\n/, $nf_dl_output)) {

	chomp;

	# Skip if no data is present
	next if (!m/\;/);

	my @fields = parse_line_custom($_);

	# Go through and look for ends within our polling interval
	if ( $fields[1] > ($current_time - ($polling_interval * 60) - 330) ) {

		foreach my $sid ( sort keys %download_alerts ) {

			if ( ($download_alerts{$sid}[0] >= 0) && ($download_alerts{$sid}[1] == $fields[3]) && ($fields[8] >= $download_alerts{$sid}[0]) ) {
				my $event_message = $sid . "\;" . join("\;",@fields);
				send_message($event_message);
				last;
			}

		}

	}

}

# Process upload flows
foreach (split(/\n/, $nf_up_output)) {

	chomp;

	# Skip if no data is present
	next if (!m/\;/);

	my @fields = parse_line_custom($_);

	# Go through and look for ends within our polling interval
	if ( $fields[1] > ($current_time - ($polling_interval * 60) - 330) ) {

		foreach my $sid ( sort keys %upload_alerts ) {

			if ( ($upload_alerts{$sid}[0] >= 0) && ($upload_alerts{$sid}[1] == $fields[3]) && ($fields[8] >= $upload_alerts{$sid}[0]) ) {
				my $event_message = $sid . "\;" . join("\;",@fields);
				send_message($event_message);
				last;
			}
		}


	}

}

#EOF
