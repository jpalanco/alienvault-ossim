#!/bin/bash

AllinoneWizard()
{
    ALLSTEP=0
    while true;do
        case $ALLSTEP in
        0) ChooseInterfaces;;
        1) ChooseNetworks;;
        2) SensorName;;
        3) ChoosepluginsMonitors;;
        4) ChoosepluginsDetectors;;
        5) $PERL $TINY $tempfile set database db_ip ""
           $PERL $TINY $tempfile set sensor ip ""
           $PERL $TINY $tempfile set server server_ip ""
			return
            ;;
        *)
            return;;
        esac
    done
  # another variables if $EXPERT is set
}

