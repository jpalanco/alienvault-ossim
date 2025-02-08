## Installation On Virtual Box VM

Install debian 9 with minimal installation only with ssh-server and system utility
#### Prompts and their inputs while installing debian
```
        When installing debian when prompted for hostname enter alienvault
        When installing debian provide the following mirror value http://archive.debian.org/
```

After installation do the following steps:

### Update the package list
`apt-get update`

### Install SSH
`apt-get install ssh`

### Edit SSH configuration file
`nano /etc/ssh/sshd_config `
set "permit root login" as "Yes"

### Install Git
`apt-get -y install git`

### Clone the repository
`git clone https://github.com/nccs-neduet/STIP.git`

### Edit bashrc file
`nano ~/.bashrc`

#### At the bottom of the file add following lines
`export STIP_PATH=/root/STIP`

`source ~/.bashrc`

### Change interface name from ensp03 to eth0:

#### Edit grub configuration
`nano /etc/default/grub`
 
#### change the following

`GRUB_CMDLINE_LINUX="" to GRUB_CMDLINE_LINUX="net.ifnames=0 biosdevname=0"`

`grub-mkconfig -o /boot/grub/grub.cfg`

`reboot`

### After the reboot, configure the interface at /etc/network/interfaces
### The primary network interface
```auto eth0
iface eth0 inet static
   address xxx.xxx.xxx.xxx/xx [24,32, etc]
   netmask xxx.xxx.xxx.xxx
   network xxx.xxx.xxx.xxx
   broadcast xxx.xxx.xxx.xxx
   gateway xxx.xxx.xxx.xxx
   dns-nameservers xxx.xxx.xxx.xxx
   dns-search alienvault
   up ip link set $IFACE promisc on
   down ip link set $IFACE promisc off
```
`reboot`

### Add keys
`cd /root/STIP/ossim-repo-key`

`/usr/bin/apt-key add data_gpg.key`

`/usr/bin/apt-key add alienvault.asc`

### Edit sources list

`nano /etc/apt/sources.list`
### And add following
```
deb http://data.alienvault.com/alienvault58/alienvault/ binary/
deb http://data.alienvault.com/alienvault58/feed/ binary/
deb http://data.alienvault.com/alienvault58/plugins-feed/ binary/
deb http://data.alienvault.com/alienvault58/mirror/debian/ stretch main contrib
deb http://data.alienvault.com/alienvault58/mirror/debian-security/ stretch/updates main contrib
```
### Update package list
`apt-get update`

### Install specific Linux image
`apt-get install linux-image-4.19.0-0.deb9.24-amd64`

`reboot`

##### Need to install the following commands in order to run dpkg-buildpackages
`apt-get install -y build-essential devscripts debhelper`

### Install debi command
`apt-get install -y automake config-package-dev debhelper devscripts dh-autoreconf dh-virtualenv docbook-to-man dpatch gettext libbson-dev libcairo2-dev libffi-dev libgda-5.0-dev libglib2.0-dev libgnet-dev sudo`

