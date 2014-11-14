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

package Avstatistic;

use v5.10;
use strict;
use warnings;
#use diagnostics;

#use AV::Log::File; for *_log_file()

my $VERSION = 1.00;

my $IOSTAT = 1;

sub get_iostat_pstat {

    my %sysconf;
    my $sysconf_name;

    while ( my $process = shift ) {

        my @ps_output = `ps xua`;
        foreach (@ps_output) {
            if (m/$process/) {

                $_ =~ s/\n//g;

                my @element = split( /\s+/, $_ );

                my $ps_id  = $element[1];
                my $ps_cpu = $element[2];
                my $ps_mem = $element[3];
                my $ps_vsz = $element[4];
                my $ps_rss = $element[5];

#verbose_log_file("CPU/MEM stats: name($process) id($ps_id) cpu($ps_cpu%) mem($ps_mem%) vsz($ps_vsz) rss($ps_rss)");
#debug_log_file("Process $process: $_");
                my $process_c = $process;
                $process_c =~ s/\s+//g;

                #
                # Update sysconf hash
                #

                $sysconf_name = "ps_cpu___" . $process_c;
                $sysconf{$sysconf_name} = $ps_cpu;

                $sysconf_name = "ps_mem___" . $process_c;
                $sysconf{$sysconf_name} = $ps_mem;

                $sysconf_name = "ps_vsz___" . $process_c;
                $sysconf{$sysconf_name} = $ps_vsz;

                $sysconf_name = "ps_rss___" . $process_c;
                $sysconf{$sysconf_name} = $ps_rss;

                #
                # Create rrd database
                #

#my $rrd_file = "$configd{'daemon.regdir'}/$hostname-current/bbdd/psstat_$process_c.rrd";
#if ( ! -f "$rrd_file" ){

#	my $interval_1h = 3600/$interval ;  # 1 hora = 3600 seg
#	my $interval_2h = 7200/$interval ;  #2 horas = 7200 seg
#	my $interval_6h = 21600/$interval ; #6 horas = 21600 seg
#	my $interval_12h_pre = 10 * $interval ; # solo cogemos cada 10 mediciones.
#	my $interval_12h = 43200/$interval_12h_pre ; # 12 horas = 43200 seg
#	my $interval_1w_pre = 30 * $interval; # 1 semana =  7x24x60x60=604800 segundos
#	my $interval_1w = 604800/$interval_1w_pre ; # 1 semana =  7x24x60x60=604800 segundos
#	#24 horas = 86400 seg
#	my $interval_d = $interval * 2;

#	system("mkdir -p $configd{'daemon.regdir'}/$hostname-current/bbdd/") if ( ! -d "$configd{'daemon.regdir'}/$hostname-current/bbdd/" );

                #	my $rrd_command = "
                #	rrdtool create $rrd_file --step $interval
                #	DS:cpu:GAUGE:$interval_d:0:U
                #	DS:mem:GAUGE:$interval_d:0:U
                #	DS:vsz:GAUGE:$interval_d:0:U
                #	DS:rss:GAUGE:$interval_d:0:U
                #	RRA:AVERAGE:0.5:1:$interval_1h
                #	RRA:AVERAGE:0.5:1:$interval_2h
                #	RRA:AVERAGE:0.5:1:$interval_6h
                #	RRA:AVERAGE:0.5:10:$interval_12h
                #	RRA:AVERAGE:0.5:30:$interval_1w
                #	RRA:MAX:0.5:1:$interval_1h
                #	RRA:MAX:0.5:1:$interval_2h
                #	RRA:MAX:0.5:1:$interval_6h
                #	RRA:MAX:0.5:10:$interval_12h
                #	RRA:MAX:0.5:30:$interval_1w
                #	RRA:MIN:0.5:1:$interval_1h
                #	RRA:MIN:0.5:1:$interval_2h
                #	RRA:MIN:0.5:1:$interval_6h
                #	RRA:MIN:0.5:10:$interval_12h
                #	RRA:MIN:0.5:30:$interval_1w" ;

                #	$rrd_command =~ s/\n//g;
                #	$rrd_command =~ s/\t/ /g;
                #	debug_log_file("Build rrd database: $rrd_command");
                #	system("$rrd_command");
                #}

      #
      # update rrd database
      #
      #my $cmd = "rrdtool update $rrd_file N:$ps_cpu:$ps_mem:$ps_vsz:$ps_rss";
      #debug_log_file("update rrd: $cmd");
      #system($cmd);
            }

        }

        if ( !map ( /$process/, @ps_output ) ) {

#console_log_file("CPU/MEM stats: name($process) -- not CPU/MEM input for process $process");
            my $process_c = $process;
            $process_c =~ s/\s+//g;

            $sysconf_name = "ps_cpu___" . $process_c;
            $sysconf{$sysconf_name} = "NULL";

            $sysconf_name = "ps_mem___" . $process_c;
            $sysconf{$sysconf_name} = "NULL";

            $sysconf_name = "ps_vsz___" . $process_c;
            $sysconf{$sysconf_name} = "NULL";

            $sysconf_name = "ps_rss___" . $process_c;
            $sysconf{$sysconf_name} = "NULL";

        }

        ## iotop

        my @iotop_output = `iotop -b -P -n1 -k`;
        if ($IOSTAT) {

            foreach (@iotop_output) {
                if (m/$process/) {
                    $_ =~ s/\n//g;

                    my @element = split( /\s+/, $_ );

                    my $io_id = $element[0];

                    #my $io_disk_read=$element[3]." ".$element[4];
                    #my $io_disk_write=$element[5]." ".$element[6];
                    #my $io_swapin=$element[7]." ".$element[8];
                    #my $io_io=$element[9]." ".$element[10];

                    my $io_disk_read  = $element[3];
                    my $io_disk_write = $element[5];
                    my $io_swapin     = $element[7];
                    my $io_io         = $element[9];

#verbose_log_file("I/O stats    : name($process) id($io_id) read($io_disk_read K/s) write($io_disk_write K/s) swapin($io_swapin %) io($io_io %)");
#debug_log_file("Process $process: $_");

                    my $process_c = $process;
                    $process_c =~ s/\s+//g;

                    #
                    # Update sysconf hash
                    #

                    $sysconf_name = "io_disk_read___" . $process_c;
                    $sysconf{$sysconf_name} = $io_disk_read;

                    $sysconf_name = "io_disk_write___" . $process_c;
                    $sysconf{$sysconf_name} = $io_disk_write;

                    $sysconf_name = "io_swapin___" . $process_c;
                    $sysconf{$sysconf_name} = $io_swapin;

                    $sysconf_name = "io_io___" . $process_c;
                    $sysconf{$sysconf_name} = $io_io;

                    #
                    # Create rrd database
                    #

#my $rrd_file = "$configd{'daemon.regdir'}/$hostname-current/bbdd/iotop_$process_c.rrd";
#if ( ! -f "$rrd_file" ){

#	my $interval_1h = 3600/$interval ;  # 1 hora = 3600 seg
#	my $interval_2h = 7200/$interval ;  #2 horas = 7200 seg
#	my $interval_6h = 21600/$interval ; #6 horas = 21600 seg
#	my $interval_12h_pre = 10 * $interval ; # solo cogemos cada 10 mediciones.
#	my $interval_12h = 43200/$interval_12h_pre ; # 12 horas = 43200 seg
#	my $interval_1w_pre = 30 * $interval; # 1 semana =  7x24x60x60=604800 segundos
#	my $interval_1w = 604800/$interval_1w_pre ; # 1 semana =  7x24x60x60=604800 segundos
#	#24 horas = 86400 seg
#	my $interval_d = $interval * 2;
#
#	system("mkdir -p $configd{'daemon.regdir'}/$hostname-current/bbdd/") if ( ! -d "$configd{'daemon.regdir'}/$hostname-current/bbdd/" );

                    #	my $rrd_command = "
                    #	rrdtool create $rrd_file --step $interval
                    #	DS:disk_read:GAUGE:$interval_d:0:U
                    #	DS:disk_write:GAUGE:$interval_d:0:U
                    #	DS:disk_swapin:GAUGE:$interval_d:0:U
                    #	DS:disk_io:GAUGE:$interval_d:0:U
                    #	RRA:AVERAGE:0.5:1:$interval_1h
                    #	RRA:AVERAGE:0.5:1:$interval_2h
                    #	RRA:AVERAGE:0.5:1:$interval_6h
                    #	RRA:AVERAGE:0.5:10:$interval_12h
                    #	RRA:AVERAGE:0.5:30:$interval_1w
                    #	RRA:MAX:0.5:1:$interval_1h
                    #	RRA:MAX:0.5:1:$interval_2h
                    #	RRA:MAX:0.5:1:$interval_6h
                    #	RRA:MAX:0.5:10:$interval_12h
                    #	RRA:MAX:0.5:30:$interval_1w
                    #	RRA:MIN:0.5:1:$interval_1h
                    #	RRA:MIN:0.5:1:$interval_2h
                    #	RRA:MIN:0.5:1:$interval_6h
                    #	RRA:MIN:0.5:10:$interval_12h
                    #	RRA:MIN:0.5:30:$interval_1w" ;

                    #	$rrd_command =~ s/\n//g;
                    #	$rrd_command =~ s/\t/ /g;
                    #	debug_log_file("Build rrd database: $rrd_command");
                    #	system("$rrd_command");
                    #}

#
# update rrd database
#
#my $cmd = "rrdtool update $rrd_file N:$io_disk_read:$io_disk_write:$io_swapin:$io_io";
#debug_log_file("update rrd: $cmd");
#system($cmd);
                }

            }

            if ( !map ( /$process/, @iotop_output ) ) {

#console_log_file("I/O stats    : name($process) -- not I/O input for process $process");
                my $process_c = $process;
                $process_c =~ s/\s+//g;

                $sysconf_name = "io_disk_read___" . $process_c;
                $sysconf{$sysconf_name} = "NULL";

                $sysconf_name = "io_disk_write___" . $process_c;
                $sysconf{$sysconf_name} = "NULL";

                $sysconf_name = "io_swapin___" . $process_c;
                $sysconf{$sysconf_name} = "NULL";

                $sysconf_name = "io_io___" . $process_c;
                $sysconf{$sysconf_name} = "NULL";

            }

        }
        else {

            #verbose_log_file("iotop not found! disable I/O stat");
        }
    }
    return %sysconf;

}

1;
