
  INTRODUCTION
  ------------

The following document describes the Agent Control API.

When sending requests to the control framework, the string is to take the form
of:

control action="request" id="who" parameter1="value1" parameter2="value2"

In this case the "request" value is the action that is being requested
and subsequent parameter/value pairs are provided as required. Non required
parameters should be silently ignored.

The "id" value indicates the id of agent, as specified in its config file, that
this request is being sent to. Ommission of this value will imply "all", which
results in a broadcast to all connected agents.


The message return format will have at least the following form.

control request id="who" errno="X" error="YYY" transaction="ZZZZZ" ...

Where the "requestedaction" is the initial action requested indicating it is
association with the original request. The "errno" value is the error return
code from the action. Zero "0" indicates success and a non-zero indicates a
failure. The "error" value provides a string literal of the actual error
message if appropriate. The "transaction_id" is used for internal message
management within the framework and can be safely ignored.

The final message in the response will be terminated by the "ackend" value and
indicates it is the last message and the client can stop polling for returns.
All other messages that form part of the response are terminated by the "ack"
indicating that messages are still forthcoming.



  GENERAL COMMANDS
  ----------------

Command: command_set

Description: List the commands supported by the Agent Control. Return is likely
to be a multi-message return.

Parameters: Nil.

Returns:
  command     - the name of the command.
  description - short description of the command.

Example:
control action="command_set"


Command: os_info

Description: List basic OS information. On Linux machines majority of
information is sourced from the kernel description string provided by
"uname -a".

Parameters: Nil.

