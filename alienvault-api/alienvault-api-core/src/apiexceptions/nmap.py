# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 20154 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
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
from apiexceptions import APIException


class APINMAPScanCannotRetrieveScanProgress(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanCannotRetrieveScanProgress, self).__init__("Cannot retrieve the scan progress")


class APINMAPScanCannotRun(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanCannotRun, self).__init__("Cannot run the nmap scan {0}".format(msg))


class APINMAPScanCannotCreateLocalFolder(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanCannotCreateLocalFolder, self).__init__("Cannot create the local folder {0}".format(msg))


class APINMAPScanCannotRetrieveBaseFolder(APIException):
    def __init__(self, base_path=""):
        super(APINMAPScanCannotRetrieveBaseFolder, self).__init__("Cannot retrieve the base folder {0}".format(base_path))


class APINMAPScanKeyNotFound(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanKeyNotFound, self).__init__("Item not found")


class APINMAPScanException(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanException, self).__init__(str(msg))


class APINMAPScanCannotBeSaved(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanCannotBeSaved, self).__init__("Cannot save the give task data")


class APINMAPScanReportNotFound(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanReportNotFound, self).__init__("NMAP Scan Report not found {0}".format(msg))


class APINMAPScanCannotReadReport(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanCannotReadReport, self).__init__("Cannot read the scan report {0}".format(msg))


class APINMAPScanReportCannotBeDeleted(APIException):
    def __init__(self, msg=""):
        super(APINMAPScanReportCannotBeDeleted, self).__init__("Cannot remove the given report")
