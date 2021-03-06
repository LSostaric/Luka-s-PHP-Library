<?php

function sanitizeString($string) {
    $searches = array('&quot;', '!', '@', '#', '$', '%', '^',
        '&', '*', '(', ')', '+', '{', '}', '|', ':', '"',
        '<', '>', '?', '[', ']', '', ';', "'", ',', '.',
        '_', '/', '*', '+', '~', '`', '=', ' ' ,'---', '--', '--');
    $replacements = array('', '-', '-', '', '' ,'', '-', '-', '',
        '', '', '', '', '', '', '-', '', '', '', '', '', '',
        '', '', '-', '-', '', '-', '-', '', '', '', '', '',
        '-', '-', '-', '-');
    $sanitizedString = str_replace($searches, $replacements, $string);
    return $sanitizedString;
}

function markdownToHTML($markdown) {
    preg_match_all("/\((\d+)\)\[(.+)\]/", $markdown, $numbers);
    $searchings = array("/\((\d+)\)\[(.+)\]/", "/(.+?)(\n|\r\n)=+/",
        "/(.+?)(\n|\r\n)-+/", "/(\n|\r\n){2}(.+)/", "/\*\*(.+?)\*\*/",
        "/\*(.+?)\*/");
    $replacements = array("", "<h1>$1</h1>", "<h2>$1</h2>",
        "<p>$2</p>", "<strong>$1</strong>", "<em>$1</em>");
    $html = preg_replace($searchings, $replacements, $markdown);
    foreach($numbers[1] as $key => $number) {
        $searchings[$key] = "/\[(.+)\]\(" . $number . "\)/";
        $replacements[$key] = "<a href=\"" . $numbers[2][$key] . "\">$1</a>";
    }
    array_push($searchings, "/  \* (.+)(\n|\r\n)/",
        "/(<li>.+<\/li>)/", "/  \d+\. (.+)(\n|\r\n)/",
        "/(<li1>.+<\/li1>)/", "/<li1>(.+?)<\/li1>/", "/---/");
    array_push($replacements, "<li>$1</li>", "<ul>$1</ul>",
        "<li1>$1</li1>", "<ol>$1</ol>", "<li>$1</li>","&mdash;");
    $html = preg_replace($searchings, $replacements, $html);
    return $html;
}

function decomposeFilePath($path) {
    $lastDot = strrpos($path, ".");
    $extension = substr($path, $lastDot + 1);
    $lastSlash = strrpos($path, "/");
    $fileName = substr($path, $lastSlash + 1, $lastDot - $lastSlash - 1);
    $directory = substr($path, 0, $lastSlash + 1);
    $result = array("extension" => $extension, "fileName" => $fileName,
        "directory" => $directory);
    return $result;
}

function getStringBetween($string, $start, $end, $trim=TRUE) {
    $results = array();
    $offset = strpos($string, $start);
    $startLength = strlen($start);
    $endLength = strlen($end);
    while( $offset !== FALSE ) {
        $startIndex = strpos($string, $start, $offset);
        $endIndex = strpos($string, $end, $offset+$startLength);
        if($trim) {
            $result = trim(substr($string, $startIndex + $startLength,
                $endIndex-$startIndex - $startLength));
        }
        else {
            $result = substr($string, $startIndex + $startLength,
                $endIndex-$startIndex - $startLength);
        }
        array_push($results, $result);
        $offset = strpos($string, $start, $endIndex + $endLength);
    }
    if(empty($results)) {
        $results[0] = $string;
    }
    return $results;
}

function trimArrayOfStrings(&$array) {
    if(!is_array($array)) {
        return trim($array);
    }
    if(!empty($array)) {
        foreach($array as $key => $element) {
            $array[$key] = trim($element);
        }
    }
}

function getRefererPath() {
    $referer = srvget("HTTP_REFERER");
    if(strpos($referer, "https://") !== FALSE) {
        $referer = substr($referer, 8);
    }
    elseif(strpos($referer, "http://") !== FALSE) {
        $referer = substr($referer, 7);
    }
    return "/" . getStringAfterSubstring($referer, "/");
}

