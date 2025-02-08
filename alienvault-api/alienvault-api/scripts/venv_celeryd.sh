#! /bin/bash

VIRTUALENV="/usr/share/python/alienvault-api-core"
CELERYD="/usr/share/python/alienvault-api/scripts/celeryd"
RUNDIR="/var/run"
PIDDIR="$RUNDIR/alienvault"
PIDFILE="$PIDDIR/celeryd.pid"

. /lib/lsb/init-functions

do_start ()
{
    # Check if /var/run is a symbolic link to determine the Debian configuration
    if [ -h $RUNDIR ]; then
       # Wheezy
       mount | grep "/run type"
       if [ "$?" != "1" ]; then
          if [ ! -d $PIDDIR ]; then
             mkdir -p -m 0770 "$PIDDIR"
             chgrp -R alienvault "$PIDDIR"
          fi
       else
          log_daemon_msg "ERROR: /run is not mounted yet. The system is not stable. Skipping"
          exit 1
       fi
    else
       # Squeeze
       if [ ! -d $PIDDIR ]; then
          mkdir -p -m 0770 "$PIDDIR"
          chgrp -R alienvault "$PIDDIR"
       fi
    fi

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
	ps -C python -o pid=,cmd= | awk '/celery.bin.celeryd/'{'print $1'} | while read p;
	do
		kill -TERM $p
	done
	sleep 10
    ps -C python -o pid=,cmd= | awk '/celery.bin.celeryd/'{'print $1'} | while read p;
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
