"""
Ossim Action Responses Framework
This script requires wmi-client and wmiexec
You have to configure user and password with a valid Admin account for your windows network/domain
rundll32 user32.dll MessageBox "My text"
"""

import os
import sys


user = 'Administrador'
password = ''
machine = ''


def null_route(ip):
    os.system('winexe --uninstall -U %s%%%s //%s "route add %s mask 255.255.255.255 %s"' % (user, password, machine, ip, machine))


def block_computer():
    os.system('winexe --uninstall -U %s%%%s //%s "Rundll32.exe user32.dll,LockWorkStation"' % (user, password, machine))


def halt_computer():
    os.system('winexe --uninstall -U %s%%%s //%s "Shutdown.exe -s -t 00"' % (user, password, machine))


def restart_computer():
    os.system('winexe --uninstall -U %s%%%s //%s "Shutdown.exe -r -t 00"' % (user, password, machine))


def sleep_computer():
    os.system('winexe --uninstall -U %s%%%s //%s "Rundll32.exe powrprof.dll,SetSuspendState Sleep"' % (user, password, machine))


def hibernate_computer():
    os.system('winexe --uninstall -U %s%%%s //%s "Rundll32.exe powrprof.dll,SetSuspendState"' % (user, password, machine))


def kill_process(process):
    os.system('winexe --uninstall -U %s%%%s //%s "taskkill /IM %s"' % (user, password, machine, process))


def start_service(service):
    os.system('winexe --uninstall -U %s%%%s //%s "net start %s"' % (user, password, machine, service))


def stop_service(service):
    os.system('winexe --uninstall -U %s%%%s //%s "net stop %s"' % (user, password, machine, service))


# Requires Msessenger service to be started
def send_message(msg):
    os.system('winexe --uninstall -U %s%%%s //%s "net send %s %s"' % (user, password, machine, machine, msg))


def flush_dns():
    os.system('winexe --uninstall -U %s%%%s //%s "ipconfig /flushdns"' % (user, password, machine))


if len(sys.argv) > 1:
    # nullroute
    if sys.argv[1] == '-nullRoute':
        machine = sys.argv[3]
        ip = sys.argv[2]
        null_route(ip)

    # block computer
    if sys.argv[1] == '-block':
        machine = sys.argv[2]
        block_computer()

    # halt computer
    if sys.argv[1] == '-halt':
        machine = sys.argv[2]
        halt_computer()

    # restart computer
    if sys.argv[1] == '-restart':
        machine = sys.argv[2]
        restart_computer()

    # sleep computer
    if sys.argv[1] == '-sleep':
        machine = sys.argv[2]
        sleep_computer()

    # hibernate computer
    if sys.argv[1] == '-hibernate':
        machine = sys.argv[2]
        hibernate_computer()

    # kill a process
    if sys.argv[1] == '-kill':
        machine = sys.argv[3]
        process = sys.argv[2]
        kill_process(process)

    # Start Service
    if sys.argv[1] == '-startService':
        machine = sys.argv[3]
        service = sys.argv[2]
        start_service(service)

    # Stop Service
    if sys.argv[1] == '-stopService':
        machine = sys.argv[3]
        service = sys.argv[2]
        stop_service(service)

    # Send Message
    if sys.argv[1] == '-sendMsg':
        machine = sys.argv[3]
        msg = sys.argv[2]
        send_message(msg)

    # FlushDNS
    if sys.argv[1] == '-flushDNS':
        machine = sys.argv[2]
        flush_dns()

else:
    print """Usage:
    \t-nullRoute ip machine
    \t-block machine
    \t-halt machine
    \t-restart machine
    \t-sleep machine
    \t-hibernate machine
    \t-kill process machine -start_service service machine
    \t-stopService service machine
    \t-sendMsg msg machine
    \t-flushDNS machine\n"""
