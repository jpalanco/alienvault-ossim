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

    my $ossim_user = "root";
    my $snort_user = "root";
    my $osvdb_user = "root";

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
        else {
            console_log(
                "Database Profile: Connection succeeded, moving on -- 2");
            dp("Connection succeeded, moving on");

            if ( "$config{'first_init'}" eq "no" ) {

                if ( $profile_database == 1 ) {

                    my $new_pass = $config{'database_pass'};
                    verbose_log(
                        "Database Profile: Change passwd detect, set new privileges for 127.0.0.1"
                    );

                    my $command
                        = "mysqladmin -h\"127.0.0.1\" -uroot -p$config_last{'database_pass'} password \"$new_pass\"";

                    debug_log("$command");
                    system("$command");

                    my @query_array = (
                        "GRANT ALL on *.* to root@\"$framework_host\" IDENTIFIED BY \'$new_pass\'",
                        "GRANT ALL on *.* to ocs@\"$framework_host\" IDENTIFIED BY \'$new_pass\'",
                        "GRANT ALL on *.* to root@\"$server_ip\" IDENTIFIED BY \'$new_pass\'",
                        "GRANT ALL ON osvdb.* to osvdb@\"$framework_host\" IDENTIFIED BY \"$db_pass\";",
                        "GRANT ALL ON osvdb.* to osvdb@\"$server_ip\" IDENTIFIED BY \"$db_pass\";"

                    );

                    foreach my $query (@query_array) {

                        my $sth = $conn->prepare($query);
                        debug_log("$query");
                        $sth->execute();
                    }

                    # Let cp command to update config_last file, in the footer, at the end of Avconfig_profile_common
                    # $command="sed -i \"s:pass=.*:pass=$new_pass:\" $config_file_last";
                    # debug_log("$command");
                    # system($command);

                }
            }

        }
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

sub execute_command {

    my @params      = @_;
    my $command     = $params[0];
    my $msg_verbose = $params[1];

    verbose_log($msg_verbose);
    debug_log("$command");
    system($command);

}

#	@commados = (
#		("ossim-reconfig","ejecutamos el reconfig"),
#
#	);

#foreach(@commados){
#Avtools::execute_command("comand", "mensaje_verbose");

sub get_system_hostname {

    my $fromfile = "error";
    my $hn       = `cat /etc/hostname`;

    if ($hn) {
        $hn =~ s/\r|\n//g;
        $fromfile = $hn;
    }

    return $fromfile;

}

sub get_system_time {

    my $fromfile = "error";
    my $hn       = `date`;

    if ($hn) {
        $hn =~ s/\r|\n//g;
        $fromfile = $hn;
    }

    return $fromfile;

}

# get_system_uptime()
# Returns uptime in days, minutes and hours
sub get_system_uptime {
    my $out = `uptime`;
    $out =~ s/\n//g;
    my $dateout;

    if ( $out =~ /up\s+(\d+)\s+(day|days),?\s+(\d+):(\d+)/ ) {

        # up 198 days,  2:06
        #return ( $1, $3, $4 );
        $dateout = "$1 days, $3 hours, $4 minutes";
        return $dateout;
    }
    elsif ( $out =~ /up\s+(\d+)\s+(day|days),?\s+(\d+)\s+min/ ) {

        # up 198 days,  10 mins
        #return ( $1, 0, $3 );
        $dateout = "$1 days, 0 hours, $3 minutes";
        return $dateout;
    }
    elsif ( $out =~ /up\s+(\d+):(\d+)/ ) {

        # up 3:10
        #return ( 0, $1, $2 );
        $dateout = "0 days, $1 hours, $2 minutes";
        return $dateout;
    }
    elsif ( $out =~ /up\s+(\d+)\s+min/ ) {

        # up 45 mins
        #return ( 0, 0, $1 );
        $dateout = "0 days, 0 hours, $1 minutes";
        return $dateout;
    }
    else {
        return ();
    }
}

sub indexof {
    for ( my $i = 1; $i <= $#_; $i++ ) {
        if ( $_[$i] eq $_[0] ) { return $i - 1; }
    }
    return -1;
}

