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

package AvHACluster;

use v5.10;
use strict;
use warnings;
#use diagnostics;


use Perl6::Slurp;

use AV::ConfigParser;
use AV::Log;

my %config;

my $profile_database  = 0;
my $profile_server    = 0;
my $profile_framework = 0;
my $profile_sensor    = 0;

my $server_hostname;
my $admin_ip;
my $server_ip;

## used for comparison (new vs user settings), this way we are not overwritting
## user settings (m h dom mon) for each rsync module
my $prev_ossim_ha_rsync_cron_file = '';
my $ossim_ha_rsync_client_file = "/usr/local/sbin/ossim_ha-rsync.sh";
my $heartbeat_config_file = "/etc/ha.d/ha.cf";
my $heartbeat_auth_file = "/etc/ha.d/authkeys";
my $heartbeat_resources_file="/etc/ha.d/haresources";
my $ossim_ha_logrotate_file = "/etc/logrotate.d/ossim-ha";
my $ossim_ha_rsync_cron_file = "/etc/cron.d/ossim_ha_rsync";
my $ossim_ha_init_file = "/etc/init.d/ossim-ha";
my $master_hostname;
# cron m (minute counter)
my $rsmm = 0;
my ( $stdout, $stderr ) = ( q{}, q{} );

sub configure_ossim_ha() {

    ## read config and profiles:

    %config      = AV::ConfigParser::current_config;

    $server_hostname = $config{'hostname'};
    $admin_ip        = $config{'server_ip'};
    $server_ip       = $config{'server_ip'};

    my @profiles_arr = map {
        if ($_ eq q{all-in-one}) {
            qw(Sensor Server Database Framework);
        }
        else {
            $_;
        }
    } split( /,\s*/, $config{'profile'} );
    for my $profile (@profiles_arr) {
        given ($profile) {
            when (/Sensor/)    { $profile_sensor    = 1; }
            when (/Server/)    { $profile_server    = 1; }
            when (/Database/)  { $profile_database  = 1; }
            when (/Framework/) { $profile_framework = 1; }
        }
    }


    update_etc_hosts_ha_related();
    build_ha_daemon_config();
    build_ha_resources_config();
    build_ha_daemon_auth();
    build_ha_etc_default_rsync();
    build_rsync_daemon_config();
    build_cron_rsync_client_entries();
    build_rsync_client_script();
    build_rsync_daemon_auth_server_and_client();
    build_ossim_ha_metaresource();
    ## iptables HA entries, handled in common_profile. firewall must be built in one shot (see Avconfig_profile_common.pm)
    build_logrotate_daemon_config_ha_related();


}


## /etc/hosts:
sub update_etc_hosts_ha_related() {

    my $ft_other = `grep "$config{'ha_other_node_name'}" '/etc/hosts'`;
    $ft_other =~ s/\n//g;
    if ( $ft_other eq "" )
    {
        console_log("Updating /etc/hosts, adding ha_other_node_ip/name value");
        my $command = "sed -i \"s:^127.0.0.1.*:127.0.0.1\\tlocalhost\\n$config{'ha_other_node_ip'}\\t$config{'ha_other_node_name'}\\n:\" /etc/hosts";
        debug_log("$command");
        system($command);
    }

    if ( $profile_database == 1 )
    {
        my $s_uuid=`/usr/bin/alienvault-system-id | tr -d '-'`;
        
        verbose_log("Update system table");
        my $command = "echo \"CALL system_update(\'$s_uuid\',\'\',\'\',\'\',\'\',\'$config{'ha_virtual_ip'}\',\'$config{'hostname'}\',\'$config{'ha_role'}\',\'\',\'\')\" | ossim-db $stdout $stderr";
        debug_log($command);
        system($command);
    }
            
}


