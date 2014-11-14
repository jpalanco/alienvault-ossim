#!/usr/bin/env python
# encoding: utf-8

"""
Created by Jaime Blasco on 2009-09-15

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

Required:

libgeoip
python-geoip

"""


import MySQLdb
import GeoIP
import time
import BaseHTTPServer
import fileinput
import re

HOST_NAME = '192.168.1.133'
PORT_NUMBER = 8081

#Location of GeoLiteCity.dat
gi = GeoIP.open('GeoLiteCity.dat', GeoIP.GEOIP_STANDARD)


class DB:
    def __init__(self):
        self.config_file = "/etc/ossim/framework/ossim.conf"
        self.server = 'localhost'
        self.user, self.password = self.readDbInfo()
    
    def readDbInfo(self):
        """Reads Database Information from ossim configuration file"""
        user = ''
        password = ''
        for line in fileinput.input(self.config_file):
            p = re.compile(r"ossim_pass=(?P<pass>\S+)")
            m = p.match(line)
            if (m):
                password = m.group(1)
            p = re.compile(r"ossim_user=(?P<user>\S+)")
            m = p.match(line)
            if (m):
                user = m.group(1)
        return user, password

    def dbConn(self):
        """Establish a mysql session and return the cursor"""
        db=MySQLdb.connect(host=self.server,user=self.user , passwd=self.password ,db='ossim')
        cursor=db.cursor()
        return cursor
    
    def getAlarms(self):
        """Get a list of all distinct ip_src from alarms"""
        cursor = self.dbConn()
        sql = "SELECT distinct(inet_ntoa(src_ip)) from ossim.alarm order by timestamp desc limit 100;"
        cursor.execute(sql)
        res = cursor.fetchall()
        return res;
        

class KmlGen:
        def __init__(self, filepath=None):
                self.filepath = filepath
                self.gen = ""

        def setHeader(self):
                self.gen += "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                self.gen += "<kml xmlns=\"http://earth.google.com/kml/2.1\">\n"
                self.gen += "\t<Document>\n"
        
        def setCamera(self):
                self.gen += "<Camera>"
                self.gen += "<longitude>-75.58051784907759</longitude>"
                self.gen += "<latitude>6.23624552696911</latitude>"
                self.gen += "<altitude>3.8689394183611</altitude>"
                self.gen += "<heading>105.6412629280566</heading>"
                self.gen += "<tilt>82.4482296774886</tilt>"
                self.gen += "<roll>-1.956179642256441e-008</roll>"
                self.gen += "</Camera>"

        def setLineStyle(self, id, width, color):
                self.style = id
                self.gen += "\t<Style id=\"" + id + "\">\n"
                self.gen += "\t\t<LineStyle>\n"
                self.gen += "\t\t\t<width>" + width + "</width>\n"
                self.gen += "\t\t\t<color>" + color + "</color>\n"
                self.gen += "\t\t</LineStyle>\n"
                self.gen += "\t</Style>"

        def setPlacemarkLineString(self, tessellate, extrude, x1, y1, z1, x2, y2, z2):
                self.gen += "<Placemark>"
                self.gen += "<styleUrl>#" + self.style + "</styleUrl>"
                self.gen += "<LineString>"
                self.gen += "<tessellate>" + tessellate + "</tessellate>"
                self.gen += "<extrude>" + extrude + "</extrude>"
                self.gen += "<coordinates>" + x1 + "," + y1+ "," + z1+ "," + x2+ "," + y2+ "," + z2 + "</coordinates>"
                self.gen += "</LineString>"
                self.gen += "</Placemark>"


        def setPlacemarkPoint(self, id, name, x, y, z):
                self.gen += "\t\t<Placemark id=\"" + id + "\">\n"
                self.gen += "\t\t\t<name>" + name + "</name>\n"
                self.gen += "\t\t\t<Point>\n"
                self.gen += "\t\t\t\t<coordinates>" + x + "," + y + "," + z + "</coordinates>\n"
                self.gen += "\t\t\t</Point>\n"
                self.gen += "\t\t</Placemark>\n"

        def write(self):
                file = open(self.filepath,"w")
                file.write(self.gen)
                file.close()

        def setFooter(self):
                self.gen += "\t</Document>\n"
                self.gen += "</kml>\n"

        def getContent(self):
            return self.gen
                
