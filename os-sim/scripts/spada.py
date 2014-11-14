from scapy import *
import threading
import time, datetime,string, logging, os, sys
from threading import Thread
#import ConfigParser

conns = []
tsts = {}
#src, dst, timestamp
ptime = 3

class Logger:

    logger = logging.getLogger('agent')
    logger.setLevel(logging.INFO)

    DEFAULT_FORMAT = '%(asctime)s %(module)s [%(levelname)s]: %(message)s'
    __formatter = logging.Formatter(DEFAULT_FORMAT)
    __streamhandler = None

    __streamhandler = logging.StreamHandler()
    __streamhandler.setFormatter(__formatter)
    logger.addHandler(__streamhandler)

    def remove_console_handler():
        if Logger.__streamhandler:
            Logger.logger.removeHandler(Logger.__streamhandler)
    remove_console_handler = staticmethod(remove_console_handler)

    def _add_file_handler(file, log_level = None):

        dir = file.rstrip(os.path.basename(file))
        if not os.path.isdir(dir):
            try:
                os.mkdir(dir, 0755)
            except OSError, e:
                print "Logger: Error adding file handler,", \
                    "can not create log directory (%s): %s" % (dir, e)
                return

        try:
            handler = logging.FileHandler(file)
        except IOError, e:
            print "Logger: Error adding file handler: %s" % (e)
            return

        handler.setFormatter(Logger.__formatter)
        if log_level: # modify log_level
            handler.setLevel(log_level)
        Logger.logger.addHandler(handler)
    _add_file_handler = staticmethod(_add_file_handler)


    def add_file_handler(file):
        Logger._add_file_handler(file)
    add_file_handler = staticmethod(add_file_handler)

    # Error file handler
    # the purpouse of this handler is to only log error and critical messages
    def add_error_file_handler(file):
        Logger._add_file_handler(file, logging.ERROR)
    add_error_file_handler = staticmethod(add_error_file_handler)


    # send events to a remote syslog
    def add_syslog_handler(address):        
        from logging.handlers import SysLogHandler
        handler = logging.handlers.SysLogHandler(address,514)
        handler.setFormatter(logging.Formatter(Logger.SYSLOG_FORMAT))
        Logger.logger.addHandler(handler)
    add_syslog_handler = staticmethod(add_syslog_handler)


    # show DEBUG messages or not
    # modifying the global (logger, not handler) threshold level
    def set_verbose(verbose = 'info'):
        if verbose.lower() == 'debug':
            Logger.logger.setLevel(logging.DEBUG)
        elif verbose.lower() == 'info':
            Logger.logger.setLevel(logging.INFO)
        elif verbose.lower() == 'warning':
            Logger.logger.setLevel(logging.WARNING)
        elif verbose.lower() == 'error':
            Logger.logger.setLevel(logging.ERROR)
        elif verbose.lower() == 'critical':
            Logger.logger.setLevel(logging.CRITICAL)
        else:
            Logger.logger.setLevel(logging.INFO)
    set_verbose = staticmethod(set_verbose)

    def next_verbose_level(verbose):
        levels = ['debug', 'info', 'warning', 'error', 'critical']
        if verbose in levels:
            index = levels.index(verbose)
            if index > 0:
                return levels[index-1]
        return verbose
    next_verbose_level = staticmethod(next_verbose_level)

class garbage(Thread):
   def __init__ (self):
      Thread.__init__(self)

   def run(self):
	while True:
		t = datetime.datetime.now()
		t = time.mktime(t.timetuple())
		for ts in tsts.keys():
			if tsts[ts] + 3 < t:
				logger.info("1 %s: Non-live dest used %s:%s, %s:%s" % (interface, ts[0], ts[1], ts[2], ts[3]))
				conns.remove([ts[0], ts[1], ts[2], ts[3]])
				del tsts[ts]
		time.sleep(0.1)
	
class conn:
	def __init__(self, src, dst, ts):
		self.src = src
		self.dst = dst
		self.ts = ts
		
def check(p):
	flag = p.getlayer(TCP).flags
	if flag == 2:
		data = [p.getlayer(IP).src, p.getlayer(TCP).sport, p.getlayer(IP).dst, p.getlayer(TCP).dport]
		if data not in conns:
			t = datetime.datetime.now()
			t = time.mktime(t.timetuple())
			conns.append(data)
			tsts[(p.getlayer(IP).src, p.getlayer(TCP).sport, p.getlayer(IP).dst, p.getlayer(TCP).dport)] = t
	elif flag == 18:
		try:
			conns.remove([p.getlayer(IP).dst, p.getlayer(TCP).dport, p.getlayer(IP).src, p.getlayer(TCP).sport])
			del tsts[(p.getlayer(IP).dst, p.getlayer(TCP).dport, p.getlayer(IP).src, p.getlayer(TCP).sport)]
		except:
			pass
	elif flag == 20:
		try:
			conns.remove([p.getlayer(IP).dst, p.getlayer(TCP).dport, p.getlayer(IP).src, p.getlayer(TCP).sport])
			del tsts[(p.getlayer(IP).dst, p.getlayer(TCP).dport, p.getlayer(IP).src, p.getlayer(TCP).sport)]
		except:
			pass
		logger.info("2 %s: Closed dest port used %s:%s, %s:%s" % (interface, p.getlayer(IP).src, p.getlayer(TCP).sport, p.getlayer(IP).dst, p.getlayer(TCP).dport))
		
try:
	interface = sys.argv[1]
	logFile = sys.argv[2]
except:
	print "Usage: spada.py interface logfile"
	sys.exit()
	
logger = Logger.logger
Logger.set_verbose('info')
Logger.add_file_handler(logFile)
logger.info('Program Started on interface %s' % interface)
garb = garbage()
garb.start()
sniff(iface=interface, prn=check, filter="tcp[13] & 4 = 4 or tcp[13] & 2 = 2", store=0)

#SYN =2
#SA = 18
#RA = 20

