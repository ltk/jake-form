<?php

class Form {
        public $valid;
        public $errors;
        private $fields;
        private $validation_errors;

        public function __construct( $fields_array ) {
            try {
                if( empty($fields_array) ){ throw new Exception; }
                
                $this->fields = array();
                foreach( $fields_array as $field_name => $field_data ) {
                    $new_field = new Field($field_name, $field_data);
                    $this->fields[$field_name] = $new_field;
                }
            } catch ( Exception $e ) {
                trigger_error('The fields array must not be empty.', E_ERROR);
            }            
            
            
            if(!empty($fields_array)){
                $this->fields = array();
                foreach( $fields_array as $field_name => $field_data ){
                    $new_field = new Field($field_name, $field_data);
                    $this->fields[$field_name] = $new_field;
                }
            } else {
                
            }
            
            $validity = $this->check_form_data();
            if( $validity !== true ) {
                $this->valid = false;
                $this->errors = $validity;
            } else {
                $this->valid = true;
            }
        }

        private function check_form_data() {
            $errors = array();

            foreach ( $this->fields as $field ) {
                $validity = $field->check_field_data();
                if ( $validity !== true) {
                    $errors[$field->name] = $validity;
                }
            }
            return ( empty( $errors ) ) ? true : $errors;
        }
        
        public function field_value( $field_name ){
            if( array_key_exists($field_name, $this->fields) ){
                return $this->fields[$field_name]->clean_value;
            } else {
                return false;
            }
        }
}



class Field {
        public $name;
        public $type;
        public $label;
        private $value_src;
        private $raw_value;
        public $clean_value;
        public $required;
        public $required_msg;
        public $validation_msg;
        
        const regex_phone = "^(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$^";

        public function __construct( $field_name, $field_options ) {
            $this->name = $field_name;
            
            foreach( $field_options as $field_option_key => $field_option_value ) {
                $this->{$field_option_key} = $field_option_value;  
            }
            $this->required_msg = ($this->required_msg) ? $this->required_msg : "Please complete the " . $this->label . " field."; 
            $this->validation_msg = ($this->validation_msg) ? $this->validation_msg : "The ".$this->label." you entered is invalid.";
            $this->value_src = (!$this->value_src) ? '_POST' : $this->value_src;
            
            global ${$this->value_src};
            $this->raw_value = ( ${$this->value_src}[$this->name] ) ? ${$this->value_src}[$this->name] : null;
            
            $this->clean_value = $this->sanitized_value();
        }
        
        private function sanitized_value(){
            $raw = trim($this->raw_value);
            $raw = htmlspecialchars($raw);
            $sanitized = filter_var($raw, FILTER_SANITIZE_STRING); 
            // $sanitized = mysql_real_escape_string($raw);
            
            return $sanitized;    
        }
        
        public function check_field_data() {
            $validity = $this->required_check();
            return ( $validity === true ) ? $this->validate() : $validity;
        }

        private function required_check() {
            return ( ($this->required === true && $this->present() === true) || $this->required === false )  ? true : $this->required_msg;
        }
        
        private function present() {
            return ( empty( $this->clean_value ) ) ? false : true;
        }

        private function validate() {
            if ( ! $this->present() ) { 
                return true; 
            }
            try {
                if( !method_exists( $this, "validate_".$this->type ) ) { 
                    throw new Exception; 
                }
                return call_user_func( array($this, "validate_".$this->type) );
            } catch ( Exception $e ) {
                trigger_error('The validation function "validate_' . $this->type . '" does not exist.', E_USER_NOTICE);
                return false;
            }       
        }
        
        private function validate_email() {
            return ( filter_var( $this->clean_value, FILTER_VALIDATE_EMAIL ) ) ? true : $this->validation_msg;
        }
        
        private function validate_phone() {
            return ( preg_match( self::regex_phone, $this->clean_value ) ) ? true : $this->validation_msg;
        }
}

if(!$_POST['submit']){
    ?>
    <form method="post" action="">
        <input type="hidden" name="submit" value="1" />
        <input type="text" name="email" placeholder="Email Address" />
        <input type="text" name="phone" placeholder="Phone Number" />

        <input type="submit" />
    </form>
    <?php
} else {
    $TEST = array(
        'email' => 'tybruffy@gmail.com<script>alert("sup");</script>',
        'phone' => '3014768373'
        );

    $fields_array = array(
        'email' => array(
            'type' => 'email',
            'label' => 'email address',
            'required' => true,
            'required_msg' => 'Please include your email address.',
            'validation_msg' => 'The email address you entered is invalid.',
            //'value_src' => '_POST'
            ),
        'phone' => array(
            'type' => 'phone',
            'label' => 'phone number',
            'required' => true,
            //'required_msg' => 'Please include your phone number.',
            //'validation_msg' => 'The phone number you entered is invalid.',
            //'value_src' => 'TEST'
            ) 
        );

    $form = new Form($fields_array);

    if ($form->valid) {
        echo "<h1>Huzzah! A valid submission!!</h1>";
        echo "<p>The email field's value is: '" . $form->field_value('email') . "'.</p>";
        echo "<p>The phone field's value is: '" . $form->field_value('phone') . "'.</p>";

    } else {
        foreach($form->errors as $error){
            echo "<p>$error</p>";
        }
    }
    echo "<a href='/'>Reset</a>";
}







?>