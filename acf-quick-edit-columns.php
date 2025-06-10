<?php
/**
 * Plugin Name: ACF Quick Edit Columns
 * Plugin URI: https://github.com/NathanDozen3/acf-quick-edit-columns
 * Description: Adds ACF fields as columns and Quick Edit fields for custom post types in the WordPress admin, with pre-populated values.
 * Version: 1.5.4
 * Author: Twelve Three Media
 * Author URI: https://www.digitalmarketingcompany.com/
 * License: GPL-2.0+
 * Text Domain: acf-quick-edit-columns
 */

declare(strict_types=1);

namespace AcfQuickEditColumns;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

require __DIR__ . '/inc/class-fieldvalueformatter.php';
require __DIR__ . '/inc/column-callbacks.php';
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/register-ajax.php';

/**
 * Add a filter to make ACF fields sortable in the admin edit screen for all custom post types.
 */
function add_sortable_columns_filter_for_all_custom_post_types(): void {
	if ( ! is_admin() ) {
		return;
	}
	foreach ( get_custom_post_types() as $post_type ) {
		add_filter(
			"manage_edit-{$post_type->name}_sortable_columns",
			__NAMESPACE__ . '\\manage_edit_sortable_columns'
		);
	}
}
add_action('init', __NAMESPACE__ . '\\add_sortable_columns_filter_for_all_custom_post_types', 20);

/**
 * Get all public custom post types.
 *
 * @param array<string, mixed> $columns Array of columns to modify.
 * @return array<\WP_Post_Type> Array of public custom post type objects.
 */
function manage_edit_sortable_columns( array $columns ): array {
	$post_type = get_sanitized_post_type();
	foreach( array_keys( get_acf_fields_by_post_type( $post_type ) ) as $key) {
		$columns[$key] = $key;
	}
	return $columns;
}

/**
 * Load plugin text domain for translations.
 */
