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

class imap {

	private $server;
	private $port;
	private $username;
	private $password;
	private $mailbox;
	private $flags;
	private $options;
	private $maximum_connection_attempts;
	private $parameters;
	private $connection;
	private $mailbox_string;
	private $server_part;
	private static $debug = NULL;

	public function __construct($host, $username,
	$password, $mailbox = "",
	$port = "", $flags = "", $options = 0,
	$connection_attempts = 0, $parameters = array()) {

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
		$this->maximum_connection_attempts = $connection_attempts;
		$this->parameters = $parameters;

		$this->mailbox_string = sprintf("{%s%s%s}%s", $this->server,
		$this->port, $this->flags, $this->mailbox);

		$this->server_part = sprintf("{%s%s%s}", $this->server,
		$this->port, $this->flags);

		$this->connection = imap_open($this->mailbox_string, $this->username,
		$this->password, $this->options,
		$this->maximum_connection_attempts,
		$this->parameters);

		if(!$this->connection && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying" .
			" to open IMAP connection. Details: %s.", imap_last_error());

			echo($error);
			exit();

		}

	}

	public function create_mailbox($mailbox_name) {

		$mailbox_name = $this->mailbox_string . $mailbox_name;
		$outcome = imap_createmailbox($this->connection, $mailbox_name);

		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("An error occurred while trying" .
			" to create a mailbox. Details: %s.", imap_last_error());

			echo($error);

		}
		elseif(!$outcome) {

			$last_error_message = imap_last_error();

			if($last_error_message == "Mailbox already exists") {

				return 1;

			}

		}

		return 0;

	}

	public function get_mailboxes($pattern = "*") {

		$mailboxes = imap_list($this->connection,
		$this->mailbox_string, $pattern);

		if(is_array($mailboxes)) {

			return $mailboxes;

		}
		elseif(self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" fetch mailboxes. Details: %s.", imap_last_error());

		}

	}

	public function rename_mailbox($old_mailbox_name, $new_mailbox_name) {

		$old = sprintf("{%s%s%s}%s", $this->server, $this->port, $this->flags,
		$old_mailbox_name);

		$new = sprintf("{%s%s%s}%s", $this->server, $this->port, $this->flags,
		$new_mailbox_name);

		$outcome = imap_renamemailbox($this->connection, $old, $new);
		if(!$outcome && self::$debug) {

			debug_print_backtrace();

			$error = sprintf("<br/>An error occurred while trying to" .
			" rename the given mailbox. Details: %s.", imap_last_error());

			echo($error);

			return 1;

		}
		elseif(!$outcome) {

			$last_error_message = imap_last_error();

			if($last_error_message === "Mailbox already exists") {

				return 1;

			}

		}

		return 0;

	}

	public function delete_mailbox($mailbox_name) {

		$mailbox_name = sprintf("{%s%s%s}%s", $this->server, $this->port,
		$this->flags, $mailbox_name);

		$outcome = imap_deletemailbox($this->connection, $mailbox_name);

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

	public function set_acl($mailbox_name, $user_id, $permissions) {

		$outcome = imap_setacl($this->connection, $mailbox_name,
		$user_id, $permissions);

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
