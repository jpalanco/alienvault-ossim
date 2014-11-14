#!/usr/bin/env python
# encoding: utf-8
"""
Created by Jaime Blasco on 2009-09-14
Ossim Information Automatic Filler for testing purposes

License:

Copyright (c) 2009 AlienVault
All rights reserved.

This package is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 dated June, 1991.
You may not use, modify or distribute this program under any other version
of the GNU General Public License.

This package is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this package; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
MA  02110-1301  USA


On Debian GNU/Linux systems, the complete text of the GNU General
Public License can be found in `/usr/share/common-licenses/GPL-2'.

Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
"""

from ossimLib import *

'''

Snare event snarewindows  	1518  	592 from  10.1.1.100 to	10.1.1.200  

4 Pix Deny events with src 10.1.1.101 and dst 192.168.1.201 port 445 src and dst

'''

def config1():
	o = ossim()

	#Clear ossim data
	o.clearData()
	o.connect()
	src_ip = '10.1.1.100'
	dst_ip = '10.1.1.200'
	sensor = o.getSensorInfo()
	print sensor
	o.sendSnareApp(src_ip, dst_ip, "now", "jaime", "pwdump.exe")
	src_ip = '10.1.1.101'
	dst_ip = '192.168.1.201'
	for i in range(0,4):
		o.sendPixFWDenyEvent(src_ip, dst_ip, "445", "445", sensor, "now")
	
	o.s.close()
	
if __name__ == '__main__':
	config1()