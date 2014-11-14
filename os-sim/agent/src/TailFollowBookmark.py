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
import os, stat, sys, time

#
# LOCAL IMPORTS
#
from Logger import *

#
# GLOBAL VARIABLES
#
logger = Logger.logger
import codecs

class TailFollowBookmark(object):
    """
    Tail a file and follow as additional data is appended.

    An optional bookmark is updated for the current file in the event that
    logging needs to resume from the last place left off. 

    TailBookmarkFollow can be used to monitor log files and can even track
    when a file has been moved (eg via log rotation )

    In this case, TailBookmarkFollow will automatically close the old file,
    and re-open the new file.
    """

    def __init__(self, filename, track=1, bookmark_dir="", encoding='latin1'):
        """Constructor that specifies the file to be tailed.  An
        optional keyword argument specifies whether or not the file
        should be tracked.
        """
        self.encode = encoding
        # bookmarks enabled based on existence of path
        self.bookmark = os.path.exists(bookmark_dir)

        self.lines = []

        self.track = track
        self.filename = filename

        if self.bookmark:
            self.bookmark_path = "%s/%s.bmk" % (os.path.dirname(bookmark_dir + "/"), os.path.basename(filename))
            logger.info('Bookmarking "%s" at: %s' % (self.filename, self.bookmark_path))
        self.nlines = 0

        self._stat_file()
        self._open_file(False)
        self._lines_have_been_readed = False

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

        bookmark_pos = self._current_file.tell()
        line = ''
        try:
            # Check for deleted files.
            if self.track:
                self._check_for_file_modification()
            line = self._current_file.readline()
        except Exception, e:
            logger.error("Error reading the log file: %s -> %s" % (self.filename, str(e)))
            return line
        if line:
            self._lines_have_been_readed = True

            # Just be sure that the last line is fully written
            if line[-1] != '\n':
                time.sleep(1)
                line += self._current_file.readline()

        if not line:
            raise StopIteration
        # check if we should be bookmarking
        elif self.bookmark and line != "":
            try:
                b = open(self.bookmark_path, 'w')

                try:
                    data = "%s\n%s" % (str(bookmark_pos), line)
                    b.write(data)

                finally:
                    b.close()

            except IOError:
                logger.warning('Unable to write bookmark file "%s" for log "%s"' % (self.bookmark_path, self.filename))


        return line

    def close(self):
        """Closes the current file."""

        self._current_file.close()

    def _stat_file(self):
        """Stats the file and verifies it is a regular file; otherwise
        an IOError exception is thrown.  Furthermore, the _current_stat
        attribute is set as a side-effect.
        """

        self._current_stat = os.stat(self.filename)

        if not stat.S_ISREG(self._current_stat.st_mode):
            raise IOError, self.filename + " is not a regular file"

    def _open_file(self, fromrotate=False):
        """
        Opens the file and seeks to the specified position based on
        the keyword arguments: offset and whence.  Furthermore, the
        _current_file attribute is set as a side-effect.
        
        fromrotate: Indicates if the file is opened when a 
                    log rotation is detected
        """

        logger.info("Opening log file with codification:%s"% self.encode)
        self._current_file = codecs.open(self.filename, 'r', encoding=self.encode)
        if not fromrotate:
            self._current_file.seek(0, os.SEEK_END)

        # check if we are using bookmarks and seek accordingly
        if self.bookmark:
            bookmark_pos = 0
            tail_pos = self._current_file.tell()

            try:
                b = open(self.bookmark_path, 'r')

                try:
                    bookmark_pos = long(b.readline())
                    bookmark_line = b.readline()

                    # seek to the bookmarked position
                    self._current_file.seek(bookmark_pos, os.SEEK_SET)

                    # check that the current line is what we last read (and noted in the bookmark)
                    line = self._current_file.readline()

                    if line != bookmark_line:
                        self._current_file.seek(tail_pos, os.SEEK_SET)
                        logger.warning('Bookmark expected "%s" but found "%s". Chasing tail instead.' % (bookmark_line, line))
                    else:
                        logger.info("Bookmark found. Offsetting to byte position: %d" % (bookmark_pos + len(bookmark_line)))
                except ValueError:
                    logger.info('Bookmark appears empty or corrupt. Ignoring.')

                finally:
                    b.close()

            except IOError:
                logger.warning('Unable to read bookmark file "%s" for log "%s"' % (self.bookmark_path, self.filename))

    def _printFileStat(self, file_stat):
        logger.info("st_mode: %s" % file_stat.st_mode)
        logger.info("st_ino: %s" % file_stat.st_ino)
        logger.info("st_dev: %s" % file_stat.st_dev)
        logger.info("st_nlink: %s" % file_stat.st_nlink)
        logger.info("st_uid: %s" % file_stat.st_uid)
        logger.info("st_gid: %s" % file_stat.st_gid)
        logger.info("st_size: %s" % file_stat.st_size)
        logger.info("st_blocks: %s" % file_stat.st_blocks)
        

    def _compareFileStats(self, oldstat, newstat, filename):
        if oldstat.st_ino != newstat.st_ino:
            logger.info("st_ino has changed for filename:%s ---->> old st_ino: %s new st_ino:%s" % (filename, oldstat.st_ino, newstat.st_ino))
        if oldstat.st_dev != newstat.st_dev:
            logger.info("st_dev has changed for filename:%s ---->> old st_dev: %s new st_dev:%s" % (filename, oldstat.st_dev, newstat.st_dev))
        if oldstat.st_blocks > newstat.st_blocks:
            logger.info("st_blocks old bl:%s ---->> old st_blocks: %s > new st_blocks:%s" % (filename, oldstat.st_blocks, newstat.st_blocks))
        if oldstat.st_size > newstat.st_size:
            logger.info("st_size has changed for filename:%s ---->> old st_size: %s > new st_size:%s" % (filename, oldstat.st_size, newstat.st_size))

    def _check_for_file_modification(self):
        """Checks to see if the file has been moved/deleted and as a
        result requires the closure of the existing file and re-opening
        of the original file.
        """

        try:
            old_stat = self._current_stat
            old_file = self._current_file
            self._stat_file()
            #self._compareFileStats(old_stat,self._current_stat,self.filename)
            if self._current_stat.st_ino != old_stat.st_ino or \
               self._current_stat.st_dev != old_stat.st_dev:
                
                #self._current_stat.st_size < old_stat.st_size:

                # Open the new log file after the rotation 
                # and indicate that this action is after a log rotation
                logger.info("File %s has been rotated..." % self.filename)
                self._lines_have_been_readed = False
                self._open_file(True)
                old_file.close()

                if self.bookmark:
                    # delete the bookmark if we got here since we finished the file
                    os.unlink(self.bookmark_path)
            #Check if the file has been truncated.
            elif (self._current_stat.st_blocks == 0 and self._lines_have_been_readed)or \
                 (self._current_stat.st_blocks < old_stat.st_blocks) or \
                 (self._current_stat.st_size == 0 and self._lines_have_been_readed) or \
                 (self._current_stat.st_size < old_stat.st_size):
                self._current_file.seek(0, os.SEEK_END)
                self._lines_have_been_readed = False
                logger.info("File %s truncated.." % self.filename)
            else:
                self._compareFileStats(old_stat, self._current_stat, self.filename)
        except (IOError, OSError):

            # The filename no longer exists, revert back, as it may
            # be in the process of being moved, a subsequent check
            # will find it and then take action.

            self._current_stat = old_stat
            self._current_file = old_file


