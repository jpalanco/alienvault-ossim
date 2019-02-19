#!/bin/bash
#threshold time after which rebuild process is counted as a process that causes deadlock
#notice: cumulative CPU time is counted, not script start time
threshold=3600 #one hour
#seconds to wait between the script tries to launch rebuild
waiting_timeout=15

wait=1
#infinite loop, to wait untill conditions are met to run the script
while [ $wait == 1 ]; do
   tokill=()
   duplicates=()
   wait=0
   running_processes=0
   #parsing ps -aux output line by line
   while read line ; do
      #check if vulnurability scan is running. If database rebuild will be launched while scan - this will cause deadlock, high system load and error in scan job.
      if [ $(ps -aux | grep "/nessus_jobs.pl" | grep -v "grep" | wc -l) != 0 ]; then
          wait=1
      #ignore empty lines
      elif [ ${#line} -gt 0 ]; then 
         _pid=$(echo "$line" | awk '{print $2}');
         #check if script is sh or 
         issh=$(echo "$line" | grep "openvas_rebuild.sh")
         if [ ${#issh} -gt 0 ]; then
             duplicates+=("$_pid")
             continue;
         fi
         #get pid and cpu time
         _time=$(echo "$line" | awk '{print $10}');
         _time=(${_time//:/ })
         #strip leading zeroes
         _minutes=$(echo "${_time[0]}" | sed "s/^0*\([1-9]\)/\1/;s/^0*$/0/")
         _seconds=$(echo "${_time[1]}" | sed "s/^0*\([1-9]\)/\1/;s/^0*$/0/")
         _time=$(( ${_minutes} * 60 + ${_seconds} ))
         #mark process as dead locked
         if [ $_time -ge $threshold ]; then
            tokill+=("$_pid")
         else
            #increment amount of running porocesses not matching threshold
            running_processes=$(($running_processes + 1))
         fi
      fi
   done <<< "$(ps -auxww | grep -P "([0-9]+:[0-9]{2} openvasmd: Rebuilding)|(openvas_rebuild.sh)" | grep -v "grep")";
   #leave only one pending sh process (remove duplicates)
   duplicates=("${duplicates[@]:1}")
   for pid in "${duplicates[@]}"; do
      kill -9 "$pid"
   done
   #sleep is count of running process is reasonable
   #number of max processes running simultaneously should always be equal to one
   #because larger numbers may break vulnerability scan in certain circumstances
   if [ $running_processes -ge 1 ]; then
      wait=1;
   fi

   if [ $wait -eq 1 ]; then
      sleep $waiting_timeout
   #or kill all deadlock causing process if count of running process is less then allowed
   else
      for pid in "${tokill[@]}"; do
         kill -9 "$pid"
      done
   fi
done;
