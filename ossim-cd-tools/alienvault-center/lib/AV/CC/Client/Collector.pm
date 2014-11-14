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

package AV::CC::Client::Collector;

use v5.10;
use strict;
use warnings;

use Perl6::Slurp;
use File::Basename;
use File::Copy;
use File::Path;
use Data::Dumper;

my $regdir = '/usr/share/alienvault-center/regdir';
use AV::Log;
use AV::ConfigParser;
use Avtools;

use AV::Status;
use AV::Debian::Versions;

sub get_statistics_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_statistics', $siem_component );
    if ( not defined( $response->result ) ) {
        say "Guru meditation";
    }
    else {
        my $response_msg = $response->result;

        my @resp = split '---', $response_msg;
        say for @resp;
    }

    return;
}

sub get_status_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_status', $siem_component );

    my $response_msg  = $response->result;
    my @response_parm = $response->paramsout;

    if (@response_parm) {
        say for @response_parm;
    }

    return;
}

sub get_system_status_in_remote {
    my $siem_component = shift;
    my $filename       = q{system_status};

    my $response =
      AV::CC::Client::Interface::run( 'get_system_status', $siem_component );

    eval { my @response_msg = @{ $response->result() }; };
    if ($@) {
        AV::Status::down( $regdir, $siem_component, $filename );
    }
    else {
        AV::Status::up( $regdir, $response, $filename );
    }
    return;
}

sub get_alienvault_status_in_remote {
    my $siem_component = shift;
    my $filename       = q{alienvault_status};

    my $response = AV::CC::Client::Interface::run( 'get_alienvault_status',
        $siem_component );

    eval { my @response_msg = @{ $response->result() }; };
    if ($@) {
        AV::Status::down( $regdir, $siem_component, $filename );
    }
    else {
        AV::Status::up( $regdir, $response, $filename );
    }
    return;
}

sub get_network_status_in_remote {
    my $siem_component = shift;
    my $filename       = q{network_status};

    my $response =
      AV::CC::Client::Interface::run( 'get_network_status', $siem_component );
    eval { my @response_msg = @{ $response->result() }; };
    if ($@) {
        AV::Status::down( $regdir, $siem_component, $filename );
    }
    else {
        AV::Status::up( $regdir, $response, $filename );
    }
    return;
}

sub get_service_in_remote {

    my $siem_component = shift;
    my @ext;
    my $service = shift;
    push( @ext, "$service" );

    my $response =
      AV::CC::Client::Interface::run( 'get_service', $siem_component, @ext );

    my @response_msg = @{ $response->result() };
    shift @response_msg;
    say for @response_msg;
    return;
}

sub get_log_line_in_remote {
    my $siem_component = shift;
    my @ext;
    my $r_file      = shift;
    my $number_line = shift;
    push( @ext, "$r_file" );
    push( @ext, "$number_line" );

    my $response =
      AV::CC::Client::Interface::run( 'get_log_line', $siem_component, @ext );

    my @response_msg = @{ $response->result() };

    my $ruta = "$regdir/$response_msg[0]/logs/";
    mkpath $ruta unless -d $ruta;

    shift(@response_msg);

    my $status = $response_msg[0];
    shift(@response_msg);

    if ( $status ne "ready" ) {
        console_log("$status");
        return;
    }
    else {
        my $dest_file = $ruta . basename($r_file);
        open my $dest_file_fh, q{>}, $dest_file;
        print {$dest_file_fh} @response_msg;
        close $dest_file_fh;
    }
}

sub get_network_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_network', $siem_component );

    my @response_msg = $response->result();

    my $ruta = "$regdir/$response_msg[0]";
    
    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );

    shift(@response_msg);

    my $ruta_file = "$ruta/network_status";
    verbose_log("save $ruta_file");

    open LFILE, "> $ruta_file";
    foreach (@response_msg) {
        print LFILE "$_";
        print "$_";
    }
    close(LFILE);

    return;
}



sub get_dpkg_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_dpkg', $siem_component );

    my @response_msg = @{ $response->result() };

    my $ruta = "$regdir/$response_msg[0]";
    
    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );

    shift(@response_msg);

    my $ruta_file = "$ruta/dpkg_total";
    verbose_log("save $ruta_file");

    open LFILE, "> $ruta_file";
    foreach (@response_msg) {
        print LFILE "$_";
    }
    close(LFILE);

    return;
}

sub get_repository_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_repository', $siem_component );

    my @response_msg = @{ $response->result() };

    print $response_msg[1] . "\n";

    return;

}

