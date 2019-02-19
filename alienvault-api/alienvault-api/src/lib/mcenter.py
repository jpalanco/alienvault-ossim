#
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

# Standard imports
import os
import ast
import pickle

from base64 import b64decode

# Related third-party imports
import requests

from requests.exceptions import RequestException
from Crypto.PublicKey import RSA
from Crypto.Signature import PKCS1_v1_5
from Crypto.Hash import SHA256


# Local specific imports
from api import app
from apimethods.system.system import get_system_tags
from apimethods.system.proxy import AVProxy
revision_packed_path = "/var/alienvault/api_mcserver_latest_revision"


def save_messages_revision(revision):
    """Save the given revision on the serialized file"""
    try:
        data = {"revision": revision}
        with open(revision_packed_path, 'wb') as handle:
            pickle.dump(data, handle)
        if os.path.isfile(revision_packed_path):
            os.chmod(revision_packed_path, 0600)
    except Exception as e:
        app.logger.error("Cannot save messages revision: %s" % str(e))
        return False
    return True


def get_latest_message_revision():
    """Loads the latest revision number"""
    revision = None
    if not os.path.isfile(revision_packed_path):
        return revision
    with open(revision_packed_path, 'rb') as handle:
        data = pickle.load(handle)
        if 'revision' in data:
            revision = data['revision']
    return revision


def verify_sign(signature, data):
    """Verify that the given data comes from the expected source
    Args:
        signature (str): Data signature
        data (str): Data (b64encoded)
    Returns:
        success(bool). True if the signature is valid; False otherwise.
    """
    try:
        pub_key = open(app.config['MESSAGE_CENTER_PUBLIC_KEY'], "r").read()
        rsakey = RSA.importKey(pub_key)
        signer = PKCS1_v1_5.new(rsakey)
        digest = SHA256.new()
        digest.update(b64decode(data))
        if signer.verify(digest, b64decode(signature)):
            return True
    except Exception as error:
        app.logger.error("An error occurred while verifying the data %s" % str(error))
    return False


def get_message_center_messages():
    """Retrieves the list of messages from the MCServer
    Args:
        None
    Returns:
        message_list: List of messages"""
    messages = []
    conn_failed = False

    try:
        # First we need to retrieve the list of tags for the current system.
        # If this call fails, we shouldn't make the mcserver request
        proxy = AVProxy()
        if proxy is None:
            app.logger.error("Connection error with AVProxy")

        system_tags = get_system_tags()
        if len(system_tags) == 0:
            return []
        revision = get_latest_message_revision()
        msg_filter = "filters={}".format(','.join(system_tags))
        if revision is not None:
            msg_filter += "&revision={}".format(revision)

        url = 'https://{}:{}/messages?{}'.format(app.config['MESSAGE_CENTER_SERVER'],
                                                 app.config['MESSAGE_CENTER_PORT'],
                                                 msg_filter)
        proxies = proxy.get_proxies()
        response = requests.get(url, proxies=proxies, timeout=20)
        response_data = response.json()
        response_code = response.status_code

        if response_code != 200:
            app.logger.warning("Invalid response from the mcserver %s:%s" % (response_code, response_data))
        for field in ['data', 'status', 'signature']:
            if field not in response_data:
                return []

        # Validate the data
        if not verify_sign(response_data['signature'], response_data['data']):
            app.logger.warning("Cannot verify the data coming from the mcserver")
            return [], False

        messages = ast.literal_eval(b64decode(response_data['data']))
        if 'revision' in response_data:
            save_messages_revision(response_data['revision'])

    except RequestException as e:
        conn_failed = True
        app.logger.error("Cannot connect to the Message Center Server : {}".format(e))

    except Exception:
        import traceback
        app.logger.error("An error occurred while retrieving the Message Center Server messages: {}".format(
            str(traceback.format_exc())))

    return messages, conn_failed
