#!/usr/bin/python
# Copyright (c) 2003, Pete Kazmier
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
#
#    - Redistributions of source code must retain the above copyright
#      notice, this list of conditions and the following disclaimer.
#
#    - Redistributions in binary form must reproduce the above
#      copyright notice, this list of conditions and the following
#      disclaimer in the documentation and/or other materials provided
#      with the distribution.
#
#    - Neither the name of the 'Kazmier' nor the names of its
#      contributors may be used to endorse or promote products derived
#      from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
# COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
"""
TailFollow enables a user to read new data as it is appended to a file
in a manner similar to a 'tail -f' command.  As a file is moved or
rotated (perhaps as part of a log rotation mechanism), TailFollow can
continue to read from the file as it moves until a new file is created
in the original location.

The simple (non-tracking mode) usage of TailFollow is as follows::

    tail = TailFollow("/var/log/syslog")
    for line in tail:
        print line,
    tail.close()

When tracking changes to a file, usage is as follows::

    while 1:
        tail = TailFollow("/var/log/syslog", track=1)
        for line in tail:
            print line,
        time.sleep(1)
    tail.close()

It is important to insert a delay to avoid a busy loop in which the
TailFollow tries to determine if the file has moved.  Failure to do so
will result in excessive CPU consumption when the end of file occurs.
"""

from os import stat
from stat import S_ISREG
from sys import argv, exit
from time import sleep

class TailFollow(object):
    """Tail a file and follow as additional data is appended.
    TailFollow can be used to monitor log files.  It can also track
    when a the file has been moved (perhaps by a log rotation script).
    In this case, TailFollow will automatically close the old file,
    and re-open the new file.
    """

    def __init__(self, filename, track=1):
        """Constructor that specifies the file to be tailed.  An
        optional keyword argument specifies whether or not the file
        should be tracked.
        """

        self.track = track
        self.filename = filename
        self._stat_file()
        self._open_file(offset=0, whence=2)

    def __iter__(self):
        """Returns an iterator that can be used to iterate over the
        lines of the file as they are appended.  TailFollow implements
        the iterator contract, as a result, self is returned to the
        caller.
        """

        return self

    def next(self):
        """Returns the next line from the file being tailed.  This
        method is part of the iterator contract.  StopIteration is
        thrown when there an EOF has been reached.
        """

        line = self._current_file.readline()

        if not line:
            if self.track:
                self._check_for_file_modification()
            raise StopIteration

        return line

    def close(self):
        """Closes the current file."""

        self._current_file.close()

    def _stat_file(self):
        """Stats the file and verifies it is a regular file; otherwise
        an IOError exception is thrown.  Furthermore, the _current_stat
        attribute is set as a side-effect.
        """

        self._current_stat = stat(self.filename)

        if not S_ISREG(self._current_stat.st_mode):
            raise IOError, self.filename + " is not a regular file"

    def _open_file(self, offset=0, whence=0):
        """Opens the file and seeks to the specified position based on
        the keyword arguments: offset and whence.  Furthermore, the
        _current_file attribute is set as a side-effect.
        """

        self._current_file = open(self.filename, 'r')
        self._current_file.seek(offset, whence)

    def _check_for_file_modification(self):
        """Checks to see if the file has been moved/deleted and as a
        result requires the closure of the existing file and re-opening
        of the original file.
        """

        try:
            old_stat = self._current_stat
            old_file = self._current_file

            self._stat_file()
            
            if self._current_stat.st_ino != old_stat.st_ino or \
               self._current_stat.st_dev != old_stat.st_dev or \
               self._current_stat.st_size < old_stat.st_size:
                self._open_file()
                old_file.close()

        except (IOError, OSError):

            # The filename no longer exists, revert back, as it may
            # be in the process of being moved, a subsequent check
            # will find it and then take action.

            self._current_stat = old_stat
            self._current_file = old_file

if __name__ == '__main__':

    def usage():
        print "Usage:", argv[0], "[-t] filename"
        print "  where"
        print "    -t   track file changes"
        exit(1)

    if len(argv) < 2:
        usage()
    elif len(argv) == 2:
        tail = TailFollow(argv[1], track=0)
    elif argv[1] == '-t':
        tail = TailFollow(argv[2], track=1)

    try:
        while 1:
            sleep(1)
            for line in tail:
                print line,

    except KeyboardInterrupt:
        tail.close()