sub update_system_in_remote {
    my $siem_component = shift;
    my $feed           = shift;

    debug_log('$feed undefined') unless defined $feed;

    my $response =
      AV::CC::Client::Interface::run( 'update_system', $siem_component, $feed );

    my @response_msg = @{ $response->result() };

    #my $response_msg = $response->result;
    #my @response_parm = $response->paramsout;
    shift(@response_msg);

    console_log($_) for @response_msg;

    # FIXME: early return?
    return;

    my $ruta = "$regdir/$response_msg[1]";

    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );
    
    my $ruta_file = "$regdir/$response_msg[1]/last_dist-upgrade-status";
    verbose_log("Save dist-upgrade status in $ruta_file");

    open LFILE, "> $ruta_file";
    print LFILE "$response_msg[0]";
    close(LFILE);

    system("cat $ruta_file") if ( -f "$ruta_file" );

    return;
}

sub upgrade_pro_web {
    my $siem_component = shift;
    my $pro_key        = shift;

    verbose_log('Upgrading to AlienVault USM Professional with key ' . $pro_key);
    my $response = AV::CC::Client::Interface::run( 'upgrade_pro_web', $siem_component, $pro_key, 'pro' );

    my @response_msg;

    eval { @response_msg = @{ $response->result() }; };
    if ($@) {
        print ("Operation failed: $@");
	exit 1;
    }
    else {
        say "Operation finished.";
        exit 0;
    }
    return;
}


sub update_system_info_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'update_system_info', $siem_component );

    my @response_msg = @{ $response->result() };

    #foreach(@response_msg){

    #		verbose_log("$_");
    #}

    #	verbose_log("Response msg: ----------------> $kkk");
    my $ruta = "$regdir/$response_msg[0]";
    
    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );

    my $ruta_file_changes =
      "$regdir/$response_msg[0]/last_dist-upgrade-changes";
    my $software_status = "$regdir/$response_msg[0]/software_status";
    my $last_update     = "$response_msg[1]";

    if ( $last_update eq "lock" ) {

        verbose_log("Apt lock, exiting");
        print "Apt lock,exiting\n";
        return;

    }

    my @software_status_content;

    #if ( $response_msg[1] =~ /^Error/ ) {
    #    verbose_log("$response_msg[1]");
    #    open EFILE ">$ruta/error_in_update";
    #    print EFILE "$response_msg[1]";
    #    close(EFILE);
    #    print "$response_msg[1]\n";
    #    return;
    #}
    #foreach(@response_msg){

    #		print "$_\n";

    #}

    shift(@response_msg);
    shift(@response_msg);

    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );
    
    verbose_log("Save dist-upgrade status in $ruta_file_changes");

    open LFILE, "> $ruta_file_changes";
    foreach (@response_msg) {
        print LFILE "$_\n";

    }

    close(LFILE);

    my $file = $software_status;
    my $mode = 0664;

    my $uid = $>; # Effective UID of running process
    my $gid = getgrnam('www-data'); # Get numeric group for www-data

    chown $uid, $gid, $file;
    chmod $mode, $file;

    my $packages_pending_updates = $#response_msg + 1;

    ## get_dpkg

    $response = AV::CC::Client::Interface::run( 'get_dpkg', $siem_component );

    @response_msg = @{ $response->result() };

    $ruta = "$regdir/$response_msg[0]";
    
    mkpath $ruta unless -d $ruta;
    AV::CC::Client::Collector::put_perms ( $ruta );

    shift(@response_msg);

    my $ruta_file = "$ruta/dpkg_total";

    verbose_log("save $ruta_file");

    open LFILE, "> $ruta_file";
    foreach (@response_msg) {
        print LFILE "$_";
    }
    close(LFILE);

    my $packages_installed        = 0;
    my $packages_installed_failed = 0;
    my $packages_purge_pending    = 0;
    my $packages_other_status     = 0;
    foreach (@response_msg) {
        if    (/^ii/) { $packages_installed++; }
        elsif (/^iF/) { $packages_installed_failed++; }
        elsif (/^rc/) { $packages_purge_pending++; }
        else          { $packages_other_status++; }

    }

    push( @software_status_content, "[Software]" );
    push( @software_status_content, "packages_installed=$packages_installed" );
    push( @software_status_content,
        "packages_installed_failed=$packages_installed_failed" );
    push( @software_status_content,
        "packages_pending_updates=$packages_pending_updates" );
    push( @software_status_content,
        "packages_pending_purge=$packages_purge_pending" );
    push( @software_status_content, "latest_update=$last_update" );

    verbose_log("save $software_status");
    open LFILE, q{>}, $software_status;
    foreach (@software_status_content) {
        print LFILE "$_\n";
    }
    close(LFILE);

    ## end dpkg

    $response =
      AV::CC::Client::Interface::run( 'get_repository', $siem_component );

    @response_msg = @{ $response->result() };

    my $repo = $response_msg[1];

    $ruta_file = "$ruta/repository";
    system("echo $repo > $ruta_file");

    verbose_log("Save $ruta_file ($response_msg[2]) ");

    #return;

    $repo = $response_msg[2];

    my $data_domain = "http://data.alienvault.com";

    my $current_repo = $repo;
    $current_repo =~ s/Packages//g;
    $current_repo =~ s/\/binary\///g;
    $current_repo =~ s/http:\/\/data.alienvault.com\///g;

    my $change_log_file =
        $data_domain . "/"
      . $current_repo
      . "/binary/www_changelog/total_withversion.gz";

    my $dest_dir = "$regdir/$response_msg[0]";

    verbose_log("Save $current_repo ");
    verbose_log("Save $change_log_file ");
    verbose_log("Save $dest_dir ");

    console_log("Update info from packages in alienvault.com");
    my $command =
      "cd $dest_dir ; wget --timeout=10 $change_log_file > /dev/null 2>&1";
    debug_log($command);
    system($command);

    if ( -f "$dest_dir/total_withversion.gz" ) {
        verbose_log("exec: UNZIP $dest_dir/total_withversion.gz");
        system("gunzip -f $dest_dir/total_withversion.gz");
    }
    else {

        error("File changelog not found !!");
        exit 0;

    }

    if ( -f "$ruta_file_changes" ) {

        my $command =
"cat $ruta_file_changes | awk '{print \$2,\$3,\$4}' | sed 's/\\\[//g' | sed 's/\\\]//g' |sed 's/(//g' 2> /dev/null";

        my @pkt_changes = qx { $command };
        open D, "> $dest_dir/last_dist-upgrade-changes_extended";

        foreach (@pkt_changes) {

            chomp;
            my @pe              = split( " ", $_ );
            my $pname           = $pe[0];
            my $current_version = $pe[1];
            my $new_version     = $pe[2];

            if ( -f "$dest_dir/total_withversion" ) {

                my @filter_changes =
                  `grep "${pname}_"  $dest_dir/total_withversion`;
                foreach (@filter_changes) {
                    chomp;
                    my @myline                = split( '\|', $_ );
                    my $description           = $myline[4];
                    my $ver_pkg               = $myline[5];
                    my @ver_pkg_split         = split( "_", $ver_pkg );
                    my $current_version_clean = $current_version;
                    $current_version_clean =~ s/.*://;
                    my $commit_date = $myline[0];
                    $commit_date =~ s/<//g;

                    my $current_version_c = $current_version_clean;
                    my $remote_version_c = ( $ver_pkg_split[1] // q{} );

                    if (
                        AV::Debian::Versions::compare(
                            $remote_version_c, 'gt', $current_version_c
                        )
                      )
                    {
                        print D
"[$pname] [$current_version_clean] [$ver_pkg_split[1]] [$ver_pkg] [$description] [$commit_date]\n";
                    }
                }

            }
        }

        close(D);

    }

    my $release_file="$ruta/release_info";

    my $output = system( "wget -O $release_file http://data.alienvault.com/RELEASES/release_info");
    if ( -s "$ruta/release_info" ) {
        return;
    }
    else {
        unlink "$ruta/release_info";
    }
    return;
}

