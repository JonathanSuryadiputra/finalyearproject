<?php
// This code snippet below is located in ProjectOneSky's WP backend in its functions.php file

// In order to plug a new Gravity Form to the sentiment analysis model, copy the following code below, and simply change/swap out the form ID on the action hook (the number integer after 'gform after_submission_') as well as the integer assigned to the $form_id variable to the new Gravity Form's form ID.
add_action( 'gform_after_submission_89', 'analyse_sentiment', 10, 2 );
function analyse_sentiment($entry, $form) {
	$form_id = 89; // form ID of the form connecting to the sentiment analysis engine
	global $wpdb; // initialise the WordPress DB object
	// get the group ID
	$results = $wpdb->get_results("SELECT group_id FROM wpqc_students WHERE student_id = '" . get_current_user_id() . "'");
	$group_id = $results[0]->group_id;
	/**
	 * for each of the fields in the form, get the sentiment weight using the HTTP API endpoint accessing the model deployed on Google Cloud Platform.
	 * Once the sentiment is returned as a JSON response, extract the value and push the response text, question text, as well as the form ID and sentiment
	 * value to the WordPress' MySQL database.
	 */
	foreach ( $form['fields'] as $field ) {
		if ($field->type == 'textarea') {
			$text = rgar( $entry, (string) $field->id );
			// make GET request using the API endpoint with the response text as the HTTP parameter 'text'.
			$response = file_get_contents("https://sentiment-ykmvrmi3zq-nw.a.run.app/analyse?text=" . urlencode($text));
			$jsonobj = json_decode($response);
			$sentiment_weight = $jsonobj->{'sentiment'};
			// insert query with the sentiment weight, response text and question text as well as the associated form ID.
			$wpdb->insert('wpqc_student_sentiments', array(
				'student_id' => get_current_user_id(),
				'question' => $field->label,
				'question_id' => $field->id,
				'response' => $text,
				'sentiment_weight' => $sentiment_weight,
				'form_id' => $form_id,
				'group_id' => $group_id
			), array('%d', '%s', '%d', '%s', '%d', '%d'));
		}
    }
}
?>