```
cd /root/STIP/ossim-repo-key/
dpkg-buildpackage -uc -us
sudo debi
```
### Build and install packages:
```
apt-get install -y libbson-dev docbook-to-man libglib2.0-dev libgda-5.0-dev libgnet-dev zlib1g-dev libjson-glib-dev libmaxminddb-dev python-all-dev python-setuptools dpatch libssl-dev uuid-dev libpcre3-dev libsoup2.4-dev php7.0-mbstring curl

cd /root/STIP/alienvault-dummies-config
dpkg-buildpackage -uc -us
debi alienvault-openssl

cd /root/STIP/os-sim
apt-get install -y php-dev

dpkg-buildpackage -uc -us

apt-get install -y fontconfig libbit-vector-perl libcarp-clan-perl libcrypt-cbc-perl libcrypt-rijndael-perl libdate-calc-perl libdate-calc-xs-perl libdatrie1 libdbd-mysql-perl libdbi-perl libdbi1 libemail-date-format-perl libgraphite2-3 libharfbuzz0b libmime-lite-perl libmime-types-perl libpango-1.0-0 libpangocairo-1.0-0 libpangoft2-1.0-0 librrd8 librrds-perl libswitch-perl libthai-data libthai0 libtime-parsedate-perl python-ipy python-mysqldb python-pycurl sshpass

apt-get install libxml-parser-perl

debi ossim-utils

cd /root/STIP/alienvault-dummies-config
debi alienvault-crypto 

cd /root/STIP/alienvault-api/alienvault-api-core
apt-get install -y --allow-downgrades dh-virtualenv libglib2.0-dev librrd-dev libmariadbclient-dev libffi-dev libcairo2-dev libpango1.0-dev libmariadbclient18=10.1.48-0+deb9u2 sudo 

dpkg-buildpackage -uc -us -d

apt-get install -y pigz augeas-tools python-yaml

debi alienvault-api-core

cd /root/STIP/ossim-cd-tools
dpkg-buildpackage -uc -us
apt-get install -y libconfig-tiny-perl dialog libuuid-perl libperl6-slurp-perl libregexp-common-perl heartbeat libdatetime-format-flexible-perl resolvconf libipc-shareable-perl libfile-touch-perl ftp console-data python-dialog python-lockfile bwm-ng pwgen curl python-augeas libconfig-inifiles-perl usbmount libyaml-libyaml-perl libdigest-hmac-perl libsocket6-perl libio-socket-inet6-perl libnet-ip-perl libnet-dns-perl libnet-domain-tld-perl libemail-valid-perl dirmngr python-apt

### when prompted select "dont touch keymap"


debi ### When you run debi you will get error ossim_setup.conf not found. Ignore this and proceed

cd /root/STIP/ossim-menu-setup/
dpkg-buildpackage -uc -us
debi

cd /root/STIP/alienvault-dummies-config
debi alienvault-firewall

cd /root/STIP/alienvault-doctor/
dpkg-buildpackage -uc -us 
apt-get install -y lshw python-m2crypto python-psutil python-netaddr debsums hdparm
debi alienvault-doctor

cd /root/STIP/alienvault-dummies-config/
apt-get install monit
debi alienvault-monit
debi alienvault-logrotate

apt-get install ipcalc
debi alienvault-network ### When you run debi you will get error ossim_setup.conf not found. Ignore this and proceed
debi alienvault-pam
debi alienvault-snmpd  ### When you run debi you will get error ossim_setup.conf not found. Ignore this and proceed
debi alienvault-openssh
apt-get install -y easy-rsa
debi alienvault-vpn ### When you run debi you will get error ossim_setup.conf not found. Ignore this and proceed
debi alienvault-sysctl
debi alienvault-depmod
apt-get install -y postfix

### --enter default values

debi alienvault-postfix ### When you run debi you will get error ossim_setup.conf not found. Ignore this and proceed
apt-get install -y plymouth v86d  plymouth-themes
debi alienvault-plymouth

apt-get update

apt-get install acpi-support-base acpid apparmor aptitude aptitude-common bc bsd-mailx ethtool expect firmware-linux-free htop iotop iptraf iptraf-ng irqbalance joe libapparmor-perl libboost-filesystem1.62.0 libboost-iostreams1.62.0 libboost-system1.62.0 libbpf0 libcwidget3v5 libdrm-amdgpu1 libdrm-intel1 libdrm-nouveau2 libdrm-radeon1 libevent-2.0-5 libevent-core-2.0-5 libevent-pthreads-2.0-5 libfontenc1 libgl1-mesa-dri libgl1-mesa-glx libglapi-mesa libgpm2 libhiredis0.13 libhtp2 libhyperscan4 libjansson4 libllvm3.9 libluajit-5.1-2 libluajit-5.1-common liblwp-useragent-determined-perl libnet-irc-perl libnetfilter-log1 libnetfilter-queue1 libnuma1 libpcap0.8 libpciaccess0 libpkcs11-helper1 libsigc++-2.0-0v5 libstatgrab6 libtcl8.6 libtk8.6 libtxc-dxtn-s2tc libutempter0 libx11-xcb1 libxaw7 libxcb-dri2-0 libxcb-dri3-0 libxcb-glx0 libxcb-present0 libxcb-shape0 libxcb-sync1 libxcomposite1 libxdamage1 libxfixes3 libxi6 libxinerama1 libxmu6 libxpm4 libxrandr2 libxshmfence1 libxss1 libxt6 libxtst6 libxv1 libxxf86dga1 libxxf86vm1 linux-image-4.19-amd64 linux-image-4.19.0-0.deb9.24-amd64 locales-all locate lsof lynx lynx-common molly-guard netcat-traditional netdiag ntpdate openvpn python-statgrab python-support python3-simplejson screen smartmontools snmpd snort-rules-default suricata suricata-rules-default sysstat tcl-expect tcl8.6 tcpdump tcptrack tk8.6 traceroute vim vim-runtime x11-utils xbitmaps xterm zip

apt-get install -y unzip snmp freeipmi-common libfreeipmi16 ipmitool net-tools

apt-get install -y gnupg1 sysvinit-core
cd /root/STIP/alienvault-dummy-common
dpkg-buildpackage -uc -us

### Enter the values given below when debi is executed
debi

profiles:  Sensor,Server,Framework,Database
hostname:  alienvault
server_ip:  127.0.0.1
database_ip:  127.0.0.1
sensor_interfaces:  eth0
sensor_detectors:  ossec-single-line, pam_unix, prads, suricata, ssh, sudo
sensor_monitors:  nmap-monitor, ping-monitor, whois-monitor, wmi-monitor


cd /root/STIP/alienvault-dummies-config
debi alienvault-postfix 
cd /root/STIP/ossim-geoip/
dpkg-buildpackage -uc -us
debi

cd /root/STIP/alienvault-dummies-config
ossim-reconfig ### This command will break but will create ossim_setup.conf required by different packages

apt-get install -y libmariadb3 libreadline5 mariadb-client-10.4 mariadb-client-core-10.4

apt-get install -y mariadb-server-10.4

cd /root/STIP/os-sim/ossim-mysql/
dpkg-buildpackage -uc -us
debi


cd /root/STIP/alienvault-dummies-config
debi alienvault-mysql 
debi alienvault-firewall

### Check /etc/ossim/ossim_setup.conf, If database password is missing add the password from /etc/ossim/ossim_setup.conf_last. Unable to identify why the password of database is being removed from ossim_setup file.

/etc/init.d/prepare-db.sh start
apt-get install alienvault-plugin-sids


cd /root/STIP/alienvault-dummies-config
apt-get install redis-server
debi alienvault-redis-server-otx  
debi alienvault-firewall


apt-get install alienvault-crosscorrelation-free ###  /etc/init.d/ossim-server not found or not executable Ignore this and proceed

apt-get install ossim-taxonomy 

apt-get install alienvault-directives-free 

apt-get install uuid-runtime libgda-5.0-mysql
cd /root/STIP/os-sim
debi ossim-server

cd /root/STIP/alienvault-dummy-server/
dpkg-buildpackage -uc -us                
debi

cd /root/STIP/alienvault-dummy-database/
dpkg-buildpackage -uc -us 
apt-get install libjemalloc1 libterm-readkey-perl mytop
debi

cd /root/STIP/alienvault-dummies-config
debi alienvault-firewall

apt-get install python-adodb python-rrdtool python-sqlalchemy python-subnettree python-suds python-tz
cd /root/STIP/os-sim
debi ossim-framework-daemon

cd /root/STIP/alienvault-dummies-config/
apt-get install apache2 apache2-bin apache2-data apache2-utils libapr1 libaprutil1 libaprutil1-dbd-sqlite3 libaprutil1-ldap liblua5.2-0
debi alienvault-apache2

apt-get install -y postgresql postgresql-9.6 postgresql-client-9.6 postgresql-client-common postgresql-common postgresql-contrib postgresql-contrib-9.6 python3-asn1crypto python3-cffi-backend python3-cryptography python3-defusedxml python3-gvm  python3-idna python3-lxml python3-ospd python3-packaging python3-paramiko python3-psutil python3-pyasn1 python3-pyparsing python3-redis rng-tools sqlite3 xml-twig-tools

### --rng-tools.service
### --   Loaded: loaded (/etc/init.d/rng-tools; generated; vendor preset: --enabled)   Active: failed (Result: exit-code) since Mon 2023-10-02 --23:49:48 PDT; 9ms ago
### --     Docs: man:systemd-sysv-generator(8)
### --  Process: 12082 ExecStart=/etc/init.d/rng-tools start (code=exited, --status=1/FAILURE)


apt-get install -y doc-base gnutls-bin ospd-openvas gvm gvm-tools gvmd gvmd-common libgnutls-dane0 libgvm11 libical2 libopts25 libpq5 libradcli4 libssh-4 libssh-gcrypt-4 libunbound2 libxml-twig-perl libyaml-tiny-perl openvas-scanner 
  
  
apt-get install sqlite3 postgresql-contrib postgresql-contrib-9.6 rng-tools

debi alienvault-redis-server-gvm

debi alienvault-gvm

### After executing the above command following error occurs
### Step 1: Checking OpenVAS (Scanner)...
###         OK: OpenVAS Scanner is present in version 7.0.1.
###         ERROR: No CA certificate file for Server found.
###         FIX: Run 'sudo runuser -u _gvm -- gvm-manage-certs -a -f'.
### 
###  ERROR: Your GVM-11 installation is not yet complete!
###  Fix the above error code by running the suggested command
### Apply the above fix and run the command below
### 
### 

sudo runuser -u _gvm -- gvm-manage-certs -a -f
debi alienvault-gvm

### ### After executing the above command following error occurs
###  Step 1: Checking OpenVAS (Scanner)...
###         OK: OpenVAS Scanner is present in version 7.0.1.
###         OK: Server CA Certificate is present as /var/lib/gvm/CA/servercert.pem.
### Checking permissions of /var/lib/openvas/gnupg/*
###         OK: _gvm owns all files in /var/lib/openvas/gnupg
###         OK: redis-server is present.
###         OK: scanner (db_address setting) is configured properly using the redis-server socket: /var/run/redis-openvas/redis-server.sock
###         OK: redis-server is running and listening on socket: /var/run/redis-openvas/redis-server.sock.
###         OK: redis-server configuration is OK and redis-server is running.
###         OK: _gvm owns all files in /var/lib/openvas/plugins
###         ERROR: The NVT collection is very small.
###         FIX: Run the synchronization script greenbone-nvt-sync.
###         sudo runuser -u _gvm -- greenbone-nvt-sync.

sudo runuser -u _gvm -- greenbone-nvt-sync

### At this point applying the above fix doesn't resolve the issue. So moving forward to the next step


debi alienvault-clean-sessions

cd /root/STIP/alienvault-dummies-config/
debi alienvault-monit

apt-get install nfsen
debi alienvault-nfsen

cd /root/STIP/os-sim/alienvault-documentation
dpkg-buildpackage -uc -us
debi

cd /root/STIP/alienvault-dummies-config  
apt-get install memcached
debi alienvault-memcached

cd /root/STIP/alienvault-libs/alienvault-messages
dpkg-buildpackage -uc -us
debi 

apt-get install ossim-compliance

cd /root/STIP/alienvault-dummies-config/
debi alienvault-redis-server

apt-get install samba-common 
debi alienvault-samba

cd /root/STIP/ossim-downloads
dpkg-buildpackage -uc -us
debi

cd /root/STIP/alienvault-dummies-config/
debi alienvault-apache2

apt-get install nagios3 nagios-images monitoring-plugins monitoring-plugins-standard nagios-plugins nagios-plugins-contrib

debi alienvault-nagios

apt-get install libqdbm14 libzip4 php-zip php7.0 php7.0-dba php7.0-zip
debi alienvault-php7

cd /root/STIP/os-sim/
apt-get install fonts-arkpandora fonts-liberation libapache2-mod-auth-alienvault libapache2-mod-authnz-external libblas-common libblas3 libclass-methodmaker-perl libcrypt-blowfish-perl  libdate-manip-perl libgfortran3 liblinear3 liblua5.3-0 libmcrypt4 libnet-netmask-perl libphp-adodb libphp-jpgraph libproc-pid-file-perl librrdp-perl libtidy5 nmap php-db php-fpdf php7.0-curl php7.0-gd php7.0-mcrypt php7.0-mysql php7.0-tidy python-scapy rrdtool ttf-bitstream-vera

debi ossim-framework

### ----Recomended to run the commands in virtual box terminal------------

apt-get install alienvault-cpe
apt-get install  alienvault-gvm11-feed
apt-get --reinstall install alienvault-cpe

### ----------------------------------------------------------------------

cd ~/STIP/alienvault-api/alienvault-api
sudo apt-get install python-sphinx sphinx-common=1.4.9-2 libjs-sphinxdoc=1.4.9-2
dpkg-buildpackage -uc -us 
apt-get install erlang-asn1 erlang-base erlang-crypto erlang-eldap erlang-ftp erlang-inets erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key erlang-runtime-tools erlang-snmp erlang-ssl erlang-syntax-tools erlang-tftp erlang-tools erlang-xmerl libapache2-mod-wsgi rabbitmq-server

debi

cd /root/STIP/alienvault-dummies-config/


cd ~/STIP/alienvault-dummy-framework
dpkg-buildpackage -uc -us

apt-get install  bind9-host cabextract check-mk-livestatus dnsutils graphviz libann0 libapache-dbi-perl libapache2-mod-perl2 libarchive13 libavahi-client3 libavahi-common-data libavahi-common3 libbind9-140 libc-ares2 libcap2-bin libcdt5 libcgraph6 libcups2 libdevel-symdump-perl libdns162 libecap3 libemu2 libgd-tools libgeoip1 libgts-0.7-5 libgvc6 libgvpr2 libio-multiplex-perl libisc160 libisccc140 libisccfg140 libldb1 liblwres141 libmspack0 libnet-cidr-perl libnet-daemon-perl libnet-server-perl libnet-snmp-perl libnl-genl-3-200 libpathplan4 libsbc1 libsmbclient libsmi2ldbl libsnappy1v5 libspandsp2 libtalloc2 libtdb1 libtevent0 libwbclient0 libwhisker2-perl libwireshark-data libwireshark11 libwiretap8 libwscodecs2 libwsutil9 libxdot4 libxml-libxml-perl libxml-namespacesupport-perl libxml-sax-base-perl libxml-sax-perl libxml-simple-perl nfdump-sflow nsis nsis-common php7.0-memcache php7.0-ldap python-cffi-backend python-cryptography python-enum34 python-idna python-ipaddress python-paramiko python-pyasn1 python-talloc samba-libs smbclient squid squid-common squid-langpack tshark wireshark-common


debi ### Select default

cd /root/STIP/alienvault-dummies-config
debi alienvault-firewall
debi alienvault-memcached
debi alienvault-monit
apt-get install alienvault-plugins
apt-get --reinstall install alienvault-cpe

cd /root/STIP/alienvault-agent-generator
dpkg-buildpackage -uc -us
debi

cd /root/STIP/os-sim/agent/
apt-get install freetds-common libsybdb5 python-bson python-geoip2 python-ldap python-libpcap python-maxminddb python-nmap python-pyinotify python-pymongo python-pymssql  python-requests python-simplejson python-urllib3
dpkg-buildpackage -uc -us
  
debi

cd /root/STIP/alienvault-dummies-config/
apt-get install fprobe
debi alienvault-fprobe

apt-get install ossec-hids
apt-get install alienvault-ossec-rules

debi alienvault-rsyslog
debi alienvault-suricata
debi alienvault-firewall
cd /root/STIP/alienvault-rhythm
apt-get install libhiredis-dev
dpkg-buildpackage -uc -us
debi


apt-get install inotify-tools libinotifytools0
cd /root/STIP/alienvault-dummies-config
debi alienvault-ossec
debi alienvault-firewall
debi alienvault-mysql
debi alienvault-suricata
apt-get install prads
debi alienvault-prads
debi alienvault-mysql


cd /root/STIP/os-sim/agent/
debi ossim-agent

cd /root/STIP/alienvault-dummies-config/
debi alienvault-network

cd /root/STIP/alienvault-api/alienvault-api-core
debi alienvault-api-core

cd /root/STIP/alienvault-dummies-config/
debi alienvault-apache2

cd ~/STIP/alienvault-dummy-common
debi alienvault-dummy-common   

cd /root/STIP/os-sim
debi ossim-server

cd /root/STIP/alienvault-dummies-config/
debi alienvault-firewall

apt-get install mbw sysbench

cd /root/STIP/alienvault-dummies-config/
debi alienvault-system-benchmark

apt-get install mongodb  mongo-tools
apt-get install --reinstall php7.0-common
apt-get install --reinstall php7.0-xml
apt-get install --reinstall php-mbstring
apt-get install --reinstall php7.0-json
apt-get install --reinstall php-mbstring
apt-get install --reinstall php7.0-mbstring

sudo service apache2 restart


cd /root/STIP/alienvault-dummies-config/
debi alienvault-nfsen
/etc/init.d/nfsen restart

cd /root/STIP/ossim-cd-tools
debi
```

### Run ossim-reconfig at the end
`ossim-reconfig`
