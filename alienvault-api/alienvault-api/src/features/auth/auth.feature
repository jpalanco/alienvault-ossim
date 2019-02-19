Feature: Auth operations
  As an API client
  I want to be able to verify all authorization: operations



	Scenario: Try to login into the system
	Given I set url param "username" to key "admin_user" from static vault
	And I set url param "password" to key "admin_pass" from static vault
	When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/login"
	Then The http status code must be "200"
	And The returned JSON must be "{"data":null,        "status":"success"}"

	Scenario: Try to logout from the system
	Given I set username and password to ghost administrator
	And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
	When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/logout"
	Then The http status code must be "200"
	And The returned JSON must be "{"data":null,  "status":"success"}"
	
	Scenario: Try to login with a unknown user
	Given I generate a random string with len "8" and store in vault key "random_password"
	And I generate a non-existent username with len "8" and store in vault key "random_user"
	And I set url param "username" to key "random_user" from vault
	And I set url param "password" to key "random_password" from vault
	When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/login"
	Then The http status code must be "401"

	Scenario: Try to logout without login cookie
	Given I set username and password to ghost administrator
	And I log into the ossim API using "https://127.0.0.1:40011/av/api/1.0/auth/login"
	And I clear the cookies
	When I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/logout"
	Then The http status code must be "401"

	Scenario: Try to login without username param
    	Given I generate a random string with len "8" and store in vault key "random_password"
	    And I set url param "password" to key "random_password" from vault
    	When  I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/login"
	    Then The http status code must be "400"
    @wip
    Scenario: Try to login without password param
	Given I generate a random string with len "8" and store in vault key "random_user"
	    And I set url param "username" to key "random_user" from vault
    	When  I send a GET request to url "https://127.0.0.1:40011/av/api/1.0/auth/login"
	    Then The http status code must be "400"

    

	


	



