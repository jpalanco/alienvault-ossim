Due to the nature of the new ossim_http_request function I was forced to add rewrite support to enable seamless remote site integration into OSSIM.

The following modules require rewrites in the the site configuration file (ie. /etc/apache2/sites-available/default):

oocs	(OCS NG support)
ovcp	(OpenVCP support)
oglpi	(GLPI support)

The required entries are:

                RewriteEngine On
                RewriteRule ^vsadmin/openvcp(.*)$ ossim/ovcp/index.php?rpath=$1
                RewriteRule ^glpi/(.*)$ ossim/oglpi/index.php?rpath=$1
                #RewriteRule ^ocs(.*)$ ossim/oocs/index.php?rpath=$1

Be advised that the OCS NG module is VERY limited and I recomment using GLPI, instead of OCS NG Reports, as I developed for SSO support (hence the move to GLPI here) with LDAP backend authentication.
