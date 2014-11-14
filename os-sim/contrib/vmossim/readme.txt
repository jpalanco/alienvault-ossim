
This virtual appliance contains a ready to use ossim deployment. For detailed information about OSSIM please refer to http://www.ossim.net.

Ossim stands for Open Source Security Information Management. It's goal is to provide a comprehensive compilation of tools which, when working together, grant a network/security administrator with detailed view over each and every aspect of his networks/hosts/physical access devices/server/etc...
Besides getting the best out of well known open source tools, some of which are quickly described below these lines, ossim provides a strong correlation engine, detailed low, mid and high level visualization interfaces as well as reporting and incident managing tools, working on a set of defined assets such as hosts, networks, groups and services.
All this information can be limited by network or sensor in order to provide just the needed information to specific users allowing for a fine grained multi-user security environment.
Also, the ability to act as an IPS (Intrusion Prevention System) based on correlated information from virtually any source result in a useful addition to any security professional.

Included with the applianace are the following software components:
- Arpwatch, used for mac anomaly detection.
- P0f, used for passive OS detection and os change analisys.
- Pads, used for service anomaly detection.
- Nessus, used for vulnerability assessment and for cross correlation (IDS vs Security Scanner).
- Snort, the IDS, also used for cross correlation with nessus.
- Spade, the statistical packet anomaly detection engine. Used to gain knowledge about attacks without signature.
- Tcptrack, used for session data information which can grant useful information for attack correlation.
- Ntop, which builds an impressive network information database from which we can get aberrant behaviour anomaly detection.
- Nagios. Being fed from the host asset database it monitors host and service availability information.
- Osiris, a great HIDS.

To this we add a bunch of self developed tools, the most important being a generic correlation engine with logical directive support. (More on http://www.ossim.net/docs.php). Some code has been added to the appliance in order to make use of the added benefits of virtualization technology.

Usually a typical ossim deployment consists of:
- A database host.
- A server which hosts the correlation, qualification and risk assesment engine.
- X agent hosts which do information collection tasks from a number of devices. For a list of plugins please refer to: http://www.ossim.net/dokuwiki/doku.php?id=roadmap:plugins
- A control daemon which does some maintenance work and ties some parts together. It's called frameworkd.
- The frontend is web based, unifying all the gathered information and providing the ability to control each of the components.

The appliance has an easy to use wizard which helps both in selecting the type of Appliance as well as the needed IP address information.
You can choose between three different deployment types:
- All in one (the default type).
- Sensor only.
- Server + Database + Frontend.

------------------------------------------------------------------------
Customization instructions.
------------------------------------------------------------------------

First of all: this appliance requires promiscuous mode NIC on the host system. Please refer to the links at the following address in order to learn how to enable promiscuous mode on your virtual server:
http://www.vmware.com/searchgl/search?q=promiscuous&btnG=VMware+Search&restrict=&site=VMware_Site&output=xml_no_dtd&client=VMware_Site&num=10&proxystylesheet=VMware_Site

Of course you must make sure your guest operating system also puts it's NIC into promiscuous mode.

In order to customize the Vmossim image you can run the management script in /root/vmossim.sh with ./vmossim.sh

First you have to decide what this image is going to be. For a start I'd suggest leaving it as it is (all in one) and only customizing it's ip address. To do so follow a couple of simple steps:

1. Start up the Virtual Appliance
2. Setup your keyboard layout using loadkeys XX (Where XX is your country code, Example: loadkeys es)
3. Setup your networking, run vmossim.sh in /root/vmossim.sh
4. Point your browser at http://your_address/ossim/. Default login is "admin:admin" and upon login further
   instructions are being shown.
5. Enjoy!

In case you want to add more appliances on other parts of your network, you should split the server up and reconfigure the sensors as, well, sensors.

To do so you can use the option "Change profile" in the Vmossim management script.

Sensor
------
- Disable server, mysql and apache.
- Reconfigure /etc/issue and /etc/issue.net so you can see what is configured at any time.
- Setup the right server & database values.

Server
------
- Disable pads, p0f, ntop, etc...
- Reconfigure /etc/issue and /etc/issue.net so you can see what is configured at any time.
- Grant mysql privileges to remote sensors.

------------------------------------------------------------------------
Maintenance and Management of Vmossim
------------------------------------------------------------------------
All the scripts have been included in a simple to use utility named vmossim,
which can be found at /root/vmossim/vmossim.sh.

Using this utility you will be able to:

- Change image profile
- Change network configuration and update network config in all programs
- Update Nessus plugins
- Update snorts sids in DB
- Update all system
- Update only OSSIM packages
- Update OSVDB database
- Clean database 
- Clean Vmossim (logs, debian apt cache, history)
- Change mysql root password
- Add new sensor info to database
- Reconfigure important packages
- Install ossim build dependencies
- Download latest cvs and create debian packages

------------------------------------------------------------------------
Passwords and parameters.
------------------------------------------------------------------------
Default password for root is vmossim
Default user:password for the web interface is admin:admin
Default user:password for Nagios interface is nagiosadmin:vmossim
Non privileged user:password is vmuser:vmossim
Default Mysql root password is vmossim
Default Nessus user is root:vmossim


------------------------------------------------------------------------
Problems and suggestions
------------------------------------------------------------------------

Please use mailing list and forums at  http://www.ossim.net 
