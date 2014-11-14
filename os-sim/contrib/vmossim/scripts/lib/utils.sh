#!/bin/bash

# This functions checks if the script is beeing run as root.
function check_if_root () {
    if [ "$UID" -ne "0" ]
    then
         echo [ERROR] $ERR_NOT_ROOT
         exit 1
    fi
}

#about menu
about (){
    $DIALOG  --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_ABOUT" --msgbox "VMOSSIM $MSG_VERSION: $VMOSSIM_VER \n$MSG_PROFILE: $ACT_PRO\nhttp://www.ossim.net" 10 50
}

# Show a message in dialog
#usage dialog_message $title $message
dialog_message (){
    $DIALOG --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$1" --msgbox "$2" 10 50
}

#usage: check_internet_con www.ossim.net
# returns 1 if there is internet connection and 0 if not
check_internet_con (){
if  ping -q -n -w 1 -c 1 www.google.com > /dev/null ; then
    return 1
else
    return 0
fi
}

#this function clean logs and history
clean_vmossim (){

if [ "$CURRENT_RUNLEVEL" = "unknown" ] || [ "$CURRENT_RUNLEVEL" = S ] || [ "$CURRENT_RUNLEVEL" = 1 ]; then
    
    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_CLEAN" --yesno "$MSG_VM_CLEAN_CONF" 0 0

    retval=$?
    
    if [ $retval -eq 0 ]
    then
        apt-get clean 2> /dev/null
        rm -f /var/log/* 2> /dev/null
        touch /var/log/btmp
        touch /var/log/wtmp
        rm -f /var/log/apache/* 2> /dev/null
        rm -f /var/log/apache2/* 2> /dev/null
        rm -f /var/log/ossim/* 2> /dev/null
        rm -f /var/log/nagios2/* 2> /dev/null
        rm -f /var/log/news/* 2> /dev/null
        rm -f /var/log/fsck/* 2> /dev/null
        rm -f /var/log/exim4/* 2> /dev/null
        rm -f /var/log/ntop/* 2> /dev/null
        rm -f /var/log/mysql/* 2> /dev/null
        rm -f /var/log/nessus/* 2> /dev/null
        rm -f /var/log/sysstat/* 2> /dev/null
        rm -f /var/log/snort/* 2> /dev/null
        rm -rf /var/lib/ntop/rrd/interfaces/eth0/hosts/* 2> /dev/null
        rm -f /var/spool/mail/* 2> /dev/null
        rm /home/vmuser/.bash_history 2> /dev/null
        rm /root/.bash_history 2> /dev/null
        
        dialog_message "$MSG_CLEAN" "$MSG_CLEANED"       
        MenuMain
    else
        MenuMain
    fi
else
    dialog_message "$MSG_ERR_RUN_TI" "$MSG_ERR_RUN"
    MenuMain
    return
fi

}

#Check if a command exists
#usage: command_exists ls
#returns 1 if commands exists, 0 if not
command_exists () {

    aux_cmd=`which $1`
    aux_out=$?
    
    if [ -x $aux_cmd ] && [ $aux_out != "0" ]; then
        return 0
        else
        return 1
    fi
}

check_repo_info(){

    #OSSIM Repo
    grep -x "^deb http://www\.ossim\.net/download/ debian/" /etc/apt/sources.list 1> /dev/null
    aux_rep=$?
    if [ $aux_rep = "1" ]; then
        echo "deb http://www.ossim.net/download/ debian/" >> /etc/apt/sources.list
    fi

    #OSSIM sources repo
    grep -x "^deb-src http://www\.ossim\.net/download/ debian/" /etc/apt/sources.list 1> /dev/null
    aux_rep=$?
    if [ $aux_rep = "1" ]; then
        echo "deb-src http://www.ossim.net/download/ debian/" >> /etc/apt/sources.list
    fi

    grep -x "^deb.*\.debian\.org/debian/ etch.*" /etc/apt/sources.list 1> /dev/null
    aux_rep=$?
    if [ $aux_rep = "1" ]; then
        echo "deb http://ftp.debian.org/debian/ etch main" >> /etc/apt/sources.list
    fi

    grep -x "^deb http://security\.debian\.org/ etch/updates main" /etc/apt/sources.list 1> /dev/null
    aux_rep=$?
    if [ $aux_rep = "1" ]; then
        echo "deb http://security.debian.org/ etch/updates main" >> /etc/apt/sources.list
    fi
}

install_package(){
    apt-get install -f "$1"
}

check_package_installed(){

    dpkg -l | grep " $1 " 1> /dev/null

    aux_rep=$?
    if [ $aux_rep = "1" ]; then
        install_package $1
    fi
}

check_profile(){

    profile=`cat /etc/vmossim-profile`

    if [ $profile = "$1" ]; then
        dialog_message "$MSG_ERR_PRO_TI" "$MSG_ERR_PRO $1"
        MenuMain
    fi
}


update_repo_info(){
    apt-get update
    aux_rep=$?
    if [ $aux_rep != "0" ]; then
       dialog_message "$MSG_ERR_UP_RE_TI" "$MSG_ERR_UP_RE"
       MenuMain
    fi
}


update_nessus_plugins(){
    check_profile server
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    else
        check_package_installed nessus-plugins
        check_package_installed nessusd
        check_package_installed ossim-utils
        dialog_message "$MSG_NE_UP_TI" "$MSG_NE_UP"
        nessus-update-plugins
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_UP_NE_TI" "$MSG_ERR_UP_NE"
            MenuMain
        fi
        /etc/init.d/nessusd restart
        perl /usr/share/ossim/scripts/update_nessus_ids.pl
        dialog_message "$MSG_NE_SU_UP_TI" "$MSG_NE_SU_UP"
        MenuMain
    fi 
}

update_debian_system(){
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    fi
    check_repo_info
    update_repo_info
    apt-get -f dist-upgrade
    $aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR_UP_TI" "$MSG_ERR_UP"
        MenuMain
    else
        dialog_message "$MSG_SU_UP_TI" "$MSG_SU_UP"
        MenuMain
    fi
}

update_ossim_packages(){
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    fi
    check_repo_info
    update_repo_info
    apt-get -f install ossim
    $aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR_UP_TI" "$MSG_ERR_UP"
        MenuMain
    else
        dialog_message "$MSG_SU_UP_TI" "$MSG_SU_UP"
        MenuMain
    fi
}

download_build_deps(){
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    fi
    check_repo_info
    update_repo_info
    apt-get -f build-dep ossim
    $aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR_BD_TI" "$MSG_ERR_BD"
        MenuMain
    else
        dialog_message "$MSG_SU_BD_TI" "$MSG_SU_BD"
        MenuMain
    fi
}

create_cvs_packages(){
    pwd_aux=`pwd`
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    fi
    check_package_installed cvs
    download_build_deps
    mkdir -p $TMP_CVS_DIR
    cd $TMP_CVS_DIR
    cvs -z3 -d ':pserver:anonymous@os-sim.cvs.sourceforge.net:/cvsroot/os-sim' checkout os-sim
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR_DO_CVS_TI" "$MSG_ERR_DO_CVS"
        cd $pwd_aux
        MenuMain
    fi
 
    cvs -z3 -d ':pserver:anonymous@os-sim.cvs.sourceforge.net:/cvsroot/os-sim' checkout agent
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR_DO_CVS_TI" "$MSG_ERR_DO_CVS"
        cd $pwd_aux
        MenuMain
    fi
 
    cd os-sim

    dpkg-buildpackage
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_CR_PK"
        cd $pwd_aux
        MenuMain
    fi
    
    cd -
    rm -rf ossim-agent*.deb
    cd agent
    dpkg-buildpackage
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_CR_PK"
        cd $pwd_aux
        MenuMain
    fi
    
    cd -
    mkdir -p $PCK_DST
    cp -f *.deb $PCK_DST 
    
    rm -rf $TMP_CVS_DIR
    
    cd $pwd_aux
    dialog_message "$MSG_PCK_SU_TI" "$MSG_PCK_SU: $TMP_CVS_DIR" 
    MenuMain
}

add_sensor(){
    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_ADD_SEN" --inputbox "$MSG_INPUT_HN" 16 51 2> $tempfile

    retval=$?

    case $retval in
    0)
        sens_hn=`cat $tempfile`;;
    1)
        MenuMain;;
    255)
        exit;;
    esac

    
    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_ADD_SEN" --inputbox "$MSG_INPUT_IA" 16 51 2> $tempfile

    retval=$?

    case $retval in
    0)
        sens_ia=`cat $tempfile`;;
    1)
        MenuMain;;
    255)
        exit;;
    esac

    perl tools/add_sensor.pl $sens_hn $sens_ia
    
    aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_SEN_ER_AD"
        return
    fi
    
    dialog_message "$MSG_SEN_AD_TI" "$MSG_SEN_AD"
    return
}

download_osvdb(){
    pwd_aux=`pwd`
    #dont allow profile sensor
    check_profile sensor
    check_internet_con
    aux_rep=$?
    if [ $aux_rep = "0" ]; then
        dialog_message "$MSG_ERR_NO_INET_TI" "$MSG_ERR_NO_INET"
        MenuMain
    fi
    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "Osvdb License" --exit-label "Ok" --textbox lib/osvdb-license 30 90 2> /dev/null
   
    check_package_installed ossim-utils
    check_package_installed bzip2
    check_package_installed perl
    check_package_installed libxml-parser-perl
    check_package_installed libdbi-perl
    check_package_installed libdbd-mysql-perl
    check_package_installed libhtml-parser-perl

    mkdir -p $TMP_OSV_DIR
    cd $TMP_OSV_DIR 
    rm -f *

    wget $OSVDB_DB_URL 
    aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_DO_OS"
        cd $pwd_aux
        MenuMain
    fi

    bunzip2 xmlDumpByID-Current.xml.bz2
    aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_DO_OS"
        cd $pwd_aux
        MenuMain
    fi

    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_MY_PASS" --inputbox "$MSG_MY_IN" 16 51 2> $tempfile

    retval=$?

    case $retval in
    0)
        pass_my=`cat $tempfile`;;
    1)
        cd $pwd_aux
        MenuMain;;
    255)
        exit;;
    esac


    dialog_message "$MSG_OSVDB_TI" "$MSG_OSVDB"
    
    perl /usr/share/ossim/scripts/xmldbImport.pl -d osvdb -u root -p $pass_my -l localhost -t 2  xmlDumpByID-Current.xml
    aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_DO_OS"
        cd $pwd_aux
        MenuMain
    fi

    dialog_message "$MSG_OSVDB_SU_TI" "$MSG_OSVDB_SU"
    
    cd $pwd_aux

    rm -f $TMP_OSV_DIR

    return
}

update_snort_sidmap(){
    check_profile server
    check_package_installed snort-mysql
    perl /usr/share/ossim/scripts/create_sidmap.pl $RULES_DIR
     aux_rep=$?
    if [ $aux_rep != "0" ]; then
        dialog_message "$MSG_ERR" "$MSG_ERR_UP_SN"
        MenuMain
    fi

    dialog_message "$MSG_SNO_TI" "$MSG_SNO"
    return
}

clean_mysql(){
    check_profile sensor

    $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_CLEAN DB" --yesno "$MSG_DB_CLEAN_CONF" 0 0

    retval=$?
    
    if [ $retval -eq 0 ]
    then
 
        $DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "$MSG_MY_PASS" --inputbox "$MSG_MY_IN" 16 51 2> $tempfile

        retval=$?

        case $retval in
        0)
            pass_my=`cat $tempfile`;;
        1)
        
        cd $pwd_aux
        MenuMain;;
        255)
            exit;;
        esac


        #DROP DATABASES
        echo "drop database ossim" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
        
        echo "drop database snort" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi

        echo "drop database ossim_acl" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
  
        echo "drop database osvdb" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
        
        #CREATE DATABASES
        echo "create database ossim" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
        
        echo "create database snort" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi

        echo "create database ossim_acl" |  mysql -uroot -p$pass_my 2>/dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
  
        echo "create database osvdb" |  mysql -uroot -p$pass_my 2> /dev/null
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi

	cat sql/ossim_acl.orig.sql | mysql -uroot -p$pass_my ossim_acl 2> /dev/null
        cat sql/ossim.orig.sql | mysql -uroot -p$pass_my ossim 2> /dev/null
	cat sql/osvdb.orig.sql | mysql -uroot -p$pass_my osvdb 2> /dev/null
	cat sql/snort.orig.sql | mysql -uroot -p$pass_my snort 2> /dev/null
 
        aux_rep=$?
        if [ $aux_rep != "0" ]; then
            dialog_message "$MSG_ERR_CLE_BD_TI" "$MSG_ERR_CLE_BD"
            MenuMain
        fi
        
        dialog_message "$MSG_CLEAN_DB_TI" "$MSG_CLEAN_DB"       
        MenuMain
    else
        MenuMain
    fi
}
