Feature: Host operations
    As a API client, verify each status operation
    
    Scenario: Purgue a host from tables
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
# The host and net must exists in each table
    And I generate "100" current_status entries of type "host,net" 
    And I generate "100" monitor_data entries of type "host,net"
    And Select a random host in current_status and component_id in var "s_uuid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/data/host|
        |$s_uuid|
    When I send a DELETE request to url stored in the variable "url"
    Then I print request result
    And The http status code must be "200"
    And I verify that no current_status with "s_uuid" in database
    And I verify that no monitor_data with "s_uuid" in database
    And JSON response has key "status" and value equals to string "success"

    @wip
    Scenario: Test the orphan deleted
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
# The host and net must exists in each table
    And I generate "100" current_status entries of type "host,net" 
    And I generate "100" monitor_data entries of type "host,net"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/data/host|
    When I send a DELETE request to url stored in the variable "url"
    Then I print request result
    And The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"

    	

