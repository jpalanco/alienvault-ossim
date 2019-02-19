#!/usr/bin/python

################################################################################################
#
# alienvault-rhythm Stress Test
# Emulates suricata input by appending records to eve.json and check accordance in  matches.log
#
################################################################################################

import sys, time, subprocess, json, datetime

eve_json = "/var/log/suricata/eve.json"
matches_log = "/var/log/alienvault/rhythm/matches.log"
file_text_ip           = '{"timestamp":"2016-01-11T11:11:11.111111","event_type":"dns","src_ip":"184.172.139.10","src_port":15892,"dest_ip":"1.1.1.1","dest_port":53,"proto":"UDP","dns":{"type":"query","id":55772,"rrname":"ns2.saudi.net.sa","rrtype":"A"}}'
# "http"/"hostname" + "dns"/"rrname" IF "answer"
file_text_domain_http  = '{"timestamp":"2016-02-22T22:22:22.222222","event_type":"http","src_ip":"1.1.1.1","src_port":41167,"dest_ip":"1.1.1.1","dest_port":80,"proto":"TCP","http":{"hostname":"heethai.com","url":"\/","http_user_agent":"Wget\/1.16 (linux-gnu)","http_content_type":"text\/html"}}'
file_text_domain_dns   = '{"timestamp":"2016-02-22T22:22:22.222222","event_type":"dns","src_ip":"1.1.1.1","src_port":53,"dest_ip":"1.1.1.1","dest_port":15892,"proto":"UDP","dns":{"type":"answer","id":55772,"rrname":"heethai.com","rrtype":"NS","ttl":3600}}'
# "dns"/"rrname" IF "answer"
#OTX hostname DB is empty :(
file_text_hostname     = '{"timestamp":"2016-03-33T33:33:33.333333","event_type":"dns","src_ip":"1.1.1.1","src_port":53,"dest_ip":"1.1.1.1","dest_port":15892,"proto":"UDP","dns":{"type":"answer","id":55772,"rrname":"saudi.net.sa","rrtype":"NS","ttl":3600}}'

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
    print(matches)
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

      if( "54d548f911d4081e0c545af7" not in json_data["pulses"] ) :
        print( "ERROR: Domain: '54d548f911d4081e0c545af7' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["54d548f911d4081e0c545af7"] ) == 0 ) :
        print( "ERROR: Domain: '54d548f911d4081e0c545af7' is empty " + line_str )
        sys.exit()

      domain_str = json_data["log"]["http"]["hostname"]
      if( domain_str != "heethai.com" ) :
        print( "FAIL: Not domain hostname: " + domain_str )
        sys.exit()

      host_str = json_data["pulses"]["54d548f911d4081e0c545af7"][0]
      if( host_str != "heethai.com" ) :
        print( "FAIL: Not domain(http) pulse hostname: " + str( host_str ) )
        sys.exit()
    if( type == "domain_dns" ) :
      if( "dns" not in json_data["log"] ) :
        print( "ERROR: 'dns' not found in " + line_str )
        sys.exit()
      if( "rrname" not in json_data["log"]["dns"] ) :
        print( "ERROR: Domain: 'rrname' not found in " + line_str )
        sys.exit()

      if( "54d548f911d4081e0c545af7" not in json_data["pulses"] ) :
        print( "ERROR: Domain: '54d548f911d4081e0c545af7' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["54d548f911d4081e0c545af7"] ) == 0 ) :
        print( "ERROR: Domain: '54d548f911d4081e0c545af7' is empty " + line_str )
        sys.exit()

      domain_str = json_data["log"]["dns"]["rrname"]
      if( domain_str != "heethai.com" ) :
        print( "FAIL: Not domain(dns) hostname: " + domain_str )
        sys.exit()

      host_str = json_data["pulses"]["54d548f911d4081e0c545af7"][0]
      if( host_str != "heethai.com" ) :
        print( "FAIL: Not domain pulse hostname: " + str( host_str ) )
        sys.exit()
    elif( type == "ip" ) :
      if( "5544c0d3b45ff53b128efa6b" not in json_data["pulses"] ) :
        print( "ERROR: IP: '5544c0d3b45ff53b128efa6b' not found in " + line_str )
        sys.exit()
      if( len( json_data["pulses"]["5544c0d3b45ff53b128efa6b"] ) == 0 ) :
        print( "ERROR: IP: '5544c0d3b45ff53b128efa6b' is empty " + line_str )
        sys.exit()

      ip_str = json_data["pulses"]["5544c0d3b45ff53b128efa6b"][0]
      if( ip_str != "184.172.139.10" ) :
        print( "FAIL: Not IP pulse ip: " + str( ip_str ) )
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

matches_text = file_matches.read()

for i in range( 1, 100 ) : # 100 cycles
  records_num = CheckMatchesLog( i, delay )
  if i % log_rotate_period == 0 :
    print( "--- log rotated" )
    subprocess.Popen( "logrotate alienvault-suricata_test", shell=True )
# sleep absence = following fresh eve.json records are lost
    time.sleep( 1 )

file_matches.close()

print ("Finished.")
