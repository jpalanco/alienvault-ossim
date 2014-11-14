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

package Avproxy;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use AV::ConfigParser;
use AV::Log;

my $VERSION = 1.00;

sub config_system_proxy() {

    # config proxy ?

    my %config = AV::ConfigParser::current_config;

    my $wget_config_file = "/etc/wgetrc";
    my $apt_config_file  = "/etc/apt/apt.conf.d/00alienvault-center-proxy";
    my $curl_config_file = "/etc/curlrc";

    my $proxy_user;
    my $proxy_pass;
    my $proxy_port;
    my $proxy_dns;

    if ( $config{'update_proxy'} eq "disabled" ) {

        # clean wget config file
        #system("echo \"\" > $wget_config_file");
        system("rm -f $wget_config_file") if ( -f "$wget_config_file" );

        # clean apt config file
        system("rm -f $apt_config_file") if ( -f "$apt_config_file" );

        # clean curl config file
        system("rm -f $curl_config_file") if ( -f "$curl_config_file" );
    }

    if ( $config{'update_proxy'} eq "alienvault-center" ) {

        $proxy_port = "3128";

        # config wget file
        system("echo \"use_proxy = on\" > $wget_config_file");
        system(
            "echo \"http_proxy = http://$config{'framework_ip'}:$proxy_port/\" >> $wget_config_file"
        );
        system(
            "echo \"ftp_proxy = http://$config{'framework_ip'}:$proxy_port/\" >> $wget_config_file"
        );

        # config apt file
        system(
            "echo \"Acquire::http::Proxy \\\"http://$config{'framework_ip'}:$proxy_port\\\";\"  > $apt_config_file  ;"
        );

        # config curl file
        system(
            "echo \"proxy = $config{'framework_ip'}:$proxy_port\"  > $curl_config_file"
        );
    }

    if ( $config{'update_proxy'} eq "manual" ) {

        my $proxy_user=q{};
        my $proxy_pass=q{};

        $proxy_user = $config{'update_proxy_user'} . ":"
            if ( $config{'update_proxy_user'} ne "disabled" );
        $proxy_pass = $config{'update_proxy_pass'} . "@"
            if ( $config{'update_proxy_pass'} ne "disabled" );
        $proxy_port = $config{'update_proxy_port'}
            if ( $config{'update_proxy_port'} ne "disabled" );
        $proxy_dns = $config{'update_proxy_dns'}
            if ( $config{'update_proxy_dns'} ne "disabled" );

        # config wget file
        system("echo \"use_proxy = on\" > $wget_config_file");
        system(
            "echo \"http_proxy = http://$proxy_user$proxy_pass$proxy_dns:$proxy_port/\" >> $wget_config_file"
        );
        system(
            "echo \"ftp_proxy = http://$proxy_user$proxy_pass$proxy_dns:$proxy_port/\" >> $wget_config_file"
        );

        # config apt file
        system(
            "echo \"Acquire::http::Proxy \\\"http://$proxy_user$proxy_pass$proxy_dns:$proxy_port\\\";\"  > $apt_config_file  ;"
        );

        # config curl file
        system(
            "echo \"proxy = $proxy_dns:$proxy_port\"  > $curl_config_file"
        );
        
        if (( $config{'update_proxy_user'} ne "disabled" ) && ( $config{'update_proxy_pass'} ne "disabled" )) {
            $proxy_user = $config{'update_proxy_user'};
            $proxy_pass = $config{'update_proxy_pass'};
            system(
                "echo \"proxy-user = $proxy_user:$proxy_pass\"  >> $curl_config_file "
            );
        }
    }

}

1;
