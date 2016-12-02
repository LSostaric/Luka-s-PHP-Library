<?php

/*
*
*       Copyright (C) 2010 Luka Sostaric. Luka's PHP Library is
*       distributed under the terms of the GNU General Public
*       License.
*
*       This file is part of Luka's PHP Library.
*
*       It is free software: You can redistribute and/or modify
*       it under the terms of the GNU General Public License, as
*       published by the Free Software Foundation, either version
*       3 of the License, or (at your option) any later version.
*
*       It is distributed in the hope that it will be useful,
*       but WITHOUT ANY WARRANTY; without even the implied warranty
*       of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*       See the GNU General Public License for more details.
*
*       You should have received a copy of the GNU General Public
*       License along with Luka's PHP Library. If not, see
*       <http://www.gnu.org/licenses/>.
*
*       Software Information
*       --------------------
*       Software Name: Luka's PHP Library
*       File Name: Form.php
*       External Components Used: None
*       Required Files: None
*       License: GNU GPL
*
*       Author Information
*       ------------------
*       Full Name: Luka Sostaric
*       E-mail: <luka@lukasostaric.com>
*       Website: <http://lukasostaric.com>
*
*/

class FormField {

  private $mandatory;
  protected $valid = TRUE;
  private $value;
  private $messageEmpty;
  private $messageInvalid;
  private $validator;
  private $arguments;
  private $errorMessage;

  protected function __construct($value, $mandatory,
  $messageInvalid, $messageEmpty, $validator, $arguments) {

    $this->mandatory = $mandatory;
    $this->value = $value;
    $this->messageEmpty = $messageEmpty;
    $this->messageInvalid = $messageInvalid;
    $this->validator = $validator;
    $this->arguments = $arguments;

  }

  public function setValid($flag) {

    $this->valid = $flag;

  }

  public function &__get($name) {

    return $this->$name;

  }

  public function __set($name, $value) {

    $this->$name = $value;

  }

}

class Form extends FormField {

  protected $formFields = array();
  protected $valid = TRUE;

  final public function __set($name, $value) {

    error_log("Cannot set property called '$name' " .
    "due to its access modifier!");

  }

  public function __construct($validationSettings, $data = NULL) {

    foreach($validationSettings as $setting) {

      $this->formFields[] = new FormField((isset($data)) ?
      $data[$setting["name"]] : pget($setting["name"]),
      $setting["mandatory"], $setting["messageInvalid"],
      $setting["messageEmpty"], $setting["validator"],
      $setting["arguments"]);

    }

  }

  public function validate() {

    foreach($this->formFields as $formField) {

      if($formField->mandatory && $formField->value == "") {

        $formField->valid = FALSE;
        $formField->errorMessage = $formField->messageEmpty;
        $this->valid = FALSE;

      }
      elseif($formField->value != "") {

        if($formField->validator != NULL) {

          $formField->arguments[] = $formField->value;
          $formField->arguments[] = $formField;

          $formField->valid = call_user_func_array(array($this,
          $formField->validator), $formField->arguments);

        }
        else {

          $formField->valid = TRUE;

        }

        if(!$formField->valid) {

          $formField->errorMessage = $formField->messageInvalid;
          $this->valid = FALSE;

        }

      }

    }

  }

  public function setValid($flag) {

    $this->valid = $flag;

  }

  protected function uniqueLDAPEntry($ldap, $filter, $validator,
  $arguments, $notUnique, $count, $value, $field) {

    if($validator != NULL) {

      $arguments[] = $value;
      $result = call_user_func_array(array($this, $validator), $arguments);

      if(!$result) {

        return FALSE;

      }

    }

    for($i = 0; $i < $count; $i++) {

      $values[] = $value;

    }

    $filter = vsprintf($filter, $values);
    $count = $ldap->countFilterResults($filter);

    if($count > 0) {

      $field->messageInvalid = $notUnique;

      return FALSE;

    }
    else {

      return TRUE;

    }

  }

  protected function regexValidator($expression, $value) {

    $result = preg_match($expression, $value);

    if($result === 0) {

      return FALSE;

    }
    elseif($result === 1) {

      return TRUE;

    }
    else {

      return 2;

    }

  }

}

?>
