#!/bin/bash
#
# Diferent services for diferent profiles
#

ALLSERVICES="monit mysql apache2 ossim-framework ossim-server ossim-agent osirisd munin-node"
SERV_ALLINONE=$ALLSERVICES
SERV_SENSOR="monit ossim-agent osirisd munin-node"
SERV_SERVER="monit mysql apache2 ossim-framework ossim-server ossim-agent osirisd munin-node"
SERV_DB="monit mysql"


service_modify_levels()
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
                eval ${CMD} >/dev/null
                CMD="/etc/init.d/${SERV} stop"
                eval ${CMD}
                CMD="/etc/init.d/${SERV} start"
                eval ${CMD}
            ;;
            *)
                echo "Error: malfunction"
                exit 1
            ;;
    esac
}

service_remove()
{
    for SERV in ${ALLSERVICES}
    do
        CMD="update-rc.d -f ${SERV} remove"
        eval ${CMD}
        CMD="/etc/init.d/${SERV} stop"
        eval ${CMD}
    done
}


change_profile() 
{
    PROFILE="$1"

    case $PROFILE in
        all-in-one)
            cp /etc/monit/monitrc-all /etc/monit/monitrc
            service_remove
            for ALLSERV in ${ALLSERVICES}
            do
                for SERV in ${SERV_ALLINONE}
                do
                    if [ ${SERV} = ${ALLSERV} ]
                    then
                        service_modify_levels "${SERV}" "start"
                    fi
                done
            done
            # Update image profile
            #echo "all-in-one" > /etc/vmossim-profile
            #perl tools/wizard.pl
            #success_pro_chg all-in-one
            return
        ;;
        sensor)
            cp /etc/monit/monitrc-sensor /etc/monit/monitrc
            service_remove
            for ALLSERV in ${ALLSERVICES}
            do
                for SERV in ${SERV_SENSOR}
                do
                if [ ${SERV} = ${ALLSERV} ]
                then
                    service_modify_levels "${SERV}" "start"
                fi
                done
            done
            # Update image profile
            #echo "sensor" > /etc/vmossim-profile
            #perl tools/wizard.pl
            #success_pro_chg sensor
            return
        ;;
         server)
            cp /etc/monit/monitrc-server /etc/monit/monitrc
            service_remove
            for ALLSERV in ${ALLSERVICES}
            do
                for SERV in ${SERV_SERVER}
                do
                if [ ${SERV} = ${ALLSERV} ]
                then
                    service_modify_levels "${SERV}" "start"
                fi
                done
            done
            #echo "server" > /etc/vmossim-profile
            #perl tools/wizard.pl
            #success_pro_chg server
            return
        ;;
         database)
            cp /etc/monit/monitrc-database /etc/monit/monitrc
            service_remove
            for ALLSERV in ${ALLSERVICES}
            do
                for SERV in ${SERV_DB}
                do
                if [ ${SERV} = ${ALLSERV} ]
                then
                    service_modify_levels "${SERV}" "start"
                fi
                done
            done
            #echo "server" > /etc/vmossim-profile
            #perl tools/wizard.pl
            #success_pro_chg server
            return;;
        *)
            exit 1
        ;;
esac
}


