#!/bin/bash


trap "rm -f $tempfile" 0 1 2 5 15


ChangeLanguage() {

TITLE="`gettext "OSSIM Setup :: General Config :: Change Language"`"
BODY="`gettext "Choose the default Frontend language"`:"

iter=1
#declare -a LANG

lang=`$PERL $TINY $tempfile get _ language`

if ! [ -z $lang ];then
        $DIALOG --default-item $lang --clear --backtitle "$BACKTITLE" \
            --title "$TITLE" \
            --cancel-label "$BACK" \
            --menu "\n$BODY" 15 71 6 \
            de_DE "German" en_GB "English" es_ES "Spanish" fr_FR "French" ja_JP \
            "Japanese" pt_BR "Brazilian Portuguese" ru_RU.UTF8 "Russian" \
            zh_CN "Simplified Chinese" zh_TW "Traditional Chinese" 2> $temp
else
        $DIALOG --clear --backtitle "$BACKTITLE" \
            --title "$TITLE" \
            --cancel-label "$BACK" \
             --menu "\n$BODY" 15 71 6 \
            de_DE "German" en_GB "English" es_ES "Spanish" fr_FR "French" ja_JP \
            "Japanese" pt_BR "Brazilian Portuguese" ru_RU.UTF8 "Russian" \
            zh_CN "Simplified Chinese" zh_TW "Traditional Chinese" 2> $temp
fi

  retval=$?
  choice=`cat $temp`
  case $retval in
  0)
        $PERL $TINY $tempfile set _ language $choice
      ;;
  1)
      clear
      return;;
  255)
      clear
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac
}

ChangeInterface() {

TITLE="`gettext "OSSIM Setup :: General Config :: Change Interface"`"
BODY="`gettext "Choose the default Network Interface. Remember that it must have a valid IPV4 address assigned"`:"

trap "rm -f $temp" 0 1 2 5 15

iter=1
declare -a DEVARR

interf=""
list=""
items=""
interf=`$PERL $TINY $tempfile get _ interface`
list=`egrep -io "[a-zA-Z]+[0-9]+" /proc/net/dev`
for d in $list;do
    items="$items $iter $d" 

    if [ "$interf" = "$d" ];then
        DEFAULT="$iter"
    fi
    DEVARR[$iter]=$d
    let iter=$iter+1
done

if ! [ -z $DEFAULT ];then
$DIALOG --default-item $DEFAULT --clear --backtitle "$BACKTITLE" \
            --title "$TITLE" \
            --cancel-label "$BACK" \
            --menu "\n$BODY" 15 71 5 $items 2> $temp
else
$DIALOG --clear --backtitle "$BACKTITLE" \
            --title "$TITLE" \
            --cancel-label "$BACK" \
            --menu "\n$BODY" 15 71 5 $items 2> $temp
fi

  retval=$?
  choice=`cat $temp`

  case $retval in
  0)
        $PERL $TINY $tempfile set _ interface ${DEVARR[$choice]}
        return
      ;;
  1)
      clear
      return;;
  255)
      clear
      exit;;
  *)
      gettext -e "dialog: Unknown return code\n"
    bye 30
    ;;
  esac

}

GeneralConfig() {

TITLE="`gettext "OSSIM Setup :: General Config"`"
BODY="`gettext "You can change the following information"`:"
OP1="`gettext "Choose the default Network Interface"`"
OP2="`gettext "Change the frontend language"`"
OP3="`gettext "Change the frontend language"`"

#OP3="`gettext Exit`" 

while true;do
$DIALOG --clear --backtitle "$BACKTITLE" \
			--title "$TITLE" \
            --cancel-label "`gettext "Return to Main"`" \
            --menu "\n$BODY" 12 71 3 "1" "$OP1" "2" "$OP2" 2> $temp

  retval=$?
  choice=`cat $temp`

  case $retval in
  0)
    case $choice in
        "1") ChangeInterface
              ;;
        "2") ChangeLanguage
              ;;
          *) gettext "Exiting: Unrecognized option.";
              echo ''
              bye 20;;
      esac
      ;;
  1)
      clear
      return;;
  255)
      clear
      exit;;
  *)
      gettext -e"dialog: Unknown return code\n"
    bye 30
  esac
done
}

