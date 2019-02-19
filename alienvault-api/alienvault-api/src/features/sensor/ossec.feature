Feature: Sensor ossec operations
  Scenario: Get the modified registry modified entries for an unknown ossec-agent
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agent/999/sys_check/windows_registry"
    Then The http status code must be "500"
    And JSON response has key "status" and value equals to string "error"


  Scenario: Get the modified registry modified entries for a known ossec-agent
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agent/998/sys_check/windows_registry"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    Then I print request result


  Scenario: Get the passlist when it exists
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agentless/passlist"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.local_path"

  Scenario: Get the passlist when it doesn't exist in the remote system
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agentless/passlist"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"

  Scenario: Set the pass list when it doesn't exists on the local folder
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agentless/passlist"
    Then The http status code must be "500"
    And JSON response has key "status" and value equals to string "error"

  Scenario: Set the pass list when it exists
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agentless/passlist"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/ossec/agentless"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.agents" and value equals to string "{u'root@192.168.1.45': {u'ppass': u'', u'pass': u'mypasss123'}}"

  Scenario: Check Ossec agent deletion through API
     Given I log in the server "127.0.0.1" using a ghost administrator
     And I select a random uuid for sensor and store in variable "s_uuid"
     And I generate a random string with len "8" and store in vault key "random_agent_name"
     And I create an ossec agent in sensor variable "s_uuid" with IP "255.255.255.255", name variable "random_agent_name" and store agent_id in "ossec_id"
     And I make url with paths and store it in variable "url"
         |paths|
         |https://127.0.0.1:40011/av/api/1.0/sensor|
         |$s_uuid|
         |ossec/agent|
         |$ossec_id|
     When I send a DELETE request to url stored in the variable "url"
     Then The http status code must be "200"


   Scenario: Check Ossec agent deletion through API, using a unexisings index
     Given I log in the server "127.0.0.1" using a ghost administrator
     And I select a random uuid for sensor and store in variable "s_uuid"
     And I make url with paths and store it in variable "url"
         |paths|
         |https://127.0.0.1:40011/av/api/1.0/sensor|
         |$s_uuid|
         |9999|
     When I send a DELETE request to url stored in the variable "url"
     Then The http status code must be "404"

  @crg
   Scenario: Check Ossec agent deletion through API, using a unexisings index, with bad chars
     Given I log in the server "127.0.0.1" using a ghost administrator
     And I select a random uuid for sensor and store in variable "s_uuid"
     And I make url with paths and store it in variable "url"
         |paths|
         |https://127.0.0.1:40011/av/api/1.0/sensor|
         |$s_uuid|
         |112|
     When I send a DELETE request to url stored in the variable "url"
     Then The http status code must be "404"


    Scenario: Test valid OSSEC Agent creation
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "agent_name" to string "test_agent"
    And I set url param "agent_ip" to string "10.1.1.1"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |ossec/agent|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"


    Scenario: Test bad agent_ip parameter in OSSEC Agent creation
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "agent_name" to string "test_agent"
    And I set url param "agent_ip" to string "10.1.1.1'aaa"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |ossec/agent|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "400"
    And JSON response has key "status" and value equals to string "error"


    Scenario: Test bad agent_name parameter in OSSEC Agent creation
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "agent_name" to string "test_agent'*"
    And I set url param "agent_ip" to string "10.1.1.1"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |ossec/agent|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "400"
    And JSON response has key "status" and value equals to string "error"


    # To test errors in agent creation, we prepare the scenario creating a fake client.keys
    # file with the same agent name that we will try to create
    Scenario: Test error in OSSEC Agent creation
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "agent_name" to string "test_agent"
    And I set url param "agent_ip" to string "10.1.1.1"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |ossec/agent|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "500"
    And JSON response has key "status" and value equals to string "error"
