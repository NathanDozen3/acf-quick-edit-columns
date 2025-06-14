<?php
/**
 * Output formatting callbacks for ACF Quick Edit Columns admin columns.
 *
 * Each function is registered to a filter of the form 'acf_quick_edit_columns_{field_type}'.
 * Handles escaping, formatting, and special cases for all supported field types.
 * See also: FieldValueFormatter for AJAX prefill logic.
 *
 * @package   AcfQuickEditColumns
 * @author    Nathan Johnson
 * @copyright 2024 Nathan Johnson
 * @license   GPL-2.0-or-later
 * @since     2.0.0
 */

declare(strict_types=1);

namespace AcfQuickEditColumns;

use function get_field;
use function esc_html;
use function esc_url;
use function esc_attr;
use function wp_kses_post;
use function date_i18n;
use function is_array;
use function implode;

/**
 * Get a consistent, translatable placeholder for empty/unsupported values.
 *
 * @return string
 */
function acf_qec_placeholder(): string {
	return esc_html__('—', 'acf-quick-edit-columns');
}

/**
 * Output for text fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 *
 * Example: Output for text/email/textarea fields is plain text, escaped for HTML.
 */
function text_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	return esc_html( $value ? $value : acf_qec_placeholder() );
}
add_filter( 'acf_quick_edit_columns_text', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_textarea', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_email', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_url', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_oembed', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_number', __NAMESPACE__ . '\text_output', 10, 4 );

/**
 * Helper to format array values for output (used by select, checkbox, relationship, taxonomy, gallery, etc.).
 *
 * @param mixed $value The field value (array or string).
 * @return string
 */
function acf_qec_format_array_output( $value ) {
	if ( is_array( $value ) ) {
		// Join array values with comma, escape each for HTML
		return esc_html( implode( ', ', array_map( 'strval', $value ) ) );
	}
	return esc_html( $value ? $value : acf_qec_placeholder() );
}

/**
 * Output for array-based fields (select, checkbox, relationship, taxonomy, gallery fallback).
 * Uses the shared helper for DRY code.
 */
function array_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	return acf_qec_format_array_output( $value );
}
add_filter( 'acf_quick_edit_columns_select', __NAMESPACE__ . '\array_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_checkbox', __NAMESPACE__ . '\array_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_relationship', __NAMESPACE__ . '\array_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_taxonomy', __NAMESPACE__ . '\array_output', 10, 4 );

/**
 * Output for image fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function image_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value['url'] ) ) {
		return '<img src="' . esc_url( $value['url'] ) . '" style="max-width: 50px; height: auto;" alt="' . esc_attr( ! empty( $value['title'] ) ? $value['title'] : 'Image' ) . '" data-image-id="' . esc_attr( $value['ID'] ) . '">';
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_image', __NAMESPACE__ . '\image_output', 10, 4 );

/**
 * Output for date fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function date_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $value ) ) );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_date_picker', __NAMESPACE__ . '\date_output', 10, 4 );

/**
 * Output for datetime fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function datetime_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $value ) ) );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_datetime_picker', __NAMESPACE__ . '\datetime_output', 10, 4 );

/**
 * Output for time fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function time_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return esc_html( date_i18n( get_option( 'time_format' ), strtotime( $value ) ) );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_time_picker', __NAMESPACE__ . '\time_output', 10, 4 );

/**
 * Output for password fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 *
 * Example: Output for password fields is masked with asterisks for security.
 */
function password_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_string( $value ) && ! empty( $value ) ) {
		// For security reasons, we do not display the actual password.
		return str_repeat( '*', strlen( $value ) );
	}
	// If the field is empty or not a string, return a placeholder.
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_password', __NAMESPACE__ . '\password_output', 10, 4 );

/**
 * Output for file fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function file_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value['url'] ) ) {
		return '<a href="' . esc_url( $value['url'] ) . '" target="_blank">' . esc_html( ! empty( $value['filename'] ) ? $value['filename'] : 'File' ) . '</a>';
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_file', __NAMESPACE__ . '\file_output', 10, 4 );

/**
 * Output for gallery fields.
 * If value is an array of images, render thumbnails. Otherwise, fallback to array_output.
 */
function gallery_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value ) ) {
		if ( is_array( $value[0] ) && isset( $value[0]['url'] ) ) {
			// Render image thumbnails for each image in the gallery
			$images = array_map(
				function( $image ) {
					return '<img src="' . esc_url( $image['url'] ) . '" style="max-width: 50px; height: auto;" alt="' . esc_attr( ! empty( $image['title'] ) ? $image['title'] : 'Image' ) . '" data-image-id="' . esc_attr( $image['ID'] ) . '">';
				},
				$value
			);
			return implode( ' ', $images );
		}
		// Fallback: treat as array of IDs or strings
		return acf_qec_format_array_output( $value );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_gallery', __NAMESPACE__ . '\gallery_output', 10, 4 );

/**
 * Output for true/false fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function true_false_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	return $value ? esc_html__( 'Yes', 'acf' ) : esc_html__( 'No', 'acf' );
}
add_filter( 'acf_quick_edit_columns_true_false', __NAMESPACE__ . '\true_false_output', 10, 4 );

/**
 * Output for relationship fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function relationship_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value ) ) {
		$titles = array_map(
			function( $post ) {
				return esc_html( get_the_title( $post ) );
			},
			$value
		);
		return esc_html( implode( ', ', $titles ) ? implode( ', ', $titles ) : acf_qec_placeholder() );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_relationship', __NAMESPACE__ . '\relationship_output', 10, 4 );

/**
 * Output for post object fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function post_object_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return esc_html( get_the_title( $value ) ? get_the_title( $value ) : acf_qec_placeholder() );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_post_object', __NAMESPACE__ . '\post_object_output', 10, 4 );

/**
 * Output for page link fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function page_link_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_page_link', __NAMESPACE__ . '\page_link_output', 10, 4 );

/**
 * Output for user fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function user_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value['user_nicename'] ) ) {
		return esc_html( $value['user_nicename'] );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_user', __NAMESPACE__ . '\user_output', 10, 4 );

/**
 * Output for taxonomy fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function taxonomy_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) ) {
		$terms = array_map(
			function( $term ) {
				return esc_html( $term->name );
			},
			$value
		);
		return esc_html( implode( ', ', $terms ) ? implode( ', ', $terms ) : acf_qec_placeholder() );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_taxonomy', __NAMESPACE__ . '\taxonomy_output', 10, 4 );

/**
 * Output for Google Map fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function google_map_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value['address'] ) ) {
		return esc_html( $value['address'] );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_google_map', __NAMESPACE__ . '\google_map_output', 10, 4 );

/**
 * Output for color picker fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function color_picker_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( $value ) {
		return '<span style="background-color:' . esc_attr( $value ) . '; width: 20px; height: 20px; display: inline-block;"></span> ' . esc_html( $value );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_color_picker', __NAMESPACE__ . '\color_picker_output', 10, 4 );

/**
 * Output for repeater fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function repeater_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) ) {
		return esc_html( count( $value ) . ' rows' );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_repeater', __NAMESPACE__ . '\repeater_output', 10, 4 );

/**
 * Output for group fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function group_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) ) {
		return esc_html__( 'Group data', 'acf' );
	}
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_group', __NAMESPACE__ . '\group_output', 10, 4 );

/**
 * Output for clone fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function clone_output( $output, $post_id, $field_name, $field_type ) {
	return acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_clone', __NAMESPACE__ . '\clone_output', 10, 4 );

/**
 * Output for wysiwyg fields (render safe HTML in admin columns).
 *
 * For column output, render formatted HTML. For AJAX prefill, raw value is returned by FieldValueFormatter.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 *
 * Example: Output for wysiwyg fields is safe HTML (wp_kses_post).
 */
function wysiwyg_output( $output, $post_id, $field_name, $field_type ) {
	// For admin column, render safe HTML. For AJAX prefill, see FieldValueFormatter::wysiwyg().
	$value = get_field( $field_name, $post_id );
	return $value ? wp_kses_post( $value ) : acf_qec_placeholder();
}
add_filter( 'acf_quick_edit_columns_wysiwyg', __NAMESPACE__ . '\wysiwyg_output', 10, 4 );