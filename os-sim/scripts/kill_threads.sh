#!/bin/bash
PROC_PID=`ps -eo "%c%p" | grep -w -e "$1\(_eth[0-9]\)" -e "$1" | awk '{print $2}' | tr '\n' ' '`

# send the KILL if a valid PID was found
if [ "x$PROC_PID" != "x" ]
then
    kill -9 $PROC_PID
fi
