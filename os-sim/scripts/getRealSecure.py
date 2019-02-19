#!/usr/bin/env python
# encoding: utf-8
"""
Python script to extract information from SiteProtector system:
Jaime Blasco jaime.blasco@alienvault.com
"""
import sys
import socket
import struct
import time
import os
import commands
import calendar
import ConfigParser

try:
    import pymssql
except ImportError:
    sys.exit("You need pymssql to run this script")
"""
For future:
if int(pymssql.__version__[0]) < 2:
    sys.exit(
        "You need at least 2nd version of the pymssql to run this script \n"
        "Please check the following link: "
        "https://github.com/pymssql/pymssql/releases"
    )
"""

CONFIG_FILE = "/etc/ossim/getrealsecure.cfg"


def get_connection_values():
    if not os.path.isfile(CONFIG_FILE):
        sys.exit("ERROR: Configuration file doesn't exist, please check it at: {}".format(CONFIG_FILE))
    else:
        config = ConfigParser.ConfigParser()
        config.read(CONFIG_FILE)
        if not config.has_section('config'):
            sys.exit("ERROR: [config] section not found")
        if config.has_option('config', 'dbhost'):
            dbhost = config.get('config', 'dbhost')
        else:
            sys.exit("ERROR: dbhost not found in config section")
        if config.has_option('config', 'dbuser'):
            dbuser = config.get('config', 'dbuser')
        else:
            sys.exit("ERROR: dbuser not found in config section")
        if config.has_option('config', 'dbpasswd'):
            dbpasswd = config.get('config', 'dbpasswd')
        else:
            sys.exit("ERROR: dbpasswd not found in config section")
        if config.has_option('config', 'dbname'):
            dbname = config.get('config', 'dbname')
        else:
            sys.exit("ERROR: dbname not found in config section")
    return dbhost, dbuser, dbpasswd, dbname


# Extract extra data from each of the events
def get_event_data(db_cursor, s_id):
    res = extract_data(db_cursor, s_id)
    result_data = ""
    for res_row in res:
        result_data += "{}:{}, ".format(res_row[0], res_row[1])
    return result_data


def check_if_running():
    """ Check if there is another instance running
    Output example:
    data[1].split('\n')
    ['apalii    3223  0.0  0.2  10124  4472 pts/1    S+   02:15   0:00 python /home/apalii/get_real_secure.py',
     'apalii    3249  0.0  0.2  10124  4376 pts/3    S+   02:16   0:00 python /home/apalii/get_real_secure.py']
    """
    pid = os.getpid()
    print "RealSecure script pid : {}".format(pid)
    output = commands.getstatusoutput(
        'ps auxwwwwwwwwww|grep getRealSecure.py|grep -v grep'
    )
    data2 = output[1].split('\n')
    if output[1].find("python /usr/share/ossim/scripts/getRealSecure.py") != -1 and len(data2) > 1:
        sys.exit("getRealSecure.py is already running")


# Initial query
def get_first_id(db_conn):
    cursor_inst = db_conn.cursor()
    query = ("select TOP 1 data.SensorDataRowID "
             "from SensorData1 as data order by data.SensorDataRowID desc")
    cursor_inst.execute(query)
    res = cursor_inst.fetchone()
    cursor_inst.close()
    return int(res[0])


def extract_data(db_cursor, s_id):
    sql1 = ("select CONVERT(nvarchar(200), AttributeName), CONVERT(nvarchar(200), AttributeValue) "
            "from SensorDataAVP1 where SensorDataID={}".format(s_id))
    db_cursor.execute(sql1)
    return db_cursor.fetchall()


def count_events(db_cursor, s_id):
    for line in extract_data(db_cursor, s_id):
        if line[0] == ":repeat-count":
            return int(line[1])
    return 1


def get_ip_from_binary(ip_data, default_ip='0.0.0.0'):
    """ Convert an IP address from binary format to string format.
    Args:
        ip_data: (str) IP representation.
        default_ip: (str) default ip address which should be used in case of errors
    """
    try:
        ip_address = socket.inet_ntoa(struct.pack('!L', int(ip_data)))
    except:
        ip_address = default_ip

    return ip_address


# Connect to Database
def connect_to_db():
    db_host, db_user, db_passwd, db_name = get_connection_values()
    while True:
        try:
            return pymssql.connect(host=db_host, user=db_user, password=db_passwd, database=db_name)
        except StandardError:  # Base error class in pymssql
            time.sleep(10)
            print "Failed to connect to MS SQL database...Waiting for next attempt"


