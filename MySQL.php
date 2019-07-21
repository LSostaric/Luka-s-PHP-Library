<?php
/*
*   Copyright (C) 2010 Luka Sostaric. Luka's PHP Library is
*   distributed under the terms of the GNU General Public
* License.
*
*   This file is part of Luka's PHP Library.
*
*   It is free software: You can redistribute and/or modify
*   it under the terms of the GNU General Public License, as
*   published by the Free Software Foundation, either version
*   3 of the License, or (at your option) any later version.
*
*   It is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty
*   of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*   See the GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public
*   License along with Luka's PHP Library. If not, see
*   <http://www.gnu.org/licenses/>.
*
*   Software Information
*   --------------------
*   Software Name: Luka's PHP Library
*   File Name: MySQL.php
*   External Components Used: None
*   Required Files: None
*   License: GNU GPL
*
*   Author Information
*   ------------------
*   Full Name: Luka Sostaric
*   E-mail: luka@lukasostaric.com
*   Website: www.lukasostaric.com
*/

class MySQL extends mysqli {
    public function __get($property) {
        if( function_exists("property_exists") ) {
            if(property_exists($this, $property)) {
                return $this->$property;
            }
            debug_print_backtrace();
            die("A nonexistent property has been requested. " .
            __LINE__ . " " . __METHOD__ . " " . __FILE__);
        }
        else {
            return $this->$property;
        }
    }
    public function __construct($server, $username, $password,
                $database, $charset="utf8") {
        parent::__construct($server, $username, $password, $database);
        if($this->connect_errno!=0) {
            debug_print_backtrace();
            die("MySQL connection error occurred. " . $this->connect_error);
        }
        if(method_exists($this, "set_charset")) {
            $result = $this->set_charset($charset);
        }
        else {
            $result = $this->query("SET NAMES '$charset'");
        }
        if(!$result) {
            debug_print_backtrace();
            die("Couldn't set the character set: " . $charset . ".");
        }
    }
    public function execute($sqlQuery, $values = NULL, $format = NULL) {
        $statement = parent::prepare($sqlQuery);
        if($statement === FALSE) {
            debug_print_backtrace();
            die("MySQL query preparation failure. " . $this->error);
        }
        if($values != NULL && $format != NULL) {
            $this->bindParamsArray($statement, $values, $format);
        }
        $outcome = $statement->execute();
        if($outcome === FALSE) {
            debug_print_backtrace();
            die("MySQL query execution failure. ".$statement->error . " "
            . __LINE__ ." " . __METHOD__ . " " . __FILE__);
        }
        if($statement->result_metadata() !== FALSE) {
            $boundResults = $this->bindResultArray($statement);
            $results = array();
            $i=0;
            while(($outcome = $statement->fetch())) {
                if($outcome === FALSE) {
                    debug_print_backtrace();
                    die("Result fetching failure. ".$statement->error .
                    __LINE__ . " " . __METHOD__ . " " . __FILE__);
                }
                foreach($boundResults as $name => $value) {
                    $row[$name] = $value;
                }
                $results[$i] = $row;
                $i++;
            }
        }
        else {
            $results = NULL;
        }
        $statement->close();
        return $results;
    }
    private function bindResultArray($statement) {
        $metadata = $statement->result_metadata();
        $columns = $metadata->fetch_fields();
        if(!$metadata || !$columns) {
            debug_print_backtrace();
            die("Metadata failure. " . " " . $statement->error
            . __LINE__ . " " . __METHOD__ . " " . __FILE__);
        }
        foreach($columns as $key => $column) {
            $values[$column->name] = NULL;
            $references[$key] = &$values[$column->name];
        }
        $outcome = call_user_func_array(
            array($statement, "bind_result"),
            $references
        );
        if($outcome === FALSE) {
            debug_print_backtrace();
            die("Call user function array failure. "
            . __LINE__ . " " . __METHOD__ . " " . __FILE__);
        }
        $metadata->close();
        return $values;
    }
    private function bindParamsArray($statement, $bindings, $format) {
        foreach($bindings as $key => $binding) {
            $references[$key] = &$bindings[$key];
        }
        array_unshift($references, $format);
        $outcome = call_user_func_array(array($statement, "bind_param"),
        $references);
        if($outcome === FALSE) {
            debug_print_backtrace();
            die("Call user function array failure. " .
            __LINE__ . " " . __METHOD__ . " " . __FILE__);
        }
    }
}
?>
