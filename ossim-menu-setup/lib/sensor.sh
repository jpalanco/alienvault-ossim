#!/bin/bash
# 

SENSORSTEP=0

IpServer() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Server IP Address"`"
BODY="`gettext "Specify the OSSIM Server IP Address"`:"

server=`$PERL $TINY $tempfile get server server_ip`

if [ -z "$server" ];then
  server="192.168.1.1"
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$server" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set server server_ip "$choices"
    	let SENSORSTEP=$SENSORSTEP+1
        return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}

SensorName() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Sensor Name"`"
BODY="`gettext "Specify the OSSIM Sensor Name for this sensor"`:"

name=`$PERL $TINY $tempfile get sensor name`

if [ -z "$name" ];then
  name="sensorname"
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$name" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set sensor name "$choices"
    	let SENSORSTEP=$SENSORSTEP+1
    	let ALLSTEP=$ALLSTEP+1
        return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
    	let ALLSTEP=$ALLSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}



ChooseNetworks() {
TITLE="`gettext "OSSIM Setup :: Profile Config :: OSSIM Networks"`"
BODY="`gettext "Specify the networks that you want to monitor in CIDR format separated by commas (Ex: 192.168.0.0/24, 10.0.0.0/8 )"`:"

networks=`$PERL $TINY $tempfile get sensor networks`

if [ -z "$networks" ];then
  networks="192.168.0.0/16"
fi

  $DIALOG --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --inputbox "\n$BODY" 15 71 "$networks" 2> $temp
  retval=$?
  choices=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set sensor networks "$choices"
    	let SENSORSTEP=$SENSORSTEP+1
		let SERVERSTEP=$SERVERSTEP+1
		let ALLSTEP=$ALLSTEP+1

        return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
		let SERVERSTEP=$SERVERSTEP-1
		let ALLSTEP=$ALLSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}


ChooseInterfaces() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: Choose Interfaces"`"
BODY="`gettext "Choose the listening Network Interfaces."`:"

iter=1
declare -a DEVARR

interfaces=""
list=""
interfaces=`$PERL $TINY $tempfile get sensor interfaces`
list=`egrep -io "[a-zA-Z]+[0-9]+" /proc/net/dev`
items=""

for d in $list;do
  echo $interfaces|grep $d >/dev/null 2>&1
  if [ $? -eq 0 ];then
      items="$items $iter $d on"
  else
      items="$items $iter $d off"
  fi

    DEVARR[$iter]=$d
    let iter=$iter+1
done

  $DIALOG --default-item $DEFAULT --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --checklist "\n$BODY" 15 71 8 $items 2> $temp
    retval=$?
    choices=`cat $temp`

  case $retval in
  0)
      ifaces=""
      flag=0
      choice=`echo $choices |tr -d "\""`
      for f in $choice;do
        if [ -z "$ifaces" ];then
          ifaces=${DEVARR[$f]}
        else
          ifaces="$ifaces,"${DEVARR[$f]}
        fi
        flag=1
      done

      if [ $flag -eq 1 ];then
    	let SENSORSTEP=$SENSORSTEP+1
		let SERVERSTEP=$SERVERSTEP+1
		let ALLSTEP=$ALLSTEP+1
        $PERL $TINY $tempfile set sensor interfaces "`echo $ifaces`"
      else
        gettext -e "Not changing! It can't be null\n"
      fi
      return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
		let SERVERSTEP=$SERVERSTEP-1
		let ALLSTEP=$ALLSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
      bye 30
      ;;
  esac
}


ChoosepluginsMonitors() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: Choose the Plugins"`"
BODY="`gettext "There are two types of plugins available for OSSIM Agent: Monitors and Detectors. The following list displays the Monitors available. Check the plugins you load with OSSIM Agent"`:"

iter=1
declare -a PLARR

#list="nmap ping ntop opennms ossim-ca tcptrack"
list=`grep cfg /etc/ossim/agent/config.cfg.orig | grep monitor | cut -f 1 -d "="`
monitors=`$PERL $TINY $tempfile get sensor monitors`

