<?php
/*
*   Copyright (C) 2010 Luka Sostaric. Luka's PHP Library is
*   distributed under the terms of the GNU General Public
*   License.
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
*   File Name: LDAPtoSquirrel.php
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

class LDAPtoSquirrel {
    private $ldapConnection;
    private $postgresConnection;
    public function __construct($ldapHost, $ldapUsername,
            $ldap_password, $postgresConnectionString) {
        $this->ldapConnection = new ldap($ldapHost, $ldapUsername,
        $ldap_password);
        $this->postgresConnection = new
        postgresql($postgresConnectionString);
    }
    public function copyUserPreferences($keyValuePairs, $username, $baseDN,
            $usernameColumn = "username", $prefkeyColumn = "prefkey",
            $prefvalColumn = "prefval", $tableName = "userprefs",
            $fullNameKey = "full_name", $emailKey = "email_address") {
        $filter = sprintf("(uid=%s)", $username);
        $userRecord = $this->ldapConnection->getRecords($baseDN, $filter);
        if($userRecord["count"] > 1) {
            debug_print_backtrace();
            echo("LDAP search query doesn't return a unique record.");
            exit();
        }
        $fullName = $userRecord[0]["cn"][0];
        $email = $userRecord[0]["mail"][0];
        $keyValuePairs[$fullNameKey] = $fullName;
        $keyValuePairs[$emailKey] = $email;
        $countQuery = sprintf("select count(*) as cnt from %s" .
        " where %s = '%s'", $tableName, $usernameColumn, $username);
        $result = $this->postgresConnection->execute($countQuery, "count");
        if($result[0]["cnt"] == 0) {
            $query = sprintf("insert into %s(%s, %s, %s) values('%s', $1, $2)",
            $tableName, $usernameColumn, $prefkeyColumn, $prefvalColumn,
            $username);
            foreach($keyValuePairs as $key => $value) {
                $this->postgresConnection->execute($query, "insertPreferences",
                array($key, $value));
            }
        }
        else {
            $keyValuePairs = array($fullNameKey => $keyValuePairs[$fullNameKey],
            $emailKey => $keyValuePairs[$emailKey]);
            $countQuery = sprintf("select count(*) as cnt from %s where %s = '%s'" .
            " and %s = $1", $tableName, $usernameColumn, $username,
            $prefkeyColumn);
            foreach($keyValuePairs as $key => $value) {
                $count = $this->postgresConnection->execute($countQuery, "keyCount",
                array($key));
                if($count[0]["cnt"] == 0) {
                    $insertQuery = sprintf("insert into %s(%s, %s, %s)" .
                    " values(%s, $2, $3)", $tableName, $usernameColumn,
                    $prefkeyColumn, $prefvalColumn, $username);
                    $this->postgresConnection->execute($insertQuery,
                    "insertNonexistentPreference", array($key, $value));
                }
                else {
                    $updateQuery = sprintf("update %s set %s = $1, %s = $2" .
                    " where %s = '%s' and %s = $3", $tableName, $prefkeyColumn,
                    $prefvalColumn, $usernameColumn, $username, $prefkeyColumn);
                    $this->postgresConnection->execute($updateQuery,
                    "updatePreferences", array($key, $value, $key));
                }
            }
        }
    }
    public function disconnect() {
        $this->ldapConnection->disconnect();
        $this->postgresConnection->disconnect();
    }
}
?>
