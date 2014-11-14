---- backdoor.rules
-- 1980 BACKDOOR DeepThroat 3.1 Connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1980, 5002, 2140);
-- 1981 BACKDOOR DeepThroat 3.1 Connection attempt [3150]
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1981, 5002, 3150);
-- 1983 BACKDOOR DeepThroat 3.1 Connection attempt [4120]
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1983, 5002, 4120);
-- 1985 BACKDOOR Doly 1.5 server response
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1985, 5002, 1094);
-- 104 BACKDOOR - Dagger_1.4.0_client_connect
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 104, 5002, 2589);
-- 106 BACKDOOR ACKcmdC trojan scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 106, 5002, 1054);
-- 108 BACKDOOR QAZ Worm Client Login access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 108, 5002, 7597);
-- 121 BACKDOOR Infector 1.6 Client to Server Connection Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 121, 5002, 146);
-- 145 BACKDOOR GirlFriendaccess
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 145, 5002, 21554);
-- 157 BACKDOOR BackConstruction 2.1 Client FTP Open Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 157, 5002, 666);
-- 159 BACKDOOR NetMetro File List
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 159, 5002, 5032);
-- 161 BACKDOOR Matrix 2.0 Client connect
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 161, 5002, 3345);
-- 162 BACKDOOR Matrix 2.0 Server access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 162, 5002, 3344);
-- 185 BACKDOOR CDK
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 185, 5002, 79);
-- 209 BACKDOOR w00w00 attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 209, 5002, 23);
-- 210 BACKDOOR attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 210, 5002, 23);
-- 211 BACKDOOR MISC r00t attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 211, 5002, 23);
-- 212 BACKDOOR MISC rewt attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 212, 5002, 23);
-- 213 BACKDOOR MISC Linux rootkit attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 213, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 213, 5001, 2);
-- 214 BACKDOOR MISC Linux rootkit attempt lrkr0x
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 214, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 214, 5001, 2);
-- 215 BACKDOOR MISC Linux rootkit attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 215, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 215, 5001, 2);
-- 216 BACKDOOR MISC Linux rootkit satori attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 216, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 216, 5001, 2);
-- 217 BACKDOOR MISC sm4ck attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 217, 5002, 23);
-- 218 BACKDOOR MISC Solaris 2.5 attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 218, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 218, 5001, 9);
-- 219 BACKDOOR HidePak backdoor attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 219, 5002, 23);
-- 220 BACKDOOR HideSource backdoor attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 220, 5002, 23);
-- 614 BACKDOOR hack-a-tack attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 614, 5002, 31789);
-- 1853 BACKDOOR win-trin00 connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1853, 5002, 35555);
-- 1843 BACKDOOR trinity connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1843, 5002, 33270);
-- 1929 BACKDOOR TCPDUMP/PCAP trojan traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1929, 5002, 1963);
-- 2124 BACKDOOR Remote PC Access connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2124, 5002, 34012);
---- bad-traffic.rules
-- 524 BAD-TRAFFIC tcp port 0 traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 524, 5002, 0);
-- 525 BAD-TRAFFIC udp port 0 traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 525, 5002, 0);
---- bleeding-malware.rules
-- 2000900 BLEEDING-EDGE Malware JoltID Agent Probing or Announcing UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000900, 5002, 3531);
-- 2000901 BLEEDING-EDGE Malware JoltID Agent Communicating TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000901, 5002, 3531);
-- 2001015 BLEEDING-EDGE Malware JoltID Agent Keep-Alive
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001015, 5002, 3531);
-- 2001109 BLEEDING-EDGE Malware Page encrypted with HTMLcrypt - dangerous SPAM
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001109, 5002, 25);
-- 2001110 BLEEDING-EDGE Malware SRC=cid - dangerous SPAM or PHISHING
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001110, 5002, 25);
-- 2001111 BLEEDING-EDGE Obfuscated URL - typical PHISHING
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001111, 5002, 25);
-- 2001112 BLEEDING-EDGE Redirecting URL - typical PHISHING
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001112, 5002, 25);
-- 2000307 BLEEDING-EDGE Virtumonde Spyware siae3123.exe GET
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000307, 5002, 8081);
---- bleeding.rules
-- 2000497 BLEEDING-EDGE FTP hidden directory access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000497, 5002, 21);
-- 2000498 BLEEDING-EDGE FTP hidden directory access 2
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000498, 5002, 21);
-- 2000499 BLEEDING-EDGE FTP inaccessible directory access COM1
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000499, 5002, 21);
-- 2000500 BLEEDING-EDGE FTP inaccessible directory access COM2
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000500, 5002, 21);
-- 2000501 BLEEDING-EDGE FTP inaccessible directory access COM3
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000501, 5002, 21);
-- 2000502 BLEEDING-EDGE FTP inaccessible directory access COM4
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000502, 5002, 21);
-- 2000503 BLEEDING-EDGE FTP inaccessible directory access LPT1
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000503, 5002, 21);
-- 2000504 BLEEDING-EDGE FTP inaccessible directory access LPT2
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000504, 5002, 21);
-- 2000505 BLEEDING-EDGE FTP inaccessible directory access LPT3
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000505, 5002, 21);
-- 2000506 BLEEDING-EDGE FTP inaccessible directory access LPT4
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000506, 5002, 21);
-- 2000507 BLEEDING-EDGE FTP inaccessible directory access AUX
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000507, 5002, 21);
-- 2000508 BLEEDING-EDGE FTP inaccessible directory access NULL
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000508, 5002, 21);
-- 2000010 BLEEDING-EDGE Cisco 514 UDP flood DoS
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000010, 5002, 514);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000010, 5001, 3);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000010, 5001, 12);
-- 2000011 BLEEDING-EDGE DOS Catalyst memory leak attack
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000011, 5002, 23);
-- 2000496 DOS Microsoft SMS dos attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000496, 5002, 2702);
-- 2000007 BLEEDING-EDGE Catalyst SSH protocol mismatch
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000007, 5002, 22);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000007, 5001, 3);
-- 2000005 BLEEDING-EDGE Cisco Telnet Buffer Overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000005, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000005, 5001, 3);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000005, 5001, 12);
-- 2000048 BLEEDING-EDGE CVS server heap overflow attempt (target Linux)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000048, 5002, 2401);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000048, 5001, 2);
-- 2000031 BLEEDING-EDGE CVS server heap overflow attempt (target BSD)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000031, 5002, 2401);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000031, 5001, 4);
-- 2000049 BLEEDING-EDGE CVS server heap overflow attempt (target Solaris)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000049, 5002, 2401);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000049, 5001, 9);
-- 2000559 BLEEDING-EDGE THCIISLame IIS SSL Exploit Attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000559, 5002, 443);
-- 2000046 BLEEDING-EDGE MS04011 Lsasrv.dll RPC exploit (Win2k)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000046, 5002, 445);
-- 2000033 BLEEDING-EDGE MS04011 Lsasrv.dll RPC exploit (WinXP)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000033, 5002, 445);
-- 2000488 BLEEDING-EDGE MS-SQL SQL Injection closing string plus line comment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000488, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000488, 5001, 1);
-- 2000372 BLEEDING-EDGE MS-SQL SQL Injection running SQL statements line comment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000372, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000372, 5001, 1);
-- 2000373 BLEEDING-EDGE MS-SQL SQL Injection line comment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000373, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000373, 5001, 1);
-- 2000374 BLEEDING-EDGE MS-SQL SQL Injection trying to guess the column name
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000374, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000374, 5001, 1);
-- 2000376 BLEEDING-EDGE MS-SQL SQL Injection running SQL statements NO line comment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000376, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000376, 5001, 1);
-- 2000375 BLEEDING-EDGE MS-SQL SQL Injection allowing empty or wrong inputwith an OR
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000375, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000375, 5001, 1);
-- 2000490 BLEEDING-EDGE MS-SQL SQL Injection allowing empty or wrong inputwith an OR 2
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000490, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000490, 5001, 1);
-- 2000491 BLEEDING-EDGE MS-SQL SQL Injection allowing empty or wrong inputwith an OR 3
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000491, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000491, 5001, 1);
-- 2000492 BLEEDING-EDGE MS-SQL SQL Injection allowing empty or wrong inputwith an OR 4
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000492, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000492, 5001, 1);
-- 2000493 BLEEDING-EDGE MS-SQL SQL Injection allowing empty or wrong inputwith an OR 5
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000493, 5002, 1433);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000493, 5001, 1);
-- 2000377 BLEEDING-EDGE MS-SQL heap overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000377, 5002, 1434);
-- 2000378 BLEEDING-EDGE MS-SQL DOS attempt (08)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000378, 5002, 1434);
-- 2000379 BLEEDING-EDGE MS-SQL DOS attempt (08) 1 byte
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000379, 5002, 1434);
-- 2000380 BLEEDING-EDGE MS-SQL Spike buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000380, 5002, 1434);
-- 2000381 BLEEDING-EDGE MS-SQL DOS bouncing packets
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000381, 5002, 1434);
-- 2000382 BLEEDING-EDGE MS-SQL ping attempt (03)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000382, 5002, 1434);
-- 2000383 BLEEDING-EDGE MS-SQL buffer overflow attempt (04)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000383, 5002, 1434);
-- 2000384 BLEEDING-EDGE MS-SQL ping attempt (05)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000384, 5002, 1434);
-- 2000385 BLEEDING-EDGE MS-SQL ping attempt (06)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000385, 5002, 1434);
-- 2000017 BLEEDING-EDGE NII Microsoft ASN.1 Library Buffer Overflow Exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000017, 5002, 139);
-- 2000565 BLEEDING-EDGE Pwdump3e Session Established Reg-Entry port 139
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000565, 5002, 139);
-- 2000566 BLEEDING-EDGE Pwdump3e Session Established Reg-Entry port 445
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000566, 5002, 445);
-- 2000564 BLEEDING-EDGE Pwdump3e pwservice.exe Access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000564, 5002, 445);
-- 2000567 BLEEDING-EDGE Pwdump3e pwservice.exe Access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000567, 5002, 139);
-- 2001053 BLEEDING-EDGE EXPLOIT NTDump.exe Service Started
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001053, 5002, 139);
-- 2001052 BLEEDING-EDGE EXPLOIT NTDump Session Established Reg-Entry
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001052, 5002, 139);
-- 2000342 BLEEDING-EDGE Squid NTLM Auth Overflow Exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000342, 5002, 3128);
-- 2000369 BLEEDING-EDGE P2P BitTorrent Announce
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000369, 5002, 6969);
-- 2001055 BLEEDING-EDGE MISC HP Web JetAdmin ExecuteFile admin access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001055, 5002, 8000);
-- 2000328 BLEEDING-EDGE Multiple Non-SMTP Server Emails
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000328, 5002, 25);
-- 2000561 BLEEDING-EDGE VIRUS Possible Bagle.AI Worm Outbound
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000561, 5002, 25);
-- 2000343 BLEEDING-EDGE VIRUS Possible Evaman Worm Outbound
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000343, 5002, 25);
-- 2000562 BLEEDING-EDGE VIRUS OUTBOUND Suspicious Email Attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000562, 5002, 25);
-- 2000310 BLEEDING-EDGE Probable Zafi Virus Outbound via SMTP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000310, 5002, 25);
-- 2000494 BLEEDING-EDGE VIRUS Possible Atak.mm Worm Outbound
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2000494, 5002, 25);
-- 2001065 BLEEDING-EDGE VIRUS Possible Bagle.AQ Worm Outbound
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001065, 5002, 25);
-- 2001011 BLEEDING-EDGE Worm Zincite Probing port 1034
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2001011, 5002, 1034);
---- chat.rules
-- 540 CHAT MSN message
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 540, 5002, 1863);
-- 1986 CHAT MSN file transfer request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1986, 5002, 1863);
-- 1988 CHAT MSN file transfer accept
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1988, 5002, 1863);
-- 1989 CHAT MSN file transfer reject
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1989, 5002, 1863);
-- 1990 CHAT MSN user search
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1990, 5002, 1863);
-- 1991 CHAT MSN login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1991, 5002, 1863);
---- ddos.rules
-- 223 DDOS Trin00 Daemon to Master PONG message detected
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 223, 5002, 31335);
-- 230 DDOS shaft client to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 230, 5002, 20432);
-- 231 DDOS Trin00 Daemon to Master message detected
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 231, 5002, 31335);
-- 232 DDOS Trin00 Daemon to Master *HELLO* message detected
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 232, 5002, 31335);
-- 233 DDOS Trin00 Attacker to Master default startup password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 233, 5002, 27665);
-- 234 DDOS Trin00 Attacker to Master default password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 234, 5002, 27665);
-- 235 DDOS Trin00 Attacker to Master default mdie password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 235, 5002, 27665);
-- 237 DDOS Trin00 Master to Daemon default password attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 237, 5002, 27444);
-- 239 DDOS shaft handler to agent
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 239, 5002, 18753);
-- 240 DDOS shaft agent to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 240, 5002, 20433);
-- 243 DDOS mstream agent to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 243, 5002, 6838);
-- 244 DDOS mstream handler to agent
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 244, 5002, 10498);
-- 245 DDOS mstream handler ping to agent
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 245, 5002, 10498);
-- 246 DDOS mstream agent pong to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 246, 5002, 10498);
-- 247 DDOS mstream client to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 247, 5002, 12754);
-- 249 DDOS mstream client to handler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 249, 5002, 15104);
---- deleted.rules
-- 325 FINGER probe 0 attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 325, 5002, 79);
-- 506 MISC ramen worm incoming
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 506, 5002, 27374);
-- 1121 WEB-MISC O'Reilly args.bat access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1121, 5002, 80);
-- 855 WEB-CGI edit.pl access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 855, 5002, 80);
-- 1619 EXPERIMENTAL WEB-IIS .htr request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1619, 5002, 80);
-- 1114 WEB-MISC prefix-get //
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1114, 5002, 80);
-- 1749 EXPERIMENTAL WEB-IIS .NET trace.axd access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1749, 5002, 80);
-- 329 FINGER cybercop redirection
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 329, 5002, 79);
-- 1780 IMAP EXPLOIT partial body overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1780, 5002, 143);
-- 291 NNTP Cassandra Overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 291, 5002, 119);
-- 318 EXPLOIT bootp x86 bsd overfow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 318, 5002, 67);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 318, 5001, 4);
-- 319 EXPLOIT bootp x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 319, 5002, 67);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 319, 5001, 2);
-- 111 BACKDOOR netbus getinfo
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 111, 5002, 12346);
-- 116 BACKDOOR BackOrifice access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 116, 5002, 31337);
-- 164 BACKDOOR DeepThroat 3.1 Server Active on Network
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 164, 5002, 60000);
-- 165 BACKDOOR DeepThroat 3.1 Keylogger on Server ON
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 165, 5002, 2140);
-- 166 BACKDOOR DeepThroat 3.1 Show Picture Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 166, 5002, 2140);
-- 167 BACKDOOR DeepThroat 3.1 Hide/Show Clock Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 167, 5002, 2140);
-- 168 BACKDOOR DeepThroat 3.1 Hide/Show Desktop Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 168, 5002, 2140);
-- 169 BACKDOOR DeepThroat 3.1 Swap Mouse Buttons Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 169, 5002, 2140);
-- 170 BACKDOOR DeepThroat 3.1 Enable/Disable CTRL-ALT-DEL Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 170, 5002, 2140);
-- 171 BACKDOOR DeepThroat 3.1 Freeze Mouse Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 171, 5002, 2140);
-- 172 BACKDOOR DeepThroat 3.1 Show Dialog Box Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 172, 5002, 2140);
-- 173 BACKDOOR DeepThroat 3.1 Show Replyable Dialog Box Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 173, 5002, 2140);
-- 174 BACKDOOR DeepThroat 3.1 Hide/Show Start Button Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 174, 5002, 2140);
-- 175 BACKDOOR DeepThroat 3.1 Resolution Change Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 175, 5002, 2140);
-- 176 BACKDOOR DeepThroat 3.1 Hide/Show Start Button Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 176, 5002, 2140);
-- 177 BACKDOOR DeepThroat 3.1 Keylogger on Server OFF
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 177, 5002, 2140);
-- 179 BACKDOOR DeepThroat 3.1 FTP Server Port Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 179, 5002, 2140);
-- 180 BACKDOOR DeepThroat 3.1 Process List Client request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 180, 5002, 2140);
-- 181 BACKDOOR DeepThroat 3.1 Close Port Scan Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 181, 5002, 2140);
-- 182 BACKDOOR DeepThroat 3.1 Registry Add Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 182, 5002, 2140);
-- 122 BACKDOOR DeepThroat 3.1 System Info Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 122, 5002, 2140);
-- 124 BACKDOOR DeepThroat 3.1 FTP Status Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 124, 5002, 2140);
-- 125 BACKDOOR DeepThroat 3.1 E-Mail Info From Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 125, 5002, 60000);
-- 126 BACKDOOR DeepThroat 3.1 E-Mail Info Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 126, 5002, 2140);
-- 127 BACKDOOR DeepThroat 3.1 Server Status From Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 127, 5002, 60000);
-- 128 BACKDOOR DeepThroat 3.1 Server Status Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 128, 5002, 2140);
-- 129 BACKDOOR DeepThroat 3.1 Drive Info From Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 129, 5002, 60000);
-- 130 BACKDOOR DeepThroat 3.1 System Info From Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 130, 5002, 60000);
-- 131 BACKDOOR DeepThroat 3.1 Drive Info Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 131, 5002, 2140);
-- 132 BACKDOOR DeepThroat 3.1 Server FTP Port Change From Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 132, 5002, 60000);
-- 133 BACKDOOR DeepThroat 3.1 Cached Passwords Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 133, 5002, 2140);
-- 134 BACKDOOR DeepThroat 3.1 RAS Passwords Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 134, 5002, 2140);
-- 135 BACKDOOR DeepThroat 3.1 Server Password Change Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 135, 5002, 2140);
-- 136 BACKDOOR DeepThroat 3.1 Server Password Remove Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 136, 5002, 2140);
-- 137 BACKDOOR DeepThroat 3.1 Rehash Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 137, 5002, 2140);
-- 138 BACKDOOR DeepThroat 3.1 Server Rehash Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 138, 5002, 3150);
-- 140 BACKDOOR DeepThroat 3.1 ICQ Alert OFF Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 140, 5002, 2140);
-- 142 BACKDOOR DeepThroat 3.1 ICQ Alert ON Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 142, 5002, 2140);
-- 143 BACKDOOR DeepThroat 3.1 Change Wallpaper Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 143, 5002, 2140);
-- 149 BACKDOOR DeepThroat 3.1 Client Sending Data to Server on Network
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 149, 5002, 3150);
-- 150 BACKDOOR DeepThroat 3.1 Server Active on Network
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 150, 5002, 60000);
-- 151 BACKDOOR DeepThroat 3.1 Client Sending Data to Server on Network
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 151, 5002, 2140);
-- 154 BACKDOOR DeepThroat 3.1 Wrong Password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 154, 5002, 60000);
-- 156 BACKDOOR DeepThroat 3.1 Visible Window List Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 156, 5002, 2140);
-- 186 BACKDOOR DeepThroat 3.1 Monitor on/off Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 186, 5002, 2140);
-- 187 BACKDOOR DeepThroat 3.1 Delete File Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 187, 5002, 2140);
-- 188 BACKDOOR DeepThroat 3.1 Kill Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 188, 5002, 2140);
-- 189 BACKDOOR DeepThroat 3.1 Disable Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 189, 5002, 2140);
-- 190 BACKDOOR DeepThroat 3.1 Enable Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 190, 5002, 2140);
-- 191 BACKDOOR DeepThroat 3.1 Change Window Title Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 191, 5002, 2140);
-- 192 BACKDOOR DeepThroat 3.1 Hide Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 192, 5002, 2140);
-- 193 BACKDOOR DeepThroat 3.1 Show Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 193, 5002, 2140);
-- 194 BACKDOOR DeepThroat 3.1 Send Text to Window Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 194, 5002, 2140);
-- 196 BACKDOOR DeepThroat 3.1 Hide/Show Systray Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 196, 5002, 2140);
-- 197 BACKDOOR DeepThroat 3.1 Create Directory Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 197, 5002, 2140);
-- 198 BACKDOOR DeepThroat 3.1 All Window List Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 198, 5002, 2140);
-- 199 BACKDOOR DeepThroat 3.1 Play Sound Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 199, 5002, 2140);
-- 200 BACKDOOR DeepThroat 3.1 Run Program Normal Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 200, 5002, 2140);
-- 201 BACKDOOR DeepThroat 3.1 Run Program Hidden Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 201, 5002, 2140);
-- 202 BACKDOOR DeepThroat 3.1 Get NET File Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 202, 5002, 2140);
-- 203 BACKDOOR DeepThroat 3.1 Find File Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 203, 5002, 2140);
-- 204 BACKDOOR DeepThroat 3.1 Find File Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 204, 5002, 2140);
-- 205 BACKDOOR DeepThroat 3.1 HUP Modem Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 205, 5002, 2140);
-- 206 BACKDOOR DeepThroat 3.1 CD ROM Open Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 206, 5002, 2140);
-- 207 BACKDOOR DeepThroat 3.1 CD ROM Close Client Request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 207, 5002, 2140);
-- 252 DNS named iquery attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 252, 5002, 53);
-- 148 BACKDOOR DeepThroat 3.1 Keylogger Active on Network
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 148, 5002, 60000);
-- 338 FTP EXPLOIT format string
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 338, 5002, 21);
-- 339 FTP EXPLOIT OpenBSD x86 ftpd
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 339, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 339, 5001, 4);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 339, 5001, 7);
-- 340 FTP EXPLOIT overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 340, 5002, 21);
-- 341 FTP EXPLOIT overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 341, 5002, 21);
-- 342 FTP EXPLOIT wu-ftpd 2.6.0 site exec format string overflow Solaris 2.8
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 342, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 342, 5001, 9);
-- 343 FTP EXPLOIT wu-ftpd 2.6.0 site exec format string overflow FreeBSD
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 343, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 343, 5001, 4);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 343, 5001, 5);
-- 344 FTP EXPLOIT wu-ftpd 2.6.0 site exec format string overflow Linux
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 344, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 344, 5001, 2);
-- 345 FTP EXPLOIT wu-ftpd 2.6.0 site exec format string overflow generic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 345, 5002, 21);
-- 346 FTP EXPLOIT wu-ftpd 2.6.0 site exec format string check
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 346, 5002, 21);
-- 348 FTP EXPLOIT wu-ftpd 2.6.0
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 348, 5002, 21);
-- 349 FTP EXPLOIT MKD overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 349, 5002, 21);
-- 350 FTP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 350, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 350, 5001, 2);
-- 351 FTP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 351, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 351, 5001, 2);
-- 352 FTP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 352, 5002, 21);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 352, 5001, 2);
-- 1296 RPC portmap request yppasswdd
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1296, 5002, 111);
-- 1297 RPC portmap request yppasswdd
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1297, 5002, 111);
-- 596 RPC portmap listing
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 596, 5002, 111);
-- 597 RPC portmap listing
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 597, 5002, 32771);
-- 293 IMAP EXPLOIT overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 293, 5002, 143);
-- 295 IMAP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 295, 5002, 143);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 295, 5001, 2);
-- 296 IMAP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 296, 5002, 143);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 296, 5001, 2);
-- 297 IMAP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 297, 5002, 143);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 297, 5001, 2);
-- 298 IMAP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 298, 5002, 143);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 298, 5001, 2);
-- 299 IMAP EXPLOIT x86 linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 299, 5002, 143);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 299, 5001, 2);
-- 617 SCAN ssh-research-scanner
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 617, 5002, 22);
-- 2102 NETBIOS SMB SMB_COM_TRANSACTION Max Data Count of 0 DOS Attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2102, 5002, 139);
-- 656 SMTP EXPLOIT x86 windows CSMMail overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 656, 5002, 25);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 656, 5001, 1);
-- 666 SMTP sendmail 8.4.1 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 666, 5002, 25);
-- 1298 RPC portmap tooltalk request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1298, 5002, 111);
-- 1299 RPC portmap tooltalk request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1299, 5002, 111);
-- 733 Virus - Possible QAZ Worm Calling Home
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 733, 5002, 25);
-- 736 Virus - Successful eurocalculator execution
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 736, 5002, 25);
-- 738 Virus - Possible Pikachu Pokemon Virus
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 738, 5002, 110);
-- 1800 VIRUS Klez Incoming
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1800, 5002, 25);
-- 2254 SMTP XEXCH50 overflow with evasion attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2254, 5002, 25);
---- dns.rules
-- 255 DNS zone transfer TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 255, 5002, 53);
-- 1948 DNS zone transfer UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1948, 5002, 53);
-- 1435 DNS named authors attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1435, 5002, 53);
-- 256 DNS named authors attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 256, 5002, 53);
-- 257 DNS named version attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 257, 5002, 53);
-- 1616 DNS named version attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1616, 5002, 53);
-- 258 DNS EXPLOIT named 8.2->8.2.1
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 258, 5002, 53);
-- 303 DNS EXPLOIT named tsig overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 303, 5002, 53);
-- 314 DNS EXPLOIT named tsig overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 314, 5002, 53);
-- 259 DNS EXPLOIT named overflow (ADM)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 259, 5002, 53);
-- 260 DNS EXPLOIT named overflow (ADMROCKS)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 260, 5002, 53);
-- 261 DNS EXPLOIT named overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 261, 5002, 53);
-- 262 DNS EXPLOIT x86 Linux overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 262, 5002, 53);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 262, 5001, 2);
-- 264 DNS EXPLOIT x86 Linux overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 264, 5002, 53);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 264, 5001, 2);
-- 265 DNS EXPLOIT x86 Linux overflow attempt (ADMv2)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 265, 5002, 53);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 265, 5001, 2);
-- 266 DNS EXPLOIT x86 FreeBSD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 266, 5002, 53);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 266, 5001, 4);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 266, 5001, 5);
-- 267 DNS EXPLOIT sparc overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 267, 5002, 53);
---- dos.rules
-- 271 DOS UDP echo+chargen bomb
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 271, 5002, 7);
-- 276 DOS Real Audio Server
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 276, 5002, 7070);
-- 277 DOS Real Server template.html
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 277, 5002, 7070);
-- 278 DOS Real Server template.html
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 278, 5002, 8080);
-- 279 DOS Bay/Nortel Nautica Marlin
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 279, 5002, 161);
-- 281 DOS Ascend Route
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 281, 5002, 9);
-- 282 DOS arkiea backup
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 282, 5002, 617);
-- 1408 DOS MSDTC attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1408, 5002, 3372);
-- 1605 DOS iParty DOS attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1605, 5002, 6004);
-- 1545 DOS Cisco attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1545, 5002, 80);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1545, 5001, 3);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1545, 5001, 12);
---- experimental.rules
---- exploit.rules
-- 1324 EXPLOIT ssh CRC32 overflow /bin/sh
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1324, 5002, 22);
-- 1325 EXPLOIT ssh CRC32 overflow filler
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1325, 5002, 22);
-- 1326 EXPLOIT ssh CRC32 overflow NOOP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1326, 5002, 22);
-- 1327 EXPLOIT ssh CRC32 overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1327, 5002, 22);
-- 300 EXPLOIT nlps x86 Solaris overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 300, 5002, 2766);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 300, 5001, 9);
-- 301 EXPLOIT LPRng overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 301, 5002, 515);
-- 302 EXPLOIT Redhat 7.0 lprd overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 302, 5002, 515);
-- 304 EXPLOIT SCO calserver overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 304, 5002, 6373);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 304, 5001, 12);
-- 305 EXPLOIT delegate proxy overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 305, 5002, 8080);
-- 306 EXPLOIT VQServer admin
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 306, 5002, 9090);
-- 309 EXPLOIT sniffit overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 309, 5002, 25);
-- 310 EXPLOIT x86 windows MailMax overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 310, 5002, 25);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 310, 5001, 1);
-- 311 EXPLOIT Netscape 4.7 unsucessful overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 311, 5002, 80);
-- 312 EXPLOIT ntpdx overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 312, 5002, 123);
-- 313 EXPLOIT ntalkd x86 Linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 313, 5002, 518);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 313, 5001, 2);
-- 315 EXPLOIT x86 Linux mountd overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 315, 5002, 635);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 315, 5001, 2);
-- 316 EXPLOIT x86 Linux mountd overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 316, 5002, 635);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 316, 5001, 2);
-- 317 EXPLOIT x86 Linux mountd overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 317, 5002, 635);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 317, 5001, 2);
-- 1240 EXPLOIT MDBMS overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1240, 5002, 2224);
-- 1261 EXPLOIT AIX pdnsd overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1261, 5002, 4242);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1261, 5001, 13);
-- 1323 EXPLOIT rwhoisd format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1323, 5002, 4321);
-- 1398 EXPLOIT CDE dtspcd exploit attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1398, 5002, 6112);
-- 1894 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1894, 5002, 749);
-- 1895 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1895, 5002, 751);
-- 1896 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1896, 5002, 749);
-- 1897 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1897, 5002, 751);
-- 1898 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1898, 5002, 749);
-- 1899 EXPLOIT kadmind buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1899, 5002, 751);
-- 1812 EXPLOIT gobbles SSH exploit attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1812, 5002, 22);
-- 1821 EXPLOIT LPD dvips remote command execution attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1821, 5002, 515);
-- 292 EXPLOIT x86 Linux samba overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 292, 5002, 139);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 292, 5001, 2);
-- 2319 EXPLOIT ebola PASS overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2319, 5002, 1655);
-- 2320 EXPLOIT ebola USER overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2320, 5002, 1655);
---- finger.rules
-- 320 FINGER cmd_rootsh backdoor attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 320, 5002, 79);
-- 321 FINGER account enumeration attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 321, 5002, 79);
-- 322 FINGER search query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 322, 5002, 79);
-- 323 FINGER root query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 323, 5002, 79);
-- 324 FINGER null request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 324, 5002, 79);
-- 326 FINGER remote command execution attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 326, 5002, 79);
-- 327 FINGER remote command pipe execution attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 327, 5002, 79);
-- 328 FINGER bomb attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 328, 5002, 79);
-- 330 FINGER redirection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 330, 5002, 79);
-- 331 FINGER cybercop query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 331, 5002, 79);
-- 332 FINGER 0 query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 332, 5002, 79);
-- 333 FINGER . query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 333, 5002, 79);
-- 1541 FINGER version query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1541, 5002, 79);
---- ftp.rules
-- 2343 FTP STOR overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2343, 5002, 21);
-- 337 FTP CEL overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 337, 5002, 21);
-- 2344 FTP XCWD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2344, 5002, 21);
-- 1919 FTP CWD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1919, 5002, 21);
-- 1621 FTP CMD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1621, 5002, 21);
-- 1379 FTP STAT overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1379, 5002, 21);
-- 2340 FTP SITE CHMOD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2340, 5002, 21);
-- 1562 FTP SITE CHOWN overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1562, 5002, 21);
-- 1920 FTP SITE NEWER overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1920, 5002, 21);
-- 1888 FTP SITE CPWD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1888, 5002, 21);
-- 1971 FTP SITE EXEC format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1971, 5002, 21);
-- 1529 FTP SITE overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1529, 5002, 21);
-- 1734 FTP USER overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1734, 5002, 21);
-- 1972 FTP PASS overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1972, 5002, 21);
-- 1942 FTP RMDIR overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1942, 5002, 21);
-- 1973 FTP MKD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1973, 5002, 21);
-- 1974 FTP REST overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1974, 5002, 21);
-- 1975 FTP DELE overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1975, 5002, 21);
-- 1976 FTP RMD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1976, 5002, 21);
-- 1623 FTP invalid MODE
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1623, 5002, 21);
-- 1624 FTP large PWD command
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1624, 5002, 21);
-- 1625 FTP large SYST command
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1625, 5002, 21);
-- 2125 FTP CWD Root directory transversal attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2125, 5002, 21);
-- 1921 FTP SITE ZIPCHK overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1921, 5002, 21);
-- 1864 FTP SITE NEWER attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1864, 5002, 21);
-- 361 FTP SITE EXEC attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 361, 5002, 21);
-- 1777 FTP EXPLOIT STAT * dos attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1777, 5002, 21);
-- 1778 FTP EXPLOIT STAT ? dos attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1778, 5002, 21);
-- 362 FTP tar parameters
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 362, 5002, 21);
-- 336 FTP CWD ~root attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 336, 5002, 21);
-- 1229 FTP CWD ...
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1229, 5002, 21);
-- 1672 FTP CWD ~ attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1672, 5002, 21);
-- 1728 FTP CWD ~<CR><NEWLINE> attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1728, 5002, 21);
-- 1779 FTP CWD .... attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1779, 5002, 21);
-- 360 FTP serv-u directory transversal
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 360, 5002, 21);
-- 1377 FTP wu-ftp bad file completion attempt [
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1377, 5002, 21);
-- 1378 FTP wu-ftp bad file completion attempt {
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1378, 5002, 21);
-- 1530 FTP format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1530, 5002, 21);
-- 1622 FTP RNFR ././ attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1622, 5002, 21);
-- 1748 FTP command overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1748, 5002, 21);
-- 1992 FTP LIST directory traversal attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1992, 5002, 21);
-- 334 FTP .forward
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 334, 5002, 21);
-- 335 FTP .rhosts
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 335, 5002, 21);
-- 1927 FTP authorized_keys
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1927, 5002, 21);
-- 356 FTP passwd retrieval attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 356, 5002, 21);
-- 1928 FTP shadow retrieval attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1928, 5002, 21);
-- 144 FTP ADMw0rm ftp login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 144, 5002, 21);
-- 353 FTP adm scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 353, 5002, 21);
-- 354 FTP iss scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 354, 5002, 21);
-- 355 FTP pass wh00t
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 355, 5002, 21);
-- 357 FTP piss scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 357, 5002, 21);
-- 358 FTP saint scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 358, 5002, 21);
-- 359 FTP satan scan
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 359, 5002, 21);
-- 2178 FTP USER format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2178, 5002, 21);
-- 2179 FTP PASS format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2179, 5002, 21);
-- 2332 FTP MKDIR format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2332, 5002, 21);
-- 2333 FTP RENAME format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2333, 5002, 21);
-- 2338 FTP LIST buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2338, 5002, 21);
-- 2272 FTP LIST integer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2272, 5002, 21);
-- 2334 FTP Yak! FTP server default account login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2334, 5002, 3535);
-- 2335 FTP RMD / attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2335, 5002, 3535);
---- icmp-info.rules
---- icmp.rules
---- imap.rules
-- 1993 IMAP login literal buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1993, 5002, 143);
-- 1842 IMAP login buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1842, 5002, 143);
-- 2105 IMAP authenticate literal overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2105, 5002, 143);
-- 1844 IMAP authenticate overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1844, 5002, 143);
-- 1930 IMAP auth literal overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1930, 5002, 143);
-- 2330 IMAP auth overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2330, 5002, 143);
-- 1902 IMAP lsub literal overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1902, 5002, 143);
-- 2106 IMAP lsub overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2106, 5002, 143);
-- 1845 IMAP list literal overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1845, 5002, 143);
-- 2118 IMAP list overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2118, 5002, 143);
-- 2119 IMAP rename literal overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2119, 5002, 143);
-- 1903 IMAP rename overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1903, 5002, 143);
-- 1904 IMAP find overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1904, 5002, 143);
-- 1755 IMAP partial body buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1755, 5002, 143);
-- 2046 IMAP partial body.peek buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2046, 5002, 143);
-- 2107 IMAP create buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2107, 5002, 143);
-- 2120 IMAP create literal buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2120, 5002, 143);
-- 2273 IMAP login brute force attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2273, 5002, 143);
---- info.rules
-- 489 INFO FTP no password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 489, 5002, 21);
-- 490 INFO battle-mail traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 490, 5002, 25);
---- local.rules
---- misc.rules
-- 505 MISC Insecure TIMBUKTU Password
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 505, 5002, 1417);
-- 507 MISC PCAnywhere Attempted Administrator Login
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 507, 5002, 5631);
-- 508 MISC gopher proxy
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 508, 5002, 70);
-- 514 MISC ramen worm
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 514, 5002, 27374);
-- 516 MISC SNMP NT UserList
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 516, 5002, 161);
-- 517 MISC xdmcp query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 517, 5002, 177);
-- 1867 MISC xdmcp info query
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1867, 5002, 177);
-- 1384 MISC UPnP malformed advertisement
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1384, 5002, 1900);
-- 1388 MISC UPnP Location overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1388, 5002, 1900);
-- 1504 MISC AFS access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1504, 5002, 7001);
-- 1636 MISC Xtramail Username overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1636, 5002, 32000);
-- 1887 MISC OpenSSL Worm traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1887, 5002, 443);
-- 1889 MISC slapper worm admin traffic
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1889, 5002, 2002);
-- 1447 MISC MS Terminal server request (RDP)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1447, 5002, 3389);
-- 1448 MISC MS Terminal server request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1448, 5002, 3389);
-- 1819 MISC Alcatel PABX 4400 connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1819, 5002, 2533);
-- 1939 MISC bootp hardware address length overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1939, 5002, 67);
-- 1940 MISC bootp invalid hardware type
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1940, 5002, 67);
-- 2039 MISC bootp hostname format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2039, 5002, 67);
-- 1966 MISC GlobalSunTech Access Point Information Disclosure attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1966, 5002, 27155);
-- 1987 MISC xfs overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1987, 5002, 7100);
-- 2043 MISC isakmp login failed
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2043, 5002, 500);
-- 2047 MISC rsyncd module list access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2047, 5002, 873);
-- 2048 MISC rsyncd overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2048, 5002, 873);
-- 2318 MISC CVS non-relative path access attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2318, 5002, 2401);
-- 2126 MISC Microsoft PPTP Start Control Request buffer overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2126, 5002, 1723);
-- 2158 MISC BGP invalid length
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2158, 5002, 179);
-- 2159 MISC BGP invalid type (0)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2159, 5002, 179);
---- multimedia.rules
-- 1436 MULTIMEDIA Quicktime User Agent access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1436, 5002, 80);
---- mysql.rules
-- 1775 MYSQL root login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1775, 5002, 3306);
-- 1776 MYSQL show databases attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1776, 5002, 3306);
---- nntp.rules
-- 1538 NNTP AUTHINFO USER overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1538, 5002, 119);
---- oracle.rules
---- other-ids.rules
---- p2p.rules
-- 549 P2P napster login
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 549, 5002, 8888);
-- 550 P2P napster new user login
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 550, 5002, 8888);
-- 551 P2P napster download attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 551, 5002, 8888);
-- 561 P2P Napster Client Data
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 561, 5002, 6699);
-- 562 P2P Napster Client Data
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 562, 5002, 7777);
-- 563 P2P Napster Client Data
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 563, 5002, 6666);
-- 564 P2P Napster Client Data
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 564, 5002, 5555);
-- 565 P2P Napster Server Login
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 565, 5002, 8875);
-- 1383 P2P Fastrack  (kazaa/morpheus) GET request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1383, 5002, 1214);
---- policy.rules
-- 553 POLICY FTP anonymous login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 553, 5002, 21);
-- 1449 POLICY FTP anonymous (ftp) login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1449, 5002, 21);
-- 566 POLICY PCAnywhere server response
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 566, 5002, 5632);
-- 568 POLICY HP JetDirect LCD modification attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 568, 5002, 9100);
-- 1445 POLICY FTP file_id.diz access possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1445, 5002, 21);
-- 543 POLICY FTP 'STOR 1MB' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 543, 5002, 21);
-- 544 POLICY FTP 'RETR 1MB' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 544, 5002, 21);
-- 546 POLICY FTP 'CWD  ' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 546, 5002, 21);
-- 547 POLICY FTP 'MKD  ' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 547, 5002, 21);
-- 548 POLICY FTP 'MKD .' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 548, 5002, 21);
-- 545 POLICY FTP 'CWD / ' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 545, 5002, 21);
-- 554 POLICY FTP 'MKD / ' possible warez site
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 554, 5002, 21);
-- 2044 POLICY PPTP Start Control Request attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2044, 5002, 1723);
-- 2040 POLICY xtacacs login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2040, 5002, 49);
-- 1771 POLICY IPSec PGPNet connection attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1771, 5002, 500);
---- pop2.rules
-- 1934 POP2 FOLD overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1934, 5002, 109);
-- 1935 POP2 FOLD arbitrary file attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1935, 5002, 109);
-- 284 POP2 x86 Linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 284, 5002, 109);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 284, 5001, 2);
-- 285 POP2 x86 Linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 285, 5002, 109);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 285, 5001, 2);
---- pop3.rules
-- 2121 POP3 DELE negative arguement attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2121, 5002, 110);
-- 2122 POP3 UIDL negative arguement attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2122, 5002, 110);
-- 1866 POP3 USER overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1866, 5002, 110);
-- 2108 POP3 CAPA overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2108, 5002, 110);
-- 2109 POP3 TOP overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2109, 5002, 110);
-- 2110 POP3 STAT overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2110, 5002, 110);
-- 2111 POP3 DELE overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2111, 5002, 110);
-- 2112 POP3 RSET overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2112, 5002, 110);
-- 1936 POP3 AUTH overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1936, 5002, 110);
-- 1937 POP3 LIST overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1937, 5002, 110);
-- 1938 POP3 XTND overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1938, 5002, 110);
-- 1634 POP3 PASS overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1634, 5002, 110);
-- 1635 POP3 APOP overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1635, 5002, 110);
-- 286 POP3 EXPLOIT x86 BSD overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 286, 5002, 110);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 286, 5001, 4);
-- 287 POP3 EXPLOIT x86 BSD overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 287, 5002, 110);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 287, 5001, 4);
-- 288 POP3 EXPLOIT x86 Linux overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 288, 5002, 110);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 288, 5001, 2);
-- 289 POP3 EXPLOIT x86 SCO overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 289, 5002, 110);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 289, 5001, 12);
-- 290 POP3 EXPLOIT qpopper overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 290, 5002, 110);
-- 2250 POP3 USER format string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2250, 5002, 110);
-- 2274 POP3 login brute force attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2274, 5002, 110);
---- porn.rules
---- rpc.rules
-- 2093 RPC portmap proxy integer overflow attempt TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2093, 5002, 111);
-- 2092 RPC portmap proxy integer overflow attempt UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2092, 5002, 111);
-- 1922 RPC portmap proxy attempt TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1922, 5002, 111);
-- 1923 RPC portmap proxy attempt UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1923, 5002, 111);
-- 1280 RPC portmap listing UDP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1280, 5002, 111);
-- 598 RPC portmap listing TCP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 598, 5002, 111);
-- 1949 RPC portmap SET attempt TCP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1949, 5002, 111);
-- 1950 RPC portmap SET attempt UDP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1950, 5002, 111);
-- 2014 RPC portmap UNSET attempt TCP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2014, 5002, 111);
-- 2015 RPC portmap UNSET attempt UDP 111
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2015, 5002, 111);
-- 599 RPC portmap listing TCP 32771
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 599, 5002, 32771);
-- 1281 RPC portmap listing UDP 32771
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1281, 5002, 32771);
-- 1746 RPC portmap cachefsd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1746, 5002, 111);
-- 1747 RPC portmap cachefsd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1747, 5002, 111);
-- 1732 RPC portmap rwalld request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1732, 5002, 111);
-- 1733 RPC portmap rwalld request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1733, 5002, 111);
-- 575 RPC portmap admind request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 575, 5002, 111);
-- 1262 RPC portmap admind request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1262, 5002, 111);
-- 576 RPC portmap amountd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 576, 5002, 111);
-- 1263 RPC portmap amountd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1263, 5002, 111);
-- 577 RPC portmap bootparam request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 577, 5002, 111);
-- 1264 RPC portmap bootparam request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1264, 5002, 111);
-- 580 RPC portmap nisd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 580, 5002, 111);
-- 1267 RPC portmap nisd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1267, 5002, 111);
-- 581 RPC portmap pcnfsd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 581, 5002, 111);
-- 1268 RPC portmap pcnfsd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1268, 5002, 111);
-- 582 RPC portmap rexd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 582, 5002, 111);
-- 1269 RPC portmap rexd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1269, 5002, 111);
-- 584 RPC portmap rusers request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 584, 5002, 111);
-- 1271 RPC portmap rusers request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1271, 5002, 111);
-- 586 RPC portmap selection_svc request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 586, 5002, 111);
-- 1273 RPC portmap selection_svc request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1273, 5002, 111);
-- 587 RPC portmap status request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 587, 5002, 111);
-- 2016 RPC portmap status request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2016, 5002, 111);
-- 593 RPC portmap snmpXdmi request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 593, 5002, 111);
-- 1279 RPC portmap snmpXdmi request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1279, 5002, 111);
-- 2017 RPC portmap espd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2017, 5002, 111);
-- 595 RPC portmap espd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 595, 5002, 111);
-- 579 RPC portmap mountd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 579, 5002, 111);
-- 1266 RPC portmap mountd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1266, 5002, 111);
-- 578 RPC portmap cmsd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 578, 5002, 111);
-- 1265 RPC portmap cmsd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1265, 5002, 111);
-- 1272 RPC portmap sadmind request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1272, 5002, 111);
-- 585 RPC portmap sadmind request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 585, 5002, 111);
-- 583 RPC portmap rstatd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 583, 5002, 111);
-- 1270 RPC portmap rstatd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1270, 5002, 111);
-- 1277 RPC portmap ypupdated request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1277, 5002, 111);
-- 591 RPC portmap ypupdated request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 591, 5002, 111);
-- 1959 RPC portmap NFS request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1959, 5002, 111);
-- 1960 RPC portmap NFS request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1960, 5002, 111);
-- 1961 RPC portmap RQUOTA request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1961, 5002, 111);
-- 1962 RPC portmap RQUOTA request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1962, 5002, 111);
-- 588 RPC portmap ttdbserv request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 588, 5002, 111);
-- 1274 RPC portmap ttdbserv request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1274, 5002, 111);
-- 589 RPC portmap yppasswd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 589, 5002, 111);
-- 1275 RPC portmap yppasswd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1275, 5002, 111);
-- 590 RPC portmap ypserv request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 590, 5002, 111);
-- 1276 RPC portmap ypserv request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1276, 5002, 111);
-- 2035 RPC portmap network-status-monitor request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2035, 5002, 111);
-- 2036 RPC portmap network-status-monitor request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2036, 5002, 111);
-- 2079 RPC portmap nlockmgr request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2079, 5002, 111);
-- 2080 RPC portmap nlockmgr request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2080, 5002, 111);
-- 2081 RPC portmap rpc.xfsmd request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2081, 5002, 111);
-- 2082 RPC portmap rpc.xfsmd request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2082, 5002, 111);
-- 2005 RPC portmap kcms_server request UDP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2005, 5002, 111);
-- 2006 RPC portmap kcms_server request TCP
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2006, 5002, 111);
---- rservices.rules
-- 601 RSERVICES rlogin LinuxNIS
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 601, 5002, 513);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 601, 5001, 2);
-- 602 RSERVICES rlogin bin
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 602, 5002, 513);
-- 603 RSERVICES rlogin echo++
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 603, 5002, 513);
-- 604 RSERVICES rsh froot
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 604, 5002, 513);
-- 606 RSERVICES rlogin root
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 606, 5002, 513);
-- 607 RSERVICES rsh bin
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 607, 5002, 514);
-- 608 RSERVICES rsh echo + +
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 608, 5002, 514);
-- 609 RSERVICES rsh froot
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 609, 5002, 514);
-- 610 RSERVICES rsh root
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 610, 5002, 514);
-- 2113 RSERVICES rexec username overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2113, 5002, 512);
-- 2114 RSERVICES rexec password overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2114, 5002, 512);
---- scan.rules
-- 616 SCAN ident version request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 616, 5002, 113);
-- 619 SCAN cybercop os probe
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 619, 5002, 80);
-- 618 SCAN Squid Proxy attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 618, 5002, 3128);
-- 615 SCAN SOCKS Proxy attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 615, 5002, 1080);
-- 620 SCAN Proxy Port 8080 attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 620, 5002, 8080);
-- 635 SCAN XTACACS logout
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 635, 5002, 49);
-- 636 SCAN cybercop udp bomb
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 636, 5002, 7);
-- 1638 SCAN SSH Version map attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1638, 5002, 22);
-- 1917 SCAN UPnP service discover attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1917, 5002, 1900);
---- shellcode.rules
---- smtp.rules
-- 654 SMTP RCPT TO overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 654, 5002, 25);
-- 657 SMTP chameleon overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 657, 5002, 25);
-- 655 SMTP sendmail 8.6.9 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 655, 5002, 25);
-- 658 SMTP exchange mime DOS
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 658, 5002, 25);
-- 659 SMTP expn decode
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 659, 5002, 25);
-- 660 SMTP expn root
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 660, 5002, 25);
-- 1450 SMTP expn *@
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1450, 5002, 25);
-- 661 SMTP majordomo ifs
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 661, 5002, 25);
-- 662 SMTP sendmail 5.5.5 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 662, 5002, 25);
-- 663 SMTP rcpt to command attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 663, 5002, 25);
-- 664 SMTP RCPT TO decode attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 664, 5002, 25);
-- 665 SMTP sendmail 5.6.5 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 665, 5002, 25);
-- 667 SMTP sendmail 8.6.10 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 667, 5002, 25);
-- 668 SMTP sendmail 8.6.10 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 668, 5002, 25);
-- 669 SMTP sendmail 8.6.9 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 669, 5002, 25);
-- 670 SMTP sendmail 8.6.9 exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 670, 5002, 25);
-- 671 SMTP sendmail 8.6.9c exploit
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 671, 5002, 25);
-- 672 SMTP vrfy decode
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 672, 5002, 25);
-- 1446 SMTP vrfy root
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1446, 5002, 25);
-- 631 SMTP ehlo cybercop attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 631, 5002, 25);
-- 632 SMTP expn cybercop attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 632, 5002, 25);
-- 1549 SMTP HELO overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1549, 5002, 25);
-- 1550 SMTP ETRN overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1550, 5002, 25);
-- 2087 SMTP From comment overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2087, 5002, 25);
-- 2183 SMTP Content-Transfer-Encoding overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2183, 5002, 25);
-- 2253 SMTP XEXCH50 overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2253, 5002, 25);
-- 2259 SMTP EXPN overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2259, 5002, 25);
-- 2260 SMTP VRFY overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2260, 5002, 25);
-- 2261 SMTP SEND FROM sendmail prescan too many addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2261, 5002, 25);
-- 2262 SMTP SEND FROM sendmail prescan too long addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2262, 5002, 25);
-- 2263 SMTP SAML FROM sendmail prescan too many addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2263, 5002, 25);
-- 2264 SMTP SAML FROM sendmail prescan too long addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2264, 5002, 25);
-- 2265 SMTP SOML FROM sendmail prescan too many addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2265, 5002, 25);
-- 2266 SMTP SOML FROM sendmail prescan too long addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2266, 5002, 25);
-- 2267 SMTP MAIL FROM sendmail prescan too many addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2267, 5002, 25);
-- 2268 SMTP MAIL FROM sendmail prescan too long addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2268, 5002, 25);
-- 2269 SMTP RCPT TO sendmail prescan too many addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2269, 5002, 25);
-- 2270 SMTP RCPT TO sendmail prescan too long addresses overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2270, 5002, 25);
---- snmp.rules
-- 1893 SNMP missing community string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1893, 5002, 161);
-- 1892 SNMP null community string attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1892, 5002, 161);
-- 1411 SNMP public access udp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1411, 5002, 161);
-- 1412 SNMP public access tcp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1412, 5002, 161);
-- 1413 SNMP private access udp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1413, 5002, 161);
-- 1414 SNMP private access tcp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1414, 5002, 161);
-- 1415 SNMP Broadcast request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1415, 5002, 161);
-- 1416 SNMP broadcast trap
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1416, 5002, 162);
-- 1417 SNMP request udp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1417, 5002, 161);
-- 1418 SNMP request tcp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1418, 5002, 161);
-- 1419 SNMP trap udp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1419, 5002, 162);
-- 1420 SNMP trap tcp
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1420, 5002, 162);
-- 1421 SNMP AgentX/tcp request
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1421, 5002, 705);
-- 1426 SNMP PROTOS test-suite-req-app attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1426, 5002, 161);
-- 1427 SNMP PROTOS test-suite-trap-app attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1427, 5002, 162);
---- sql.rules
-- 676 MS-SQL/SMB sp_start_job - program execution
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 676, 5002, 139);
-- 677 MS-SQL/SMB sp_password password change
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 677, 5002, 139);
-- 678 MS-SQL/SMB sp_delete_alert log file deletion
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 678, 5002, 139);
-- 679 MS-SQL/SMB sp_adduser database user creation
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 679, 5002, 139);
-- 708 MS-SQL/SMB xp_enumresultset possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 708, 5002, 139);
-- 1386 MS-SQL/SMB raiserror possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1386, 5002, 139);
-- 702 MS-SQL/SMB xp_displayparamstmt possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 702, 5002, 139);
-- 703 MS-SQL/SMB xp_setsqlsecurity possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 703, 5002, 139);
-- 681 MS-SQL/SMB xp_cmdshell program execution
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 681, 5002, 139);
-- 689 MS-SQL/SMB xp_reg* registry access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 689, 5002, 139);
-- 690 MS-SQL/SMB xp_printstatements possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 690, 5002, 139);
-- 692 MS-SQL/SMB shellcode attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 692, 5002, 139);
-- 694 MS-SQL/SMB shellcode attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 694, 5002, 139);
-- 695 MS-SQL/SMB xp_sprintf possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 695, 5002, 139);
-- 696 MS-SQL/SMB xp_showcolv possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 696, 5002, 139);
-- 697 MS-SQL/SMB xp_peekqueue possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 697, 5002, 139);
-- 698 MS-SQL/SMB xp_proxiedmetadata possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 698, 5002, 139);
-- 700 MS-SQL/SMB xp_updatecolvbm possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 700, 5002, 139);
-- 673 MS-SQL sp_start_job - program execution
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 673, 5002, 1433);
-- 674 MS-SQL xp_displayparamstmt possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 674, 5002, 1433);
-- 675 MS-SQL xp_setsqlsecurity possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 675, 5002, 1433);
-- 682 MS-SQL xp_enumresultset possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 682, 5002, 1433);
-- 683 MS-SQL sp_password - password change
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 683, 5002, 1433);
-- 684 MS-SQL sp_delete_alert log file deletion
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 684, 5002, 1433);
-- 685 MS-SQL sp_adduser - database user creation
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 685, 5002, 1433);
-- 686 MS-SQL xp_reg* - registry access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 686, 5002, 1433);
-- 687 MS-SQL xp_cmdshell - program execution
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 687, 5002, 1433);
-- 691 MS-SQL shellcode attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 691, 5002, 1433);
-- 693 MS-SQL shellcode attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 693, 5002, 1433);
-- 699 MS-SQL xp_printstatements possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 699, 5002, 1433);
-- 701 MS-SQL xp_updatecolvbm possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 701, 5002, 1433);
-- 704 MS-SQL xp_sprintf possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 704, 5002, 1433);
-- 705 MS-SQL xp_showcolv possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 705, 5002, 1433);
-- 706 MS-SQL xp_peekqueue possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 706, 5002, 1433);
-- 707 MS-SQL xp_proxiedmetadata possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 707, 5002, 1433);
-- 1387 MS-SQL raiserror possible buffer overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1387, 5002, 1433);
-- 1759 MS-SQL xp_cmdshell program execution (445)
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1759, 5002, 445);
-- 2003 MS-SQL Worm propagation attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2003, 5002, 1434);
-- 2004 MS-SQL Worm propagation attempt OUTBOUND
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2004, 5002, 1434);
-- 2049 MS-SQL ping attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2049, 5002, 1434);
-- 2050 MS-SQL version overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2050, 5002, 1434);
---- telnet.rules
-- 1430 TELNET Solaris memory mismanagement exploit attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1430, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1430, 5001, 9);
-- 711 TELNET SGI telnetd format bug
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 711, 5002, 23);
-- 712 TELNET ld_library_path
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 712, 5002, 23);
-- 713 TELNET livingston DOS
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 713, 5002, 23);
-- 714 TELNET resolv_host_conf
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 714, 5002, 23);
-- 1253 TELNET bsd exploit client finishing
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1253, 5002, 23);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1253, 5001, 4);
-- 709 TELNET 4Dgifts SGI account attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 709, 5002, 23);
-- 710 TELNET EZsetup account attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 710, 5002, 23);
---- tftp.rules
-- 1941 TFTP GET filename overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1941, 5002, 69);
-- 2337 TFTP PUT filename overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2337, 5002, 69);
-- 1289 TFTP GET Admin.dll
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1289, 5002, 69);
-- 1441 TFTP GET nc.exe
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1441, 5002, 69);
-- 1442 TFTP GET shadow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1442, 5002, 69);
-- 1443 TFTP GET passwd
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1443, 5002, 69);
-- 519 TFTP parent directory
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 519, 5002, 69);
-- 520 TFTP root directory
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 520, 5002, 69);
-- 518 TFTP Put
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 518, 5002, 69);
-- 1444 TFTP Get
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1444, 5002, 69);
-- 2339 TFTP NULL command attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2339, 5002, 69);
---- virus.rules
-- 732 Virus - Possible QAZ Worm Infection
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 732, 5002, 139);
-- 721 VIRUS OUTBOUND .pif file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 721, 5002, 25);
-- 730 VIRUS OUTBOUND .shs file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 730, 5002, 25);
-- 2160 VIRUS OUTBOUND .exe file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2160, 5002, 25);
-- 2161 VIRUS OUTBOUND .doc file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2161, 5002, 25);
-- 793 VIRUS OUTBOUND .vbs file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 793, 5002, 25);
-- 2162 VIRUS OUTBOUND .hta file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2162, 5002, 25);
-- 2163 VIRUS OUTBOUND .chm file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2163, 5002, 25);
-- 2164 VIRUS OUTBOUND .reg file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2164, 5002, 25);
-- 2165 VIRUS OUTBOUND .ini file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2165, 5002, 25);
-- 2166 VIRUS OUTBOUND .bat file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2166, 5002, 25);
-- 2167 VIRUS OUTBOUND .diz file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2167, 5002, 25);
-- 2168 VIRUS OUTBOUND .cpp file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2168, 5002, 25);
-- 2169 VIRUS OUTBOUND .dll file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2169, 5002, 25);
-- 2170 VIRUS OUTBOUND .vxd file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2170, 5002, 25);
-- 2171 VIRUS OUTBOUND .sys file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2171, 5002, 25);
-- 2172 VIRUS OUTBOUND .com file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2172, 5002, 25);
-- 729 VIRUS OUTBOUND .scr file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 729, 5002, 25);
-- 2173 VIRUS OUTBOUND .hsq file attachment
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2173, 5002, 25);
---- web-attacks.rules
---- web-cgi.rules
-- 1868 WEB-CGI story.pl arbitrary file read attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1868, 5002, 8080);
-- 1869 WEB-CGI story.pl access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1869, 5002, 8080);
-- 2086 WEB-CGI streaming server parse_xml.cgi access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 2086, 5002, 1220);
---- web-client.rules
---- web-coldfusion.rules
---- web-frontpage.rules
---- web-iis.rules
---- web-misc.rules
-- 1498 WEB-MISC PIX firewall manager directory traversal attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1498, 5002, 8181);
-- 1604 WEB-MISC iChat directory traversal attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1604, 5002, 4080);
-- 1558 WEB-MISC Delegate whois overflow attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1558, 5002, 8080);
-- 1518 WEB-MISC nstelemetry.adp access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1518, 5002, 8000);
-- 1132 WEB-MISC Netscape Unixware overflow
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1132, 5002, 457);
-- 1199 WEB-MISC Compaq Insight directory traversal
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1199, 5002, 2301);
-- 1232 WEB-MISC VirusWall catinfo access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1232, 5002, 1812);
-- 1858 WEB-MISC CISCO PIX Firewall Manager directory traversal attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1858, 5002, 8181);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1858, 5001, 3);
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1858, 5001, 12);
-- 1859 WEB-MISC Sun JavaServer default password login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1859, 5002, 9090);
-- 1860 WEB-MISC Linksys router default password login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1860, 5002, 8080);
-- 1861 WEB-MISC Linksys router default username and password login attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1861, 5002, 8080);
-- 1499 WEB-MISC SiteScope Service access
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1499, 5002, 8888);
-- 1946 WEB-MISC answerbook2 admin attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1946, 5002, 8888);
-- 1947 WEB-MISC answerbook2 arbitrary command execution attempt
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1947, 5002, 8888);
---- web-php.rules
---- x11.rules
-- 1225 X11 MIT Magic Cookie detected
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1225, 5002, 6000);
-- 1226 X11 xopen
INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, 1226, 5002, 6000);