if [ -z "$monitors" ];then
  monitors="nmap ping ntop ossim-ca"
fi

items=""
for d in $list;do
  echo $monitors|grep $d >/dev/null 2>&1
  if [ $? -eq 0 ];then
      items="$items $iter $d on"
  else
      items="$items $iter $d off"
  fi

    PLARR[$iter]=$d
    let iter=$iter+1
done

  $DIALOG --default-item $DEFAULT --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --checklist "\n$BODY" 25 71 8 $items 2> $temp
    retval=$?
    choices=`cat $temp`

  case $retval in
  0)
      monits=""
      flag=0
      choice=`echo $choices |tr -d "\""`
      for f in $choice;do
        if [ -z "$monits" ];then
          monits=${PLARR[$f]}
        else
          monits="$monits,"${PLARR[$f]}
        fi
        flag=1
      done

      if [ $flag -eq 1 ];then
    	let SENSORSTEP=$SENSORSTEP+1
    	let ALLSTEP=$ALLSTEP+1
        $PERL $TINY $tempfile set sensor monitors "`echo $monits`"
      else
        gettext -e "Not changing! It can't be null\n"
      fi
      return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
    	let ALLSTEP=$ALLSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
      bye 30
      ;;
  esac
}


ChoosepluginsDetectors() {

TITLE="`gettext "OSSIM Setup :: Profile Config :: Choose the Plugins"`"
BODY="`gettext "There are two types of plugins available for OSSIM Agent: Monitors and Detectors. The following list displays the Detectors available. Check the plugins you load with OSSIM Agent"`:"

iter=1
declare -a PLARR

#list="apache arpwatch cisco-ids cisco-pix cisco-router gfi heartbeat iis iptables mwcollect nagios netgear netscreen-manager netscreen-firewall ntsyslog osiris p0f pads pam_unix postfix realsecure rrd snort spamassassin ssh sudo ossec"
list=`grep cfg /etc/ossim/agent/config.cfg.orig | grep -v monitor | cut -f 1 -d "="`


detectors=`$PERL $TINY $tempfile get sensor detectors`
if [ -z "$detectors" ];then
  detectors="snare osiris snort ssh pam_unix rrd sudo iptables nagios ossec-single-line"
fi

items=""
for d in $list;do
  echo $detectors|grep $d >/dev/null 2>&1
  if [ $? -eq 0 ];then
      items="$items $iter $d on"
  else
      items="$items $iter $d off"
  fi

    PLARR[$iter]=$d
    let iter=$iter+1
done

  $DIALOG --default-item $DEFAULT --clear --backtitle "$BACKTITLE" --title "$TITLE" --cancel-label "$BACK" --checklist "\n$BODY" 25 71 8 $items 2> $temp

    retval=$?
    choices=`cat $temp`

  case $retval in
  0)
      detects=""
      flag=0
      choice=`echo $choices |tr -d "\""`
      for f in $choice;do
        if [ -z "$detects" ];then
          detects=${PLARR[$f]}
        else
          detects="$detects,"${PLARR[$f]}
        fi
        flag=1
      done

      if [ $flag -eq 1 ];then
    	let SENSORSTEP=$SENSORSTEP+1
    	let ALLSTEP=$ALLSTEP+1
        $PERL $TINY $tempfile set sensor detectors "`echo $detects`"
      else
        gettext -e "Not changing! It can't be null\n"
      fi
      return
      ;;
  1)
    	let SENSORSTEP=$SENSORSTEP-1
    	let ALLSTEP=$ALLSTEP-1
      return;;
  255)
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
      bye 30
      ;;
  esac
}


SensorWizard() {
SENSORSTEP=0
while true;do
	case $SENSORSTEP in
	0)
		SensorName;;
	1)
		ChooseInterfaces;;
	2)
		ChooseNetworks;;
	3)
		IpServer;;
	4)
		ChoosepluginsMonitors;;
	5)
		ChoosepluginsDetectors;;
	6)
		return
		;;
	*)
		return
		;;
	esac
done
}

