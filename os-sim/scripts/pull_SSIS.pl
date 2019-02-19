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

# Purpose : Pull event ID list with brute force directly from Microsoft TechNet
#           And generates the SQL statements we need.
# If there's any problem, please contact: nelsonh@infinet.com.tw

use 5.010;
use strict;
use warnings;
use LWP::Simple;

my $plugin_id = 1655;

print <<HEADER;
-- snare-msssis
-- plugin_id: $plugin_id;
DELETE FROM plugin WHERE id = "$plugin_id";
DELETE FROM plugin_sid where plugin_id = "$plugin_id";

INSERT INTO plugin (id, type, name, description) VALUES ($plugin_id, 1, 'snare-msssis', 'MS SQL Server Integration Services');

HEADER

sub get_event_id {
    return unpack("N", pack("B32", substr('0' x 32 . substr(sprintf("%b", shift), -16), -32)));
}

my @sections = get('http://technet.microsoft.com/en-us/library/ms345164.aspx')
  =~ /MTPS_CollapsibleSection.+?tableSection(.+?)<\/table>/sg;

foreach my $table (@sections) {
    foreach my $row ($table =~ /<tr>(.+?)<\/tr>/sg) {
        my @cols = ($row =~ /<td>(.+?)<\/td>/sg)[0, 3];

        if ($cols[0] && $cols[1]) {
            map { s/<.+?>//g } @cols;
            $cols[0] = get_event_id(hex $cols[0]);
            $cols[1] =~ s/'/\\'/g;
            print 'INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) ';
            say "VALUES ($plugin_id, $cols[0], NULL, NULL, 1, 5, 'MS SSIS: $cols[1]');";
        }
    }
}
