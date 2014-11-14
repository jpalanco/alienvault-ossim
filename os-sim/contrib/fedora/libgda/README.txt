Purpose
-------

The current ossim-server version (0.9.9rc5) requires the exact libgda-1.2.3
and newer Red Hat versions (>FC5) ships with a newer version of this lib,
incompatible with ossim-server.

The libgda-ossim.spec allows to create a libgda-ossim-1.2.3.rpm package that
will install enterely at /usr/share/ossim/libgda-1.2.3. With it you can
compile the server with:

./configure --with-libgda=/usr/share/ossim/libgda-1.2.3

without the need of uninstalling or messing with your distro's libgda.

Instructions for the libgda-1.2.3 RPM building
-----------------------------------------------

(as root)
# cd /usr/src/redhat/SOURCES
# wget http://ftp.acc.umu.se/pub/GNOME/sources/libgda/1.2/libgda-1.2.3.tar.gz
(or any mirror from http://www.gnome-db.org/Download)
# tar xvfz libgda-1.2.3.tar.gz
# mv libgda-1.2.3 libgda-ossim-1.2.3
# tar cvfz libgda-ossim-1.2.3 libgda-ossim-1.2.3.tar.gz
# cd ../SPECS
# rpmbuild -bb libgda-ossim.spec
(dest rpm should be: /usr/src/redhat/RPMS/i386/libgda-ossim-1.2.3-1.i386.rpm)

