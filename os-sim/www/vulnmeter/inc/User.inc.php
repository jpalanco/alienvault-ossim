<?php
/*
* Class for managing user data
*/

class User {

	var $groups;  // array of groups user is a member of
	var $roles;  // array of roles that the user has
	var $email;  // email address
	var $name;   // users full name
	var $login;  // users login ID
	var $lastLogin;  // last login date for user
	var $locked;    // account is locked or not
	var $dbh;    // the database handle to use

	// constructor
	function User($uname, $dbh) {
		$this->login = $uname;
		$this->dbh = $dbh;
	}

	// function to return the users currently defined roles
	function getRoles() {
		return false;
	}

	// generic function to get any attribute about the user
	function getAttr($attr) {
		return false;
	}


	// function to get the users group memberships
	function getGroups() {
		return false;
	}
}
?>