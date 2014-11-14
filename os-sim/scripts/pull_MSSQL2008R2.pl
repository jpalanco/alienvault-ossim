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

# Purpose : Pull event ID list with brute force directly from THE MSDN ABYSS(TM)
#           And generates the SQL statements we need.
#           This is for MS SQL Server 2008 R2.
# If there's any problem, please contact: nelsonh@infinet.com.tw

use 5.010;
use strict;
use warnings;
use LWP::Simple;

my $plugin_id = 1654;

print <<HEADER;
-- MSSQLServer
-- plugin_id: $plugin_id;
DELETE FROM plugin WHERE id = "$plugin_id";
DELETE FROM plugin_sid where plugin_id = "$plugin_id";

INSERT INTO plugin (id, type, name, description) VALUES ($plugin_id, 1, 'snare-mssql', 'MS SQL Server');

HEADER

my $base_url = 'http://msdn.microsoft.com/en-us/library/cc645603.aspx';
(my $main_page) = get($base_url) =~ /title="System Error Messages"(.+?)alt="Separator"/s;

foreach my $url ($main_page =~ /<a href="(.+?)"/g) {
    (my $content) = get($url) =~ /<table>(.+?)<\/table>/;
    foreach my $row ($content =~ /<tr>(.+?)<\/tr>/g) {
        my @cols = ($row =~ /<p>(.+?)<\/p>/g)[0, 2, 3];
        map { s/<.+?>//g } @cols;
        $cols[2] =~ s/'/\\'/g;
        if ($cols[1] eq 'Yes') {
            print 'INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) ';
            say "VALUES ($plugin_id, $cols[0], NULL, NULL, 1, 5, 'MSSQLServer: $cols[2]');";
        }
    }
}
