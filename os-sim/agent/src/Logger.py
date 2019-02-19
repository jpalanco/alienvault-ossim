#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
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

#
# GLOBAL IMPORTS
#
import logging
import os
import string
import sys

#
# CONSTANTS
#

ERROR_CONNECTING_TO_SERVER = \
    "[sid=1] Error connecting to server %s, port %s"
WATCHDOG_PROCESS_STARTED = \
    "[sid=2] Process %s belonging to plugin %s has been started"
WATCHDOG_PROCESS_STOPPED = \
    "[sid=3] Process %s belonging to plugin %s has been stopped"
WATCHDOG_ERROR_STARTING_PROCESS = \
    "[sid=4] There was an error starting process %s belonging to plugin %s"
WATCHDOG_ERROR_STOPPING_PROCESS = \
    "[sid=5] There was an error stopping process %s belonging to plugin %s"

class Lazyformat(object):

    def __init__(self, template, *items):
        self.__template = template
        self.__items = items

    def __str__(self):
        return self.__template.format(*self.__items)

    def __format__(self, format_spec=""):
        return self.__str__()

    def __add__(self, other):
        return self.__str__() + other

    def __radd__(self, other):
        return other + self.__str__()

    def __mul__(self, other):
        return self.__str__() * other

    def __rmul__(self, other):
        return other * self.__str__()


class Logger:
    """Static class for logging purposes.

    More info at http://docs.python.org/lib/module-logging.html

    Example usage from other classes:
      from Logger import Logger
      logger = Logger.logger

      logger.debug("Some debug")
      logger.info("Some info")
      logger.error("Error")
    """

    logger = logging.getLogger('agent')
    logger.setLevel(logging.INFO)

    DEFAULT_FORMAT = '%(asctime)s %(module)s [%(levelname)s]: %(message)s'
    SYSLOG_FORMAT = 'ossim-agent: %(asctime)s %(module)s [%(levelname)s]: %(message)s'
    __formatter = logging.Formatter(DEFAULT_FORMAT)
    __streamhandler = None

    # load the console handler by default
    # it will be removed in daemon mode
    __streamhandler = logging.StreamHandler()
    __streamhandler.setFormatter(__formatter)
    logger.addHandler(__streamhandler)


    def remove_console_handler():
        """ Removes the stream handler.

        Useful when agent starts in daemon mode
        """
        if Logger.__streamhandler:
            Logger.logger.removeHandler(Logger.__streamhandler)

    remove_console_handler = staticmethod(remove_console_handler)


    def _add_file_handler(file, log_level = None):
        """Log to file.

        File should be log->file in configuration.
        """

        dir = file.rstrip(os.path.basename(file))

        if not os.path.isdir(dir):
            try:
                os.makedirs(dir, 0755)

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


    def add_error_file_handler(file):
        """Error file handler.

        The purpouse of this handler is to only log error and critical messages.
        """

        Logger._add_file_handler(file, logging.ERROR)

    add_error_file_handler = staticmethod(add_error_file_handler)


    def add_syslog_handler(address):
        """Send events to a remote syslog."""
        from logging.handlers import SysLogHandler
        handler = SysLogHandler(address)
        handler.setFormatter(logging.Formatter(Logger.SYSLOG_FORMAT))
        Logger.logger.addHandler(handler)

    add_syslog_handler = staticmethod(add_syslog_handler)


    def set_verbose(verbose = 'info'):
        """Show DEBUG messages or not

        Modifying the global (logger, not handler) threshold level
        """

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


if __name__ == "__main__":

    logger = Logger.logger
    Logger.set_verbose('debug')

    # logs to console
    logger.debug("Some debug text")
    logger.info("Some info text")
    logger.critical("Oppps, error")

    # now logs to file and not to log
    Logger.add_file_handler('/tmp/ossim.log')
    Logger.add_error_file_handler('/tmp/ossim_error.log')
    Logger.remove_console_handler()
    logger.debug("log debug info to file")
    logger.warning("log warning info to file")
    logger.error("log error info to file")

# vim:ts=4 sts=4 tw=79 expandtab:
