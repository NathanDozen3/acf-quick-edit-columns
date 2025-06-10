<?php
declare(strict_types=1);

namespace AcfQuickEditColumns;

/**
 * AJAX handler for fetching ACF field value for Quick Edit.
 * 
 * This function retrieves the value of a specified ACF field for a given post ID.
 * It verifies the nonce for security, checks if the post ID and field name are valid,
 * and returns the field value in a JSON response.
 * 
 * @since 1.0.0
 * @return void
 * @throws \WP_Error If nonce verification fails or if the post ID or field name is invalid.
 * @throws \Exception If there is an error retrieving the field value.
 */
function ajax_get_field(): void {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acf_quick_edit_nonce' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ACF Quick Edit Columns: Nonce verification failed, nonce: ' . ( $_POST['nonce'] ?? 'missing' ) );
		}
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
	}

	$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$field_name = isset( $_POST['field_name'] ) ? sanitize_text_field( $_POST['field_name'] ) : '';

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "ACF Quick Edit Columns: Fetching field {$field_name} for post {$post_id}" );
	}

	if ( ! $post_id || ! $field_name ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ACF Quick Edit Columns: Invalid post ID or field name' );
		}
		wp_send_json_error( array( 'message' => 'Invalid post ID or field name' ) );
	}

	$acf_quick = new FieldValueFormatter( $field_name, $post_id );
	$value     = $acf_quick->get_value();

	$response = array( 'value' => $value );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "ACF Quick Edit Columns: Sending response for {$field_name}: " . print_r( $response, true ) );
	}
	wp_send_json_success( $response );
}
add_action( 'wp_ajax_acf_quick_edit_get_field', __NAMESPACE__ . '\\ajax_get_field' );

/**
 * AJAX handler for searching posts in ACF Quick Edit.
 *
 * This function handles the search requests for post objects in ACF fields.
 * It verifies the nonce, checks the field type, and returns search results.
 * 
 * @since 1.0.0
 * @return void
 * @throws \WP_Error If nonce verification fails or if the field is invalid.
 */
function search_posts() : void {
	$nonce = $_REQUEST['nonce'] ?? '';
	error_log("ACF Quick Edit Columns: Post search nonce received: {$nonce}");

	if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($nonce, 'acf_quick_edit_nonce')) {
		error_log("ACF Quick Edit Columns: Nonce verification failed for post search, nonce: {$nonce}");
		wp_send_json_error(['message' => 'Invalid nonce']);
	}

	$field_name = isset($_REQUEST['field_name']) ? sanitize_text_field($_REQUEST['field_name']) : '';
	$search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

	if (!$field_name) {
		error_log('ACF Quick Edit Columns: Missing field name for post search');
		wp_send_json_error(['message' => 'Missing field name']);
	}

	$acf_field = acf_get_field($field_name);
	if (!$acf_field || $acf_field['type'] !== 'post_object') {
		error_log("ACF Quick Edit Columns: Invalid or non-post_object field {$field_name}");
		wp_send_json_error(['message' => 'Invalid field']);
	}

	$post_types = get_post_types( array( 'public' => true ) );
	if ( ! in_array( 'attachment', $post_types, true ) ) {
		$post_types[] = 'attachment';
	}
	$args = [
		'post_type' => $post_types,
		'post_status'    => array( 'publish', 'inherit' ),
		'posts_per_page' => 20,
		's' => $search,
	];

	$query = new \WP_Query($args);
	$results = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_type = get_post_type();
		$post_type_obj = get_post_type_object( $post_type );
		$post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $post_type );

		if ( ! isset( $results[ $post_type_label ] ) ) {
			$results[ $post_type_label ] = array();
		}

		$results[ $post_type_label ][] = array(
			'id'    => get_the_ID(),
			'text'  => get_the_title() ?: '(no title)',
		);
	}
	wp_reset_postdata();

	// Format for Select2 optgroups
	$select2_results = array();
	foreach ( $results as $label => $posts ) {
		$select2_results[] = array(
			'text'     => $label,
			'children' => $posts,
		);
	}

	error_log("ACF Quick Edit Columns: Post search for field {$field_name}, results: " . print_r($select2_results, true));
	wp_send_json_success(['results' => $select2_results]);
}
add_action('wp_ajax_acf_quick_edit_search_posts', __NAMESPACE__ . '\\search_posts');
