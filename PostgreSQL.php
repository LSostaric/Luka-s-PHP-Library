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
*	File Name: PostgreSQL.php
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

class postgresql {

	private $link;
	private $query_names = array();

	public function __construct($connection_string) {

		$this->link = pg_connect($connection_string);

		if(!$this->link) {

			debug_print_backtrace();

			$error = sprintf("An error occurred while" .
			" establishing a database connection." .
			" Details: %s.", pg_last_error());

			echo($error);
			exit();

		}

	}

	public function execute($query, $query_name, $parameters = array()) {

		$results = array();

		if(!(isset($this->query_names[$query_name]) ||
		array_key_exists($query_name, $this->query_names))) {

			if(!pg_connection_busy($this->link)) {

				$outcome = pg_send_prepare($this->link, $query_name, $query);
				$this->query_names[$query_name] = $query_name;
				$result = pg_get_result($this->link);

			}

			if(($error = pg_last_error($this->link)) || $outcome === FALSE) {

				debug_print_backtrace();

				$error = sprintf("Query preparation failed. Query: %s." .
				" Details: %s.", $query, $error);

				echo($error);
				exit();

			}

		}

		if(!pg_connection_busy($this->link)) {

			$outcome = pg_send_execute($this->link, $query_name, $parameters);

			if(($error = pg_last_error($this->link)) || $outcome === FALSE) {

				debug_print_backtrace();

				$error = sprintf("An error occurred while executing" .
				" the following query: %s. Details: %s.", $query, $error);

				echo($error);
				exit();

			}

			$result=pg_get_result($this->link);
			$i=0;

			while(($row = pg_fetch_assoc($result)) !== FALSE) {

				$results[$i] = $row;
				$i++;

			}

		}

		return $results;

	}

	public function fetch_page($query, $page_number, $parameters = NULL,
	$elements_per_page = 10, $sort_by = "date_and_time") {

		if($parameters === NULL) {

			$parameters = array();

		}

		array_push($parameters, $sort_by, $elements_per_page,
		($page_number - 1) * $elements_per_page);

		$dollar_count = substr_count($query, "$");
		$dollar_numbers = array();

		for($i = $dollar_count, $j = 0; $j < 3; $i++, $j++) {

			array_push($dollar_numbers, $i + 1);

		}

		$query_string = sprintf("%s ORDER BY $%d LIMIT $%d OFFSET $%d",
		$query, $dollar_numbers[0], $dollar_numbers[1], $dollar_numbers[2]);

		$x = $this->execute($query_string, "fetch_page", $parameters);

		return $x;

	}

	public function disconnect() {

		pg_close($this->link);

	}

}

?>