## /etc/ha.d/ha.cf:
sub build_ha_daemon_config() {

        # heartbeat
        if ( ( $config{'ha_heartbeat_start'} // q{} ) eq "yes" ) {

            # ha_heartbeat_start=yes
            verbose_log("Common Profile, AvHACluster: heartbeat daemon config /etc/ha.d/ha.cf");
            open HADFILE, "> $heartbeat_config_file";
            print HADFILE "keepalive $config{'ha_keepalive'}\n";
            print HADFILE "deadtime $config{'ha_deadtime'}\n";
            print HADFILE "warntime 5\n";
            print HADFILE "initdead 25\n";
            if ( $config{'ha_log'} eq "yes" ) {
                print HADFILE "#debug_log /var/log/ha.log\n";
                print HADFILE "#logfacility     local0\n";
                print HADFILE "logfile /var/log/ha.log\n";
            }

            if ( $config{'ha_autofailback'} eq "yes" ) {
                verbose_log("Common Profile, AvHACluster: heartbeat autofailback ON");
                print HADFILE "auto_failback on\n";
            }
            else {
                verbose_log("Common Profile, AvHACluster: heartbeat autofailback OFF");
                print HADFILE "auto_failback off\n";
            }

            print HADFILE "udpport 694\n";

            if ( $config{'ha_heartbeat_comm'} eq "bcast" ) {
                verbose_log("Common Profile, AvHACluster: heartbeat COMM TYPE BROADCAST");
                print HADFILE "bcast $config{'ha_device'}\n";

                # mcast and serial not implemented
            }
            else {
                verbose_log("Common Profile, AvHACluster: heartbeat COMM TYPE UNICAST");
                print HADFILE
                    "ucast $config{'ha_device'} $config{'ha_other_node_ip'}\n";
            }

            print HADFILE "respawn hacluster /usr/lib/heartbeat/ipfail\n";

            if ( $config{'ha_ping_node'} eq "default" ) {
                print HADFILE "ping $config{'ha_other_node_ip'}\n";
            }
            else {
                print HADFILE "ping $config{'ha_ping_node'}\n";
            }

            print HADFILE "node $config{'hostname'}\n";
            print HADFILE "node $config{'ha_other_node_name'}\n";
            close(HADFILE);

}


## /etc/ha.d/haresources:
sub build_ha_resources_config() {

            if ( "$config{'ha_role'}" eq "master" ) {
                $master_hostname = "$config{'hostname'}";
            }
            else {
                $master_hostname = "$config{'ha_other_node_name'}";
            }
            open HARESOURC, "> $heartbeat_resources_file";
            print HARESOURC
                "$master_hostname IPaddr2::$config{'ha_virtual_ip'} ossim-ha openvpn\n";
            close(HARESOURC);

}


## /etc/ha.d/authkeys:
sub build_ha_daemon_auth() {

            open AUTHDFILE, "> $heartbeat_auth_file";
            print AUTHDFILE "auth 1\n";
            print AUTHDFILE "1 sha1 $config{'ha_password'}\n";
            close(AUTHDFILE);
            `chmod 600 /etc/heartbeat/authkeys`;

}


## /etc/default/rsync:
sub build_ha_etc_default_rsync() {

            open RSYNCDEFAULT, "> /etc/default/rsync";
            print RSYNCDEFAULT "RSYNC_ENABLE=true";
            close(RSYNCDEFAULT);

}


## /etc/rsyncd.conf:
sub build_rsync_daemon_config() {

            if ( -f "/etc/rsyncd.conf" ) {
                if ( !-f "/etc/rsyncd.conf-B") {
                    system ("cp /etc/rsyncd.conf /etc/rsyncd.conf-B ");
                }
            }

            verbose_log("Common Profile, AvHACluster: HA rsync daemon");
            open RSYNCFILE, "> /etc/rsyncd.conf";

            print RSYNCFILE "# /etc/rsyncd.conf\n# HA. rsync daemon config\n# OW\n";

            # /etc/ossim/framework/panel/configs had 0700
            #print RSYNCFILE "uid = www-data\n";
            #print RSYNCFILE "uid = nobody\n";
            print RSYNCFILE "uid = root\n";
            print RSYNCFILE "use chroot = yes\n";
            print RSYNCFILE "max connections = 33\n";
            print RSYNCFILE "syslog facility = local5\n";
            print RSYNCFILE "pid file = /var/run/rsyncd.pid\n";
            print RSYNCFILE "auth users = ruth\n";
            print RSYNCFILE "secrets file = /etc/rsyncd.secrets\n";
            print RSYNCFILE "transfer logging = yes\n";
            print RSYNCFILE "timeout = 600\n";
            print RSYNCFILE "log file = /var/log/ossim/ha_rsync.log\n";
            print RSYNCFILE "\n\n";

            if ( $profile_database == 1 ) {

                # files to exclude for D atabase
                open RSYNCEXCD, "> /etc/rsyncd_exclude-D.conf";
                print RSYNCEXCD "filetoexclude\n";
                close (RSYNCEXCD);

                print RSYNCFILE "\n\n";
                print RSYNCFILE "# Database profile:\n";
                print RSYNCFILE "# ( internal replication )\n";
            }

            if ( $profile_framework == 1 ) {

                # files to exclude for F ramework
                open RSYNCEXCF, "> /etc/rsyncd_exclude-F.conf";
                print RSYNCEXCF "filetoexclude\n";
                close (RSYNCEXCF);

                print RSYNCFILE "\n\n";
                print RSYNCFILE "# Framework profile:\n";

                # dashboards
                print RSYNCFILE "#[etc_ossim_framework_panel_configs]\n#path = /etc/ossim/framework/panel/configs\n";
                print RSYNCFILE "#exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # rrd
                print RSYNCFILE "[var_lib_ossim_rrd]\npath = /var/lib/ossim/rrd\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # flows. exclude 'run/*' (/var/nfsen/run/* (socket, pids))
                print RSYNCFILE "[etc_nfsen]\npath = /etc/nfsen\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";
                print RSYNCFILE "[var_cache_nfdump]\npath = /var/cache/nfdump\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";
                print RSYNCFILE "[var_nfsen]\npath = /var/nfsen\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # nagios
                print RSYNCFILE "[etc_nagios3_conf.d]\npath = /etc/nagios3/conf.d\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";
                print RSYNCFILE "[var_cache_nagios3]\npath = /var/cache/nagios3\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # apache
                print RSYNCFILE "#[var_cache_apache2]\n#path = /var/cache/apache2\n";
                print RSYNCFILE "#exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # ossim_sessions
                print RSYNCFILE "#[var_ossim_sessions]\n#path = /var/ossim/sessions\n";
                print RSYNCFILE "#exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # kismet
                print RSYNCFILE "#[var_ossim_kismet]\n#path = /var/ossim/kismet\n";
                print RSYNCFILE "#exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # backups
                print RSYNCFILE "[var_lib_ossim_backup]\npath = /var/lib/ossim/backup\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # ssh keys remotelogger (gen both and uses private)
                print RSYNCFILE "[etc_ossim_framework_ssh]\npath = /etc/ossim/framework/ssh\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";

                # ssh keys remotelogger (public authorized)
                print RSYNCFILE "[root_.ssh]\npath = /root/.ssh\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-F.conf\n\n";
            }

            if ( $profile_sensor == 1 ) {

                # files to exclude for X ensor
                open RSYNCEXCX, "> /etc/rsyncd_exclude-X.conf";
                print RSYNCEXCX "filetoexclude\n";
                close (RSYNCEXCX);

                print RSYNCFILE "\n\n";
                print RSYNCFILE "# Sensor profile:\n";

                # agent plugins. exclude config.cfg. master id=M, slave id=S (check)
                print RSYNCFILE "[etc_ossim_agent]\npath = /etc/ossim/agent\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-X.conf\n\n";

                # openvas
                print RSYNCFILE "#[var_cache_openvas]\n#path = /var/cache/openvas\n";
                print RSYNCFILE "#exclude from = /etc/rsyncd_exclude-X.conf\n\n";

                # ntop
                print RSYNCFILE "[var_lib_ntop]\npath = /var/lib/ntop\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-X.conf\n\n";
                print RSYNCFILE "[etc_ntop]\npath = /etc/ntop\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-X.conf\n\n";

                # ossec
                print RSYNCFILE "[var_ossec]\npath = /var/ossec\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-X.conf\n\n";
            }

            if ( $profile_server == 1 ) {

                # files to exclude for S erver
                open RSYNCEXCS, "> /etc/rsyncd_exclude-S.conf";
                print RSYNCEXCS "filetoexclude\n";
                close (RSYNCEXCS);

                print RSYNCFILE "\n\n";
                print RSYNCFILE "# Server profile:\n";

                # logger (warn: if external storage)
                print RSYNCFILE "[var_ossim_logs]\npath = /var/ossim/logs\n";
                print RSYNCFILE "exclude from = /etc/rsyncd_exclude-S.conf\n\n";
            }

            close(RSYNCFILE);

}


## /etc/cron.d/ossim_ha_rsync
sub build_cron_rsync_client_entries() {

            verbose_log("Common Profile, AvHACluster: HA rsync cron entries");

            open RSYNCCRONFILE, ">> $ossim_ha_rsync_cron_file";

            if ( -f "/etc/cron.d/ossim_ha_rsync" ) {
                $prev_ossim_ha_rsync_cron_file = `cat /etc/cron.d/ossim_ha_rsync`;
            }else{
                # write info header
                print RSYNCCRONFILE "# /etc/cron.d/ossim_ha_rsync\n# HA. rsync cron\n# Configurable m h dom mon\n\n";
            }

            my $rsmodstr = `grep '^\\\[' /etc/rsyncd.conf | sed 's:\\\]:,:g' | sed 's:\\\[::g'`; $rsmodstr=~ s/\n//g; $rsmodstr =~ s/\s+//g;
            foreach my $rsmodule ( split( /,\s*/, $rsmodstr ) ) {
                my $rsmodulepath = $rsmodule;
                $rsmodulepath =~ s/_/\//g; $rsmodulepath = "/$rsmodulepath";
                debug_log("Common Profile, AvHACluster: HA rsync module, modulepath: $rsmodule, $rsmodulepath");
                if (not map(/$rsmodule*/,$prev_ossim_ha_rsync_cron_file)) {
                    print RSYNCCRONFILE "#$rsmm * * * * root $ossim_ha_rsync_client_file $rsmodule $rsmodulepath >/dev/null\n";
                }else{
                    debug_log("Common Profile, AvHACluster: HA rsync module $rsmodule already updated");
                }
                $rsmm += 2;
            }

            close(RSYNCCLIENTFILE);

}


## /usr/local/sbin/ossim_ha-rsync.sh
sub build_rsync_client_script() {

            verbose_log("Common Profile, AvHACluster: Update HA rsync client commands script");

            open RSYNCCLIENTFILE, "> $ossim_ha_rsync_client_file";
            print RSYNCCLIENTFILE <<EOF;
#!/bin/bash

#
# ha_ rsync client commands for cron entries
# /etc/rsyncd.conf must be identical for both nodes
# /etc/cron.d/ossim_ha_rsync
# 

# -- checks start --
if [ \$# -ne 2 ]; then
        echo " Usage: \$0 remotemodulename localdstpath"
        echo " Example: \$0 var_lib_ossim_rrd /var/lib/ossim/rrd"
        exit 1
fi

ha_other_node_ip=`grep ^ha_other_node_ip= /etc/ossim/ossim_setup.conf| awk -F'=' '{print \$2}'| xargs`
hostname=`grep ^hostname= /etc/ossim/ossim_setup.conf| awk -F'=' '{print \$2}'`
logfile="/var/log/ossim/ha_rsync_client.log"
modul="\$1"
dstpath="\$2"

if [ ! -f /etc/rsyncd.conf ]; then
        echo "/etc/rsyncd.conf not found"
        exit 0
fi
if \$(ping -c 1 -W 1 \$ha_other_node_ip > /dev/null); then
        echo "`date` + ping -c 1 -W 1 \$ha_other_node_ip - OK" >> \$logfile 2>&1
else
        echo "`date` + ping -c 1 -W 1 \$ha_other_node_ip - FAILED" >> \$logfile 2>&1
        exit 0
fi
if [ ! -f /etc/ha.d/haresources ]; then
        echo "/etc/ha.d/haresources not found" >> \$logfile 2>&1
        exit 0
fi
configuredVIP=`grep IPaddr2 /etc/ha.d/haresources |awk -F'IPaddr2::' '{print \$2}' |awk -F' ' '{print \$1}'`
if ip a l |grep \$configuredVIP > /dev/null 2>&1; then
        echo "-- `date` - Active node. Passive node is who reads from me" >> \$logfile 2>&1
        exit 0
fi
# -- checks end --

echo "`date` - Module: \$modul - Start" >> \$logfile
echo "rsync --password-file=/etc/rsyncd.secrets-client --delete -avzh ruth@\$ha_other_node_ip::\$modul \$dstpath >> \$logfile 2>&1"
rsync --password-file=/etc/rsyncd.secrets-client --delete -avzh ruth@\$ha_other_node_ip::\$modul \$dstpath >> \$logfile 2>&1
echo -e "`date` - Module: \$modul - End\\n\\n" >> \$logfile

exit 0

EOF

            close(RSYNCCLIENTFILE);

            `chmod +x $ossim_ha_rsync_client_file`;

}


## rsyncd auth related files:
sub build_rsync_daemon_auth_server_and_client() {

            open RSYNCFILEKEY, "> /etc/rsyncd.secrets";
            print RSYNCFILEKEY "ruth:$config{'ha_password'}\n";
            close(RSYNCFILEKEY);

            open RSYNCFILEKEYC, "> /etc/rsyncd.secrets-client";
            print RSYNCFILEKEYC "$config{'ha_password'}\n";
            close(RSYNCFILEKEYC);

            `chmod 0600 /etc/rsyncd.secrets*`;
        }

}


# /etc/init.d/ossim-ha
# manage ossim services, cron, logrotate depending on profile and node state (active/passsive)
sub build_ossim_ha_metaresource() {

        my @profiles_arrayssss = split( /,\s*/, $config{'profile'} );

        my @service_for_profiles;

        for (@profiles_arrayssss) {

            if (/Server/) { push( @service_for_profiles, "ossim-server" ); }
            if (/Framework/) {
                push( @service_for_profiles,
                    "apache2 ossim-framework nfsen nfdump nagios3 fprobe ossec"
                );
            }
            if (/Sensor/) { push( @service_for_profiles, "ossim-agent" ); }

        }

		push( @service_for_profiles, "alienvault-center" );

        my $string_service = join( ' ', @service_for_profiles );

        open INITHAFILE, "> $ossim_ha_init_file";
        print INITHAFILE <<"EOF";
#!/bin/bash
### BEGIN INIT INFO
# Provides:          ossim-ha
# Required-Start:    \$local_fs \$remote_fs \$network \$syslog
# Required-Stop:
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# X-Interactive:     true
# Short-Description: OSSIM High Availability script
# Description:       OSSIM Heartbeat Resource.
#                    Intended for start/stop services
#                    and related tasks
#                    in active/passive node
### END INIT INFO


#set -e

. /lib/lsb/init-functions

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
DESC="HA resources (services)"
NAME=$ossim_ha_init_file
SCRIPTNAME=/etc/init.d/\$NAME
GW=`route -n | grep "^0.0.0.0" | awk '{print \$2}' | sed "s/ //g"`
VIP=`grep ^ha_virtual_ip= /etc/ossim/ossim_setup.conf| awk -F'=' '{print \$2}'`

disableddirs="/etc/logrotate.d/ha_passive_node /etc/cron.d/ha_passive_node /etc/cron.hourly/ha_passive_node"
for dir in \$disableddirs; do if [ ! -d \$dir ]; then mkdir -v -p \$dir; fi; done

starterlist="ossim-framework ossim-server ossim-agent snort"
crondlist="acid-backup"
cronhlist="ossim-compliance ossim-compliance-iso27001 ossim-sem scheduler-report"

d_services="$string_service"

d_start(){
    # notify new pair MAC-IP to GW
    echo "arping -c 2 -S \$VIP \$GW"
    arping -c 2 -S \$VIP \$GW

    for d_service in \$d_services; do /etc/init.d/\$d_service restart; done

    for starter in \$starterlist; do mv /etc/logrotate.d/ha_passive_node/\$starter /etc/logrotate.d/ >/dev/null 2>&1; done
    for crond in \$crondlist; do mv /etc/cron.d/ha_passive_node/\$crond /etc/cron.d/ >/dev/null 2>&1; done
    for cronh in \$cronhlist; do mv /etc/cron.hourly/ha_passive_node/\$cronh /etc/cron.hourly/ >/dev/null 2>&1; done

    /etc/init.d/monit restart

    ## t.f.! ossim-reconfig -c -v
}

d_stop(){
    /etc/init.d/monit stop

    for starter in \$starterlist; do mv -v /etc/logrotate.d/\$starter /etc/logrotate.d/ha_passive_node/ >/dev/null 2>&1; done
    for crond in \$crondlist; do mv -v /etc/cron.d/\$crond /etc/cron.d/ha_passive_node/ >/dev/null 2>&1; done
    for cronh in \$cronhlist; do mv -v /etc/cron.hourly/\$cronh /etc/cron.hourly/ha_passive_node/ >/dev/null 2>&1; done

    for d_service in \$d_services; do /etc/init.d/\$d_service stop; done

    ps -fea | grep snort | grep -v grep | awk '{print \$2}' |while read line ; do kill -9 \$line >/dev/null 2>&1; done
    killall -9 openvasmd >/dev/null 2>&1
    killall -9 openvasad >/dev/null 2>&1
    killall -9 openvassd >/dev/null 2>&1
}

d_restart() {
    d_stop
    sleep 1
    d_start
}

case "\$1" in
  start)
    log_daemon_msg "Starting \$DESC" "\$NAME"
    if d_start ; then
        log_end_msg \$?
    else
        log_end_msg \$?
    fi
        ;;
  stop)
    log_daemon_msg "Stopping \$DESC" "\$NAME"
    if d_stop ; then
        log_end_msg \$?
    else
        log_end_msg \$?
    fi
        ;;
  restart|force-reload)
    log_daemon_msg "Restarting \$DESC" "\$NAME"
    if d_restart ; then
        log_end_msg \$?
    else
        log_end_msg \$?
    fi
        ;;
  *)
        echo "Usage: \$SCRIPTNAME {start|stop|restart|force-reload}" >\&2
        exit 1
        ;;
esac

exit 0

EOF

        close(INITHAFILE);
        `chmod +x $ossim_ha_init_file`;

}


## /etc/logrotate.d/ossim-ha:
sub build_logrotate_daemon_config_ha_related() {

        open LOGROTATEHAFILE, "> $ossim_ha_logrotate_file";
        print LOGROTATEHAFILE <<EOF;
/var/log/ossim/ha_rsync.log /var/log/ossim/ha_rsync_client.log /var/log/ha.log
{
	rotate 7
	daily
	missingok
	notifempty
	delaycompress
	compress
	copytruncate
}
EOF
        close(LOGROTATEHAFILE);


}

1;
