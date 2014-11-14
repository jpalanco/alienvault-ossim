#
# License:
#
#  Copyright (c) 2011-2014 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#


package Avnetwork;
#
# Some code has been taken from debian-linux-lib.pl from webmin 1.51 by Rene Mayrhofer, July 2000

use v5.10;
use strict;
use warnings;
#use diagnostics;
use AV::ConfigParser;
use AV::Log;
use Avtools;

use Config::Tiny;

use Time::HiRes qw(usleep nanosleep);

my $network_interfaces_config = '/etc/network/interfaces';
my $network_interfaces        = '/proc/net/dev';
my $modules_config;

# gets a list of interface definitions (including their options) from the
# central config file
# the returned list is an array whose contents are tupels of
# (name, addrfam, method, options) with
#    name          the interface name (e.g. eth0)
#    addrfam       the address family (e.g. inet, inet6)
#    method        the address activation method (e.g. static, dhcp, loopback)
#    options       is a list of (param, value) pairs
sub get_interface_defs {
    local *CFGFILE;
    my @ret;
    my $pathname;
    open CFGFILE, "< $network_interfaces_config";

    # read the file line by line
    my $line = <CFGFILE>;
    while ( defined $line ) {
        chomp($line);

        # skip comments
        if ( $line =~ /^\s*#/ || $line =~ /^\s*$/ ) {
            $line = <CFGFILE>;
            next;
        }

        if ( $line =~ /^\s*auto/ ) {

            # skip auto stanzas
            $line = <CFGFILE>;
            while ( defined($line) && $line !~ /^\s*(iface|mapping|auto)/ ) {
                $line = <CFGFILE>;
                next;
            }
        }
        elsif ( $line =~ /^\s*mapping/ ) {

            # skip mapping stanzas
            $line = <CFGFILE>;
            while ( defined($line) && $line !~ /^\s*(iface|mapping|auto)/ ) {
                $line = <CFGFILE>;
                next;
            }
        }
        elsif ( my ( $name, $addrfam, $method )
            = ( $line =~ /^\s*iface\s+(\S+)\s+(\S+)\s+(\S+)\s*$/ ) )
        {

            # only lines starting with "iface" are expected here
            my @iface_options;

            # now read everything until the next iface definition
            $line = <CFGFILE>;
            while ( defined $line
                && !( $line =~ /^\s*(iface|mapping|auto)/ ) )
            {

                # skip comments and empty lines
                if ( $line =~ /^\s*#/ || $line =~ /^\s*$/ ) {
                    $line = <CFGFILE>;
                    next;
                }
                my ( $param, $value );
                if ( ( $param, $value )
                    = ( $line =~ /^\s*(\S+)\s+(.*)\s*$/ ) )
                {
                    push( @iface_options, [ $param, $value ] );
                }
                elsif ( ($param) = ( $line =~ /^\s*(\S+)\s*$/ ) ) {
                    push( @iface_options, [ $param, '' ] );
                }
                else {
                    error("Error in option line: '$line' invalid");
                }
                $line = <CFGFILE>;
            }
            push( @ret, [ $name, $addrfam, $method, \@iface_options ] );
        }
        else {
            error("Error reading file $pathname: unexpected line '$line'");
        }
    }
    close(CFGFILE);
    return @ret;
}

# get_auto_defs()
# Returns a list of interfaces in auto lines
sub get_auto_defs {
    my @rv;
    open( CFGFILE, "< $network_interfaces_config" );
    while (<CFGFILE>) {
        s/\r|\n//g;
        s/^\s*#.*$//g;
        if (/^\s*auto\s*(.*)/) {
            push( @rv, split( /\s+/, $1 ) );
        }
    }
    close(CFGFILE);
    return @rv;
}

# get_teaming_partner(devicename, line)
# Gets the teamingpartner of a configuration line
# Example configuration line: "/sbin/ifenslave bond0 eth0 eth1"
sub get_teaming_partner {
    my ( $deviceName, $line ) = @_;
    my @params = split( / /, $line );
    my $return;

    for ( my $i = scalar(@params); $i > 0; $i-- ) {
        if ( $deviceName eq $params[$i] ) {
            last;
        }
        else {
            $return = $params[$i] . " " . $return;
        }
    }
    chop $return;
    return $return;
}

# get_module_defs(device)
# Returns the modul options form /etc/modprobe.d/arch/i386
# for a special device
# Return hash: ($mode, $miimon, $downdelay, $updelay)
sub get_module_defs {
    return () if ( !$modules_config );
    local *CFGFILE;
    my ($device) = @_;
    my %ret;
    open CFGFILE, " < $modules_config";

    my $line = <CFGFILE>;
    while ( defined($line) ) {
        chomp($line);
        my @params = split( " ", $line );

        # Search for an entry concerning to the device
        if ( $params[0] eq "alias" && $params[1] eq $device ) {
            $line = <CFGFILE>;
            chomp $line;
            @params = split( " ", $line );

            # Check if it is an options line
            if ( $params[0] eq "options" && $params[1] eq "bonding" ) {
                for ( my $i = 2; $i < scalar(@params); $i++ ) {
                    ( my $key, my $value ) = split( "=", $params[$i] );
                    $ret{$key} = $value;
                }
            }
        }
        $line = <CFGFILE>;
    }
    return %ret;
}

