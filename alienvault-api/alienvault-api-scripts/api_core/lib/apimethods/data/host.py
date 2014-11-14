#  License:
#
#  Copyright (c) 2013 AlienVault
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
from db.methods.data import host_clean_orphan_ref
from celerymethods.jobs.asset import asset_clean_orphan_status_messages


def delete_host_references(host_id):
    """
    Remove the orphan host references
    """
    success = host_clean_orphan_ref(host_id)
    if not success:
        return (False, "Can't delete host %s" + host_id)

    return (True, None)


def delete_orphan_status_message():
    """
    Launch a job to remove orphan status messages
    """
    job = asset_clean_orphan_status_messages.apply_async()
    if job.state != 'FAILURE':
        return (True, {'jobid': job.id})
    else:
        return (False, "Can't start task to delete orphan status message")
