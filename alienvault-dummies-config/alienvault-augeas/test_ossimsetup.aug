module Test_ossimsetup =

let conf ="admin_dns=8.8.8.8
admin_gateway=192.168.230.1
admin_ip=192.168.230.110
admin_netmask=255.255.255.0
domain=alienvault
email_notify=system@alienvault.com
hostname=alienvault-temporal
interface=eth0
mailserver_relay=no
mailserver_relay_passwd=unconfigured
mailserver_relay_port=25
mailserver_relay_user=unconfigured
ntp_server=no
profile=Server,Sensor,Framework,Database

[database]
db_ip=127.0.0.1
pass=fAAB8o4KhZ
user=root

[firewall]
active=yes

[framework]
framework_https_cert=default
framework_https_key=default
framework_ip=192.168.230.110

[ha]
ha_autofailback=no
ha_deadtime=10
ha_device=eth0
ha_heartbeat_comm=bcast
ha_heartbeat_start=no
ha_keepalive=3
ha_local_node_ip=192.168.230.110
ha_log=no
ha_other_node_ip=unconfigured
ha_other_node_name=unconfigured
ha_password=unconfigured
ha_ping_node=default
ha_role=master
ha_virtual_ip=unconfigured

[sensor]
asec=no
detectors=apache, ossec-single-line, pam_unix, prads, ssh, sudo, suricata, cisco-asa, postfix
ids_rules_flow_control=yes
interfaces=eth0
ip=
monitors=nmap-monitor, ntop-monitor, ossim-monitor
mservers=no
name=alienvault
netflow=yes
netflow_remote_collector_port=555
networks=192.168.0.0/16,172.16.0.0/12,10.0.0.0/8
sensor_ctx=
tzone=Europe/Madrid

[server]
alienvault_ip_reputation=enabled
idm_mssp=no
rservers=no
server_ip=127.0.0.1
server_plugins=osiris, pam_unix, ssh, snare, sudo
server_pro=yes

[snmp]
community=public
snmp_comunity=public
snmpd=no
snmptrap=no

[update]
update_proxy=disabled
update_proxy_dns=my.proxy.com
update_proxy_pass=disabled
update_proxy_port=disabled
update_proxy_user=disabled

[vpn]
vpn_infraestructure=yes
vpn_net=10.67.68
vpn_netmask=255.255.255.0
vpn_port=33800
"

test OssimSetup.lns get conf =
{ "general"
  { "admin_dns" = "8.8.8.8" }
  { "admin_gateway" = "192.168.230.1" }
  { "admin_ip" = "192.168.230.110" }
  { "admin_netmask" = "255.255.255.0" }
  { "domain" = "alienvault" }
  { "email_notify" = "system@alienvault.com" }
  { "hostname" = "alienvault-temporal" }
  { "interface" = "eth0" }
  { "mailserver_relay" = "no" }
  { "mailserver_relay_passwd" = "unconfigured" }
  { "mailserver_relay_port" = "25" }
  { "mailserver_relay_user" = "unconfigured" }
  { "ntp_server" = "no" }
  { "profile" = "Server,Sensor,Framework,Database" }
}
{ "database"
  { "db_ip" = "127.0.0.1" }
  { "pass" = "fAAB8o4KhZ" }
  { "user" = "root" }
}
{ "firewall"
  { "active" = "yes" }
}
{ "framework"
  { "framework_https_cert" = "default" }
  { "framework_https_key" = "default" }
  { "framework_ip" = "192.168.230.110" }
}
{ "ha"
  { "ha_autofailback" = "no" }
  { "ha_deadtime" = "10" }
  { "ha_device" = "eth0" }
  { "ha_heartbeat_comm" = "bcast" }
  { "ha_heartbeat_start" = "no" }
  { "ha_keepalive" = "3" }
  { "ha_local_node_ip" = "192.168.230.110" }
  { "ha_log" = "no" }
  { "ha_other_node_ip" = "unconfigured" }
  { "ha_other_node_name" = "unconfigured" }
  { "ha_password" = "unconfigured" }
  { "ha_ping_node" = "default" }
  { "ha_role" = "master" }
  { "ha_virtual_ip" = "unconfigured" }
}
{ "sensor"
  { "asec" = "no" }
  { "detectors" = "apache, ossec-single-line, pam_unix, prads, ssh, sudo, suricata, cisco-asa, postfix" }
  { "ids_rules_flow_control" = "yes" }
  { "interfaces" = "eth0" }
  { "ip" = ""}
  { "monitors" = "nmap-monitor, ntop-monitor, ossim-monitor" }
  { "mservers" = "no" }
  { "name" = "alienvault" }
  { "netflow" = "yes" }
  { "netflow_remote_collector_port" = "555" }
  { "networks" = "192.168.0.0/16,172.16.0.0/12,10.0.0.0/8" }
  { "sensor_ctx" = "" }
  { "tzone" = "Europe/Madrid" }
}
{ "server"
  { "alienvault_ip_reputation" = "enabled" }
  { "idm_mssp" = "no" }
  { "rservers" = "no" }
  { "server_ip" = "127.0.0.1" }
  { "server_plugins" = "osiris, pam_unix, ssh, snare, sudo" }
  { "server_pro" = "yes" }
}
{ "snmp"
  { "community" = "public" }
  { "snmp_comunity" = "public" }
  { "snmpd" = "no" }
  { "snmptrap" = "no" }
}
{ "update"
  { "update_proxy" = "disabled" }
  { "update_proxy_dns" = "my.proxy.com" }
  { "update_proxy_pass" = "disabled" }
  { "update_proxy_port" = "disabled" }
  { "update_proxy_user" = "disabled" }
}
{ "vpn"
  { "vpn_infraestructure" = "yes" }
  { "vpn_net" = "10.67.68" }
  { "vpn_netmask" = "255.255.255.0" }
  { "vpn_port" = "33800" }
}
