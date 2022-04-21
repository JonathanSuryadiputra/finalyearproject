<?php
// Gravity Forms is a WordPress form creation plugin
add_action( 'gform_after_submission_91', 'register_form', 10, 2 ); // action hook after the submission of form ID 91 from Gravity Forms
/**
 * Function below is tied to the above action hook registers a Gravity Form that is intended to be plugged to the sentiment analysis engine.
 * This only allows the form to be listed under the selection boxes in the sentiment data visualisation embed page.
 * To actually connect the form to the sentiment analysis engine, the plug code must be copied and the form ID on the action hook and
 * the a variable in the function must be changed to the new form to be connected.
 */
function register_form($entry, $form) {
	global $wpdb; // instantiate the WordPress DB object.
	// get the required values submitted from the input field entries
	$form_id = $entry['1'];
	$form_name = $entry['2'];
	// push the GF form ID and the GF form name to the WordPress' MySQL database.
	$wpdb->insert('wpqc_sentimentforms', array(
		'form_id' => $form_id,
		'form_name' => $form_name
	), array('%d', '%s'));
}
?>