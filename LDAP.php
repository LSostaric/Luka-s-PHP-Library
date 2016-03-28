<?php

/*
*
*	Copyright (C) 2010 Luka Sostaric. Luka's PHP Library is
*	distributed under the terms of the GNU General Public
*	License.
*
*	This file is part of Luka's PHP Library.
*
*	It is free software: You can redistribute and/or modify
*	it under the terms of the GNU General Public License, as
*	published by the Free Software Foundation, either version
*	3 of the License, or (at your option) any later version.
*
*	It is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty
*	of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*	See the GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public
*	License along with Luka's PHP Library. If not, see
*	<http://www.gnu.org/licenses/>.
*
*	Software Information
*	--------------------
*	Software Name: Luka's PHP Library
*	File Name: LDAP.php
*	External Components Used: None
*	Required Files: None
*	License: GNU GPL
*
*	Author Information
*	------------------
*	Full Name: Luka Sostaric
*	E-mail: <luka@lukasostaric.com>
*	Website: <http://lukasostaric.com>
*
*/

class ldap {

	private $host;
	private $port;
	private $connection;
	private $constructor_error_code = 0x00;
	private static $debug = NULL;

	public function __construct($host, $dn = NULL,
	$password = NULL, $port = 389) {

		if(self::$debug === NULL) {

			self::$debug = ini_get("display_errors");

		}

		$this->host = $host;
		$this->port = $port;
		$this->connection = @ldap_connect($this->host, $this->port);

		if(!$this->connection) {

			debug_print_backtrace();

			echo("<br/>An error occurred while connecting to" .
			" LDAP server.");

			exit();

		}

		$outcome = @ldap_bind($this->connection, $dn, $password);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>Could not bind to LDAP server. Details: " .
			ldap_error($this->connection) . ".");

			$this->constructor_error_code = ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			$this->constructor_error_code = ldap_errno($this->connection);

		}

	}

	public function bind_as_another_user($dn = NULL, $password = NULL) {

		$outcome = @ldap_bind($this->connection, $dn, $password);

		if(!$outcome && self::$debug) {

			echo("<br/>An error occurred while trying to bind as" .
			" another user. Details: " . ldap_error($this->connection) . ".");

			return ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}
	}

	public function add_record($dn, $data) {

		$outcome = @ldap_add($this->connection, $dn, $data);
		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>An error occurred while" .
			" attempting to add a record to LDAP. Details: " .
			ldap_error($this->connection));

			return ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

		return 0x00;

	}

	public function delete_record($dn) {

		$outcome = @ldap_delete($this->connection, $dn);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>Unable to delete the specified" .
			" record from LDAP directory. Either it" .
			" doesn't exist or you don't have the" .
			" permission to delete it. Details: " .
			ldap_error($this->connection) . ".");

			return ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

		return 0x00;

	}

	public function delete_records($base_dn, $filter) {

		$records = $this->get_records($base_dn, $filter, array("dn"));

		for($i = 0; $i < $records["count"]; $i++) {

			$this->delete_record($records[$i]["dn"]);

		}

	}

	public function move_record($dn, $new_rdn, $new_parent,
	$delete_old_rdn = TRUE) {

		$outcome = @ldap_rename($dn, $new_rdn, $new_parent, $delete_old_rdn);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>An error occurred while trying to" .
			" move the specified record to another part" .
			" of the tree. Details: " .
			ldap_error($this->connection) . ".");

			return ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

		return 0x00;

	}

	public function get_records($base_dn, $filter, $attributes = array(),
	$attribute_types_only = 0, $max_result_count = 0, $time_limit = 0,
	$deref = LDAP_DEREF_NEVER) {

		$result_set = @ldap_search($this->connection, $base_dn, $filter,
		$attributes, $attribute_types_only, $max_result_count,
		$time_limit, $deref);

		if($result_set === FALSE && self::$debug) {

			debug_print_backtrace();

			echo("<br/>There was an error during the LDAP search. Details: " .
			ldap_error($this->connection));

			return ldap_errno($this->connection);

		}
		elseif($result_set === FALSE) {

			return ldap_errno($this->connection);

		}

		$records = @ldap_get_entries($this->connection, $result_set);

		if($records === FALSE && self::$debug) {

			debug_print_backtrace();

			echo("<br/>Couldn't fetch LDAP entries according to the given" .
			" result set. Details: " . ldap_error($this->connection));

		}
		elseif($records === FALSE) {

			return ldap_errno($this->connection);

		}

		return $records;

	}

	public function modify_record($dn, $data) {

		$outcome = ldap_modify($this->connection, $dn, $data);
		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>An error occurred while trying to modify" .
			" the LDAP record. Details: " .
			ldap_error($this->connection) . ".");

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

		return 0x00;

	}

	public function set_option($option, $value) {

		$outcome = ldap_set_option($this->connection, $option, $value);
		if(!$outcome && self::$debug) {

			echo("Couldn't set LDAP option. Details: " .
			ldap_error($this->connection . "."));

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

	}

	public function disconnect() {

		$outcome = @ldap_close($this->connection);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>Couldn't disconnect from LDAP server. Details: " .
			ldap_error($this->connection) . ".");

			exit();

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

	}

	public function toggle_debugging() {

		if(!self::$debug) {

			self::$debug = TRUE;

		}
		else {

			self::$debug = FALSE;

		}

	}

	public function print_debugging_status() {

		if(self::$debug) {

			echo("<br/>Debugging is enabled.");

		}
		else {

			echo("<br/>Debugging is disabled.");

		}

	}

	public function is_debugging_enabled() {

		if(self::$debug) {

			return TRUE;

		}
		else {

			return FALSE;

		}

	}
	public function turn_on_debugging() {

		if(!self::$debug) {

			self::$debug = TRUE;
			echo("<br/>Debugging turned on!");

		}
		else {

			echo("<br/>Couldn't enable debugging. It's" .
			" already been enabled.");

		}

	}

	public function turn_off_debugging() {

		if(!self::$debug) {

			echo("<br/>Couldn't disable debugging. It's" .
			" already been disabled.");

		}
		else {

			self::$debug = FALSE;

		}

	}

}

?>
