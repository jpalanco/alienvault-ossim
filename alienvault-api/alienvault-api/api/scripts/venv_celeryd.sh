#! /bin/bash

VIRTUALENV="/usr/share/alienvault/api_core"
CELERYD="/usr/share/alienvault/api/scripts/celeryd"
PIDFILE="/var/run/alienvault/celeryd.pid"

do_start ()
{
    $CELERYD start > /dev/null 2>&1
    if [ $? != 0 ]; then
        ps ax | grep 'celeryd' | grep -v grep > /dev/null
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
    $CELERYD stop > /dev/null 2>&1
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

do_force_stop ()
{
	do_stop
	RET1=$?
	ps auxwww | grep 'celery.bin.celeryd' | grep python| awk '{print $2}' | while read p;
	do
		kill -TERM $p
	done
	sleep 10
    ps auxwww | grep 'celery.bin.celeryd' | grep python| awk '{print $2}' | while read p;
	do
		kill -9 $p
	done

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
	forcestop)
		. $VIRTUALENV/bin/activate
		do_force_stop
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
