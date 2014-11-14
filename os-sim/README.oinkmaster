Ossim + Oinkmaster FAQ (2004-06-04)
-----------------------------------

The oinkmaster modifications described here are intended to alleviate the need
to distribute different rulesets to different sites in an easy manner, doing
the mods from a central location.

First apply the contrib/oinkmaster-1.0.diff to the original oinkmaster
distribution.

This will create a couple of additional files at the oinkmaster source root:

README.ossim                - This file
oinkmaster.conf.sites       - Sample remote site config
oinkmaster.conf.master      - Sample master site config
oinkmaster.cron             - Sample cron file
httpd-oink.conf             - Sample apache include file
ossim-oink.conf             - Site authentication sample
contrib/update_rules.pl             - Master rule updating script.
contrib/create_sidmap_ossim.pl      - Update ossim's plugin_sid table.

oinkmaster.pl has been modified to add user auth & search for the
oinkmaster.conf file at /etc/ossim/oinkmaster/ first.

So, let's describe a sample master site setup (you can have multiple master
sites / master site chain)
--------------
Master
--------------

The idea is to have an easy to manage directory hierarchy from where you can
update rulesets in a simply manner for multiple remote sites/sensors.

1) Create sample directory structure. 

/var/www/htdocs/rules
/var/www/htdocs/rules/site
/var/www/htdocs/rules/site/sensor
/var/www/htdocs/rules/site/sensor/conf
/var/www/htdocs/rules/site/sensor/rules

2) Copy a sample snort-rules.tar.gz to the sensor dir and rename it to ossim-rules.tar.gz

3) Create site/sensor dirs for each site and it's sensors.

/var/www/htdocs/rules/nombre_cliente <-- Real directory for each site (e.g. site name)
/var/www/htdocs/rules/nombre_cliente/nombre_sensor <-- This can be a real
directory if you want unique rules for this sensor. If you have multiple
sensors using the same ruleset just do a symlink to a real sensor's directory.
/var/www/htdocs/rules/nombre_sensor/conf <-- Only needed for real sensors
/var/www/htdocs/rules/nombre_sensor/rules <-- Temporary ruleset

4) Add a new user entry for each site and update httpd.conf

The sample http-oink.conf has two entries. A generic one for sites that are
symlinks so they can authenticate and a specific for each site.

For each new site you have to:
a) Duplicate directory entries adjusting everything for the new site
b) htpasswd -c /var/www/.htaccess site_name

5) For each *REAL* directory we've created

Copy oinkmaster.conf.master to the conf directory for each sensor (rename it
to oinkmaster.conf) and update the sensors config from there.
Update root_dir within update_rules.pl (default /var/www/htdocs/rules) and
execute it. It should update all the sites rules with the rules from
snort.org. If it doesn't work look at the script, it's very simple.

----------------
Site
----------------

1) Copy oinkmaster.conf.site to /etc/ossim/oinkmaster/oinkmaster.conf
2) Copy ossim-oink.conf to the same dir
3) Adjust user/password settings within ossim-oink.conf
4) Copy modified oinkmaster.pl to /usr/local/bin and chmod +x
5) Make sure you can reach your master site via https/http
6) Test oinkmaster. If it works you can use the sample oinkmaster.cron

WARNING: the .cron restarts snort automatically which may cause you big
trouble. Read oinkmasters's own README.