function load_textdomain(): void {
	load_plugin_textdomain('acf-quick-edit-columns', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', __NAMESPACE__ . '\\load_textdomain');

/**
 * Enqueue scripts and styles for the admin area.
 *
 * @since 1.0.0
 */
function enqueue_scripts( string $hook ): void {
	if ($hook !== 'edit.php') {
		return;
	}

	wp_enqueue_media(); // Enqueue media library scripts
	wp_enqueue_style(
		'acf-quick-edit',
		plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.css',
		[],
		'1.5.4' // Updated version
	);
	
	wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
	wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);

	wp_enqueue_script(
		'acf-quick-edit',
		plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.js',
		['jquery', 'inline-edit-post', 'media-editor', 'select2'],
		'1.5.4',
		true
	);

	$cpt_fields = get_custom_post_types_and_acf_fields();
	$post_type = get_sanitized_post_type();
	if (!isset($cpt_fields[$post_type])) {
		return;
	}
	$field_data = [];
	foreach ($cpt_fields[$post_type] as $column_key => $field) {
		$field_data[$column_key] = [
			'field_name' => $field['field_name'],
			'type' => $field['type'],
		];
	}
	wp_localize_script('acf-quick-edit', 'acfQuickEdit', [
		'fields' => $field_data,
		'nonce' => wp_create_nonce('acf_quick_edit_nonce'),
		'ajaxurl' => admin_url('admin-ajax.php'),
	]);
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts');

/**
 * Save ACF fields from Quick Edit.
 *
 * This function handles saving ACF field values when a post is saved via Quick Edit.
 * It verifies permissions, nonce, and sanitizes input before updating the fields.
 *
 * @param int $post_id The ID of the post being saved.
 * @param \WP_Post $post The post object being saved.
 */
function save_post( int $post_id, \WP_Post $post ): void {
	$post_type = $post->post_type;
	$fields = get_acf_fields_by_post_type($post_type);

	try {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			error_log("ACF Quick Edit Columns: Autosave detected for post {$post_id}, exiting");
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			error_log("ACF Quick Edit Columns: User lacks permission to edit post {$post_id}");
			return;
		}
		if (!isset($_POST['acf_quick_edit_nonce']) || !wp_verify_nonce($_POST['acf_quick_edit_nonce'], 'acf_quick_edit_nonce')) {
			error_log("ACF Quick Edit Columns: Nonce verification failed for post {$post_id}, nonce: " . ($_POST['acf_quick_edit_nonce'] ?? 'missing'));
			return;
		}

		error_log("ACF Quick Edit Columns: Saving Quick Edit data for post {$post_id}, post type {$post->post_type}");

		foreach ($fields as $column_key => $field) {
			$field_name = $field['field_name'] ?? '';
			$field_type = $field['type'] ?? '';
			$input_name = "acf_{$field_name}";

			if (empty($field_name) || empty($field_type)) {
				error_log("ACF Quick Edit Columns: Invalid field data for column {$column_key} in post {$post_id}");
				continue;
			}

			if (!isset($_POST[$input_name])) {
				error_log("ACF Quick Edit Columns: Field {$field_name} not found in POST data for post {$post_id}");
				continue;
			}

			$value = $_POST[$input_name];
			$acf_field = acf_get_field($field_name);

			if (!$acf_field) {
				error_log("ACF Quick Edit Columns: ACF field {$field_name} not found for post {$post_id}");
				continue;
			}

			// Clear empty values
			if ($value === '' || ($field_type === 'select' && empty($value)) || ($field_type === 'checkbox' && empty($value))) {
				$sanitized_value = in_array($field_type, ['checkbox', 'select', 'post_object', 'relationship', 'taxonomy']) ? [] : '';
				error_log("ACF Quick Edit Columns: Clearing field {$field_naxme} (type: {$field_type}) for post {$post_id}");
				update_field($field_name, $sanitized_value, $post_id);
				continue;
			}

			// Sanitize based on field type
			switch ($field_type) {
				case 'text':
				case 'radio':
				case 'date_picker':
					$sanitized_value = sanitize_text_field($value);
					break;

				case 'textarea':
				case 'wysiwyg':
					$sanitized_value = sanitize_textarea_field($value);
					break;

				case 'select':
					$sanitized_value = !empty($acf_field['multiple'])
						? (is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)])
						: sanitize_text_field($value);
					break;

				case 'checkbox':
					$sanitized_value = is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)];
					break;

				case 'image':
				case 'file':
					$sanitized_value = absint($value);
					break;

				case 'url':
					$sanitized_value = esc_url_raw($value);
					break;

				case 'email':
					$sanitized_value = sanitize_email($value);
					break;

				case 'number':
					$sanitized_value = is_numeric($value) ? floatval($value) : '';
					break;

				case 'true_false':
					$sanitized_value = $value ? 1 : 0;
					break;

				case 'post_object':
				case 'relationship':
				case 'taxonomy':
					$sanitized_value = is_array($value) ? array_map('absint', $value) : absint($value);
					break;

				default:
					$sanitized_value = sanitize_text_field($value);
					error_log("ACF Quick Edit Columns: Default sanitization for unknown field type {$field_type} for field {$field_name}");
					break;
			}

			error_log("ACF Quick Edit Columns: Saving {$field_type} field {$field_name} for post {$post_id}, value: " . print_r($sanitized_value, true));
			$result = update_field($field_name, $sanitized_value, $post_id);
			error_log("ACF Quick Edit Columns: Save result for {$field_name}: " . ($result ? 'Success' : 'Failed'));
		}

		// Clear transient
		error_log("ACF Quick Edit Columns: Clearing transient after save for post {$post_id}");
		delete_transient('acf_quick_edit_columns_fields');
	} catch (\Exception $e) {
		error_log("ACF Quick Edit Columns: Fatal error in save logic for post {$post_id}: {$e->getMessage()}");
	}
}
add_action('save_post', 'AcfQuickEditColumns\\save_post', 10, 2);