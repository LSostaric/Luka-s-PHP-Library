<?php
class LDAP {
    private $host;
    private $port;
    private $connection;
    private $constructorErrorCode = 0x00;
    private $elementsPerPage = 20;
    private $baseDN;
    private $lastPageFilter;
    private static $debug = NULL;
    public function __construct($host, $dn = NULL,
            $password = NULL, $port = 389, $startTLS = TRUE,
            $options=array()) {
        if(self::$debug === NULL) {
            self::$debug = ini_get("display_errors");
        }
        $this->host = $host;
        $this->port = $port;
        $this->connection = ldap_connect($this->host, $this->port);
        if(!$this->connection && self::$debug) {
            debug_print_backtrace();
            echo("<br/>An error occurred while connecting to" .
                " LDAP server.");
            exit();
        }
        elseif(!$this->connection) {
            $this->constructorErrorCode = ldap_errno($this->connection);
        }
        if(!empty($options)) {
            $this->setOptions($options);
        }
        if($startTLS) {
            $result = ldap_start_tls($this->connection);
            if(!$result && self::$debug) {
                debug_print_backtrace();
                echo("<br/>Could not issue the STARTTLS command! " .
                    ldap_error($this->connection) . ".");
                exit();
            }
            elseif(!$result) {
                $this->constructorErrorCode = ldap_errno($this->connection);
            }
        }
        $outcome = ldap_bind($this->connection, $dn, $password);
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
        else {
            return ldap_errno($this->connection);
        }
    }

    public function addRecord($dn, $data) {
        $outcome = ldap_add($this->connection, $dn, $data);
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
        $outcome = ldap_delete($this->connection, $dn);
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
            $outcome = $this->deleteRecord($records[$i]["dn"]);
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
        }
        return 0x00;
    }

    public function removeAttributeValuesFromRecords($filter, $avPairs) {
        $records = $this->getRecords($this->baseDN, $filter);
        for($i = 0; $i < $records["count"]; $i++) {
            ldap_mod_del($this->connection, $records[$i]["dn"], $avPairs);
        }
    }

    public function moveRecord($dn, $newRDN, $newParent,
        $deleteOldRDN = TRUE) {
        $outcome = ldap_rename($this->connection, $dn, $newRDN, $newParent, $deleteOldRDN);
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
        $records = ldap_get_entries($this->connection, $resultSet);
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
        $this->lastPageFilter = $filter;
        $records = $this->getRecords($this->baseDN, $filter,
            $attributes, $attrsOnly, $this->elementsPerPage * $pageNumber,
            $timeLimit, $deref);
        $totalCount = $records["count"];
        unset($records["count"]);
        $offset = ($pageNumber - 1) * $this->elementsPerPage;
        if($offset + $this->elementsPerPage - 1 > $totalCount - 1) {
            return array_slice($records, $offset);
        }
        else {
            return array_slice($records, $offset, $this->elementsPerPage);
        }
    }

    public function countFilterResults($filter, $resultsLimit = 0,
            $timeLimit = 0, $deref = LDAP_DEREF_NEVER) {
        $resultIdentifier = ldap_search($this->connection,
            $this->baseDN, $filter, array(), 1, $resultsLimit, $timeLimit, $deref);
        return ldap_count_entries($this->connection, $resultIdentifier);
    }

    public function getUniqueNumber($attribute = "uidNumber", $baseDN = NULL, $minimum) {
        if($baseDN == NULL) {
            $baseDN = $this->baseDN;
        }
        $results = $this->getRecords($baseDN, "($attribute=*)", array($attribute));
        unset($results["count"]);
        $i=0;
        $temp = array();
        foreach($results as $key => $value) {
            $temp[$i] = $value[$attribute][0];
            $i++;
        }
        $current = $minimum;
        foreach($temp as $value) {
            if(!in_array($current, $temp)) {
                return $current;
            }
            $current += 1;
        }
    }

    public function getNumberOfPages($filter = NULL) {
        if($filter == NULL) {
            $filter = $this->lastPageFilter;
        }
        return ceil($this->countFilterResults($filter) / $this->elementsPerPage);
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

    public function replaceAttributeValues($baseDN, $filter, $rules) {
        $records = $this->getRecords($baseDN, $filter);
        unset($records["count"]);
        foreach($records as $record) {
            foreach($rules as $key => $rule) {
                $data[$key] = $record[$key];
                unset($data[$key]["count"]);
                foreach($rule as $pair) {
                    $index = array_search($pair[0], $data[$key]);
                    if($index !== FALSE) {
                        $data[$key][$index] = $pair[1];
                    }
                }
            }
            $this->modifyRecord($record["dn"], $data);
        }
    }

    private function setOptions($options) {
        foreach($options as $option => $value) {
            $outcome = ldap_set_option($this->connection, $option, $value);
            if(!$outcome && self::$debug) {
                echo("Couldn't set LDAP option. Details: " .
                    ldap_error($this->connection . "."));
                return ldap_errno($this->connection);
            }
            elseif(!$outcome) {
                return ldap_errno($this->connection);
            }
        }
    }

    public function getOption($option) {
        $outcome = ldap_get_option($this->connection, $option, $value);
        if(!$outcome && self::$debug) {
            echo("Couldn't get LDAP option. Details: " .
                ldap_error($this->connection . "."));
            return ldap_errno($this->connection);
        }
        elseif(!$outcome) {
            return ldap_errno($this->connection);
        }
        return $value;
    }

    public function setElementsPerPage($noe) {
        $this->elementsPerPage = $noe;
    }

    public function getElementsPerPage() {
        return $this->elementsPerPage;
    }

    public function setBaseDN($baseDN) {
        $this->baseDN = $baseDN;
    }

    public static function escape($value) {
        $searches = array("\\", "\\0", "*", "(", ")");
        $replacements = array("\\0x5c", "\\0x00", "\\0x2a", "\\0x28", "\\0x29");
        return str_replace($searches, $replacements, $value);
    }

    public function disconnect() {
        $outcome = ldap_close($this->connection);
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

    public static function getSubtree($dn, $startLevel = 1) {
        $components = ldap_explode_dn($dn, 0);
        unset($components["count"]);
        for($i = 0; $i < $startLevel; $i++) {
            unset($components[$i]);
        }
        return implode(",", $components);
    }
}
?>
