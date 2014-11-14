'''

Generate SIDs for SiteProtector plugin

Download and expand the zip file from Download http://www.iss.net/security_center/reference/vuln/
into a new directory named "files": /usr/share/ossim/scripts/files/

Copy the SiteProtector map to the new directory:
cp /usr/share/ossim-agent/ossim_agent/SiteProtectorMap.py /usr/share/ossim/scripts

next, run this script with the SQL or MAP argument
python ./generateSiteProtectorMap.py SQL

'''

from SiteProtectorMap2 import *
import os
import sys
import re

sqlHeader = """-- SiteProtector

-- plugin_id: 1611

--

DELETE FROM plugin WHERE id = "1611";
DELETE FROM plugin_sid where plugin_id = "1611";
"""

TMP_TRANS = {}
TMP_TRANS.update(ISS_SITEPROTECTOR_SID_TRANSLATION_MAP)
#print len(TMP_TRANS)
def existName(name):
	try:
		ISS_SITEPROTECTOR_SID_TRANSLATION_MAP[name]
		return True
	except:
		return False

def generateMap():
	print "ISS_SITEPROTECTOR_SID_TRANSLATION_MAP = {"
	for sig in TMP_TRANS.keys():
		print "\t'%s': '%s'," % (sig, TMP_TRANS[sig])
	print "\t}"	

def generateSQL():
	print sqlHeader
	for sig in TMP_TRANS.keys():
		print 'REPLACE INTO plugin_sid (plugin_id, sid, priority, category_id, class_id, name) VALUES (1611, %s, 3, NULL, NULL, "Siteprotector: %s");' % (TMP_TRANS[sig], sig)

op = sys.argv[1]
if op != "MAP" and op != "SQL":
	print "Usage:\n\tgenerateMap (MAP|SQL)"
	sys.exit(0)

reg = re.compile("(?P<name>(.*)).htm[l]*$")
ids = os.listdir("files")
cnt = 0
for i in ids:
	m = reg.match(i)
	if m:
		data = m.group(1)
		if not existName(data):
			TMP_TRANS[data] = str(cnt)
			#print "%s,%d" % (data,cnt)
		cnt = cnt + 1
if op == "MAP":
	generateMap()	
elif op == "SQL":
	generateSQL()
