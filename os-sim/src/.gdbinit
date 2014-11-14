set environment G_DEBUG=fatal_warnings
define ossim-debug
	shell rm /var/log/ossim/server.log
	run -D 6
end
set print elements 0 
define ossim-pcre
	shell rm /var/log/ossim/server.log
	run -D 6 -c /etc/ossim/server/config-new.xml
end
