

# initial recolector (by ip in ossim-db)

	# sensor
	
	for i in `echo "select ip from sensor;" | ossim-db | grep -v ip `; do echo "alienvault-center-collector --server_ip=$i --get" ; done
	
	# databases
	
	
	
	for i in `SELECT ip FROM `databases`;" | ossim-db | grep -v ip `; do echo "alienvault-center-collector --server_ip=$i --get" ; done

	# server
	
	for i in `SELECT ip FROM `server`;" | ossim-db | grep -v ip `; do echo "alienvault-center-collector --server_ip=$i --get" ; done