sub update_system_info_in_remote_debian_package {
    my $siem_component = shift;

    my @ext;
    my $debian_package = shift;
    push( @ext, "$debian_package" );

    my $response =
      AV::CC::Client::Interface::run( 'update_system_info_debian_package',
        $siem_component, @ext );

    my @response_msg = @{ $response->result() };
    say for @response_msg;

    return;
}

sub changelog_diff {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_dpkg', $siem_component );

    my @response_msg = @{ $response->result() };

    #verbose_log("send $name_l_file to remote server");
    my $ruta_file = "$regdir/$response_msg[1]/dpkg_total";

    open LFILE, "> $ruta_file";
    print LFILE "$response_msg[0]";
    close(LFILE);

    $response =
      AV::CC::Client::Interface::run( 'get_repository', $siem_component );

    @response_msg = @{ $response->result() };

    my $repo        = $response_msg[1];
    my $data_domain = "http://data.alienvault.com";

    my $current_repo = $data_domain . "/" . $repo . "/binary/";

    my $change_log_file = $current_repo . "www_changelog/total_withversion.gz";

    my $dest_dir = "$regdir/$response_msg[0]";

    console_log("Update info from packages in alienvault.com");
    my $command =
      "cd $dest_dir ; wget --timeout=10 $change_log_file > /dev/null 2&>1";
    debug_log($command);
    system($command);

    if ( -f "$dest_dir/total_withversion.gz" ) {
        verbose_log("exec: UNZIP $dest_dir/total_withversion.gz");
        system("gunzip -f $dest_dir/total_withversion.gz");
        verbose_log(
"exec: mv $dest_dir/total_withversion $dest_dir/total_withversion-$repo"
        );
        system(
            "mv $dest_dir/total_withversion $dest_dir/total_withversion-$repo"
        );
    }
    else {

        error("File changelog not found !!");
        exit 0;

    }

    if ( -f "$ruta_file" ) {

        my @dpkg_remote_info =
          `cat $ruta_file | grep -v "^Desired=" | grep -v "^|" | grep -v "^+"`;

#my @dpkg_extended_remote_info =  `cat $dest_dir/total_withversion-$repo| awk -F\\\| '{print \$NF}'`;
        my @dpkg_extended_remote_info = `cat $dest_dir/total_withversion-$repo`;
        my @dpkg_extended_remote_info_ext =
`cat $dest_dir/total_withversion-$repo| awk -F\\\| '{print \$1 \$NF \$(NF-1)}' | awk -F\\\< '{print \$2}'`;

#my @dpkg_extended_remote_info =  `cat /tmp/total_withversion| awk -F\\\| '{print \$NF}'`;
#my @dpkg_extended_remote_info_ext =  `cat /tmp/total_withversion| awk -F\\\| '{print \$1 \$NF \$(NF-1)}' | awk -F\\\< '{print \$2}'`;

        my @dpkg_remote_info_clean;
        my @dpkg_remote_info_clean_name;
        my @dpkg_remote_info_clean_version;

        foreach (@dpkg_remote_info) {

            #print $_ . "\n";
            #s\/n//g;
            my @content_dpkg = split( " ", $_ );
            my $status       = $content_dpkg[0];
            my $name         = $content_dpkg[1];
            my $version      = $content_dpkg[2];
            my $versionclean = $version;
            $versionclean =~ s/.*://g;
            my $description = $content_dpkg[3];

            my $pktcomp = "${name}_${versionclean}";
            push( @dpkg_remote_info_clean,         $pktcomp );
            push( @dpkg_remote_info_clean_name,    $name );
            push( @dpkg_remote_info_clean_version, $versionclean );

            my $search = $name . "_";

            my @pkt_change;
            foreach (@dpkg_extended_remote_info) {

                if (/.*\s\|\s$search/) {
                    my @content_dpkg = split( ' \| ', $_ );
                    my $date         = $content_dpkg[0];
                    my $sha          = $content_dpkg[1];
                    my $develop      = $content_dpkg[2];
                    my $developemail = $content_dpkg[3];
                    my $commit       = $content_dpkg[4];
                    my $pkg          = $content_dpkg[5];
                    $pkg =~ s/\n//g;

                    my @pkgclean   = split( "_", $pkg );
                    my $pkgName    = $pkgclean[0];
                    my $pkgVersion = $pkgclean[1];
                    my $pkgArch    = $pkgclean[2];

                   #print "-----------$pkgName  $versionclean > $pkgVersion \n";

                    if ( $versionclean lt $pkgVersion ) {
                        my $cad =
                          $pkgName . " " . $pkgVersion . " " . $commit . "\n";

                        push( @pkt_change, $cad );
                    }

                }

            }

            foreach (@pkt_change) {
                print $_ ;

            }

        }

    }

    return;
}