Returns:
  system   - Type of system (eg. Linux)
  hostname - Host name of the system (eg. aegis)
  release  - Kernel version (eg. 2.6.31-20-generic)
  version  - Extra description (eg. #57-Ubuntu SMP Mon Feb 8 09:02:26 UTC 2010)

Example: 
control action="os_info"



  CONFIGURATION FILE COMMANDS
  ---------------------------

Command: config_file_backup

Description: Backup an existing configuration file (*.cfg).

Parameters:
  path - The full path to the configuration file of interest

Returns:
  backup_path - full path of the new backup file
  timestamp   - timestamp extension applied to the backup file

Example:
control action="config_file_backup"


config_file_backup_list

Description: Lists all backups of for a specified configuration file (*.cfg)

Parameters:
  path - The full path to the configuration file of interest.

Returns:
  count     - The number of timestamps available.
  timestamp - available timestamp for restore.

Example:
control action="config_file_backup_list"


config_file_backup_restore

Description: Restore a previous backup for the specified configuration file
(*.cfg). If the timestamp parameter is ommited the most recent back up is
assumed. If the type parameter is ommited "overwrite_pop" is assumed.

Parameters:
  path      - The full path to the configuration file of interest.
  timestamp - The timestamp of the backup file to restore.
  type      - "overwrite_clear" will restore the file and delete the backup.
              "overwrite_only" will restore the backup and leave the backup in
              place.

Returns:
Nil.

Example:
control action="config_file_backup_list"


config_file_get

Description: Get the contents of an existing configuration file (*.cfg). Files
that contain multiple lines will be sent via individual control messsages. The
last message will contain the "ackend" terminator.

Parameters:
  path - The full path to the configuration file being read.

Returns: 
  length - length of the plain text contained within "line".
  line   - hexified gzip compressed representation of the line contents.

Example:
control action="config_file_get" path="/etc/ossim/agent/plugins/wmi-monitor.cfg"

> control config_file_get transaction="15943" id="aegis" length="7" line="789cb3b65628cfcde4020007a501ee" ack
> control config_file_get transaction="15943" id="aegis" length="17" line="789cb3b65628a92c48b552c8cdcfcb2cc92fe20200322105c5" ack
> control config_file_get transaction="15943" id="aegis" length="19" line="789cb3b65628c8294dcfcc8bcf4cb15230323034e202003ced057b" ack

---8< snip >8 ---

> control config_file_get transaction="15943" id="aegis" length="6" line="789c2bce4cb135e10200077e01bc" ack
> control config_file_get transaction="15943" id="aegis" length="16" line="789c2b4a4d4fad28b055d28849d1d4d352e202002e4f0480" ack
> control config_file_get transaction="15943" id="aegis" length="12" line="789c2b4a2d2ecd29b1ad5631ace502001ede0434" ack
> control config_file_get transaction="15943" id="aegis" errno="0" error="Success." ackend


config_file_set

Description: Get the contents of an existing configuration file (*.cfg).

Parameters:
  path   - The full path to the configuration file being written.
  type   - write  - write a line to the file buffer.
           commit - commit the entire file buffer to the file.
           reset  - reset/clear the entire file buffer.
  length - length of the plain text contained within "line".
  line   - hexified gzip compressed representation of the line contents.

Returns: Nil.

Example:
control action="config_file_set" type
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="7" line="789cb3b65628cfcde4020007a501ee"
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="17" line="789cb3b65628a92c48b552c8cdcfcb2cc92fe20200322105c5" ack
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="19" line="789cb3b65628c8294dcfcc8bcf4cb15230323034e202003ced057b" ack

---8< snip >8 ---

control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="6" line="789c2bce4cb135e10200077e01bc" ack
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="16" line="789c2b4a4d4fad28b055d28849d1d4d352e202002e4f0480" ack
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="write" length="12" line="789c2b4a2d2ecd29b1ad5631ace502001ede0434" ack
control action="config_file_set" path="/etc/ossim/agent/plugins/wmi-monitor.cfg" type="commit"



  NMAP SCANNING COMMANDS
  ----------------------

Command: nmap_scan

Description: Perform an Nmap scan on a target IP address or IP address range in
CIDR notation.

Only one scan can be active on a single agent at any time. Should a scan be
requested during an active scan then a error message is returned as well as the
status of the active scan.

Parameters:
  target - The target IP address or IP address range (CIDR notation) to be 
           scanned.
  type   - The type of scan to be performed. Current supported scan types
           include "ping" (or "0") and "root" (or "1")

Returns:
Nil.

Example:
control action="nmap_scan" type="ping" target="192.168.1.0/24"

> control nmap_scan transaction="61547" id="aegis" errno="0" error="Success." ackend



Command: nmap_status

Description: Get the status of the Nmap working thread state.

Parameters:
Nil.

Returns:
  status - Percentage of current job completion. Idle is represented by a status
           value of zero ("0"), a failure is represented by ("-1") and an active
           state is represented by a positive value between 1 and 99.

Example:
control action="nmap_status"

> control nmap_scan transaction="2331" id="aegis" status="0" errno="0" error="Success." ackend


Command: nmap_reset

Description: Reset the error state from the last failure incurred.

Parameters:
Nil.

Returns:
Nil.

Example:
control action="nmap_reset"


Command: nmap_report_list

Description: Lists all reports generated by this agent's specified report path.

Parameters:
  target - The target IP address or network range to be scanned.
  type   - The type of scan to be performed. Current supported scan types
           include "ping" (or "0") and "root" (or "1")

Returns:
Nil.

Example:
control action="nmap_scan" type="ping" target="192.168.1.0/24"

> control nmap_scan transaction="61547" id="aegis" errno="0" error="Success." ackend


Command: nmap_report_get

Description: Get a formatted response from a previously generated report.

Parameters:
  path - Path/filename of generated report. A list of valid report paths can be
         found using nmap_report_list.

Returns:
  ip          - The IP of active host
  mac         - MAC address of active host
  os          - Best guess of the active host's OS
  os_accuracy - Accuracy of the active host's OS
  port        - Open port on the active host
  vendor      - Vendor of network interface on active host
  count       - The number of active host messages sent in this request.

Example:
control action="nmap_report_get" path="nmap_ping_scan.20100321200800"

> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.1" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.11" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.12" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.13" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.50" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.90" ack
> control nmap_report_get transaction="17184" id="aegis" ip="192.168.1.91" ack
> control nmap_report_get transaction="17184" id="aegis" count="7" ackend


control action="nmap_report_get" path="nmap_root_scan.20100327154000"

> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.1" mac="00:24:B2:E4:25:65" vendor="Netgear" port="23|tcp" port="53|tcp" port="80|tcp" port="5000|tcp" os="Linux 2.6.15 - 2.6.23 (embedded)" os_accuracy="100" ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.11" mac="00:15:AF:83:41:DE" vendor="AzureWave Technologies" ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.12" port="22|tcp" port="80|tcp" ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.13" mac="00:1C:26:29:4C:B3" vendor="Hon Hai Precision Ind. Co." ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.50" mac="00:0D:3A:33:35:E4" vendor="Microsoft" port="21|tcp" os="Microsoft Xbox game console (modified, running XboxMediaCenter)" os_accuracy="100" ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.90" mac="00:18:4D:A5:EE:4B" vendor="Netgear" port="22|tcp" port="80|tcp" port="139|tcp" port="445|tcp" port="3306|tcp" port="9418|tcp" ack
> control nmap_report_get transaction="15335" id="aegis" ip="192.168.1.91" mac="00:20:00:58:CB:79" vendor="Lexmark International" port="21|tcp" port="80|tcp" port="631|tcp" port="8000|tcp" port="9100|tcp" port="10000|tcp" port="50000|tcp" os="Lexmark X4530 or 4800 wireless printer" os_accuracy="100" ack


Command: nmap_report_raw_get

Description: Get the raw XML response from a previously generated report. 
Similar to config_file_get, the will be sent line by line via individual control
messsages. The last message will contain the "ackend" terminator.

Parameters:
  path - Path/filename of generated report. A list of valid report paths can be
         found using nmap_report_list.

Returns:
  length - length of the plain text contained within "line".
  line   - hexified gzip compressed representation of the line contents.

Example:
control action="nmap_report_raw_get" path="nmap_ping_scan.20100321200800"


Command: nmap_report_delete

Description: Delete a previously generated report.

Parameters:
  path - Path/filename of generated report. A list of valid report paths can be
         found using nmap_report_list. All reports can be deleted sing the
         wildcard ("*").

Returns:
Nil.

Example:
control action="nmap_report_delete" path="nmap_ping_scan.20100321200800"
