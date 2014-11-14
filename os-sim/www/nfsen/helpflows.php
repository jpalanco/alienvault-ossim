<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/


require_once ('av_init.php');
Session::logcheck("environment-menu", "MonitorsNetflows");

$helpflows['Activating NetFlow Globally on your Existing Routers and Switches'] = array(
"I have an Adtran NetVanta Router"
	=>"The Adtran NetVanta Router supports NetFlow v9. For more information, please refer to the Adtran Web Site.

	View a PDF walkthrough of Setting up NetFlow on the NetVanta3130 - v17.02.02.AE

	More information can be found on page 27 of the Adtran Configuration Guide. ",
"I have a Cisco ASA Firewall"
	=>"Take a look at the blog from Systrax for information on how to enable NetFlow on your Cisco ASA gear.",
"I have a Cisco Router (Cisco IOS)"
	=>"Enable Cisco Express Forwarding:

	router(config)# ip cef

	In the configuration terminal on the router, issue the following to start NetFlow Export.

	It is necessary to enable NetFlow on all interfaces through which traffic you are interested in will flow. Now, verify that the router is generating flow stats - try 'show ip cache flow'. Note that for routers with distributed switching (GSR's, 75XX's) the Rendezvous Point CLI will only show flows that made it up to the RP. To see flows on the individual linecards use the 'attach' or 'if-con' command and issue the 'show ip cache flow' on each LC.

	Enable export of these flows with the global commands. 'ip flow-export source' can be set to any interface, but one which is the least likely to enter a 'down' state is preferable. Netflow will not be exported if the specified source is down. For this reason, we suggest the Loopback interface, or a stable Ethernet interface:

	router(config)# ip flow-export version 5
	router(config)# ip flow-export destination <ip-address> <port>
	router(config)# ip flow-export source FastEthernet0

	Use the IP address of your NetFlow Collector and configured listening port.

	If your router uses BGP protocol, you can configure AS to be included in exports with command:

	router(config)# ip flow-export version 5 [peer-as | origin-as]

	The following commands break up flows into shorter segments.

	router(config)# ip flow-cache timeout active 1
	router(config)# ip flow-cache timeout inactive 15

	Use the commands below to enable NetFlow on each physical interface (i.e. not VLANs and Tunnels, as they are auto included) you are interested in collecting a flow from. This will normally be an Ethernet or WAN interface. You may also need to set the speed of the interface in kilobits per second. It is especially important to set the speed for frame relay or ATM virtual circuits.

	interface <interface>
	ip route-cache flow
	bandwidth

	Now write your configuration with the 'write' or 'copy run start' commands. When in enabled mode, you can see current NetFlow configuration and state with the following commands:

	router# show ip flow export
	router# show ip cache flow
	router# show ip cache verbose flow
	",
"I have a 4000 series Catalyst running in Hybrid or Native Mode"
	=>"Configure the switch the same as an IOS device, but instead of the command

	ip route-cache flow

	...use the command

	ip route-cache flow infer-fields

	This series requires a Supervisor Engine IV with a NetFlow Services daughter card to support NDE.
	",
"I have a non-4000 series Catalyst switch"
	=>"Are you running CatOS?
	- Yes

	Router side:

	Enter the following global commands.

	Ip flow-export source <int-name>
	Ip flow-export version 5
	Ip flow-export destination <ip-address> <port>
	Ip flow-cache timeout active 1

	Enter the following command on each physical interface. You will need to log into each interface one at a time.

	Ip route-cache flow

	Switch side:

	Set mls nde <ip-address> <port>
	Set mls nde version 5
	Set mls flow full
	Set mls agingtime long 128
	Set mls agingtime 64
	Set mls bridged-flow-statistics enable
	Set mls nde enable
	- No

	Enter the following global commands (all commands are entered in the router <enable> config t option).

	Ip flow-export source <int-name>
	Ip flow-export version 5
	Ip flow-export destination <ip-address> <port>
	Ip flow-cache timeout active 1
	Mls nde sender version 5
	Mls flow ip interface-full
	Mls nde interface
	Mls aging long 64
	Mls aging normal 64

	Enter the following command on each physical interface. You will need to log into each interface one at a time.

	Ip route-cache flow
	",
"I have a Cisco 4605 series with a daughter card configured with VLANs"
	=>"Bandwidth needs to be set explicitly at the VLAN:

	ip route-cache flow infer-fields
	ip flow ingress infer-fields
	",
"I have a Cisco Catalyst 4500 Series Switch"
	=>"Please refer to the NetFlow Configuration Examples on Catalyst 4500 Series.",
"I have a Cisco Catalyst 6500/6000 Series Switch"
	=>"Review Cisco's Catalyst 6500/6000 Switches NetFlow Configuration and Troubleshooting guide or the Configuring NetFlow on the MSFC guide.",
"I have a Cisco 7600 router"
	=>"1. If you plan to export NetFlow statistics, globally enable NDE on the router by issuing the following commands:
      configure terminal
      ip flow-export destination
      ip flow-export version
      mls nde sender version
   2. Enable NetFlow on individual interfaces by issuing the following commands:
      configure terminal
      interface
      ip flow ingress
   3. (Optional) To configure NetFlow sampling, do the following:
         1. Enable sampled NetFlow globally on the router (mls sampling).
         2. Enable sampled NetFlow on individual interfaces (mls netflow sampling).
   4. Verify the NDE configuration to ensure that it does not conflict with other features such as QoS or multicast. Use the show ip interface command to verify the configuration.

	These and other related commands can be found in the Cisco 7600 Series Cisco IOS Software Configuration Guide.
	",
"I have an Enterasys Router"
	=>"The following commands are used to configure Netflow Export on Enterasys routers:

	netflow set interval 1
	netflow set ports all-ports
	netflow set collector <ip-address>
	netflow enable

	For more information, please refer to our 'How to Enable NetFlow on an Enterasys SSR' guide or the Enterasys documentation and support at http://www.enterasys.com.
	",
"I have an ESX Server running VMware"
	=>"Review the Enabling NetFlow on Virtual Switches technical note.",
"I have an Extreme Networks Router"
	=>"To enable the flow statistics feature on a switch, use the following command:

	enable flowstats

	The flow statistics feature is disabled by default.

	To disable the flow statistics feature on a switch, use the following command:

	disable flowstats

	To enable the flow statistics function on the specified port, use the following command:

	enable flowstats ports <portlist>

	The flow statistics function is disabled by default.

	To disable the flow statistics function on the specified port, use the following command:

	disable flowstats ports <portlist>

	A single port can distribute statistics across multiple groups of flow-collector devices. This NetFlow distribution capability makes it possible to create a collection architecture that scales to accommodate high volumes of exported data. It also offers a health-checking function that improves the reliability of the collection architecture by ensuring that only responsive flow-collector devices are included in active export distribution lists. The distribution algorithm also ensures that all the ingress flow records for a given flow are exported to the same collector.

	NetFlow distribution is enabled by configuring export distribution groups that identify the addresses of multiple flow-collector devices. You can configure up to 32 export distribution groups on a BlackDiamond 6800 series switch, and each group can contain as many as eight flow-collector devices.

	To configure the export groups and flow-collector devices to which NetFlow datagrams are exported, use the following command:

	config flowstats export <group#> [add | delete] [<ipaddress> | <hostname>] port <udp_port>

	The group# parameter is an integer in the range from 1 through 32 that identifies the specific group for which the destination is being configured.

	You can use the add and delete keywords to add or delete flow-collector destinations.

	To export NetFlow datagrams to a group, you must configure at least one flow-collector destination. By default, no flow-collector destinations are configured. To configure a flow-collector destination, use either an IP address and UDP port number pair or a hostname and UDP port number pair to identify the flow-collector device to which NetFlow export datagrams are to be transmitted. You can configure up to eight flow-collector destinations for each group. When multiple flow-collectors are configured as members of the same group, the exported NetFlow datagrams are distributed across the available destinations.

	To configure the IP address that is to be used as the source IP address for NetFlow datagrams to be exported, use the following command:

	config flowstats source <ipaddress>

	By default, flow records are exported with the VLAN interface address that has a route to the configured flow-collector device. Depending on how it is configured, a flow-collector device can use the source IP address of received NetFlow datagrams to identify the switch that sent the information.

	The following command example specifies that the IP address 192.168.100.1 is to be used as the source IP address for exported NetFlow datagrams.

	config flowstats source 192.168.100.1

	Flow records are exported on an age basis. If the age of the flow record is greater than the configured time-out, the record is exported.

	To configure the time-out value for flow records on the specified port, use the following command:

	config flowstats timeout <minutes> ports [<portlist> | any]

	The time-out value is the number of minutes to use in deciding when to export flow records. The default time-out is 5 minutes.

	The following command example specifies a 10-minute time-out for exported NetFlow datagrams on port 1 of the Ethernet module installed in slot 8 of the BlackDiamond switch.

	config flowstats timeout 10 ports 8:1

	To reset the flow statistics configuration parameters for a specified Ethernet port to their default values, use the following command:

	unconfig flowstats ports <portlist>

	To display status information for the flow statistics function, use the following command:

	show flowstats {detail | group <group#> | ports <portlist>}

	where:
	detail 	Use this optional keyword to display detailed NetFlow configuration information.
	group# 	Use this optional parameter with the group keyword to display status information for a specific export group.
	portlist 	Use this optional parameter to specify one or more ports or slots and ports for which status information is to be displayed.

	If you enter the show flowstats command with none of the optional keywords or parameters, the command displays a summary of status information for all ports.

	The summary status display for a port shows the values for all flow statistics configuration parameters for the port.

	The summary status display for an export group includes the following information:

		* Values for all configuration parameters
		* Status of each export destination device

	The detailed status display for an export group includes the summary information, plus the following management information:

		* Counts of the number of times each flow collector destination has been taken out of service due to health-check (ping check) failures
		* The source IP address configuration information

	For more information, please refer to Extreme Networks documentation and support at http://www.extremenetworks.com
	",
"I have a Juniper Router"
	=>"Juniper supports flow exports by sampling packet headers with the routing engine and aggregating them into flows. Packet sampling is acheived by defining a firewall filter to accept and sample all traffic, applying that rule to an interface, and then configuring the sampling forwarding option.

	interfaces {
	ge-0/1/0 {
	unit 0 {
	   family inet {
		  filter {
			 input all;
			 output all;
		  }
		  address <network>/<mask>  (<- This is in binary notation)
	   }
	}
	}
	}
			
	firewall {
	filter all {
	term all {
	   then {
		  sample;
		  accept;
	   }
	}
	}
	}
			
	forwarding-options {
	sampling {
	input {
	   family inet {
		  rate 100;
	   }
	}
	output {
	   cflowd  {
		  port <port>;
		  version <version_number>;
	   }
	}
	}
	}
					

	For more information on configuring Juniper routers, refer to: http://www.juniper.net.
	",
"I have a Mikrotik Router"
	=>"Below are examples of how to enable Traffic-Flow on a router.

	   1. Enable Traffic-Flow on the router:

		  [admin@MikroTik] ip traffic-flow> set enabled=yes
		  [admin@MikroTik] ip traffic-flow> print
						enabled: yes
					 interfaces: all
				  cache-entries: 1k
			active-flow-timeout: 30m
			inactive-flow-timeout: 15s
		  [admin@MikroTik] ip traffic-flow>
						

	   2. Specify IP address and port of the host, which will receive Traffic-Flow packets:

		  [admin@MikroTik] ip traffic-flow target> 
		  add address=192.168.0.2:2055 \
		  \... version=9
		  [admin@MikroTik] ip traffic-flow target> print
		  Flags: X - disabled
		   #   ADDRESS               VERSION
		   0   192.168.0.2:2055      9
		  [admin@MikroTik] ip traffic-flow target>
						

	",
"I have a Riverbed Steelhead Appliance"
	=>"(config)# ip flow-export destination <ip-addr> interface <int #>
	(config)# ip flow-export enable",
"I have Vyatta Core 6 software"
	=>"Configuration

	system {
	  accounting {
		interface <ifname> {        # multi-value
		  sampling-rate <u32>       # sample 1 in N packets, default
		}
		syslog-facility facility
		netflow {
		  version <1|5|9>           # default 5
		  engine-id <u32>           # 0-255
		  server <ipv4> {           # multi-value
			port <u32>              #
		  }
		  timeout {
			expiry-interval <u32>   # default 60
			flow-generic <u32>      # default 3600
			icmp <u32>              # default 300
			max-active-life <u32>   # default 604800
			tcp-fin <u32>           # default 300
			tcp-generic <u32>       # default 3600
			tcp-rst <u32>           # default 120
			udp <u32>               # default 300
		  }
		}
		sflow {
		  agentid <u32>
		  server <ipv4> {           # multi-value
			port <u32>              # default 6343
		  }
	  }
	}
				

	More commands (e.g. show accounting...) start on page 5 of the Vyatta documentation.
	"
);
$helpflows['Activating sFlow Globally on your Existing Switches'] = array(
"I have an Alcatel Switch"
	=>"Enter your Scrutinizer server information:

	sflow receiver 1 name address udp-port packet-size 1400 version 5 timeout 0

	Receiver Name can be set to any one-word string you want (e.g. Scrutinizer). Port should be set to 2055 by default. Packet-size should be set to 1400, version should be 5, and timeout should be 0.

	Next, configure a sampler on all desired interfaces:

	sflow sampler 1 receiver 1 rate 1 sample-hdr-size 128

	So, if you wanted to configure ports 18 and 35 to sample for a switch with a single blade and 48 ports, you would enter:

	sflow sampler 1 1/18 receiver 1 rate 1 sample-hdr-size 128
	sflow sampler 1 1/35 receiver 1 rate 1 sample-hdr-size 128

	Finally, configure one poller to get sFlow counters:

	sflow poller 1 receiver 1 interval 5

	Write configuration to switch:

	write memory
	",
"I have a D-Link DGS-3627 or DGS-3650 switch"
	=>"For information on enabling sFlow on supported D-Link switches, please review the sFlow section of the DGS-36XX User Manual V2.00, as well as our D-Link sFlow Configuration Guide.",
"I have an Enterasys B3/C3/G3 series switch"
	=>"sFlow is only supported on Enterasys B3/C3/G3 series switches running firmware 6.3.1 or above. For information on enabling sFlow on these supported Enterasys® switches, please review the sFlow section of the Enterasys® SecureStack™ Configuration Guide, beginning on page 28-4.",
"I have an ExtremeXOS Switch"
	=>"View the PDF Guide, which references the commands to configure sFlow on Extreme Switches.

	For more Extreme commands, view the ExtremeWare Command Reference Guide.
	",
"I have a Force10 Switch or Router"
	=>"The following commands configure a Force10 switch/router with IP address 1.1.2.2 to sample at 1-in-512 and send the sFlow packets to Scrutinizer with IP address 1.1.1.1 over UDP port 6343:
	Force10(conf)#sflow collector 1.1.1.1 agent-addr 1.1.2.2
	Force10(conf)#sflow sample-rate 512
	Force10(conf)#sflow enable

	sFlow must then be enabled on every interface that should be sampled:
	Force10(conf-if-gi-0/0)#sflow enable

	To list the configuration settings use the command:
	Force10#show sflow

	sFlow services are enabled
	Global default sampling rate: 512
	Global default counter polling interval: 20
	Global extended information enabled: none
	1 collectors configured
	Collector IP addr: 1.1.1.1, Agent IP addr: 1.1.2.2, UDP port: 6343
	20088 UDP packets exported
	0 UDP packets dropped
	3940 sFlow samples collected
	0 sFlow samples dropped due to sub-sampling
	Linecard 0 Port set 0 H/W sampling rate 512
	Gi 0/0: configured rate 512, actual rate 512, sub-sampling rate 1",
"I have a Foundry Switch"
	=>"There are only 3 commands to enable sFlow on Foundry gear.

	   1. Enable it globally
			 1. (config)# sflow enable
	   2. Configure a destination
			 1. (config)# sflow destination x.x.x.x
	   3. Enable it on port(s)
			 1. (config)# interface eth 1 (or for multiple ports (config)# interface eth 1 to 48)
			 2. (config-if-1)# sflow forwarding

	For more Foundry commands, view the Foundry Command Reference Guide.
	",
"I have an H3C S5500-E1 or S7500-E Series Switch"
	=>"View the PDF Guide, which references the commands to configure sFlow on H3C supported equipment.",
"I have an HP Procurve Switch 2800 or 5300 series"
	=>"IMPORTANT:
	2800 Series must be running Software Revision I.08.105 and Firmware (ROM) version I.08.07
	5300 Series must be running Software Revision E.10.37 or higher

	For information on enabling sFlow on 2800 or 5300 series HP Procurve Switches, download this ZIP file and review the PDF inside for further instructions.
	",
"I have an HP Procurve Switch 5400, 3500, 2600 or 8200 series - running K code"
	=>"HP has added support for configuring sFlow directly on the CLI.

	From config mode:

	   1. Configure destination collector
			  * sflow <1-3> destination
				   1. where 1-3 is the sFlow instance, IP-addr is the address of the Scrutinizer collector, and udp-port-for-sflow is the number of the listening port of the collector.
				   2. example: sflow 1 destination 192.168.1.1 6343
	   2. Activate Sampling
			  * sflow <1-3> sampling N
				   1. where 1-3 is the sFlow instance, ports list is the port(s) setup for sFlow, and N is the number of sampled packets (to sample every 100 packets set N to 100).
				   2. example: sflow 1 sampling all 100
	   3. Activate Polling
			  * sflow <1-3> polling N
				   1. where 1-3 is the sFlow instance, ports list is the port(s) setup for sFlow, and N is the number of interval (in seconds) between polling intervals.
				   2. example: sflow 1 polling all 60
	   4. Save Configuration
			  * write mem

	",
"I have an HP Procurve Switch 5400zl, 3500yl and 6200yl"
	=>"For information on enabling sFlow on supported HP Procurves, view the ProCurve Networking FAQ.",
"I have a Juniper Switch or Router"
	=>"For instructions on how to enable sFlow on supported Juniper routers and switches, please review Configuring sFlow Technology for Network Monitoring (CLI Procedure).",
"I have a Juniper EX3200 switch"
	=>"The following configuration enables sFlow monitoring of all interfaces on a Juniper EX3200 switch, sampling packets at 1-in-500, polling counters every 30 seconds and sending the sFlow to an analyzer (10.0.0.50) on UDP port 6343 (the default sFlow port).

	protocols { 
	  sflow {   
		polling-interval 30;   
		sample-rate 500;   
		collector 10.0.0.50 {     
		  udp-port 6343;   
		}   
		interfaces ge-0/0/0.0;   
		interfaces ge-0/0/1.0;   
		interfaces ge-0/0/2.0;   
		interfaces ge-0/0/3.0;   
		interfaces ge-0/0/4.0;   
		interfaces ge-0/0/5.0;   
		interfaces ge-0/0/6.0;   
		interfaces ge-0/0/7.0;   
		interfaces ge-0/0/8.0;   
		interfaces ge-0/0/9.0;   
		interfaces ge-0/0/10.0;   
		interfaces ge-0/0/11.0;   
		interfaces ge-0/0/12.0;   
		interfaces ge-0/0/13.0;   
		interfaces ge-0/0/14.0;   
		interfaces ge-0/0/15.0;   
		interfaces ge-0/0/16.0;   
		interfaces ge-0/0/17.0;   
		interfaces ge-0/0/18.0;   
		interfaces ge-0/0/19.0;   
		interfaces ge-0/0/20.0;   
		interfaces ge-0/0/21.0;   
		interfaces ge-0/0/22.0;   
		interfaces ge-0/0/23.0;
	  }
	}
					

	Visit blog.sFlow.com for more information on configuring sFlow on Juniper switches.
	"
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php
echo gettext("OSSIM Framework"); ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
<script type="text/javascript" src="../js/jquery.min.js"></script>
</head>
<body>
<table width="100%" class="transparent">
<?$i = 1; foreach ($helpflows as $helptype=>$arr1) { ?>
	<tr><th style="padding:5px"><?=_($helptype)?></th></tr>
	<tr>
		<td class="nobborder">
			<table width="100%">
			<? foreach ($arr1 as $title=>$content) { $color = ($i%2==0) ? "#FFFFFF" : "#EEEEEE"; ?>
				<tr><td class="left nobborder" style="padding:3px;background-color:<?=$color?>"><a href="javascript:;" onclick="$('#<?=$i?>').toggle()"><b><?=_($title)?></b></a></td></tr>
				<tr><td class="left nobborder" style="display:none" id="<?=$i?>"><pre><?=_(str_replace("\t","",$content))?></pre></td></tr>
			<? $i++; } ?>
			</table>
		</td>
	</tr>
<? } ?>
</table>
</body>
</html>
