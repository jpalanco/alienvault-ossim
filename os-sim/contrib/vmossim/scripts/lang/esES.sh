# es_ES

SELECT_OPTION="Seleccione una opción"
MSG_SEL_OPT="Seleccione una de las siguientes opciones:"
SELECT_SYSTEM_TYPE="Seleccione uno de los siguientes perfiles para la imagen"

#Main menu options
MSG_MAIN="Menu Principal"
MSG_WELCOME="Bienvenido a la configuración de VMOSSIM"
MSG_CH_PRO="Cambiar el perfil de la imagen"
MSG_NET_CONF="Cambiar configuración de la red"
MSG_ABOUT="Acerca de este script"
MSG_OTHER="Otras tareas"
MSG_QUIT="Salir"
MSG_BACK="Volver atrás"
MSG_RECONFIG="Configurar"
MSG_EXIT="Salir"
MSG_UPDATE="Actualizar Vmossim"
MSG_NET_UP_TI="Configuración actualizada"
MSG_NET_UP="Su configuración de red ha cambiado, se aplicará la nueva configuración a las diferentes aplicaciones."
MSG_CONF_UP_TI="Configuración actualizada"
MSG_CONF_UP="Se aplicó la nueva configuración de red a todas las aplicaciones con éxito."
MSG_PRO_SEL="Seleccione uno de los siguientes perfiles para VMOSSIM."
MSG_UP_NE="Actualizar plugins de Nessus"
MSG_UP_OV="Actualizar bbdd OSVDB"
MSG_UP_DE="Actualizar todo el sistema"
MSG_UP_OS="Actualizar ossim"
MSG_DEVEL="Desarrollo"
MSG_BUILD_DPS="Instalar dependencias de compilación"
MSG_PCK_CVS="Crear paquetes Debian desde el CVS"
MSG_ERR="Error"
MSG_ERR_CR_PK="Error creando los paquetes de Debian"
MSG_ERR_DO_OS="Error actualizando la base de datos de OSVDB"

MSG_ERR_UP_SN="Error actualizando los sids en la BBDD"

MSG_SNO_TI="Sids actualizados"
MSG_SNO="Los sids Snort se actualizaron correctamente en la BBDD"
MSG_UP_SN="Actualizar sids de Snort"

MSG_MY_PASS="Contraseña de Mysql"
MSG_MY_IN="Escriba su contraseña de Mysql"
MSG_OSVDB_TI="Aviso importante"
MSG_OSVDB="El script importará los datos a la BBDD, esto puede durar hasta 6 horas"
MSG_OSVDB_SU="OSVDB actualizado con éxito"
MSG_OSVDB_SU_TI="OSVDB"
MSG_ADD_SEN="Añadir un sensor en BBDD"
MSG_INPUT_HN="¿Cuál es el nombre del host?"
MSG_INPUT_IA="¿Cúal es la dirección IP del sensor?"

#Menu reconfigure
MSG_RE_SER="Reconfigurar Ossim Server"
MSG_RE_FRA="Reconfigurar Ossim Framework"
MSG_RE_PHP="Reconfigurar Phpgacl"
MSG_RE_SNO="Reconfigurar Snort"
MSG_RE_ACI="Reconfigurar AcidBase"
MSG_RE_NTO="Reconfigurar Ntop"


MSG_UP_TI="Otrás tareas"
MSG_VERSION="versión"
MSG_PROFILE="perfil"

MSG_CH_MYSQL_PASS="Cambiar contraseña servidor Mysql"
MSG_MY_CH_TI="Mysql"
MSG_MY_CH="Se cambió la contraseña de mysql con éxito"

MSG_ERR_RUN_TI="Error single-user-mode"
MSG_ERR_RUN="Para hacer uso de esta funcionalidad debe arrancar en single-user-mode"

MSG_ERR_UP_RE_TI="Error actualizando"
MSG_ERR_UP_RE="Error al actualizar repositorios, pruebe a usar apt-get update manualmente"

MSG_CLEAN="Limpiar Vmossim"
MSG_CLEANED="Vmossim: Limpieza realizada con éxito"
MSG_VM_CLEAN_CONF="Se limpiaran los logs, historiales y el cache de dpkg. Seleccione Yes/Sí para continuar"
MSG_VM_CLEAN_TI="Vmossim"
MSG_VM_CLEAN="Vmossim: Limpieza realizada con éxito"

#profile confirmation request
MSG_CONF_PRO="¿Está seguro de querer utilizar el siguente perfil?: "
MSG_CONF_TI="Confirmación"

#wrong profile
MSG_ERR_PRO_TI="Perfil erroneo"
MSG_ERR_PRO="No se puede usar esta funcionalidad en el siguiente perfil: "

#no internet connection
MSG_ERR_NO_INET_TI="No hay conexión"
MSG_ERR_NO_INET="No se pudo utilizar la conexión a internet, revise su configuración"

#error updating nessus plugins
MSG_ERR_UP_NE_TI="Error actualizando plugins"
MSG_ERR_UP_NE="Error! No se pudieron actualizar los plugins"

#successfull nessus plugins update
MSG_NE_SU_UP_TI="Plugins actualizados"
MSG_NE_SU_UP="Plugins de Nessus actualizados con éxito"

#Error updating
MSG_ERR_UP_RE_TI="Error actualizando"
MSG_ERR_UP="Ocurrió un error a la hora de actualizar el sistema"

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
MSG_SEN_AD_TI="Sensor"
MSG_SEN_AD="Sensor añadido a la base de datos"

MSG_SEN_ER_AD="Error al añadir el sensor en la base de datos"  

MSG_ERR_DO_CVS_TI="Error en la descarga"
MSG_ERR_DO_CV="Error! No se pudo descargar la información del repositorio CVS"

MSG_PCK_SU_TI="Paquetes creados"
MSG_PCK_SU="Paquetes creados en"

#Profile names
MSG_ALL_IN_ONE="All-in-one"
MSG_SENSOR="Sensor"
MSG_SERVER="Servidor"

#Errors
ERR_NOT_ROOT="Error, este script debe ser utilizado como usuario root"
ERR_NOT_OPTION="Opción inválida"

# Script to configure the network
MESSAGE0="No se encontraron tarjetas de red."
MESSAGE1="Por favor seleccione un dispositivo de red"
MESSAGE4="Error!."
MESSAGE5="Pulse intro para salir."
MESSAGE6="Introduzca la dirección IP "
MESSAGE7="Introduzca la máscara de red "
MESSAGE8="Introduzca la dirección de broadcast "
MESSAGE9="Introduzca la puerta de enlace"
MESSAGE10="Introduzca los DNS (separados por espacios)"
MESSAGE11="Estableciendo DNS en /etc/resolv.conf a"
MESSAGE12="Añadiendo información DNS a /etc/resolv.conf:"
