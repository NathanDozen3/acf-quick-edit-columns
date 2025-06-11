<?php
/**
 * ACF Quick Edit Columns - Column Output Callbacks
 *
 * This file contains output formatting callbacks for each supported ACF field type in the posts list table columns.
 * Each function is registered to a filter of the form 'acf_quick_edit_columns_{field_type}'.
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
 * Output for text fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function text_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	return esc_html( $value ? $value : '—' );
}
add_filter( 'acf_quick_edit_columns_text', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_textarea', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_wysiwyg', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_email', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_url', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_oembed', __NAMESPACE__ . '\text_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_number', __NAMESPACE__ . '\text_output', 10, 4 );

/**
 * Output for array fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function array_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) ) {
		return esc_html( implode( ', ', array_map( 'strval', $value ) ) ? implode( ', ', array_map( 'strval', $value ) ) : '—' );
	}
	return esc_html( $value ? $value : '—' );
}
add_filter( 'acf_quick_edit_columns_select', __NAMESPACE__ . '\array_output', 10, 4 );
add_filter( 'acf_quick_edit_columns_checkbox', __NAMESPACE__ . '\array_output', 10, 4 );

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
	return '—';
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
	return '—';
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
	return '—';
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
	return '—';
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
 */
function password_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_string( $value ) && ! empty( $value ) ) {
		// For security reasons, we do not display the actual password.
		return str_repeat( '*', strlen( $value ) );
	}
	// If the field is empty or not a string, return a placeholder.
	return '—';
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
	return '—';
}
add_filter( 'acf_quick_edit_columns_file', __NAMESPACE__ . '\file_output', 10, 4 );

/**
 * Output for gallery fields.
 *
 * @param string $output     The default output.
 * @param int    $post_id    The post ID.
 * @param string $field_name The field name.
 * @param string $field_type The field type.
 * @return string The formatted output.
 */
function gallery_output( $output, $post_id, $field_name, $field_type ) {
	$value = get_field( $field_name, $post_id );
	if ( is_array( $value ) && ! empty( $value ) ) {
		if ( is_array( $value[0] ) ) {
			$images = array_map(
				function( $image ) {
					return '<img src="' . esc_url( $image['url'] ) . '" style="max-width: 50px; height: auto;" alt="' . esc_attr( ! empty( $image['title'] ) ? $image['title'] : 'Image' ) . '" data-image-id="' . esc_attr( $image['ID'] ) . '">';
				},
				$value
			);
			return implode( ' ', $images );
		} else {
			return esc_html( implode( ', ', array_map( 'strval', $value ) ) ? implode( ', ', array_map( 'strval', $value ) ) : '—' );
		}
	}
	return '—';
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
		return esc_html( implode( ', ', $titles ) ? implode( ', ', $titles ) : '—' );
	}
	return '—';
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
		return esc_html( get_the_title( $value ) ? get_the_title( $value ) : '—' );
	}
	return '—';
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
	return '—';
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
	return '—';
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
		return esc_html( implode( ', ', $terms ) ? implode( ', ', $terms ) : '—' );
	}
	return '—';
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
	return '—';
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
	return '—';
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
	return '—';
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
	return '—';
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
	return '—';
}
add_filter( 'acf_quick_edit_columns_clone', __NAMESPACE__ . '\clone_output', 10, 4 );