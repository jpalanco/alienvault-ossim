#!/usr/bin/env python
import pyudev
import socket
import time

DELIMITER = ';'

ip = socket.gethostbyname(socket.gethostname())

context = pyudev.Context()
monitor = pyudev.Monitor.from_netlink(context)
monitor.filter_by(subsystem='usb',device_type='usb_device')
for action, device in monitor:
    date = time.strftime("%B %d %X", time.localtime())
    udev_log_output = '{0}' + DELIMITER + '{1}' + DELIMITER + '{2}'
    print(date + DELIMITER + ip + DELIMITER + \
        udev_log_output.format(action, device.__getitem__('DEVTYPE'),
                             device.__getitem__('ID_SERIAL')))
