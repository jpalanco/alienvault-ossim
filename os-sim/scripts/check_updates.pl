#!/usr/bin/perl
#
# License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2014 AlienVault
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
use lib "/usr/share/ossim/include";
use DBI;
use ossim_conf;

$update_location = "/etc/ossim/updates/update_log.txt";
$update_location_pro = "/etc/ossim/updates/update_log_pro.txt";

$check_enable = $ossim_conf::ossim_data->{"update_checks_enable"}; 
$pro = ($ossim_conf::ossim_data->{"ossim_server_version"} =~ /pro|demo/) ? 1 : 0;

if($check_enable ne "yes")
{
exit();
}

$use_proxy = $ossim_conf::ossim_data->{"update_checks_use_proxy"}; 
$update_url = $ossim_conf::ossim_data->{"update_checks_source"}; 
$proxy_str = "";
if($use_proxy eq "yes")
{
$proxy_url = $ossim_conf::ossim_data->{"proxy_url"}; 
$ENV{http_proxy} = $proxy_url;
$proxy_user = quotemeta $ossim_conf::ossim_data->{"proxy_user"}; 
$proxy_password = quotemeta $ossim_conf::ossim_data->{"proxy_password"}; 
$proxy_str = "--proxy-user=$proxy_user --proxy-password=$proxy_password";
}

$update_url =~ s/\\/\\\\/g;
$update_url =~ s/\'/\\\'/g;

system("wget --quiet -O $update_location $proxy_str '$update_url'\n");

if ($pro && $ossim_conf::ossim_data->{"update_checks_pro_source"}) {
  $update_url = $ossim_conf::ossim_data->{"update_checks_pro_source"};
  $update_url =~ s/\\/\\\\/g;
  $update_url =~ s/\'/\\\'/g;  
  system("wget --quiet -O $update_location_pro $proxy_str '$update_url'\n");
}
