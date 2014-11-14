# en_US english translation

SELECT_OPTION="Select one of the following options"
MSG_SEL_OPT="Select one of the following options:"
SELECT_SYSTEM_TYPE="Select one of the following system profiles"
SELECT_SYSTEM_SERVER="Select one of the following server system profiles"

#Main menu options
MSG_MAIN="Main"
MSG_WELCOME="Welcome to the VMOSSIM configuration utility"
MSG_CH_PRO="Change system profile"
MSG_NET_CONF="Change network configuration"
MSG_ABOUT="About"
MSG_OTHER="Other"
MSG_QUIT="Quit"
MSG_BACK="Back"
MSG_RECONFIG="Reconfigure"
MSG_EXIT="Exit"
MSG_UPDATE="Update Vmossim"
MSG_NET_UP_TI="Network configuration changed"
MSG_NET_UP="Your network has been successfully configured, Now all the applications will be updated with the new network info."
MSG_CONF_UP_TI="Configuration updated"
MSG_CONF_UP="Applications have been successfully configured with the new network config."
MSG_PRO_SEL="Select one of the following VMOSSIM profiles."
MSG_UP_NE="Update Nessus plugins"
MSG_UP_OV="Update OSVDB database"
MSG_UP_DE="Update all system"
MSG_UP_OS="Update ossim packages"
MSG_DEVEL="Development"
MSG_BUILD_DPS="Install build dependencies"
MSG_PCK_CVS="Create packages from CVS"
MSG_ERR="Error"
MSG_ERR_CR_PK="Error creating Debian packages"
MSG_ERR_DO_OS="Error updating OSVDB database"

MSG_ERR_UP_SN="Error updating snort sids in database"

MSG_SNO_TI="Sids updated"
MSG_SNO="Snort sids have been updated in database"
MSG_UP_SN="Update Snort sids"

MSG_MY_PASS="Mysql password"
MSG_MY_IN="Write your mysql password (Default is vmossim)"
MSG_OSVDB_TI="Important"
MSG_OSVDB="Now the script will do the import,this may take up to 5-6 hours"
MSG_OSVDB_SU="OSVDB database updated successfully"
MSG_OSVDB_SU_TI="OSVDB"
MSG_ADD_SEN="Add a new sensor"
MSG_INPUT_HN="What is the sensor hostname?"
MSG_INPUT_IA="What is the sensor IP adress?"

#Menu reconfigure
MSG_RE_SER="Reconfigure Ossim Server"
MSG_RE_FRA="Reconfigure Ossim Framework"
MSG_RE_PHP="Reconfigure Phpgacl"
MSG_RE_SNO="Reconfigure Snort"
MSG_RE_ACI="Reconfigure AcidBase"
MSG_RE_NTO="Reconfigure Ntop"


MSG_UP_TI="VMOSSIM Other"
MSG_VERSION="version"
MSG_PROFILE="profile"

MSG_CH_MYSQL_PASS="Change mysql root password"
MSG_MY_CH_TI="Mysql password changed"
MSG_MY_CH="The mysql password has been changed"

MSG_ERR_RUN_TI="Not single-user-mode"
MSG_ERR_RUN="You must run in single-user-mode in order to clean Vmossim"

MSG_ERR_UP_RE_TI="Error updating"
MSG_ERR_UP_RE="Could not update repo info, try running apt-get update manually"

MSG_CLEAN="Clean Vmossim"
MSG_CLEANED="Vmossim has been cleaned"
MSG_VM_CLEAN_CONF="All logs, history and debian apt cache data will be deleted, Select Yes to continue"
MSG_VM_CLEAN_TI="Vmossim cleaned"
MSG_VM_CLEAN="Vmossim has been cleaned"

#profile confirmation request
MSG_CONF_PRO="Are you sure you want to change profile to: "
MSG_CONF_TI="Confirmation"

#wrong profile
MSG_ERR_PRO_TI="Wrong profile"
MSG_ERR_PRO="Can not use this function in current profile: "

#no internet connection
MSG_ERR_NO_INET_TI="No internet"
MSG_ERR_NO_INET="Can connect to internet, review your network config"

#error updating nessus plugins
MSG_ERR_UP_NE_TI="Error updating plugins"
MSG_ERR_UP_NE="There was an error while updating nessus plugins"

#successfull nessus plugins update
MSG_NE_SU_UP_TI="Plugins updated"
MSG_NE_SU_UP="Nessus plugins successfully updated"

#Error updating
MSG_ERR_UP_RE_TI="Error updating"
MSG_ERR_UP="There was an error updating the requested packages"

#Successfull update
MSG_SU_UP_TI="Update successfull"
MSG_SU_UP="Packages were updated successfully"

#error installing build-deps
MSG_ERR_BD_TI="Error installing"
MSG_ERR_BD="Error installing build dependencies"

#build deps installed
MSG_SU_BD_TI="Dependencies installed"
MSG_SU_BD="Build dependencies have been installed"

#sensor added
MSG_SEN_AD_TI="Sensor added"
MSG_SEN_AD="Sensor added succesfully to db"

MSG_SEN_ER_AD="Error including sensor in Database"  

MSG_ERR_DO_CVS_TI="Error downloading"
MSG_ERR_DO_CV="There was an error downloading data from cvs repository"

MSG_PCK_SU_TI="Packages created"
MSG_PCK_SU="Packages created in"

#Profile names
MSG_ALL_IN_ONE="All-in-one"
MSG_SENSOR="Sensor"
MSG_SERVER="Server"
SERVER_1="Server 1"
SERVER_2="Server 2"
SERVER_3="Server 3"

#Errors
ERR_NOT_ROOT="Sorry, this script must be run as root"
ERR_NOT_OPTION="Invalid option"

# Script to configure the network
MESSAGE0="No supported network cards found."
MESSAGE1="Please select network device"
MESSAGE2="Use DHCP broadcast ?"
MESSAGE3="Sending DHCP broadcast from device"
MESSAGE4="Failed."
MESSAGE5="Hit return to exit."
MESSAGE6="Please enter IP Address for "
MESSAGE7="Please enter Network Mask for "
MESSAGE8="Please enter Broadcast Address for "
MESSAGE9="Please enter Default Gateway"
MESSAGE10="Please enter Nameserver(s)"
MESSAGE11="Setting Nameserver in /etc/resolv.conf to"
MESSAGE12="Adding Nameserver to /etc/resolv.conf:"

#14-04

#Clean DB

MSG_CLEAN_DB_ME="Clean DB"
MSG_DB_CLEAN_CONF="Are you sure you want to clean your DB? This will delete all your data"
MSG_CLEAN_DB_TI="DB Cleaned"
MSG_CLEAN_DB="Your database has been cleaned"
MSG_ERR_CLE_DB_TI="Error cleaning DB"
MSG_ERR_CLE_DB="There was an error while trying to clean your database"
