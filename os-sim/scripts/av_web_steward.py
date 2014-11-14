#!/usr/bin/env python
import os
import socket
import time
import sys
import re
import logging
import getopt
import pwd
CONFIG_FILE="/etc/ossim/framework/ossim.conf"
LOG_FILE = "/var/log/ossim/av_web_steward.log"
E_GETOPT = 2
E_INVALID_ARGS =3
E_SOCKET_TIMEOUT = 4
E_SOCKET_ERROR = 5
E_BAD_RESPONSE = 6
E_GENERIC_ERROR = 7
logger = logging.getLogger('getpcap')
hdlr = logging.FileHandler(LOG_FILE)
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

# Set the file permissions, just in case...
os.chown(LOG_FILE, pwd.getpwnam('www-data').pw_uid, -1)

class OssimMiniConf :

    def __init__ (self, config_file=CONFIG_FILE) :
        self._conf = {}
        # get config only from ossim.conf file
        # (for example, when you only need database access)
        self._get_conf(config_file)

    def __setitem__ (self, key, item) :
        self._conf[key] = item

    def __getitem__ (self, key) :
        return self._conf.get(key, None)

    def __repr__ (self):
        repr = ""
        for key, item in self._conf.iteritems():
            repr += "%s\t: %s\n" % (key, item)
        return repr


    def _get_conf (self, config_file) :

        # Read config from file
        #
        try:
            config = open(config_file)
        except IOError, e:
            print "Error opening OSSIM configuration file (%s)" % e
            sys.exit()
       
        pattern = re.compile("^(\S+)\s*=\s*(\S+)")

        for line in config:
            result = pattern.match(line)
            if result is not None:
                (key, item) = result.groups()
                self[key] = item
       
        config.close()
def usage():
    logger.info( """
    Usage av_web_steward:
        -v set verbose mode
        -h show this help
        -r request to send
        -t path to store dwonloaded file.
    Example:
    av_web_steward.py -r resquest -t path_to_save_file
    av_web_steward.py --request=request --tempath=path_to_save_file
    """)
    print  """
    Usage av_web_steward:
        -v set verbose mode
        -h show this help
        -r request to send
        -t path to store dwonloaded file.
        -s Ossim server ip
        -p Ossim server listen port
    Example:
    av_web_steward.py -r resquest -t path_to_save_file -s server_ip -p server_port
    av_web_steward.py --request=request --tempath=path_to_save_file 
    """
def write_data(filepath,data):
    try:
        f = open(filepath,'wb')
        f.write(data)
        f.close()
        logger.info("Write data ok->%s" % data)
    except Exception,e:
        logger.error("I can't write the file:%s - error:%s" % (data,str(e)))
if __name__=="__main__":
    #server_config = OssimMiniConf(config_file='/etc/ossim/ossim_setup.conf')
    server_ip = "127.0.0.1"#server_config['server_ip']
    server_port = "40001"#server_config['server_port']a
    request = ''
    filepath = ''
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hr:t:s:p:v", ["help", "request=","tempath=","server=","port="])
    except getopt.GetoptError, err:       
        print str(err) 
        usage()
        sys.exit(E_GETOPT)
    for option,value in opts:
        if option == "-v":
            verbose = True
        elif option in ("-h", "--help"):
            usage()
            sys.exit(0)
        elif option in ("-r", "--request"):
            request = value
        elif option in ("-t", "--tempath"):
            filepath = value
        elif option in ("-s", "--server"):
            server_ip = value
        elif option in ("-p", "--port"):
            server_port = value
    
    msg_connection = ""
    conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    if conn:
        logger.info("Connecting...")
        try:            
            conn.connect((server_ip, int(server_port)))
            conn.settimeout(1)
            conn.send("connect id=\"1\" type=\"web\"\n")
            data = conn.recv(1024)
            logger.info( "Connected %s" % data)
            logger.info("Sending request:%s " % request)
            msg = ''
            conn.send(request+"\n")
            while True:
                try:
                    recv_data= ""
                    recv_data = conn.recv(1)
                except socket.timeout:
                    break
                else:
                    msg += recv_data
                if recv_data == '' or not recv_data:
                   break
            logger.info("Server Response: %s" % msg.rstrip('\n'))
            write_data(filepath, msg)
        except Exception, e:
            msg = "AVWEBSTEWARD_ERROR: %s" % str(e)
            logger.error(msg)
            write_data(filepath, msg)
            sys.exit(E_GENERIC_ERROR)
    else:
        logger.erro("Can't connect..")
        msg = "AVWEBSTEWARD_ERROR: Can't connect"
        write_data(filepath, msg)
