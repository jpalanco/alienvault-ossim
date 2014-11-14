#!/bin/bash

success_pro_chg (){
    $DIALOG --title "Profile changed" --msgbox "Profile changed to: $1 Please
    reboot" 10 50
}

f_check_service()
{
	ACTION="$2"
	SERVICE="$1"
	case ${ACTION} in
			start)
				case ${SERVICE} in
					mysql-ndb-mgm)
						PRIO_S="19"
						PRIO_K="90"
					;;
					mysql)
						PRIO_S="20"
						PRIO_K="91"
					;;
					mysql-ndb)
						PRIO_S="21"
						PRIO_K="92"
					;;
					ossim-framework)
						PRIO_S="31"
						PRIO_K="10"
					;;
					ossim-server)
						PRIO_S="22"
						PRIO_K="80"
					;;
					nagios2)
						PRIO_S="30"
						PRIO_K="90"
					;;
					ossim-agent)
						PRIO_S="92"
						PRIO_K="10"
					;;
					apache2)
						PRIO_S="91"
						PRIO_K="10"
					;;					
					nessusd)
						PRIO_S="91"
						PRIO_K="10"
					;;					
					osirismd)
						PRIO_S="91"
						PRIO_K="10"
					;;					
					osirisd)
						PRIO_S="91"
						PRIO_K="10"
					;;					
					ntop)
						PRIO_S="91"
						PRIO_K="10"
					;;					
				    snort)
						PRIO_S="91"
						PRIO_K="10"
					;;					
					esac
				CMD="update-rc.d ${SERV} start ${PRIO_S} 2 3 5 . stop ${PRIO_K} 0 1 6 ."
				eval ${CMD}
			;;
			*)
				echo "Error: malfunction"
				exit 1
			;;
	esac
}

f_stop_all()
{
	for SERV in ${ALLSERVICES}
	do
		CMD="update-rc.d -f ${SERV} remove"
		eval ${CMD}
	done
}


change_profile () {
    PROFILE="$1"

    case $PROFILE in
	    All-in-one)
            cp /etc/monit/monitrc-all /etc/monit/monitrc
            f_stop_all
		    for ALLSERV in ${ALLSERVICES}
		    do
			    for SERV in ${SERV_ALLINONE}
			    do
				    if [ ${SERV} = ${ALLSERV} ]
				    then
					    f_check_service "${SERV}" "start"
				    fi
			    done
		    done
            # Update image profile
            echo "all-in-one" > /etc/vmossim-profile
            perl tools/wizard.pl
            success_pro_chg all-in-one
	        return
        ;;
        Sensor)
		    cp /etc/monit/monitrc-sensor /etc/monit/monitrc
		    f_stop_all
            for ALLSERV in ${ALLSERVICES}
		    do
			    for SERV in ${SERV_SENSOR}
			    do
				if [ ${SERV} = ${ALLSERV} ]
				then
					f_check_service "${SERV}" "start"
				fi
			    done
		    done
            # Update image profile
            echo "sensor" > /etc/vmossim-profile
            perl tools/wizard.pl
            success_pro_chg sensor
            return
	    ;;
	    Server)
		    cp /etc/monit/monitrc-server /etc/monit/monitrc
		    f_stop_all
            for ALLSERV in ${ALLSERVICES}
		    do
			    for SERV in ${SERV_SERVER}
			    do
				if [ ${SERV} = ${ALLSERV} ]
				then
					f_check_service "${SERV}" "start"
				fi
			    done
		    done
	        echo "server" > /etc/vmossim-profile
            perl tools/wizard.pl
            success_pro_chg server
            return
        ;;
	    *)
		    exit 1
	    ;;
esac
}

confirmation_pro () {
    pro_name=$1
    
    $DIALOG --clear --backtitle "$VMOSSIM_VER" --title "$MSG_CONF_TI" --yesno "$MSG_CONF_PRO $pro_name" 0 0 

    retval=$?
    if [ $retval -eq 0 ]
    then
        change_profile $pro_name
    else
        MenuMain
    fi
}





choose_server_profile(){
#dialog --checklist "Choose toppings:" 10 40 3 1 Cheese on 2 "Tomato Sauce" on 3 Anchovies off 2> $tempfile
echo ""
}








