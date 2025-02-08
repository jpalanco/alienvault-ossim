#!/usr/bin/python

activate_this = '/usr/share/python/alienvault-api-core/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

from apimethods.otx.pulse import OTXv2
pulse_id_list=["6202a9bc1182eff53c3eea00"]
otx = OTXv2(key="6808cdb072d20204ce2f216ef2e0b1db100bd18ff9f02f2365b8faa1dd06760b")
total = otx.add_pulses_from_list(pulse_id_list)


################################################################################################
#
# alienvault-rhythm Stress Test
# Emulates suricata input by appending records to eve.json and check accordance in  matches.log
#
################################################################################################

import sys, time, subprocess, json, datetime

eve_json = "/var/log/suricata/eve.json"
matches_log = "/var/log/alienvault/rhythm/matches.log"
#Pulse: https://otx.alienvault.com/pulse/6202a9bc1182eff53c3eea00
# 2.56.59.42
# threesmallhills.com
# sha1:   cfdf141404d8783f2c6181d185b3e1ad107e4169
# sh256:  14e7cc2eadc7c9bac1930f37e25303212c8974674b21ed052a483727836a5e43
# md5:    88319e075ee9d7092a11a1e0237ee16c

file_text_ip           = '{"timestamp":"2022-01-11T11:11:11.111111","event_type":"dns","src_ip":"2.56.59.42","src_port":15892,"dest_ip":"1.1.1.1","dest_port":53,"proto":"UDP","dns":{"type":"query","id":55772,"rrname":"ns2.saudi.net.sa","rrtype":"A"}}'

file_text_domain_http  = '{"timestamp":"2022-02-22T22:22:22.222222","event_type":"http","src_ip":"1.1.1.1","src_port":41167,"dest_ip":"1.1.1.1","dest_port":80,"proto":"TCP","http":{"hostname":"threesmallhills.com","url":"\/","http_user_agent":"Wget\/1.16 (linux-gnu)","http_content_type":"text\/html"}}'
file_text_domain_dns   = '{"timestamp":"2022-02-22T22:22:22.222222","event_type":"dns","src_ip":"1.1.1.1","src_port":53,"dest_ip":"1.1.1.1","dest_port":15893,"proto":"UDP","dns":{"type":"answer","id":55772,"rrname":"threesmallhills.com","rrtype":"NS","ttl":3600}}'
# "dns"/"rrname" IF "answer"
#OTX hostname DB is empty :(
file_text_hostname     = '{"timestamp":"2022-03-33T33:33:33.333333","event_type":"dns","src_ip":"1.1.1.1","src_port":53,"dest_ip":"1.1.1.1","dest_port":15894,"proto":"UDP","dns":{"type":"answer","id":55772,"rrname":"saudi.net.sa","rrtype":"NS","ttl":3600}}'
file_text_fileinfo     = '{"timestamp":"2022-02-08T05:16:30.297479-0500","flow_id":1563233928257170,"in_iface":"eth0","event_type":"fileinfo","src_ip":"216.239.38.120","src_port":80,"dest_ip":"10.80.50.80","dest_port":42898,"proto":"TCP","http":{"hostname":"www.google.com","url":"/","http_user_agent":"Mozilla/5.0 (X11; Linux x86_64; rv:24.0) Gecko/20140429 Firefox/24.0 Iceweasel/24.5.0","http_content_type":"text/html","http_method":"GET","protocol":"HTTP/1.1","status":302,"redirect":"https://www.google.com/?gws_rd=ssl","length":231},"app_proto":"http","fileinfo":{"filename":"/","sid":[],"gaps":false,"state":"CLOSED","md5":"a","sha1":"b","sha256":"c","stored":false,"size":231,"tx_id":0}}'

delay = 5 # seconds
log_rotate_period = 10

#-----------------------------------------------------------------------------------------------

