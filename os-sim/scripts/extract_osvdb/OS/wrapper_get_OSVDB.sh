#/bin/bash
# here you need to insert the mysql password. 'temporal' i.e. :)

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%windows%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 1

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%linux%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 2

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%cisco%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 3

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%BSD%" and object_base.base_name not like "OpenBSD" and object_base.base_name not like "FreeBSD" and object_base.base_name not like "NetBSD"' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 4

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "FreeBSD" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 5

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "NetBSD" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 6

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "OpenBSD" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 7

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%HP-UX%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 8

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%Solaris%" order by osvdb_id' > os.txt

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%sun%" order by osvdb_id' >> os.txt

cat os.txt |sort|uniq > os_aux.txt

./get_OS_OSVDB.pl f.txt os_aux.txt 9

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%mac%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 10

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%plan9%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 11

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%SCO%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 12

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%AIX%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 13

mysql osvdb -ptemporal -e ' SELECT DISTINCT object.osvdb_id FROM object, object_base, object_correlation WHERE object_base.base_id = object_correlation.base_id AND object.corr_id = object_correlation.corr_id AND object_base.base_name like "%UNIX%" order by osvdb_id' > os.txt

./get_OS_OSVDB.pl f.txt os.txt 14




