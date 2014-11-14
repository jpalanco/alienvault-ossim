#!/bin/bash
# netcardconfig - A very light-weight text-based network configuration tool.
# (C) Klaus Knopper Nov 2002
# Modified by Juan Manuel Lorenzo (juanma at ossim dot net)

bailout(){
rm -f "$TMP_NET"
exit $1
}


configure_network (){

if [ -z "$NETDEVICES" ]; then
$DIALOG --msgbox "$MESSAGE0" 15 45
bailout
fi

count="$(echo "$NETDEVICES" | wc -w)"

if [ "$count" -gt 1 ]; then
DEVICELIST=""
for DEVICE in $NETDEVICES; do DEVICELIST="$DEVICELIST ${DEVICE} Netzwerkkarte_${DEVICE##eth}"; done
rm -f "$TMP_NET"
$DIALOG --menu "$MESSAGE1" 18 45 12 $DEVICELIST 2>"$TMP_NET" || bailout
read DV <"$TMP_NET" ; rm -f "$TMP_NET"
else
# Remove additional spaces
DV="$(echo $NETDEVICES)"
fi

# Debian
if [ -f /etc/network/interfaces ]; then
awk '/iface/{if(/'"$DV"'/){found=1}else{found=0}} 
     /address/{if(found){address=$NF}}
     /netmask/{if(found){netmask=$NF}}
     /broadcast/{if(found){broadcast=$NF}}
     /gateway/{if(found){gateway=$NF}}
   END{print address" "netmask" "broadcast" "gateway}' /etc/network/interfaces >"$TMP_NET"
read IP NM BC DG <"$TMP_NET"
rm -f "$TMP_NET"
fi

$DIALOG --inputbox "$MESSAGE6 $DV" 10 45 "${IP:-172.18.1.1}" 2>"$TMP_NET" || bailout 1
read IP <"$TMP_NET" ; rm -f "$TMP_NET"
$DIALOG --inputbox "$MESSAGE7 $DV" 10 45 "${NM:-255.255.255.0}" 2>"$TMP_NET" || bailout 1
read NM <"$TMP_NET" ; rm -f "$TMP_NET"
$DIALOG --inputbox "$MESSAGE8 $DV" 10 45 "${BC:-${IP%.*}.255}" 2>"$TMP_NET" || bailout 1
read BC <"$TMP_NET" ; rm -f "$TMP_NET"
$DIALOG --inputbox "$MESSAGE9" 10 45 "${DG:-${IP%.*}.254}" 2>"$TMP_NET"
read DG <"$TMP_NET" ; rm -f "$TMP_NET"
if [ -f "/etc/resolv.conf" ]
then
NS="$(awk '/^nameserver/{printf "%s ",$2}' /etc/resolv.conf)"
fi
$DIALOG --inputbox "$MESSAGE10" 10 45 "${NS:-${IP%.*}.254}" 2>"$TMP_NET"
read NS <"$TMP_NET" ; rm -f "$TMP_NET"

CMD="ifconfig $DV $IP netmask $NM broadcast $BC up"
echo "$CMD"
pump -k -i $DV >/dev/null 2>&1 && sleep 4
$CMD

if [ -n "$DG" ]
then
CMD="route add default gw $DG"
echo "$CMD"
$CMD
fi

if [ -w /etc/network/interfaces ]; then
awk '/iface/{if(/'"$DV"'/){found=1}else{found=0}}
     {if(!found){print}}
     END{print "\niface '"$DV"' inet static\n\taddress '"$IP"'\n\tnetmask '"$NM"'\n\tnetwork '"${IP%.*}.0"'\n\tbroadcast '"$BC"'";if("'"$DG"'"!=""){print "\tgateway '"$DG"'"};print "\n"}' \
     /etc/network/interfaces >"$TMP_NET"
# Add an "auto" entry
if egrep -e "^auto[ 	]+.*$DV" /etc/network/interfaces; then

cat "$TMP_NET" >/etc/network/interfaces
else
awk '{if(/^auto/){print $0 " '"$DV"'"}else{print}}' "$TMP_NET" > /etc/network/interfaces
fi
fi

if [ -n "$NS" ]
then
more=""
for i in $NS
do
if [ -z "$more" ]
then
more=yes
echo "$MESSAGE11 $i"
echo "nameserver $i" >/etc/resolv.conf
else
echo "$MESSAGE12 $i"
echo "nameserver $i" >>/etc/resolv.conf
fi
done
fi
dialog_message "$MSG_NET_UP_TI" "$MSG_NET_UP"
}