function performFileUpload($fieldName, $destination, $debug = FALSE) {
    if(ini_get("display_errors") === "1") {
        $debug = TRUE;
    }
    if(is_array($_FILES[$fieldName]["name"])) {
        for($i = 0; $i < count($_FILES[$fieldName]["name"]); $i++) {
            $temporaryName = $_FILES[$fieldName]["tmp_name"][$i];
            $name = $_FILES[$fieldName]["name"][$i];
            move_uploaded_file($temporaryName, "$destination/$name");
        }
    }
    else {
        $temporaryName = $_FILES[$fieldName]["tmp_name"];
        $name = $_FILES[$fieldName]["name"];
        move_uploaded_file($temporaryName, "$destination/$name");
    }
    $outcome = $_FILES[$fieldName]["error"];
    switch($outcome) {
        case UPLOAD_ERR_OK:
            if($debug) {
                echo("There is no error: The file uploaded with success.");
                return 0;
            }
            return 0;
        break;
        case UPLOAD_ERR_INI_SIZE:
            if($debug) {
                echo("The uploaded file exceeds the upload_max_filesize" .
                    " directive in php.ini.");
                return 1;
            }
            return 1;
        break;
        case UPLOAD_ERR_FORM_SIZE:
            if($debug) {
                echo("The uploaded file exceeds the MAX_FILE_SIZE directive" .
                    " specified in the HTML form.");
                return 2;
            }
            return 2;
        break;
        case UPLOAD_ERR_PARTIAL:
            if($debug) {
                echo("The uploaded file was only partially uploaded.");
                return 3;
            }
            return 3;
        break;
        case UPLOAD_ERR_NO_FILE:
            if($debug) {
                echo("No file was uploaded.");
                return 4;
            }
            return 4;
        break;
    }
}

function gset($name, $value=NULL) {
    if($value===NULL) {
        return isset($_GET[$name]);
    }
    else {
        $_GET[$name] = $value;
    }
}

function gget($name) {
    if( isset($_GET[$name]) ) {
        return $_GET[$name];
    }
    return NULL;
}

function pset($name, $value=NULL) {
    if($value===NULL) {
        return isset($_POST[$name]);
    }
    else {
        $_POST[$name] = $value;
    }
}

function pget($name) {
    if( isset($_POST[$name]) ) {
        return $_POST[$name];
    }
    return NULL;
}

function sset($name, $value=NULL) {
    if($value===NULL) {
        return isset($_SESSION[$name]);
    }
    else {
        $_SESSION[$name] = $value;
    }
}

function sget($name) {
    if( isset($_SESSION[$name]) ) {
        return $_SESSION[$name];
    }
    return NULL;
}

function srvset($name, $value=NULL) {
    if($value===NULL) {
        return isset($_SERVER[$name]);
    }
    else {
        $_SERVER[$name] = $value;
    }
}

function srvget($name) {
    if( isset($_SERVER[$name]) ) {
        return $_SERVER[$name];
    }
    return NULL;
}

function getStringAfterSubstring($string, $substring) {
    $substringPosition = strpos($string, $substring);
    return substr($string, $substringPosition + 1);
}

function getStringBeforeSubstring($string, $substring) {
    $substringPosition = strpos($string, $substring);
    return substr($string, 0, $substringPosition + 1);
}

function replaceWithAsciiEquivalents($string) {
    $searches = array('Š', 'Đ', 'Č', 'Ć', 'Ž', 'š',
        'đ', 'č', 'ć', 'ž');
    $replacements = array('S', 'D', 'C', 'C', 'Z', 's',
        'd', 'c', 'c', 'z');
    return str_replace($searches, $replacements, $string);
}

function executeExternalCommand($command, $arguments, &$output = array()) {
    $count = count($arguments);
    for($i = 0; $i < $count; $i++) {
        $arguments[$i] = escapeshellarg($arguments[$i]);
    }
    $command = vsprintf($command, $arguments);
    exec($command, $output, $status);
    return $status;
}