sub get_current_task_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'get_current_task', $siem_component );

    my @response_msg = @{ $response->result() };

    #my $response_msg  = $response->result;
    #my @response_parm = $response->paramsout;

    say for @response_msg;

    return;

}

sub get_last_task_update {
    my $siem_component = shift;
    my $response =
      AV::CC::Client::Interface::run( 'get_last_update_task', $siem_component );

    my @response_msg = @{ $response->result() };

    print for @response_msg;

    return;

}

sub exec_reconfig_in_remote {
    my $siem_component = shift;

    my $response =
      AV::CC::Client::Interface::run( 'reconfig_system', $siem_component );

    my @response_msg = @{ $response->result() };

    # FIXME:  Why remove the first element?
    shift @response_msg;

    console_log($_) for @response_msg;

    return;
}

sub put_perms {
    
    my $path = shift;
    my $file_owner = shift // q{};
    my $file_group = shift // q{};
    my $file_permission = shift // q{};
    
    $file_owner = 'www-data' if $file_owner eq q{};
    $file_group = 'alienvault' if $file_group eq q{};

    $file_permission = ((-d $path) ? '0755' : '0644') if $file_permission eq q{};

    my $uid = getpwnam($file_owner);
    my $gid = getgrnam($file_group);

    console_log("Setting owner/group for file $path: $file_owner/$file_group");

    chown $uid, $gid, $path;
    chmod oct($file_permission), $path;
    
    return;
}



1;



