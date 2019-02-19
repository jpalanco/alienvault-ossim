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

package AV::Status;

use v5.10;
use File::Copy;
use File::Path;
use AV::Log;
use Avtools;

sub down_parse {
    my $regdir         = shift;
    my $siem_component = shift;
    my $filename       = shift;

    $conn = Avtools::get_database();
    my $query = "select LOWER(CONCAT(LEFT(hex(system.id), 8), '-', MID(hex(system.id), 9,4), '-', MID(hex(system.id), 13,4), '-', MID(hex(system.id), 17,4), '-', RIGHT(hex(system.id), 12))) as uuid from alienvault.system where inet6_ntoa(admin_ip)=\'$siem_component\';";
    my $sth = $conn->prepare($query);
    $sth->execute();
    my @uuid = $sth->fetchrow_array();

    error('Unknown machine, ignoring connection.') if ( !defined $uuid[0] );

    my $uuid_path = "$regdir/$uuid[0]";

    verbose_log("destination directory = $uuid_path\n");
    my $status_file        = "$uuid_path/$filename";
    my $status_file_backup = "$uuid_path/$filename.old";

    copy( $status_file, $status_file_backup );
    open my $status_file_fh_source, '<', $status_file_backup;
    open my $status_file_fh_target, '>', $status_file;

    while ( defined( my $line = <$status_file_fh_source> ) ) {
        chomp $line;
        $line =~ s/(^|\W+)UP$/$1DOWN/;
        print {$status_file_fh_target} "$line\n";
    }
    close $status_file_fh_source;
    close $status_file_fh_target;
    unlink $status_file_backup;
}

sub down {
    my $regdir         = shift;
    my $siem_component = shift;
    my $filename       = shift;

    $conn = Avtools::get_database();
    my $query = "select LOWER(CONCAT(LEFT(hex(system.id), 8), '-', MID(hex(system.id), 9,4), '-', MID(hex(system.id), 13,4), '-', MID(hex(system.id), 17,4), '-', RIGHT(hex(system.id), 12))) as uuid from alienvault.system where inet6_ntoa(admin_ip)=\'$siem_component\';";
    my $sth = $conn->prepare($query);
    $sth->execute();
    my @uuid = $sth->fetchrow_array();

    error('Unknown machine, ignoring connection.') if ( !defined $uuid[0] );

    my $uuid_path = "$regdir/$uuid[0]";

    verbose_log("destination directory = $uuid_path\n");
    my $status_file = "$uuid_path/$filename";

    open my $status_file_fh, '>', $status_file;
    say {$status_file_fh} "status=DOWN";
    close $status_file_fh;
}

sub up {
    my $regdir       = shift;
    my $response     = shift;
    my $filename     = shift;
    my @response_msg = @{ $response->result() };
    my $ruta         = "$regdir/$response_msg[0]";

    verbose_log("XXXXXXXXXXXXXXX -> $response_msg[0]");

    shift(@response_msg);

    mkpath $ruta unless -d $ruta;
    my $ruta_file = "$ruta/$filename";
    verbose_log("save $ruta_file");

    open my $target_file, '>', $ruta_file;
    foreach (@response_msg) {
        print {$target_file} "$_\n";
        print "$_\n";
    }
    close($target_file);
}

1;
