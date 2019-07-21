<?php

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
    public $settings;
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
    public function emptyFieldValues() {
        foreach($this->formFields as $formField) {
            $formField->value = "";
        }
    }
    protected function uniqueLDAPEntry($ldap, $filter, $validator,
                $arguments, $notUnique, $count, $value, $field) {
        if($validator != NULL) {
            $arguments[] = $value;
            $arguments[] = $field;
            $result = call_user_func_array(array($this, $validator), $arguments);
            if(!$result) {
                return FALSE;
            }
        }
        $values = array();
        for($i = 0; $i < $count; $i++) {
            $values[] = $value;
        }
        $filter = vsprintf($filter, $values);
        $count = $ldap->countFilterResults($filter);
        if($count > 0 && $notUnique != NULL) {
            $field->messageInvalid = $notUnique;
            return FALSE;
        }
        else {
            return TRUE;
        }
    }
    protected function regexValidator($expression, $value, $formField) {
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
