Alienvault Agent Installer Generator v2.2.3
-------------------------------------------

The generator currently generates unattended exe installation files for:

- OCS
- OSSEC

Tested Windows Systems
----------------------
- Windows XP Pro SP3 (32 bit)
- Windows 2003 Enterprise (64bit)
- Windows 7 starter


Usage
-----

This generator should be installed under /usr/share/ossim/agent-generator/. This is what we'll call $AAIG.

export $AAIG="/usr/share/ossim/agent-generator/"

The main file is $AAIG/gen_ossim_agents.pl. You have to run this file for each IP address you want to generate an agent for, and it will leave a self-installing exe under $AAIG/alienvault_agents_IP.exe. Important note: you have to run the code from withing $AAIG.

cd $AAIG;
perl gen_ossim_agents.pl 192.168.1.40
ls -la alienvault_agents_192.168.1.40.exe

Additionally, if you want only the standalone OCS or standalone OSSEC installer, they'll be placed under $AAIG/ocs_installer.exe and $AAIG/ossec_installer.exe, for the latest generated IP.

Copy the file over to your windows system and you're ready to go.

Massive Deployment from within OSSIM Server
-------------------------------------------
Required instructions for Windows XP: 
1) Open any folder
2) Open Tools (5th menu)
3) Folder Options... (4th option)
4) View (2nd tab)
5) In Advanced settings, scroll down to the last option
6) Disable "Use simple file sharing"
7) OK
(Windows XP remote management is disabled by default, this is why this procedure has to be done)

Tool: gen_ossim_agents.pl
-------------------------
Usage:
perl gen_ossim_agents.pl target_ip

Will generate a unified OCS and OSSEC installer with the current host as target server and valid credentials for both.
Installer will be under alienvault_(agents_target_ip).exe
Separate unattended installers for OCS and OSSEC will be stored each time under ocs_installer.exe and ossec_installer.exe respectively.


Tool: deploy.pl
---------------
Usage:
perl deploy.pl target_ip username password
(deploy.pl will generate installation packages for target_ip if not already present)
Please make sure 'winexe' is in your path (part of the wmi-client package)

Notes: 
- ' (single quotation mark) is not allowed for either username or password
- Use of ":" or "\" characters is discouraged at both username or password. ":" will break the massive deployment functionality, "\" will confuse the underlaying command into thinking the part before it is a domain.

Tool: mass_deploy.pl
--------------------
Usage:
perl mass_deploy.pl target_ip_file username password
(mass_deploy.pl will generate installation packages for target_ips if not already present)
Please make sure 'winexe' is in your path (part of the wmi-client package)
target_ip_file expects one ip per line, followed by optional username and password information. Domain information optional.
Sample lines:
192.168.1.40
192.168.1.41
192.168.1.69:wmi:wmi
192.168.1.70:Domain\User:password

Notes: 
- ' (single quotation mark) is not allowed for either username or password
- Use of ":" or "\" characters is discouraged at both username or password. ":" will break the massive deployment functionality, "\" will confuse the underlaying command into thinking the part before it is a domain.

Tips and tricks
---------------
If you got a subnet you want to deploy and you don't want to type in all the addresses, use nmap:
- nmap -sL -n 192.168.1.0/24 | grep "Host" | grep "not scanned" | cut -f 2 -d " "
Nmap allows for other fancy host definitions (get the .1 and .254 hosts for 10 subnets:
- nmap -sL -n 192.168.1-10.1,254 | grep "Host" | grep "not scanned" | cut -f 2 -d " "


TODO
----
- Make all of this platform independent
- Enable agentless installation for OSSEC
- Accept network ranges as input
