#!/bin/bash

source lib/static.sh
source lib/utils.sh
source lib/devel.sh
source lib/profiles.sh
source lib/menus.sh
source lib/netconfig.sh


# Let's check if the script is beeing run as root.
check_if_root

#Main menu
#MenuSelectLang
source lang/enUS.sh
MenuMain