class genBaseFile:
    def __init__(self):
        self.gen = ""
    
    def setContent(self):
        self.gen += "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        self.gen += "<kml xmlns=\"http://earth.google.com/kml/2.1\">\n"
        self.gen += "\t<Folder>\n"
        self.gen += "\t\t<open>1</open>\n"
        self.gen += "\t\t<name>Ossim Alarms</name>\n"
        self.gen += "\t\t<NetworkLink>\n"
        self.gen += "\t\t\t<name>Ossim Alarms View</name>\n"
        self.gen += "\t\t\t<visibility>1</visibility>\n"
        self.gen += "\t\t\t<Url>\n"
        url = "http://%s:%d/alarms.kml" % (HOST_NAME, PORT_NUMBER)
        self.gen += "\t\t\t\t<href>%s</href>\n" % url
        self.gen += "\t\t\t\t<refreshMode>onInterval</refreshMode>\n"
        self.gen += "\t\t\t\t<refreshInterval>30</refreshInterval>\n"
        self.gen += "\t\t\t</Url>\n"
        self.gen += "\t\t\t<refreshVisibility>1</refreshVisibility>\n"
        self.gen += "\t\t</NetworkLink>\n"
        self.gen += "\t</Folder>\n"
        self.gen += "</kml>\n"
    
    def getContent(self):
        return self.gen
    
def buildHostInfo(res):
    hosts = []
    for reg in res:
        gir = gi.record_by_addr(reg[0])
        print reg[0]
        if gir != None:
            hosts.append([reg[0], gir['country_name'], str(gir['latitude']), str(gir['longitude'])])
    return hosts
        

def sendKMLData(s):
    print "Request for Data"
    db = DB()
    ips = db.getAlarms()
    hosts = buildHostInfo(ips)
    kml = KmlGen()
    kml.setHeader()
    kml.setLineStyle("est1", "0.5", "ff0000ff")
    for host in hosts:
        kml.setPlacemarkPoint(host[0], host[1], host[3], host[2], "0")
        kml.setPlacemarkLineString("1", "1", host[3], host[2], "0", "0.22", "39.28", "0")
    kml.setFooter()
    data = kml.getContent()
    s.send_response(200)
    s.send_header("Content-type", "application/vnd.google-earth.kml+xml")
    s.end_headers()
    s.wfile.write(data)
    
    
def sendBaseKML(s):
    print "Request for Base"
    base = genBaseFile()
    base.setContent()
    s.send_response(200)
    s.send_header("Content-type", "application/vnd.google-earth.kml+xml")
    s.end_headers()
    s.wfile.write(base.getContent())
    
def processRequest(s):
    path = s.path
    try:
        path = path.split('/')
    except:
        s.send_response(404)
        return 0
    
    if path[1] == 'base.kml':
        sendBaseKML(s)
    elif path[1] == 'alarms.kml':
        sendKMLData(s)
    else:
        s.send_response(404)
        
class requestHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_GET(s):
        addres, port = s.client_address
        processRequest(s)
        
class webServer():
    def __init__(self, host, port):
        self.host = host
        self.port = port
        
    def run(self):
        self.server_class = BaseHTTPServer.HTTPServer
        try:
            self.httpd = self.server_class((self.host, self.port), requestHandler)
        except:
            print "Error while starting control server"
            return 0
        try:
            self.httpd.serve_forever()
        except KeyboardInterrupt:
            pass
        self.httpd.server_close()
        
    def close(self):
        self.httpd.server_close()
    
def main():
    gServer = webServer(HOST_NAME, PORT_NUMBER)
    gServer.run()

if __name__ == '__main__':
    main()

