#!/bin/bash


source lib/sensor.sh
source lib/server.sh
source lib/database.sh
source lib/allinone.sh
source lib/libinit.sh

ProfileConfig() {
  TITLE="`gettext "OSSIM Setup :: Profile Config "`"
  BODY="`gettext "The server profile includes a small agent and a OSSIM database in localhost by default if no other is specified. The all-in-one profile installs a server, sensor and database.\n\nChoose the desired OSSIM profile for this host"`:"

  iter=1
  #declare -a LANG

  profile=`$PERL $TINY $tempfile get _ profile`

  if [ -z $profile ];then
  profile="all-in-one"
  fi

  $DIALOG --default-item $profile --clear --backtitle "$BACKTITLE" \
            --title "$TITLE" \
            --cancel-label "$BACK" \
            --menu "\n$BODY" 20 80 8 \
            sensor "`gettext "This host is a sensor. (disable server and DB, enable sensor stuff)"`." \
      server "`gettext -e "This host is a server + DB. (disable agent and capture stuff)"`" \
            database "`gettext "Install only the database on this host. (reconfigure everything for DB only)"`" \
      all-in-one "`gettext -e "Install a complete system on this host. (enable everything)"`" 2> $temp

  retval=$?
  choice=`cat $temp`
  case $retval in
  0)
        $PERL $TINY $tempfile set _ profile $choice
    case $choice in 
    sensor)
      SensorWizard
    ;;
    server)
      ServerWizard
    ;;
    database)
      DatabaseWizard
    ;;
    all-in-one)
      AllinoneWizard
    ;;
    esac
      ;;
  1)
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}


