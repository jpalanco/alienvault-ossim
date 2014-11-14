#!/bin/bash

DBSTEP=0


dbhost() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Mysql Server IP Address"`"
BODY="`gettext "Please, specify the MySQL database server ip address or leave it blank and localhost will be used"`:"

dbip=`$PERL $TINY $tempfile get database db_ip`

if [ -z "$dbip" ];then
  dbip=""
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$dbip" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set database db_ip "$choices"
    	let DBSTEP=$DBSTEP+1
    	let SERVERSTEP=$SERVERSTEP+1
        return
      ;;
  1)
    	let DBSTEP=$DBSTEP-1
    	let SERVERSTEP=$SERVERSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}

dbport() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Mysql Server Port"`"
BODY="`gettext "Please, specify the MySQL database server port"`:"

dbport=`$PERL $TINY $tempfile get database db_port`

if [ -z "$dbport" ];then
  dbport="ossim"
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$dbport" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set database db_port "$choices"
    	let DBSTEP=$DBSTEP+1
    	let SERVERSTEP=$SERVERSTEP+1
        return
      ;;
  1)
    	let DBSTEP=$DBSTEP-1
    	let SERVERSTEP=$SERVERSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}



dbpass() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Mysql Password"`"
BODY="`gettext "This is the current mysql password for user root. You need to specify it in the server profile that will connect to this database"`:"

pass=`$PERL $TINY $tempfile get database pass`

if [ -z "$pass" ];then
  pass="ossim"
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$pass" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set database pass "$choices"
    	let DBSTEP=$DBSTEP+1
    	let SERVERSTEP=$SERVERSTEP+1
        return
      ;;
  1)
    	let DBSTEP=$DBSTEP-1
    	let SERVERSTEP=$SERVERSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}

DatabaseWizard() {

DBSTEP=0

while true;do
	case $DBSTEP in
		0)
			dbpass;;
		*)
			return;;
	esac
done
}
