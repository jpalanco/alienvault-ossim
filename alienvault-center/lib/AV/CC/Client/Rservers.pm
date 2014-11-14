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

package AV::CC::Client::Rservers;

use v5.10;
use strict;
use warnings;

use Perl6::Slurp;
use File::Basename;
use File::Copy;
use File::Path;
use Data::Dumper;

use Avconfigd;
use AV::Log;
use AV::Log::File;
use Avtools;

use AV::Status;

use AV::uuid;
my $host_uuid = `/usr/bin/alienvault-system-id`;


sub my_family {
    my $ks             = shift;
    my $conn           = shift;
    my $host_uuid      = shift;
    my $host_uuid_comp = uc $host_uuid;
    $host_uuid_comp =~ s/\-//g;
    my $query;
    my @tot;
    my @total;

    if ( $ks eq "children" ){
        $query = qq(select LOWER(CONCAT_WS('-',substr(hex(child_id),1,8),substr(hex(child_id),9,4),substr(hex(child_id),13,4),substr(hex(child_id),17,4),substr(hex(child_id),21))) from alienvault.server_hierarchy where parent_id = 0x$host_uuid_comp);
    }else {
        $query = qq(select LOWER(CONCAT_WS('-',substr(hex(parent_id),1,8),substr(hex(parent_id),9,4),substr(hex(parent_id),13,4),substr(hex(parent_id),17,4),substr(hex(parent_id),21))) from alienvault.server_hierarchy where child_id = 0x$host_uuid_comp);
    }
    verbose_log_file($query);
    my $sth = $conn->prepare($query);
    $sth->execute();
    while ( my $system_installed = $sth->fetchrow_arrayref ) {
        push @tot, $system_installed->[0];
    }
    $sth->finish();

    for (@tot) {
        my $s_id = $_; $s_id=~s/-//g;
        my $query
            = qq{SELECT inet6_ntop(admin_ip) as admin_ip FROM alienvault.system WHERE id = unhex("$s_id")};
        verbose_log_file("SQL COMMAND : $query");
        my $sth = $conn->prepare($query);
        $sth->execute();

        my $current_ip = $sth->fetchrow_arrayref();
        if ( ( defined $current_ip->[0] ) && ($current_ip->[0] ne "") )
        {
            push @total, $current_ip->[0]
        }

		#
		# vpn
		#
         $query
             = qq{SELECT ifnull(inet6_ntop(vpn_ip),'') as vpn_ip FROM alienvault.system WHERE id = unhex("$s_id")};
         debug_log_file("SQL COMMAND : $query");
         $sth = $conn->prepare($query);
         $sth->execute();
         $current_ip = $sth->fetchrow_arrayref();
        if ( ( defined $current_ip->[0] ) && ($current_ip->[0] ne "") )
        {
            push @total, $current_ip->[0]
        }
 }
    return @total;
}

sub servers_consolidate {
    verbose_log_file('Consolidate servers');
    my $conn  = shift;
    my $query = 'SELECT uuid,admin_ip,vpn_ip FROM avcenter.current_remote';

    #my @s_ins_tot;

    my $sth = $conn->prepare($query);
    $sth->execute();
    while ( my $system_installed = $sth->fetchrow_arrayref ) {
        my $uuid           = $system_installed->[0];
        my $host_uuid_comp = uc $uuid;
        $host_uuid_comp =~ s/\-//g;
        my $ip = $system_installed->[1];
        my $vpnip = $system_installed->[2] // "";

		next if $uuid eq $host_uuid;

        my $bad_uuid_query
            = qq{SELECT hex(id) FROM alienvault.server WHERE inet6_ntop(ip) = "$ip"};
        verbose_log_file ("Is this a bad uuid for $ip?: $bad_uuid_query");

        my $sthB = $conn->prepare($bad_uuid_query);
        $sthB->execute();
        my $bad_uuid = $sthB->fetchrow_array() // "";
        $sthB->finish();

		#
		# vpn
		#

		if ( $bad_uuid eq "" ){
			next if ( $vpnip eq "" );
			$bad_uuid_query
				= qq{SELECT hex(id) FROM alienvault.server WHERE inet6_ntop(ip) = "$vpnip"};
            verbose_log_file ("Is this a bad uuid for $vpnip?: $bad_uuid_query");
            $sthB = $conn->prepare($bad_uuid_query);
            $sthB->execute();
            $bad_uuid = $sthB->fetchrow_array() // "";
            $ip = $vpnip;
            $sthB->finish();
		}

        if ( $bad_uuid ne "" )
        {
            my @qch = (
                qq{UPDATE alienvault.server_hierarchy SET child_id  = UNHEX("$host_uuid_comp") WHERE HEX(child_id)  = "$bad_uuid"},
                qq{UPDATE alienvault.server_hierarchy SET parent_id = UNHEX("$host_uuid_comp") WHERE HEX(parent_id) = "$bad_uuid"},
                qq{UPDATE alienvault.server_forward_role SET server_src_id = UNHEX("$host_uuid_comp") WHERE HEX(server_src_id) = "$bad_uuid"},
                qq{UPDATE alienvault.server_forward_role SET server_dst_id = UNHEX("$host_uuid_comp") WHERE HEX(server_dst_id) = "$bad_uuid"},
                qq{UPDATE alienvault.server_role SET server_id = UNHEX("$host_uuid_comp") WHERE HEX(server_id) = "$bad_uuid"},
                );

            for my $query (@qch) {
                verbose_log_file ("Updating related server table using: $query");
                my $sth = $conn->prepare($query);
                $sth->execute();
                $sth->finish();
            }

            # Servers
            my $update_query
                = qq{UPDATE alienvault.server SET id = UNHEX("$host_uuid_comp") WHERE HEX(id) = "$bad_uuid"};
            verbose_log_file ("Update id for server using: $update_query");
            my $sthC = $conn->prepare($update_query);
            $sthC->execute();
            $sthC->finish();
        }
    }
    $sth->finish();

    # Check for orphaned servers
    my @qch = (
        qq{DELETE FROM alienvault.server_hierarchy WHERE child_id NOT IN (SELECT id FROM server)},
        qq{DELETE FROM alienvault.server_hierarchy WHERE parent_id NOT IN (SELECT id FROM server)},
        qq{DELETE FROM alienvault.server_forward_role WHERE server_src_id NOT IN (SELECT id FROM server)},
        qq{DELETE FROM alienvault.server_forward_role WHERE server_dst_id NOT IN (SELECT id FROM server)},
        qq{DELETE FROM alienvault.server_role WHERE server_id NOT IN (SELECT id FROM server)},
        );

    for my $query (@qch) {
        verbose_log_file ("Delete orphaned server using: $query");
        my $sth = $conn->prepare($query);
        $sth->execute();
        $sth->finish();
    }
}

1;