def writeToEveJson( count, otx_type, matches ) :
  try:
    eve_file = open(eve_json, "a")
  except IOError:
    print ("There was an error writing to", eve_json)
    sys.exit()

  n = 0
  otx_count = 0
  while( n < count ) :
    dt = str( datetime.datetime.now().isoformat() )
    if( otx_type == "ip" ) :
      json_data = json.loads( file_text_ip )
      if n % 2 == 0 :
        json_data["src_ip"] = "2.2.2.2"
      else :
        matches.append( [ "ip", dt ] )
        otx_count += 1
    elif( otx_type == "domain" ) :
      if n % 2 == 0 :
        json_data = json.loads( file_text_domain_http )
        matches.append( [ "domain_http", dt ] )
        otx_count += 1
      elif( n % 3 == 0 ) :
        json_data = json.loads( file_text_domain_dns )
        json_data["dns"]["type"] = "query"
      else :
        json_data = json.loads( file_text_domain_dns )
        matches.append( [ "domain_dns", dt ] )
        otx_count += 1
    elif( otx_type == "hostname" ) :
      json_data = json.loads( file_text_hostname )
    elif( otx_type == "fileinfo" ) :
      json_data = json.loads( file_text_fileinfo )

      if( n % 4 == 0 ) :
        json_data["fileinfo"]["md5"] = "88319e075ee9d7092a11a1e0237ee16c"
        matches.append( [ "fileinfo", dt ] )
        otx_count += 1
      elif( n % 4 == 1 ) :
        json_data["fileinfo"]["sha1"] = "cfdf141404d8783f2c6181d185b3e1ad107e4169"
        matches.append( [ "fileinfo", dt ] )
        otx_count += 1
      elif( n % 4 == 2 ) :
        json_data["fileinfo"]["sha256"] = "14e7cc2eadc7c9bac1930f37e25303212c8974674b21ed052a483727836a5e43"
        matches.append( [ "fileinfo", dt ] )
        otx_count += 1


    json_data["timestamp"] = dt
    eve_str = json.dumps( json_data )

    eve_file.write( eve_str + "\n" )
    n += 1

  eve_file.close()
  return otx_count

#-----------------------------------------------------------------------------------------------

def CheckMatchesLog( count, checkDelay ) :

  otx_count = 0
  matches = []
  otx_count = writeToEveJson( count, "ip", matches )
  otx_count += writeToEveJson( count, "domain", matches )
  otx_count += writeToEveJson( count, "hostname", matches )
  otx_count += writeToEveJson( count, "fileinfo", matches )


  print json.dumps(matches, indent=4);

  print( str( count ) + ". Added " + str( count * 3 ) + "/" + str( otx_count ) + "(Total/OTX) records. Waiting " + str( checkDelay ) + " secs..." )
  time.sleep( checkDelay )

