<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
/**
 * GMW main shortcode displaying the form elements
 * 
 * @param  shortcode attributes ( form, search_form, map, search_results... with form ID as the value. ex. form="1" )
 * @return [type]   [description]
 */
function gmw_shortcode( $attr ) {

	// abort if no shortcode attribute provided!
	if ( empty( $attr ) ) {

		echo trigger_error( 'Shortcode attributes are missing.', E_USER_NOTICE );

		return;
	}
		
	$_GET = apply_filters( 'gmw_modify_get_args', $_GET );

	// get the first attribute of the shortcode.
	// the first attribute must be the element ( form, search_form, map or search_results ).
	$element = key( $attr );

	// get the form ID from the shortcode attrbute
	$element_value = $attr[$element];

	$status = 'ok';

	// verify that the element is lagit
	if ( empty( $element_value ) ) {

		$status = 'error';

		$error_message = __( 'Invalid or missing form type.', 'GMW' );

		return;
	}

	$url_px = gmw_get_url_prefix();

	// if this is results page we get the form ID from URL
	if ( $element_value == 'results' || $element == 'search_results' ) {


		// abort if form was not submitted
		if ( ! isset( $_GET[$url_px.'form'] ) ) {

			$status = 'results page was not submitted.';

			return;
		}

		// get the form ID from URL
		$form_id = absint( $_GET[$url_px.'form'] );

		// abort if search_results shortcode is being used but does not belong
		// to the submitted search form.
		if ( $element == 'search_results' && $element_value != $form_id ) {

			$status = 'results page does not match.';

			return;
		}

		// set the element as results page
		$element = 'search_results';

	// otherwise, get the form ID from shortcode attribute value
	} else {

		// verify the form ID
	 	$form_id = absint( $attr[$element] );
	}

	// get form data
	$form = gmw_get_form( $form_id );

	// abort if form was not found
	if ( empty( $form ) ) {

		$status = 'error';

		$error_message = __( 'Form does not exist.', 'GMW' );

		return;
	}

	// Abort if the add-on this form belongs to is deactivated
	if ( ! gmw_is_addon_active( $form['addon'] ) ) {

		$status = 'error';

		$error_message = __( 'The add-on which this form belongs to is deactivated.', 'GMW' );

		return;
	}
	
	ob_start();

	// if form verified
	if ( $status != 'ok' ) {
		
		if ( ! empty( $error_message ) ) {
			trigger_error( $error_message, E_USER_NOTICE );
		}

		return;
	}

	// get the class name of the add-on need to be queried based on its slug
	$class_name = 'GMW_'.$form['slug'].'_Form';
					
	// check if the child class exists
	if ( ! class_exists( $class_name ) ) {

		trigger_error( $class_name . ' class is missing.' , E_USER_NOTICE );

		return;
	}

	$new_form = new $class_name( $attr, $form );

	GMW()->current_form = $new_form->form;

	// output only if element allowed
	if ( $new_form->element_allowed ) {
		//display the form
		$new_form->output();
	}

	$output_form = ob_get_contents();

	ob_end_clean();

	return $output_form;
}
add_shortcode( 'gmw', 'gmw_shortcode' );

/**
 * GMW Function - get single location information
 */
function gmw_get_address_fields_shortcode( $args ) {

	//default shortcode attributes
	$attr = shortcode_atts( array(
		'object_type'	=> '',
		'object_id'     => '',
		'fields' 		=> 'formatted_address',
		'separator' 	=> ', '
	), $args );

	$fields = ! empty( $attr['fields'] ) ? explode( ',', $attr['fields'] ) : array( 'formatted_address' );

	$location = gmw_get_address_fields( $attr['object_type'], $attr['object_id'], $fields, $attr['separator'] );

	return $location;
}
add_shortcode( 'gmw_address_fields', 'gmw_get_address_fields_shortcode' );