def main():
    check_if_running()

    db_connection = connect_to_db()
    c_val = get_first_id(db_connection)

    while True:
        try:
            # check if still connected to db
            # db_connection._cnx -> will raise pymssql.InterfaceError('Not connected')
            db_connection._cnx
        except pymssql.InterfaceError:
            db_connection = connect_to_db()

        sql = ("SELECT  SensorData.SensorDataRowID, SensorData.AlertName, SensorData.AlertDateTime, "
               "SensorData.SensorAddressInt, SensorData.SrcAddressInt, SensorData.DestAddressInt, "
               "SensorData.SourcePort, Observances.SecChkID, SecurityChecks.ChkBriefDesc, "
               "SensorData.AlertPriority, SensorData.DestPortName, Protocols.ProtocolName, Products.ProdName, "
               "SensorData.VirtualSensorName, ObservanceType.ObservanceTypeDesc, SensorData.AlertCount, "
               "SensorData.Cleared, SensorData.ObjectName, SensorData.ObjectType, SensorData.VulnStatus, "
               "SensorData.UserName, attrs.event_type, attrs.adapter, attrs.attacker_ip, attrs.attacker_port,  "
               "attrs.victim_ip, attrs.victim_port, attrs.url, attrs.server,  attrs.protocol, attrs.field, "
               "attrs.value, attrs.httpsvr, attrs.login, SensorData.SensorDataID FROM  SensorData WITH (NOLOCK) "
               "LEFT OUTER JOIN  (SELECT SensorDataID, max(case when AttributeName = ':event-type'  "
               "then AttributeValue end)  as event_type, max(case when AttributeName = ':adapter' "
               "then AttributeValue end)  as adapter, max(case when AttributeName = ':intruder-ip-addr' "
               "then AttributeValue end)  as attacker_ip, max(case when AttributeName = ':intruder-port' "
               "then AttributeValue end) as attacker_port, max(case when AttributeName = ':victim-ip-addr' "
               "then AttributeValue end) as victim_ip, max(case when AttributeName = ':victim-port' "
               "then AttributeValue end)  as victim_port, max(case when AttributeName = ':URL' "
               "then AttributeValue end)  as url, max(case when AttributeName = ':server' "
               "then AttributeValue end) as server,  max(case when AttributeName = ':protocol' "
               "then AttributeValue end)  as protocol, max(case when AttributeName = ':field' "
               "then AttributeValue end)  as field, max(case when AttributeName = ':value' "
               "then AttributeValue end)  as value, max(case when AttributeName = ':httpsvr' "
               "then AttributeValue end)  as httpsvr, max(case when AttributeName = ':login' "
               "then AttributeValue end)  as login  from SensorDataAVP  "
               "where AttributeName in "
               "( ':event-type', ':adapter', ':intruder-ip-addr', ':intruder-port', ':victim-ip-addr', ':victim-port' ) "
               "group by SensorDataID ) attrs on SensorData.SensorDataID = attrs.SensorDataID "
               "LEFT OUTER JOIN Observances WITH (NOLOCK) ON SensorData.ObservanceID = Observances.ObservanceID "
               "LEFT OUTER JOIN SecurityChecks WITH (NOLOCK) ON Observances.SecChkID = SecurityChecks.SecChkID "
               "LEFT OUTER JOIN Protocols WITH (NOLOCK) ON SensorData.ProtocolID = Protocols.ProtocolID "
               "LEFT OUTER JOIN Products WITH (NOLOCK) ON SensorData.ProductID = Products.ProductID "
               "LEFT OUTER JOIN ObservanceType WITH (NOLOCK) "
               "ON Observances.ObservanceType = ObservanceType.ObservanceType "
               "WHERE SensorData.SensorDataRowID > {} ORDER BY SensorData.SensorDataRowID ASC".format(c_val))

        cursor = db_connection.cursor()
        cursor.execute(sql)
        result = cursor.fetchall()
        if result and len(result) > 0:
            c_val = int(result[len(result) - 1][0])

        with open('/var/log/siteprotector.log', 'a') as stp_log:
            for row in result:
                ev_id = str(row[0])
                ev_name = str(row[1])

                try:
                    # UTC => localtime
                    ev_date = time.strftime("%Y-%m-%d %H:%M:%S",
                                            time.localtime(
                                                calendar.timegm(time.strptime(str(row[2]), "%Y-%m-%d %H:%M:%S"))))
                except:
                    ev_date = row[2]

                ev_sensor = get_ip_from_binary(row[3], default_ip="127.0.0.1")
                ev_src = get_ip_from_binary(row[4], default_ip="0.0.0.0")
                ev_dst = get_ip_from_binary(row[5], default_ip="0.0.0.0")

                try:
                    ev_src_port = str(row[6]) or "0"
                except:
                    ev_src_port = "0"

                try:
                    ev_dst_port = str(row[17]) or "0"
                except:
                    ev_dst_port = "0"

                data = get_event_data(cursor, int(row[34])).replace("\n", "")
                for _ in xrange(0, count_events(cursor, int(row[34]))):
                    stp_log.write("{},{},{},{},{},{},{},{},{}\n".format(
                        ev_id, ev_name, ev_date, ev_sensor, ev_src, ev_dst, ev_src_port, ev_dst_port, data))

        cursor.close()
        # db_connection.close()  # close connection to DB it will be reopened on next iteration.
        time.sleep(20)


if __name__ == '__main__':
    main()
