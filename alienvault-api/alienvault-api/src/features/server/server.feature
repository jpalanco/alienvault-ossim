Feature: Server blueprints tests
  As an API client
  I want to be able to verify all server operations
    Scenario: Test PUT /server/<server_id>/nfsen/reconfigure
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/server/local/nfsen/reconfigure"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
    And JSON response has key "data.rc" and value equals to string "0"


    Scenario: Test PUT /server/<server_id>/nfsen/reconfigure WITH WRONG FILE
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/server/local/nfsen/reconfigure"
    Then The http status code must be "500"
    And JSON response has key "status" and value equals to string "error"
    And JSON response has key "message"
    And JSON response has key "status_long_message"

