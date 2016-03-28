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
*	File Name: UtilityFunctions.php
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

function sanitize_string($string) {

	$searches = array('&quot;', '!', '@', '#', '$', '%', '^',
	'&', '*', '(', ')', '+', '{', '}', '|', ':', '"',
	'<', '>', '?', '[', ']', '', ';', "'", ',', '.',
	'_', '/', '*', '+', '~', '`', '=', ' ' ,'---', '--', '--');

	$replacements = array('', '-', '-', '', '' ,'', '-', '-', '',
	'', '', '', '', '', '', '-', '', '', '', '', '', '',
	'', '', '-', '-', '', '-', '-', '', '', '', '', '',
	'-', '-', '-', '-');

	$sanitized_string = str_replace($searches, $replacements, $string);

	return $sanitized_string;

}

function markdown_to_html($markdown) {

	preg_match_all("/\((\d+)\)\[(.+)\]/", $markdown, $numbers);

	$searchings = array("/\((\d+)\)\[(.+)\]/", "/(.+?)(\n|\r\n)=+/",
	"/(.+?)(\n|\r\n)-+/", "/(\n|\r\n){2}(.+)/", "/\*\*(.+?)\*\*/",
	"/\*(.+?)\*/");

	$replacements = array("", "<h1>$1</h1>", "<h2>$1</h2>",
	"<p>$2</p>", "<strong>$1</strong>", "<em>$1</em>");

	$html = preg_replace($searchings, $replacements, $markdown);

	foreach($numbers[1] as $key => $number) {

		$searchings[$key] = "/\[(.+)\]\(".$number."\)/";
		$replacements[$key] = "<a href=\"".$numbers[2][$key]."\">$1</a>";

	}

	array_push($searchings, "/  \* (.+)(\n|\r\n)/",
	"/(<li>.+<\/li>)/", "/  \d+\. (.+)(\n|\r\n)/",
	"/(<li1>.+<\/li1>)/", "/<li1>(.+?)<\/li1>/", "/---/");

	array_push($replacements, "<li>$1</li>", "<ul>$1</ul>",
	"<li1>$1</li1>", "<ol>$1</ol>", "<li>$1</li>","&mdash;");
	$html = preg_replace($searchings, $replacements, $html);

	return $html;

}

function decompose_file_path($path) {

	$last_dot = strrpos($path, ".");
	$extension = substr($path, $last_dot + 1);
	$last_slash = strrpos($path, "/");
	$file_name = substr($path, $last_slash + 1, $last_dot - $last_slash - 1);
	$directory = substr($path, 0, $last_slash + 1);

	$result = array("extension" => $extension, "file_name" => $file_name,
	"directory" => $directory);

	return $result;

}

function get_string_between($string, $start, $end, $trim=TRUE) {

	$results = array();
	$offset = strpos($string, $start);
	$start_length = strlen($start);
	$end_length = strlen($end);

	while( $offset !== FALSE ) {

		$start_index = strpos($string, $start, $offset);
		$end_index = strpos($string, $end, $offset+$start_length);

		if($trim) {

			$result = trim(substr($string, $start_index + $start_length,
			$end_index-$start_index - $start_length));

		}
		else {

			$result = substr($string, $start_index + $start_length,
			$end_index-$start_index - $start_length);

		}

		array_push($results, $result);
		$offset = strpos($string, $start, $end_index + $end_length);

	}

	if(empty($results)) {

		$results[0] = $string;

	}

	return $results;

}

function trim_array_of_strings(&$array) {

	if(!is_array($array)) {

		return trim($array);

	}

	if(!empty($array)) {

		foreach($array as $key => $element) {

			$array[$key] = trim($element);

		}

	}

}

function perform_file_upload($field_name, $destination,
$debug = FALSE) {

	if(ini_get("display_errors") === "1") {

		$debug = TRUE;

	}

	if(is_array($_FILES[$field_name]["name"])) {

		for($i = 0; $i < count($_FILES[$field_name]["name"]); $i++) {

			$temporary_name = $_FILES[$field_name]["tmp_name"][$i];
			$name = $_FILES[$field_name]["name"][$i];
			move_uploaded_file($temporary_name, "$destination/$name");

		}

	}
	else {

		$temporary_name = $_FILES[$field_name]["tmp_name"];
		$name = $_FILES[$field_name]["name"];
		move_uploaded_file($temporary_name, "$destination/$name");

	}

	$outcome = $_FILES[$field_name]["error"];

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

function get_string_after_substring($string, $substring) {

	$substring_position = strpos($string, $substring);
	return substr($string, $substring_position + 1);

}

function get_string_before_substring($string, $substring) {

	$substring_position = strpos($string, $substring);
	return substr($string, 0, $substring_position + 1);

}

function replaceWithAsciiEquivalents($string) {

	$searches = array('Š', 'Đ', 'Č', 'Ć', 'Ž', 'š',
	'đ', 'č', 'ć', 'ž');

	$replacements = array('S', 'D', 'C', 'C', 'Z', 's',
	'd', 'c', 'c', 'z');

	return str_replace($searches, $replacements, $string);

}

function execute_external_command($command, $arguments,
&$output = array()) {

	array_unshift($arguments, $command);

	for($i = 1; $i < count($arguments); $i++) {

		$arguments[$i] = escapeshellarg($arguments[$i]);

	}

	$command = call_user_func_array("sprintf", $arguments);
	$last_line = exec($command, $output, $status);
	return $status;

}

function print_nested_list($collection) {

	echo("<ul>");

	foreach($collection as $key => $element) {

		$difference = $element["rgt"] - $element["lft"];

		if($difference > 1) {

			echo("<li>" . $element["name"]);
			echo("<ul>");

		}
		else {

			echo("<li>" . $element["name"] . "</li>");

		}

		if((isset($collection[$key + 1]) &&
		$collection[$key + 1]["depth"] < $element["depth"]) ||
		!isset($collection[$key + 1])) {

			for($i = 0; $i < $element["depth"]; $i++) {

				echo("</ul></li>");

			}

		}

	}

	echo("</ul>");

}

function generate_pages($number_of_records,
$records_per_page, $html_container = "a", $prefix = "page-",
$name = "page", $url_base = "/", $appendix = "",
$selected_class_name = "selected") {

	$number_of_pages = ceil($number_of_records / $records_per_page);
	$pagination_string = "";

	if($html_container == "a") {

		$href = "href=";

	}

	for($i = 1; $i <= $number_of_pages; $i++) {

		$class = NULL;

		if(gget($name) == $i) {

			$class = "class=$selected_class_name";

		}

		$pagination_string.= "<$html_container $class $href\"$url_base" .
		"$prefix$i$appendix\">$i</$html_container>";

	}

	echo($pagination_string);

}

?>
