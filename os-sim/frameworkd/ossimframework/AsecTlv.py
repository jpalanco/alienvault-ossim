# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2012-2014 AlienVault
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

import struct
import os
import array
import base64

class ASECTLV(object):
    """@brief Encapsulates the TLV (type-len-value) messages
    We use 4 bytes for the type and 4 bytes for the len
    0            4            8                           n
    +------------+------------+---------------------------+
    + TLV_TYPE   + TLV_LEN    +         DATA              +
    +------------+------------+---------------------------+
    """
    #message types
    TLV_TYPE_UNKNOWN =       0x00000000
    TLV_TYPE_LOG =           0x1F1FAA01
    TLV_TYPE_MLOG4FWK =      0x1F1FAA02
    TLV_TYPE_PATTERN =       0x1F1FAA03
    TLV_TYPE_ACTIVE_PLUGIN = 0x1F1FAA04

    #message fields
    TLV_TYPE_LOG_FIELD_FILENAME =  0x1F1F0001
    TLV_TYPE_LOG_FIELD_LOG_LINE =  0x1F1F0002
    TLV_TYPE_LOG_FIELD_SENSOR_ID = 0x1F1F0003

    TLV_TYPE_MLOG4FWK_FIELD_LOG_LINE = 0x1F1F0010
    TLV_TYPE_MLOG4FWK_FIELD_REGEXP =   0x1F1F0011
    TLV_TYPE_MLOG4FWK_FIELD_SENSOR_ID= 0x1F1F0012
    
    TLV_TYPE_PATTERN_FIELD_ID =       0x1F1F0030
    TLV_TYPE_PATTERN_FIELD_FILENAME = 0x1F1F0031
    TLV_TYPE_PATTERN_FIELD_JSON_STR = 0x1F1F0032

    TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_ID =        0x1F1F0040
    TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_NAME =      0x1F1F0041
    TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_SENSOR_ID = 0x1F1F0042
    TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_LOG_FILE =  0x1F1F0043


    @staticmethod
    def tlv_simple(tlv_type,tlv_data,tlv_len):
        """Builds a simple TLV message and pack it!
        @param tlv_data the TLV data 
        @param tlv_len the TLV len
        """
        tlv_buff=struct.pack('!I',tlv_type)
        tlv_buff+=struct.pack('!I',tlv_len)
        for c in tlv_data:
            tlv_buff+= struct.pack('!c', c);
        return tlv_buff

    @staticmethod
    def tlv_composite(tlv_type,tlv_list):
        """Builds a composite TLV message and pack it
        @param tlv_list TLV list 
        """
        tlv_buff = struct.pack('!I',tlv_type)
        total_len = 0
        tlv_data = array.array('c')
        for tlv in tlv_list:
            tlv_type,tlv_len,tlv_value = ASECTLV.tlv_decode(tlv)
            total_len += tlv_len + 8
            for c in tlv:
                tlv_data.append(c)
        tlv_data = tlv_data.tolist()
        tlv_buff+=struct.pack('!I',total_len)
        for c in tlv_data:
            tlv_buff+= struct.pack('!c', c);

        return tlv_buff

    @staticmethod
    def tlv_decode(data):
        """@brief Decodes a tlv from the data
        """
        offset = 0
        tlv_type = struct.unpack('!I',data[offset:offset+4])[0];
        offset +=4
        tlv_len = struct.unpack('!I',data[offset:offset+4])[0];
        offset +=4
        datavalue = data[offset:(offset+tlv_len)]
        tlv_value = struct.unpack('!%ds'%tlv_len, datavalue)[0];
        return (tlv_type,tlv_len,tlv_value)
