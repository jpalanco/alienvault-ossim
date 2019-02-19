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

package AV::Netifaces;


use strict;
#use diagnostics;
use warnings;
no warnings 'experimental::smartmatch';
use autodie;
use v5.10;
use File::Copy;
use AV::ConfigParser;
use AV::Log;

sub network_config_debian {

    my $network_interfaces_config = '/etc/network/interfaces';
    my $network_interfaces_backup = '/etc/network/interfaces.old';
    my $resolv_conf               = '/etc/resolv.conf';

    my %config          = AV::ConfigParser::current_config;
    my $admin_ip        = $config{'admin_ip'};
    my $admin_netmask   = $config{'admin_netmask'};
    my $admin_interface = $config{'interface'};
    my $admin_gateway   = $config{'admin_gateway'};
    my $admin_dns       = $config{'admin_dns'};
    $admin_dns =~ s/,/ /ig;

    system("sed -i 's:nameserver.*:nameserver $admin_dns:' $resolv_conf");
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

    my %config          = AV::ConfigParser::current_config;
    my $interface       = $config{'interface'};

    console_log("Restarting interface $interface");
    system("ip ro del default");
    system "ifdown --force $interface";
    if ( $? == 0 ) {
        system "ifup --force $interface";
    }
    else {
        console_log("Error when taking down interface $interface");
    }
}

1;
