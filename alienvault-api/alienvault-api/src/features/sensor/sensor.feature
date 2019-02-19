
Feature: Sensors operations
  As an API client
  I want to be able to verify all sensor operations


    Scenario: Get sensor list and verify against data from database
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.sensors"
    And JSON response key "data.sensors" match ossim sensors table

    Scenario: Select a random sensor  and retrieve the sensor interfaces
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |interface|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    #And I print request result
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.interfaces"
    #And JSON response interfaces are in ossim_setup.conf

    Scenario: Get the properties of a unique sensor
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    #And I print request result
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.sensor"
    And JSON response properties are equal to sensor with uuid in variable "s_uuid"
   
    Scenario: Select a random sensor, retrieve machine interfaces and set in ossim_setup.conf
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I get the interfaces for sensor for uuid stored in variable "s_uuid" and store in variable "interfaces"
    And I select a group of interfaces from variable "interfaces" and store in variable "ifaces"
    And I set url param "ifaces" to variable "ifaces"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |interface|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    # I print request result
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.job_id_reconfig"
    And I store the key "data.job_id_reconfig" from result in variable "jobid"
    # Disable till some bugs in celery_job are resolved
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "300" seconds
    And I print request result
   
    Scenario: Test the PUT method of /sensor/<s_uuid>ctx=
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I store the sensor_ctx in variable "sensor_ctx" for sensor with uuid in variable "s_uuid"
    And I generate a random uuid and store in variable "ctxuuid"
    And I set url param "ctx" to variable "ctxuuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    #And I print request result
    #And The ossim_setup.conf key "[sensor]/sensor_ctx" for sensor variable "s_uuid" is equal to variable "ctxuuid"

 


    Scenario: Test the PUT method of /sensor/<s_uuid>/interface?ifaces with bad sensors
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "ifaces" to string "ascodevida,gamusino"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |interface|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "500"
    #And I print request result

    #@windows
    # Scenario: Test the OSSEC install /sensor/<s_uuid>/interface/ossec/deploy
    #     Given I set username and password to ghost administrator
    #     And I set the API server to "127.0.0.1"
    #     And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    #     And I select a random uuid for sensor and store in variable "s_uuid"
    #     And I set url param "agent_name" to string "prueba_behave"
    #     And I set url param "windows_ip" to string "------"
    #     And I set url param "windows_domain" to string "----"
    #     And I set url param "windows_username" to string "---"
    #     And I set url param "windows_password" to string "----"
    #     And I make url with paths and store it in variable "url"
    #         |paths|
    #         |https://127.0.0.1:40011/av/api/1.0/sensor|
    #         |$s_uuid|
    #         |ossec|
    #         |deploy|
    #     When I send a PUT request to url stored in the variable "url"
    #     Then The http status code must be "200"
    #     And JSON response has key "status" and value equals to string "success"
    #     And I store the key "data.job_id" from result in variable "jobid"
    #     And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "300" seconds
        #And I print status of job with id in variable "jobid"
        #And I print request result

   

    Scenario: Test the get sensor/<uuid>/networks
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |network|
    When I send a GET request to url stored in the variable "url"
    Then The http status code must be "200"
    And I print request result

    Scenario: Test the set sensor/<uuid>/networks
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I select a random uuid for sensor and store in variable "s_uuid"
    And I set url param "nets" to string "192.168.60.0/24,192.168.1.0/24,192.168.2.0/24"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/sensor|
        |$s_uuid|
        |network|
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"
    And I store the key "data.job_id_reconfig" from result in variable "jobid"
    And I verify the job with job_id in variable "jobid" has type "task-succeeded" after wait "300" seconds
    And The sensor networks are equal to string "192.168.60.0/24,192.168.1.0/24,192.168.2.0/24"

   

