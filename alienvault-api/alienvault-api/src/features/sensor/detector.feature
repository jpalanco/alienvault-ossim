Feature: Sensor detector operations

  Scenario: Get the plugin list within the config.yml
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/plugins/asset/enabled"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
	And JSON response has key "data.plugins.ae298b1a-af3f-11e3-9452-c242e4cca549.apache.plugin_id" and value equals to string "1501"
    And JSON response has key "data.plugins.ae298b1a-af3f-11e3-9452-c242e4cca549.cisco-pix.plugin_id" and value equals to string "1514"
    And JSON response has key "data.plugins.ae298b1a-af3f-11e3-9452-c242e4cca548.cisco-asa.plugin_id" and value equals to string "1636"
    And JSON response has key "data.plugins.ae298b1a-af3f-11e3-9452-c242e4cca548.pam_unix.plugin_id" and value equals to string "4004"
    And JSON response has key "data.plugins.ae298b1a-af3f-11e3-9452-c242e4cca548.ssh.plugin_id" and value equals to string "4003"

  @wip
  Scenario: Set a new plugin list - with fake plugins parameter
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I add POST data key "plugins","ll"
    When I send a POST request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/plugins/asset/enabled"
    Then The http status code must be "400"
    And JSON response has key "status" and value equals to string "error"
  @wip
  Scenario: Set a new plugin list - with fake plugin
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I set url param "plugins" to string "{"ae298b1a-af3f-11e3-9452-c242e4cca549":{},"ae298b1a-af3f-11e3-9452-c242e4cca548":{"pam_unix-fake":"cpe:/a:cpe_data","ssh":"cpe:/a:cpe_data"}}"
    When I send a PUT request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/plugins/detector/enabled"
    Then The http status code must be "500"
    And JSON response has key "status" and value equals to string "error"
    And JSON response has key "message" and value equals to string "Error setting sensor detector plugins"

  Scenario: Set a new plugin list
    Given I set username and password to ghost administrator
    And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
    And I add POST data key "plugins","{"00000000-0000-0000-0000-00000000aaaa":["apache", "cisco-asa"],"00000000-0000-0000-0000-00000000bbbb":["pam_unix", "ssh"]}"
    When I send a POST request to url "https://127.0.0.1:40011/av/api/1.0/sensor/local/plugins/asset/enabled"
    Then The http status code must be "200"
    And JSON response has key "status" and value equals to string "success"
