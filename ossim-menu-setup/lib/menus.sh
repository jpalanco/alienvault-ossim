#!/bin/bash

MenuReconfigure() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO"  --title "$MSG_RECONFIG" \
--menu "$MSG_SEL_OPT\n\n\
$SELECT_OPTION:" 20 51 6 \
"1" "$MSG_RE_SER" \
"2" "$MSG_RE_FRA" \
"3" "$MSG_RE_SNO" \
"4" "$MSG_RE_ACI" \
"5" "$MSG_RE_PHP" \
"6" "$MSG_RE_NTO" \
"7" "Back" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in
0)
    case $choice in
        "1") dpkg-reconfigure -plow ossim-server
             return;;
        "2") dpkg-reconfigure -plow ossim-framework
             return;;
        "3") dpkg-reconfigure -plow snort-mysql
             return;;
        "4") dpkg-reconfigure -plow acidbase
             return;;
        "5") dpkg-reconfigure -plow phpgacl
             return;;
        "6") dpkg-reconfigure -plow ntop
             return;;
        "7") MenuOther;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


MenuDevel() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO"  --title "$MSG_DEVEL" \
--menu "$MSG_SEL_OPT\n\n\
$SELECT_OPTION:" 20 51 6 \
"1" "$MSG_BUILD_DPS" \
"2" "$MSG_PCK_CVS" \
"3" "Back" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in
0)
    case $choice in
        "1") download_build_deps
             return;;
        "2") create_cvs_packages
             return;;
        "3") return;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}

MenuOther() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO"  --title "$MSG_OTHER" \
--menu "$MSG_SEL_OPT\n\n\
$SELECT_OPTION:" 20 52 6 \
"1" "$MSG_DEVEL" \
"2" "$MSG_ADD_SEN" \
"3" "$MSG_CLEAN" \
"4" "$MSG_CLEAN_DB_ME" \
"5" "$MSG_RECONFIG" \
"6" "$MSG_CH_MYSQL_PASS" \
"7" "Back" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in
0)
    case $choice in
        "1") MenuDevel;;
        "2") add_sensor
             return;;
        "3") clean_vmossim
             dialog_message "$MSG_VM_CLEAN_TI" "$MSG_VM_CLEAN"
             return;;
        "4") clean_mysql
             return;;
        "5") MenuReconfigure
             return;;
        "6") dpkg-reconfigure mysql-server-5.0
             dialog_message "$MSG_MY_CH_TI" "$MSG_MY_CH"
             return;;
        "7") return;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


MenuUpdate() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO"  --title "$MSG_UPDATE" \
--menu "$MSG_WELCOME.\n\n\
$SELECT_OPTION:" 20 51 6 \
"1" "$MSG_UP_NE" \
"2" "$MSG_UP_OV" \
"3" "$MSG_UP_DE" \
"4" "$MSG_UP_OS" \
"5" "$MSG_UP_SN" \
"6" "$MSG_BACK" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in
0)
    case $choice in
        "1") update_nessus_plugins
             return;; 
        "2") download_osvdb
             return;; 
        "3") update_debian_system
             return;; 
        "4") update_ossim_packages
             return;;
        "5") update_snort_sidmap
             return;;
        "6") return;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


MenuProf() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO" --title "VMOSSIM Profile" \
--menu "$MSG_PRO_SEL\n\n\
$SELECT_OPTION:" 20 51 6 \
"1" "$MSG_ALL_IN_ONE" \
"2" "$MSG_SENSOR" \
"3" "$MSG_SERVER"  \
"4" "$MSG_BACK" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in
0)
    case $choice in
        "1") confirmation_pro All-in-one
             return;; 
        "2") confirmation_pro Sensor
             return;; 
        "3") confirmation_pro Server
             return;;
        "4") MenuMain;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


MenuSelectLang() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER"  --cancel-label "Exit" --title "VMOSSIM" \
--menu "Welcome to VMOSSIM.\n\n\
Select your language / Escoja su idioma:" 20 51 6 \
"1" "English" \
"2" "Español" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in

0)
    case $choice in
        "1") source lang/enUS.sh
             MenuMain;;
        "2") source lang/esES.sh
             MenuMain;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


MenuMain() {
rm -f /tmp/vmossim*

tempfile=`tempfile 2>/dev/null` || tempfile=/tmp/vmossim$$

trap "rm -f $tempfile" 0 1 2 5 15

$DIALOG --clear --backtitle "$VMOSSIM_VER | $ACT_PRO"  --title "$MSG_MAIN" \
--cancel-label "$MSG_EXIT" --menu "$MSG_WELCOME.\n\n\
$SELECT_OPTION:" 20 51 6 \
"1" "$MSG_CH_PRO" \
"2" "$MSG_NET_CONF" \
"3" "$MSG_OTHER" \
"4" "$MSG_UPDATE" \
"5" "$MSG_ABOUT" 2> $tempfile

retval=$?

choice=`cat $tempfile`

case $retval in

0)
    case $choice in
        "1") MenuProf;;
        "2") configure_network
             perl tools/wizard.pl
             dialog_message "$MSG_CONF_UP_TI" "$MSG_CONF_UP"
             MenuMain;;
        "3") MenuOther
             MenuMain;;
        "4") MenuUpdate
             MenuMain;;
        "5") about
             MenuMain;;
        *) exit;;
    esac;;
1)
    clear
    exit;;
255)
    clear
    exit;;
esac

#delete old tempfiles
rm -f /tmp/vmossim*
}


