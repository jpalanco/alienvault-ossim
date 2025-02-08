#!/bin/bash

PARENT_PROCESS_NAME="Unknown"
LOG="/var/log/alienvault/scripts/latest_clean_zero_sized.log"

parent_process_name(){

  parentPID=$PPID
  parentPID_name=`ps -p $parentPID -o comm=`
  if [[ ${?} -eq 0 ]]; then
    PARENT_PROCESS_NAME=$parentPID_name
    return 0
  fi

}

check_process_is_not_running(){

  local process_name
  process_name=$1
  first_time=0
  retries=0
  
  PID_process=`/bin/pidof \$process_name`

  if [[ ${?} -eq 0 ]]; then
      while [ -e /proc/$PID_process ]; do
         if [[ ${retries} -gt 100 ]]; then
            echo "Max num retries reached ... exiting"
            return 1
	     fi
 
         if [[ ${first_time} -eq 0 ]]; then
            echo "${process_name} proccess with PID ${PID_process} is running: waiting for its ending..."
            first_time=1
         else
            printf  "."
         fi

         sleep .3
         retries=$(( $retries +1 ))
       done
       
       printf " finished\n"
       return 0
  else
       echo "Checked that ${process_name} process is not running"
       return 0
  fi

}

parent_process_name $0
echo "[$(date)] ------ Started by $PARENT_PROCESS_NAME" | tee -a $LOG

check_process_is_not_running "logrotate" 

if [[ ${?} -eq 0 ]]; then
   find /var/log -type f -size 0 -name "*.1" -exec rm -v {} \; | tee -a $LOG
   find /var/log -type f -size 0 -name "*.gz" -exec rm -v {} \; | tee -a $LOG
   find /var/ossec/logs -type f -size 0 -name "*.1" -exec rm -v {} \; | tee -a $LOG
   find /var/ossec/logs -type f -size 0 -name "*.gz" -exec rm -v {} \; | tee -a $LOG
fi


