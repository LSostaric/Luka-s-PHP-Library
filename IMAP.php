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
*	File Name: IMAP.php
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

class IMAP {

	private $server;
	private $port;
	private $username;
	private $password;
	private $mailbox;
	private $flags;
	private $options;
	private $maximumConnectionAttempts;
	private $parameters;
	private $connection;
	private $mailboxString;
	private $serverPart;
	private static $debug = NULL;

	public function __construct($host, $username,
	$password, $mailbox = "",
	$port = "", $flags = "", $options = 0,
	$connectionAttempts = 0, $parameters = array()) {

		if(self::$debug === NULL) {

			self::$debug = ini_get("display_errors");

		}

		$this->server = $host;

		if($port != "") {

			$this->port = ":" . $port;

		}

		if($mailbox != "") {

			$this->mailbox = $mailbox;

		}

		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox;
		$this->flags = $flags;
		$this->options = $options;
		$this->maximumConnectionAttempts = $connectionAttempts;
		$this->parameters = $parameters;

		$this->mailboxString = sprintf("{%s%s%s}%s", $this->server,
		$this->port, $this->flags, $this->mailbox);

		$this->serverPart = sprintf("{%s%s%s}", $this->server,
		$this->port, $this->flags);

		$this->connection = imap_open($this->mailboxString, $this->username,
		$this->password, $this->options,
		$this->maximumConnectionAttempts,
		$this->parameters);

		if(!$this->connection && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying" .
			" to open IMAP connection. Details: %s.", imap_last_error());

			echo($error);
			exit();

		}

	}

	public function createMailbox($mailboxName) {

		$mailboxName = $this->mailboxString . $mailboxName;
		$outcome = imap_createmailbox($this->connection, $mailboxName);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("An error occurred while trying" .
			" to create a mailbox. Details: %s.", imap_last_error());

			echo($error);

		}
		elseif(!$outcome) {

			$lastErrorMessage = imap_last_error();

			if($lastErrorMessage == "Mailbox already exists") {

				return 1;

			}

		}

		return 0;

	}

	public function getMailboxes($pattern = "*") {

		$mailboxes = imap_list($this->connection,
		$this->mailboxString, $pattern);

		if(is_array($mailboxes)) {

			return $mailboxes;

		}
		elseif(self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" fetch mailboxes. Details: %s.", imap_last_error());

		}

	}

	public function renameMailbox($oldMailboxName, $newMailboxName) {

		$old = sprintf("{%s%s%s}%s", $this->server, $this->port, $this->flags,
		$oldMailboxName);

		$new = sprintf("{%s%s%s}%s", $this->server, $this->port, $this->flags,
		$newMailboxName);

		$outcome = imap_renamemailbox($this->connection, $old, $new);
		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" rename the given mailbox. Details: %s.", imap_last_error());

			echo($error);

			return 1;

		}
		elseif(!$outcome) {

			$lastErrorMessage = imap_last_error();

			if($lastErrorMessage === "Mailbox already exists") {

				return 1;

			}

		}

		return 0;

	}

	public function deleteMailbox($mailboxName) {

		$mailboxName = sprintf("{%s%s%s}%s", $this->server, $this->port,
		$this->flags, $mailboxName);

		$outcome = imap_deletemailbox($this->connection, $mailboxName);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" rename the given mailbox. Details: %s.", imap_last_error());

			return 1;

		}
		elseif(!$outcome) {

			return 1;

		}

		return 0;

	}

	public function setACL($mailboxName, $userID, $permissions) {

		$outcome = imap_setacl($this->connection, $mailboxName,
		$userID, $permissions);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" set permissions on the mailbox. Details: %s.",
			imap_last_error());

			echo($error);

		}
		elseif(!$outcome) {

			return 1;

		}

		return 0;

	}

	public function disconnect($flag = 0) {

		$outcome = imap_close($this->connection, $flag);
		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>Unable to disconnect from" .
			" the IMAP server. Details: %s.", imap_last_error());

			echo($error);

			exit();

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
