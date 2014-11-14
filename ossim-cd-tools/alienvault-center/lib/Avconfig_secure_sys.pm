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

package Avconfig_secure_system;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use AV::ConfigParser;
use AV::Log;
use AV::Log::File;
use Avtools;

my %config = AV::ConfigParser::current_config;

sub secure_system() {
    console_sec("---------------------------------------");
    console_sec("Configuring Security system");

}

sub cap3_1() {
    console_sec("3.1 -- Unified /tmp and /var/tmp ");
    system("rm -rf /var/tmp");
    system("ln -s /tmp /var/tmp");
}

sub cap3_2() {
    console_sec("3.2 -- Install");
    verbose_sec("Installer pressed install english only select keyboard");
    ## installer pressed install english only select keyboard
    ##
    verbose_sec("Installer network manually not dhcp");

    verbose_sec("Validate root password");

    #
    # not implemented
    #

    verbose_sec("Not privileges user created");
}

sub cap3_3() {
    console_sec("3.3 -- Installer from CD ROM or alienvault-center");

    #
    # not implemented
    #
}

sub cap3_4() {
    console_sec("3.4 -- Select install software");
}

sub cap4() {
    console_sec("4. -- Grub secure");
    verbose_log("Disable boot secuance");
    verbose_log("Configuring Grub password");
    if ( $config{'database_pass'} ne "" ) {

        #open
        system("chmod 600 /boot/grub/menu.lst");

        verbose_log("Configuring Grub password");

    }
    else {
        verbose_log("Configuring Grub password -> ERROR");
    }
}

sub console_sec() {
    my $msg = shift;
    console_log_file("SECURE MODULE : $msg");

}

sub verbose_sec() {
    my $msg = shift;
    console_log_file("SECURE MODULE : -----> $msg");

}
