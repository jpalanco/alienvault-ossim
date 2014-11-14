#!/bin/bash
usage="$(basename "$0") [-h] -- Alienvault Benchmark
Alienvault Benchmark script that will help you know your system performance and healthy
This script will perfom the following tests:
 * CPU test.
    - Bogomips: This is a measurement of the CPU Speed. This measuremnt cannot be used for performance comparaison. 
                You can use this value to check whether the processor is in a range similar to other processor. 
    - Max prime calculation. We're going measure the time that the system needs to calculate the first 20000 prime numbers.
    - Simple calculus. We're going to measure the time that the system needs to calculate a simple exponent. (but a cpu-cost calculus)
 * RAM test. (Memory bandwich benchmark)
    - We are going to use mbw app to measure the memory bandwich of our system. This is made by using three differents approaches:
       - MEMCPY - memcpy test 
       - DUMB - dumb copy 
       - MCBLOCK - memcpy block test        
 * Disk IO test
    - Test I/O benchmark. We're going to measure the file I/O performance. To do that we're going to use a tool called sysbench.
 * DNS status.
    - Check the DNS status
where:
    -h  show this help text
Requirenments:
    In order tu run this benchmark you will need a couple of packages installed on your system:
    * sysbench
    * mbw
    To install them you can run: apt-get install sysbench mbw
"
while getopts ':hs:' option; do
  case "$option" in
    h) echo "$usage"
       exit
       ;;
   \?) printf "illegal option: -%s\n" "$OPTARG" >&2
       echo "$usage" >&2
       exit 1
       ;;
  esac
done

needed_packages=('sysbench' 'mbw')

for var in "${needed_packages[@]}"
do
  #echo "Checking package... ${var}"
  dpkg-query -l ${var}  > /dev/null 2>&1
  result=$?
  if [ $result -gt 0 ];then
    echo "The package $var is not installed. Install it by running: apt-get install $var";
    exit 1;
  fi
done
#Parse test response to get the throughput
get_throughput()
{
    throughput=$(echo "$1" | grep 'Total transferred .* \(.*\)' | awk -F '(' '{print $2}' | sed 's/)//')
}


test_disk()
{
    echo "Disk Test"
    echo "========="
    
    #Declare all the possible tests
    declare -A TESTS
    TESTS['seqwr']='Sequential Write'
    TESTS['seqrewr']='Sequential Rewrite'
    TESTS['seqrd']='Sequential Read'
    TESTS['rndrd']='Random Read'
    TESTS['rndwr']='Random Write'
    TESTS['rndrw']='Combined Random Read/Write'
    
    
    #Preparing the tests
    sysbench --test=fileio --file-total-size=5G prepare > /dev/null
    
    
    for test_mode in "${!TESTS[@]}" 
    do
        result=`sysbench --test=fileio --file-total-size=5G --file-test-mode=$test_mode --max-time=120 --max-requests=0 run`
        
        get_throughput "$result"
        
        echo "${TESTS[$test_mode]},$throughput" | awk -F ',' '{print $1"                        |"$2}'| column -s '|' -t
        
    done

    #Cleaning the tests
    sysbench --test=fileio --file-total-size=5G cleanup > /dev/null
    
}


test_ram()
{
    echo "RAM Memory Test"
    echo "==============="
    echo -e "\t\tElapsed|\tCopy" | column -t -s '|'
    memtest=`mbw -q 1024 | grep AVG`
    
    time=$(echo "$memtest" | grep AVG | grep MEMCPY | awk -F ':' '{print $3}' | awk -F ' ' '{print $1}' | sed 's/ *//')
    speed=$(echo "$memtest" | grep AVG | grep MEMCPY | awk -F ':' '{print $5}' | sed 's/ *//')
    
    echo -e "MEMCPY |\t $time\t $speed" | column -t -s '|'

    time=$(echo "$memtest" | grep AVG | grep DUMB | awk -F ':' '{print $3}' | awk -F ' ' '{print $1}' | sed 's/ *//')
    speed=$(echo "$memtest" | grep AVG | grep DUMB | awk -F ':' '{print $5}' | sed 's/ *//')
    
    echo -e "DUMB  |\t $time\t $speed" | column -t -s '|'
    
    time=$(echo "$memtest" | grep AVG | grep MCBLOCK | awk -F ':' '{print $3}' | awk -F ' ' '{print $1}' | sed 's/ *//')
    speed=$(echo "$memtest" | grep AVG | grep MCBLOCK | awk -F ':' '{print $5}' | sed 's/ *//')
    
    echo -e "MCBLOCK |\t $time\t $speed" | column -t -s '|'
}


test_cpu()
{
    echo "CPU Test"
    echo "========"

    result=0
    for r in `cat /proc/cpuinfo | grep bogomips | awk -F ':' '{print $2}' | sed 's/ *//'`
    do
        result=$(echo $result + $r | bc)
    done
    
    echo -e "Bogomips | $result" | column -t -s '|'
    cpu_prime_calculation=`sysbench --test=cpu --cpu-max-prime=20000 run | grep "total time:"  | awk '{print $3}'`
    echo -e "Max prime number (20000)| $cpu_prime_calculation" | column -t -s '|'
    result=$((time  `echo '5^2^20' |  bc > /dev/null`)  2>&1 | grep real | awk '{print $2}')
    echo -e "Simple calculation (5^2^20)| $result" | column -t -s '|'

}

get_disk_size()
{
    #disk_size=`df -H / | sed '1d'`
    #disk_available=`df -H / | sed '1d' | awk '{print $5}' | cut -d'%' -f1`
    echo "DISK INFORMATION"
    echo "================"
    while read -r line ; do
        #echo $line | awk '{print  "Partition Name: \t",$1,"\nDisk Space    : \t",$2,"\nDisk Available: \t",$4}'
        echo $line | awk '{print  "Partition Name |",$1,"\nDisk Space |",$2,"\nDisk Available |",$4}' |column -t -s '|'
        echo "------------------------------------"
    done < <(df -H | sed '1d')
}

get_dns_stats()
{
    echo "DSN Stats"
    echo "========="
    external=`dig @8.8.8.8 www.google.com | grep msec | awk -F ':' '{print $2}'` 
    echo "External DNS (8.8.8.8)       ,$external" | column -t -s ','
    configured_dns=$(cat /etc/resolv.conf | grep -v '#' | grep nameserver | awk '{print $2}')
    for i in $configured_dns; do
        internal=`dig @$i www.google.com | grep msec | awk -F ':' '{print $2}'`
        echo "Configured DNS ($i)     ,$internal" | column -t -s ','
    done
}


get_regex_stats()
{
    echo "Regex Stats"
    echo "========="
}



clear
echo "######################################################################"
echo "# Alienvault System Benchmark                                        #"
echo "# This benchmark may take a few minutes, please be patient           #"
echo "######################################################################"
echo -e "\n"
get_disk_size
echo -e "\n"
test_cpu
echo -e "\n"
test_ram
echo -e "\n"
test_disk
echo -e "\n"
get_dns_stats
