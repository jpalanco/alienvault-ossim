
README.phpgacl
updated : 14-jul-2004
mailto  : ossim@ossim.net
web     : http://www.ossim.net


 PHPGACL Installation Summary

  Complete installation manual at
  http://phpgacl.sourceforge.net/demo/phpgacl/docs/manual.html

   - Download phpgacl
     (https://sourceforge.net/project/showfiles.php?group_id=57103)
   - Uncompress and place it into /var/www/phpgacl directory.
   - Edit phpgacl/gacl.class.php and set "db_type", "db_host", "db_user",
     "db_password", and "db_name" with the same values that the OSSIM database
     has (we are going to insert gacl tables into ossim database).
     Set "db_table_prefix" to ''
   - Edit phpgacl/admin/gacl_admin.inc.php with the same db settings.
   - Go to http://yourhost/phpgacl/setup.php
   - Create phpgacl/admin/templates_c directory (IMPORTANT: this
     directory must be writable by the user the webserver runs as).
   - phpgacl now is installed. Take a look at
     http://yourhost/phpgacl/admin/acl_admin.php


 OSSIM configuration

   - New table "user" with default user admin-admin. Password in md5 format.

     + from db/create_mysql.sql:
       INSERT INTO users (login, name, pass)
          VALUES ('admin', 'OSSIM admin', '21232f297a57a5a743894a0e4a801fc3');

   - Run http://yourhost/ossim/setup/ossim_acl.php script to fill database
     with default acls.

   - Force to use ossim pages instead of phpgacl pages with an apache auth
     directory.


 Problems:

  - We have noticed that phpGACL querys doesn't work in mysql3.
    If you have this problem, comment lines 365, 367 and 368 of
    gacl.class.php.


