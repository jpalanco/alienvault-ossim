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

package Avtools;

use v5.10;
use strict;
use warnings;
no warnings 'experimental::smartmatch';
#use diagnostics;

use Config::Tiny;
use DBI;
use File::Basename;

use Time::HiRes qw(usleep nanosleep);

use AV::ConfigParser;
use AV::Log;

sub get_database {
    # FIXME
    my %config      = AV::ConfigParser::current_config;
    my %config_last = AV::ConfigParser::last_config;

    my $server_hostname = $config{'hostname'};
    my $server_port     = "40001";
    my $server_ip       = $config{'server_ip'};
    my $framework_port  = $config{'framework_port'};
    my $framework_host  = $config{'framework_ip'};
    my $db_host         = $config{'database_ip'};
    my $db_pass         = $config{'database_pass'};

    my @profiles_arr;

    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    my $profile_database = 0;

    foreach my $profile (@profiles_arr) {

        given ($profile) {
            when ( m/Database/ ) { $profile_database = 1; }
        }

    }

    #verbose_log("Checking DB");dp("Checking DB");

    #	if ( $config{'database_ip'} eq $debconf{'iplocal'} ){
    #		$config{'database_ip'} = "localhost";
    #	}

    my $conn = "";
    my $dsn
        = "dbi:"
        . $config{'database_type'} . ":"
        . $config{'database_ossim'} . ":"
        . $config{'database_ip'} . ":"
        . $config{'database_port'} . ":";

    #debug_log("Database Profile: 1st -- Use $dsn,$config{'database_user'},$config{'database_pass'}");
    $conn = DBI->connect(
        $dsn,
        $config{'database_user'},
        $config{'database_pass'}
    );

    if ( !$conn ) {
        console_log(
            "Error: Unable to connect to OSSIM DB with config file settings, trying old settings"
        );
        dp( "Unable to connect to OSSIM DB with config file settings, trying old settings"
        );
        $dsn
            = "dbi:"
            . $config_last{'database_type'} . ":"
            . $config_last{'database_ossim'} . ":"
            . $config_last{'database_ip'} . ":"
            . $config_last{'database_port'} . ":";

        debug_log(
            "Database Profile: 2st -- $dsn,$config_last{'database_user'},$config_last{'database_pass'}"
        );

        $conn = DBI->connect(
            $dsn,
            $config_last{'database_user'},
            $config_last{'database_pass'},

        );

        if ( !$conn ) {
            warning("Can't connect to Database\n");    #exit 0;
        }

        console_log("Database password change detected");
        system("dpkg-trigger --no-await alienvault-mysql-set-grants");
        system("dpkg --configure --pending");
    }

    return $conn;

}

sub execute_query_without_return {

    #my %config = AV::ConfigParser::current_config;
    my $conn = get_database();

    for my $sentence (@_) {
        my $sth = $conn->prepare($sentence);
        debug_log($sentence);
        $sth->execute();
        $sth->finish();
    }

    $conn->disconnect
        || verbose_log("Disconnect error.\nError: $DBI::errstr");
    return;
}

1;
