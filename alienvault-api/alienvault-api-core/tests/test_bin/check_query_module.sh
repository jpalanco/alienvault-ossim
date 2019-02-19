#!/bin/bash
TEST_MODULE_BIN=./test-module
$TEST_MODULE_BIN -m av_query_ossim_db.py -a "host=192.168.230.5 \
								   port=3306 \
								   passwd=GqgDLUTN46 \
								   user=root \
								   database=alienvault \
								   query=\"select * from sensor;	\"  "
								  