function printHTMLNestedList($collection, $listStart = "<ul>",
        $listEnd = "</ul>", $elementStart = "<li>", $elementEnd = "</li>",
        $attrLeft = "lft", $attrRight = "rgt", $attrName = "name",
        $attrDepth = "depth") {
    echo($listStart);
    foreach($collection as $key => $element) {
        $difference = $element[$attrRight] - $element[$attrLeft];
        if($difference > 1) {
            echo($elementStart . $element[$attrName]);
            echo($listStart);
        }
        else {
            echo($elementStart . $element[$attrName] . $elementEnd);
        }
        if((isset($collection[$key + 1]) &&
            $collection[$key + 1][$attrDepth] < $element[$attrDepth]) ||
            !isset($collection[$key + 1])) {
            for($i = 0; $i < $element[$attrDepth]; $i++) {
                echo($listEnd . $elementEnd);
            }
        }
    }
    echo($listEnd);
}

function generatePagesAlt($numberOfRecords,
        $recordsPerPage, $currentPage = NULL, $prefix = "page-",
        $urlBase = "/", $wrapper = FALSE, $defaultClass, $selectedClassName = "selected",
        $lcp = 5, $rcp = 5, $htmlWrapperContainer="li",
        $htmlContainer = "a", $name = "page",
        $appendix = "") {
        $numberOfPages = ceil($numberOfRecords / $recordsPerPage);
        $paginationString = "";
        $href = "";
    if($htmlContainer == "a") {
        $href = "href=";
    }
    if($currentPage == NULL) {
        $currentPage = gget($name);
    }
    $start = $currentPage - $lcp;
    $start = ($start < 1) ? 1 : $start;
    $end = $currentPage + $rcp;
    $end = ($end > $numberOfPages) ? $numberOfPages : $end;
    for($i = $start; $i <= $end; $i++) {
        $class = NULL;
        if($currentPage == $i) {
            $class = 'class="' . $selectedClassName . '"';
        }
        else {
            $class = 'class="' . $defaultClass . '"';
        }
        if($wrapper) {
            $paginationString .= "<$htmlWrapperContainer $class>";
        }   
        $paginationString .= "<$htmlContainer $class $href\"$urlBase" .
        "$prefix$i$appendix\">$i</$htmlContainer>";
        if($wrapper) {
            $paginationString .= "</$htmlWrapperContainer>";
        }
    }
    echo($paginationString);
    return $numberOfPages;
}

function generatePages($numberOfRecords,
        $recordsPerPage, $currentPage = NULL, $prefix = "page-",
        $urlBase = "/", $selectedClassName = "selected",
        $lcp = 5, $rcp = 5, $htmlWrapperContainer="li",
        $htmlContainer = "a", $name = "page",
        $appendix = "") {
    $numberOfPages = ceil($numberOfRecords / $recordsPerPage);
    $paginationString = "";
    $href = "";
    if($htmlContainer == "a") {
            $href = "href=";
    }
    if($currentPage == NULL) {
            $currentPage = gget($name);
    }
    $start = $currentPage - $lcp;
    $start = ($start < 1) ? 1 : $start;
    $end = $currentPage + $rcp;
    $end = ($end > $numberOfPages) ? $numberOfPages : $end;
    for($i = $start; $i <= $end; $i++) {
            $class = NULL;
            if($currentPage == $i) {
                    $class = 'class="' . $selectedClassName . '"';
            }
            $paginationString .= "<$htmlWrapperContainer $class>" .
            "<$htmlContainer $href\"$urlBase" .
            "$prefix$i$appendix\">$i</$htmlContainer>" .
            "</$htmlWrapperContainer>";

    }
    echo($paginationString);
}


function removeSpaces($string, $replacement) {
    $trim = trim($string);
    $trim = preg_replace("/\s+/", $replacement, $trim);
    return $trim;
}

function prepareStringForEmail($string, $replacement = ".",
    $charClasses = "[-@]") {
    $string = removeSpaces($string, $replacement);
    $string = preg_replace("/\\$replacement*($charClasses+)\\$replacement*/",
        "$1", $string);
    return strtolower(replaceWithAsciiEquivalents($string));
}

function hexStringToDec($string) {
    return preg_replace_callback("/\\\([0-9A-Fa-f]{2})/", 
        function($matches) {
            return chr(hexdec($matches[0]));
        }, 
        $string
    );
}
?>
