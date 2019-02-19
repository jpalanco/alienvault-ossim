# License:
#
# Copyright (c) 2007 - 2015 AlienVault
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
import socket
import time
import threading

import api_log

MAX_WAIT = 600  # 10 minutes
MAX_TRIES = 3


class Connection(object):
    """Connection Class
    This class aims is to create a socket within a given endpoint."""

    def __init__(self, ip, port):
        """Constructor
            :param ip(string): IP address (string-dotted) for the remote end point.
            :param port(int): Port to which the socket should be connected.
        """
        self.__ip = ip
        self.__port = port
        self.__sock = None
        self.__wait = 0
        self.__tries = 0
        self.__connected = False
        self.__close_lock = threading.RLock()
        self.__rcv_lock = threading.RLock()
        self.__send_lock = threading.RLock()

    @property
    def connected(self):
        """Returns whether the socket is connected or not"""
        return self.__connected

    @property
    def connection_address(self):
        if self.__sock is not None:
            return self.__sock.getsockname()
        return None

    @property
    def ip(self):
        return self.__ip

    @property
    def port(self):
        return self.__port

    def connect(self, attempts=3, default_wait=10):
        """Connects to the given endpoint"""

        while self.__sock is None and self.__wait < MAX_WAIT and self.__tries < MAX_TRIES:

            api_log.debug("Connection [%s:%s] - Trying to connect ... " % (self.ip, self.port ))
            # Create a new socket

            try:
                plain_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            except Exception as err:
                api_log.error("Connection [%s:%s] - Cannot create a new socket %s" % (self.ip, self.port, str(err)))
                self.__tries += 1
                self.__wait += default_wait
                time.sleep(default_wait)
                continue

            # Set socket options:
            plain_sock.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 2)  # Enable keep alive
            plain_sock.setsockopt(socket.SOL_TCP, socket.TCP_KEEPIDLE, 5)  # Start to send keepalieves after this period
            plain_sock.setsockopt(socket.SOL_TCP, socket.TCP_KEEPINTVL, 3)  # interval between keepalieves.
            plain_sock.setsockopt(socket.SOL_TCP, socket.TCP_KEEPCNT, 5)  # number of keep alieves before deadth

            self.__sock = plain_sock
            try:
                api_log.debug("Connection [%s:%s] - Connect using plain socket" % (self.ip, self.port))
                self.__sock.connect((self.__ip, self.__port))
            except Exception as err:
                api_log.error("Connection [%s:%s] - Cannot establish a plain connection"
                      "with the remote server, waiting for %d seconds: %s" % (
                          self.__ip, self.__port, default_wait, str(err)))
                self.__tries += 1
                self.__wait += default_wait
                time.sleep(default_wait)
                continue
            else:
                api_log.error("Connection [%s:%s] - Plain socket connected..." % (self.ip, self.port))
                self.__connected = True

        return self.__connected

    def send(self, data):
        """Sends data
        :param data(str) the data to be sent.

        :returns success(boolean) True on success, false otherwise"""
        success = False
        try:
            self.__send_lock.acquire()
            data = str(data)
            if self.__sock is not None:
                self.__sock.sendall(data)
            success = True
        except (socket.error, socket.herror, socket.gaierror, socket.timeout, IOError) as err:
            api_log.error("Connection [%s:%s] - Connection  failed. ERROR: %s" % (self.__ip, self.__port, str(err)))
            self.close()
        except Exception as exc:
            api_log.error("Connection [%s:%s] - Error sending data '%s'" % (self.ip, self.port, str(data)))
            self.close()
        finally:
            self.__send_lock.release()
        return success

    def receive(self):
        # TODO: The sockets are blocking by default, this means, that for example
        # if we send a random message to the ossim-server as a connect message,
        # the server will discard the message
        # but the connection will remain open, so this method will remain blocked.
        # In this case, Should the server close the connection?
        ans = ''
        chunk = ''
        try:
            self.__rcv_lock.acquire()
            if self.__connected:
                while ans[-1:] != '\n':
                    chunk = self.__sock.recv(1)

                    if chunk is None or len(chunk) < 1:
                        break
                    else:
                        ans += chunk
        except socket.timeout:
            api_log.error("Connection [%s:%s]  timeout" % (self.ip, self.port))
        except (socket.error, socket.herror, socket.gaierror, IOError) as err:
            api_log.error("Connection [%s:%s] - Connection failed" % (self.__ip, self.__port))
            self.close()
        except Exception as err:
            api_log.error("Connection [%s:%s] - Error receiving data" % (self.__ip, self.__port))
            self.close()
        finally:
            self.__rcv_lock.release()
        return ans

    def close(self):
        """Closes the current connection"""
        self.__close_lock.acquire()
        try:
            self.__connected = False
            if self.__sock is not None:
                try:
                    self.__sock.shutdown(socket.SHUT_RDWR)
                    self.__sock.close()
                except Exception as exc:
                    pass
            self.__sock = None
        except Exception as err:
            pass
        self.__close_lock.release()
