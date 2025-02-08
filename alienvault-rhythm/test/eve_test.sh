echo "" > /var/log/suricata/eve.json
# BAD: rhythm goes MAD echo "" > /var/log/alienvault/rhythm/matches.log
#ls -l /var/log/suricata/eve.json
#ls -l /var/log/alienvault/rhythm/matches.log
python eve_test.py
#echo EVE.JSON:
#cat /var/log/suricata/eve.json
#echo ------------------------
#echo MATCHES.LOG:
#cat /var/log/alienvault/rhythm/matches.log
