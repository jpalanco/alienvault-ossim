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

package AvupdateSystem;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use POSIX qw/ strftime /;
use Data::Dumper;
use DateTime;
use DateTime::Format::Flexible;
use Perl6::Slurp;

use AV::Log;
use AV::Log::File;
use AV::CC::SharedData;
use Linux::APT;
use AV::ConfigParser;
use Avrepository;
my $systemuuid = `/usr/bin/alienvault-system-id`;

#AV::CC::SharedData->set( 'update', 'empty' );

my $update_thr;
my $upgrade_thr;

my $log_update_file = "/var/log/dpkg.log";


sub system_update_proccess_info {

    my @out = ($systemuuid);

    #if ( -f '/var/lib/apt/lists/lock' ) {
    #   push( @out, "lock" );
    #}

    my $apt = Linux::APT->new( debug => 0 );

    my $update = $apt->update;

    if ( $update->{error} ) {

        #my $kkk= Dumper($update->{error});

        if ( $update->{error}->[0]->{message}
            =~ /Some index files failed to download, they have been ignored, or old ones used instead./
            )
        {
            my $msg = 'Error: ' . $update->{error}->[0]->{message};
            #push( @out, $msg );
            #return @out;
        }
        else {
            my $msg = "Error: " . $update->{error}->[0]->{message};
            push @out, $msg;
            return @out;
        }
    }

    #
    # upgrade
    #
    if ( !-s "$log_update_file" ) {
        $log_update_file = "/var/log/dpkg.log.1";
    }
    #my $last_update = `tail -1 /var/log/dpkg.log  | awk {'print \$1,\$2'}`; $last_update =~ s/n//g;
    chomp( my $log_update
            = `tail -1 $log_update_file  | awk {'print \$1,\$2'}` );
    my $tzone = slurp { chomp => 1 }, '/etc/timezone';
    my $last_update
        = DateTime::Format::Flexible->parse_datetime( "$log_update DUMMYTZ",
        tz_map => { DUMMYTZ => $tzone }, );
    $last_update->set_time_zone('UTC');

    push @out, $last_update->datetime();

    my $toupgrade = $apt->toupgrade;

    if ( $toupgrade->{error} ) {
        my $msg = 'Error: ' . $toupgrade->{error}->[0]->{message} . '\n';
        push @out, $msg;
        return @out;
    }

    #print Dumper( $toupgrade->{packages} );

    for ( keys( %{ $toupgrade->{packages} } ) ) {

        my $new_v     = "$toupgrade->{packages}->{$_}->{new}";
        my $current_v = "$toupgrade->{packages}->{$_}->{current}";
        my $source    = "$toupgrade->{packages}->{$_}->{source}";
        my $size      = "$toupgrade->{packages}->{$_}->{size}";
        my $string    = "Inst $_ [$current_v] ($new_v) ($size)";

        #print "------------> $string";
        push @out, $string;
    }
    return @out;
}

sub system_update {
    my @out = ($systemuuid);
    
    if ( AV::CC::SharedData->lock_if_empty_fail_otherwise( 'update', 'starting update' ) ) {
        push( @out, "Wake up thread for update system in background" ) if $0 =~ /av-centerd/ ;
        console_log_file("Starting update system");
        
        update_thread( @_ ) and exit unless fork;
    }
    else {
        push @out, 'update already in progress...';
    }
    return @out;
}

sub update_thread {
    my $interval = 2;

    unless ( defined $_[0] ) {
        @_ = ( q{} );
    }
    my $args = join q{ }, @_;

    my %sysconf = Avrepository::get_current_repository_info();
    # TODO:  append + logrotate
    system("echo > /var/log/alienvault-center_update.log");
    debug_log("alienvault-update -v $args |");
    open( F, "alienvault-update -v $args |" );

    while (<F>) {
        chomp;
        AV::CC::SharedData->set( 'update', "$_" );
        system("echo \"$_\" >> /var/log/alienvault-center_update.log");
        console_log_file("Thread Alienvault-update -> $_");
        console_log("Thread Alienvault-update -> $_") if $0 !~ /av-centerd/ ;
    }

    if ( !-s "$log_update_file" ) {
        $log_update_file = "/var/log/dpkg.log.1";
    }
    chomp( my $log_update
            = `tail -1 $log_update_file  | awk {'print \$1,\$2'}` );
    my $tzone = slurp { chomp => 1 }, '/etc/timezone';
    my $last_date
        = DateTime::Format::Flexible->parse_datetime( "$log_update DUMMYTZ",
        tz_map => { DUMMYTZ => $tzone }, );
    $last_date->set_time_zone('UTC');
    console_log_file("Finished update system, kill thread in $last_date");
    AV::CC::SharedData->set( 'update', 'empty' );
    AV::CC::SharedData->set( 'update_last_date', $last_date->datetime() );
}

1;