sub list_processes {

    my ( $pcmd, $line, $i, %pidmap, @plist, $dummy, @w, $_ );
    my $out = `ps V 2>&1`;
    my %text;
    my %stat_map;

    if (   $out =~ /version\s+([0-9\.]+)\./ && $1 >= 2
        || $out =~ /version\s+\./ )
    {

        # New version of ps, as found in redhat 6
        my $width;
        if ( $1 >= 3.2 ) {

            # Use width format character if allowed
            $width = ":80";
        }
        open( PS,
            "ps --cols 2048 -eo user$width,ruser$width,group$width,rgroup$width,pid,ppid,pgid,pcpu,vsz,nice,etime,time,stime,tty,args 2>/dev/null |"
        );
        $dummy = <PS>;
        for ( $i = 0; $line = <PS>; $i++ ) {
            chop($line);
            $line =~ s/^\s+//g;
            eval { @w = split( /\s+/, $line, -1 ); };
            if ($@) {

                # Hit a split loop
                $i--;
                next;
            }
            if ( $line =~ /ps --cols 500 -eo user/ ) {

                # Skip process ID 0 or ps command
                $i--;
                next;
            }
            if ( @_ && &indexof( $w[4], @_ ) < 0 ) {

                # Not interested in this PID
                $i--;
                next;
            }
            $plist[$i]->{"pid"}    = $w[4];
            $plist[$i]->{"ppid"}   = $w[5];
            $plist[$i]->{"user"}   = $w[0];
            $plist[$i]->{"cpu"}    = "$w[7] %";
            $plist[$i]->{"size"}   = "$w[8] kB";
            $plist[$i]->{"time"}   = $w[11];
            $plist[$i]->{"_stime"} = $w[12];
            $plist[$i]->{"nice"}   = $w[9];
            $plist[$i]->{"args"}
                = @w < 15 ? "defunct" : join( ' ', @w[ 14 .. $#w ] );
            $plist[$i]->{"_group"}  = $w[2];
            $plist[$i]->{"_ruser"}  = $w[1];
            $plist[$i]->{"_rgroup"} = $w[3];
            $plist[$i]->{"_pgid"}   = $w[6];
            $plist[$i]->{"_tty"}
                = $w[13] =~ /\?/ ? $text{'edit_none'} : "/dev/$w[13]";
        }
        close(PS);
    }
    else {

        # Old version of ps
        $pcmd = join( ' ', @_ );
        open( PS, "ps aulxhwwww $pcmd 2>/dev/nul |" );
        for ( $i = 0; $line = <PS>; $i++ ) {
            chop($line);
            if ( $line =~ /ps aulxhwwww/ ) { $i--; next; }
            if ( $line
                !~ /^\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+([\-\d]+)\s+([\-\d]+)\s+(\d+)\s+(\d+)\s+(\S*)\s+(\S+)[\s<>N]+(\S+)\s+([0-9:]+)\s+(.*)$/
                )
            {
                $i--;
                next;
            }
            $pidmap{$3}          = $i;
            $plist[$i]->{"pid"}  = $3;
            $plist[$i]->{"ppid"} = $4;
            $plist[$i]->{"user"} = getpwuid($2);
            $plist[$i]->{"size"} = "$7 kB";
            $plist[$i]->{"cpu"}  = "Unknown";
            $plist[$i]->{"time"} = $12;
            $plist[$i]->{"nice"} = $6;
            $plist[$i]->{"args"} = $13;
            $plist[$i]->{"_pri"} = $5;
            $plist[$i]->{"_tty"}
                = $11 eq "?" ? $text{'edit_none'} : "/dev/tty$11";
            $plist[$i]->{"_status"} = $stat_map{ substr( $10, 0, 1 ) };
            ( $plist[$i]->{"_wchan"} = $9 ) =~ s/\s+$//g;

            if ( !$plist[$i]->{"_wchan"} ) {
                delete( $plist[$i]->{"_wchan"} );
            }
            if ( $plist[$i]->{"args"} =~ /^\((.*)\)/ ) {
                $plist[$i]->{"args"} = $1;
            }
        }
        close(PS);
        open( PS, "ps auxh $pcmd |" );
        while ( $line = <PS> ) {
            if ( $line =~ /^\s*(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+/
                && defined( $pidmap{$2} ) )
            {
                $plist[ $pidmap{$2} ]->{"cpu"}  = $3;
                $plist[ $pidmap{$2} ]->{"_mem"} = "$4 %";
            }
        }
        close(PS);
    }
    return @plist;
}

# get_current_cpu_temps()
# Returns a list of hashes containing core and temp keys
sub get_current_cpu_temps {
    my @rv;
    if ( -f "/usr/bin/sensors" ) {
        open( SENSORS, "sensors </dev/null 2>/dev/null|" );

        while (<SENSORS>) {
            if (/Core\s+(\d+):\s+([\+\-][0-9\.]+)/) {
                push(
                    @rv,
                    {   'core' => $1,
                        'temp' => $2
                    }
                );
            }
            elsif (/CPU:\s+([\+\-][0-9\.]+)/) {
                push(
                    @rv,
                    {   'core' => 0,
                        'temp' => $1
                    }
                );
            }
        }
        close(SENSORS);
    }
    return @rv;
}

sub get_cpu_info {

    my $out = `uptime 2>&1`;
    my $out_r;
    if ( $out =~ /average(s)?:\s+([0-9\.]+),?\s+([0-9\.]+),?\s+([0-9\.]+)/i )
    {
        $out_r = "$2 (1 min) $3 (5 mins) $4 (15 mins)";
    }
    else {
        $out_r = "";
    }
    if ( $out =~ /(\d+) user/i ) {
        $out_r .= ";$1";
    }
    else {
        $out_r .= ";";
    }

    return $out_r;

}

# get_cpu_cores_info
# Parsing "mpstat -P ALL" system command
# Return cpu_core = usage percents
sub get_cpu_cores_info {

    my @out = split( /\n/, `grep "cpu" /proc/stat 2>&1` );
    my $out_r = '', my $out_i = '';

    #
    # cpu load
    #
    my %cpus = ();
    foreach (@out) {
        if (/^(cpu.*?) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+)/) {

            # cpu0 143925181 740916 5584070 29925623 199755 17191 1340584 0 0
            $cpus{$1}{'total1'} = $2 + $3 + $4 + $5 + $6 + $7 + $8;
            $cpus{$1}{'work1'}  = $2 + $3 + $4;
        }
    }
    Time::HiRes::sleep(0.2);
    @out = split( /\n/, `grep "cpu" /proc/stat 2>&1` );
    foreach (@out) {
        if (/^(cpu.*?) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+)/) {

            # cpu0 143925181 740916 5584070 29925623 199755 17191 1340584 0 0
            $cpus{$1}{'total2'} = $2 + $3 + $4 + $5 + $6 + $7 + $8;
            $cpus{$1}{'work2'}  = $2 + $3 + $4;
        }
    }
    foreach my $cpu ( keys %cpus ) {
        my $work  = $cpus{$cpu}{'work1'} - $cpus{$cpu}{'work2'};
        my $total = $cpus{$cpu}{'total1'} - $cpus{$cpu}{'total2'};
        $cpu =~ s/\s*$//;
        my $cpuload_percent;
        eval { 
            $cpuload_percent = sprintf ("%.2f", $work / $total * 100 );
        };
        $cpuload_percent = 0 if $@;
        my $cpuload = sprintf ("%.2f", $cpuload_percent );
        if ( $cpu eq "cpu" ){
			$out_r .= "cpuaverage=$cpuload\n";
		}else{
			$out_r .= "cpu=$cpuload\n";
			
		}
        #$out_r .= "cpu[]=" . ( $work / $total * 100 ) . "\n";
    }

    #
    # cpu info
    #
    @out = split( /\n/, `grep "model name" /proc/cpuinfo 2>&1` );
    my $i = 0;
    foreach (@out) {
        s/.*: //;
        s/\s+/ /g;
        #$out_i .= "cpu" . $i . "=" . $_ . "\n";
        $out_i .= "core[]=" . $_ . "\n";
        $i++;
    }

    #return "[CPU]\n" . $out_r . "[CPU Info]\n" . $out_i;
    return "[CPU]\n" . $out_r ."\n". $out_i;

}

# get_disk_usage_info
# Parsing "df -h" system command
# Return device = usage percents
sub get_disk_usage_info {

    my @out = split( /\n/, `df -h -P | grep "^/" 2>&1` );
    my $out_r = '';
    my $mounted = '';
    $out_r .= "[Disk Usage]\n";
    foreach (@out) {

        # /dev/sda1   141G  70G  64G  53% /
        my @data = split /\s+/;
        $out_r
            .= "device["
            . $data[0]
            . "]="
            . $data[1] . "#"
            . $data[2] . "#"
            . $data[3] . "#"
            . $data[4] . "#"
            . $data[5] . "\n";
        $mounted .= $data[0] . ";";
    }
    #$mounted =~ s/;$//;
    #$out_r .= "[Disk usage]\nmounted=$mounted\n";
    
    my @total_df = split ( /\s+/, `df -h -P --total | grep total 2>&1` );
    $out_r 
		.= "device["
		.$total_df[0]
		. "]="
		. $total_df[1] . "#"
        . $total_df[2] . "#"
        . $total_df[3] . "#"
        . $total_df[4] . "\n";

    return $out_r;
}

# get_memory_info()
# Returns a list containing the real mem, free real mem, swap and free swap
# (In kilobytes).
sub get_memory_info {
    my %m;

    open( MEMINFO, "/proc/meminfo" ) || return ();
    while (<MEMINFO>) {
        if (/^(\S+):\s+(\d+)/) {
            $m{ lc($1) } = $2;
        }
    }
    close(MEMINFO);

    $m{'memfree'} += $m{'buffers'}+$m{'cached'};

    return %m;
}

sub nice_size {
	my $v = shift;
    my ( $units, $uname );
    if ( abs( $v ) > 1024 * 1024 * 1024 * 1024
        || $v >= 1024 * 1024 * 1024 * 1024 )
    {
        $units = 1024 * 1024 * 1024 * 1024;
        $uname = "TB";
    }
    elsif ( abs( $v ) > 1024 * 1024 * 1024 || $v >= 1024 * 1024 * 1024 )
    {
        $units = 1024 * 1024 * 1024;
        $uname = "GB";
    }
    elsif ( abs( $v ) > 1024 * 1024 || $v >= 1024 * 1024 ) {
        $units = 1024 * 1024;
        $uname = "MB";
    }
    elsif ( abs( $v ) > 1024 || $v >= 1024 ) {
        $units = 1024;
        $uname = "kB";
    }
    else {
        $units = 1;
        $uname = "bytes";
    }
    my $sz = sprintf( "%.2f", ( $v * 1.0 / $units ) );
    $sz =~ s/\.00$//;
    return $sz . " " . $uname;
}


sub nice_size_un {
    my ( $units, $uname );
    if ( abs( $_[0] ) > 1024 * 1024 * 1024 * 1024
        || $_[1] >= 1024 * 1024 * 1024 * 1024 )
    {
        $units = 1024 * 1024 * 1024 * 1024;
        $uname = "TB";
    }
    elsif ( abs( $_[0] ) > 1024 * 1024 * 1024 || $_[1] >= 1024 * 1024 * 1024 )
    {
        $units = 1024 * 1024 * 1024;
        $uname = "GB";
    }
    elsif ( abs( $_[0] ) > 1024 * 1024 || $_[1] >= 1024 * 1024 ) {
        $units = 1024 * 1024;
        $uname = "MB";
    }
    elsif ( abs( $_[0] ) > 1024 || $_[1] >= 1024 ) {
        $units = 1024;
        $uname = "kB";
    }
    else {
        $units = 1;
        $uname = "bytes";
    }
    my $sz = sprintf( "%.2f", ( $_[0] * 1.0 / $units ) );
    $sz =~ s/\.00$//;
    return $sz . " " . $uname;
}

=pod
Discover unoptimized MySQL tables and optimize them.
Finds all tables that need optimising and loops through them, running optimise against them. This works server-wide, on all databases and tables.

for table in $(echo "select concat(TABLE_SCHEMA, '.', TABLE_NAME) from information_schema.TABLES where TABLE_SCHEMA NOT IN ('information_schema','mysql') and Data_free > 0" | mysql --skip-column-names); do echo "optimize table ${table}" | mysql; done;
=cut

##
##  For Database profile
##

# statistics
sub mysql_proccess_list {

    #mysqladmin --verbose --user=root --password=temporalo processlist

}

# backup

sub mysql_backup_in_files {

#	for I in $(mysql -e 'show databases' -s --skip-column-names); do mysqldump $I | gzip > "$I.sql.gz"; done

    #or

# for db in $(mysql -e 'show databases' -s --skip-column-names); do mysqldump $db | gzip > "/backups/mysqldump-$(hostname)-$db-$(date +%Y-%m-%d-%H.%M.%S).gz"; done

}

sub mysql_in_utf8 {

# Convert all mysql database  to utf8
# 	mysql --database=dbname -B -N -e "SHOW TABLES"  | awk '{print "ALTER TABLE", $1, "CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;"}' | mysql --database=dbname &
#or
# Convert mysql database from latin1 to utf8
# mysqldump --add-drop-table -uroot -p "DB_name"  | replace CHARSET=latin1 CHARSET=utf8 | iconv -f latin1 -t utf8 | mysql -uroot -p "DB_name"
}

sub mysql_uptime {

# 	mysql -ptemporalo -e"SHOW STATUS LIKE '%uptime%'"|awk '/ptime/{ calc = $NF / 3600;print $(NF-1), calc"Hour" }'

}

sub mysql_db_size {

# 	mysql -u root -pPasswort -e 'select table_schema,round(sum(data_length+index_length)/1024/1024,4) from information_schema.tables group by table_schema;'

}

sub mysql_number_of_querys {

# mysql -uUser -pPassword -N -s -r -e 'SHOW PROCESSLIST' | grep -cv "SHOW PROCESSLIST"
}

# capture mysql queries sent to server
# tshark -i any -T fields -R mysql.query -e mysql.query

sub mysql_size_databases {
    my @dbcontent;
    my $database;
    my $directory = '/var/lib/mysql/';

    for my $entry (glob "$directory/*") {
        next unless -d $entry;
        chomp ( my $size = qx{ du -hb $entry | awk {'print \$1'} } );
        $size /= 1024 * 1024;
        my $database = File::Basename::basename($entry);
        push @dbcontent, "database_size_$database=$size";
    }
    return @dbcontent;
}

sub mysql_size_databases_all {

    my $conn = get_database();
    my @dbcontent;
    my $database;
    my $size_MB;
    my $free_MB;

    my $query
        = q{SELECT table_schema "Data Base Name",	sum( data_length + index_length ) / 1024 / 1024 "Data Base Size in MB", sum( data_free )/ 1024 / 1024 "Free Space in MB" 
	FROM information_schema.TABLES 
	GROUP BY table_schema};

    my $sth = $conn->prepare($query);
    $sth->execute();

    while ( ( $database, $size_MB, $free_MB ) = $sth->fetchrow_array ) {

        push( @dbcontent, "database_size_$database=$size_MB" );

    }
    return @dbcontent;

}

sub mysql_event {

#`tail -1000 /var/log/ossim/server.log | grep Events | tail -1| awk '{print \$NF}'` ; $event_in_database = s/\n//g;
}

sub ha_status {
    my $hacheck_command = 'cl_status hbstatus';
    my $harsc_command = 'cl_status rscstatus';

    my @hadisabled = qx{$hacheck_command};
    return 'NA' if ( /^Heartbeat is stopped.*/ ~~ @hadisabled);

    my @haresources_output = qx{$harsc_command};
    return 'DOWN' if ( /^none$/ ~~ @haresources_output);

    return 'UP';
}

1;