# ip_to_integer(ip)
# Given an IP address, returns a 32-bit number
sub ip_to_integer {
    my @ip = split( /\./, $_[0] );
    return ( $ip[0] << 24 ) + ( $ip[1] << 16 ) + ( $ip[2] << 8 )
        + ( $ip[3] << 0 );
}

# integer_to_ip(integer)
# Given a 32-bit number, converts it to an IP
sub integer_to_ip {
    return sprintf "%d.%d.%d.%d",
        ( $_[0] >> 24 ) & 0xff,
        ( $_[0] >> 16 ) & 0xff,
        ( $_[0] >> 8 ) & 0xff,
        ( $_[0] >> 0 ) & 0xff;
}

sub iface_statistic {

	my %config = AV::ConfigParser::current_config();
    my $iface = shift;

    my ( $rx_bytes, $rx_packets, $rx_errs, $rx_drop, $rx_fifo, $rx_frame,
        $rx_compressed, $rx_multicast );
    my ( $tx_bytes, $tx_packets, $tx_errs, $tx_drop, $tx_fifo, $tx_colls,
        $tx_carrier, $tx_compressed );
    my $times_per_second = 3.0;
    my $delay = 1/3.0;

    my $st;
    my @varnetdev;
    open( my $network_interfaces_fh, '<', $network_interfaces );
		@varnetdev = <$network_interfaces_fh>;
	close($network_interfaces_fh);

    # Delay to stats
    Time::HiRes::sleep($delay);

    my $st2;
    my @varnetdev2;
    open( my $network_interfaces2_fh, '<', $network_interfaces );
        @varnetdev2 = <$network_interfaces2_fh>;
    close($network_interfaces2_fh);
	
	foreach(@varnetdev){
		$st = "$_" if (/$iface\:/);
    }
	foreach(@varnetdev2){
		$st2 = "$_" if (/$iface\:/);
	}
    
    chomp ($st);
    chomp ($st2);
    my $content;
    if ( $st ne "" ) {
        my @dat = split(/[(' ':)]+/, $st );
        shift @dat;
        shift @dat;
        $rx_bytes      = "$iface\[rx_bytes\]=$dat[0]";
        $rx_packets    = "$iface\[rx_packets\]=$dat[1]";
        $rx_errs       = "$iface\[rx_errs\]=$dat[2]";
        $rx_drop       = "$iface\[rx_drop\]=$dat[3]";
        $rx_fifo       = "$iface\[rx_fifo\]=$dat[4]";
        $rx_frame      = "$iface\[rx_frame\]=$dat[5]";
        $rx_compressed = "$iface\[rx_compressed\]=$dat[6]";
        $rx_multicast  = "$iface\[rx_multicast\]=$dat[7]";

        $tx_bytes      = "$iface\[tx_bytes\]=$dat[8]";
        $tx_packets    = "$iface\[tx_packets\]=$dat[9]";
        $tx_errs       = "$iface\[tx_errs\]=$dat[10]";
        $tx_drop       = "$iface\[tx_drop\]=$dat[11]";
        $tx_fifo       = "$iface\[tx_fifo\]=$dat[12]";
        $tx_colls      = "$iface\[tx_colls\]=$dat[13]";
        $tx_carrier    = "$iface\[tx_carrier\]=$dat[14]";
        $tx_compressed = "$iface\[tx_compressed\]=$dat[15]";

        my @iface_promisc = split ',\s*', $config{'sensor_interfaces'};
        #my $promisc = $#iface_promisc;
        my $promisc;
        if ( map (/$iface/,@iface_promisc)){
			$promisc = "$iface\[promiscuous_mode\]=yes";
		}else{
			$promisc = "$iface\[promiscuous_mode\]=no";
		}
        
        my @dat1 = split(/[(' ':)]+/, $st2 );
        shift @dat1;
        shift @dat1;
        my $diff_rx = ( $dat1[0] - $dat[0] ) * $times_per_second;
        my $diff_tx = ( $dat1[8] - $dat[8] ) * $times_per_second;

        $content
            = "$iface\[status\]=up\n$promisc\n$rx_bytes\n$iface\[rx_Bps\]=$diff_rx\n$rx_packets\n$rx_errs\n$rx_drop\n$rx_fifo\n$rx_frame\n$rx_compressed\n$rx_multicast\n$tx_bytes\n$iface\[tx_Bps\]=$diff_tx\n$tx_packets\n$tx_errs\n$tx_drop\n$tx_fifo\n$tx_colls\n$tx_carrier\n$tx_compressed";
    }
    else {
        $content = "status=down";
    }
    return $content;
}

sub system_dns {

    my $resolv_conf = "/etc/resolv.conf";
    my @varnetdev;
    my @outdns;

    if ( -f "$resolv_conf" ) {
        open LFILE, "< $resolv_conf";
        @varnetdev = <LFILE>;
        close(LFILE);

        foreach (@varnetdev) {

            if (/^nameserver\s+(\d+\.\d+\.\d+\.\d+)/) {

                push( @outdns, $1 );
            }

        }

        my $out = join( ";", @outdns );
        return $out;

    }

}
1;

