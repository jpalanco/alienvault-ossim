#!/bin/bash

if [ "$1" == "stop_search" ]
then
   PROC_PID=`ps -eo pid,args | grep -w -e 'nmap' | grep nmap_scan_${2} | grep -v _targets_file | grep -v sudo | awk '{print $1}' | tr '\n' ' '`
elif [ "$1" == "stop_scan" ]
then
   PROC_PID=`ps -eo pid,args | grep -w -e 'nmap' | grep nmap_scan_${2} | grep _targets_file | grep -v sudo | awk '{print $1}' | tr '\n' ' '`
else
   PROC_PID=`ps -eo "%c%p" | grep -w -e "nmap" | awk '{print $2}' | tr '\n' ' '`
fi

#echo $PROC_PID

# send the KILL if a valid PID was found
if [ "x$PROC_PID" != "x" ]
then
    kill -9 $PROC_PID
fi
