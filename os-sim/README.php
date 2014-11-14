with the introduction of glpi/ovcp/ocs SSO support you will need to install the mcrypt module for php. 

on a debian box that would mean:

apt-get install php4-mcrypt

once it's installed restart apache. if you have setup your glpi site correctly you will be able to login automatically if
authed against the LDAP backend.

chuck
aka slydder

