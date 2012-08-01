<?php

class Form {
		public $valid;
		public $errors;
		protected $fields;
		protected $validation_errors;

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
			
			$this->errors = array();

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
		const regex_zip = "/^[0-9]{5}$/";

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
			
			$this->convert_checkbox_data();

			$this->clean_value = $this->sanitized_value();
		}
		
		private function convert_checkbox_data() {
			if($this->type == 'checkbox'){
				switch ($this->raw_value) {
					case "true":
					case "1":
					case "on":
						$this->raw_value = true;
						break;
					default:
						$this->raw_value = false;
						break;
				}
			}
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

		private function validate_zip() {
			return ( preg_match( self::regex_zip, $this->clean_value ) ) ? true : $this->validation_msg;
		}

		private function validate_numeric() {
			return ( is_numeric( $this->clean_value ) ) ? true : $this->validation_msg;
		}

		private function validate_checkbox() {
			return true;
		}

}

class FormCreator extends Form {
	private $form_options;
	private $form_html;

	public function __construct($fields_array, $html_options){
		parent::__construct($fields_array);

		foreach($html_options as $html_option_key => $html_option_value){
			$this->{$html_option_key} = $html_option_value;
		}

		

		if(!empty($this->fields)){
			$this->form_html = $this->output_form_start();

			foreach($this->fields as $field){
				$this->form_html .= $this->output_field_html($field);
			}

			$this->form_html .= $this->output_form_end();
		}	

	}

	public function output_html() {
		echo $this->form_html;
	}

	private function output_form_start(){
		return sprintf("<form id='%s' class='%s' method='%s' action='%s'>",
			isset($this->form_options['id']) ? $this->form_options['id'] : 'form',
			isset($this->form_options['class']) ? $this->form_options['class'] : 'form',
			isset($this->form_options['method']) ? $this->form_options['method'] : 'post',
			isset($this->form_options['action']) ? $this->form_options['action'] : ''
			);
	}

	private function output_form_end() {
		return sprintf("<input type='submit' id='%s' class='%s' value='%s' /></form>",
			isset($this->form_options['submit']['id']) ? $this->form_options['submit']['id'] : 'form-submit',
			isset($this->form_options['submit']['class']) ? $this->form_options['submit']['class'] : 'form-submit',
			isset($this->form_options['submit']['value']) ? $this->form_options['submit']['value'] : 'Submit'
			);
	}

	private function output_field_html( $field ){
		try {
			if( !method_exists( $this, "output_field_type_".$field->type ) ) { 
				throw new Exception; 
			}
			return call_user_func( array($this, "output_field_type_".$field->type), $field );
		} catch ( Exception $e ) {
			trigger_error('The field output function "output_field_type_' . $field->type . '" does not exist.', E_USER_NOTICE);
			return false;
		} 
	}

	private function output_field_type_email( $field ){
		return sprintf("<input id='%s' name='%s' class='%s %s' type='text' value='%s' placeholder='%s' />",
			$field->name,
			$field->name,
			$field->class ? $field->class : $field->type,
			array_key_exists($field->name, $this->errors) ? 'form-error' : '',
			$field->clean_value ? $field->clean_value : '',
			$field->label ? $field->label : ''
			);
	}

	private function output_field_type_phone( $field ){
		return sprintf("<input id='%s' name='%s' class='%s %s' type='text' value='%s' placeholder='%s' />",
			$field->name,
			$field->name,
			$field->class ? $field->class : $field->type,
			array_key_exists($field->name, $this->errors) ? 'form-error' : '',
			$field->clean_value ? $field->clean_value : '',
			$field->label ? $field->label : ''
			);
	}

	private function output_field_type_zip( $field ){
		return sprintf("<input id='%s' name='%s' class='%s %s' type='text' value='%s' placeholder='%s' />",
			$field->name,
			$field->name,
			$field->class ? $field->class : $field->type,
			array_key_exists($field->name, $this->errors) ? 'form-error' : '',
			$field->clean_value ? $field->clean_value : '',
			$field->label ? $field->label : ''
			);
	}

	private function output_field_type_numeric( $field ){
		return sprintf("<input id='%s' name='%s' class='%s %s' type='text' value='%s' placeholder='%s' />",
			$field->name,
			$field->name,
			$field->class ? $field->class : $field->type,
			array_key_exists($field->name, $this->errors) ? 'form-error' : '',
			$field->clean_value ? $field->clean_value : '',
			$field->label ? $field->label : ''
			);
	}

	private function output_field_type_checkbox( $field ){
		return sprintf("<input type='hidden' name='%s' value='false' /><input type='checkbox' name='%s' %s />",
			$field->name,
			$field->name,
			$field->clean_value ? "checked='checked'" : '' 
			);
	}

}

?>