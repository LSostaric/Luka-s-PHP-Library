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

class LDAP {

	private $host;
	private $port;
	private $connection;
	private $constructorErrorCode = 0x00;
	private $elementsPerPage = 20;
	private $baseDN;
	private static $debug = NULL;

	public function __construct($host, $dn = NULL,
	$password = NULL, $port = 389, $startTLS = TRUE) {

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

		if($startTLS) {

			$result = @ldap_start_tls($this->connection);

			if(!$result) {

				debug_print_backtrace();

				echo("<br/>Could not issue the STARTTLS command!");
				exit();

			}

		}

		$outcome = @ldap_bind($this->connection, $dn, $password);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			echo("<br/>Could not bind to LDAP server. Details: " .
			ldap_error($this->connection) . ".");

			$this->constructorErrorCode = ldap_errno($this->connection);

		}
		elseif(!$outcome) {

			$this->constructorErrorCode = ldap_errno($this->connection);

		}

	}

	public function bindAsAnotherUser($dn = NULL, $password = NULL) {

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

	public function addRecord($dn, $data) {

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

	public function deleteRecord($dn) {

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

	public function deleteRecords($baseDN, $filter) {

		$records = $this->getRecords($baseDN, $filter, array("dn"));

		for($i = 0; $i < $records["count"]; $i++) {

			$this->deleteRecord($records[$i]["dn"]);

		}

	}

	public function moveRecord($dn, $newRDN, $newParent,
	$deleteOldRDN = TRUE) {

		$outcome = @ldap_rename($dn, $newRDN, $newParent, $deleteOldRDN);

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

	public function getRecords($baseDN, $filter, $attributes = array(),
	$attributeTypesOnly = 0, $maxResultCount = 0, $timeLimit = 0,
	$deref = LDAP_DEREF_NEVER) {

		$resultSet = @ldap_search($this->connection, $baseDN, $filter,
		$attributes, $attributeTypesOnly, $maxResultCount,
		$timeLimit, $deref);

		if($resultSet === FALSE && self::$debug) {

			debug_print_backtrace();

			echo("<br/>There was an error during the LDAP search. Details: " .
			ldap_error($this->connection));

			return ldap_errno($this->connection);

		}
		elseif($resultSet === FALSE) {

			return ldap_errno($this->connection);

		}

		$records = @ldap_get_entries($this->connection, $resultSet);

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

	public function fetchPage($pageNumber, $filter,
	$attributes = array(), $attrsOnly = 0, $timeLimit = 0,
	$deref = LDAP_DEREF_NEVER) {

		$records = $this->getRecords($this->baseDN, $filter,
		$attributes, $attrsOnly, $this->elementsPerPage * $pageNumber,
		$timeLimit, $deref);

		$totalCount = $records["count"];
		$offset = ($pageNumber - 1) * $this->elementsPerPage;

		if($offset + $this->elementsPerPage - 1 > $totalCount - 1) {

			$length = NULL;

		}
		else {

			$length = $this->elementsPerPage;

		}

		return array_slice($records, $offset, $length);

	}

	public function modifyRecord($dn, $data) {

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

	public function setOption($option, $value) {

		$outcome = ldap_set_option($this->connection, $option, $value);
		if(!$outcome && self::$debug) {

			echo("Couldn't set LDAP option. Details: " .
			ldap_error($this->connection . "."));

		}
		elseif(!$outcome) {

			return ldap_errno($this->connection);

		}

	}

	public function setElementsPerPage($noe) {

		$this->elementsPerPage = $noe;

	}

	public function setBaseDN($baseDN) {

		$this->baseDN = $baseDN;

	}

	public function escape($value) {

		$searches = array("\\", "\\0", "*", "(", ")");
		$replacements = array("\\0x5c", "\\0x00", "\\0x2a", "\\0x28", "\\0x29");

		return str_replace($searches, $replacements);

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

	public function toggleDebugging() {

		if(!self::$debug) {

			self::$debug = TRUE;

		}
		else {

			self::$debug = FALSE;

		}

	}

	public function printDebuggingStatus() {

		if(self::$debug) {

			echo("<br/>Debugging is enabled.");

		}
		else {

			echo("<br/>Debugging is disabled.");

		}

	}

	public function isDebuggingEnabled() {

		if(self::$debug) {

			return TRUE;

		}
		else {

			return FALSE;

		}

	}
	public function turnOnDebugging() {

		if(!self::$debug) {

			self::$debug = TRUE;
			echo("<br/>Debugging turned on!");

		}
		else {

			echo("<br/>Couldn't enable debugging. It's" .
			" already been enabled.");

		}

	}

	public function turnOffDebugging() {

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