# Running logrotate right after writing eve.json = fresh eve.json records are lost, logrotate failes with: "error: error renaming temp state file /var/lib/logrotate/status.tmp"
#  if count % log_rotate_period == 0 :
#    print( "--- log rotated +1" )
#    subprocess.Popen( "logrotate alienvault-suricata_test", shell=True )

  matches_text = file_matches.read()
  if( len( matches_text ) == 0 and otx_count > 0 ) :
    print( "ERROR: No new data in matches.log" )
    sys.exit()

  matches_lines = []
  start = 0
  lines = 0
  while( True ) :
    end = matches_text.find( "\n", start )
    if( end == -1 ) :
      break
    line_str = matches_text[start:end]
    matches_lines.append( line_str )
    lines += 1
    start = end + 1

  if( lines == otx_count ) :
    print( "OK. Found " + str(lines) )
  else :
    print( "FAIL: matches(" + str(lines) + ") not equal to new eve.json records(" + str(otx_count) + ")" )
    sys.exit()

  if( len(matches) != otx_count ) :
    print( "FAIL: matches size(" + str( len( matches ) ) + ") not equal to new eve.json records(" + str(otx_count) + ")" )
    sys.exit()

  for i in range( 0, otx_count ) :
    line_str = matches_lines[i]
    match = matches[i]
    type = match[0]
    dt = match[1]
    json_data = json.loads( line_str )
    if( "log" not in json_data) :
      print( "ERROR: 'log' not found in " + line_str )
      sys.exit()
    if( "pulses" not in json_data) :
      print( "ERROR: 'pulses' not found in " + line_str )
      sys.exit()

    if( json_data["timestamp"] != dt ) :
      print( "FAIL: " + type + ". Bad timestamp: " + json_data["timestamp"] + " vs " + dt )
      sys.exit()

    if( type == "domain_http" ) :
      if( "http" not in json_data["log"] ) :
        print( "ERROR: 'http' not found in " + line_str )
        sys.exit()
      if( "hostname" not in json_data["log"]["http"] ) :
        print( "ERROR: Domain: 'hostname' not found in " + line_str )
        sys.exit()

      if( "6202a9bc1182eff53c3eea00" not in json_data["pulses"] ) :
        print( "ERROR: Domain: '6202a9bc1182eff53c3eea00' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["6202a9bc1182eff53c3eea00"] ) == 0 ) :
        print( "ERROR: Domain: '6202a9bc1182eff53c3eea00' is empty " + line_str )
        sys.exit()

      domain_str = json_data["log"]["http"]["hostname"]
      if( domain_str != "threesmallhills.com" ) :
        print( "FAIL: Not domain hostname: " + domain_str )
        sys.exit()

      host_str = json_data["pulses"]["6202a9bc1182eff53c3eea00"][0]
      if( host_str != "threesmallhills.com" ) :
        print( "FAIL: Not domain(http) pulse hostname: " + str( host_str ) )
        sys.exit()
    if( type == "domain_dns" ) :
      if( "dns" not in json_data["log"] ) :
        print( "ERROR: 'dns' not found in " + line_str )
        sys.exit()
      if( "rrname" not in json_data["log"]["dns"] ) :
        print( "ERROR: Domain: 'rrname' not found in " + line_str )
        sys.exit()

      if( "6202a9bc1182eff53c3eea00" not in json_data["pulses"] ) :
        print( "ERROR: Domain: '6202a9bc1182eff53c3eea00' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["6202a9bc1182eff53c3eea00"] ) == 0 ) :
        print( "ERROR: Domain: '6202a9bc1182eff53c3eea00' is empty " + line_str )
        sys.exit()

      domain_str = json_data["log"]["dns"]["rrname"]
      if( domain_str != "threesmallhills.com" ) :
        print( "FAIL: Not domain(dns) hostname: " + domain_str )
        sys.exit()

      host_str = json_data["pulses"]["6202a9bc1182eff53c3eea00"][0]
      if( host_str != "threesmallhills.com" ) :
        print( "FAIL: Not domain pulse hostname: " + str( host_str ) )
        sys.exit()
    elif( type == "ip" ) :
      if( "6202a9bc1182eff53c3eea00" not in json_data["pulses"] ) :
        print( "ERROR: Pulse: '6202a9bc1182eff53c3eea00' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["6202a9bc1182eff53c3eea00"] ) == 0 ) :
        print( "ERROR: Pulse: '6202a9bc1182eff53c3eea00' is empty " + line_str )
        sys.exit()

      ip_str = json_data["pulses"]["6202a9bc1182eff53c3eea00"][0]
      if( ip_str != "2.56.59.42" ) :
        print( "FAIL: Not IP pulse ip: " + str( ip_str ) )
        sys.exit()
    elif( type == "fileinfo" ) :
      if( "6202a9bc1182eff53c3eea00" not in json_data["pulses"] ) :
        print( "ERROR: IP: '6202a9bc1182eff53c3eea00' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["6202a9bc1182eff53c3eea00"] ) == 0 ) :
        print( "ERROR: IP: '6202a9bc1182eff53c3eea00' is empty " + line_str )
        sys.exit()



  return count * 3

#-----------------------------------------------------------------------------------------------

def processCount( filter, grep_str ) :
#  cmd = ['ps', '-ef', '|', 'grep', 's' ]
  cmd = 'ps -ef | grep "' + filter + '"'
  try:
    p = subprocess.Popen( cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, stdin=subprocess.PIPE, shell=True )
  except OSError as e:
    print( "ERROR: executing of ps failed. errno=" + str( e.errno ) + ", strerror=" + e.strerror + ", filename=" + str( e.filename ) )
    sys.exit()
  except:
    print "Error > ",sys.exc_info()[0]
    sys.exit()

  lines = iter( p.stdout.readline, b'' )
  found = 0
  for line in lines :
    if line.find( grep_str ) != -1 :
      found += 1

  return found

#-----------------------------------------------------------------------------------------------





print ("Starting...")

#Preconditions check

if processCount( "/suricata", "/usr/bin/suricata --af-packet" ) > 0 :
  print ( "ERROR: Make sure suricata is NOT running" )
  sys.exit()
rhythm_count = processCount( "alienvault-rhythm", "/usr/bin/alienvault-rhythm -D" )
#rhythm_count = processCount( "alienvault-rhythm", "/alienvault-rhythm" )
if( rhythm_count < 1 ) :
  print ( "ERROR: Make sure rhythm is running" )
  sys.exit()
elif( rhythm_count > 1 ) :
  print ( "ERROR: Make sure ONE instance of rhythm is running" )
  sys.exit()

try:
  file_matches = open( matches_log, "r" )
except IOError:
  print( "There was an error reading file" )
  sys.exit()


#matches_text = file_matches.read()

for i in range( 1, 100 ) : # 100 cycles
  records_num = CheckMatchesLog( i, delay )
  if i % log_rotate_period == 0 :
    print( "--- log rotated" )
    subprocess.Popen( "logrotate alienvault-suricata_test", shell=True )
# sleep absence = following fresh eve.json records are lost
    time.sleep( 1 )

file_matches.close()

print ("Finished.")
