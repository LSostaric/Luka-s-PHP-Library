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
*   File Name: Email.php
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
class Email {
    private $subject;
    private $message;
    private $sender;
    private $recipient;
    private $headers;
    public function __construct($subject, $message, $sender,
            $recipient, $headers = NULL) {
        $this->subject = $subject;
        $this->message = $message;
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->headers = $headers;
    }
    public function send() {
        if($this->headers !== NULL) {
            $outcome = mail($this->recipient, $this->subject,
            $this->message, $this->headers);
        }
        else {
            $outcome = mail($this->recipient, $this->subject, $this->message);
        }
        return $outcome;
    }
    public function __get($property) {
        if(function_exists("property_exists")) {
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
    public function setSubject($subject) {
        $this->subject = $subject;
    }
    public function setMessage($message) {
        $this->message = $message;
    }
    public function setSender($sender) {
        $this->sender = $sender;
    }
    public function setRecipient($recipient) {
        $this->recipient = $recipient;
    }
    public function setHeaders($headers) {
        $this->headers = $headers;
    }
}
?>
