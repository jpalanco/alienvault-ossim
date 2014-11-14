#! /bin/bash

VIRTUALENV="/usr/share/alienvault/api_core"
CELERYBEAT="/usr/share/alienvault/api/scripts/celerybeat"
PIDFILE="/var/run/alienvault/beat.pid"
SCHEDFILE="/tmp/celerybeat-schedule"
API_LOG="/var/log/alienvault/api/api.log"
API_USER="avapi"

do_start ()
{
    # Delete old scheduler file, if exists.
    if [ -f $SCHEDFILE ]; then
        rm $SCHEDFILE
    fi

    sudo -u $API_USER $CELERYBEAT start > /dev/null 2>&1
    if [ $? != 0 ]; then
        ps ax | grep 'celerybeat' | grep -v grep > /dev/null
        if [ $? == 0 ]; then
            echo " already started"
            return 1
        else
            echo "KO"
            return 2
        fi
    else
        echo " OK"
    fi

    return 0
}

do_stop ()
{
    $CELERYBEAT stop > /dev/null 2>&1
    if [ -f $PIDFILE ]; then
        PID=`cat $PIDFILE`
        rm $PIDFILE
        kill -9 $PID
        if [ $? == 0 ]; then
            echo "OK"
        else
            echo "KO"
            return 1
        fi
    fi

    return 0
}

case "$1" in
    start)
        . $VIRTUALENV/bin/activate
        do_start
        RET=$?
        deactivate
        exit $RET
        ;;

    stop)
        . $VIRTUALENV/bin/activate
        do_stop
        RET=$?
        deactivate
        exit $RET
        ;;

    restart)
        . $VIRTUALENV/bin/activate
        do_stop
        [ $? == 0 ] || (deactivate && exit 1)
        do_start
        RET=$?
        deactivate
        exit $RET
        ;;
esac

exit 0
