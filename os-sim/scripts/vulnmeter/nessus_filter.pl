#!/usr/bin/perl
#
###############################################################################
#
#    License:
#
#   Copyright (c) 2003-2006 ossim.net
#   Copyright (c) 2007-2013 AlienVault
#   All rights reserved.
#
#   This package is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; version 2 dated June, 1991.
#   You may not use, modify or distribute this program under any other version
#   of the GNU General Public License.
#
#   This package is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this package; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#   MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt

foreach (<STDIN>) {
    s/ plugins(;|\s)/ vuln_plugins$1/g; 
    s/INSERT/INSERT IGNORE/;
    s/DROP TABLE vuln_plugins/DROP TABLE IF EXISTS vuln_plugins/;
    s/\\(\\+)/\\/g;
    
    # nessus
    s/^\s*(id int NOT NULL)/$1, oid varchar(50) NOT NULL/;
    s/VALUES \('(\d+)'/VALUES ('$1','$1'/;

    # openvas
    s/^\s*(oid varchar\(50\) NOT NULL)/id int NOT NULL, $1/;
    s/primary key \(oid\)/ primary key (id)/;
    s/\'(\d+\.\d+\.\d+\.\d+\.\d+\.\d+\.\d+\.\d+\.\d+\.)(\d+)\'/'$2','$1$2'/;
    
    s/CAN\-(\d+)/CVE-$1/g;
    s/AN\-(\d+)/CVE-$1/g;
    s/([^C])VE\-(\d+)/$1CVE-$2/g;
    
    print $_; 
}