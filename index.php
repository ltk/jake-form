<style type="text/css">
	.form-error {
		border:1px solid red;
	}
</style>
<?php


$fields_array = array(
	'email' => array(
		'type' => 'email',
		'label' => 'Email Address',
		'required' => true,
		'required_msg' => 'Please include your email address.',
		'validation_msg' => 'The email address you entered is invalid.',
		//'value_src' => '_POST'
		),
	'phone' => array(
		'type' => 'phone',
		'label' => 'Phone Number',
		'required' => true,
		//'required_msg' => 'Please include your phone number.',
		//'validation_msg' => 'The phone number you entered is invalid.',
		//'value_src' => 'TEST'
		),
	'zip' => array(
		'type' => 'zip',
		'label' => 'Zip Code',
		'required' => true,
		//'required_msg' => 'Please include your phone number.',
		//'validation_msg' => 'The phone number you entered is invalid.',
		//'value_src' => 'TEST'
		),
	'amount' => array(
		'type' => 'numeric',
		'label' => 'Amount',
		'required' => false,
		//'required_msg' => 'Please include your phone number.',
		//'validation_msg' => 'The phone number you entered is invalid.',
		//'value_src' => 'TEST'
		),
	'checkers' => array(
		'type' => 'checkbox',
		'label' => 'Checkers',
		'required' => true,
		//'required_msg' => 'Please include your phone number.',
		//'validation_msg' => 'The phone number you entered is invalid.',
		//'value_src' => 'TEST'
		),


	);

$form_options = array(
	'submit' => array(
		'value' => 'Send it!'
		)
	);

include( "form.class.php" );

$form = new FormCreator($fields_array, $form_options);

if ( ! empty( $_POST ) ) {

	if ($form->valid) {
		echo "<h1>Huzzah! A valid submission!!</h1>";
		echo "<p>The email field's value is: '" . $form->field_value('email') . "'.</p>";
		echo "<p>The phone field's value is: '" . $form->field_value('phone') . "'.</p>";

	} else {
		foreach($form->errors as $error){
			echo "<p>$error</p>";
		}
	}
}
$form->output_html();

?>
<!-- 
<form method="post" action="">
	<input type="hidden" name="submit" value="1" />
	<input type="text" name="email" placeholder="Email Address" value="<?php echo $form->field_value('email') ?>" />
	<input type="text" name="phone" placeholder="Phone Number" value="<?php echo $form->field_value('phone') ?>" />
	<input type="text" name="zip" placeholder="Zip" value="<?php echo $form->field_value('zip') ?>" />
	<input type="text" name="amount" placeholder="Amount" value="<?php echo $form->field_value('amount') ?>" />
	<input type="hidden" name="checkers" value="false" />
	<input type="checkbox" name="checkers"  />

	<input type="submit" />
</form> -->