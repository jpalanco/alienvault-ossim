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


package Avrepository;

use v5.10;
use strict;
use warnings;
#use diagnostics;
use AV::ConfigParser;
use AV::Log;
use Avproxy;

my $VERSION    = 1.00;
my $dataserver = "http://data.alienvault.com";
my $current_version_alienvault;
my $current_version_local;
my $cversion = q{};
my $csubversion = q{};
my $csubsubversion = q{};
my $DEBUGLOG             = 0;
my $dbinary              = "dialog";
my $profesional_version  = 0;
my $profesional_version2 = 0;
my $profesional_version3 = 0;
my $profesional_version4 = 0;
my $profesional_code = q{};
my $profesional_code2;
my $profesional_code3;
my $profesional_code4;
my $free_version  = 0;
my $free_version2 = 0;
my $free_version3 = 0;
my $free_version4 = 0;
my $percent       = 0;
my $dialog_active = 1;

my $arch = `uname -m`;
$arch =~ s/\n//g;

sub get_current_repository_info_old() {

    my %sysconf;

    # current distro
    #

    my @current_sources_list = `cat /etc/apt/sources.list`;

    my $repos_unstable = 0;
    my $repos_testing  = 0;
    foreach (@current_sources_list) {
        next if /^[\t\w]+#/;
        s/#.*//;

        if (/data\.alienvault\.com/) {
            if (/debian/) {
                s/\n//g;

                #					debug_log_file("repository found : $_");
                $sysconf{'repo_stable'} = "$_";

                next;
            }
            if (/\s+stable/) {
                s/\n//g;

                #					debug_log_file("repository found : $_");
                $sysconf{'repo_stable'} = "$_";

                next;
            }

            if (/unstable/) {
                s/\n//g;

                #					debug_log_file("repository found : $_");
                $sysconf{'repo_unstable'} = "$_";
                $repos_unstable = 1;
                next;
            }
            if (/testing/) {
                s/\n//g;

                #					debug_log_file("repository found : $_");
                $sysconf{'repo_testing'} = "$_";
                $repos_testing = 1;
                next;
            }
        }

    }

    if ( $repos_unstable == 1 ) {
        $sysconf{'distro'}    = "unstable";
        $sysconf{'repo_free'} = $sysconf{'repo_unstable'};
    }
    elsif ( $repos_testing == 1 ) {
        $sysconf{'distro'}    = "testing";
        $sysconf{'repo_free'} = $sysconf{'repo_testing'};
    }
    else {
        $sysconf{'distro'}    = "stable";
        $sysconf{'repo_free'} = $sysconf{'repo_stable'};
    }

#verbose_log_file("Common Profile: Current alienvault distro : $sysconf{'distro'}");
#verbose_log_file("Common Profile: Current repository $sysconf{'repo_free'}");

=pod
	#
	# compare last packages in data.alienvault repository 
	#
	
	if ( $configd{'daemon.internet_available'} eq "yes" ){
		
			verbose_log_file("Download Packages from repository: $sysconf{'repo_free'}");
			my $download_repo = $sysconf{'repo_free'}; 
			$download_repo =~ s/^deb\s+//;
			$download_repo =~ s/\s+//g;
			
			verbose_log_file("Wget timeout is : $wget_timeout");
			my $cmd = "cd $configd{'daemon.regdir'}/$hostname-current/; wget -nc -T$wget_timeout $download_repo/Packages.gz";
			verbose_log_file("Download $download_repo/Packages.gz");
			debug_log_file("exec: $cmd");
			system("$cmd");
			
			#
			#  if Packages.gz exist
			#
			
			if ( -f "$configd{'daemon.regdir'}/$hostname-current/Packages.gz" ){
				
				verbose_log_file("$configd{'daemon.regdir'}/$hostname-current/Packages.gz download success");
				my @repos_packages_current = `zcat $configd{'daemon.regdir'}/$hostname-current/Packages.gz`;
				my @packets_current_clean;				
				
				#
				# Into the file
				#
				
				foreach(@repos_packages_current){
				
						my $pkg_name;
						my $pkg_version;
						my $fnd = 0;
						my @pkg_name;
						my @pkg_version;

						if (/^Package:\s/){ s/\n//g ; @pkg_name = split(/:\s/,$_) ; next; }
						if (/^Version:\s/){ s/\n//g; @pkg_version = split(/:\s/,$_) ; $fnd = 1;}
						
						
						
						my $pkg_repo = $pkg_name[1] ." ". $pkg_version[1];
						if ( $fnd == 1){
								
						debug_log_file("Pacakge in data.alienvault.com: $pkg_repo") ;
						
								
								if (map(/(\w\w\s)$pkg_name[1]\s(.*)/,@packets_current_dpkg_clean)){
									
									if (map(/(\w\w\s)$pkg_repo(.*)/,@packets_current_dpkg_clean)){
										  debug_log("$pkg_repo Found");
									}else{
										  verbose_log_file("!!! $pkg_name[1] not update !!!");
									}
									
								}else{
										verbose_log_file("$pkg_name[1] not installed");
									
								}
								
								
						}
						
				}
			
					
			}
			
		
	}
=cut

    return %sysconf;

}

sub get_current_repository_info() {

    Avproxy::config_system_proxy;

    my %sysconf;
    my @sourceslist;
    my $stdout;
    my $stderr;
    system("mkdir /etc/alienvault-center")
        if ( !-d "/etc/alienvault-center" );

    if   ($DEBUGLOG) { $stdout = "";             $stderr = ""; }
    else             { $stdout = " >/dev/null "; $stderr = " 2>&1"; }
    my $wget = `which wget`;
    error(
        "wget command not found, please install it using: apt-get install wget"
    ) if ( $wget eq "" );

    #	console_log("Checking current version");

    my $alienvault_version_file
        = "/etc/alienvault-center/alienvault-info-version";

#		console_log("cat $alienvault_version_file | grep \"^Product:\" | awk -F: '{print \$2}'");
    if ( -s "$alienvault_version_file" ) {

#		console_log("cat $alienvault_version_file | grep \"^Product:\" | awk -F: '{print \$2}'");
        $current_version_local
            = `cat $alienvault_version_file | grep "^Product:" | awk -F: '{print \$2}'`;
        $current_version_local =~ s/\n//g;
        $current_version_local =~ s/ //g;

        $cversion = `cat $alienvault_version_file | grep "^Version:" | awk -F': +' '{print \$2}'`;
        $cversion =~ s/\n//g; $cversion =~ s/\n//g;
        $csubversion = `cat $alienvault_version_file | grep "^Subversion:" | awk -F': +' '{print \$2}'`;
        $csubversion =~ s/\n//g; $csubversion =~ s/\n//g;
        $csubsubversion = `cat $alienvault_version_file | grep "^Subsubversion:" | awk -F': +' '{print \$2}'`;
        $csubsubversion =~ s/\n//g; $csubsubversion =~ s/\n//g;

    }
    else {
        $current_version_local = "alienvault0";
    }

#			console_log("cat $alienvault_version_file | grep \"^Product:\" | awk -F: '{print \$2}'");
#		console_log("Checking professional version");

    # online

    my $dirtoget = "/etc/apt/sources.list.d";
    opendir( IMD, $dirtoget );
    my @sourcesfile = readdir(IMD);
    close(IMD);

    #console_log("Add /etc/apt/sources.list");
    push( @sourceslist, "/etc/apt/sources.list" );
    foreach (@sourcesfile) {


        if (/^alienvault.*list/) {
            console_log("Add $dirtoget/$_");
            push( @sourceslist, "$dirtoget/$_" );

        }
    }

    foreach (@sourceslist) {
        s/\n//g;
#        my @content = `cat $_ | grep -v "^#" | grep "data.alienvault.com"`;
        my @content = `cat $_ | grep -v "^#" | grep -v "\/mirror\/" | grep "data.alienvault.com"`;
        foreach (@content) {
            my @deb_line = split( " ", $_ );
            my $repo_remote = $deb_line[1] . "/binary/Packages";
            system("rm -rf /tmp/sourceslistd");

#console_log("Donloading $repo_remote"); dp("Downloading Updates information\nPlease be patient ... ");
            system("wget -O /tmp/sourceslistd $repo_remote  $stdout $stderr");
            if ( -s "/tmp/sourceslistd" ) {
                my $search_pro
                    = `cat /tmp/sourceslistd | grep -v "ossim-server-dbg" | grep -A2 "Package: ossim-server" | grep "^Version:" |awk -F: '{print \$2}'`;
                my $search_pro_version
                    = `cat /tmp/sourceslistd | grep -v "ossim-server-dbg" | grep -A2 "Package: ossim-server" | grep "^Version:" |awk -F: '{print \$3}'| awk -F\. '{print \$1}'`;
                $search_pro =~ s/ //g;
                $search_pro =~ s/\n//g;
                my $search_pro_number = 0;
                $search_pro_number = int ($search_pro) if ($search_pro ne q{});

                if ( $search_pro_number >= 10 ) {

                    $profesional_version = 1;

                    my $search_pro_version
                        = `cat /tmp/sourceslistd | grep -v "ossim-server-dbg" | grep -A2 "Package: ossim-server" | grep "^Version:" |awk -F: '{print \$3}'| awk -F\. '{print \$1}'`;
                    $search_pro_version =~ s/ //g;
                    $search_pro_version =~ s/\n//g;

#			verbose_log("FIND REPO PRO: $repo_remote found ($search_pro) : ($search_pro_version)");
                    if ( $search_pro_version <= 2 ) {
                        $profesional_version2 = 1;
                        $profesional_code2    = $repo_remote;

                    }
                    if ( $search_pro_version == 3 ) {
                        $profesional_version3 = 1;
                        $profesional_code3    = $repo_remote;
                    }
                    if ( $search_pro_version == 4 ) {
                        $profesional_version4 = 1;
                        $profesional_code4    = $repo_remote;
                    }

                }
                else {

                    my $search_free_version
                        = `cat /tmp/sourceslistd | grep -v "ossim-server-dbg" | grep -A2 "Package: ossim-server" | grep "^Version:" |awk -F: '{print \$3}'| awk -F\. '{print \$1}'`;
                    $search_free_version =~ s/ //g;
                    $search_free_version =~ s/\n//g;
                    my $search_free_version_number = 0;
                    $search_free_version_number = int ($search_free_version) if ($search_free_version ne q{});

     #		verbose_log("Repo found: $repo_remote (<10): ($search_free_version)");
                    if ( $search_free_version_number <= 2 ) { $free_version2 = 1; }
                    if ( $search_free_version_number == 3 ) { $free_version3 = 1; }
                    if ( $search_free_version_number == 4 ) { $free_version4 = 1; }

                }

                if ( $profesional_version == 1 ) {

                    if ( $profesional_version2 == 1 ) {
                        $current_version_local = "alienvault2-pro";
                        $profesional_code      = $profesional_code2;
                    }
                    if ( $profesional_version3 == 1 ) {
                        $current_version_local = "alienvault3-pro";
                        $profesional_code      = $profesional_code3;
                    }
                    if ( $profesional_version4 == 1 ) {
                        $current_version_local = "alienvault4-pro";
                        $profesional_code      = $profesional_code4;

                        #$current_version_local = "unstable-pro";
                    }

                }
                else {

                    if ( $free_version2 == 1 ) {
                        $current_version_local = "alienvault2";
                    }
                    if ( $free_version3 == 1 ) {
                        $current_version_local = "alienvault3";
                    }
                    if ( $free_version4 == 1 ) {
                        $current_version_local = "alienvault4";
                        #$current_version_local = "unstable";
                    }

                }

            }
            else { console_log("Error downloading $repo_remote"); }

        }

    }

#console_log("Checking download server status");
#system("wget -O /tmp/alienvault-update-current $dataserver/RELEASES/current  $stdout $stderr");
#error("ERROR: The current AlienVault distribution could not be downloaded $dataserver")  if ( -z "/tmp/alienvault-update-current" );
#$current_version_alienvault = `cat /tmp/alienvault-update-current`; $current_version_alienvault =~ s/\n//g;

    #console_log("Current version in $web is $current_version_alienvault");
    #console_log("Installed version: $current_version_local");

    #if (  $current_version_local eq "alienvault3-pro" ){
    #	if ( -f "/etc/apt/sources.list.d/unstable.list" ){

    #		$current_version_local = "unstable";
    #		$profesional_code= "unstable_pro_zsync";
    #	}
    #}

    $sysconf{'distro'} = $current_version_local;
    if ( $profesional_code ne "" ) {

        #$profesional_code =~ s/"http:\/\/data.alienvault.com\/"//g;
        $sysconf{'code'} = $profesional_code;
    }
    else {
        $sysconf{'code'} = $current_version_local;
    }

    $sysconf{'version'} = $cversion;
    $sysconf{'subversion'} = $csubversion;
    $sysconf{'subsubversion'} = $csubsubversion;

    return %sysconf;

}
1;

