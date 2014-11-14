#!/usr/bin/env python

#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#


import paramiko
import sys

try:

    host     = sys.argv[1]
    user     = sys.argv[2]
    password = sys.argv[3]

except Exception, e:

    print ( """
    Invalid arguments

    Example:
    check_ssh_credentials.py IP USER PASS
    """)
    
    sys.exit(1)


ssh = paramiko.SSHClient()

ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    ssh.connect(host, port=22 , username=user, password=password, look_for_keys=False)
    
    print 'ok'
    
except Exception, e:
    
    print str(e)

