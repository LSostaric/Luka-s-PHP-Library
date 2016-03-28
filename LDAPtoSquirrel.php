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
*	File Name: LDAPtoSquirrel.php
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

class ldap_to_squirrel {

	private $ldap_connection;
	private $postgres_connection;

	public function __construct($ldap_host, $ldap_username,
	$ldap_password, $postgres_connection_string) {

		$this->ldap_connection = new ldap($ldap_host, $ldap_username,
		$ldap_password);

		$this->postgres_connection = new
		postgresql($postgres_connection_string);

	}

	public function copy_user_preferences($key_value_pairs, $username,
	$username_column = "username", $prefkey_column = "prefkey",
	$prefval_column = "prefval", $table_name = "userprefs") {

		$filter = sprintf("(uid=%s)", $username);

		$user_record = $this->ldap_connection->get_records("dc=agrokor,dc=hr",
		$filter);

		if($user_record["count"] > 1) {

			debug_print_backtrace();
			echo("LDAP search query doesn't return a unique record.");
			exit();

		}

		$full_name = $user_record[0]["cn"][0];
		$email = $user_record[0]["mail"][0];
		$key_value_pairs["full_name"] = $full_name;
		$key_value_pairs["email_address"] = $email;

		$count_query = sprintf("select count(*) as cnt from %s" .
		" where %s = '%s'", $table_name, $username_column, $username);

		$result = $this->postgres_connection->execute($count_query, "count");

		if($result[0]["cnt"] == 0) {

			$query = sprintf("insert into %s(%s, %s, %s) values('%s', $1, $2)",
			$table_name, $username_column, $prefkey_column, $prefval_column,
			$username);

			foreach($key_value_pairs as $key => $value) {

				$this->postgres_connection->execute($query, "insert_preferences",
				array($key, $value));

			}

		}
		else {

			$key_value_pairs = array("full_name" => $key_value_pairs["full_name"],
			"email_address" => $key_value_pairs["email_address"]);

			$count_query = sprintf("select count(*) as cnt from %s where %s = '%s'" .
			" and %s = $1", $table_name, $username_column, $username,
			$prefkey_column);

			foreach($key_value_pairs as $key => $value) {

				$count = $this->postgres_connection->execute($count_query, "key_count",
				array($key));

				if($count[0]["cnt"] == 0) {

					$insert_query = sprintf("insert into %s(%s, %s, %s)" .
					" values(%s, $2, $3)", $table_name, $username_column,
					$prefkey_column, $prefval_column, $username);

					$this->postgres_connection->execute($insert_query,
					"insert_nonexistent_preference", array($key, $value));

				}
				else {

					$update_query = sprintf("update %s set %s = $1, %s = $2" .
					" where %s = '%s' and %s = $3", $table_name, $prefkey_column,
					$prefval_column, $username_column, $username, $prefkey_column);

					$this->postgres_connection->execute($update_query,
					"update_preferences", array($key, $value, $key));

				}

			}

		}

	}

	public function disconnect() {

		$this->ldap_connection->disconnect();
		$this->postgres_connection->disconnect();

	}

}

?>
