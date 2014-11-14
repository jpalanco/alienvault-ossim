Ossim + Nessus FAQ (2004-06-09) for 0.9.5
-----------------------------------------

Since many things have changed regarding ossim-nessus integration I'll rewrite
this (nearly) from scratch.

Of course this document assumes you've got running Nessus daemon(s) and a
running ossim server with it's db structure.

There are two ways to run nessus with ossim:

- Distributed
This mode will try to scan each host from it's associated sensor. This means
you'll have to setup a nessusd on each sensor. This is the fastest way to scan
a large number of hosts since the scans will run in paralell mode.

- Non-distributed
All the scans are run from a central Nessus daemon.

Note: The clients are no longer required to run manually first in order to pass the
paranoia check (I didn't know about nessus -x).

First, you'll have to let ossim know where to locate nessus. By default edit
/etc/ossim/framework/ossim.conf and update the following lines:

nessus_user=user
nessus_pass=password
nessus_host=localhost
nessus_port=1241
nessus_path=/usr/local/bin/nessus
nessus_rpt_path=/var/www/ossim/vulnmeter/
nessus_distributed=1

This last line is important. 1 means you have setup multiple Nessus daemons. 0
means you've got a central nessusd. At this time you'll have to reuse
port/user & pass if you want to use distributed scanning.

The first thing you should do is update your nessus plugins
(nessus-update-plugins).

Next, update the ossim db using update_nessus_ids.pl. This will let ossim gain
knowledge about the latest installed plugins. And adjust priorities for each
vuln.

Next, check the hosts/networks you want to scan using the web UI.

And, one last step: run do_nessus.pl. This will fetch the targets, launch the
nessus clients, collect the results and:
- Update vulnmeter entries.
- Update reports.
- Update correlation tables.

In order to enable local security checks:

Use SSH to perform local security checks[entry]:SSH user name : = root
Use SSH to perform local security checks[file]:SSH public key to use : = /Users/dk/.ssh/id_dsa.pub
Use SSH to perform local security checks[file]:SSH private key to use : = /Users/dk/.ssh/id_dsa
Use SSH to perform local security checks[password]:Passphrase for SSH key : = your_password
