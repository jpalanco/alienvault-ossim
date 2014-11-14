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

package AV::Debian::Netifaces;

use v5.10;
use strict;
use warnings;

#use diagnostics;
use File::Copy;

use AV::ConfigParser;
use AV::Log;

sub network_config_debian {
    my $network_interfaces_config = '/etc/network/interfaces';
    my $network_interfaces_backup = '/etc/network/interfaces.old';

    my %config          = AV::ConfigParser::current_config;
    my $admin_ip        = $config{'admin_ip'};
    my $admin_netmask   = $config{'admin_netmask'};
    my $admin_interface = $config{'interface'};
    my $admin_gateway   = $config{'admin_gateway'};
    my $admin_dns       = $config{'admin_dns'};
    $admin_dns =~ s/,/ /ig;

    copy( $network_interfaces_config, $network_interfaces_backup );
    open my $interfaces_current_fh,   '<', $network_interfaces_backup;
    open my $interfaces_processed_fh, '>', $network_interfaces_config;

    my $detected_if = 0;
    my $tab         = " " x 8;

  OUTER:
    while ( defined( my $line = <$interfaces_current_fh> ) ) {
        chomp $line;
        if (
            $line ~~ [
                qr{allow-hotplug\s*$admin_interface},
                qr{auto\s*$admin_interface},
                qr{iface $admin_interface},
            ]
          )
        {
            $detected_if = 1;
            say {$interfaces_processed_fh} $line;
            next OUTER;
        }

        if ( $detected_if > 0 && $detected_if <= 4 ) {
            given ($line) {
                when (/address/) {
                    say {
                        $interfaces_processed_fh
                    }
                    $tab, "address $admin_ip";
                    $detected_if += 1;
                    next OUTER;
                }
                when (/netmask/) {
                    say {
                        $interfaces_processed_fh
                    }
                    $tab, "netmask $admin_netmask";
                    $detected_if += 1;
                    next OUTER;
                }
                when (/gateway/) {
                    say {
                        $interfaces_processed_fh
                    }
                    $tab, "gateway $admin_gateway";
                    $detected_if += 1;
                    next OUTER;
                }
                when (/dns-nameservers/) {
                    say {
                        $interfaces_processed_fh
                    }
                    $tab, "dns-nameservers $admin_dns";
                    $detected_if += 1;
                    next OUTER;
                }
                when (/^\s*#/) {
                    say {
                        $interfaces_processed_fh
                    }
                    $line;
                    next OUTER;
                }
                default {
                    say {
                        $interfaces_processed_fh
                    }
                    "# $line\t# WARNING: Unrecognized option";
                    next OUTER;
                }
            }
        }

        say {$interfaces_processed_fh} $line;
    }
    close($interfaces_current_fh);
    close($interfaces_processed_fh);
    chmod oct(600), $network_interfaces_config;
}

sub network_config_apply {
    my %config    = AV::ConfigParser::current_config;
    my %config_last     = AV::ConfigParser::last_config;
    my $interface = $config{'interface'};
    my $interface_last = $config_last{'interface'};

    console_log("Restarting interface $interface");
    system "ifdown $interface";
    if ( $? == 0 ) {
        system "ifup $interface";
        if ( $interface ne $interface_last ) {
            console_log("Taking down previously configured interface $interface_last");
            system "ifconfig $interface_last down";
        }
    }else {
        console_log("Error while taking down interface $interface");
    }
}

sub _network_read_debian {    #list interfaces in debian setup
    my $network_interfaces_config = '/etc/network/interfaces';
    my @return_interface_list;

    open my $network_interfaces_config_fh, '<', $network_interfaces_config;
    while ( defined( my $line = <$network_interfaces_config_fh> ) ) {
        chomp $line;
        if ( $line ~~ qr{iface} ) {
            push @return_interface_list, ( $line =~ qr{\s+(\S+)} );
        }
    }
    close $network_interfaces_config_fh;
    return \@return_interface_list;
}

sub _network_ifaces_ip {    #list interfaces seen from 'ip a'
    my @return_interface_list;

    my @output = qx{ ip a };
    push @return_interface_list, ( $_ =~ /^\d\:\s(\S+)\:/ ) for @output;
    return \@return_interface_list;
}

sub network_sniffing_interfaces {
    my @network_interfaces_ip     = @{ _network_ifaces_ip() };
    my @network_interfaces_debian = @{ _network_read_debian() };
    my %config                    = AV::ConfigParser::current_config;
    my $sensor_interfaces         = $config{'sensor_interfaces'};
    my @network_sniffing_interfaces;


    for (@network_interfaces_ip) {
	push @network_sniffing_interfaces, $_ if (( $_ ~~ @network_interfaces_ip ) && !( $_ ~~ @network_interfaces_debian));
    }
    return \@network_sniffing_interfaces;
}

sub vpn_tun_network_stats {
    my $vpn_tun = shift;

    my @vpn_tun_stats;

    my @output = qx{ ifconfig $vpn_tun };
    chomp for @output;
    my $out = join '\n', @output;

    my $vpn_addr = $1 if $out =~ /addr:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/;
    my $vpn_mask = $1 if $out =~ /Mask:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/;
    my $vpn_gway = $1 if $out =~ /P-t-P:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/;

    push @vpn_tun_stats, ( "$vpn_tun\[address\]=$vpn_addr" );
    push @vpn_tun_stats, ( "$vpn_tun\[netmask\]=$vpn_mask" );
    push @vpn_tun_stats, ( "$vpn_tun\[gateway\]=$vpn_gway" );

    return \@vpn_tun_stats;
}

1;
