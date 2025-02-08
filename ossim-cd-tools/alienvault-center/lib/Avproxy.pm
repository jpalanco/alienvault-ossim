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
use URI::Escape;

use AV::ConfigParser;
use AV::Log;

my $VERSION = 1.00;

sub config_system_proxy() {

    # config proxy ?

    my %config = AV::ConfigParser::current_config;

    my $wget_config_file = "/etc/wgetrc";
    my $apt_config_file  = "/etc/apt/apt.conf.d/00alienvault-center-proxy";
    my $curl_config_file = "/etc/curlrc";

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

    if ( $config{'update_proxy'} eq "alienvault-proxy" ) {

        $proxy_port = "3128";

        # config wget file
	# Well, spawn several proccess to write a file... very efficient code (sig :( )
	# Also the code doesn't verify if fails
        #system("echo \"use_proxy = on\" > $wget_config_file");
        #system(
        #    "echo \"http_proxy = http://$config{'framework_ip'}:$proxy_port/\" >> $wget_config_file"
        #);
        #system(
        #    "echo \"ftp_proxy = http://$config{'framework_ip'}:$proxy_port/\" >> $wget_config_file"
        #);

	open(my $fh, ">", $wget_config_file) or die ("Can't open the $wget_config_file error:$!");
	print $fh  "# Automatic generated file\n";
	print $fh "use_proxy = on\n";
	print $fh "http_proxy = http://$config{'framework_ip'}:$proxy_port/\n";
	print $fh "ftp_proxy =  http://$config{'framework_ip'}:$proxy_port/\n";
	print $fh "cache = off\n";
	close $fh;

        # config apt file
        system(
            "echo \"Acquire::http::Proxy \\\"http://$config{'framework_ip'}:$proxy_port\\\";\"  > $apt_config_file  ;"
        );

        # config curl file
        #system(
        #    "echo \"proxy = $config{'framework_ip'}:$proxy_port\"  > $curl_config_file"
        #);
	open (my $fhcurl, ">",  $curl_config_file) or die ("Can't open $curl_config_file error:$!");
	print $fhcurl "proxy = $config{'framework_ip'}:$proxy_port\n";
	print $fhcurl "header = \"Pragma: no-cache\"\n";
	close $fhcurl;
    }

    if ( $config{'update_proxy'} eq "manual" ) {
        my $proxy_credentials=q{};
        my $ascii_proxy_credentials=q{};
        my $use_proxy_credentials = 0;
        my $proxy_curl_credentials = q{};

        # We need to escape special characters in user and passwd
        # in order to avoid problems in wgetrc file
        if ( $config{'update_proxy_user'} ne "disabled" and $config{'update_proxy_user'} ne "" ){
            $proxy_credentials = $config{'update_proxy_user'};
            $ascii_proxy_credentials = uri_escape($config{'update_proxy_user'});

            if ( $config{'update_proxy_pass'} ne "disabled" ){
                $proxy_credentials = $proxy_credentials.":".$config{'update_proxy_pass'};
                $ascii_proxy_credentials = $ascii_proxy_credentials.":".uri_escape($config{'update_proxy_pass'});
            }

            $proxy_curl_credentials = $proxy_credentials;
            $proxy_credentials = $proxy_credentials."@";
            $ascii_proxy_credentials = $ascii_proxy_credentials. "@";

            $use_proxy_credentials = 1;
        }


        $proxy_port = ":" . $config{'update_proxy_port'} if ( $config{'update_proxy_port'} =~ m/^[0-9]{1,5}$/ );
        $proxy_dns = $config{'update_proxy_dns'} if ( $config{'update_proxy_dns'} ne "disabled" );

	open(my $fh, ">", $wget_config_file) or die ("Can't open the $wget_config_file error:$!");
	print $fh  "# Automatic generated file\n";
	print $fh "use_proxy = on\n";
	print $fh "http_proxy = http://$ascii_proxy_credentials$proxy_dns$proxy_port/\n";
	print $fh "ftp_proxy = http://$ascii_proxy_credentials$proxy_dns$proxy_port/\n";
	print $fh "cache = off\n";
	close $fh;



        # config apt file
        system(
            "echo \"Acquire::http::Proxy \\\"http://$proxy_credentials$proxy_dns$proxy_port\\\";\"  > $apt_config_file  ;"
        );

        # config curl file
        #system(
        #    "echo \"proxy = $proxy_dns:$proxy_port\"  > $curl_config_file"
        #);
	open (my $fhcurl, ">",  $curl_config_file) or die ("Can't open $curl_config_file error:$!");
	print $fhcurl "proxy = $proxy_dns$proxy_port\n";
	print $fhcurl "header = \"Pragma: no-cache\"\n";


        if ( $use_proxy_credentials == 1 ) {
            print $fhcurl "proxy-user = $proxy_curl_credentials\n";
        }
	close $fhcurl;

    }

}

1;
