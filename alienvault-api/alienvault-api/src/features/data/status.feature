Feature: Status operations
    As a API client, verify each status operation
   
    Scenario: Get all status messages
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"  
    And I clean the status_message database
    And I generate "100" current_status entries
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.total" and value equals to string "100"
    And Verify the current_status result
 
    Scenario: Test pagination of messages
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set url param "page" to string "1"
    And I set url param "page_rows" to string "5"
    And I clean the status_message database 
    And I generate "100" current_status entries
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    And I print request result
    #And JSON response has key "data.total" and value equals to string "100"

    Scenario: Test filter order_desc = true
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set url param "page_rows" to string "5"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "page" to string "1"
    And I set url param "page_rows" to string "20"
    And I set url param "order_desc" to string "true"
    And I set url param "order_by" to string "creation_time"
    And I set url param "page_rows" to string "5"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"

    Scenario: Test filter order_desc = false
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set url param "page_rows" to string "5"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "page" to string "1"
    And I set url param "page_rows" to string "20"
    And I set url param "order_desc" to string "false"
    And I set url param "order_by" to string "creation_time"
    And I set url param "page_rows" to string "5"
       When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
   
    @wip
    @skip
    Scenario: Get a message
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
      And I clean the status_message database 
    And I generate "100" current_status entries
    And Select a random entry from current_status and store entry in var "entry"
    And Select key "id" from dict "entry" and store in var "mid"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/data/status|
        |$mid|
    When I send a GET request request to url stored in the variable "url"
    Then I print request result
    And The http status code must be "200"

    # Test ordering by message_level
    Scenario: Test asc ordering by message_level
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "order_by" to string "message_level"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"

    Scenario: Test desc ordering by message_level
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "order_by" to string "message_level"
    And I set url param "order_desc" to string "true"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    
    # Test the component_id filter  
    Scenario: Test the component_id filter
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And Select a random asset and store component_id in var "c_id" 
    And I set url param "component_id" to variable "c_id"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"

    # Test the component_type filter
    Scenario: Test the component_type filter net
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "component_type" to string "net" 
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    #And I print request result
    # Alter the viewed flag


    Scenario: Test message (current) status update
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And Select a random asset and store component_id in var "c_id" 
    And I store asset type with component_id "c_id" in var "asset_type"
    And I select a random message and store id in  var "m_id"
    And I create or update a current_status entry with component_id "c_id" message id "m_id" asset type "asset_type" and viewed "0"
    And I store the id of current_status entry with component_id "c_id" message id "m_id" asset type "asset_type" in var "cs_id"
    And I make url with paths and store it in variable "url"
        |paths|
        |https://127.0.0.1:40011/av/api/1.0/data/status|
        |$cs_id|
    And I set url param "viewed" to string "true"
    When I send a PUT request to url stored in the variable "url"
    Then The http status code must be "200"

    Scenario: Test the level filter
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "page" to string "1"
    And I set url param "page_rows" to string "20"
    And I set url param "level" to string "warning"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    And I verify that all results has level equals to "warning"
    #And I print request result

    Scenario: Test the level filter with two variables
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I clean the status_message database 
    And I generate "100" current_status entries
    And I set url param "page" to string "1"
    And I set url param "page_rows" to string "20"
    And I set url param "level" to string "warning,error"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then The http status code must be "200"
    And I verify that all results has level equals to string list "warning,error"
    #And I print request result

    Scenario: Test combined filter: component_id and level
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I clean the status_message database
    And I generate "100" current_status entries
    And Select a random asset and store component_id in var "c_id"
    And I set url param "component_id" to variable "c_id"
    And I set url param "level" to string "info,error"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then I print request result
    And The http status code must be "200"
    And All responses must have component_id equals to var "c_id" and levels equals to "info,error"

    @wip
    # 
    Scenario: Test combined filter: component_id, level and component_type
    Given I log in the server "127.0.0.1" using a ghost administrator
    And I clean the status_message database
    And I generate "200" current_status entries
    And Select a random asset of type "host" and store component_id in var "c_id"
    And I set url param "component_id" to variable "c_id"
    And I set url param "level" to string "info,error,warning"
    And I set url param "component_type" to string "host"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/data/status"
    Then I print request result
    And The http status code must be "200"
    And All responses must be of type "host"
    And All responses must have component_id equals to var "c_id"
    And All responses must have level in "info,error,warning"
    


    
    





    





    
    



