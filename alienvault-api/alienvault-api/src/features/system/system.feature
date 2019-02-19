Feature: System operations
    As a API client, verify each 

    Scenario: Get the system list
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/system"
    Then The http status code must be "200"
    And The JSON response is equals to properties of system list

    Scenario: Get the system properties
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And I print request result
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.info"

    Scenario: Get the system properties local
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set variable "s_uuid" to string "local"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    #And I print request result
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.info"

    Scenario: Get a system interfaces local
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set variable "s_uuid" to string "local"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "data.interfaces"
    And JSON response has key "status" and value equals to string "success" 
    And The JSON response has all the interfaces for system in variable "s_uuid"
    #And I print request result
     
    Scenario: Get a system interfaces
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "data.interfaces"
    And JSON response has key "status" and value equals to string "success" 
    And The JSON response has all the interfaces for system in variable "s_uuid"


    Scenario: Get the the status of a system iface
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.interface"
    And I verify the interface in variable "iface" of system with uuid in variable "s_uuid"
    
    Scenario: Turn off the promisc mode of a interface
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random ethernet interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I set url param "promisc" to string "false"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 

    Scenario: Turn on the promisc mode of a interface
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random ethernet interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I set url param "promisc" to string "false"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
   
    Scenario: Verify we don't used bad parameters in promisc 
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random ethernet interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I set url param "promisc" to string "asco"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "400"
    #And I print request result

    Scenario: Verify the traffic stats
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random ethernet interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |traffic_stats|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.stats"

    
 

    Scenario: Get system interfaces traffic stats
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
        |traffic|
    And I set url param "timeout" to string "10"
    When I send a GET request to url stored in the variable "url"
    #Then I print request result
    #And The http status code must be "200"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.stats.has_traffic"
    #And JSON response has key "data.stats.TX"

    # Testing "local"
    Scenario:  Get system interfaces traffic stats with local
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set variable "s_uuid" to string "local"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
        |traffic|
    And I set url param "timeout" to string "10"
    When I send a GET request to url stored in the variable "url"
    #Then I print request result
    #And The http status code must be "200"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.stats.has_traffic"
    #And JSON response has key "data.stats.TX"

     
    

    Scenario: Get system interfaces traffic stats with timeout
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
        |traffic|
    And I set url param "timeout" to string "20"
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    #And I print request result
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.stats.has_traffic"

    Scenario: Get system interfaces traffic stats with  bad timeout (negative number)
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
        |traffic|
    And I set url param "timeout" to string "-10"
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "500"
    #And I print request result

    Scenario: Get system interfaces traffic stats with  bad timeout (bad number)
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I select a random interface for system with uuid in variable "s_uuid" and store in variable "iface"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
        |$iface|
        |traffic|
    And I set url param "timeout" to string "XXX"
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "500"
    #And I print request result


    #


    Scenario: Test the /system/<s_uuid>/doctor/support=ticket=support-id (ticket with 8 chars len)
    Given I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I set url param "ticket" to string "01234567"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |doctor|
        |support|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "data"
    And JSON response has key "status" and value equals to string "success" 
    And Verify the doctor response
    #And I print request result

    #Scenario: Test the /system/<s_uuid>/reconfig
    #Given I set username and password to ghost administrator
    #And I set the API server to "127.0.0.1"
    #And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    #And I select the uuid for random system and store it in variable "s_uuid"
    #And I make url with paths and store it in variable "url"
    #    |paths|
    #    |https://127.0.0.1:40011/av/api/1.0/system|
    #    |$s_uuid|
    #    |reconfig|
    #When I send a GET request to url stored in the variable "url"
    #Then The http status code must be "200"
    #And JSON response has key "status" and value equals to string "success" 
    #And I print request result

    Scenario: Test  /system/<system_id>/network/resolve
    Given I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |resolve|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success" 
    And JSON response has key "data.dns_resolution"

    Scenario: Test the /system/<system_id>/email
    Given I create a ghost SMTP server on port "5000"
    And I set username and password to ghost administrator
    And I set the API server to "127.0.0.1"
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |email|
    And I set url param "host" to string "localhost"
    And I set url param "port" to string "5000"
    And I set url param "subject" to string "Test de correo"
    And I set url param "body" to string "Esto es un test"
    And I set url param "sender" to string "ossim@127.0.0.1"
    And I set url param "recipients" to string "root@127.0.0.1"
    And I set url param "use_ssl" to string "0"
    When I send a GET request to url stored in the variable "url"
    Then I destroy the ghost SMTP server
    And I print request result
    And The http status code must be "200"

    Scenario: Test PUT /system/<system_id>/
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth2": {"ipaddress": "10.0.0.1", "netmask": "255.255.255.0", "role": "log_management"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I print request result 

    Scenario: Test PUT /system/<system_id>/ with the eth0 iface
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth0": {"ipaddress": "10.0.0.1", "netmask": "255.255.255.0", "role": "log_management"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "500"
    And I print request result 

	@wip
    Scenario: Test PUT /system/<system_id>/ with eth1 disabled  and eth2 configured with 10.0.0.1/24
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And The admin interface in the system with uuid in var "s_uuid" is "eth0"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth2": {"ipaddress": "10.0.0.1", "netmask": "255.255.255.0", "role": "log_management"}, "eth1":{"role":"disabled"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I print request result
    And I store the key "data.jobid" from result in variable "jobid" 
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "180" seconds
    And The interface "eth1" role is "disabled" in the system with uuid in var "s_uuid"
    And The interface "eth2" role is "log_management" in the system with uuid in var "s_uuid"
    And The interface "eth2" has ip "10.0.0.1" and netmask "255.255.255.0" in the system with uuid in var "s_uuid"

    #And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "180" seconds
   
    Scenario: Test PUT /system/<system_id>/ with eth1 and eth2, now changed :)
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth1": {"ipaddress": "10.0.0.1", "netmask": "255.255.255.0", "role": "log_management"}, "eth2":{"role":"disabled"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I store the key "data.jobid" from result in variable "jobid" 
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "180" seconds
    And The interface "eth1" role is "log_management" in the system with uuid in var "s_uuid"
    And The interface "eth2" role is "disabled" in the system with uuid in var "s_uuid"
    And The interface "eth1" has ip "10.0.0.1" and netmask "255.255.255.0" in the system with uuid in var "s_uuid"


    @wip
    Scenario: Test PUT /system/<system_id>/ with eth1 and eth2 disabled
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth1": {"role": "disabled"}, "eth2":{"role":"disabled"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I store the key "data.jobid" from result in variable "jobid" 
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "180" seconds
    And I flush API cache
    And The interface "eth1" role is "disabled" in the system with uuid in var "s_uuid"
    And The interface "eth2" role is "disabled" in the system with uuid in var "s_uuid"

    Scenario: Test PUT /system/<system_id>/ with eth1 and eth2 log_management
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select the uuid for random system and store it in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/system|
        |$s_uuid|
        |network|
        |interface|
    And I set url param "interfaces" to string "{"eth1": {"role": "log_management","ipaddress":"10.10.10.10","netmask":"255.255.255.0"}, "eth2":{"role":"log_management","ipaddress":"10.10.10.20","netmask":"255.255.255.0"}}"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I store the key "data.jobid" from result in variable "jobid" 
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "180" seconds
    And I flush API cache
    And The interface "eth1" role is "log_management" in the system with uuid in var "s_uuid"
    And The interface "eth1" has ip "10.10.10.10" and netmask "255.255.255.0" in the system with uuid in var "s_uuid"
    And The interface "eth2" role is "log_management" in the system with uuid in var "s_uuid"
    And The interface "eth2" has ip "10.10.10.20" and netmask "255.255.255.0" in the system with uuid in var "s_uuid"

    @crg
    Scenario: Test PUT /system/<system_id>/update/feed 
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/system/local/update/feed"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.job_id"


    @crg
    Scenario: Test PUT /system/<system_id>/update
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/system/local/update"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.job_id"


    @crg
    Scenario: Test PUT /system/<system_id>/update/feed  with wrong system id
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/system/1234/update/feed"
    Then The http status code must be "400"


    @crg
    Scenario: Test PUT /system/<system_id>/update with wrong system id
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/system/1234/update"
    Then The http status code must be "400"
